<?php

namespace app\common\model;

use think\Db;
use think\Log;

/**
 * 提现订单分表模型
 * 
 * 按月分表，表名格式：withdraw_order_202603
 * 
 * 使用方式：
 * // 插入数据（自动路由）
 * $order = WithdrawOrderSplit::createOrder($data);
 * 
 * // 查询当月数据
 * $orders = WithdrawOrderSplit::useTable()->where('user_id', 1)->select();
 * 
 * // 查询指定月份
 * $orders = WithdrawOrderSplit::useTable(strtotime('2026-02-01'))->where('user_id', 1)->select();
 * 
 * // 跨表查询统计
 * $stats = WithdrawOrderSplit::getMonthStats('2026-01-01', '2026-03-31');
 */
class WithdrawOrderSplit extends SplitTableModel
{
    // 分表类型：按月
    protected $splitType = 'month';
    
    // 分表依据字段
    protected $splitField = 'createtime';
    
    // 主表名
    protected $baseTable = 'withdraw_order';
    
    // 自动写入时间戳
    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    
    // 状态列表
    public static $statusList = [
        0 => '待审核',
        1 => '审核通过',
        2 => '打款中',
        3 => '提现成功',
        4 => '审核拒绝',
        5 => '打款失败',
        6 => '已取消'
    ];
    
    // 提现方式
    public static $typeList = [
        'alipay' => '支付宝',
        'wechat' => '微信',
        'bank' => '银行卡'
    ];
    
    /**
     * 创建提现订单（自动路由到正确的表）
     * @param array $data 订单数据
     * @return array
     */
    public static function createOrder($data)
    {
        $result = [
            'success' => false,
            'message' => '',
            'data' => null
        ];
        
        try {
            // 生成订单号
            if (!isset($data['order_no'])) {
                $data['order_no'] = self::generateOrderNo();
            }
            
            // 设置创建时间
            if (!isset($data['createtime'])) {
                $data['createtime'] = time();
            }
            
            // 获取时间戳确定分表
            $timestamp = $data['createtime'];
            
            $model = new self();
            $tableName = $model->getTableName($timestamp);
            
            // 确保表存在
            $model->ensureTableExists($tableName);
            
            // 切换到目标表
            $model->name = $tableName;
            
            // 插入数据
            $id = $model->insertGetId($data);
            
            $result['success'] = true;
            $result['data'] = [
                'id' => $id,
                'order_no' => $data['order_no'],
                'table' => $tableName
            ];
            
        } catch (\Exception $e) {
            $result['message'] = $e->getMessage();
            Log::error('创建提现订单失败: ' . $e->getMessage());
        }
        
        return $result;
    }
    
    /**
     * 生成订单号
     * @return string
     */
    public static function generateOrderNo()
    {
        return 'WD' . date('YmdHis') . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
    }
    
    /**
     * 获取状态文本
     */
    public function getStatusTextAttr($value, $data)
    {
        return self::$statusList[$data['status']] ?? '';
    }
    
    /**
     * 获取提现方式文本
     */
    public function getWithdrawTypeTextAttr($value, $data)
    {
        return self::$typeList[$data['withdraw_type']] ?? '';
    }
    
    /**
     * 关联用户
     */
    public function user()
    {
        return $this->belongsTo('User', 'user_id');
    }
    
    /**
     * 获取今日提现统计
     * @return array
     */
    public static function getTodayStats()
    {
        $todayStart = strtotime(date('Y-m-d'));
        $todayEnd = strtotime(date('Y-m-d') . ' 23:59:59');
        
        $model = new self();
        
        return [
            'today_count' => $model->aggregateSplit($todayStart, $todayEnd, 'count', 'id'),
            'today_coin_amount' => $model->aggregateSplit($todayStart, $todayEnd, 'sum', 'coin_amount'),
            'today_cash_amount' => $model->aggregateSplit($todayStart, $todayEnd, 'sum', 'cash_amount'),
            'today_success_count' => $model->aggregateSplit($todayStart, $todayEnd, 'count', 'id', function($q) {
                return $q->where('status', 3);
            }),
            'today_success_amount' => $model->aggregateSplit($todayStart, $todayEnd, 'sum', 'cash_amount', function($q) {
                return $q->where('status', 3);
            }),
            'today_pending_count' => $model->aggregateSplit($todayStart, $todayEnd, 'count', 'id', function($q) {
                return $q->where('status', 0);
            }),
        ];
    }
    
    /**
     * 获取昨日提现统计
     * @return array
     */
    public static function getYesterdayStats()
    {
        $yesterdayStart = strtotime(date('Y-m-d', strtotime('-1 day')));
        $yesterdayEnd = strtotime(date('Y-m-d', strtotime('-1 day')) . ' 23:59:59');
        
        $model = new self();
        
        return [
            'yesterday_count' => $model->aggregateSplit($yesterdayStart, $yesterdayEnd, 'count', 'id'),
            'yesterday_coin_amount' => $model->aggregateSplit($yesterdayStart, $yesterdayEnd, 'sum', 'coin_amount'),
            'yesterday_cash_amount' => $model->aggregateSplit($yesterdayStart, $yesterdayEnd, 'sum', 'cash_amount'),
            'yesterday_success_count' => $model->aggregateSplit($yesterdayStart, $yesterdayEnd, 'count', 'id', function($q) {
                return $q->where('status', 3);
            }),
            'yesterday_success_amount' => $model->aggregateSplit($yesterdayStart, $yesterdayEnd, 'sum', 'cash_amount', function($q) {
                return $q->where('status', 3);
            }),
        ];
    }
    
    /**
     * 获取本月提现统计
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
            'month_coin_amount' => $model->aggregateSplit($monthStart, $monthEnd, 'sum', 'coin_amount'),
            'month_cash_amount' => $model->aggregateSplit($monthStart, $monthEnd, 'sum', 'cash_amount'),
            'month_success_count' => $model->aggregateSplit($monthStart, $monthEnd, 'count', 'id', function($q) {
                return $q->where('status', 3);
            }),
            'month_success_amount' => $model->aggregateSplit($monthStart, $monthEnd, 'sum', 'cash_amount', function($q) {
                return $q->where('status', 3);
            }),
            'month_pending_count' => $model->aggregateSplit($monthStart, $monthEnd, 'count', 'id', function($q) {
                return $q->where('status', 0);
            }),
            'month_pending_amount' => $model->aggregateSplit($monthStart, $monthEnd, 'sum', 'cash_amount', function($q) {
                return $q->where('status', 0);
            }),
        ];
    }
    
    /**
     * 获取综合统计数据（用于后台首页展示）
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
        
        // 待审核数量和金额
        $pendingCount = 0;
        $pendingAmount = 0;
        
        $tables = $model->getTableList();
        foreach ($tables as $table) {
            $pendingCount += Db::name($table)->where('status', 0)->count();
            $pendingAmount += Db::name($table)->where('status', 0)->sum('cash_amount');
        }
        
        return [
            // 今日数据
            'today' => [
                'count' => $model->aggregateSplit($todayStart, $todayEnd, 'count', 'id'),
                'coin_amount' => $model->aggregateSplit($todayStart, $todayEnd, 'sum', 'coin_amount'),
                'cash_amount' => $model->aggregateSplit($todayStart, $todayEnd, 'sum', 'cash_amount'),
                'success_count' => $model->aggregateSplit($todayStart, $todayEnd, 'count', 'id', function($q) {
                    return $q->where('status', 3);
                }),
                'success_amount' => $model->aggregateSplit($todayStart, $todayEnd, 'sum', 'cash_amount', function($q) {
                    return $q->where('status', 3);
                }),
            ],
            // 昨日数据
            'yesterday' => [
                'count' => $model->aggregateSplit($yesterdayStart, $yesterdayEnd, 'count', 'id'),
                'coin_amount' => $model->aggregateSplit($yesterdayStart, $yesterdayEnd, 'sum', 'coin_amount'),
                'cash_amount' => $model->aggregateSplit($yesterdayStart, $yesterdayEnd, 'sum', 'cash_amount'),
                'success_count' => $model->aggregateSplit($yesterdayStart, $yesterdayEnd, 'count', 'id', function($q) {
                    return $q->where('status', 3);
                }),
                'success_amount' => $model->aggregateSplit($yesterdayStart, $yesterdayEnd, 'sum', 'cash_amount', function($q) {
                    return $q->where('status', 3);
                }),
            ],
            // 本月数据
            'month' => [
                'count' => $model->aggregateSplit($monthStart, $monthEnd, 'count', 'id'),
                'coin_amount' => $model->aggregateSplit($monthStart, $monthEnd, 'sum', 'coin_amount'),
                'cash_amount' => $model->aggregateSplit($monthStart, $monthEnd, 'sum', 'cash_amount'),
                'success_count' => $model->aggregateSplit($monthStart, $monthEnd, 'count', 'id', function($q) {
                    return $q->where('status', 3);
                }),
                'success_amount' => $model->aggregateSplit($monthStart, $monthEnd, 'sum', 'cash_amount', function($q) {
                    return $q->where('status', 3);
                }),
            ],
            // 上月数据
            'last_month' => [
                'count' => $model->aggregateSplit($lastMonthStart, $lastMonthEnd, 'count', 'id'),
                'coin_amount' => $model->aggregateSplit($lastMonthStart, $lastMonthEnd, 'sum', 'coin_amount'),
                'cash_amount' => $model->aggregateSplit($lastMonthStart, $lastMonthEnd, 'sum', 'cash_amount'),
                'success_count' => $model->aggregateSplit($lastMonthStart, $lastMonthEnd, 'count', 'id', function($q) {
                    return $q->where('status', 3);
                }),
                'success_amount' => $model->aggregateSplit($lastMonthStart, $lastMonthEnd, 'sum', 'cash_amount', function($q) {
                    return $q->where('status', 3);
                }),
            ],
            // 待审核
            'pending' => [
                'count' => $pendingCount,
                'amount' => $pendingAmount,
            ],
            // 状态分布
            'status_distribution' => self::getStatusDistribution(),
        ];
    }
    
    /**
     * 获取状态分布
     * @return array
     */
    public static function getStatusDistribution()
    {
        $model = new self();
        $tables = $model->getTableList();
        
        $distribution = [];
        foreach (self::$statusList as $status => $label) {
            $distribution[$status] = [
                'status' => $status,
                'label' => $label,
                'count' => 0,
                'amount' => 0,
            ];
        }
        
        foreach ($tables as $table) {
            $data = Db::name($table)
                ->field('status, COUNT(*) as count, SUM(cash_amount) as amount')
                ->group('status')
                ->select();
            
            foreach ($data as $row) {
                if (isset($distribution[$row['status']])) {
                    $distribution[$row['status']]['count'] += $row['count'];
                    $distribution[$row['status']]['amount'] += $row['amount'];
                }
            }
        }
        
        return array_values($distribution);
    }
    
    /**
     * 按日期统计（用于图表）
     * @param string $startDate 开始日期 Y-m-d
     * @param string $endDate 结束日期 Y-m-d
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
                'coin_amount' => 0,
                'cash_amount' => 0,
                'success_count' => 0,
                'success_amount' => 0,
            ];
            $current = strtotime('+1 day', $current);
        }
        
        // 汇总数据
        foreach ($tables as $table) {
            $data = Db::name($table)
                ->field("FROM_UNIXTIME(createtime, '%Y-%m-%d') as date,
                         COUNT(*) as count,
                         SUM(coin_amount) as coin_amount,
                         SUM(cash_amount) as cash_amount,
                         SUM(CASE WHEN status = 3 THEN 1 ELSE 0 END) as success_count,
                         SUM(CASE WHEN status = 3 THEN cash_amount ELSE 0 END) as success_amount")
                ->where('createtime', '>=', $startTimestamp)
                ->where('createtime', '<=', $endTimestamp)
                ->group('date')
                ->select();
            
            foreach ($data as $row) {
                if (isset($result[$row['date']])) {
                    $result[$row['date']]['count'] += $row['count'];
                    $result[$row['date']]['coin_amount'] += $row['coin_amount'];
                    $result[$row['date']]['cash_amount'] += $row['cash_amount'];
                    $result[$row['date']]['success_count'] += $row['success_count'];
                    $result[$row['date']]['success_amount'] += $row['success_amount'];
                }
            }
        }
        
        return array_values($result);
    }
}
