<?php

namespace app\admin\controller\setting;

use app\common\controller\Backend;
use think\Db;

/**
 * 系统配置管理
 */
class Config extends Backend
{
    /**
     * 金币配置
     */
    public function coin()
    {
        $group = 'coin';
        
        if ($this->request->isPost()) {
            $params = $this->request->post('row/a');
            
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
                        'group' => $group,
                        'title' => $this->getConfigTitle($name),
                        'tip' => $this->getConfigTip($name),
                        'type' => $this->getConfigType($name),
                        'value' => $value,
                        'createtime' => time(),
                        'updatetime' => time(),
                    ]);
                }
            }
            
            $this->success();
        }

        $configs = Db::name('config')->where('group', $group)->column('value', 'name');
        
        $defaultConfigs = [
            'coin_rate' => $configs['coin_rate'] ?? '10000',
            'video_coin_reward' => $configs['video_coin_reward'] ?? '100',
            'video_watch_duration' => $configs['video_watch_duration'] ?? '30',
            'daily_video_limit' => $configs['daily_video_limit'] ?? '500',
            'daily_coin_limit' => $configs['daily_coin_limit'] ?? '50000',
            'withdraw_min' => $configs['withdraw_min'] ?? '1',
            'withdraw_max' => $configs['withdraw_max'] ?? '500',
            'withdraw_daily_limit' => $configs['withdraw_daily_limit'] ?? '3',
            'new_user_coin' => $configs['new_user_coin'] ?? '1000',
        ];

        $this->view->assign('configs', $defaultConfigs);
        return $this->view->fetch();
    }

    /**
     * 提现配置
     */
    public function withdraw()
    {
        $group = 'withdraw';
        
        if ($this->request->isPost()) {
            $params = $this->request->post('row/a');
            
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
                        'group' => $group,
                        'title' => $this->getConfigTitle($name),
                        'tip' => $this->getConfigTip($name),
                        'type' => $this->getConfigType($name),
                        'value' => $value,
                        'createtime' => time(),
                        'updatetime' => time(),
                    ]);
                }
            }
            
            $this->success();
        }

        $configs = Db::name('config')->where('group', $group)->column('value', 'name');
        
        $defaultConfigs = [
            'withdraw_enabled' => $configs['withdraw_enabled'] ?? '1',
            'withdraw_min_amount' => $configs['withdraw_min_amount'] ?? '1',
            'withdraw_max_amount' => $configs['withdraw_max_amount'] ?? '500',
            'withdraw_daily_limit' => $configs['withdraw_daily_limit'] ?? '3',
            'withdraw_fee_rate' => $configs['withdraw_fee_rate'] ?? '0',
            'withdraw_audit_enabled' => $configs['withdraw_audit_enabled'] ?? '1',
            'withdraw_auto_pay' => $configs['withdraw_auto_pay'] ?? '0',
        ];

        $this->view->assign('configs', $defaultConfigs);
        return $this->view->fetch();
    }

    /**
     * 邀请配置
     */
    public function invite()
    {
        $group = 'invite';
        
        if ($this->request->isPost()) {
            $params = $this->request->post('row/a');
            
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
                        'group' => $group,
                        'title' => $this->getConfigTitle($name),
                        'tip' => $this->getConfigTip($name),
                        'type' => $this->getConfigType($name),
                        'value' => $value,
                        'createtime' => time(),
                        'updatetime' => time(),
                    ]);
                }
            }
            
            $this->success();
        }

        $configs = Db::name('config')->where('group', $group)->column('value', 'name');
        
        $defaultConfigs = [
            'invite_enabled' => $configs['invite_enabled'] ?? '1',
            'invite_level1_reward' => $configs['invite_level1_reward'] ?? '1000',
            'invite_level2_reward' => $configs['invite_level2_reward'] ?? '500',
            'invite_commission_enabled' => $configs['invite_commission_enabled'] ?? '1',
            'invite_level1_rate' => $configs['invite_level1_rate'] ?? '0.1',
            'invite_level2_rate' => $configs['invite_level2_rate'] ?? '0.05',
        ];

        $this->view->assign('configs', $defaultConfigs);
        return $this->view->fetch();
    }

    /**
     * 风控配置
     */
    public function risk()
    {
        $group = 'risk';
        
        if ($this->request->isPost()) {
            $params = $this->request->post('row/a');
            
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
                        'group' => $group,
                        'title' => $this->getConfigTitle($name),
                        'tip' => $this->getConfigTip($name),
                        'type' => $this->getConfigType($name),
                        'value' => $value,
                        'createtime' => time(),
                        'updatetime' => time(),
                    ]);
                }
            }
            
            $this->success();
        }

        $configs = Db::name('config')->where('group', $group)->column('value', 'name');
        
        $defaultConfigs = [
            'risk_enabled' => $configs['risk_enabled'] ?? '1',
            'risk_auto_ban' => $configs['risk_auto_ban'] ?? '1',
            'risk_ban_threshold' => $configs['risk_ban_threshold'] ?? '700',
            'risk_freeze_threshold' => $configs['risk_freeze_threshold'] ?? '300',
            'risk_score_decay' => $configs['risk_score_decay'] ?? '0.1',
            'emulator_block' => $configs['emulator_block'] ?? '1',
            'hook_block' => $configs['hook_block'] ?? '1',
            'proxy_detect' => $configs['proxy_detect'] ?? '1',
        ];

        $this->view->assign('configs', $defaultConfigs);
        return $this->view->fetch();
    }

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
