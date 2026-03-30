<?php

namespace app\admin\controller\redpacket;

use app\common\controller\Backend;
use app\common\model\RedPacketTask as RedPacketTaskModel;
use app\common\model\RedPacketTaskSplit;
use app\common\model\RedPacketResource;
use app\common\library\WebSocketService;
use app\common\model\User;
use think\Db;
use think\Exception;

/**
 * 红包任务管理
 */
class Task extends Backend
{
    /**
     * RedPacketTask模型对象（主表）
     */
    protected $model = null;

    /**
     * RedPacketTaskSplit模型对象（分表）
     */
    protected $splitModel = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new RedPacketTaskModel;
        $this->splitModel = new RedPacketTaskSplit;

        // 搜索字段映射
        $this->searchFields = ['name', 'display_title', 'description', 'sender_name'];

        // 类型列表
        $typeList = RedPacketTaskSplit::$typeList;
        $this->view->assign('typeList', $typeList);

        // 状态列表
        $statusList = RedPacketTaskSplit::$statusList;
        $this->view->assign('statusList', $statusList);

        // 是否显示红包（使用主模型的静态属性）
        $showRedPacketList = RedPacketTaskModel::$showRedPacketList;
        $this->view->assign('showRedPacketList', $showRedPacketList);

        // 资源列表
        $resourceList = RedPacketResource::where('status', 'normal')->column('id,name,type');
        $this->view->assign('resourceList', $resourceList);
    }

    /**
     * 系统会员选择接口（供 selectpage 调用）
     * 只返回 user_type=1 的系统会员
     */
    public function systemUsers()
    {
        $this->request->filter(['strip_tags', 'trim']);

        // 兼容 selectpage 插件发送的参数格式：
        // searchField[]=nickname  &  nickname=xxx  或  q_word[]=xxx
        $searchFields = $this->request->request('searchField/a', []);
        $search = '';
        if (!empty($searchFields)) {
            foreach ($searchFields as $field) {
                $val = $this->request->request($field, '');
                if ($val !== '' && $val !== null) {
                    $search = $val;
                    break;
                }
            }
        }
        // 兜底：直接读 search 参数
        if ($search === '') {
            $search = $this->request->request('search', '');
        }

        $pageNumber = intval($this->request->request('pageNumber', 1));
        $pageSize = intval($this->request->request('pageSize', 20));
        if ($pageNumber < 1) $pageNumber = 1;
        if ($pageSize < 1) $pageSize = 20;
        if ($pageSize > 100) $pageSize = 100;

        // 直接查库，绕过 User 模型的 $append 干扰
        $prefix = \think\Db::getConfig('prefix');
        $query = \think\Db::name('user')
            ->where('user_type', 1)
            ->where('status', 'normal');

        if ($search) {
            $query->where('nickname|username', 'like', '%' . $search . '%');
        }

        $total = $query->count();
        $list = $query
            ->field('id,nickname,username,avatar')
            ->order('id', 'asc')
            ->page($pageNumber, $pageSize)
            ->select();

        return json(['total' => $total, 'list' => $list]);
    }

    /**
     * 查看
     */
    public function index()
    {
        // 设置过滤方法
        $this->request->filter(['strip_tags', 'trim']);

        if ($this->request->isAjax()) {
            // 如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }

            list($where, $sort, $order, $offset, $limit) = $this->buildparams();

            // 默认按ID排序，避免sort字段不存在的问题
            if ($sort == 'sort') {
                $sort = 'id';
            }

            // 获取时间筛选条件
            $filter = $this->request->get('filter', '');
            $filterData = json_decode($filter, true);
            
            // 检查是否有 createtime 时间范围筛选
            $hasTimeFilter = false;
            $startTime = null;
            $endTime = null;
            
            if (isset($filterData['createtime']) && !empty($filterData['createtime'])) {
                $timeRange = $filterData['createtime'];
                // 解析时间范围 (格式: 开始时间 - 结束时间)
                if (strpos($timeRange, ' - ') !== false) {
                    $times = explode(' - ', $timeRange);
                    if (count($times) == 2) {
                        $startTime = strtotime(trim($times[0]));
                        $endTime = strtotime(trim($times[1]) . ' 23:59:59');
                        $hasTimeFilter = true;
                    }
                }
            }

            // 数据库表前缀
            $prefix = config('database.prefix');
            
            if ($hasTimeFilter && $startTime && $endTime) {
                // 有时间筛选条件，跨分表查询
                $total = 0;
                $rows = [];
                
                // 计算需要查询的分表
                $currentMonth = strtotime(date('Y-m-01', $startTime));
                $endMonth = strtotime(date('Y-m-01', $endTime));
                
                $tablesToQuery = [];
                while ($currentMonth <= $endMonth) {
                    $suffix = date('Ym', $currentMonth);
                    $tableName = $prefix . 'red_packet_task_' . $suffix;
                    // 检查表是否存在
                    $exists = Db::query("SHOW TABLES LIKE '{$tableName}'");
                    if (!empty($exists)) {
                        $tablesToQuery[] = 'red_packet_task_' . $suffix;
                    }
                    $currentMonth = strtotime('+1 month', $currentMonth);
                }
                
                // 如果没有找到分表，返回空结果
                if (empty($tablesToQuery)) {
                    return json(['total' => 0, 'rows' => []]);
                }
                
                // 计算总数
                foreach ($tablesToQuery as $table) {
                    $query = Db::name($table);
                    if (is_callable($where)) {
                        $query->where($where);
                    }
                    $query->where('createtime', '>=', $startTime)
                          ->where('createtime', '<=', $endTime);
                    $total += $query->count();
                }
                
                // 分页获取数据
                $collected = 0;
                $skipCount = $offset;
                $needCollect = $limit;
                
                // 从最新的分表开始查询
                $tablesToQuery = array_reverse($tablesToQuery);
                
                foreach ($tablesToQuery as $table) {
                    if ($collected >= $needCollect) {
                        break;
                    }
                    
                    $countQuery = Db::name($table);
                    if (is_callable($where)) {
                        $countQuery->where($where);
                    }
                    $countQuery->where('createtime', '>=', $startTime)
                              ->where('createtime', '<=', $endTime);
                    $tableCount = $countQuery->count();
                    
                    if ($tableCount <= $skipCount) {
                        $skipCount -= $tableCount;
                        continue;
                    }
                    
                    $query = Db::name($table);
                    if (is_callable($where)) {
                        $query->where($where);
                    }
                    $query->where('createtime', '>=', $startTime)
                          ->where('createtime', '<=', $endTime)
                          ->order($sort, $order)
                          ->limit($skipCount, $needCollect - $collected);
                    
                    $data = $query->select();
                    // 安全地转换为数组
                    if (is_object($data)) {
                        $data = $data->toArray();
                    } elseif (!is_array($data)) {
                        $data = [];
                    }
                    $skipCount = 0;
                    
                    foreach ($data as $row) {
                        $row['_table'] = $table;
                        $rows[] = $row;
                        $collected++;
                    }
                }
                
                // 按时间戳排序
                usort($rows, function($a, $b) use ($sort, $order) {
                    $aVal = $a[$sort] ?? 0;
                    $bVal = $b[$sort] ?? 0;
                    return $order === 'desc' ? ($bVal - $aVal) : ($aVal - $bVal);
                });
                
            } else {
                // 没有时间筛选，默认查询当月分表
                $suffix = date('Ym');
                $tableName = 'red_packet_task_' . $suffix;
                $fullTableName = $prefix . $tableName;
                
                // 检查分表是否存在，不存在则创建
                $exists = Db::query("SHOW TABLES LIKE '{$fullTableName}'");
                if (empty($exists)) {
                    // 检查主表是否存在
                    $mainTable = $prefix . 'red_packet_task';
                    $mainExists = Db::query("SHOW TABLES LIKE '{$mainTable}'");
                    if (!empty($mainExists)) {
                        // 创建分表
                        Db::execute("CREATE TABLE IF NOT EXISTS `{$fullTableName}` LIKE `{$mainTable}`");
                    }
                }
                
                // 计算总数
                $countQuery = Db::name($tableName);
                if (is_callable($where)) {
                    $countQuery->where($where);
                }
                $total = $countQuery->count();
                
                // 获取数据
                $query = Db::name($tableName);
                if (is_callable($where)) {
                    $query->where($where);
                }
                $data = $query->order($sort, $order)
                    ->limit($offset, $limit)
                    ->select();
                
                // 安全地转换为数组
                if (is_object($data)) {
                    $rows = $data->toArray();
                } elseif (is_array($data)) {
                    $rows = $data;
                } else {
                    $rows = [];
                }
            }
            
            // 加载关联资源
            $resourceIds = array_unique(array_filter(array_column($rows, 'resource_id')));
            $resources = [];
            if (!empty($resourceIds)) {
                $resources = RedPacketResource::where('id', 'in', $resourceIds)->column('*', 'id');
            }
            
            // 处理数据
            $typeList = RedPacketTaskSplit::$typeList;
            $statusList = RedPacketTaskSplit::$statusList;
            
            foreach ($rows as &$row) {
                $row['resource'] = isset($resources[$row['resource_id']]) ? $resources[$row['resource_id']] : null;
                $row['type_text'] = $typeList[$row['type']] ?? $row['type'];
                $row['status_text'] = $statusList[$row['status']] ?? $row['status'];
                $row['show_red_packet_text'] = ($row['show_red_packet'] ?? 0) ? '是' : '否';
                // 添加月份字段，用于操作按钮传递参数
                $row['_month'] = isset($row['createtime']) ? date('Ym', $row['createtime']) : date('Ym');
            }
            unset($row);

            $result = ['total' => $total, 'rows' => $rows];
            return json($result);
        }

        return $this->view->fetch();
    }

    /**
     * 添加
     */
    public function add()
    {
        if ($this->request->isPost()) {
            $params = $this->request->post('row/a');
            if ($params) {
                $params = $this->preExcludeFields($params);

                if ($this->dataLimit && $this->dataLimitFieldAutoFill) {
                    $params[$this->dataLimitField] = $this->auth->id;
                }

                $result = false;
                Db::startTrans();
                try {
                    // 设置发送者信息：优先使用选择的系统会员，否则使用默认系统信息
                    $senderId = intval($params['sender_id'] ?? 0);
                    if ($senderId > 0) {
                        $senderUser = User::where('id', $senderId)->where('user_type', 1)->find();
                        if ($senderUser) {
                            $params['sender_id'] = $senderUser['id'];
                            $params['sender_name'] = $senderUser['nickname'] ?? $senderUser['username'];
                            $params['sender_avatar'] = $senderUser['avatar'] ?? '';
                        } else {
                            $params['sender_id'] = 0;
                            $params['sender_name'] = '系统';
                            $params['sender_avatar'] = '';
                        }
                    } else {
                        $params['sender_id'] = 0;
                        $params['sender_name'] = '系统';
                        $params['sender_avatar'] = '';
                    }

                    // 默认值
                    if (!isset($params['show_red_packet'])) {
                        // 如果是小程序游戏类型，默认显示红包
                        $type = $params['type'] ?? 'chat';
                        $params['show_red_packet'] = ($type === 'miniapp') ? 1 : 0;
                    }

                    // 使用分表模型插入数据
                    $createResult = RedPacketTaskSplit::createTask($params);
                    if (!$createResult['success']) {
                        throw new Exception($createResult['message'] ?: '创建失败');
                    }
                    $result = true;

                    Db::commit();
                } catch (Exception $e) {
                    Db::rollback();
                    $this->error($e->getMessage());
                }

                if ($result !== false) {
                    $this->success();
                } else {
                    $this->error(__('No rows were inserted'));
                }
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }

        return $this->view->fetch();
    }

    /**
     * 编辑
     */
    public function edit($ids = null)
    {
        // 获取请求中的月份参数，默认当月
        $month = $this->request->get('month', date('Ym'));
        $tableName = 'red_packet_task_' . $month;

        // 直接使用 Db 查询分表
        $rowData = Db::name($tableName)->where('id', $ids)->find();

        if (!$rowData) {
            $this->error(__('No Results were found'));
        }

        // 检查是否已发送，已发送的不允许修改
        if ($rowData['push_status'] == 1) {
            $this->error('该任务已发送，不允许修改');
        }

        $adminIds = $this->getDataLimitAdminIds();
        if (is_array($adminIds)) {
            if (!in_array($rowData[$this->dataLimitField], $adminIds)) {
                $this->error(__('You have no permission'));
            }
        }

        if ($this->request->isPost()) {
            $params = $this->request->post('row/a');
            if ($params) {
                $params = $this->preExcludeFields($params);
                $result = false;
                Db::startTrans();
                try {
                    $result = Db::name($tableName)->where('id', $ids)->update($params);
                    Db::commit();
                } catch (Exception $e) {
                    Db::rollback();
                    $this->error($e->getMessage());
                }
                if ($result !== false) {
                    $this->success();
                } else {
                    $this->error(__('No rows were updated'));
                }
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }

        // 加载关联资源 - 确保始终设置 resource 字段
        if (!empty($rowData['resource_id'])) {
            $resource = RedPacketResource::where('id', $rowData['resource_id'])->find();
            if ($resource) {
                $resourceData = $resource->toArray();
                // 添加类型文本
                $typeList = RedPacketResource::$typeList;
                $resourceData['type_text'] = $typeList[$resourceData['type']] ?? $resourceData['type'];
                $rowData['resource'] = $resourceData;
            } else {
                $rowData['resource'] = null;
            }
        } else {
            $rowData['resource'] = null;
        }

        // 添加类型文本
        $taskTypeList = RedPacketTaskSplit::$typeList;
        $rowData['type_text'] = $taskTypeList[$rowData['type']] ?? $rowData['type'];

        $this->view->assign('row', $rowData);
        return $this->view->fetch();
    }

    /**
     * 发送预览页面
     */
    public function send($ids = null)
    {
        // 获取请求中的月份参数，默认当月
        $month = $this->request->get('month', date('Ym'));
        $tableName = 'red_packet_task_' . $month;

        // 直接使用 Db 查询分表
        $rowData = Db::name($tableName)->where('id', $ids)->find();

        if (!$rowData) {
            $this->error(__('No Results were found'));
        }

        // 已发送的任务不能再发送
        if ($rowData['push_status'] == 1) {
            $this->error('该任务已发送，请勿重复发送');
        }

        // 加载关联资源
        if (!empty($rowData['resource_id'])) {
            $rowData['resource'] = RedPacketResource::where('id', $rowData['resource_id'])->find();
        } else {
            $rowData['resource'] = null;
        }

        // 添加文本字段
        $typeList = RedPacketTaskSplit::$typeList;
        $statusList = RedPacketTaskSplit::$statusList;
        $rowData['type_text'] = $typeList[$rowData['type']] ?? $rowData['type'];
        $rowData['status_text'] = $statusList[$rowData['status']] ?? $rowData['status'];
        $rowData['show_red_packet_text'] = ($rowData['show_red_packet'] ?? 0) ? '是' : '否';

        // 获取发送数据预览
        $sendData = $this->buildPushData($rowData);

        // 根据任务类型获取聊天内容
        $chatContent = '';
        if ($rowData['type'] === 'chat') {
            if ($rowData['resource']) {
                // 有关联资源，使用资源的聊天要求
                $chatContent = $rowData['resource']['chat_requirement'] ?? ($rowData['description'] ?? '');
            } else {
                // 无关联资源，使用任务描述作为聊天内容
                $chatContent = $rowData['description'] ?? '';
            }
        }

        // JSON数据在PHP中处理
        $sendDataJson = json_encode($sendData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        $this->view->assign('row', $rowData);
        $this->view->assign('sendData', $sendData);
        $this->view->assign('sendDataJson', $sendDataJson);
        $this->view->assign('chatContent', $chatContent);

        return $this->view->fetch();
    }

    /**
     * 执行发送
     */
    public function doSend($ids = null)
    {
        // 获取请求中的月份参数，默认当月
        $month = $this->request->post('month', date('Ym'));
        $tableName = 'red_packet_task_' . $month;

        // 直接使用 Db 查询分表
        $rowData = Db::name($tableName)->where('id', $ids)->find();

        if (!$rowData) {
            $this->error(__('No Results were found'));
        }

        // 已发送的任务不能再发送
        if ($rowData['push_status'] == 1) {
            $this->error('该任务已发送，请勿重复发送');
        }

        try {
            // 加载关联资源
            if (!empty($rowData['resource_id'])) {
                $rowData['resource'] = RedPacketResource::where('id', $rowData['resource_id'])->find();
            }
            
            // 调用推送服务
            $pushData = $this->buildPushData($rowData);
            $pushResult = $this->sendPushNotification($pushData);

            if ($pushResult['success']) {
                // 更新推送状态
                Db::name($tableName)->where('id', $ids)->update([
                    'push_status' => 1,
                    'push_time' => time(),
                    'status' => 'normal',
                    'updatetime' => time()
                ]);

                $this->success('发送成功', null, $pushResult);
            } else {
                $this->error('发送失败: ' . ($pushResult['message'] ?? '未知错误'));
            }
        } catch (Exception $e) {
            $this->error('发送失败: ' . $e->getMessage());
        }
    }

    /**
     * 推送红包
     */
    public function push($ids = null)
    {
        // 获取请求中的月份参数，默认当月
        $month = $this->request->post('month', date('Ym'));
        $tableName = 'red_packet_task_' . $month;

        // 直接使用 Db 查询分表
        $rowData = Db::name($tableName)->where('id', $ids)->find();

        if (!$rowData) {
            $this->error(__('No Results were found'));
        }

        if ($rowData['status'] != 'normal' && $rowData['status'] != 'pending') {
            $this->error('该红包任务状态不允许推送');
        }

        try {
            // 加载关联资源
            if (!empty($rowData['resource_id'])) {
                $rowData['resource'] = RedPacketResource::where('id', $rowData['resource_id'])->find();
            }
            
            // 调用推送服务
            $pushData = $this->buildPushData($rowData);
            $pushResult = $this->sendPushNotification($pushData);

            if ($pushResult['success']) {
                // 更新推送状态
                Db::name($tableName)->where('id', $ids)->update([
                    'push_status' => 1,
                    'push_time' => time(),
                    'status' => 'normal',
                    'updatetime' => time()
                ]);

                $this->success('推送成功', null, $pushResult);
            } else {
                $this->error('推送失败: ' . ($pushResult['message'] ?? '未知错误'));
            }
        } catch (Exception $e) {
            $this->error('推送失败: ' . $e->getMessage());
        }
    }

    /**
     * 发送推送通知（纯 TCP，不走 HTTP）
     * 通过 fsockopen 直连 Swoole 内部 TCP 端口发送推送指令
     */
    protected function sendPushNotification($data)
    {
        $host = '127.0.0.1';
        $port = 3003;
        $timeout = 5;
        
        $fp = @fsockopen($host, $port, $errno, $errstr, $timeout);
        if (!$fp) {
            return ['success' => false, 'message' => "无法连接推送服务({$host}:{$port}): [{$errno}] {$errstr}"];
        }
        
        stream_set_timeout($fp, $timeout);
        
        // 构造 TCP 推送指令（以换行符结尾）
        $payload = json_encode([
            'action' => 'push_task',
            'api_key' => WebSocketService::API_KEY,
            'data' => $data
        ], JSON_UNESCAPED_UNICODE) . "\n";
        
        fwrite($fp, $payload);
        
        // 读取响应（服务端返回 JSON + \n 后关闭连接）
        // 用 fgets 按行读取，自动等待数据到达
        $response = '';
        while (!feof($fp)) {
            $line = fgets($fp, 65536);
            if ($line === false) {
                break;
            }
            $response .= $line;
            // 读到有效内容就退出（服务端发送完立即 close）
            if (strlen(trim($line)) > 0) {
                break;
            }
        }
        
        fclose($fp);
        
        $result = json_decode(trim($response), true);
        if (!$result) {
            return ['success' => false, 'message' => '推送服务响应解析失败, raw: ' . mb_substr($response, 0, 200)];
        }
        
        return $result;
    }

    /**
     * 删除
     */
    public function del($ids = '')
    {
        if (!$this->request->isPost()) {
            $this->error(__('Invalid parameters'));
        }

        $ids = $ids ? $ids : $this->request->post('ids');
        if ($ids) {
            // 获取请求中的月份参数，默认当月
            $month = $this->request->post('month', date('Ym'));
            $tableName = 'red_packet_task_' . $month;
            
            $adminIds = $this->getDataLimitAdminIds();
            $query = Db::name($tableName)->where('id', 'in', $ids);
            if (is_array($adminIds)) {
                $query->where($this->dataLimitField, 'in', $adminIds);
            }

            $count = 0;
            Db::startTrans();
            try {
                $count = $query->delete();
                Db::commit();
            } catch (Exception $e) {
                Db::rollback();
                $this->error($e->getMessage());
            }

            if ($count) {
                $this->success();
            } else {
                $this->error(__('No rows were deleted'));
            }
        }
        $this->error(__('Parameter %s can not be empty', 'ids'));
    }

    /**
     * 任务详情
     */
    public function detail($ids = null)
    {
        // 获取请求中的月份参数，默认当月
        $month = $this->request->get('month', date('Ym'));
        $tableName = 'red_packet_task_' . $month;

        // 直接使用 Db 查询分表
        $rowData = Db::name($tableName)->where('id', $ids)->find();

        if (!$rowData) {
            $this->error(__('No Results were found'));
        }

        // 加载关联资源 - 确保始终设置 resource 字段
        if (!empty($rowData['resource_id'])) {
            $rowData['resource'] = RedPacketResource::where('id', $rowData['resource_id'])->find();
        } else {
            $rowData['resource'] = null;
        }

        // 添加文本字段
        $typeList = RedPacketTaskSplit::$typeList;
        $statusList = RedPacketTaskSplit::$statusList;
        $rowData['type_text'] = $typeList[$rowData['type']] ?? $rowData['type'];
        $rowData['status_text'] = $statusList[$rowData['status']] ?? $rowData['status'];
        $rowData['show_red_packet_text'] = ($rowData['show_red_packet'] ?? 0) ? '是' : '否';

        // 获取领取记录
        $records = \app\common\model\UserRedPacketAccumulate::where('task_id', $ids)
            ->with(['user'])
            ->order('id', 'desc')
            ->limit(20)
            ->select();

        // 统计信息
        $stats = [
            'total_records' => \app\common\model\UserRedPacketAccumulate::where('task_id', $ids)->count(),
            'total_amount_sent' => \app\common\model\UserRedPacketAccumulate::where('task_id', $ids)->where('is_collected', 1)->sum('total_amount'),
        ];

        $this->view->assign('row', $rowData);
        $this->view->assign('records', $records);
        $this->view->assign('stats', $stats);

        return $this->view->fetch();
    }

    /**
     * 构建推送数据
     */
    protected function buildPushData($rowData)
    {
        // 获取CDN域名，优先使用配置的cdnurl，否则使用当前请求域名
        $cdnDomain = \think\Config::get('upload.cdnurl');
        if (empty($cdnDomain)) {
            $cdnDomain = $this->request->domain();
        }

        $data = [
            'task_id' => $rowData['id'],
            'task_name' => $rowData['name'] ?? '',
            'type' => $rowData['type'] ?? '',
            'description' => $rowData['description'] ?? '',
            'display_title' => $rowData['display_title'] ?? ($rowData['name'] ?? ''),
            'display_description' => $rowData['display_description'] ?? ($rowData['description'] ?? ''),
            'show_red_packet' => ($rowData['type'] ?? '') === 'miniapp' || ($rowData['show_red_packet'] ?? 0) == 1,
            'background_image' => '',
            'jump_url' => '',
            'status' => $rowData['status'] ?? '',
            'sender_id' => $rowData['sender_id'] ?? 0,
            'sender_name' => $rowData['sender_name'] ?? '',
            'sender_avatar' => cdnurl($rowData['sender_avatar'] ?? '', true),
            'timestamp' => time(),
        ];

        // 如果有 sender_id，尝试查用户表获取最新的头像信息
        $senderId = intval($rowData['sender_id'] ?? 0);
        if ($senderId > 0) {
            $senderUser = User::where('id', $senderId)->find();
            if ($senderUser) {
                $data['sender_id'] = $senderUser['id'];
                $data['sender_name'] = $senderUser['nickname'] ?? $senderUser['username'];
                $data['sender_avatar'] = cdnurl($senderUser['avatar'] ?? '', true);
            }
        }

        // 关联资源信息
        if (!empty($rowData['resource'])) {
            $resource = $rowData['resource'];
            $resourceData = is_array($resource) ? $resource : ($resource->getData() ?? []);

            // 处理资源图片URL，确保带完整域名
            $resourceImages = [];
            if (!empty($resourceData['images'])) {
                $decodedImages = json_decode($resourceData['images'], true);
                if (is_array($decodedImages)) {
                    foreach ($decodedImages as $img) {
                        $resourceImages[] = cdnurl($img, true);
                    }
                }
            }

            $data['resource'] = [
                'id' => $resourceData['id'] ?? 0,
                'name' => $resourceData['name'] ?? '',
                'description' => $resourceData['description'] ?? '',
                'logo' => cdnurl($resourceData['logo'] ?? '', true),
                'images' => $resourceImages,
                'type' => $resourceData['type'] ?? '',
            ];

            if (empty($data['display_title'])) {
                $data['display_title'] = $resourceData['name'] ?? $data['task_name'];
            }
            if (empty($data['display_description'])) {
                $data['display_description'] = $resourceData['description'] ?? $data['description'];
            }
            $data['background_image'] = cdnurl($resourceData['logo'] ?? '', true);
            $data['jump_url'] = cdnurl($resourceData['url'] ?? '', true);

            $resourceType = $resourceData['type'] ?? '';
            switch ($resourceType) {
                case 'chat':
                    $data['resource']['chat_duration'] = $resourceData['chat_duration'] ?? 30;
                    $data['resource']['chat_requirement'] = $resourceData['chat_requirement'] ?? '';
                    break;
                case 'miniapp':
                case 'mini_program':
                    $data['resource']['miniapp_id'] = $resourceData['miniapp_id'] ?? ($resourceData['app_id'] ?? '');
                    $data['resource']['miniapp_path'] = $resourceData['miniapp_path'] ?? '';
                    $data['resource']['miniapp_duration'] = $resourceData['miniapp_duration'] ?? 0;
                    break;
                case 'download':
                case 'download_app':
                    $data['resource']['download_url'] = cdnurl($resourceData['download_url'] ?? ($resourceData['url'] ?? ''), true);
                    $data['resource']['download_type'] = $resourceData['download_type'] ?? '';
                    $data['resource']['package_name'] = $resourceData['package_name'] ?? '';
                    if (empty($data['jump_url'])) {
                        $data['jump_url'] = cdnurl($resourceData['url'] ?? ($resourceData['download_url'] ?? ''), true);
                    }
                    break;
                case 'adv':
                    $data['resource']['adv_id'] = $resourceData['adv_id'] ?? '';
                    $data['resource']['adv_platform'] = $resourceData['adv_platform'] ?? '';
                    $data['resource']['adv_duration'] = $resourceData['adv_duration'] ?? 0;
                    break;
                case 'video':
                case 'watch_video':
                    $data['resource']['video_url'] = cdnurl($resourceData['video_url'] ?? '', true);
                    $data['resource']['video_duration'] = $resourceData['video_duration'] ?? 0;
                    break;
            }
        } else {
            $taskType = $rowData['type'] ?? '';
            if ($taskType === 'chat') {
                $data['chat_content'] = $rowData['description'] ?? '';
                $data['chat_duration'] = 30;
            }
        }

        return $data;
    }
}
