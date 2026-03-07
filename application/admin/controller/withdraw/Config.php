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
     * 配置页面
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
                foreach ($data as $code => $value) {
                    if (!is_scalar($value)) {
                        continue;
                    }
                    $this->setConfig($code, (string)$value);
                }
                
                Db::commit();
                // 使用 json 返回标准响应格式
                return json(['code' => 1, 'msg' => '保存成功']);
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
            $list = Db::name('withdraw_config')->select();
            
            foreach ($list as $item) {
                if (isset($config[$item['code']])) {
                    $config[$item['code']] = $item['value'];
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
    protected function setConfig($code, $value)
    {
        if (!isset($this->defaultConfig[$code])) {
            return;
        }
        
        $exists = Db::name('withdraw_config')
            ->where('code', $code)
            ->find();
        
        $time = time();
        
        if ($exists) {
            Db::name('withdraw_config')
                ->where('code', $code)
                ->update([
                    'value' => $value,
                    'updatetime' => $time,
                ]);
        } else {
            Db::name('withdraw_config')->insert([
                'name' => $this->getConfigName($code),
                'code' => $code,
                'value' => $value,
                'type' => 'string',
                'title' => $this->getConfigName($code),
                'remark' => '',
                'group' => 'withdraw',
                'sort' => 0,
                'createtime' => $time,
                'updatetime' => $time,
            ]);
        }
    }

    /**
     * 获取配置名称
     */
    protected function getConfigName($code)
    {
        $names = [
            'enabled' => '开启提现',
            'amounts' => '可选提现金额',
            'min_withdraw' => '最低提现金额',
            'max_withdraw' => '最高提现金额',
            'daily_withdraw_limit' => '每日提现次数',
            'daily_withdraw_amount' => '每日提现金额',
            'same_ip_limit' => '同IP提现次数',
            'same_device_limit' => '同设备提现次数',
            'auto_audit_amount' => '自动审核金额',
            'manual_audit_amount' => '人工审核金额',
            'new_user_withdraw_days' => '新用户提现天数',
            'fee_rate' => '提现手续费率',
            'transfer_retry_count' => '提现重试次数',
            'transfer_retry_interval' => '重试间隔',
        ];
        
        return $names[$code] ?? $code;
    }
}
