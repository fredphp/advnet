<?php

namespace app\admin\controller\migration;

use app\common\controller\Backend;
use think\Db;

/**
 * 数据迁移配置
 */
class Config extends Backend
{
    /**
     * 配置页面
     */
    public function index()
    {
        if ($this->request->isPost()) {
            $params = $this->request->post('row/a');
            
            foreach ($params as $name => $value) {
                $exists = Db::name('migration_config')->where('name', $name)->find();
                if ($exists) {
                    Db::name('migration_config')->where('name', $name)->update([
                        'value' => $value,
                        'updatetime' => time(),
                    ]);
                } else {
                    Db::name('migration_config')->insert([
                        'name' => $name,
                        'value' => $value,
                        'createtime' => time(),
                        'updatetime' => time(),
                    ]);
                }
            }
            
            $this->success();
        }

        $configs = Db::name('migration_config')->column('value', 'name');
        
        $this->view->assign('configs', $configs);
        return $this->view->fetch();
    }
}
