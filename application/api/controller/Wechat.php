<?php

namespace app\api\controller;

use app\common\controller\Api;
use app\common\library\WechatService;
use app\common\library\SystemConfigService;
use think\Config;

/**
 * 微信授权登录接口
 */
class Wechat extends Api
{
    protected $noNeedLogin = ['appLogin', 'miniLogin', 'officialLogin', 'bindWechat', 'getMiniPhone'];
    protected $noNeedRight = '*';

    public function _initialize()
    {
        parent::_initialize();
    }

    /**
     * 微信App授权登录
     *
     * @ApiMethod (POST)
     * @ApiParams (name="code", type="string", required=true, description="微信授权code")
     * @ApiParams (name="invite_code", type="string", required=false, description="邀请码")
     * @ApiParams (name="device_id", type="string", required=false, description="设备ID")
     * @ApiReturnParams (name="code", type="integer", description="状态码")
     * @ApiReturnParams (name="msg", type="string", description="提示信息")
     * @ApiReturnParams (name="data", type="object", description="返回数据")
     * @ApiReturnParams (name="data.user_id", type="integer", description="用户ID")
     * @ApiReturnParams (name="data.token", type="string", description="登录Token")
     * @ApiReturnParams (name="data.userinfo", type="object", description="用户信息")
     * @ApiReturnParams (name="data.is_new", type="boolean", description="是否新用户")
     */
    public function appLogin()
    {
        $code = $this->request->post('code');
        $inviteCode = $this->request->post('invite_code');
        $deviceId = $this->request->post('device_id');
        
        if (!$code) {
            $this->error(__('Invalid parameters'), 'code参数不能为空');
        }
        
        // 检查是否开启微信App登录
        if (!SystemConfigService::isWechatAppEnabled()) {
            $this->error('微信App登录未开启');
        }
        
        // 扩展参数
        $extend = [];
        if ($inviteCode) {
            $extend['invite_code'] = $inviteCode;
        }
        if ($deviceId) {
            $extend['device_id'] = $deviceId;
        }
        if ($this->request->ip()) {
            $extend['register_ip'] = $this->request->ip();
        }
        
        // 调用微信登录
        $result = WechatService::appLogin($code, $extend);
        
        if (!$result) {
            $this->error(WechatService::getError());
        }
        
        // 如果是新用户且有邀请码，处理邀请关系
        if ($result['is_new'] && $inviteCode) {
            $this->handleInvite($result['user_id'], $inviteCode);
        }
        
        $this->success(__('Logged in successful'), $result);
    }

    /**
     * 微信小程序登录
     *
     * @ApiMethod (POST)
     * @ApiParams (name="code", type="string", required=true, description="小程序登录code")
     * @ApiParams (name="nickname", type="string", required=false, description="用户昵称")
     * @ApiParams (name="avatar", type="string", required=false, description="用户头像")
     * @ApiParams (name="gender", type="integer", required=false, description="性别:0未知,1男,2女")
     * @ApiParams (name="invite_code", type="string", required=false, description="邀请码")
     * @ApiParams (name="device_id", type="string", required=false, description="设备ID")
     * @ApiReturnParams (name="code", type="integer", description="状态码")
     * @ApiReturnParams (name="msg", type="string", description="提示信息")
     * @ApiReturnParams (name="data", type="object", description="返回数据")
     */
    public function miniLogin()
    {
        $code = $this->request->post('code');
        $nickname = $this->request->post('nickname');
        $avatar = $this->request->post('avatar');
        $gender = $this->request->post('gender/d');
        $inviteCode = $this->request->post('invite_code');
        $deviceId = $this->request->post('device_id');
        
        if (!$code) {
            $this->error(__('Invalid parameters'), 'code参数不能为空');
        }
        
        // 检查是否开启微信小程序登录
        if (!SystemConfigService::isWechatMiniEnabled()) {
            $this->error('微信小程序登录未开启');
        }
        
        // 扩展参数
        $extend = [
            'nickname' => $nickname,
            'avatar' => $avatar,
            'gender' => $gender,
        ];
        
        if ($inviteCode) {
            $extend['invite_code'] = $inviteCode;
        }
        if ($deviceId) {
            $extend['device_id'] = $deviceId;
        }
        if ($this->request->ip()) {
            $extend['register_ip'] = $this->request->ip();
        }
        
        // 调用微信小程序登录
        $result = WechatService::miniLogin($code, $extend);
        
        if (!$result) {
            $this->error(WechatService::getError());
        }
        
        // 如果是新用户且有邀请码，处理邀请关系
        if ($result['is_new'] && $inviteCode) {
            $this->handleInvite($result['user_id'], $inviteCode);
        }
        
        $this->success(__('Logged in successful'), $result);
    }

    /**
     * 微信公众号授权登录
     *
     * @ApiMethod (POST)
     * @ApiParams (name="code", type="string", required=true, description="微信授权code")
     * @ApiParams (name="invite_code", type="string", required=false, description="邀请码")
     * @ApiReturnParams (name="code", type="integer", description="状态码")
     * @ApiReturnParams (name="msg", type="string", description="提示信息")
     * @ApiReturnParams (name="data", type="object", description="返回数据")
     */
    public function officialLogin()
    {
        $code = $this->request->post('code');
        $inviteCode = $this->request->post('invite_code');
        
        if (!$code) {
            $this->error(__('Invalid parameters'), 'code参数不能为空');
        }
        
        // 检查是否开启微信公众号登录
        if (!SystemConfigService::isWechatOfficialEnabled()) {
            $this->error('微信公众号登录未开启');
        }
        
        // 扩展参数
        $extend = [];
        if ($inviteCode) {
            $extend['invite_code'] = $inviteCode;
        }
        if ($this->request->ip()) {
            $extend['register_ip'] = $this->request->ip();
        }
        
        // 调用微信公众号登录
        $result = WechatService::officialLogin($code, $extend);
        
        if (!$result) {
            $this->error(WechatService::getError());
        }
        
        // 如果是新用户且有邀请码，处理邀请关系
        if ($result['is_new'] && $inviteCode) {
            $this->handleInvite($result['user_id'], $inviteCode);
        }
        
        $this->success(__('Logged in successful'), $result);
    }

    /**
     * 获取小程序手机号
     *
     * @ApiMethod (POST)
     * @ApiParams (name="code", type="string", required=true, description="小程序获取手机号的code")
     * @ApiReturnParams (name="code", type="integer", description="状态码")
     * @ApiReturnParams (name="msg", type="string", description="提示信息")
     * @ApiReturnParams (name="data", type="object", description="返回数据")
     */
    public function getMiniPhone()
    {
        // 此接口需要用户已登录
        // $this->checkLogin();
        
        $code = $this->request->post('code');
        
        if (!$code) {
            $this->error(__('Invalid parameters'), 'code参数不能为空');
        }
        
        // 检查是否开启微信小程序登录
        if (!SystemConfigService::isWechatMiniEnabled()) {
            $this->error('微信小程序登录未开启');
        }
        
        // 获取配置
        $config = SystemConfigService::getWechatMiniConfig();
        
        // 获取access_token（需要使用小程序的access_token）
        $accessToken = $this->getMiniAccessToken($config['appid'], $config['secret']);
        if (!$accessToken) {
            $this->error('获取access_token失败');
        }
        
        // 获取手机号
        $url = "https://api.weixin.qq.com/wxa/business/getuserphonenumber?access_token={$accessToken}";
        $data = ['code' => $code];
        
        $result = $this->httpPost($url, json_encode($data));
        
        if (isset($result['errcode']) && $result['errcode'] == 0) {
            $phoneInfo = $result['phone_info'] ?? [];
            $this->success('获取成功', [
                'phoneNumber' => $phoneInfo['phoneNumber'] ?? '',
                'purePhoneNumber' => $phoneInfo['purePhoneNumber'] ?? '',
                'countryCode' => $phoneInfo['countryCode'] ?? '',
            ]);
        } else {
            $this->error($result['errmsg'] ?? '获取手机号失败');
        }
    }

    /**
     * 绑定微信
     *
     * @ApiMethod (POST)
     * @ApiParams (name="code", type="string", required=true, description="微信授权code")
     * @ApiParams (name="platform", type="string", required=true, description="平台:app/mini/official")
     */
    public function bindWechat()
    {
        $code = $this->request->post('code');
        $platform = $this->request->post('platform', 'app');
        
        if (!$code) {
            $this->error(__('Invalid parameters'), 'code参数不能为空');
        }
        
        $result = WechatService::bindWechat($this->auth->id, $code, $platform);
        
        if (!$result) {
            $this->error(WechatService::getError());
        }
        
        $this->success('绑定成功');
    }

    /**
     * 解绑微信
     *
     * @ApiMethod (POST)
     * @ApiParams (name="platform", type="string", required=true, description="平台:app/mini/official")
     */
    public function unbindWechat()
    {
        $platform = $this->request->post('platform', 'app');
        
        $result = WechatService::unbindWechat($this->auth->id, $platform);
        
        if (!$result) {
            $this->error(WechatService::getError());
        }
        
        $this->success('解绑成功');
    }

    /**
     * 获取微信登录配置状态
     *
     * @ApiMethod (GET)
     */
    public function loginStatus()
    {
        $data = [
            'app_enabled' => SystemConfigService::isWechatAppEnabled(),
            'mini_enabled' => SystemConfigService::isWechatMiniEnabled(),
            'official_enabled' => SystemConfigService::isWechatOfficialEnabled(),
            'auto_register' => SystemConfigService::isWechatAutoRegister(),
            'bind_mobile' => SystemConfigService::isWechatBindMobile(),
        ];
        
        $this->success('', $data);
    }

    /**
     * 处理邀请关系
     */
    protected function handleInvite($userId, $inviteCode)
    {
        try {
            // 查找邀请人
            $inviter = \app\common\model\User::where('invite_code', $inviteCode)->find();
            if (!$inviter || $inviter->id == $userId) {
                return false;
            }
            
            // 更新用户的上下级关系
            \app\common\model\User::where('id', $userId)->update([
                'parent_id' => $inviter->id,
                'grandparent_id' => $inviter->parent_id ?: 0,
            ]);
            
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * 获取小程序access_token
     */
    protected function getMiniAccessToken($appid, $secret)
    {
        $cacheKey = 'wechat_mini_access_token_' . $appid;
        $token = \think\Cache::get($cacheKey);
        
        if ($token) {
            return $token;
        }
        
        $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid={$appid}&secret={$secret}";
        $result = $this->httpGet($url);
        
        if (isset($result['access_token'])) {
            // 缓存7000秒（微信token有效期7200秒）
            \think\Cache::set($cacheKey, $result['access_token'], 7000);
            return $result['access_token'];
        }
        
        return null;
    }

    /**
     * HTTP GET请求
     */
    protected function httpGet($url)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        $response = curl_exec($ch);
        curl_close($ch);
        
        return json_decode($response, true);
    }

    /**
     * HTTP POST请求
     */
    protected function httpPost($url, $data)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        
        $response = curl_exec($ch);
        curl_close($ch);
        
        return json_decode($response, true);
    }
}
