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
            
            if (empty($data)) {
                $this->error('参数错误');
            }

            Db::startTrans();
            try {
                foreach ($data as $key => $value) {
                    SystemConfigService::set('withdraw', $key, $value);
                }
                
                Db::commit();
                $this->success('保存成功');
            } catch (\Exception $e) {
                Db::rollback();
                $this->error($e->getMessage());
            }
        }

        // 获取默认配置
        $defaults = SystemConfigService::getDefaults();
        $defaultWithdraw = isset($defaults['withdraw']) ? $defaults['withdraw'] : [];
        
        // 获取提现配置
        $config = SystemConfigService::getWithdrawConfig();
        
        // 确保是数组
        if (!is_array($config)) {
            $config = [];
        }
        
        // 合并默认值，确保所有字段都有值
        $config = array_merge($defaultWithdraw, $config);
        
        $this->view->assign('config', $config);
        return $this->view->fetch();
    }
}
