<?php

namespace app\admin\controller\video;

use app\common\controller\Backend;
use think\Db;

/**
 * 视频观看记录
 */
class Watchrecord extends Backend
{
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\common\model\VideoWatchRecord();
    }

    /**
     * 观看记录列表
     */
    public function index()
    {
        if ($this->request->isAjax()) {
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();

            // 处理 where 条件中的字段歧义
            $newWhere = [];
            foreach ($where as $condition) {
                if (is_array($condition) && count($condition) >= 2) {
                    $field = $condition[0];
                    // 为 user_id 字段添加表别名
                    if ($field === 'user_id') {
                        $condition[0] = 'vwr.user_id';
                    }
                    // 为 video_id 字段添加表别名
                    if ($field === 'video_id') {
                        $condition[0] = 'vwr.video_id';
                    }
                }
                $newWhere[] = $condition;
            }

            $total = Db::name('video_watch_record')->alias('vwr')->where($newWhere)->count();
            $list = Db::name('video_watch_record')
                ->alias('vwr')
                ->join('user u', 'u.id = vwr.user_id', 'LEFT')
                ->join('video v', 'v.id = vwr.video_id', 'LEFT')
                ->field('vwr.*, u.username, u.nickname, v.title')
                ->where($newWhere)
                ->order("vwr.{$sort}", $order)
                ->limit($offset, $limit)
                ->select();

            $result = ['total' => $total, 'rows' => $list];
            return json($result);
        }
        return $this->view->fetch();
    }

    /**
     * 观看详情
     */
    public function detail($ids = null)
    {
        $row = Db::name('video_watch_record')
            ->alias('vwr')
            ->join('user u', 'u.id = vwr.user_id', 'LEFT')
            ->join('video v', 'v.id = vwr.video_id', 'LEFT')
            ->field('vwr.*, u.username, u.nickname, u.mobile, v.title, v.cover, v.duration as video_duration')
            ->where('vwr.id', $ids)
            ->find();

        if (!$row) {
            $this->error(__('未找到记录'));
        }

        $this->view->assign('row', $row);
        return $this->view->fetch();
    }

    /**
     * 删除观看记录
     */
    public function del($ids = '')
    {
        if (!$this->request->isPost()) {
            $this->error(__('参数错误'));
        }
        $ids = $ids ? $ids : $this->request->post('ids');
        if (empty($ids)) {
            $this->error(__('参数错误'));
        }

        $count = Db::name('video_watch_record')->where('id', 'in', $ids)->delete();
        if ($count) {
            $this->success();
        } else {
            $this->error(__('删除失败'));
        }
    }

    /**
     * 观看统计
     */
    public function statistics()
    {
        $startDate = $this->request->get('start_date', date('Y-m-d', strtotime('-7 days')));
        $endDate = $this->request->get('end_date', date('Y-m-d'));

        $startTimestamp = strtotime($startDate);
        $endTimestamp = strtotime($endDate . ' 23:59:59');

        if ($this->request->isAjax()) {
            $totalStats = Db::name('video_watch_record')
                ->where('createtime', 'between', [$startTimestamp, $endTimestamp])
                ->field('COUNT(*) as total_watches, COUNT(DISTINCT user_id) as unique_users,
                         COUNT(DISTINCT video_id) as unique_videos, SUM(coin_earned) as total_coin')
                ->find();

            $dailyStats = Db::name('video_watch_record')
                ->field('FROM_UNIXTIME(createtime, "%Y-%m-%d") as date,
                         COUNT(*) as watches, COUNT(DISTINCT user_id) as users,
                         SUM(coin_earned) as coin')
                ->where('createtime', 'between', [$startTimestamp, $endTimestamp])
                ->group('date')
                ->order('date', 'asc')
                ->select();

            $hotVideos = Db::name('video_watch_record')
                ->alias('vwr')
                ->join('video v', 'v.id = vwr.video_id', 'LEFT')
                ->field('v.id, v.title, COUNT(*) as watch_count, SUM(vwr.coin_earned) as total_coin')
                ->where('vwr.createtime', 'between', [$startTimestamp, $endTimestamp])
                ->group('vwr.video_id')
                ->order('watch_count', 'desc')
                ->limit(20)
                ->select();

            $activeUsers = Db::name('video_watch_record')
                ->alias('vwr')
                ->join('user u', 'u.id = vwr.user_id', 'LEFT')
                ->field('u.id, u.username, u.nickname, COUNT(*) as watch_count, SUM(vwr.coin_earned) as total_coin')
                ->where('vwr.createtime', 'between', [$startTimestamp, $endTimestamp])
                ->group('vwr.user_id')
                ->order('watch_count', 'desc')
                ->limit(20)
                ->select();

            $this->success('', null, [
                'total_stats' => $totalStats,
                'daily_stats' => $dailyStats,
                'hot_videos' => $hotVideos,
                'active_users' => $activeUsers,
            ]);
        }

        $this->view->assign('start_date', $startDate);
        $this->view->assign('end_date', $endDate);
        return $this->view->fetch();
    }

    /**
     * 导出观看记录
     */
    public function export()
    {
        $ids = $this->request->get('ids');
        $where = [];
        if ($ids) {
            $where['vwr.id'] = ['in', $ids];
        }

        $list = Db::name('video_watch_record')
            ->alias('vwr')
            ->join('user u', 'u.id = vwr.user_id', 'LEFT')
            ->join('video v', 'v.id = vwr.video_id', 'LEFT')
            ->field('vwr.id, u.username, u.nickname, v.title, vwr.watch_duration, 
                     vwr.watch_progress, vwr.coin_earned, vwr.createtime')
            ->where($where)
            ->order('vwr.createtime', 'desc')
            ->select();

        // 导出Excel
        $header = ['ID', '用户名', '昵称', '视频标题', '观看时长(秒)', '观看进度(%)', '获得金币', '观看时间'];
        $data = [];
        foreach ($list as $item) {
            $data[] = [
                $item['id'],
                $item['username'],
                $item['nickname'],
                $item['title'],
                $item['watch_duration'],
                $item['watch_progress'],
                $item['coin_earned'],
                date('Y-m-d H:i:s', $item['createtime']),
            ];
        }

        // 使用FastAdmin导出功能
        return $this->exportExcel($header, $data, '观看记录');
    }
}
