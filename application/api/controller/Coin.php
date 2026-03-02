<?php

namespace app\api\controller;

use app\common\controller\Api;
use app\common\library\CoinService;
use think\facade\Db;

/**
 * 用户金币接口
 */
class Coin extends Api
{
    // 无需登录的接口
    protected $noNeedLogin = [];
    
    // 无需鉴权的接口
    protected $noNeedRight = ['*'];
    
    /**
     * 获取金币余额
     */
    public function balance()
    {
        $coinService = new CoinService();
        $balance = $coinService->getBalance($this->auth->id);
        
        $this->success('获取成功', [
            'balance' => $balance,
        ]);
    }
    
    /**
     * 获取账户详情
     */
    public function account()
    {
        $coinService = new CoinService();
        $account = $coinService->getAccountInfo($this->auth->id);
        
        $this->success('获取成功', $account);
    }
    
    /**
     * 获取金币流水
     */
    public function logs()
    {
        $page = $this->request->get('page/d', 1);
        $limit = $this->request->get('limit/d', 20);
        $type = $this->request->get('type/s', '');
        $month = $this->request->get('month/s', date('Ym'));
        
        $tableName = 'coin_log_' . $month;
        
        $query = Db::name($tableName)
            ->where('user_id', $this->auth->id);
        
        if ($type) {
            $query->where('type', $type);
        }
        
        $total = $query->count();
        $list = $query->order('createtime', 'desc')
            ->page($page, $limit)
            ->select();
        
        $this->success('获取成功', [
            'total' => $total,
            'list' => $list,
        ]);
    }
    
    /**
     * 获取流水类型
     */
    public function types()
    {
        $types = [
            // 收入类型
            ['type' => 'video_watch', 'name' => '观看视频'],
            ['type' => 'video_share', 'name' => '分享视频'],
            ['type' => 'task_reward', 'name' => '任务奖励'],
            ['type' => 'sign_in', 'name' => '签到奖励'],
            ['type' => 'invite_level1', 'name' => '一级邀请奖励'],
            ['type' => 'invite_level2', 'name' => '二级邀请奖励'],
            ['type' => 'commission_level1', 'name' => '一级佣金'],
            ['type' => 'commission_level2', 'name' => '二级佣金'],
            ['type' => 'red_packet', 'name' => '红包奖励'],
            ['type' => 'game_reward', 'name' => '游戏奖励'],
            // 支出类型
            ['type' => 'withdraw', 'name' => '提现'],
        ];
        
        $this->success('获取成功', $types);
    }
}
