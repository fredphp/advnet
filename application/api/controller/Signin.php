<?php

namespace app\api\controller;

use app\common\controller\Api;
use app\common\library\CoinService;
use think\Db;
use think\Exception;

/**
 * 签到接口
 * 所有金币操作统一通过 CoinService，不操作 user.score 字段
 */
class Signin extends Api
{
    // 无需登录的接口
    protected $noNeedLogin = [];
    
    // 无需鉴权的接口
    protected $noNeedRight = ['*'];
    
    /**
     * 签到首页 - 获取签到配置和用户签到状态
     * GET /api/signin/index
     */
    public function index()
    {
        $userId = $this->auth->id;
        $today = date('Y-m-d');
        
        // 获取签到配置
        $config = Db::name('signin_config')->find(1);
        if (!$config) {
            $this->error('签到系统未配置');
        }
        
        // 检查签到是否启用
        if (!$config['enabled']) {
            $this->error('签到功能已关闭');
        }
        
        // 获取奖励规则
        $rules = Db::name('signin_rule')->order('day', 'asc')->select();
        $signinscore = [];
        foreach ($rules as $rule) {
            $signinscore['s' . $rule['day']] = (int)$rule['coins'];
        }
        
        // 检查今日是否已签到
        $todayRecord = Db::name('signin_record')
            ->where('user_id', $userId)
            ->where('signin_date', $today)
            ->find();
        
        $isSignin = $todayRecord ? 1 : 0;
        
        // 计算连续签到天数
        $successions = $this->getSuccessions($userId);
        
        // 通过 CoinService 获取用户金币余额
        $coinService = new CoinService();
        $score = $coinService->getBalance($userId);
        
        // 计算用户签到排名（按连续签到天数降序）
        $selfRank = $this->getSelfRank($userId, $successions);
        
        // 构建消息
        if ($isSignin) {
            $msg = '今日已签到，连续签到' . $successions . '天';
        } else {
            $nextDay = ($successions % count($rules)) + 1;
            $nextCoins = $rules ? $rules[min($nextDay - 1, count($rules) - 1)]['coins'] : 0;
            $msg = '今日未签到，签到可获得' . $nextCoins . '金币';
        }
        
        $this->success('获取成功', [
            'is_signin'    => $isSignin,
            'score'        => $score,
            'successions'  => $successions,
            'self_rank'    => $selfRank,
            'fillupdays'   => (int)$config['fillup_days'],
            'fillupscore'  => (int)$config['fillup_cost'],
            'signinscore'  => $signinscore,
            'msg'          => $msg,
        ]);
    }
    
    /**
     * 获取月度签到数据
     * GET /api/signin/monthSign
     */
    public function monthSign()
    {
        $userId = $this->auth->id;
        $date = $this->request->get('date/s', '');
        
        if (!$date) {
            $this->error('缺少日期参数');
        }
        
        // 验证并格式化日期为 YYYY-MM（兼容 2026-4 和 2026-04）
        $dateParts = explode('-', $date);
        if (count($dateParts) != 2 || !is_numeric($dateParts[0]) || !is_numeric($dateParts[1])) {
            $this->error('日期格式错误');
        }
        $year = intval($dateParts[0]);
        $month = intval($dateParts[1]);
        if ($year < 2020 || $year > 2100 || $month < 1 || $month > 12) {
            $this->error('日期范围错误');
        }
        $date = sprintf('%04d-%02d', $year, $month);
        
        // 查询该月的签到记录
        $monthPrefix = $date . '-';
        $records = Db::name('signin_record')
            ->where('user_id', $userId)
            ->where('signin_date', 'like', $monthPrefix . '%')
            ->field('signin_date, coins')
            ->select();
        
        // 组装返回数据 {"01": 10, "02": 20, ...}
        $result = [];
        foreach ($records as $record) {
            $day = substr($record['signin_date'], 8, 2);
            $result[$day] = (int)$record['coins'];
        }
        
        $this->success('获取成功', $result);
    }
    
    /**
     * 执行签到
     * POST /api/signin/dosign
     */
    public function dosign()
    {
        $userId = $this->auth->id;
        $today = date('Y-m-d');
        
        // 获取签到配置
        $config = Db::name('signin_config')->find(1);
        if (!$config || !$config['enabled']) {
            $this->error('签到功能已关闭');
        }
        
        // 检查是否已签到
        $exists = Db::name('signin_record')
            ->where('user_id', $userId)
            ->where('signin_date', $today)
            ->find();
        
        if ($exists) {
            $this->error('今天已签到，请明天再来');
        }
        
        // 获取奖励规则
        $rules = Db::name('signin_rule')->order('day', 'asc')->select();
        if (empty($rules)) {
            $this->error('签到奖励规则未配置');
        }
        
        $cycleLength = count($rules);
        
        // 计算连续签到天数
        $yesterday = date('Y-m-d', strtotime('-1 day'));
        $yesterdayRecord = Db::name('signin_record')
            ->where('user_id', $userId)
            ->where('signin_date', $yesterday)
            ->find();
        
        if ($yesterdayRecord) {
            $successions = (int)$yesterdayRecord['successions'] + 1;
        } else {
            $successions = 1;
        }
        
        // 计算周期第几天（用于获取对应奖励）
        $cycleDay = (($successions - 1) % $cycleLength) + 1;
        
        // 获取对应奖励金币
        $rewardCoins = 0;
        foreach ($rules as $rule) {
            if ($rule['day'] == $cycleDay) {
                $rewardCoins = (int)$rule['coins'];
                break;
            }
        }
        
        // 如果没有找到对应天数的规则，使用最小奖励
        if ($rewardCoins == 0 && !empty($rules)) {
            $rewardCoins = (int)$rules[0]['coins'];
        }
        
        Db::startTrans();
        try {
            // 插入签到记录
            Db::name('signin_record')->insert([
                'user_id'     => $userId,
                'signin_date' => $today,
                'coins'       => $rewardCoins,
                'type'        => 'daily',
                'successions' => $successions,
                'createtime'  => time(),
            ]);
            
            // 通过 CoinService 增加金币（自动记录到金币流水日志 coin_log_YYYYMM）
            $coinService = new CoinService();
            $coinResult = $coinService->addCoin($userId, $rewardCoins, 'sign_in', '', 0, '每日签到奖励（连续' . $successions . '天）');
            
            if (!$coinResult['success']) {
                throw new Exception($coinResult['message']);
            }
            
            Db::commit();
        } catch (Exception $e) {
            Db::rollback();
            $this->error('签到失败: ' . $e->getMessage());
        }
        
        $this->success('签到成功', [
            'coins'      => $rewardCoins,
            'successions' => $successions,
        ]);
    }
    
    /**
     * 补签
     * POST /api/signin/fillup
     */
    public function fillup()
    {
        $userId = $this->auth->id;
        $date = $this->request->param('date/s', '');
        
        if (!$date) {
            $this->error('缺少日期参数');
        }
        
        // 验证并格式化日期为 YYYY-MM-DD（兼容不补零格式）
        $dateParts = explode('-', $date);
        if (count($dateParts) != 3 || !is_numeric($dateParts[0]) || !is_numeric($dateParts[1]) || !is_numeric($dateParts[2])) {
            $this->error('日期格式错误');
        }
        $year = intval($dateParts[0]);
        $month = intval($dateParts[1]);
        $day = intval($dateParts[2]);
        if ($year < 2020 || $year > 2100 || $month < 1 || $month > 12 || $day < 1 || $day > 31) {
            $this->error('日期范围错误');
        }
        $date = sprintf('%04d-%02d-%02d', $year, $month, $day);
        
        $today = date('Y-m-d');
        
        // 补签日期不能是今天或未来
        if ($date >= $today) {
            $this->error('只能补签过去的日期');
        }
        
        // 获取签到配置
        $config = Db::name('signin_config')->find(1);
        if (!$config || !$config['enabled']) {
            $this->error('签到功能已关闭');
        }
        
        $fillupDays = (int)$config['fillup_days'];
        $fillupCost = (int)$config['fillup_cost'];
        
        // 检查补签日期是否在允许范围内
        $fillupLimitDate = date('Y-m-d', strtotime("-{$fillupDays} days"));
        if ($date < $fillupLimitDate) {
            $this->error('只能补签最近' . $fillupDays . '天的记录');
        }
        
        // 检查该日期是否已签到
        $exists = Db::name('signin_record')
            ->where('user_id', $userId)
            ->where('signin_date', $date)
            ->find();
        
        if ($exists) {
            $this->error('该日期已签到');
        }
        
        // 通过 CoinService 检查用户金币余额（读取 coin_account.balance）
        $coinService = new CoinService();
        $balance = $coinService->getBalance($userId);
        
        if ($balance < $fillupCost) {
            $this->error('金币不足，补签需要' . $fillupCost . '金币');
        }
        
        // 获取奖励规则
        $rules = Db::name('signin_rule')->order('day', 'asc')->select();
        $rewardCoins = !empty($rules) ? (int)$rules[0]['coins'] : 10;
        
        Db::startTrans();
        try {
            // 通过 CoinService 扣除补签消耗金币（自动记录到金币流水日志）
            $deductResult = $coinService->deductCoin($userId, $fillupCost, 'sign_fillup', '', 0, '补签' . $date . '消耗');
            if (!$deductResult['success']) {
                throw new Exception($deductResult['message']);
            }

            // 计算补签后的连续签到天数（补签不影响连续天数记录，设为0表示非连续）
            $successions = 0;
            
            // 插入签到记录
            Db::name('signin_record')->insert([
                'user_id'     => $userId,
                'signin_date' => $date,
                'coins'       => $rewardCoins,
                'type'        => 'fillup',
                'successions' => $successions,
                'createtime'  => time(),
            ]);
            
            // 增加签到奖励金币（自动记录到金币流水日志）
            if ($rewardCoins > 0) {
                $addResult = $coinService->addCoin($userId, $rewardCoins, 'sign_fillup_reward', '', 0, '补签' . $date . '奖励');
                if (!$addResult['success']) {
                    throw new Exception($addResult['message']);
                }
            }
            
            Db::commit();
        } catch (Exception $e) {
            Db::rollback();
            $this->error('补签失败: ' . $e->getMessage());
        }
        
        $this->success('补签成功');
    }
    
    /**
     * 签到排行榜
     * GET /api/signin/rank
     */
    public function rank()
    {
        $userId = $this->auth->id;
        
        // 获取当前用户的连续签到天数
        $mySuccessions = $this->getSuccessions($userId);
        
        // 获取排行榜前10名（按最大连续签到天数降序，相同则最早签到排前面）
        $rankList = Db::name('signin_record')
            ->alias('sr')
            ->join('user u', 'u.id = sr.user_id', 'LEFT')
            ->field([
                'sr.user_id',
                'u.avatar',
                'u.nickname',
                Db::raw('MAX(sr.successions) as max_successions'),
                Db::raw('MIN(sr.createtime) as first_sign_time'),
            ])
            ->where('sr.type', 'daily')
            ->group('sr.user_id')
            ->having(Db::raw('max_successions > 0'))
            ->order('max_successions desc, first_sign_time asc')
            ->limit(10)
            ->select();
        
        // 格式化排行榜数据
        $list = [];
        foreach ($rankList as $item) {
            $list[] = [
                'user'        => [
                    'avatar'   => $item['avatar'] ? cdnurl($item['avatar']) : '',
                    'nickname' => $item['nickname'] ?: '用户' . $item['user_id'],
                ],
                'max_successions' => (int)$item['max_successions'],
            ];
        }
        
        // 计算用户排名
        $selfRank = $this->getSelfRank($userId, $mySuccessions);
        
        $this->success('获取成功', [
            'ranklist'    => $list,
            'self_rank'   => $selfRank,
            'successions' => $mySuccessions,
        ]);
    }
    
    /**
     * 签到日志（分页）
     * GET /api/signin/signLog
     */
    public function signLog()
    {
        $userId = $this->auth->id;
        $page = $this->request->get('page/d', 1);
        $limit = 20;
        
        $query = Db::name('signin_record')
            ->where('user_id', $userId);
        
        $total = $query->count();
        $list = Db::name('signin_record')
            ->where('user_id', $userId)
            ->order('createtime', 'desc')
            ->page($page, $limit)
            ->select();
        
        // 格式化日志数据，匹配前端显示需求
        foreach ($list as &$item) {
            $item['type'] = $item['type'] == 'fillup' ? '补签' : '签到';
            $item['createtime'] = date('m-d H:i', $item['createtime']);
        }
        unset($item);
        
        $this->success('获取成功', [
            'data'         => $list,
            'current_page' => (int)$page,
            'last_page'    => (int)ceil($total / $limit),
        ]);
    }
    
    /**
     * 获取用户连续签到天数
     */
    private function getSuccessions($userId)
    {
        $today = date('Y-m-d');
        $successions = 0;
        $checkDate = $today;
        
        while (true) {
            $record = Db::name('signin_record')
                ->where('user_id', $userId)
                ->where('signin_date', $checkDate)
                ->where('type', 'daily')
                ->find();
            
            if ($record) {
                $successions++;
                $checkDate = date('Y-m-d', strtotime($checkDate . ' -1 day'));
            } else {
                break;
            }
        }
        
        return $successions;
    }
    
    /**
     * 获取用户签到排名
     */
    private function getSelfRank($userId, $mySuccessions)
    {
        if ($mySuccessions <= 0) {
            return 0;
        }
        
        $subSql = Db::name('signin_record')
            ->where('type', 'daily')
            ->where('successions', '>', 0)
            ->group('user_id')
            ->field(['user_id', Db::raw('MAX(successions) as max_successions')])
            ->buildSql();
        
        $count = Db::table($subSql . ' t')
            ->where('t.max_successions', '>', $mySuccessions)
            ->count();
        
        $sameCount = Db::table($subSql . ' t')
            ->where('t.max_successions', '=', $mySuccessions)
            ->where('t.user_id', '<', $userId)
            ->count();
        
        return $count + $sameCount + 1;
    }
}
