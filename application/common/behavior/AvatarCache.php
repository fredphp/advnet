<?php

namespace app\common\behavior;

use think\Cache;
use think\Hook;
use think\Db;

/**
 * 头像缓存自动刷新行为
 * 
 * 在 upload_after 钩子中自动清除头像列表缓存，
 * 确保后台上传新头像附件后，前端下次请求能获取到最新列表
 */
class AvatarCache
{
    public function run(&$params)
    {
        // 只处理图片类型附件
        if ($params && isset($params['mimetype'])) {
            if (strpos($params['mimetype'], 'image/') === 0) {
                Cache::rm('chat_avatar_list');
            }
        }
    }
}
