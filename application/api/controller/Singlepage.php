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
     * 帮助中心文章中需要动态替换的配置项
     * 模板中使用 {{config_key}} 格式的占位符
     */
    protected $helpConfigKeys = [
        'coin_rate',
        'new_user_coin',
        'video_coin_reward',
        'video_watch_duration',
        'daily_video_limit',
        'daily_coin_limit',
        'hourly_coin_limit',
        'min_withdraw',
        'max_withdraw',
        'withdraw_amounts',
        'daily_withdraw_limit',
        'fee_rate',
        'auto_audit_amount',
        'manual_audit_amount',
        'new_user_withdraw_days',
        'invite_max_level',
        'invite_register_reward',
        'invite_first_withdraw_reward',
        'invite_level1_reward',
        'invite_level2_reward',
        'level1_commission_rate',
        'level2_commission_rate',
        'red_packet_max_reward',
        'risk_score_threshold',
        'risk_freeze_threshold',
        'risk_ban_threshold',
    ];

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
     * 文章内容支持 {{config_key}} 模板占位符，动态替换为当前系统配置值
     * 返回 version 用于前端缓存判断（包含配置哈希，配置变更自动刷新缓存）
     * @method GET
     * @param int $version 客户端本地缓存的版本号，相同则返回304标识
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

        // 获取最新更新时间
        $version = 0;
        if (!empty($list)) {
            $version = Db::name('singlepage')
                ->where('category_id', $categoryId)
                ->where('status', 1)
                ->max('updatetime');
            $version = intval($version);
        }

        // 计算配置哈希：当系统配置变更时，版本号也会变化，触发前端缓存刷新
        $configHash = $this->getHelpConfigHash();
        $version = $version . '_' . $configHash;

        // 客户端版本比对：如果传入了version且相同，返回not_modified标识
        $clientVersion = $this->request->get('version', '');
        if ($clientVersion !== '' && $clientVersion === $version) {
            $this->success('not_modified', ['list' => [], 'version' => $version, 'not_modified' => true]);
            return;
        }

        // 动态替换文章内容中的模板占位符为实际配置值
        if (!empty($list)) {
            $configValues = $this->getHelpConfigValues();
            foreach ($list as &$item) {
                $item['content'] = $this->replaceConfigPlaceholders($item['content'], $configValues);
            }
            unset($item);
        }

        $this->success('获取成功', ['list' => $list, 'version' => $version]);
    }

    /**
     * 获取帮助中心相关配置项的值
     * @return array 配置键值对
     */
    protected function getHelpConfigValues()
    {
        $values = [];
        foreach ($this->helpConfigKeys as $key) {
            $values[$key] = config('site.' . $key) ?? '';
        }
        return $values;
    }

    /**
     * 计算配置哈希值
     * 将所有相关配置值拼接后取MD5前8位，用于版本控制
     * 配置变更时哈希值变化，触发前端缓存刷新
     * @return string 8位哈希字符串
     */
    protected function getHelpConfigHash()
    {
        $values = $this->getHelpConfigValues();
        $concat = '';
        foreach ($this->helpConfigKeys as $key) {
            $concat .= $key . '=' . ($values[$key] ?? '') . '|';
        }
        return substr(md5($concat), 0, 8);
    }

    /**
     * 替换内容中的 {{config_key}} 占位符为实际配置值
     * @param string $content 包含占位符的HTML内容
     * @param array $configValues 配置键值对
     * @return string 替换后的内容
     */
    protected function replaceConfigPlaceholders($content, $configValues)
    {
        foreach ($configValues as $key => $value) {
            if ($value !== '' && $value !== null) {
                $content = str_replace('{{' . $key . '}}', $value, $content);
            }
        }
        return $content;
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
