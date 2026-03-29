<?php

namespace app\admin\controller\withdraw;

use app\common\controller\Backend;
use app\common\library\SystemConfigService;
use app\common\model\Config as ConfigModel;
use think\Db;

/**
 * 提现配置管理
 * 
 * 配置已统一到系统配置（advn_config表）
 * 此控制器作为入口跳转到系统配置的提现配置分组
 */
class Config extends Backend
{
    protected $model = null;
    protected $noNeedRight = ['index'];

    /**
     * 配置页面
     * 重定向到系统配置页面的提现配置分组
     */
    public function index()
    {
        // 直接跳转到系统配置页面的提现配置分组
        $this->redirect('general/config/index', ['group' => 'withdraw']);
    }
    
    /**
     * 获取提现配置（供其他模块调用）
     * @return array
     */
    public static function getWithdrawConfig()
    {
        return SystemConfigService::getWithdrawConfig();
    }
    
    /**
     * 获取金币汇率（供其他模块调用）
     * @return int
     */
    public static function getCoinRate()
    {
        return SystemConfigService::getCoinRate();
    }
    
    /**
     * 金币转人民币
     * @param int $coin 金币数量
     * @return float
     */
    public static function coinToCash($coin)
    {
        return SystemConfigService::coinToCash($coin);
    }
    
    /**
     * 人民币转金币
     * @param float $cash 人民币金额
     * @return int
     */
    public static function cashToCoin($cash)
    {
        return SystemConfigService::cashToCoin($cash);
    }
}
