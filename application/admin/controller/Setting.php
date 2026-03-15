<?php

namespace app\admin\controller;

use app\common\controller\Backend;
use think\Db;

/**
 * 系统设置控制器
 * 用于处理 setting 菜单下的配置管理
 */
class Setting extends Backend
{
    /**
     * 配置类型映射
     */
    protected $configTypes = [
        'coin' => '金币配置',
        'withdraw' => '提现配置',
        'invite' => '邀请配置',
        'risk' => '风控配置',
    ];
    
    /**
     * 各类型默认配置
     */
    protected $defaultConfigs = [
        'coin' => [
            'coin_rate' => '10000',
            'video_coin_reward' => '100',
            'video_watch_duration' => '30',
            'daily_video_limit' => '500',
            'daily_coin_limit' => '50000',
            'withdraw_min' => '1',
            'withdraw_max' => '500',
            'withdraw_daily_limit' => '3',
            'new_user_coin' => '1000',
        ],
        'withdraw' => [
            'withdraw_enabled' => '1',
            'withdraw_min_amount' => '1',
            'withdraw_max_amount' => '500',
            'withdraw_daily_limit' => '3',
            'withdraw_fee_rate' => '0',
            'withdraw_audit_enabled' => '1',
            'withdraw_auto_pay' => '0',
        ],
        'invite' => [
            'invite_enabled' => '1',
            'invite_level1_reward' => '1000',
            'invite_level2_reward' => '500',
            'invite_commission_enabled' => '1',
            'invite_level1_rate' => '0.1',
            'invite_level2_rate' => '0.05',
        ],
        'risk' => [
            'risk_enabled' => '1',
            'risk_auto_ban' => '1',
            'risk_ban_threshold' => '700',
            'risk_freeze_threshold' => '300',
            'risk_score_decay' => '0.1',
            'emulator_block' => '1',
            'hook_block' => '1',
            'proxy_detect' => '1',
        ],
    ];
    
    /**
     * 配置管理入口
     * 处理 setting/config/{type} 格式的URL
     */
    public function config()
    {
        // 获取配置类型
        $type = $this->request->param('action', 'coin');
        
        if (!isset($this->configTypes[$type])) {
            $this->error('配置类型不存在');
        }
        
        return $this->showConfig($type);
    }
    
    /**
     * 默认页面 - 跳转到金币配置
     */
    public function index()
    {
        $this->redirect('setting/config/coin');
    }
    
    /**
     * 显示配置页面
     */
    protected function showConfig($type)
    {
        if ($this->request->isPost()) {
            return $this->saveConfig($type);
        }
        
        // 获取配置数据
        $configs = Db::name('config')->where('group', $type)->column('value', 'name');
        
        // 默认配置
        $defaultConfigs = $this->getDefaultConfigs($type);
        
        // 合并数据库配置
        foreach ($defaultConfigs as $key => $value) {
            if (isset($configs[$key])) {
                $defaultConfigs[$key] = $configs[$key];
            }
        }
        
        $this->view->assign('configs', $defaultConfigs);
        return $this->view->fetch('setting/config/' . $type);
    }
    
    /**
     * 保存配置
     */
    protected function saveConfig($type)
    {
        $params = $this->request->post('row/a');
        
        if (empty($params)) {
            $this->error('参数错误');
        }
        
        foreach ($params as $name => $value) {
            $exists = Db::name('config')->where('name', $name)->find();
            if ($exists) {
                Db::name('config')->where('name', $name)->update([
                    'value' => $value,
                    'updatetime' => time(),
                ]);
            } else {
                Db::name('config')->insert([
                    'name' => $name,
                    'group' => $type,
                    'title' => $this->getConfigTitle($name),
                    'tip' => $this->getConfigTip($name),
                    'type' => $this->getConfigType($name),
                    'value' => $value,
                    'createtime' => time(),
                    'updatetime' => time(),
                ]);
            }
        }
        
        $this->success('保存成功');
    }
    
    /**
     * 获取默认配置
     */
    protected function getDefaultConfigs($type)
    {
        $configs = [
            'coin' => [
                'coin_rate' => '10000',
                'video_coin_reward' => '100',
                'video_watch_duration' => '30',
                'daily_video_limit' => '500',
                'daily_coin_limit' => '50000',
                'withdraw_min' => '1',
                'withdraw_max' => '500',
                'withdraw_daily_limit' => '3',
                'new_user_coin' => '1000',
            ],
            'withdraw' => [
                'withdraw_enabled' => '1',
                'withdraw_min_amount' => '1',
                'withdraw_max_amount' => '500',
                'withdraw_daily_limit' => '3',
                'withdraw_fee_rate' => '0',
                'withdraw_audit_enabled' => '1',
                'withdraw_auto_pay' => '0',
            ],
            'invite' => [
                'invite_enabled' => '1',
                'invite_level1_reward' => '1000',
                'invite_level2_reward' => '500',
                'invite_commission_enabled' => '1',
                'invite_level1_rate' => '0.1',
                'invite_level2_rate' => '0.05',
            ],
            'risk' => [
                'risk_enabled' => '1',
                'risk_auto_ban' => '1',
                'risk_ban_threshold' => '700',
                'risk_freeze_threshold' => '300',
                'risk_score_decay' => '0.1',
                'emulator_block' => '1',
                'hook_block' => '1',
                'proxy_detect' => '1',
            ],
        ];
        
        return $configs[$type] ?? [];
    }
    
    /**
     * 获取配置标题
     */
    protected function getConfigTitle($name)
    {
        $titles = [
            'coin_rate' => '金币汇率',
            'video_coin_reward' => '视频观看奖励',
            'video_watch_duration' => '有效观看时长',
            'daily_video_limit' => '每日视频上限',
            'daily_coin_limit' => '每日金币上限',
            'withdraw_min' => '最低提现金额',
            'withdraw_max' => '最大提现金额',
            'withdraw_daily_limit' => '每日提现次数',
            'new_user_coin' => '新用户奖励',
            'withdraw_enabled' => '提现开关',
            'withdraw_min_amount' => '最低提现金额',
            'withdraw_max_amount' => '最大提现金额',
            'withdraw_fee_rate' => '提现手续费率',
            'withdraw_audit_enabled' => '提现审核',
            'withdraw_auto_pay' => '自动打款',
            'invite_enabled' => '邀请开关',
            'invite_level1_reward' => '一级邀请奖励',
            'invite_level2_reward' => '二级邀请奖励',
            'invite_commission_enabled' => '分佣开关',
            'invite_level1_rate' => '一级分佣比例',
            'invite_level2_rate' => '二级分佣比例',
            'risk_enabled' => '风控开关',
            'risk_auto_ban' => '自动封禁',
            'risk_ban_threshold' => '封禁阈值',
            'risk_freeze_threshold' => '冻结阈值',
            'risk_score_decay' => '分数衰减率',
            'emulator_block' => '模拟器拦截',
            'hook_block' => 'Hook拦截',
            'proxy_detect' => '代理检测',
        ];
        
        return $titles[$name] ?? $name;
    }

    /**
     * 获取配置提示
     */
    protected function getConfigTip($name)
    {
        $tips = [
            'coin_rate' => '多少金币等于1元人民币',
            'video_coin_reward' => '每次有效观看奖励的金币数量',
            'video_watch_duration' => '观看多少秒才算有效观看',
            'daily_video_limit' => '用户每日可观看视频次数上限',
            'daily_coin_limit' => '用户每日可获取金币上限',
            'withdraw_min' => '用户最低提现金额（元）',
            'withdraw_max' => '用户单次最大提现金额（元）',
            'withdraw_daily_limit' => '用户每日提现次数上限',
            'new_user_coin' => '新用户注册奖励金币数量',
        ];
        
        return $tips[$name] ?? '';
    }

    /**
     * 获取配置类型
     */
    protected function getConfigType($name)
    {
        $switches = ['withdraw_enabled', 'withdraw_audit_enabled', 'withdraw_auto_pay', 
                     'invite_enabled', 'invite_commission_enabled', 'risk_enabled', 
                     'risk_auto_ban', 'emulator_block', 'hook_block', 'proxy_detect'];
        
        if (in_array($name, $switches)) {
            return 'switch';
        }
        
        return 'string';
    }
}
