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
        'type_text'
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
            
            $timestamp = $data['createtime'];
            
            $model = new self();
            $tableName = $model->getTableName($timestamp);
            $model->ensureTableExists($tableName);
            $model->name = $tableName;
            
            $id = $model->insertGetId($data);
            
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
}
