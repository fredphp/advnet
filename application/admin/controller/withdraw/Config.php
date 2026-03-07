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

        // 获取提现配置
        $config = SystemConfigService::getWithdrawConfig();
        
        $this->view->assign('config', $config);
        return $this->view->fetch();
    }
}
