<?php

namespace app\admin\controller;

use app\common\controller\Backend;

/**
 * 系统设置入口
 */
class Setting extends Backend
{
    /**
     * 默认跳转到金币配置
     */
    public function index()
    {
        // 默认跳转到金币配置页面
        $this->redirect('setting/config/coin');
    }
}
