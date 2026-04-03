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
     * 获取帮助中心文章列表（含内容）
     * 按"帮助中心"分类获取所有已启用的文章，返回标题+内容，前端直接展示无需跳转
     * 返回 version 时间戳用于前端缓存判断
     * @method GET
     * @param int $version 客户端本地缓存的版本时间戳，相同则返回304标识
     */
    public function helpList()
    {
        // 查找"帮助中心"分类
        $categoryId = Db::name('singlepage_category')
            ->where('name', '帮助中心')
            ->where('status', 1)
            ->value('id');

        if (!$categoryId) {
            $this->success('获取成功', ['list' => [], 'version' => time()]);
            return;
        }

        // 查询该分类下所有已启用的文章（包含content字段）
        $list = Db::name('singlepage')
            ->where('category_id', $categoryId)
            ->where('status', 1)
            ->order('weigh', 'desc')
            ->order('id', 'asc')
            ->field('id,title,description,content,updatetime')
            ->select();

        // 获取最新更新时间作为版本号
        $version = 0;
        if (!empty($list)) {
            $version = Db::name('singlepage')
                ->where('category_id', $categoryId)
                ->where('status', 1)
                ->max('updatetime');
            $version = intval($version);
        }

        // 客户端版本比对：如果传入了version且相同，返回not_modified标识
        $clientVersion = $this->request->get('version', 0, 'intval');
        if ($clientVersion > 0 && $clientVersion >= $version) {
            $this->success('not_modified', ['list' => [], 'version' => $version, 'not_modified' => true]);
            return;
        }

        $this->success('获取成功', ['list' => $list, 'version' => $version]);
    }

    /**
     * 获取单页详情
     * 支持按ID、tpl标识、分类名获取
     * @method GET
     * @param int $id 单页ID
     * @param string $tpl 按自定义标识获取（对应数据库tpl字段）
     * @param string $category 按分类名称获取最新一篇
     */
    public function detail()
    {
        $id = $this->request->get('id', 0, 'intval');
        $tpl = $this->request->get('tpl', '');
        $category = $this->request->get('category', '');

        $page = null;

        // 优先按ID查询
        if ($id > 0) {
            $page = Db::name('singlepage')
                ->where('id', $id)
                ->where('status', 1)
                ->find();
        }
        // 其次按tpl标识查询
        elseif ($tpl) {
            $page = Db::name('singlepage')
                ->where('tpl', $tpl)
                ->where('status', 1)
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

        // 浏览量+1（忽略软删除和字段不存在的情况）
        try {
            Db::name('singlepage')->where('id', $page['id'])->setInc('views');
        } catch (\Exception $e) {}

        $this->success('获取成功', $page);
    }
}
