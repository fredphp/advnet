<?php

namespace app\admin\controller\risk;

use app\common\controller\Backend;
use think\Db;

/**
 * 白名单管理
 */
class Whitelist extends Backend
{
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\common\model\RiskWhitelist();
    }

    /**
     * 白名单列表
     */
    public function index()
    {
        if ($this->request->isAjax()) {
            $offset = $this->request->get('offset', 0);
            $limit = $this->request->get('limit', 10);
            $sort = $this->request->get('sort', 'createtime');
            $order = $this->request->get('order', 'desc');

            $type = $this->request->get('filter.type');
            $search = $this->request->get('search');

            $prefix = config('database.prefix');

            // 安全处理参数
            $sort = preg_replace('/[^a-zA-Z0-9_]/', '', $sort);
            $order = strtolower($order) === 'asc' ? 'ASC' : 'DESC';
            $offset = intval($offset);
            $limit = intval($limit);

            $whereSql = "1=1";
            if ($type) $whereSql .= " AND type = '" . addslashes($type) . "'";
            if ($search) $whereSql .= " AND (value LIKE '%" . addslashes($search) . "%' OR reason LIKE '%" . addslashes($search) . "%')";

            // 使用原生SQL查询
            $list = Db::query("
                SELECT * FROM {$prefix}risk_whitelist
                WHERE {$whereSql}
                ORDER BY {$sort} {$order}
                LIMIT {$offset}, {$limit}
            ");

            $countResult = Db::query("
                SELECT COUNT(*) as total FROM {$prefix}risk_whitelist WHERE {$whereSql}
            ");
            $total = $countResult[0]['total'] ?? 0;

            // 格式化数据
            foreach ($list as &$item) {
                $item['type_text'] = $this->getTypeText($item['type']);
            }

            return json(['total' => $total, 'rows' => $list]);
        }

        return $this->view->fetch();
    }

    /**
     * 获取类型文本
     */
    private function getTypeText($type)
    {
        $types = [
            'user' => '用户',
            'ip' => 'IP地址',
            'device' => '设备ID',
            'phone' => '手机号',
        ];
        return $types[$type] ?? $type;
    }

    /**
     * 添加白名单
     */
    public function add()
    {
        if ($this->request->isPost()) {
            $params = $this->request->post('row/a');

            // 支持直接传 user_id 参数（从用户管理页面调用）
            $userId = $this->request->post('user_id');
            if ($userId && !$params) {
                $params = [
                    'type' => 'user',
                    'value' => $userId,
                    'reason' => $this->request->post('reason', '信任用户')
                ];
            }

            if (!$params || empty($params['type']) || empty($params['value'])) {
                $this->error('参数不能为空');
            }

            $prefix = config('database.prefix');

            // 检查是否已存在
            $exists = Db::query("
                SELECT id FROM {$prefix}risk_whitelist
                WHERE type = ? AND value = ? LIMIT 1
            ", [$params['type'], $params['value']]);

            if ($exists) {
                $this->error('该记录已存在');
            }

            $now = time();

            // 使用实际表结构的字段名：enabled 而不是 status
            Db::execute("
                INSERT INTO {$prefix}risk_whitelist
                (type, value, reason, expire_time, admin_id, admin_name, enabled, createtime, updatetime)
                VALUES (?, ?, ?, NULL, ?, ?, 1, ?, ?)
            ", [
                $params['type'],
                $params['value'],
                $params['reason'] ?? '',
                $this->auth->id,
                $this->auth->username,
                $now,
                $now
            ]);

            $this->success();
        }

        return $this->view->fetch();
    }

    /**
     * 编辑白名单
     */
    public function edit($ids = null)
    {
        $prefix = config('database.prefix');
        $ids = intval($ids);

        // 使用原生SQL获取记录
        $row = Db::query("SELECT * FROM {$prefix}risk_whitelist WHERE id = ? LIMIT 1", [$ids]);

        if (empty($row)) {
            $this->error('记录不存在');
        }
        $row = $row[0];

        if ($this->request->isPost()) {
            $params = $this->request->post('row/a');

            if (!$params || empty($params['type']) || empty($params['value'])) {
                $this->error('参数不能为空');
            }

            $now = time();

            // 使用实际表结构的字段名：enabled 而不是 status
            Db::execute("
                UPDATE {$prefix}risk_whitelist
                SET type = ?, value = ?, reason = ?, enabled = ?, updatetime = ?
                WHERE id = ?
            ", [
                $params['type'],
                $params['value'],
                $params['reason'] ?? '',
                isset($params['enabled']) ? intval($params['enabled']) : 1,
                $now,
                $ids
            ]);

            $this->success();
        }

        $this->view->assign('row', $row);
        return $this->view->fetch();
    }

    /**
     * 移除白名单
     */
    public function remove()
    {
        $ids = $this->request->post('ids');

        if (!$ids) {
            $this->error('请选择要移除的记录');
        }

        $prefix = config('database.prefix');

        $ids = array_map('intval', explode(',', $ids));
        $idList = implode(',', $ids);

        Db::execute("DELETE FROM {$prefix}risk_whitelist WHERE id IN ({$idList})");

        $this->success();
    }

    /**
     * 删除（重写父类方法）
     */
    public function del($ids = "")
    {
        if (!$this->request->isPost()) {
            $this->error(__("Invalid parameters"));
        }
        $ids = $ids ? $ids : $this->request->post("ids");
        if ($ids) {
            $prefix = config('database.prefix');

            $ids = array_map('intval', is_array($ids) ? $ids : explode(',', $ids));
            $idList = implode(',', $ids);
            Db::execute("DELETE FROM {$prefix}risk_whitelist WHERE id IN ({$idList})");
            $this->success();
        }
        $this->error(__('Parameter %s can not be empty', 'ids'));
    }
}
