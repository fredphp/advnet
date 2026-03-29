<?php

namespace app\admin\controller\risk;

use app\common\controller\Backend;
use think\Db;

/**
 * 风控数据初始化
 */
class Initdata extends Backend
{
    protected $noNeedLogin = ['index', 'install'];
    protected $noNeedRight = ['index', 'install'];
    
    /**
     * 初始化页面
     */
    public function index()
    {
        if ($this->request->isPost()) {
            return $this->install();
        }
        
        // 检查表是否存在
        $tables = $this->checkTables();
        
        $this->view->assign('tables', $tables);
        return $this->view->fetch();
    }
    
    /**
     * 执行安装
     */
    public function install()
    {
        $sqlFile = ROOT_PATH . 'sql/risk_demo_data_advn.sql';
        
        if (!file_exists($sqlFile)) {
            $this->error('SQL文件不存在');
        }
        
        $sql = file_get_contents($sqlFile);
        $statements = explode(";\n", $sql);
        
        $results = [
            'tables' => [],
            'data' => [],
            'errors' => [],
        ];
        
        foreach ($statements as $statement) {
            $statement = trim($statement);
            if (empty($statement) || $statement === ';') {
                continue;
            }
            
            // 跳过注释
            if (strpos($statement, '--') === 0) {
                continue;
            }
            
            try {
                Db::execute($statement);
                
                if (stripos($statement, 'CREATE TABLE') !== false) {
                    if (preg_match('/`(\w+)`/', $statement, $matches)) {
                        $results['tables'][] = $matches[1];
                    }
                } elseif (stripos($statement, 'INSERT INTO') !== false) {
                    if (preg_match('/`(\w+)`/', $statement, $matches)) {
                        $results['data'][$matches[1]] = ($results['data'][$matches[1]] ?? 0) + 1;
                    }
                }
            } catch (\Exception $e) {
                // 忽略已存在的错误和重复键错误
                if (strpos($e->getMessage(), 'already exists') === false && 
                    strpos($e->getMessage(), 'Duplicate') === false) {
                    $results['errors'][] = substr($statement, 0, 80) . '... : ' . $e->getMessage();
                }
            }
        }
        
        // 验证数据
        $results['verify'] = $this->checkTables();
        
        $this->success('初始化完成', null, $results);
    }
    
    /**
     * 检查表是否存在及数据量
     */
    private function checkTables()
    {
        $tables = [
            'risk_rule' => '风控规则',
            'user_risk_score' => '用户风险评分',
            'ban_record' => '封禁记录',
            'risk_log' => '风险日志',
            'risk_blacklist' => '黑名单',
            'risk_whitelist' => '白名单',
            'device_fingerprint' => '设备指纹',
            'user_behavior_stat' => '用户行为统计',
            'risk_stat' => '风控统计',
        ];
        
        $result = [];
        foreach ($tables as $table => $name) {
            try {
                $count = Db::name($table)->count();
                $result[$table] = [
                    'name' => $name,
                    'exists' => true,
                    'count' => $count,
                ];
            } catch (\Exception $e) {
                $result[$table] = [
                    'name' => $name,
                    'exists' => false,
                    'count' => 0,
                ];
            }
        }
        
        return $result;
    }
}
