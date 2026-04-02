<?php

namespace app\api\controller;

use app\common\controller\Api;
use app\common\library\InviteCommissionService;
use app\common\model\User;
use app\common\model\UserInviteStat;
use app\common\model\UserCommissionStat;
use app\common\model\InviteRelation;
use app\common\model\InviteCommissionLog;
use app\common\library\SystemConfigService;
use think\Db;
use think\Log;
use think\exception\HttpResponseException;

/**
 * 邀请分佣API接口
 * 
 * 模仿 coagent 插件返回格式，为前端分销中心提供完整数据
 * 
 * 接口列表：
 * - GET  /api/invite/overview       分销首页概览（替代 coagent get_agent_user）
 * - GET  /api/invite/teamList       团队成员列表（替代 coagent get_agent_child）
 * - GET  /api/invite/commissionList 佣金明细列表（替代 coagent get_agent_order）
 * - GET  /api/invite/ranking        业绩排行
 * - GET  /api/invite/myCode         我的邀请码/链接
 * - POST /api/invite/bind           绑定邀请码
 */
class Invite extends Api
{
    // 无需登录的接口
    protected $noNeedLogin = ['bind'];
    
    // 无需鉴权的接口
    protected $noNeedRight = ['*'];
    
    // 分销等级配置（按累计佣金分等级）
    private static $levelConfig = [
        ['min' => 0,      'name' => '普通会员'],
        ['min' => 100,    'name' => '青铜代理'],
        ['min' => 500,    'name' => '白银代理'],
        ['min' => 2000,   'name' => '黄金代理'],
        ['min' => 5000,   'name' => '铂金代理'],
        ['min' => 10000,  'name' => '钻石代理'],
        ['min' => 50000,  'name' => '星耀代理'],
        ['min' => 100000, 'name' => '王者代理'],
    ];
    
    // 分佣来源中文名
    private static $sourceTypeNames = [
        'withdraw'    => '提现分佣',
        'video'       => '视频分佣',
        'red_packet'  => '红包分佣',
        'game'        => '游戏分佣',
        'sign'        => '签到分佣',
        'other'       => '其他分佣',
    ];
    
    // 分佣来源图标
    private static $sourceTypeIcons = [
        'withdraw'    => 'red-packet-fill',
        'video'       => 'play-circle-fill',
        'red_packet'  => 'red-packet-fill',
        'game'        => 'game-fill',
        'sign'        => 'checkmark-circle-fill',
        'other'       => 'gift-fill',
    ];

    private function rethrowHttpResponseException(\Throwable $e)
    {
        if ($e instanceof HttpResponseException) {
            throw $e;
        }
    }
    
    // ==================== 首页概览 ====================
    
    /**
     * 分销首页概览（模仿 coagent get_agent_user）
     * 
     * 返回字段对应前端 index.vue 的 userInfo 对象：
     * - month_reward      本月收益
     * - total_income      累计收益
     * - order_nums        推广订单数
     * - team_nums         团队人数
     * - income_money      可提现金额
     * - nosettle_money    待结算金额
     * - settle_money      已结算金额
     * - total_withdraw    累计提现
     * - level / level_name 分销等级
     * - parent_name / parent_user_id 上级信息
     * - invite_code / invite_link 邀请码
     * - today_commission  今日收益
     * - yesterday_commission 昨日收益
     * 
     * @ApiMethod (GET)
     */
    public function overview()
    {
        $userId = $this->auth->id;
        if (!$userId) {
            $this->error('请先登录');
        }
        
        try {
            // 用户基本信息
            $user = User::find($userId);
            if (!$user) {
                $this->error('用户不存在');
            }
            
            // 邀请统计
            $inviteStat = UserInviteStat::getOrCreate($userId);
            
            // 佣金统计
            $commissionStat = UserCommissionStat::getOrCreate($userId);
            
            // 上级信息
            $parentName = '无';
            $parentUserId = 0;
            $parentAvatar = '';
            $relation = InviteRelation::where('user_id', $userId)->find();
            if ($relation && $relation->parent_id > 0) {
                $parent = User::find($relation->parent_id);
                if ($parent) {
                    $parentName = $parent->nickname ?: '未知';
                    $parentUserId = $parent->id;
                    $parentAvatar = $parent->avatar ?: '';
                }
            }
            
            // 分销等级（按累计佣金）
            $level = 1;
            $levelName = '普通会员';
            foreach (self::$levelConfig as $cfg) {
                if ($commissionStat->total_commission >= $cfg['min']) {
                    $level = array_search($cfg, self::$levelConfig) + 1;
                    $levelName = $cfg['name'];
                }
            }
            
            // ========== 钱包数据（从金币账户获取原始数据） ==========
            $coinRate = SystemConfigService::getCoinRate() ?: 10000;
            $coinAccount = Db::name('coin_account')->where('user_id', $userId)->find();
            $coinBalance = $coinAccount ? floatval($coinAccount['balance'] ?? 0) : 0;
            $coinFrozen = $coinAccount ? floatval($coinAccount['frozen'] ?? 0) : 0;
            
            // 累计佣金（用于业绩概览）
            $totalCommission = floatval($commissionStat->total_commission);
            
            // 总订单数 = 所有类型分佣记录数
            $orderNums = intval($commissionStat->withdraw_count ?? 0)
                      + intval($commissionStat->video_count ?? 0)
                      + intval($commissionStat->red_packet_count ?? 0)
                      + intval($commissionStat->game_count ?? 0);
            
            // 邀请码
            $inviteCode = $user->invite_code ?: '';
            if (empty($inviteCode)) {
                $inviteCode = $this->generateInviteCode($userId);
                User::where('id', $userId)->update(['invite_code' => $inviteCode]);
            }
            $inviteLink = $this->getInviteLink($inviteCode);
            
            $data = [
                // 用户基本信息
                'id'            => $userId,
                'level'         => $level,
                'level_name'    => $levelName,
                'parent_name'   => $parentName,
                'parent_user_id'=> $parentUserId,
                'parent_avatar' => $parentAvatar,
                
                // 业绩概览
                'month_reward'  => round(floatval($commissionStat->month_commission), 2),
                'total_income'  => round($totalCommission, 2),
                'order_nums'    => $orderNums,
                'team_nums'     => intval($inviteStat->total_invite_count),
                
                // 钱包原始数据（提现统计由前端调用 withdraw/config + withdraw/stat 获取）
                'coin_balance'  => $coinBalance,
                'coin_frozen'   => $coinFrozen,
                'exchange_rate' => $coinRate,
                
                // 今日/昨日
                'today_commission'     => round(floatval($commissionStat->today_commission), 2),
                'yesterday_commission' => round(floatval($commissionStat->yesterday_commission), 2),
                
                // 邀请
                'total_invite_count' => intval($inviteStat->total_invite_count),
                'level1_count'       => intval($inviteStat->level1_count),
                'level2_count'       => intval($inviteStat->level2_count),
                'new_invite_today'   => intval($inviteStat->new_invite_today),
                'new_invite_yesterday' => intval($inviteStat->new_invite_yesterday),
                
                // 邀请码
                'invite_code'   => $inviteCode,
                'invite_link'   => $inviteLink,
            ];
            
            $this->success('获取成功', $data);
            
        } catch (\Throwable $e) {
            $this->rethrowHttpResponseException($e);
            Log::error('分销概览获取失败: ' . $e->getMessage() . ' [' . $e->getFile() . ':' . $e->getLine() . ']' . "\n" . $e->getTraceAsString());
            $this->error('系统错误: ' . $e->getMessage());
        }
    }
    
    // ==================== 团队列表 ====================
    
    /**
     * 团队成员列表（模仿 coagent get_agent_child）
     * 
     * 返回格式：
     * - team_1_count  一级成员数
     * - team_2_count  二级成员数
     * - team_nums     总人数
     * - total         总条数
     * - list          成员列表
     * 
     * @ApiMethod (GET)
     * @param int $level 0=全部 1=一级 2=二级
     * @param int $page 页码
     * @param int $limit 每页数量
     */
    public function teamList()
    {
        $userId = $this->auth->id;
        if (!$userId) {
            $this->error('请先登录');
        }
        
        $level = (int) $this->request->get('level', 0);
        $page = (int) $this->request->get('page', 1);
        $limit = (int) $this->request->get('limit', 20);
        $limit = min($limit, 50);
        
        try {
            // 一级成员ID（使用Db::name避免Model层autoWriteTimestamp干扰）
            $level1Ids = Db::name('invite_relation')->where('parent_id', $userId)->column('user_id');
            $level1Ids = $level1Ids ? $level1Ids : [];
            
            // 二级成员ID
            $level2Ids = Db::name('invite_relation')->where('grandparent_id', $userId)->column('user_id');
            $level2Ids = $level2Ids ? $level2Ids : [];
            
            $team1Count = count($level1Ids);
            $team2Count = count($level2Ids);
            $teamNums = $team1Count + $team2Count;
            
            // 合并和筛选
            $allIds = [];
            if ($level == 0) {
                $allIds = array_merge($level1Ids, $level2Ids);
            } elseif ($level == 1) {
                $allIds = $level1Ids;
            } elseif ($level == 2) {
                $allIds = $level2Ids;
            }
            
            $total = count($allIds);
            
            if ($total == 0) {
                $this->success('获取成功', [
                    'team_1_count' => $team1Count,
                    'team_2_count' => $team2Count,
                    'team_nums'    => 0,
                    'total'        => 0,
                    'list'         => [],
                ]);
                return;
            }
            
            // 分页
            $pagedIds = array_slice($allIds, ($page - 1) * $limit, $limit);
            
            // 查询成员信息（resultset_type=array 时 select 已返回数组，无需 toArray）
            $users = Db::name('user')
                ->whereIn('id', $pagedIds)
                ->field('id, nickname, avatar, logintime, createtime')
                ->select();
            $users = $users ? (array)$users : [];
            
            // 查询每个成员对我的佣金贡献（使用Db::name避免Model层干扰聚合查询）
            $commissionMap = [];
            if (!empty($pagedIds)) {
                $commissions = Db::name('invite_commission_log')
                    ->whereIn('user_id', $pagedIds)
                    ->where('parent_id', $userId)
                    ->where('status', 1)
                    ->field('user_id, SUM(commission_amount) as total_commission')
                    ->group('user_id')
                    ->select();
                if ($commissions) {
                    foreach ($commissions as $c) {
                        $commissionMap[$c['user_id']] = floatval($c['total_commission']);
                    }
                }
            }
            
            // 批量查询成员佣金统计（避免N+1查询）
            $memberStats = [];
            if (!empty($pagedIds)) {
                $stats = Db::name('user_commission_stat')
                    ->whereIn('user_id', $pagedIds)
                    ->column('total_commission', 'user_id');
                if ($stats) {
                    $memberStats = $stats;
                }
            }
            
            // 组装列表
            $level1Set = array_flip($level1Ids);
            $list = [];
            foreach ($users as $u) {
                $uid = $u['id'];
                $isLevel1 = isset($level1Set[$uid]);
                
                // 成员自己的佣金统计
                $memberTotalIncome = isset($memberStats[$uid]) ? floatval($memberStats[$uid]) : 0;
                
                // 成员等级
                $memberLevelName = '普通会员';
                if (isset($memberStats[$uid])) {
                    $memberCommission = floatval($memberStats[$uid]);
                    foreach (self::$levelConfig as $cfg) {
                        if ($memberCommission >= $cfg['min']) {
                            $memberLevelName = $cfg['name'];
                        }
                    }
                }
                
                $list[] = [
                    'user_id'    => $uid,
                    'user'       => [
                        'nickname'       => $u['nickname'] ?: '未知用户',
                        'avatar'         => $u['avatar'] ?: '/static/image/avatar.png',
                        'logintime_text' => !empty($u['logintime']) ? date('Y-m-d H:i', $u['logintime']) : '',
                    ],
                    'ulevel'            => $isLevel1 ? 1 : 2,
                    'level_name'        => $memberLevelName,
                    'total_income'      => round($memberTotalIncome, 2),
                    'commission_contribution' => round($commissionMap[$uid] ?? 0, 2),
                ];
            }
            
            $this->success('获取成功', [
                'team_1_count' => $team1Count,
                'team_2_count' => $team2Count,
                'team_nums'    => $teamNums,
                'total'        => $total,
                'list'         => $list,
            ]);
            return;
            
        } catch (\Throwable $e) {
            $this->rethrowHttpResponseException($e);
            Log::error('团队列表获取失败: ' . $e->getMessage() . ' [' . $e->getFile() . ':' . $e->getLine() . ']' . "\n" . $e->getTraceAsString());
            $this->error('系统错误: ' . $e->getMessage());
        }
    }
    
    // ==================== 佣金明细 ====================
    
    /**
     * 佣金明细列表（模仿 coagent get_agent_order）
     * 
     * 返回格式：每条记录包含 goods 对象，兼容前端模板
     * 
     * @ApiMethod (GET)
     * @param string $source_type 来源类型 withdraw/video/red_packet/game
     * @param int $level 层级 1=一级 2=二级 0=全部
     * @param int $page 页码
     * @param int $limit 每页数量
     */
    public function commissionList()
    {
        $userId = $this->auth->id;
        if (!$userId) {
            $this->error('请先登录');
        }
        
        $sourceType = $this->request->get('source_type', '');
        $level = (int) $this->request->get('level', 0);
        $page = (int) $this->request->get('page', 1);
        $limit = (int) $this->request->get('limit', 20);
        $limit = min($limit, 50);
        $month = $this->request->get('month', '');
        
        try {
            // 构建查询条件
            $where = ['parent_id' => $userId];
            
            if ($sourceType) {
                $where['source_type'] = $sourceType;
            }
            
            if ($level > 0) {
                $where['level'] = $level;
            }
            
            // 月份筛选：传入 2026-03 自动计算该月起止时间戳
            if ($month && preg_match('/^\d{4}-\d{2}$/', $month)) {
                $dt = new \DateTime($month . '-01 00:00:00', new \DateTimeZone('Asia/Shanghai'));
                $startTs = $dt->getTimestamp();
                // 获取该月最后一天23:59:59
                $lastDay = (int) $dt->format('t');
                $dt->setDate((int) $dt->format('Y'), (int) $dt->format('m'), $lastDay);
                $dt->setTime(23, 59, 59);
                $endTs = $dt->getTimestamp();
                
                $total = Db::name('invite_commission_log')
                    ->where($where)
                    ->where('createtime', 'between', [$startTs, $endTs])
                    ->count();
                $list = Db::name('invite_commission_log')
                    ->where($where)
                    ->where('createtime', 'between', [$startTs, $endTs])
                    ->order('id', 'desc')
                    ->page($page, $limit)
                    ->select();
            } else {
                $total = Db::name('invite_commission_log')->where($where)->count();
                $list = Db::name('invite_commission_log')
                    ->where($where)
                    ->order('id', 'desc')
                    ->page($page, $limit)
                    ->select();
            }
            
            $list = $list ? (array)$list : [];
            
            // 获取CDN域名，用于补全头像等资源的完整URL
            $cdnUrl = '';
            try {
                $cdnUrl = \think\Config::get('upload.cdnurl');
            } catch (\Exception $e) {}
            if (empty($cdnUrl)) {
                $cdnUrl = $this->request->domain();
            }
            $cdnUrl = rtrim($cdnUrl, '/');

            // 获取下级用户信息
            $userIds = array_unique(array_column($list, 'user_id'));
            $userMap = [];
            if (!empty($userIds)) {
                $users = Db::name('user')
                    ->whereIn('id', $userIds)
                    ->field('id, nickname, avatar')
                    ->select();
                if ($users) {
                    foreach ((array)$users as $u) {
                        $avatar = $u['avatar'] ?: '/static/image/avatar.png';
                        // 补全CDN域名
                        if ($avatar && strpos($avatar, 'http') !== 0 && strpos($avatar, '//') !== 0) {
                            $avatar = $cdnUrl . $avatar;
                        }
                        $userMap[$u['id']] = [
                            'nickname' => $u['nickname'] ?: '未知用户',
                            'avatar'   => $avatar,
                        ];
                    }
                }
            }
            
            // 状态映射
            $statusMap = [
                0 => '待结算',
                1 => '已结算',
                2 => '已取消',
                3 => '已冻结',
            ];
            
            // 组装数据（兼容 coagent 格式，每条记录含 goods 对象）
            $result = [];
            foreach ($list as $item) {
                $sourceType = $item['source_type'] ?? 'other';
                $level = $item['level'] == 1 ? '一级' : '二级';
                $typeName = self::$sourceTypeNames[$sourceType] ?? '其他';
                
                $status = ($item['status'] == 1) ? 'completed' : 'pending';
                $statusText = $statusMap[$item['status']] ?? '未知';
                
                $result[] = [
                    'id'            => $item['id'],
                    'order_no'      => $item['order_no'] ?? '',
                    'goods'         => [
                        'title'     => $level . $typeName,
                        'image'     => '',
                        'attrdata'  => $typeName,
                    ],
                    'status'        => $status,
                    'status_text'   => $statusText,
                    'reward_money'  => round(floatval($item['commission_amount']), 2),
                    'coin_amount'   => round(floatval($item['coin_amount'] ?? 0), 2),
                    'createtime'    => $item['createtime'] ? date('Y-m-d H:i', $item['createtime']) : '',
                    'source_type'   => $sourceType,
                    'level'         => $item['level'] ?? 1,
                    'remark'        => $item['remark'] ?? '',
                    // 额外信息
                    'user_info'     => $userMap[$item['user_id']] ?? ['nickname' => '未知', 'avatar' => ''],
                    'source_amount' => round(floatval($item['source_amount'] ?? 0), 2),
                    'commission_rate' => $item['commission_rate'] ?? 0,
                    'settle_time'   => $item['settle_time'] ? date('Y-m-d H:i', $item['settle_time']) : '',
                ];
            }
            
            $this->success('获取成功', [
                'total' => $total,
                'list'  => $result,
            ]);
            
        } catch (\Throwable $e) {
            $this->rethrowHttpResponseException($e);
            Log::error('佣金明细获取失败: ' . $e->getMessage() . ' [' . $e->getFile() . ':' . $e->getLine() . ']' . "\n" . $e->getTraceAsString());
            $this->error('系统错误: ' . $e->getMessage());
        }
    }
    
    // ==================== 业绩排行 ====================
    
    /**
     * 业绩排行
     * @ApiMethod (GET)
     * @param string $type 排行类型 invite/commission
     * @param int $limit 限制条数
     */
    public function ranking()
    {
        $userId = $this->auth->id;
        if (!$userId) {
            $this->error('请先登录');
        }
        
        $type = $this->request->get('type', 'commission');
        $limit = (int) $this->request->get('limit', 50);
        
        try {
            if ($type == 'invite') {
                // 邀请人数排行
                $list = Db::name('user_invite_stat')
                    ->alias('s')
                    ->join('user u', 's.user_id = u.id', 'LEFT')
                    ->field('s.user_id, u.nickname, u.avatar, s.total_invite_count as total_income, s.level1_count')
                    ->where('s.total_invite_count', '>', 0)
                    ->order('s.total_invite_count', 'desc')
                    ->limit($limit)
                    ->select();
            } else {
                // 佣金排行
                $list = Db::name('user_commission_stat')
                    ->alias('s')
                    ->join('user u', 's.user_id = u.id', 'LEFT')
                    ->field('s.user_id, u.nickname, u.avatar, s.total_commission as total_income')
                    ->where('s.total_commission', '>', 0)
                    ->order('s.total_commission', 'desc')
                    ->limit($limit)
                    ->select();
            }
            
            $list = $list ? (array)$list : [];
            
            // 标记当前用户
            $myRank = 0;
            $myAmount = 0;
            foreach ($list as $index => &$item) {
                $item['total_income'] = round(floatval($item['total_income']), 2);
                $item['isCurrentUser'] = ($item['user_id'] == $userId);
                
                if ($item['user_id'] == $userId) {
                    $myRank = $index + 1;
                    $myAmount = $item['total_income'];
                }
            }
            unset($item);
            
            // 如果当前用户不在列表中，查找其排名
            if ($myRank == 0) {
                if ($type == 'invite') {
                    $myCount = Db::name('user_invite_stat')->where('user_id', $userId)->value('total_invite_count');
                    if ($myCount > 0) {
                        $myRank = Db::name('user_invite_stat')->where('total_invite_count', '>', $myCount)->count() + 1;
                        $myAmount = $myCount;
                    }
                } else {
                    $myCommission = Db::name('user_commission_stat')->where('user_id', $userId)->value('total_commission');
                    if ($myCommission > 0) {
                        $myRank = Db::name('user_commission_stat')->where('total_commission', '>', $myCommission)->count() + 1;
                        $myAmount = round(floatval($myCommission), 2);
                    }
                }
            }
            
            // 分离前三名
            $firstPlace = $list[0] ?? ['user' => ['nickname' => '', 'avatar' => '/static/image/avatar.png'], 'total_income' => 0];
            $secondPlace = $list[1] ?? ['user' => ['nickname' => '', 'avatar' => '/static/image/avatar.png'], 'total_income' => 0];
            $thirdPlace = $list[2] ?? ['user' => ['nickname' => '', 'avatar' => '/static/image/avatar.png'], 'total_income' => 0];
            
            $this->success('获取成功', [
                'firstPlace'   => $firstPlace,
                'secondPlace'  => $secondPlace,
                'thirdPlace'   => $thirdPlace,
                'list'         => $list,
                'myRank'       => $myRank,
                'myAmount'     => $myAmount,
            ]);
            
        } catch (\Throwable $e) {
            $this->rethrowHttpResponseException($e);
            Log::error('排行获取失败: ' . $e->getMessage() . ' [' . $e->getFile() . ':' . $e->getLine() . ']' . "\n" . $e->getTraceAsString());
            $this->error('系统错误: ' . $e->getMessage());
        }
    }
    
    // ==================== 我的邀请码 ====================
    
    /**
     * 获取我的邀请码
     * @ApiMethod (GET)
     */
    public function myCode()
    {
        $userId = $this->auth->id;
        if (!$userId) {
            $this->error('请先登录');
        }
        
        $user = User::find($userId);
        if (!$user) {
            $this->error('用户不存在');
        }
        
        if (empty($user->invite_code)) {
            $inviteCode = $this->generateInviteCode($userId);
            User::where('id', $userId)->update(['invite_code' => $inviteCode]);
        } else {
            $inviteCode = $user->invite_code;
        }
        
        $this->success('获取成功', [
            'invite_code'   => $inviteCode,
            'invite_link'   => $this->getInviteLink($inviteCode),
            'invite_qrcode' => '',
        ]);
    }
    
    // ==================== 绑定邀请码 ====================
    
    /**
     * 绑定邀请关系
     * @ApiMethod (POST)
     * @param string $invite_code 邀请码
     */
    public function bind()
    {
        $inviteCode = $this->request->post('invite_code', '');
        
        if (empty($inviteCode)) {
            $this->error('请输入邀请码');
        }
        
        $userId = $this->auth->id;
        
        $service = new InviteCommissionService();
        $result = $service->bindInvite($userId, $inviteCode, [
            'channel' => $this->request->post('channel', 'link'),
        ]);
        
        if ($result['success']) {
            $this->success($result['message'], $result['data']);
        } else {
            $this->error($result['message']);
        }
    }
    
    // ==================== 辅助方法 ====================
    
    protected function generateInviteCode($userId)
    {
        $prefix = strtoupper(substr(md5($userId), 0, 4));
        $suffix = str_pad($userId, 6, '0', STR_PAD_LEFT);
        return $prefix . $suffix;
    }
    
    protected function getInviteLink($inviteCode)
    {
        // 使用 cdnurl 配置中的域名
        $cdnUrl = '';
        try {
            $cdnUrl = \think\Config::get('upload.cdnurl');
        } catch (\Exception $e) {}
        
        $domain = $cdnUrl ?: '';
        if (empty($domain)) {
            $domain = $this->request->domain();
        }
        
        // 移除末尾斜杠
        $domain = rtrim($domain, '/');
        
        return $domain . '/pages/register?invite_code=' . $inviteCode;
    }
}
