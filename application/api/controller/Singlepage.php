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

    /**
     * 获取单页详情
     * 支持按ID或分类名获取
     * @method GET
     * @param int $id 单页ID
     * @param string $category 按分类名称获取最新一篇
     * @param string $diyname 按自定义标识获取
     */
    public function detail()
    {
        $id = $this->request->get('id', 0, 'intval');
        $category = $this->request->get('category', '');
        $diyname = $this->request->get('diyname', '');

        $page = null;

        // 优先按ID查询
        if ($id > 0) {
            $page = Db::name('singlepage')
                ->where('id', $id)
                ->where('status', 1)
                ->find();
        }
        // 其次按diyname查询（需查询custom字段或title）
        elseif ($diyname) {
            // 尝试通过自定义标识查询
            $page = Db::name('singlepage')
                ->where('title', $diyname)
                ->where('status', 1)
                ->order('id', 'desc')
                ->find();
        }
        // 最后按分类名查询最新一篇
        elseif ($category) {
            $categoryId = Db::name('singlepage_category')
                ->where('name', $category)
                ->where('status', 1)
                ->value('id');

            if ($categoryId) {
                $page = Db::name('singlepage')
                    ->where('category_id', $categoryId)
                    ->where('status', 1)
                    ->order('weigh', 'desc')
                    ->order('id', 'desc')
                    ->find();
            }
        }

        if (!$page) {
            $this->error('页面不存在');
        }

        // 浏览量+1
        Db::name('singlepage')->where('id', $page['id'])->setInc('views');

        $this->success('获取成功', $page);
    }
}
