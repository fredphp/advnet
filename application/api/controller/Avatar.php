<?php

namespace app\api\controller;

use app\common\controller\Api;
use think\Db;
use think\Cache;
use think\Hook;

/**
 * 头像列表接口
 */
class Avatar extends Api
{
    protected $noNeedLogin = ['*'];
    protected $noNeedRight = ['*'];

    // 缓存相关
    const CACHE_KEY = 'chat_avatar_list';
    const CACHE_TTL = 3600; // 1小时

    /**
     * 获取头像列表
     * 从附件表读取图片类型的附件URL，带缓存
     */
    public function list()
    {
        // 1. 读缓存
        $avatars = Cache::get(self::CACHE_KEY);
        if ($avatars !== null && is_array($avatars)) {
            $this->success('获取成功', ['avatars' => $avatars]);
        }

        // 2. 从附件表查询图片类型的附件
        $list = Db::name('attachment')
            ->where('mimetype', 'like', 'image/%')
            ->where('url', '<>', '')
            ->order('id', 'desc')
            ->column('url');

        if (empty($list)) {
            $this->success('获取成功', ['avatars' => []]);
        }

        // 3. 补全CDN前缀
        $cdnurl = \think\Config::get('upload.cdnurl');
        $avatars = [];
        foreach ($list as $url) {
            if (strpos($url, 'http') === 0) {
                $avatars[] = $url;
            } else {
                $avatars[] = rtrim($cdnurl, '/') . $url;
            }
        }

        // 4. 写入缓存
        Cache::set(self::CACHE_KEY, $avatars, self::CACHE_TTL);

        $this->success('获取成功', ['avatars' => $avatars]);
    }
}
