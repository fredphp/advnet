<?php

namespace app\admin\controller\adincome;

use app\common\controller\Backend;
use app\common\model\AdIncomeLogSplit;
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

            // 1. 概览统计（跨分表）
            $rangeStats = $this->safeQuery(
                ['count' => 0, 'user_coin' => 0, 'platform_coin' => 0, 'total_coin' => 0],
                function () use ($startTime, $endTime) {
                    return AdIncomeLogSplit::getRangeStats($startTime, $endTime);
                }
            );

            // 独立用户数（跨分表）
            $userCount = $this->safeQuery(0, function () use ($startTime, $endTime) {
                return AdIncomeLogSplit::getDistinctUserCount($startTime, $endTime);
            });

            $overview = [
                'total_records'  => $rangeStats['count'],
                'total_coin'     => $rangeStats['total_coin'],
                'user_coin'      => $rangeStats['user_coin'],
                'platform_coin'  => $rangeStats['platform_coin'],
                'user_count'     => $userCount,
            ];

            // 2. 按类型统计（跨分表）
            $typeStats = $this->safeQuery([], function () use ($startTime, $endTime) {
                $list = AdIncomeLogSplit::getGroupStats($startTime, $endTime, 'ad_type', 'user_amount_coin');
                $result = [];
                foreach ($list as $row) {
                    $result[] = ['ad_type' => $row['ad_type'], 'count' => $row['cnt'], 'user_coin' => $row['total']];
                }
                return $result;
            });

            // 3. 按平台统计（跨分表）
            $providerStats = $this->safeQuery([], function () use ($startTime, $endTime) {
                $list = AdIncomeLogSplit::getGroupStats($startTime, $endTime, 'ad_provider', 'user_amount_coin');
                $result = [];
                foreach ($list as $row) {
                    $result[] = ['ad_provider' => $row['ad_provider'], 'count' => $row['cnt'], 'user_coin' => $row['total']];
                }
                return $result;
            });

            // 4. 用户排行（跨分表）
            $userRanking = $this->safeQuery([], function () use ($startTime, $endTime) {
                $list = AdIncomeLogSplit::getUserRanking($startTime, $endTime, 10);
                $result = [];
                foreach ($list as $row) {
                    $result[] = [
                        'user_id'     => $row['user_id'],
                        'count'       => $row['count'],
                        'total_coin'  => $row['total_coin'],
                        'nickname'    => '',
                        'username'    => '',
                    ];
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
                'title'           => $title,
                'overview'        => $overview,
                'type_stats'      => $typeStats,
                'provider_stats'  => $providerStats,
                'user_ranking'    => $userRanking,
            ]);
        }
        return $this->view->fetch();
    }
}
