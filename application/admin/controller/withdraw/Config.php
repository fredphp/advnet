<?php

namespace app\admin\controller\withdraw;

use app\common\controller\Backend;
use think\Db;

/**
 * 提现配置管理
 */
class Config extends Backend
{
    protected $model = null;
    protected $noNeedRight = ['index'];

    // 默认配置
    protected $defaultConfig = [
        'enabled' => '1',
        'amounts' => '10,20,50,100',
        'min_withdraw' => '1',
        'max_withdraw' => '500',
        'daily_withdraw_limit' => '3',
        'daily_withdraw_amount' => '500',
        'same_ip_limit' => '5',
        'same_device_limit' => '3',
        'auto_audit_amount' => '10',
        'manual_audit_amount' => '50',
        'new_user_withdraw_days' => '3',
        'fee_rate' => '0',
        'transfer_retry_count' => '3',
        'transfer_retry_interval' => '300',
    ];

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
                    if (!is_scalar($value)) {
                        continue;
                    }
                    $this->setConfig('withdraw_' . $key, $value);
                }
                
                Db::commit();
                $this->success('保存成功');
            } catch (\Exception $e) {
                Db::rollback();
                $this->error($e->getMessage());
            }
        }

        // 读取配置
        $config = $this->getConfig();
        
        $this->view->assign('config', $config);
        return $this->view->fetch();
    }

    /**
     * 获取配置
     */
    protected function getConfig()
    {
        $config = $this->defaultConfig;
        
        try {
            $list = Db::name('config')
                ->where('name', 'like', 'withdraw_%')
                ->select();
            
            foreach ($list as $item) {
                $key = str_replace('withdraw_', '', $item['name']);
                if (isset($config[$key])) {
                    $config[$key] = $item['value'];
                }
            }
        } catch (\Exception $e) {
            // 使用默认配置
        }
        
        return $config;
    }

    /**
     * 设置配置
     */
    protected function setConfig($name, $value)
    {
        $exists = Db::name('config')
            ->where('name', $name)
            ->find();
        
        $time = time();
        
        if ($exists) {
            Db::name('config')
                ->where('name', $name)
                ->update([
                    'value' => $value,
                    'updatetime' => $time,
                ]);
        } else {
            Db::name('config')->insert([
                'name' => $name,
                'group' => 'withdraw',
                'title' => $this->getConfigTitle($name),
                'tip' => '',
                'type' => 'string',
                'value' => $value,
                'content' => '',
                'rule' => '',
                'extend' => '',
                'setting' => '',
                'status' => 1,
                'createtime' => $time,
                'updatetime' => $time,
            ]);
        }
    }

    /**
     * 获取配置标题
     */
    protected function getConfigTitle($name)
    {
        $titles = [
            'withdraw_enabled' => '开启提现',
            'withdraw_amounts' => '可选提现金额',
            'withdraw_min_withdraw' => '最低提现金额',
            'withdraw_max_withdraw' => '最高提现金额',
            'withdraw_daily_withdraw_limit' => '每日提现次数',
            'withdraw_daily_withdraw_amount' => '每日提现金额',
            'withdraw_same_ip_limit' => '同IP提现次数',
            'withdraw_same_device_limit' => '同设备提现次数',
            'withdraw_auto_audit_amount' => '自动审核金额',
            'withdraw_manual_audit_amount' => '人工审核金额',
            'withdraw_new_user_withdraw_days' => '新用户提现天数',
            'withdraw_fee_rate' => '提现手续费率',
            'withdraw_transfer_retry_count' => '提现重试次数',
            'withdraw_transfer_retry_interval' => '重试间隔',
        ];
        
        return $titles[$name] ?? $name;
    }
}
