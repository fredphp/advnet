<?php

namespace app\api\controller;

use app\common\controller\Api;
use app\common\library\CoinService;
use app\common\library\Ems;
use app\common\library\Sms;
use app\common\model\UserCommissionStat;
use fast\Random;
use think\Validate;

/**
 * 会员接口
 */
class User extends Api
{
    protected $noNeedLogin = ['login', 'mobilelogin', 'register', 'resetpwd', 'changeemail', 'changemobile', 'third'];
    protected $noNeedRight = '*';

    public function _initialize()
    {
        parent::_initialize();
    }

    /**
     * 获取个人资料
     * GET /api/user/profile
     * 返回当前用户的详细个人资料信息，用于编辑页面初始化
     */
    public function getProfile()
    {
        $user = $this->auth->getUser();
        if (!$user) {
            $this->error('用户不存在');
        }

        $profile = [
            'id'        => $user->id,
            'avatar'    => $user->avatar ? cdnurl($user->avatar) : '',
            'username'  => $user->username ?: '',
            'nickname'  => $user->nickname ?: '',
            'gender'    => intval($user->gender),
            'birthday'  => $user->birthday ?: '',
            'bio'       => $user->bio ?: '',
            'mobile'    => $user->mobile ?: '',
            'email'     => $user->email ?: '',
        ];

        $this->success('获取成功', $profile);
    }

    /**
     * 会员中心 - 获取用户完整信息
     * GET /api/user/index
     */
    public function index()
    {
        $userId = $this->auth->id;
        
        $user = \app\common\model\User::find($userId);
        if (!$user) {
            $this->error('用户不存在');
        }
        
        // 邀请码（如果没有则生成）
        $inviteCode = $user->invite_code ?: '';
        if (empty($inviteCode)) {
            $prefix = strtoupper(substr(md5($userId), 0, 4));
            $suffix = str_pad($userId, 6, '0', STR_PAD_LEFT);
            $inviteCode = $prefix . $suffix;
            \app\common\model\User::where('id', $userId)->update(['invite_code' => $inviteCode]);
        }
        
        // 金币余额
        $coinService = new CoinService();
        $coinBalance = $coinService->getBalance($userId);
        
        // 兑换比例直接从 site 配置读取（$site.coin_rate）
        $coinRate = intval(config('site.coin_rate') ?: 10000);
        $cashAmount = $coinRate > 0 ? round($coinBalance / $coinRate, 2) : 0;
        
        // 分销等级（按累计佣金）
        $level = 1;
        $levelName = '普通会员';
        $levelConfig = [
            ['min' => 0,      'name' => '普通会员'],
            ['min' => 100,    'name' => '青铜代理'],
            ['min' => 500,    'name' => '白银代理'],
            ['min' => 2000,   'name' => '黄金代理'],
            ['min' => 5000,   'name' => '铂金代理'],
            ['min' => 10000,  'name' => '钻石代理'],
            ['min' => 50000,  'name' => '星耀代理'],
            ['min' => 100000, 'name' => '王者代理'],
        ];
        
        $commissionStat = UserCommissionStat::getOrCreate($userId);
        $totalCommission = floatval($commissionStat->total_commission);
        foreach ($levelConfig as $cfg) {
            if ($totalCommission >= $cfg['min']) {
                $level = array_search($cfg, $levelConfig) + 1;
                $levelName = $cfg['name'];
            }
        }
        
        $userInfo = [
            'id'            => $userId,
            'nickname'      => $user->nickname ?: '用户' . $userId,
            'avatar'        => $user->avatar ? cdnurl($user->avatar) : '',
            'mobile'        => $user->mobile ?: '',
            'level'         => $level,
            'level_name'    => $levelName,
            'invite_code'   => $inviteCode,
            'coin_balance'  => intval($coinBalance),
            'cash_amount'   => floatval($cashAmount),
            'coin_rate'     => $coinRate,
            'score'         => floatval($user->score),
        ];
        
        $this->success('获取成功', ['userInfo' => $userInfo]);
    }

    /**
     * 会员登录
     *
     * @ApiMethod (POST)
     * @ApiParams (name="account", type="string", required=true, description="账号")
     * @ApiParams (name="password", type="string", required=true, description="密码")
     */
    public function login()
    {
        $account = $this->request->post('account');
        $password = $this->request->post('password');
        if (!$account || !$password) {
            $this->error(__('Invalid parameters'));
        }
        $ret = $this->auth->login($account, $password);
        if ($ret) {
            $data = ['userinfo' => $this->auth->getUserinfo()];
            $this->success(__('Logged in successful'), $data);
        } else {
            $this->error($this->auth->getError());
        }
    }

    /**
     * 手机验证码登录
     *
     * @ApiMethod (POST)
     * @ApiParams (name="mobile", type="string", required=true, description="手机号")
     * @ApiParams (name="captcha", type="string", required=true, description="验证码")
     */
    public function mobilelogin()
    {
        $mobile = $this->request->post('mobile');
        $captcha = $this->request->post('captcha');
        if (!$mobile || !$captcha) {
            $this->error(__('Invalid parameters'));
        }
        if (!Validate::regex($mobile, "^1\d{10}$")) {
            $this->error(__('Mobile is incorrect'));
        }
        if (!Sms::check($mobile, $captcha, 'mobilelogin')) {
            $this->error(__('Captcha is incorrect'));
        }
        $user = \app\common\model\User::getByMobile($mobile);
        if ($user) {
            if ($user->status != 'normal') {
                $this->error(__('Account is locked'));
            }
            //如果已经有账号则直接登录
            $ret = $this->auth->direct($user->id);
        } else {
            $ret = $this->auth->register($mobile, Random::alnum(), '', $mobile, []);
        }
        if ($ret) {
            Sms::flush($mobile, 'mobilelogin');
            $data = ['userinfo' => $this->auth->getUserinfo()];
            $this->success(__('Logged in successful'), $data);
        } else {
            $this->error($this->auth->getError());
        }
    }

    /**
     * 注册会员
     *
     * @ApiMethod (POST)
     * @ApiParams (name="username", type="string", required=true, description="用户名")
     * @ApiParams (name="password", type="string", required=true, description="密码")
     * @ApiParams (name="email", type="string", required=true, description="邮箱")
     * @ApiParams (name="mobile", type="string", required=true, description="手机号")
     * @ApiParams (name="code", type="string", required=true, description="验证码")
     */
    public function register()
    {
        $username = $this->request->post('username');
        $password = $this->request->post('password');
        $email = $this->request->post('email');
        $mobile = $this->request->post('mobile');
        $code = $this->request->post('code');
        if (!$username || !$password) {
            $this->error(__('Invalid parameters'));
        }
        if ($email && !Validate::is($email, "email")) {
            $this->error(__('Email is incorrect'));
        }
        if ($mobile && !Validate::regex($mobile, "^1\d{10}$")) {
            $this->error(__('Mobile is incorrect'));
        }
        $ret = Sms::check($mobile, $code, 'register');
        if (!$ret) {
            $this->error(__('Captcha is incorrect'));
        }
        $ret = $this->auth->register($username, $password, $email, $mobile, []);
        if ($ret) {
            $data = ['userinfo' => $this->auth->getUserinfo()];
            $this->success(__('Sign up successful'), $data);
        } else {
            $this->error($this->auth->getError());
        }
    }

    /**
     * 退出登录
     * @ApiMethod (POST)
     */
    public function logout()
    {
        if (!$this->request->isPost()) {
            $this->error(__('Invalid parameters'));
        }
        $this->auth->logout();
        $this->success(__('Logout successful'));
    }

    /**
     * 修改会员个人信息
     * POST /api/user/profile
     *
     * 支持字段：avatar, username, nickname, gender, birthday, bio
     * gender: 0=未知, 1=男, 2=女
     * birthday: YYYY-MM-DD 格式
     */
    public function profile()
    {
        $user = $this->auth->getUser();
        if (!$user) {
            $this->error('用户不存在');
        }

        $username = $this->request->post('username', '');
        $nickname = $this->request->post('nickname', '');
        $bio      = $this->request->post('bio', '');
        $avatar   = $this->request->post('avatar', '', 'trim,strip_tags,htmlspecialchars');
        $gender   = $this->request->post('gender', 0, 'intval');
        $birthday = $this->request->post('birthday', '');

        // 用户名（可选修改，不为空时检查唯一性）
        if ($username !== '') {
            if (mb_strlen($username) < 3 || mb_strlen($username) > 32) {
                $this->error('用户名长度为3-32个字符');
            }
            $exists = \app\common\model\User::where('username', $username)->where('id', '<>', $this->auth->id)->find();
            if ($exists) {
                $this->error(__('Username already exists'));
            }
            $user->username = $username;
        }

        // 昵称（必填）
        if ($nickname !== '') {
            if (mb_strlen($nickname) < 2 || mb_strlen($nickname) > 50) {
                $this->error('昵称长度为2-50个字符');
            }
            $exists = \app\common\model\User::where('nickname', $nickname)->where('id', '<>', $this->auth->id)->find();
            if ($exists) {
                $this->error(__('Nickname already exists'));
            }
            $user->nickname = $nickname;
        } else {
            $this->error('昵称不能为空');
        }

        // 性别：0=未知, 1=男, 2=女
        if (in_array($gender, [0, 1, 2])) {
            $user->gender = $gender;
        }

        // 生日：校验日期格式
        if ($birthday !== '') {
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $birthday)) {
                $this->error('生日格式不正确，应为YYYY-MM-DD');
            }
            $user->birthday = $birthday;
        } else {
            $user->birthday = null;
        }

        $user->bio = mb_substr($bio, 0, 100);
        $user->avatar = $avatar;
        $user->save();

        // 返回更新后的用户信息（用于前端同步vuex）
        $updatedUser = [
            'id'        => $user->id,
            'nickname'  => $user->nickname ?: '用户' . $user->id,
            'avatar'    => $user->avatar ? cdnurl($user->avatar) : '',
            'gender'    => intval($user->gender),
            'birthday'  => $user->birthday ?: '',
            'bio'       => $user->bio ?: '',
        ];

        $this->success('保存成功', $updatedUser);
    }

    /**
     * 修改邮箱
     *
     * @ApiMethod (POST)
     * @ApiParams (name="email", type="string", required=true, description="邮箱")
     * @ApiParams (name="captcha", type="string", required=true, description="验证码")
     */
    public function changeemail()
    {
        $user = $this->auth->getUser();
        $email = $this->request->post('email');
        $captcha = $this->request->post('captcha');
        if (!$email || !$captcha) {
            $this->error(__('Invalid parameters'));
        }
        if (!Validate::is($email, "email")) {
            $this->error(__('Email is incorrect'));
        }
        if (\app\common\model\User::where('email', $email)->where('id', '<>', $user->id)->find()) {
            $this->error(__('Email already exists'));
        }
        $result = Ems::check($email, $captcha, 'changeemail');
        if (!$result) {
            $this->error(__('Captcha is incorrect'));
        }
        $verification = $user->verification;
        $verification->email = 1;
        $user->verification = $verification;
        $user->email = $email;
        $user->save();

        Ems::flush($email, 'changeemail');
        $this->success();
    }

    /**
     * 修改手机号
     *
     * @ApiMethod (POST)
     * @ApiParams (name="mobile", type="string", required=true, description="手机号")
     * @ApiParams (name="captcha", type="string", required=true, description="验证码")
     */
    public function changemobile()
    {
        $user = $this->auth->getUser();
        $mobile = $this->request->post('mobile');
        $captcha = $this->request->post('captcha');
        if (!$mobile || !$captcha) {
            $this->error(__('Invalid parameters'));
        }
        if (!Validate::regex($mobile, "^1\d{10}$")) {
            $this->error(__('Mobile is incorrect'));
        }
        if (\app\common\model\User::where('mobile', $mobile)->where('id', '<>', $user->id)->find()) {
            $this->error(__('Mobile already exists'));
        }
        $result = Sms::check($mobile, $captcha, 'changemobile');
        if (!$result) {
            $this->error(__('Captcha is incorrect'));
        }
        $verification = $user->verification;
        $verification->mobile = 1;
        $user->verification = $verification;
        $user->mobile = $mobile;
        $user->save();

        Sms::flush($mobile, 'changemobile');
        $this->success();
    }

    /**
     * 第三方登录
     *
     * @ApiMethod (POST)
     * @ApiParams (name="platform", type="string", required=true, description="平台名称")
     * @ApiParams (name="code", type="string", required=true, description="Code码")
     */
    public function third()
    {
        $url = url('user/index');
        $platform = $this->request->post("platform");
        $code = $this->request->post("code");
        $config = get_addon_config('third');
        if (!$config || !isset($config[$platform])) {
            $this->error(__('Invalid parameters'));
        }
        $app = new \addons\third\library\Application($config);
        //通过code换access_token和绑定会员
        $result = $app->{$platform}->getUserInfo(['code' => $code]);
        if ($result) {
            $loginret = \addons\third\library\Service::connect($platform, $result);
            if ($loginret) {
                $data = [
                    'userinfo'  => $this->auth->getUserinfo(),
                    'thirdinfo' => $result
                ];
                $this->success(__('Logged in successful'), $data);
            }
        }
        $this->error(__('Operation failed'), $url);
    }

    /**
     * 重置密码
     *
     * @ApiMethod (POST)
     * @ApiParams (name="mobile", type="string", required=true, description="手机号")
     * @ApiParams (name="newpassword", type="string", required=true, description="新密码")
     * @ApiParams (name="captcha", type="string", required=true, description="验证码")
     */
    public function resetpwd()
    {
        $type = $this->request->post("type", "mobile");
        $mobile = $this->request->post("mobile");
        $email = $this->request->post("email");
        $newpassword = $this->request->post("newpassword");
        $captcha = $this->request->post("captcha");
        if (!$newpassword || !$captcha) {
            $this->error(__('Invalid parameters'));
        }
        //验证Token
        if (!Validate::make()->check(['newpassword' => $newpassword], ['newpassword' => 'require|regex:\S{6,30}'])) {
            $this->error(__('Password must be 6 to 30 characters'));
        }
        if ($type == 'mobile') {
            if (!Validate::regex($mobile, "^1\d{10}$")) {
                $this->error(__('Mobile is incorrect'));
            }
            $user = \app\common\model\User::getByMobile($mobile);
            if (!$user) {
                $this->error(__('User not found'));
            }
            $ret = Sms::check($mobile, $captcha, 'resetpwd');
            if (!$ret) {
                $this->error(__('Captcha is incorrect'));
            }
            Sms::flush($mobile, 'resetpwd');
        } else {
            if (!Validate::is($email, "email")) {
                $this->error(__('Email is incorrect'));
            }
            $user = \app\common\model\User::getByEmail($email);
            if (!$user) {
                $this->error(__('User not found'));
            }
            $ret = Ems::check($email, $captcha, 'resetpwd');
            if (!$ret) {
                $this->error(__('Captcha is incorrect'));
            }
            Ems::flush($email, 'resetpwd');
        }
        //模拟一次登录
        $this->auth->direct($user->id);
        $ret = $this->auth->changepwd($newpassword, '', true);
        if ($ret) {
            $this->success(__('Reset password successful'));
        } else {
            $this->error($this->auth->getError());
        }
    }
}
