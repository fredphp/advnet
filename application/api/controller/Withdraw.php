<?php

namespace app\api\controller;

use app\common\controller\Api;
use app\common\library\WithdrawService;
use app\common\library\CoinService;
use think\facade\Db;

/**
 * 提现API接口
 */
class Withdraw extends Api
{
    // 无需鉴权的接口
    protected $noNeedRight = ['*'];
    
    /**
     * 获取提现配置
     * @ApiMethod (GET)
     */
    public function config()
    {
        $config = Db::name('withdraw_config')->column('value', 'code');
        
        // 获取用户账户信息
        $userId = $this->auth->id;
        $coinService = new CoinService();
        $account = $coinService->getAccountInfo($userId);
        
        $this->success('获取成功', [
            'min_withdraw' => $config['min_withdraw'] ?? 10000,
            'max_withdraw' => $config['max_withdraw'] ?? 1000000,
            'exchange_rate' => $config['exchange_rate'] ?? 10000,
            'fee_rate' => $config['fee_rate'] ?? 0,
            'daily_limit' => $config['daily_withdraw_limit'] ?? 3,
            'daily_amount' => $config['daily_withdraw_amount'] ?? 100,
            'balance' => $account['balance'] ?? 0,
            'frozen' => $account['frozen'] ?? 0,
        ]);
    }
    
    /**
     * 申请提现
     * @ApiMethod (POST)
     * @param float $coin_amount 提现金币数量
     * @param string $withdraw_type 提现方式 wechat/alipay/bank
     * @param string $withdraw_account 提现账号
     * @param string $withdraw_name 收款人姓名
     */
    public function apply()
    {
        $coinAmount = $this->request->post('coin_amount', 0);
        $withdrawType = $this->request->post('withdraw_type', 'wechat');
        $withdrawAccount = $this->request->post('withdraw_account', '');
        $withdrawName = $this->request->post('withdraw_name', '');
        $bankName = $this->request->post('bank_name', '');
        $bankBranch = $this->request->post('bank_branch', '');
        
        if ($coinAmount <= 0) {
            $this->error('请输入提现金额');
        }
        
        if (empty($withdrawAccount)) {
            $this->error('请填写提现账号');
        }
        
        if (empty($withdrawName)) {
            $this->error('请填写收款人姓名');
        }
        
        $userId = $this->auth->id;
        
        $service = new WithdrawService();
        $result = $service->apply($userId, $coinAmount, [
            'withdraw_type' => $withdrawType,
            'withdraw_account' => $withdrawAccount,
            'withdraw_name' => $withdrawName,
            'bank_name' => $bankName,
            'bank_branch' => $bankBranch,
            'ip' => $this->request->ip(),
            'device_id' => $this->request->header('X-Device-Id'),
            'user_agent' => $this->request->header('user-agent'),
        ]);
        
        if ($result['success']) {
            $this->success($result['message'], $result['data']);
        } else {
            $this->error($result['message']);
        }
    }
    
    /**
     * 提现记录列表
     * @ApiMethod (GET)
     * @param int $status 状态筛选
     * @param int $page 页码
     * @param int $limit 每页数量
     */
    public function list()
    {
        $userId = $this->auth->id;
        $status = $this->request->get('status');
        $page = (int) $this->request->get('page', 1);
        $limit = (int) $this->request->get('limit', 20);
        
        $service = new WithdrawService();
        $result = $service->getUserOrders($userId, $status, $page, $limit);
        
        // 格式化数据
        foreach ($result['list'] as $item) {
            $item->status_text = \app\common\model\WithdrawOrder::$statusList[$item->status] ?? '';
            $item->withdraw_type_text = \app\common\model\WithdrawOrder::$typeList[$item->withdraw_type] ?? '';
            $item->create_time_text = date('Y-m-d H:i:s', $item->createtime);
        }
        
        $this->success('获取成功', $result);
    }
    
    /**
     * 提现详情
     * @ApiMethod (GET)
     * @param int $id 提现订单ID
     */
    public function detail()
    {
        $id = $this->request->get('id');
        $userId = $this->auth->id;
        
        $order = \app\common\model\WithdrawOrder::where('id', $id)
            ->where('user_id', $userId)
            ->find();
        
        if (!$order) {
            $this->error('订单不存在');
        }
        
        $order->status_text = \app\common\model\WithdrawOrder::$statusList[$order->status] ?? '';
        $order->withdraw_type_text = \app\common\model\WithdrawOrder::$typeList[$order->withdraw_type] ?? '';
        $order->create_time_text = date('Y-m-d H:i:s', $order->createtime);
        
        $this->success('获取成功', $order);
    }
    
    /**
     * 取消提现
     * @ApiMethod (POST)
     * @param int $id 提现订单ID
     */
    public function cancel()
    {
        $id = $this->request->post('id');
        $userId = $this->auth->id;
        
        $service = new WithdrawService();
        $result = $service->cancelOrder($id, $userId);
        
        if ($result['success']) {
            $this->success($result['message']);
        } else {
            $this->error($result['message']);
        }
    }
    
    /**
     * 提现统计
     * @ApiMethod (GET)
     */
    public function stat()
    {
        $userId = $this->auth->id;
        
        $service = new WithdrawService();
        $stat = $service->getUserStat($userId);
        
        // 获取今日提现次数
        $todayCount = \app\common\model\WithdrawOrder::where('user_id', $userId)
            ->where('createtime', '>=', strtotime('today'))
            ->whereNotIn('status', [6])
            ->count();
        
        // 获取今日提现金额
        $todayAmount = \app\common\model\WithdrawOrder::where('user_id', $userId)
            ->where('createtime', '>=', strtotime('today'))
            ->whereNotIn('status', [6])
            ->sum('cash_amount');
        
        // 待审核数量
        $pendingCount = \app\common\model\WithdrawOrder::where('user_id', $userId)
            ->where('status', 0)
            ->count();
        
        $this->success('获取成功', [
            'total_withdraw_count' => $stat['total_withdraw_count'] ?? 0,
            'total_withdraw_amount' => $stat['total_withdraw_amount'] ?? 0,
            'success_count' => $stat['success_count'] ?? 0,
            'today_withdraw_count' => $todayCount,
            'today_withdraw_amount' => $todayAmount,
            'pending_count' => $pendingCount,
        ]);
    }
    
    /**
     * 计算提现金额预览
     * @ApiMethod (POST)
     * @param float $coin_amount 提现金币数量
     */
    public function preview()
    {
        $coinAmount = $this->request->post('coin_amount', 0);
        
        if ($coinAmount <= 0) {
            $this->error('请输入提现金币数量');
        }
        
        // 获取配置
        $config = Db::name('withdraw_config')->column('value', 'code');
        $exchangeRate = $config['exchange_rate'] ?? 10000;
        $feeRate = floatval($config['fee_rate'] ?? 0);
        
        // 计算金额
        $cashAmount = round($coinAmount / $exchangeRate, 4);
        $feeAmount = round($cashAmount * $feeRate, 4);
        $actualAmount = round($cashAmount - $feeAmount, 4);
        
        $this->success('计算成功', [
            'coin_amount' => $coinAmount,
            'cash_amount' => $cashAmount,
            'fee_amount' => $feeAmount,
            'actual_amount' => $actualAmount,
            'exchange_rate' => $exchangeRate,
        ]);
    }
    
    /**
     * 获取用户的提现账号列表
     * @ApiMethod (GET)
     */
    public function accounts()
    {
        $userId = $this->auth->id;
        
        // 从用户账户表获取已绑定的提现账号
        $accounts = [];
        
        // 微信
        $wechatOpenid = Db::name('user_oauth')
            ->where('user_id', $userId)
            ->where('platform', 'wechat')
            ->value('openid');
        
        if ($wechatOpenid) {
            $accounts[] = [
                'type' => 'wechat',
                'type_text' => '微信',
                'account' => $wechatOpenid,
                'is_default' => true,
            ];
        }
        
        // 从人民币账户表获取
        $cashAccount = Db::name('cash_account')->where('user_id', $userId)->find();
        
        if ($cashAccount) {
            // 支付宝
            if (!empty($cashAccount['alipay_account'])) {
                $accounts[] = [
                    'type' => 'alipay',
                    'type_text' => '支付宝',
                    'account' => $cashAccount['alipay_account'],
                    'name' => $cashAccount['alipay_name'],
                    'is_default' => $cashAccount['default_account_type'] == 'alipay',
                ];
            }
            
            // 银行卡
            if (!empty($cashAccount['bank_card_no'])) {
                $accounts[] = [
                    'type' => 'bank',
                    'type_text' => '银行卡',
                    'account' => $cashAccount['bank_card_no'],
                    'name' => $cashAccount['bank_card_name'],
                    'bank_name' => $cashAccount['bank_name'],
                    'is_default' => $cashAccount['default_account_type'] == 'bank',
                ];
            }
        }
        
        $this->success('获取成功', ['list' => $accounts]);
    }
}
