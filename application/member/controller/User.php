<?php

namespace app\member\controller;

use app\common\controller\Backend;
use app\member\model\User as UserModel;
use think\Db;
use think\exception\PDOException;
use think\exception\ValidateException;

/**
 * 会员管理
 */
class User extends Backend
{
    /**
     * User模型对象
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new UserModel();
    }

    /**
     * 会员统计
     */
    public function statistics()
    {
        $start_date = $this->request->get('start_date', date('Y-m-d', strtotime('-30 days')));
        $end_date = $this->request->get('end_date', date('Y-m-d'));

        if (false === $this->request->isAjax()) {
            return $this->view->fetch();
        }

        // 统计数据
        $startTime = strtotime($start_date);
        $endTime = strtotime($end_date . ' 23:59:59');

        // 总会员数
        $totalUsers = $this->model->count();

        // 新增会员数
        $newUsers = $this->model->where('createtime', 'between', [$startTime, $endTime])->count();

        // 活跃会员数
        $activeUsers = $this->model->where('logintime', 'between', [$startTime, $endTime])->count();

        // 按日期统计新增会员
        $dailyStats = $this->model
            ->where('createtime', 'between', [$startTime, $endTime])
            ->field("FROM_UNIXTIME(createtime, '%Y-%m-%d') as date, COUNT(*) as count")
            ->group('date')
            ->select();

        $result = [
            'total' => [
                'total_users' => $totalUsers,
                'new_users' => $newUsers,
                'active_users' => $activeUsers,
            ],
            'daily' => $dailyStats,
            'start_date' => $start_date,
            'end_date' => $end_date,
        ];

        return json($result);
    }

    /**
     * 查看
     */
    public function index()
    {
        $this->request->filter(['strip_tags', 'trim']);
        if (false === $this->request->isAjax()) {
            return $this->view->fetch();
        }
        if ($this->request->request('keyField')) {
            return $this->selectpage();
        }
        [$where, $sort, $order, $offset, $limit] = $this->buildparams();
        $list = $this->model
            ->where($where)
            ->order($sort, $order)
            ->paginate($limit);
        $result = ['total' => $list->total(), 'rows' => $list->items()];
        return json($result);
    }

    /**
     * 添加
     */
    public function add()
    {
        if (false === $this->request->isPost()) {
            return $this->view->fetch();
        }
        $params = $this->request->post('row/a');
        if (empty($params)) {
            $this->error(__('Parameter %s can not be empty', ''));
        }
        $params = $this->preExcludeFields($params);

        if ($this->dataLimit && $this->dataLimitFieldAutoFill) {
            $params[$this->dataLimitField] = $this->auth->id;
        }
        $result = false;
        Db::startTrans();
        try {
            if ($this->modelValidate) {
                $name = str_replace("\\model\\", "\\validate\\", get_class($this->model));
                $validate = is_bool($this->modelValidate) ? ($this->modelSceneValidate ? $name . '.add' : $name) : $this->modelValidate;
                $this->model->validateFailException()->validate($validate);
            }
            $result = $this->model->allowField(true)->save($params);
            Db::commit();
        } catch (ValidateException|PDOException|\Exception $e) {
            Db::rollback();
            $this->error($e->getMessage());
        }
        if ($result === false) {
            $this->error(__('No rows were inserted'));
        }
        $this->success();
    }

    /**
     * 编辑
     */
    public function edit($ids = null)
    {
        $row = $this->model->get($ids);
        if (!$row) {
            $this->error(__('No Results were found'));
        }
        $adminIds = $this->getDataLimitAdminIds();
        if (is_array($adminIds) && !in_array($row[$this->dataLimitField], $adminIds)) {
            $this->error(__('You have no permission'));
        }
        if (false === $this->request->isPost()) {
            $this->view->assign('row', $row);
            return $this->view->fetch();
        }
        $params = $this->request->post('row/a');
        if (empty($params)) {
            $this->error(__('Parameter %s can not be empty', ''));
        }
        $params = $this->preExcludeFields($params);
        $result = false;
        Db::startTrans();
        try {
            if ($this->modelValidate) {
                $name = str_replace("\\model\\", "\\validate\\", get_class($this->model));
                $validate = is_bool($this->modelValidate) ? ($this->modelSceneValidate ? $name . '.edit' : $name) : $this->modelValidate;
                $row->validateFailException()->validate($validate);
            }
            $result = $row->allowField(true)->save($params);
            Db::commit();
        } catch (ValidateException|PDOException|\Exception $e) {
            Db::rollback();
            $this->error($e->getMessage());
        }
        if ($result === false) {
            $this->error(__('No rows were updated'));
        }
        $this->success();
    }

    /**
     * 删除
     */
    public function del($ids = null)
    {
        if (false === $this->request->isPost()) {
            $this->error(__("Invalid parameters"));
        }
        $ids = $ids ?: $this->request->post("ids");
        if (empty($ids)) {
            $this->error(__('Parameter %s can not be empty', 'ids'));
        }
        $pk = $this->model->getPk();
        $adminIds = $this->getDataLimitAdminIds();
        if (is_array($adminIds)) {
            $this->model->where($this->dataLimitField, 'in', $adminIds);
        }
        $list = $this->model->where($pk, 'in', $ids)->select();

        $count = 0;
        Db::startTrans();
        try {
            foreach ($list as $item) {
                $count += $item->delete();
            }
            Db::commit();
        } catch (PDOException|\Exception $e) {
            Db::rollback();
            $this->error($e->getMessage());
        }
        if ($count) {
            $this->success();
        }
        $this->error(__('No rows were deleted'));
    }

    /**
     * 获取系统会员数量统计
     */
    public function getSystemMemberCount()
    {
        $systemCount = $this->model->where('user_type', 1)->count();
        $realCount = $this->model->where('user_type', 0)->count();
        $totalCount = $this->model->count();

        $this->success('', [
            'system_count' => $systemCount,
            'real_count' => $realCount,
            'total_count' => $totalCount,
        ]);
    }

    /**
     * 批量生成系统会员
     * 头像从附件库中随机分配
     */
    public function generateSystemMembers()
    {
        $count = intval($this->request->post('count', 10));
        $password = $this->request->post('password', 'qwe123');

        if ($count <= 0 || $count > 500) {
            $this->error('生成数量需在1~500之间');
        }
        if (strlen($password) < 4) {
            $this->error('密码长度不能少于4位');
        }

        // 从附件表随机获取头像图片
        $avatarList = Db::name('attachment')
            ->where('mimetype', 'like', 'image/%')
            ->where('url', '<>', '')
            ->column('url');
        $hasAvatar = !empty($avatarList);

        // 昵称库
        $nicknames = [
            '夏天的风', '阳光少年', '暴走萝莉', '奶茶续命', '佛系青年',
            '烟火人间', '暮色四合', '逍遥游', '追风少年', '樱花落尽',
            '蓝色海岸', '星辰大海', '浅笑安然', '温柔以待', '月光如水',
            '青春纪念册', '繁花似锦', '清风明月', '踏雪寻梅', '南风知我意',
            '一叶知秋', '浮生若梦', '云淡风轻', '时光旅人', '静水深流',
            '紫霞仙子', '闲云野鹤', '雨后初晴', '桃花源记', '诗和远方',
            '寻梦环游', '岁月静好', '山河故人', '晚风轻拂', '林间小路',
            '晨曦微露', '花间一壶酒', '竹林听雨', '秋水共长天', '江上清风',
            '红尘客栈', '归来少年', '海上生明月', '雪落无声', '春风十里',
            '半夏微凉', '柠檬不酸', '薄荷微凉', '南城旧事', '拾光者',
            'Alex Chen', 'Emma Wilson', 'Jake Miller', 'Sophia Lee', 'Noah Zhang',
            'Olivia Wang', 'Liam Liu', 'Ava Chen', 'Ethan Huang', 'Isabella Wu',
            'Mason Xu', 'Mia Zhao', 'Logan Yang', 'Charlotte Sun', 'Aiden Zhou',
            'Amelia Zheng', 'Lucas Feng', 'Harper Lin', 'Henry Wu', 'Evelyn He',
            'Daniel Kim', 'Abigail Park', 'Michael Chang', 'Emily Song', 'James Yoon',
            'Elizabeth Cho', 'Benjamin Tang', 'Chloe Guan', 'William Hao', 'Avery Jiang',
            '大卫Walker', '小李飞刀', '安娜贝尔', '马克思K', '海阔天空',
            'Jack王同学', 'Rose李小姐', '大胡子Bob', 'Vicky张', '小土豆Tom',
            '花花世界', 'Kevin刘总', '一杯咖啡', 'Cici陈', 'Leo王大锤',
            '自由飞翔', 'Amy赵小花', '大白兔Miki', 'Tony孙大圣', '猫和鱼Fish',
            '数字猎人', '代码诗人', '像素冒险', '比特行者', '量子漫步',
            '云端织梦', '银河信使', '极光守望', '晨光微熹', '夜色温柔',
            '笑忘书', '逆光飞翔', '时光煮雨', '微风不燥', '星辰向北',
            '南风过境', '十里桃花', '月下独酌', '浮云游子', '落花有意',
        ];

        // 手机号前缀
        $mobilePrefixes = ['130','131','132','133','135','136','137','138','139','150','151','152','155','156','157','158','159','170','176','177','178','180','181','182','183','185','186','187','188','189'];

        $now = time();
        $success = 0;

        Db::startTrans();
        try {
            for ($i = 0; $i < $count; $i++) {
                $username = 'sys_' . str_pad(mt_rand(100000, 999999), 6, '0', STR_PAD_LEFT);
                $nickname = $nicknames[mt_rand(0, count($nicknames) - 1)];
                $salt = $this->generateSalt();
                $encryptedPassword = md5(md5($password) . $salt);
                $mobile = $mobilePrefixes[mt_rand(0, count($mobilePrefixes) - 1)] . str_pad(mt_rand(10000000, 99999999), 8, '0');
                $avatar = $hasAvatar ? $avatarList[mt_rand(0, count($avatarList) - 1)] : '';
                $createtime = $now - mt_rand(30 * 86400, 365 * 86400);

                try {
                    $this->model->insert([
                        'username'   => $username,
                        'nickname'   => $nickname,
                        'password'   => $encryptedPassword,
                        'salt'       => $salt,
                        'mobile'     => $mobile,
                        'avatar'     => $avatar,
                        'user_type'  => 1,
                        'status'     => 'normal',
                        'level'      => 0,
                        'gender'     => mt_rand(0, 2),
                        'score'      => 0,
                        'successions' => 1,
                        'maxsuccessions' => 1,
                        'joinip'     => mt_rand(1, 254) . '.' . mt_rand(0, 255) . '.' . mt_rand(0, 255) . '.' . mt_rand(1, 254),
                        'jointime'   => $createtime,
                        'createtime' => $createtime,
                        'updatetime' => $now,
                        'source'     => 'system',
                    ]);
                    $success++;
                } catch (\Exception $e) {
                    if (strpos($e->getMessage(), 'Duplicate') !== false) {
                        continue;
                    }
                    throw $e;
                }
            }
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            $this->error('生成失败: ' . $e->getMessage());
        }

        $totalSystemMembers = $this->model->where('user_type', 1)->count();
        $this->success('成功生成 ' . $success . ' 个系统会员', [
            'success' => $success,
            'total_system_members' => $totalSystemMembers,
            'has_avatar' => $hasAvatar,
        ]);
    }

    /**
     * 生成随机盐值
     */
    private function generateSalt()
    {
        $chars = 'abcdefghijklmnopqrstuvwxyz0123456789';
        $salt = '';
        for ($i = 0; $i < 6; $i++) {
            $salt .= $chars[mt_rand(0, strlen($chars) - 1)];
        }
        return $salt;
    }
}
