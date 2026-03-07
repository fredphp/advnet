<?php

namespace app\admin\controller\withdraw;

use app\common\controller\Backend;
use app\common\library\SystemConfigService;
use think\Db;

/**
 * 提现配置管理
 */
class Config extends Backend
{
    protected $model = null;
    protected $noNeedRight = ['index'];

    public function _initialize()
    {
        parent::_initialize();
    }

    /**
     * 配置列表
     */
    public function index()
    {
        if ($this->request->isPost()) {
            $data = $this->request->post('row/a');
            
            if (empty($data) || !is_array($data)) {
                $this->error('参数错误');
            }

            Db::startTrans();
            try {
                foreach ($data as $key => $value) {
                    // 确保值是标量类型
                    if (!is_scalar($value)) {
                        continue;
                    }
                    SystemConfigService::set('withdraw', $key, $value);
                }
                
                Db::commit();
                // 返回当前URL让前端刷新页面
                $this->success('保存成功', null, $this->request->url());
            } catch (\Exception $e) {
                Db::rollback();
                $this->error($e->getMessage());
            }
        }

        try {
            // 获取默认配置
            $defaults = SystemConfigService::getDefaults();
            $defaultWithdraw = [];
            if (is_array($defaults) && isset($defaults['withdraw']) && is_array($defaults['withdraw'])) {
                $defaultWithdraw = $defaults['withdraw'];
            }
            
            // 获取提现配置
            $config = SystemConfigService::getWithdrawConfig();
            
            // 确保是数组
            if (!is_array($config)) {
                $config = [];
            }
            
            // 合并默认值，确保所有字段都有值
            $config = array_merge($defaultWithdraw, $config);
            
            // 确保所有值都是标量类型，避免模板渲染问题
            foreach ($config as $key => $value) {
                if (!is_scalar($value) && !is_null($value)) {
                    $config[$key] = is_array($value) ? json_encode($value) : strval($value);
                }
            }
            
        } catch (\Exception $e) {
            // 出错时使用默认配置
            $config = [
                'withdraw_enabled' => 1,
                'withdraw_amounts' => '10,20,50,100',
                'min_withdraw' => 1,
                'max_withdraw' => 500,
                'daily_withdraw_limit' => 3,
                'daily_withdraw_amount' => 500,
                'same_ip_limit' => 5,
                'same_device_limit' => 3,
                'auto_audit_amount' => 10,
                'manual_audit_amount' => 50,
                'new_user_withdraw_days' => 3,
                'fee_rate' => 0,
                'transfer_retry_count' => 3,
                'transfer_retry_interval' => 300,
            ];
        }
        
        $this->view->assign('config', $config);
        return $this->view->fetch();
    }
}
