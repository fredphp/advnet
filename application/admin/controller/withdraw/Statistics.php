<?php

namespace app\admin\controller\withdraw;

use app\common\controller\Backend;

/**
 * 提现统计（重定向到订单统计）
 */
class Statistics extends Backend
{
    public function _initialize()
    {
        parent::_initialize();
    }

    /**
     * 重定向到订单统计页面
     */
    public function index()
    {
        // 直接重定向到 withdraw/order/statistics
        $this->redirect('withdraw/order/statistics');
    }
}
