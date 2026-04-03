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
     * 安全执行查询，异常时返回默认值
     */
    private function safeQuery($default, $callback)
    {
        try {
            $result = $callback();
            return $result !== null ? $result : $default;
        } catch (\Exception $e) {
            $msg = $e->getMessage();
            if (empty($msg) && $e->getPrevious()) {
                $msg = $e->getPrevious()->getMessage();
            }
            return $default;
        }
    }

    /**
     * 统计面板
     */
    public function index()
    {
        if ($this->request->isAjax()) {
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

            // 1. 概览统计
            $overview = $this->safeQuery(
                ['total_records' => 0, 'total_coin' => 0, 'user_coin' => 0, 'platform_coin' => 0, 'user_count' => 0],
                function () use ($startTime, $endTime) {
                    $row = Db::name('ad_income_log')
                        ->where('status', 'in', [1, 2])
                        ->where('createtime', 'between', [$startTime, $endTime])
                        ->field('COUNT(*) as total_records, SUM(amount_coin) as total_coin, SUM(user_amount_coin) as user_coin, SUM(platform_amount_coin) as platform_coin, COUNT(DISTINCT user_id) as user_count')
                        ->find();
                    return $row ?: ['total_records' => 0, 'total_coin' => 0, 'user_coin' => 0, 'platform_coin' => 0, 'user_count' => 0];
                }
            );

            // 2. 按类型统计
            $typeStats = $this->safeQuery([], function () use ($startTime, $endTime) {
                $list = Db::name('ad_income_log')
                    ->where('status', 'in', [1, 2])
                    ->where('createtime', 'between', [$startTime, $endTime])
                    ->group('ad_type')
                    ->field('ad_type, COUNT(*) as count, SUM(user_amount_coin) as user_coin')
                    ->select();
                $result = [];
                foreach ($list as $row) {
                    $result[] = ['ad_type' => $row['ad_type'], 'count' => intval($row['count']), 'user_coin' => intval($row['user_coin'])];
                }
                return $result;
            });

            // 3. 按平台统计
            $providerStats = $this->safeQuery([], function () use ($startTime, $endTime) {
                $list = Db::name('ad_income_log')
                    ->where('status', 'in', [1, 2])
                    ->where('createtime', 'between', [$startTime, $endTime])
                    ->group('ad_provider')
                    ->field('ad_provider, COUNT(*) as count, SUM(user_amount_coin) as user_coin')
                    ->select();
                $result = [];
                foreach ($list as $row) {
                    $result[] = ['ad_provider' => $row['ad_provider'], 'count' => intval($row['count']), 'user_coin' => intval($row['user_coin'])];
                }
                return $result;
            });

            // 4. 用户排行
            $userRanking = $this->safeQuery([], function () use ($startTime, $endTime) {
                $list = Db::name('ad_income_log')
                    ->where('status', 'in', [1, 2])
                    ->where('createtime', 'between', [$startTime, $endTime])
                    ->group('user_id')
                    ->field('user_id, COUNT(*) as count, SUM(user_amount_coin) as total_coin')
                    ->order('total_coin', 'desc')
                    ->limit(10)
                    ->select();
                $result = [];
                foreach ($list as $row) {
                    $result[] = ['user_id' => intval($row['user_id']), 'count' => intval($row['count']), 'total_coin' => intval($row['total_coin']), 'nickname' => '', 'username' => ''];
                }
                return $result;
            });

            // 5. 批量填充用户昵称
            if (!empty($userRanking)) {
                $userIds = array_unique(array_column($userRanking, 'user_id'));
                $userMap = $this->safeQuery([], function () use ($userIds) {
                    $list = Db::name('user')->where('id', 'in', $userIds)->field('id, nickname, username')->select();
                    $map = [];
                    foreach ($list as $u) {
                        $map[$u['id']] = ['nickname' => $u['nickname'] ?: '', 'username' => $u['username'] ?: ''];
                    }
                    return $map;
                });
                foreach ($userRanking as &$rank) {
                    if (isset($userMap[$rank['user_id']])) {
                        $rank['nickname'] = $userMap[$rank['user_id']]['nickname'];
                        $rank['username'] = $userMap[$rank['user_id']]['username'];
                    }
                }
                unset($rank);
            }

            $this->success('', null, [
                'title'         => $title,
                'overview'      => $overview,
                'type_stats'    => $typeStats,
                'provider_stats' => $providerStats,
                'user_ranking'  => $userRanking,
            ]);
        }
        return $this->view->fetch();
    }
}
