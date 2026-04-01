<?php

namespace app\api\controller;

use app\common\controller\Api;
use think\Db;

/**
 * 单页接口
 */
class Singlepage extends Api
{
    protected $noNeedLogin = ['*'];
    protected $noNeedRight = ['*'];

    /**
     * 获取弹窗公告
     * 查询"弹窗公告"分类下最新一条已启用的单页
     * @method GET
     * @param int $read_id 客户端已读的公告ID，相同则不返回
     */
    public function notice()
    {
        $readId = $this->request->get('read_id', 0, 'intval');

        // 查找"弹窗公告"分类
        $categoryId = Db::name('singlepage_category')
            ->where('name', '弹窗公告')
            ->where('status', 1)
            ->value('id');

        if (!$categoryId) {
            $this->success('', null);
            return;
        }

        // 查询该分类下最新一条已启用的单页
        $notice = Db::name('singlepage')
            ->where('category_id', $categoryId)
            ->where('status', 1)
            ->order('weigh', 'desc')
            ->order('id', 'desc')
            ->find();

        if (!$notice) {
            $this->success('', null);
            return;
        }

        // 如果客户端已读过这条公告，不再返回
        if ($readId > 0 && $notice['id'] == $readId) {
            $this->success('', null);
            return;
        }

        $this->success('', $notice);
    }
}
