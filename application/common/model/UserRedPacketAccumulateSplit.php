<?php

namespace app\common\model;

use think\Db;
use think\Log;

/**
 * 红包领取记录分表模型
 * 
 * 按月分表，表名格式：user_red_packet_accumulate_202603
 */
class UserRedPacketAccumulateSplit extends SplitTableModel
{
    // 分表类型：按月
    protected $splitType = 'month';
    
    // 分表依据字段
    protected $splitField = 'createtime';
    
    // 主表名
    protected $baseTable = 'user_red_packet_accumulate';
    
    // 自动写入时间戳
    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    
    // Redis锁前缀
    const LOCK_PREFIX = 'lock:red_packet_grab:';
    
    /**
     * 关联用户
     */
    public function user()
    {
        return $this->belongsTo('User', 'user_id');
    }
    
    /**
     * 关联任务
     */
    public function task()
    {
        return $this->belongsTo('RedPacketTask', 'task_id');
    }
    
    /**
     * 创建领取记录（自动路由到正确的表）
     * @param array $data 记录数据
     * @return array
     */
    public static function createRecord($data)
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
            Log::error('创建红包领取记录失败: ' . $e->getMessage());
        }
        
        return $result;
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
            'today_amount' => $model->aggregateSplit($todayStart, $todayEnd, 'sum', 'total_amount'),
            'today_collected_count' => $model->aggregateSplit($todayStart, $todayEnd, 'count', 'id', function($q) {
                return $q->where('is_collected', 1);
            }),
            'today_collected_amount' => $model->aggregateSplit($todayStart, $todayEnd, 'sum', 'total_amount', function($q) {
                return $q->where('is_collected', 1);
            }),
        ];
    }
    
    /**
     * 获取昨日统计
     * @return array
     */
    public static function getYesterdayStats()
    {
        $yesterdayStart = strtotime(date('Y-m-d', strtotime('-1 day')));
        $yesterdayEnd = strtotime(date('Y-m-d', strtotime('-1 day')) . ' 23:59:59');
        
        $model = new self();
        
        return [
            'yesterday_count' => $model->aggregateSplit($yesterdayStart, $yesterdayEnd, 'count', 'id'),
            'yesterday_amount' => $model->aggregateSplit($yesterdayStart, $yesterdayEnd, 'sum', 'total_amount'),
            'yesterday_collected_count' => $model->aggregateSplit($yesterdayStart, $yesterdayEnd, 'count', 'id', function($q) {
                return $q->where('is_collected', 1);
            }),
            'yesterday_collected_amount' => $model->aggregateSplit($yesterdayStart, $yesterdayEnd, 'sum', 'total_amount', function($q) {
                return $q->where('is_collected', 1);
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
            'month_amount' => $model->aggregateSplit($monthStart, $monthEnd, 'sum', 'total_amount'),
            'month_collected_count' => $model->aggregateSplit($monthStart, $monthEnd, 'count', 'id', function($q) {
                return $q->where('is_collected', 1);
            }),
            'month_collected_amount' => $model->aggregateSplit($monthStart, $monthEnd, 'sum', 'total_amount', function($q) {
                return $q->where('is_collected', 1);
            }),
            'month_pending_count' => $model->aggregateSplit($monthStart, $monthEnd, 'count', 'id', function($q) {
                return $q->where('is_collected', 0);
            }),
            'month_pending_amount' => $model->aggregateSplit($monthStart, $monthEnd, 'sum', 'total_amount', function($q) {
                return $q->where('is_collected', 0);
            }),
        ];
    }
    
    /**
     * 获取综合统计数据
     * @return array
     */
    public static function getDashboardStats()
    {
        $model = new self();
        
        // 今日
        $todayStart = strtotime(date('Y-m-d'));
        $todayEnd = strtotime(date('Y-m-d') . ' 23:59:59');
        
        // 昨日
        $yesterdayStart = strtotime(date('Y-m-d', strtotime('-1 day')));
        $yesterdayEnd = strtotime(date('Y-m-d', strtotime('-1 day')) . ' 23:59:59');
        
        // 本月
        $monthStart = strtotime(date('Y-m-01'));
        $monthEnd = strtotime(date('Y-m-t') . ' 23:59:59');
        
        // 上月
        $lastMonthStart = strtotime(date('Y-m-01', strtotime('-1 month')));
        $lastMonthEnd = strtotime(date('Y-m-t', strtotime('-1 month')) . ' 23:59:59');
        
        // 新老用户统计
        $tables = $model->getTableList();
        $newUserCount = 0;
        $newUserAmount = 0;
        $oldUserCount = 0;
        $oldUserAmount = 0;
        
        foreach ($tables as $table) {
            $newUserCount += Db::name($table)->where('is_new_user', 1)->where('is_collected', 1)->count();
            $newUserAmount += Db::name($table)->where('is_new_user', 1)->where('is_collected', 1)->sum('total_amount');
            $oldUserCount += Db::name($table)->where('is_new_user', 0)->where('is_collected', 1)->count();
            $oldUserAmount += Db::name($table)->where('is_new_user', 0)->where('is_collected', 1)->sum('total_amount');
        }
        
        return [
            'today' => [
                'count' => $model->aggregateSplit($todayStart, $todayEnd, 'count', 'id'),
                'amount' => $model->aggregateSplit($todayStart, $todayEnd, 'sum', 'total_amount'),
                'collected_count' => $model->aggregateSplit($todayStart, $todayEnd, 'count', 'id', function($q) {
                    return $q->where('is_collected', 1);
                }),
                'collected_amount' => $model->aggregateSplit($todayStart, $todayEnd, 'sum', 'total_amount', function($q) {
                    return $q->where('is_collected', 1);
                }),
            ],
            'yesterday' => [
                'count' => $model->aggregateSplit($yesterdayStart, $yesterdayEnd, 'count', 'id'),
                'amount' => $model->aggregateSplit($yesterdayStart, $yesterdayEnd, 'sum', 'total_amount'),
                'collected_count' => $model->aggregateSplit($yesterdayStart, $yesterdayEnd, 'count', 'id', function($q) {
                    return $q->where('is_collected', 1);
                }),
                'collected_amount' => $model->aggregateSplit($yesterdayStart, $yesterdayEnd, 'sum', 'total_amount', function($q) {
                    return $q->where('is_collected', 1);
                }),
            ],
            'month' => [
                'count' => $model->aggregateSplit($monthStart, $monthEnd, 'count', 'id'),
                'amount' => $model->aggregateSplit($monthStart, $monthEnd, 'sum', 'total_amount'),
                'collected_count' => $model->aggregateSplit($monthStart, $monthEnd, 'count', 'id', function($q) {
                    return $q->where('is_collected', 1);
                }),
                'collected_amount' => $model->aggregateSplit($monthStart, $monthEnd, 'sum', 'total_amount', function($q) {
                    return $q->where('is_collected', 1);
                }),
            ],
            'last_month' => [
                'count' => $model->aggregateSplit($lastMonthStart, $lastMonthEnd, 'count', 'id'),
                'amount' => $model->aggregateSplit($lastMonthStart, $lastMonthEnd, 'sum', 'total_amount'),
                'collected_count' => $model->aggregateSplit($lastMonthStart, $lastMonthEnd, 'count', 'id', function($q) {
                    return $q->where('is_collected', 1);
                }),
                'collected_amount' => $model->aggregateSplit($lastMonthStart, $lastMonthEnd, 'sum', 'total_amount', function($q) {
                    return $q->where('is_collected', 1);
                }),
            ],
            'user_type' => [
                'new_user' => [
                    'count' => $newUserCount,
                    'amount' => $newUserAmount,
                ],
                'old_user' => [
                    'count' => $oldUserCount,
                    'amount' => $oldUserAmount,
                ],
            ],
        ];
    }
    
    /**
     * 按日期统计
     * @param string $startDate 开始日期
     * @param string $endDate 结束日期
     * @return array
     */
    public static function getDailyStats($startDate, $endDate)
    {
        $model = new self();
        $startTimestamp = strtotime($startDate);
        $endTimestamp = strtotime($endDate . ' 23:59:59');
        
        $tables = $model->getTableList($startDate, $endDate);
        
        // 初始化日期数组
        $result = [];
        $current = $startTimestamp;
        while ($current <= $endTimestamp) {
            $date = date('Y-m-d', $current);
            $result[$date] = [
                'date' => $date,
                'count' => 0,
                'amount' => 0,
                'collected_count' => 0,
                'collected_amount' => 0,
            ];
            $current = strtotime('+1 day', $current);
        }
        
        // 汇总数据
        foreach ($tables as $table) {
            $data = Db::name($table)
                ->field("FROM_UNIXTIME(createtime, '%Y-%m-%d') as date,
                         COUNT(*) as count,
                         SUM(total_amount) as amount,
                         SUM(CASE WHEN is_collected = 1 THEN 1 ELSE 0 END) as collected_count,
                         SUM(CASE WHEN is_collected = 1 THEN total_amount ELSE 0 END) as collected_amount")
                ->where('createtime', '>=', $startTimestamp)
                ->where('createtime', '<=', $endTimestamp)
                ->group('date')
                ->select();
            
            foreach ($data as $row) {
                if (isset($result[$row['date']])) {
                    $result[$row['date']]['count'] += $row['count'];
                    $result[$row['date']]['amount'] += $row['amount'];
                    $result[$row['date']]['collected_count'] += $row['collected_count'];
                    $result[$row['date']]['collected_amount'] += $row['collected_amount'];
                }
            }
        }
        
        return array_values($result);
    }
}
