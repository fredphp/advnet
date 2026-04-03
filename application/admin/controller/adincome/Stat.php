<?php

namespace app\admin\controller\adincome;

use app\common\controller\Backend;
use think\Db;

/**
 * 广告统计
 */
class Stat extends Backend
{
    protected $dataLimit = false;

    /**
     * 检查广告收益表是否存在
     */
    protected function checkTableExists()
    {
        static $exists = null;
        if ($exists === null) {
            try {
                Db::query("SELECT 1 FROM " . config('database.prefix') . "ad_income_log LIMIT 1");
                $exists = true;
            } catch (\Exception $e) {
                $exists = false;
            }
        }
        return $exists;
    }

    /**
     * 统计面板
     */
    public function index()
    {
        if ($this->request->isAjax()) {
            // 检查表是否存在
            if (!$this->checkTableExists()) {
                $this->error('广告收益表不存在，请先执行数据库迁移: sql/add_ad_income_tables.sql');
            }

            $type = $this->request->get('type', 'today');
            $startDate = $this->request->get('start_date', '');
            $endDate = $this->request->get('end_date', '');

            switch ($type) {
                case 'today':
                    $startTime = strtotime(date('Y-m-d'));
                    $endTime = time();
                    $title = '今日统计';
                    break;
                case 'yesterday':
                    $startTime = strtotime(date('Y-m-d', strtotime('-1 day')));
                    $endTime = strtotime(date('Y-m-d 23:59:59', strtotime('-1 day')));
                    $title = '昨日统计';
                    break;
                case 'week':
                    $startTime = strtotime(date('Y-m-d', strtotime('-7 days')));
                    $endTime = time();
                    $title = '近7天统计';
                    break;
                case 'month':
                    $startTime = strtotime(date('Y-m-01'));
                    $endTime = time();
                    $title = '本月统计';
                    break;
                case 'custom':
                    $startTime = $startDate ? strtotime($startDate) : strtotime(date('Y-m-01'));
                    $endTime = $endDate ? strtotime($endDate . ' 23:59:59') : time();
                    $title = '自定义统计';
                    break;
                default:
                    $startTime = strtotime(date('Y-m-d'));
                    $endTime = time();
                    $title = '今日统计';
            }

            try {
                // 概览统计
                $overview = Db::name('ad_income_log')
                    ->where('status', 'in', [1, 2])
                    ->where('createtime', 'between', [$startTime, $endTime])
                    ->field('COUNT(*) as total_records, SUM(amount_coin) as total_coin, SUM(user_amount_coin) as user_coin, SUM(platform_amount_coin) as platform_coin, COUNT(DISTINCT user_id) as user_count')
                    ->find();

                $overview = $overview ? $overview : [
                    'total_records' => 0,
                    'total_coin' => 0,
                    'user_coin' => 0,
                    'platform_coin' => 0,
                    'user_count' => 0,
                ];

                // 按类型统计 - 转为普通数组
                $typeStats = Db::name('ad_income_log')
                    ->where('status', 'in', [1, 2])
                    ->where('createtime', 'between', [$startTime, $endTime])
                    ->group('ad_type')
                    ->field('ad_type, COUNT(*) as count, SUM(user_amount_coin) as user_coin')
                    ->select();
                $typeStats = $typeStats ? json_decode(json_encode($typeStats), true) : [];

                // 按平台统计
                $providerStats = Db::name('ad_income_log')
                    ->where('status', 'in', [1, 2])
                    ->where('createtime', 'between', [$startTime, $endTime])
                    ->group('ad_provider')
                    ->field('ad_provider, COUNT(*) as count, SUM(user_amount_coin) as user_coin')
                    ->select();
                $providerStats = $providerStats ? json_decode(json_encode($providerStats), true) : [];

                // 用户排行 - 转为普通数组并填充用户信息
                $userRanking = Db::name('ad_income_log')
                    ->where('status', 'in', [1, 2])
                    ->where('createtime', 'between', [$startTime, $endTime])
                    ->group('user_id')
                    ->field('user_id, COUNT(*) as count, SUM(user_amount_coin) as total_coin')
                    ->order('total_coin', 'desc')
                    ->limit(10)
                    ->select();
                $userRanking = $userRanking ? json_decode(json_encode($userRanking), true) : [];

                // 填充用户昵称
                if (!empty($userRanking)) {
                    $userIds = array_column($userRanking, 'user_id');
                    $users = Db::name('user')
                        ->where('id', 'in', $userIds)
                        ->column('nickname,username', 'id');
                    foreach ($userRanking as &$rank) {
                        $rank['nickname'] = $users[$rank['user_id']]['nickname'] ?? '';
                        $rank['username'] = $users[$rank['user_id']]['username'] ?? '';
                    }
                    unset($rank);
                }

                $this->success('', null, [
                    'title' => $title,
                    'overview' => $overview,
                    'type_stats' => $typeStats,
                    'provider_stats' => $providerStats,
                    'user_ranking' => $userRanking,
                ]);
            } catch (\Exception $e) {
                $this->error('查询失败: ' . $e->getMessage());
            }
        }
        return $this->view->fetch();
    }
}
