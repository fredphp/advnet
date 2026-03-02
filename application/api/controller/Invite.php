<?php

namespace app\api\controller;

use app\common\controller\Api;
use app\common\library\InviteCommissionService;
use app\common\model\User;
use think\facade\Db;

/**
 * 邀请分佣API接口
 */
class Invite extends Api
{
    // 无需登录的接口
    protected $noNeedLogin = ['bind'];
    
    // 无需鉴权的接口
    protected $noNeedRight = ['*'];
    
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
    
    /**
     * 获取邀请统计概览
     * @ApiMethod (GET)
     */
    public function overview()
    {
        $userId = $this->auth->id;
        
        $service = new InviteCommissionService();
        $data = $service->getInviteOverview($userId);
        
        $this->success('获取成功', $data);
    }
    
    /**
     * 获取我的邀请码
     * @ApiMethod (GET)
     */
    public function myCode()
    {
        $userId = $this->auth->id;
        
        $user = User::find($userId);
        if (!$user) {
            $this->error('用户不存在');
        }
        
        // 如果没有邀请码，生成一个
        if (empty($user->invite_code)) {
            $inviteCode = $this->generateInviteCode($userId);
            User::where('id', $userId)->update(['invite_code' => $inviteCode]);
            $user->invite_code = $inviteCode;
        }
        
        $this->success('获取成功', [
            'invite_code' => $user->invite_code,
            'invite_link' => $this->getInviteLink($user->invite_code),
            'invite_qrcode' => $this->getInviteQrcode($user->invite_code),
        ]);
    }
    
    /**
     * 获取邀请列表
     * @ApiMethod (GET)
     * @param int $level 层级 1=一级 2=二级 0=全部
     * @param int $page 页码
     * @param int $limit 每页数量
     */
    public function list()
    {
        $userId = $this->auth->id;
        $level = (int) $this->request->get('level', 0);
        $page = (int) $this->request->get('page', 1);
        $limit = (int) $this->request->get('limit', 20);
        
        $service = new InviteCommissionService();
        $result = $service->getInviteList($userId, $level, $page, $limit);
        
        $this->success('获取成功', $result);
    }
    
    /**
     * 获取佣金明细
     * @ApiMethod (GET)
     * @param string $source_type 来源类型: withdraw/video/red_packet/game
     * @param int $level 层级 1=一级 2=二级 0=全部
     * @param int $page 页码
     * @param int $limit 每页数量
     */
    public function commissionList()
    {
        $userId = $this->auth->id;
        $sourceType = $this->request->get('source_type', '');
        $level = (int) $this->request->get('level', 0);
        $page = (int) $this->request->get('page', 1);
        $limit = (int) $this->request->get('limit', 20);
        
        $service = new InviteCommissionService();
        $result = $service->getCommissionList($userId, [
            'source_type' => $sourceType,
            'level' => $level,
            'page' => $page,
            'limit' => $limit,
        ]);
        
        $this->success('获取成功', $result);
    }
    
    /**
     * 获取佣金统计图表数据
     * @ApiMethod (GET)
     * @param string $type 类型: daily/weekly/monthly
     * @param string $start_date 开始日期
     * @param string $end_date 结束日期
     */
    public function chart()
    {
        $userId = $this->auth->id;
        $type = $this->request->get('type', 'daily');
        $startDate = $this->request->get('start_date', date('Y-m-d', strtotime('-7 days')));
        $endDate = $this->request->get('end_date', date('Y-m-d'));
        
        // 根据类型获取不同粒度的数据
        switch ($type) {
            case 'daily':
                $data = $this->getDailyChartData($userId, $startDate, $endDate);
                break;
            case 'source':
                $data = $this->getSourceChartData($userId, $startDate, $endDate);
                break;
            case 'level':
                $data = $this->getLevelChartData($userId, $startDate, $endDate);
                break;
            default:
                $data = [];
        }
        
        $this->success('获取成功', $data);
    }
    
    /**
     * 获取邀请排行
     * @ApiMethod (GET)
     * @param string $type 排行类型: invite/commission
     * @param int $limit 限制条数
     */
    public function ranking()
    {
        $type = $this->request->get('type', 'invite');
        $limit = (int) $this->request->get('limit', 50);
        
        if ($type == 'invite') {
            $list = \app\common\model\UserInviteStat::getRanking('total', $limit);
        } else {
            $list = \app\common\model\UserCommissionStat::getRanking('total', $limit);
        }
        
        $this->success('获取成功', ['list' => $list]);
    }
    
    /**
     * 生成邀请码
     */
    protected function generateInviteCode($userId)
    {
        // 生成规则：用户ID编码 + 随机字符
        $prefix = strtoupper(substr(md5($userId), 0, 4));
        $suffix = str_pad($userId, 6, '0', STR_PAD_LEFT);
        return $prefix . $suffix;
    }
    
    /**
     * 获取邀请链接
     */
    protected function getInviteLink($inviteCode)
    {
        $baseUrl = $this->request->domain();
        return $baseUrl . '/pages/register?invite_code=' . $inviteCode;
    }
    
    /**
     * 获取邀请二维码
     */
    protected function getInviteQrcode($inviteCode)
    {
        // 如果有二维码生成服务，可以在这里生成
        return '';
    }
    
    /**
     * 获取每日图表数据
     */
    protected function getDailyChartData($userId, $startDate, $endDate)
    {
        $list = Db::name('invite_commission_log')
            ->where('parent_id', $userId)
            ->where('status', 1)
            ->where('settle_time', '>=', strtotime($startDate . ' 00:00:00'))
            ->where('settle_time', '<=', strtotime($endDate . ' 23:59:59'))
            ->field([
                'DATE(FROM_UNIXTIME(settle_time)) as date',
                'SUM(commission_amount) as commission',
                'COUNT(*) as count',
            ])
            ->group('date')
            ->order('date', 'asc')
            ->select();
        
        // 填充日期
        $dates = [];
        $current = strtotime($startDate);
        $end = strtotime($endDate);
        
        while ($current <= $end) {
            $date = date('Y-m-d', $current);
            $dates[$date] = [
                'date' => $date,
                'commission' => 0,
                'count' => 0,
            ];
            $current = strtotime('+1 day', $current);
        }
        
        foreach ($list as $item) {
            if (isset($dates[$item['date']])) {
                $dates[$item['date']]['commission'] = $item['commission'];
                $dates[$item['date']]['count'] = $item['count'];
            }
        }
        
        return array_values($dates);
    }
    
    /**
     * 获取来源分布图表数据
     */
    protected function getSourceChartData($userId, $startDate, $endDate)
    {
        $list = Db::name('invite_commission_log')
            ->where('parent_id', $userId)
            ->where('status', 1)
            ->where('settle_time', '>=', strtotime($startDate . ' 00:00:00'))
            ->where('settle_time', '<=', strtotime($endDate . ' 23:59:59'))
            ->field([
                'source_type',
                'SUM(commission_amount) as commission',
                'COUNT(*) as count',
            ])
            ->group('source_type')
            ->select();
        
        $typeNames = [
            'withdraw' => '提现分佣',
            'video' => '视频分佣',
            'red_packet' => '红包分佣',
            'game' => '游戏分佣',
        ];
        
        $data = [];
        foreach ($list as $item) {
            $data[] = [
                'name' => $typeNames[$item['source_type']] ?? $item['source_type'],
                'value' => $item['commission'],
                'count' => $item['count'],
            ];
        }
        
        return $data;
    }
    
    /**
     * 获取层级分布图表数据
     */
    protected function getLevelChartData($userId, $startDate, $endDate)
    {
        $list = Db::name('invite_commission_log')
            ->where('parent_id', $userId)
            ->where('status', 1)
            ->where('settle_time', '>=', strtotime($startDate . ' 00:00:00'))
            ->where('settle_time', '<=', strtotime($endDate . ' 23:59:59'))
            ->field([
                'level',
                'SUM(commission_amount) as commission',
                'COUNT(*) as count',
            ])
            ->group('level')
            ->select();
        
        $data = [];
        foreach ($list as $item) {
            $data[] = [
                'name' => $item['level'] == 1 ? '一级分佣' : '二级分佣',
                'value' => $item['commission'],
                'count' => $item['count'],
            ];
        }
        
        return $data;
    }
}
