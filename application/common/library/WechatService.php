<?php

namespace app\common\library;

use app\common\model\User;
use fast\Random;
use think\Cache;
use think\Db;
use think\Exception;
use think\Log;

/**
 * 微信服务类
 * 
 * 提供微信App、小程序、公众号登录及支付功能
 */
class WechatService
{
    // 微信API地址
    const API_ACCESS_TOKEN = 'https://api.weixin.qq.com/sns/oauth2/access_token';
    const API_REFRESH_TOKEN = 'https://api.weixin.qq.com/sns/oauth2/refresh_token';
    const API_USERINFO = 'https://api.weixin.qq.com/sns/userinfo';
    const API_JSCODE2SESSION = 'https://api.weixin.qq.com/sns/jscode2session';
    const API_OAUTH2_AUTHORIZE = 'https://open.weixin.qq.com/connect/oauth2/authorize';
    
    // 错误信息
    protected static $error = '';
    
    /**
     * 获取错误信息
     */
    public static function getError()
    {
        return self::$error;
    }
    
    /**
     * 设置错误信息
     */
    protected static function setError($error)
    {
        self::$error = $error;
        Log::record('WechatService Error: ' . $error, 'error');
        return false;
    }
    
    // ==================== 微信App登录 ====================
    
    /**
     * 微信App授权登录
     * 
     * @param string $code 微信返回的授权code
     * @param array $extend 扩展参数
     * @return array|false 用户信息或false
     */
    public static function appLogin($code, $extend = [])
    {
        // 检查是否开启微信App登录
        if (!SystemConfigService::isWechatAppEnabled()) {
            return self::setError('微信App登录未开启');
        }
        
        // 获取配置
        $config = SystemConfigService::getWechatAppConfig();
        if (empty($config['appid']) || empty($config['secret'])) {
            return self::setError('微信App配置不完整');
        }
        
        // 通过code获取access_token
        $tokenInfo = self::getAppAccessToken($config['appid'], $config['secret'], $code);
        if (!$tokenInfo) {
            return false;
        }
        
        // 获取用户信息
        $userinfo = self::getUserInfo($tokenInfo['access_token'], $tokenInfo['openid']);
        if (!$userinfo) {
            return false;
        }
        
        // 处理登录或注册
        return self::handleLogin($userinfo, 'app', $extend);
    }
    
    /**
     * 获取微信App的access_token
     */
    protected static function getAppAccessToken($appid, $secret, $code)
    {
        $params = [
            'appid' => $appid,
            'secret' => $secret,
            'code' => $code,
            'grant_type' => 'authorization_code',
        ];
        
        $url = self::API_ACCESS_TOKEN . '?' . http_build_query($params);
        $result = self::httpGet($url);
        
        if (isset($result['errcode']) && $result['errcode'] != 0) {
            return self::setError('获取access_token失败: ' . ($result['errmsg'] ?? '未知错误'));
        }
        
        return $result;
    }
    
    // ==================== 微信小程序登录 ====================
    
    /**
     * 微信小程序登录
     * 
     * @param string $jscode 小程序登录code
     * @param array $extend 扩展参数（可包含encryptedData和iv用于获取手机号）
     * @return array|false
     */
    public static function miniLogin($jscode, $extend = [])
    {
        // 检查是否开启微信小程序登录
        if (!SystemConfigService::isWechatMiniEnabled()) {
            return self::setError('微信小程序登录未开启');
        }
        
        // 获取配置
        $config = SystemConfigService::getWechatMiniConfig();
        if (empty($config['appid']) || empty($config['secret'])) {
            return self::setError('微信小程序配置不完整');
        }
        
        // 通过code获取session_key和openid
        $sessionInfo = self::jscode2Session($config['appid'], $config['secret'], $jscode);
        if (!$sessionInfo) {
            return false;
        }
        
        // 小程序用户信息需要前端单独获取，这里只处理openid
        $userinfo = [
            'openid' => $sessionInfo['openid'] ?? '',
            'unionid' => $sessionInfo['unionid'] ?? '',
            'session_key' => $sessionInfo['session_key'] ?? '',
        ];
        
        // 如果传入了用户信息
        if (isset($extend['nickname'])) {
            $userinfo['nickname'] = $extend['nickname'];
        }
        if (isset($extend['avatar'])) {
            $userinfo['headimgurl'] = $extend['avatar'];
        }
        if (isset($extend['gender'])) {
            $userinfo['sex'] = $extend['gender'];
        }
        if (isset($extend['city'])) {
            $userinfo['city'] = $extend['city'];
        }
        if (isset($extend['province'])) {
            $userinfo['province'] = $extend['province'];
        }
        if (isset($extend['country'])) {
            $userinfo['country'] = $extend['country'];
        }
        
        return self::handleLogin($userinfo, 'mini', $extend);
    }
    
    /**
     * 小程序code换取session_key
     */
    protected static function jscode2Session($appid, $secret, $jscode)
    {
        $params = [
            'appid' => $appid,
            'secret' => $secret,
            'js_code' => $jscode,
            'grant_type' => 'authorization_code',
        ];
        
        $url = self::API_JSCODE2SESSION . '?' . http_build_query($params);
        $result = self::httpGet($url);
        
        if (isset($result['errcode']) && $result['errcode'] != 0) {
            return self::setError('获取session_key失败: ' . ($result['errmsg'] ?? '未知错误'));
        }
        
        return $result;
    }
    
    /**
     * 解密小程序加密数据
     * 
     * @param string $sessionKey 会话密钥
     * @param string $encryptedData 加密数据
     * @param string $iv 初始向量
     * @return array|false
     */
    public static function decryptMiniData($sessionKey, $encryptedData, $iv)
    {
        try {
            $aesKey = base64_decode($sessionKey);
            $aesIV = base64_decode($iv);
            $aesCipher = base64_decode($encryptedData);
            
            $decrypted = openssl_decrypt($aesCipher, 'AES-128-CBC', $aesKey, OPENSSL_RAW_DATA, $aesIV);
            $data = json_decode($decrypted, true);
            
            return $data;
        } catch (\Exception $e) {
            return self::setError('解密失败: ' . $e->getMessage());
        }
    }
    
    // ==================== 微信公众号登录 ====================
    
    /**
     * 构建微信公众号网页授权URL
     * 
     * @param string $appid 公众号AppID
     * @param string $redirectUri 授权后重定向的回调链接
     * @param string $scope 应用授权作用域: snsapi_base（静默）/ snsapi_userinfo（弹窗）
     * @param string $state 重定向后会带上state参数
     * @return string
     */
    public static function buildOfficialAuthUrl($appid, $redirectUri, $scope = 'snsapi_userinfo', $state = 'wechat_auth')
    {
        $params = [
            'appid' => $appid,
            'redirect_uri' => $redirectUri,
            'response_type' => 'code',
            'scope' => $scope,
            'state' => $state,
        ];
        
        // http_build_query 已自动对值进行URL编码，无需再手动urlencode
        return self::API_OAUTH2_AUTHORIZE . '?' . http_build_query($params) . '#wechat_redirect';
    }
    
    /**
     * 微信公众号网页授权登录
     * 
     * @param string $code 授权code
     * @param array $extend 扩展参数
     * @return array|false
     */
    public static function officialLogin($code, $extend = [])
    {
        // 检查是否开启微信公众号登录
        if (!SystemConfigService::isWechatOfficialEnabled()) {
            return self::setError('微信公众号登录未开启');
        }
        
        // 获取配置
        $config = SystemConfigService::getWechatOfficialConfig();
        if (empty($config['appid']) || empty($config['secret'])) {
            return self::setError('微信公众号配置不完整');
        }
        
        // 通过code获取access_token
        $tokenInfo = self::getOfficialAccessToken($config['appid'], $config['secret'], $code);
        if (!$tokenInfo) {
            return false;
        }
        
        // 获取用户信息
        $userinfo = self::getUserInfo($tokenInfo['access_token'], $tokenInfo['openid']);
        if (!$userinfo) {
            return false;
        }
        
        return self::handleLogin($userinfo, 'official', $extend);
    }
    
    /**
     * 获取公众号access_token
     */
    protected static function getOfficialAccessToken($appid, $secret, $code)
    {
        $params = [
            'appid' => $appid,
            'secret' => $secret,
            'code' => $code,
            'grant_type' => 'authorization_code',
        ];
        
        $url = self::API_ACCESS_TOKEN . '?' . http_build_query($params);
        $result = self::httpGet($url);
        
        if (isset($result['errcode']) && $result['errcode'] != 0) {
            return self::setError('获取access_token失败: ' . ($result['errmsg'] ?? '未知错误'));
        }
        
        return $result;
    }
    
    // ==================== 通用方法 ====================
    
    /**
     * 获取微信用户信息
     */
    protected static function getUserInfo($accessToken, $openid)
    {
        $params = [
            'access_token' => $accessToken,
            'openid' => $openid,
        ];
        
        $url = self::API_USERINFO . '?' . http_build_query($params);
        $result = self::httpGet($url);
        
        if (isset($result['errcode']) && $result['errcode'] != 0) {
            return self::setError('获取用户信息失败: ' . ($result['errmsg'] ?? '未知错误'));
        }
        
        return $result;
    }
    
    /**
     * 处理登录逻辑
     * 
     * @param array $wechatInfo 微信用户信息
     * @param string $platform 平台: app/mini/official
     * @param array $extend 扩展参数
     * @return array
     */
    protected static function handleLogin($wechatInfo, $platform, $extend = [])
    {
        $openid = $wechatInfo['openid'] ?? '';
        $unionid = $wechatInfo['unionid'] ?? '';
        
        if (empty($openid)) {
            return self::setError('openid不能为空');
        }
        
        // 字段映射
        $openidField = self::getOpenidField($platform);
        
        // 查找用户
        $user = null;
        
        // 优先通过unionid查找
        if (!empty($unionid)) {
            $user = User::where('wechat_unionid', $unionid)->find();
        }
        
        // 通过openid查找
        if (!$user) {
            $user = User::where($openidField, $openid)->find();
        }
        
        if ($user) {
            // 用户已存在，更新信息并登录
            return self::loginExistingUser($user, $wechatInfo, $platform, $openidField);
        } else {
            // 用户不存在
            if (SystemConfigService::isWechatAutoRegister()) {
                // 自动注册
                return self::registerNewUser($wechatInfo, $platform, $openidField, $unionid, $extend);
            } else {
                return self::setError('用户不存在，请先注册');
            }
        }
    }
    
    /**
     * 获取openid字段名
     */
    protected static function getOpenidField($platform)
    {
        $fields = [
            'app' => 'wechat_openid',
            'mini' => 'wechat_mini_openid',
            'official' => 'wechat_official_openid',
        ];
        
        return $fields[$platform] ?? 'wechat_openid';
    }
    
    /**
     * 登录已存在用户
     */
    protected static function loginExistingUser($user, $wechatInfo, $platform, $openidField)
    {
        // 检查用户状态
        if ($user->status != 'normal') {
            return self::setError('账号已被禁用');
        }
        
        // 更新微信信息
        $updateData = [
            $openidField => $wechatInfo['openid'] ?? $user->$openidField,
            'updatetime' => time(),
        ];
        
        // 如果有unionid
        if (!empty($wechatInfo['unionid'])) {
            $updateData['wechat_unionid'] = $wechatInfo['unionid'];
        }
        
        // 更新昵称和头像（如果为空）
        if (empty($user->nickname) && !empty($wechatInfo['nickname'])) {
            $updateData['nickname'] = $wechatInfo['nickname'];
            $updateData['wechat_nickname'] = $wechatInfo['nickname'];
        }
        if (empty($user->avatar) && !empty($wechatInfo['headimgurl'])) {
            $updateData['avatar'] = $wechatInfo['headimgurl'];
            $updateData['wechat_avatar'] = $wechatInfo['headimgurl'];
        }
        
        $user->save($updateData);
        
        // 直接登录
        $auth = Auth::instance();
        $auth->direct($user->id);
        
        return [
            'user_id' => $user->id,
            'token' => $auth->getToken(),
            'userinfo' => $auth->getUserinfo(),
            'is_new' => false,
        ];
    }
    
    /**
     * 注册新用户
     */
    protected static function registerNewUser($wechatInfo, $platform, $openidField, $unionid, $extend = [])
    {
        $auth = Auth::instance();
        
        // 生成唯一用户名
        $username = 'wx_' . Random::alnum(8);
        while (User::getByUsername($username)) {
            $username = 'wx_' . Random::alnum(8);
        }
        
        // 构建注册数据
        $data = [
            $openidField => $wechatInfo['openid'] ?? '',
            'wechat_unionid' => $unionid,
            'wechat_nickname' => $wechatInfo['nickname'] ?? '',
            'wechat_avatar' => $wechatInfo['headimgurl'] ?? '',
            'wechat_gender' => $wechatInfo['sex'] ?? 0,
            'wechat_city' => $wechatInfo['city'] ?? '',
            'wechat_province' => $wechatInfo['province'] ?? '',
            'wechat_country' => $wechatInfo['country'] ?? '',
            'wechat_bindtime' => time(),
            'source' => 'wechat_' . $platform,
        ];
        
        // 合并扩展参数
        $data = array_merge($data, $extend);
        
        // 设置昵称和头像
        if (!empty($wechatInfo['nickname'])) {
            $data['nickname'] = $wechatInfo['nickname'];
        }
        if (!empty($wechatInfo['headimgurl'])) {
            $data['avatar'] = $wechatInfo['headimgurl'];
        }
        
        // 注册
        $password = Random::alnum(16);
        $result = $auth->register($username, $password, '', '', $data);
        
        if (!$result) {
            return self::setError('注册失败: ' . $auth->getError());
        }
        
        return [
            'user_id' => $auth->id,
            'token' => $auth->getToken(),
            'userinfo' => $auth->getUserinfo(),
            'is_new' => true,
        ];
    }
    
    /**
     * 绑定微信账号
     * 
     * @param int $userId 用户ID
     * @param string $code 微信授权code
     * @param string $platform 平台
     * @return bool
     */
    public static function bindWechat($userId, $code, $platform = 'app')
    {
        $user = User::get($userId);
        if (!$user) {
            return self::setError('用户不存在');
        }
        
        // 根据平台获取access_token
        if ($platform == 'app') {
            $config = SystemConfigService::getWechatAppConfig();
            $tokenInfo = self::getAppAccessToken($config['appid'], $config['secret'], $code);
        } elseif ($platform == 'mini') {
            $config = SystemConfigService::getWechatMiniConfig();
            $tokenInfo = self::jscode2Session($config['appid'], $config['secret'], $code);
        } else {
            $config = SystemConfigService::getWechatOfficialConfig();
            $tokenInfo = self::getOfficialAccessToken($config['appid'], $config['secret'], $code);
        }
        
        if (!$tokenInfo) {
            return false;
        }
        
        $openidField = self::getOpenidField($platform);
        $openid = $tokenInfo['openid'];
        
        // 检查是否已被其他用户绑定
        $existUser = User::where($openidField, $openid)->find();
        if ($existUser && $existUser->id != $userId) {
            return self::setError('该微信已被其他账号绑定');
        }
        
        // 更新绑定
        $updateData = [
            $openidField => $openid,
            'wechat_bindtime' => time(),
        ];
        
        if (!empty($tokenInfo['unionid'])) {
            $updateData['wechat_unionid'] = $tokenInfo['unionid'];
        }
        
        $user->save($updateData);
        
        return true;
    }
    
    /**
     * 解绑微信
     */
    public static function unbindWechat($userId, $platform = 'app')
    {
        $user = User::get($userId);
        if (!$user) {
            return self::setError('用户不存在');
        }
        
        $openidField = self::getOpenidField($platform);
        $user->save([
            $openidField => '',
            'wechat_bindtime' => null,
        ]);
        
        return true;
    }
    
    // ==================== HTTP请求方法 ====================
    
    /**
     * GET请求
     */
    protected static function httpGet($url, $timeout = 30)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        
        $response = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            Log::record('WechatService HTTP Error: ' . $error, 'error');
            return null;
        }
        
        return json_decode($response, true);
    }
    
    /**
     * POST请求
     */
    protected static function httpPost($url, $data = [], $timeout = 30)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, is_array($data) ? json_encode($data) : $data);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        
        $response = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            Log::record('WechatService HTTP Error: ' . $error, 'error');
            return null;
        }
        
        return json_decode($response, true);
    }
}
