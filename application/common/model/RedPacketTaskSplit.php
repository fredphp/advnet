<?php

namespace app\common\model;

use think\Db;
use think\Log;

/**
 * 红包任务分表模型
 * 
 * 按月分表，表名格式：red_packet_task_202603
 */
class RedPacketTaskSplit extends SplitTableModel
{
    // 表名（基础表名，不带后缀）
    protected $name = 'red_packet_task';
    
    // 分表类型：按月
    protected $splitType = 'month';
    
    // 分表依据字段
    protected $splitField = 'createtime';
    
    // 主表名
    protected $baseTable = 'red_packet_task';
    
    // 自动写入时间戳
    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    
    // 关闭严格字段检查
    protected $strict = false;
    
    // 追加属性
    protected $append = [
        'status_text',
        'type_text',
        'show_red_packet_text'
    ];
    
    // 状态列表
    public static $statusList = [
        'pending' => '待发送',
        'normal' => '进行中',
        'finished' => '已抢完',
        'expired' => '已过期'
    ];
    
    // 任务类型列表
    public static $typeList = [
        'chat' => '普通聊天',
        'download' => '下载App',
        'miniapp' => '小程序游戏',
        'adv' => '广告时长',
        'video' => '观看视频'
    ];
    
    // 是否显示红包
    public static $showRedPacketList = [
        0 => '否',
        1 => '是'
    ];
    
    public function getStatusTextAttr($value, $data)
    {
        return isset($data['status']) ? self::$statusList[$data['status']] ?? '' : '';
    }
    
    public function getTypeTextAttr($value, $data)
    {
        if (!isset($data['type']) || empty($data['type'])) {
            return '';
        }
        return self::$typeList[$data['type']] ?? $data['type'];
    }
    
    public function getShowRedPacketTextAttr($value, $data)
    {
        return isset($data['show_red_packet']) ? self::$showRedPacketList[$data['show_red_packet']] ?? '' : '';
    }
    
    /**
     * 创建任务（自动路由到正确的表）
     * @param array $data 任务数据
     * @return array
     */
    public static function createTask($data)
    {
        $result = [
            'success' => false,
            'message' => '',
            'data' => null
        ];
        
        try {
            if (!isset($data['createtime'])) {
                $data['createtime'] = time();
            }
            
            // 添加更新时间
            $data['updatetime'] = time();
            
            $timestamp = $data['createtime'];
            
            $model = new self();
            $tableName = $model->getTableName($timestamp);
            $model->ensureTableExists($tableName);
            
            // 使用 Db::name 直接插入到分表
            $id = Db::name($tableName)->insertGetId($data);
            
            $result['success'] = true;
            $result['data'] = [
                'id' => $id,
                'table' => $tableName
            ];
            
        } catch (\Exception $e) {
            $result['message'] = $e->getMessage();
            Log::error('创建红包任务失败: ' . $e->getMessage());
        }
        
        return $result;
    }
    
    /**
     * 关联资源
     */
    public function resource()
    {
        return $this->belongsTo('RedPacketResource', 'resource_id');
    }
    
    /**
     * 获取今日统计
     * @return array
     */
    public static function getTodayStats()
    {
        $todayStart = strtotime(date('Y-m-d'));
        $todayEnd = strtotime(date('Y-m-d') . ' 23:59:59');
        
        $model = new self();
        
        return [
            'today_count' => $model->aggregateSplit($todayStart, $todayEnd, 'count', 'id'),
            'today_pushed_count' => $model->aggregateSplit($todayStart, $todayEnd, 'count', 'id', function($q) {
                return $q->where('push_status', 1);
            }),
            'today_finished_count' => $model->aggregateSplit($todayStart, $todayEnd, 'count', 'id', function($q) {
                return $q->where('status', 'finished');
            }),
        ];
    }
    
    /**
     * 获取本月统计
     * @return array
     */
    public static function getMonthStats($month = null)
    {
        if ($month === null) {
            $month = date('Y-m');
        }
        
        $monthStart = strtotime($month . '-01');
        $monthEnd = strtotime($month . '-' . date('t', $monthStart) . ' 23:59:59');
        
        $model = new self();
        
        return [
            'month_count' => $model->aggregateSplit($monthStart, $monthEnd, 'count', 'id'),
            'month_pushed_count' => $model->aggregateSplit($monthStart, $monthEnd, 'count', 'id', function($q) {
                return $q->where('push_status', 1);
            }),
            'month_finished_count' => $model->aggregateSplit($monthStart, $monthEnd, 'count', 'id', function($q) {
                return $q->where('status', 'finished');
            }),
        ];
    }
    
    /**
     * 获取类型分布
     * @return array
     */
    public static function getTypeDistribution()
    {
        $model = new self();
        $tables = $model->getTableList();
        
        $distribution = [];
        foreach (self::$typeList as $type => $label) {
            $distribution[$type] = [
                'type' => $type,
                'label' => $label,
                'count' => 0,
            ];
        }
        
        foreach ($tables as $table) {
            $data = Db::name($table)
                ->field('type, COUNT(*) as count')
                ->group('type')
                ->select();
            
            foreach ($data as $row) {
                if (isset($distribution[$row['type']])) {
                    $distribution[$row['type']]['count'] += $row['count'];
                }
            }
        }
        
        return array_values($distribution);
    }
    
    /**
     * 跨表分页查询
     * @param int $startTime 开始时间戳
     * @param int $endTime 结束时间戳
     * @param callable|null $callback 查询回调函数
     * @param int $offset 偏移量
     * @param int $limit 每页数量
     * @return array ['total' => 总数, 'rows' => 数据列表]
     */
    public function querySplitWithPaginate($startTime, $endTime, $callback = null, $offset = 0, $limit = 10)
    {
        // 获取时间范围内的所有分表
        $tables = $this->getTableList(
            date('Y-m-d', $startTime),
            date('Y-m-d', $endTime)
        );
        
        // 如果没有分表，返回空结果
        if (empty($tables)) {
            return ['total' => 0, 'rows' => []];
        }
        
        // 先计算总数
        $total = 0;
        foreach ($tables as $table) {
            $query = Db::name($table);
            
            if ($callback) {
                $query = $callback($query);
            }
            
            $query->where($this->splitField, '>=', $startTime)
                  ->where($this->splitField, '<=', $endTime);
            
            $total += $query->count();
        }
        
        // 获取数据
        $rows = [];
        $collected = 0;
        $needCollect = $limit;
        $skipInCurrentTable = $offset;
        
        // 按时间倒序，从最新的分表开始查询
        $tables = array_reverse($tables);
        
        foreach ($tables as $table) {
            if ($collected >= $needCollect) {
                break;
            }
            
            $query = Db::name($table);
            
            if ($callback) {
                $query = $callback($query);
            }
            
            $query->where($this->splitField, '>=', $startTime)
                  ->where($this->splitField, '<=', $endTime);
            
            // 获取当前表的记录数
            $tableCount = $query->count();
            
            // 如果当前表的记录数小于需要跳过的数量，跳过整个表
            if ($tableCount <= $skipInCurrentTable) {
                $skipInCurrentTable -= $tableCount;
                continue;
            }
            
            // 从当前表获取数据
            $data = Db::name($table)
                ->where($this->splitField, '>=', $startTime)
                ->where($this->splitField, '<=', $endTime)
                ->order($this->splitField, 'desc')
                ->limit($skipInCurrentTable, $needCollect - $collected)
                ->select()
                ->toArray();
            
            $skipInCurrentTable = 0;
            
            foreach ($data as $row) {
                $row['_table'] = $table;
                $row['type_text'] = self::$typeList[$row['type']] ?? $row['type'];
                $row['status_text'] = self::$statusList[$row['status']] ?? $row['status'];
                $row['show_red_packet_text'] = ($row['show_red_packet'] ?? 0) ? '是' : '否';
                $rows[] = $row;
                $collected++;
            }
        }
        
        // 加载关联资源
        $resourceIds = array_unique(array_filter(array_column($rows, 'resource_id')));
        $resources = [];
        if (!empty($resourceIds)) {
            $resources = RedPacketResource::where('id', 'in', $resourceIds)->column('*', 'id');
        }
        
        foreach ($rows as &$row) {
            $row['resource'] = isset($resources[$row['resource_id']]) ? $resources[$row['resource_id']] : null;
        }
        unset($row);
        
        // 按时间戳排序
        usort($rows, function($a, $b) {
            return ($b['createtime'] ?? 0) - ($a['createtime'] ?? 0);
        });
        
        return ['total' => $total, 'rows' => $rows];
    }

    /**
     * 判断任务类型是否需要显示红包
     */
    public function shouldShowRedPacket()
    {
        $type = $this->getData('type');
        return $type === 'miniapp' || $this->show_red_packet == 1;
    }

    /**
     * 获取推送数据
     */
    public function getPushData()
    {
        $data = [
            'task_id' => $this->id,
            'task_name' => $this->name,
            'type' => $this->getData('type'),
            'description' => $this->description,
            'display_title' => $this->display_title ?: $this->name,
            'display_description' => $this->display_description ?: $this->description,
            'show_red_packet' => $this->shouldShowRedPacket(),
            'background_image' => '',
            'jump_url' => '',
            'status' => $this->status,
            'sender_name' => $this->sender_name,
            'sender_avatar' => $this->sender_avatar,
            'timestamp' => time(),
        ];

        // 关联资源信息
        if ($this->resource) {
            $resourceData = $this->resource->getData();

            $data['resource'] = [
                'id' => $this->resource->id,
                'name' => isset($resourceData['name']) ? $resourceData['name'] : '',
                'description' => isset($resourceData['description']) ? $resourceData['description'] : '',
                'logo' => isset($resourceData['logo']) ? $resourceData['logo'] : '',
                'images' => isset($resourceData['images']) && $resourceData['images'] ? json_decode($resourceData['images'], true) : [],
                'type' => isset($resourceData['type']) ? $resourceData['type'] : '',
            ];

            // 展示标题优先级
            if (empty($data['display_title'])) {
                $data['display_title'] = isset($resourceData['name']) ? $resourceData['name'] : $this->name;
            }

            // 展示描述优先级
            if (empty($data['display_description'])) {
                $data['display_description'] = isset($resourceData['description']) ? $resourceData['description'] : $this->description;
            }

            // 背景图和跳转链接
            $data['background_image'] = isset($resourceData['logo']) ? $resourceData['logo'] : '';
            $data['jump_url'] = isset($resourceData['url']) ? $resourceData['url'] : '';

            // 根据资源类型添加跳转配置
            $resourceType = isset($resourceData['type']) ? $resourceData['type'] : '';
            switch ($resourceType) {
                case 'chat':
                    $data['resource']['chat_duration'] = isset($resourceData['chat_duration']) ? $resourceData['chat_duration'] : 30;
                    $data['resource']['chat_requirement'] = isset($resourceData['chat_requirement']) ? $resourceData['chat_requirement'] : '';
                    break;
                case 'miniapp':
                case 'mini_program':
                    $data['resource']['miniapp_id'] = isset($resourceData['miniapp_id']) ? $resourceData['miniapp_id'] : (isset($resourceData['app_id']) ? $resourceData['app_id'] : '');
                    $data['resource']['miniapp_path'] = isset($resourceData['miniapp_path']) ? $resourceData['miniapp_path'] : '';
                    $data['resource']['miniapp_duration'] = isset($resourceData['miniapp_duration']) ? $resourceData['miniapp_duration'] : 0;
                    break;
                case 'download':
                case 'download_app':
                    $data['resource']['download_url'] = isset($resourceData['download_url']) ? $resourceData['download_url'] : (isset($resourceData['url']) ? $resourceData['url'] : '');
                    $data['resource']['download_type'] = isset($resourceData['download_type']) ? $resourceData['download_type'] : '';
                    $data['resource']['package_name'] = isset($resourceData['package_name']) ? $resourceData['package_name'] : '';
                    if (empty($data['jump_url'])) {
                        $data['jump_url'] = isset($resourceData['url']) ? $resourceData['url'] : (isset($resourceData['download_url']) ? $resourceData['download_url'] : '');
                    }
                    break;
                case 'adv':
                    $data['resource']['adv_id'] = isset($resourceData['adv_id']) ? $resourceData['adv_id'] : '';
                    $data['resource']['adv_platform'] = isset($resourceData['adv_platform']) ? $resourceData['adv_platform'] : '';
                    $data['resource']['adv_duration'] = isset($resourceData['adv_duration']) ? $resourceData['adv_duration'] : 0;
                    break;
                case 'video':
                case 'watch_video':
                    $data['resource']['video_url'] = isset($resourceData['video_url']) ? $resourceData['video_url'] : '';
                    $data['resource']['video_duration'] = isset($resourceData['video_duration']) ? $resourceData['video_duration'] : 0;
                    break;
            }
        } else {
            // 普通聊天任务没有关联资源时，使用任务描述作为聊天内容
            $taskType = $this->getData('type');
            if ($taskType === 'chat') {
                $data['chat_content'] = $this->description ?: '';
                $data['chat_duration'] = 30;
            }
        }

        return $data;
    }
}
