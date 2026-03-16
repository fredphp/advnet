<?php

namespace app\admin\controller\general;

use app\common\controller\Backend;
use app\common\library\SystemConfigService;
use think\Cache;
use think\Db;
use think\Exception;

/**
 * 微信配置管理
 *
 * @icon fa fa-wechat
 * @remark 微信相关配置管理，包括App、小程序、公众号、支付等配置
 */
class WechatConfig extends Backend
{
    /**
     * @var \app\common\model\Config
     */
    protected $model = null;
    protected $noNeedRight = ['index', 'save'];

    public function _initialize()
    {
        parent::_initialize();
    }

    /**
     * 查看配置
     */
    public function index()
    {
        // 获取所有微信配置
        $config = SystemConfigService::getWechatConfig();
        
        // 配置分组
        $groups = [
            'app' => [
                'title' => '微信App配置',
                'icon' => 'fa fa-mobile',
                'fields' => [
                    'wechat_app_enabled' => ['title' => '开启App登录', 'type' => 'switch'],
                    'wechat_app_appid' => ['title' => 'AppID', 'type' => 'text'],
                    'wechat_app_secret' => ['title' => 'AppSecret', 'type' => 'password'],
                ],
            ],
            'mini' => [
                'title' => '微信小程序配置',
                'icon' => 'fa fa-weixin',
                'fields' => [
                    'wechat_mini_enabled' => ['title' => '开启小程序登录', 'type' => 'switch'],
                    'wechat_mini_appid' => ['title' => 'AppID', 'type' => 'text'],
                    'wechat_mini_secret' => ['title' => 'AppSecret', 'type' => 'password'],
                ],
            ],
            'official' => [
                'title' => '微信公众号配置',
                'icon' => 'fa fa-comments',
                'fields' => [
                    'wechat_official_enabled' => ['title' => '开启公众号登录', 'type' => 'switch'],
                    'wechat_official_appid' => ['title' => 'AppID', 'type' => 'text'],
                    'wechat_official_secret' => ['title' => 'AppSecret', 'type' => 'password'],
                ],
            ],
            'pay' => [
                'title' => '微信支付配置',
                'icon' => 'fa fa-money',
                'fields' => [
                    'wechat_pay_enabled' => ['title' => '开启微信支付', 'type' => 'switch'],
                    'wechat_pay_mchid' => ['title' => '商户号', 'type' => 'text'],
                    'wechat_pay_key' => ['title' => 'API密钥', 'type' => 'password'],
                    'wechat_pay_cert_pem' => ['title' => '证书cert', 'type' => 'textarea'],
                    'wechat_pay_key_pem' => ['title' => '证书key', 'type' => 'textarea'],
                    'wechat_pay_notify_url' => ['title' => '回调地址', 'type' => 'text'],
                ],
            ],
            'transfer' => [
                'title' => '企业付款配置',
                'icon' => 'fa fa-exchange',
                'fields' => [
                    'wechat_transfer_enabled' => ['title' => '开启企业付款', 'type' => 'switch'],
                    'wechat_transfer_mchid' => ['title' => '商户号', 'type' => 'text'],
                    'wechat_transfer_key' => ['title' => 'API密钥', 'type' => 'password'],
                    'wechat_transfer_cert_pem' => ['title' => '证书cert', 'type' => 'textarea'],
                    'wechat_transfer_key_pem' => ['title' => '证书key', 'type' => 'textarea'],
                ],
            ],
            'login' => [
                'title' => '登录配置',
                'icon' => 'fa fa-sign-in',
                'fields' => [
                    'wechat_auto_register' => ['title' => '自动注册', 'type' => 'switch', 'tip' => '微信登录时用户不存在是否自动注册'],
                    'wechat_bind_mobile' => ['title' => '强制绑定手机', 'type' => 'switch', 'tip' => '微信登录后是否强制绑定手机号'],
                ],
            ],
        ];
        
        $this->view->assign('config', $config);
        $this->view->assign('groups', $groups);
        
        return $this->view->fetch();
    }

    /**
     * 保存配置
     */
    public function save()
    {
        if ($this->request->isPost()) {
            $data = $this->request->post('row/a', [], null);
            
            if (empty($data)) {
                $this->error(__('Parameter %s can not be empty', ''));
            }
            
            Db::startTrans();
            try {
                foreach ($data as $key => $value) {
                    // 类型转换
                    $type = 'string';
                    if (is_bool($value) || in_array($value, ['0', '1', 0, 1, true, false])) {
                        // 布尔值
                        $type = 'boolean';
                        $value = $value ? '1' : '0';
                    } elseif (is_array($value)) {
                        $type = 'json';
                        $value = json_encode($value, JSON_UNESCAPED_UNICODE);
                    }
                    
                    // 更新或插入
                    $exists = Db::name('system_config')
                        ->where('group', 'wechat')
                        ->where('name', $key)
                        ->find();
                    
                    if ($exists) {
                        Db::name('system_config')
                            ->where('id', $exists['id'])
                            ->update([
                                'value' => $value,
                                'type' => $type,
                                'updatetime' => time(),
                            ]);
                    } else {
                        // 获取配置标题
                        $title = $this->getConfigTitle($key);
                        $tip = $this->getConfigTip($key);
                        
                        Db::name('system_config')->insert([
                            'group' => 'wechat',
                            'name' => $key,
                            'value' => $value,
                            'type' => $type,
                            'title' => $title,
                            'tip' => $tip,
                            'status' => 1,
                            'createtime' => time(),
                            'updatetime' => time(),
                        ]);
                    }
                }
                
                // 清除缓存
                SystemConfigService::clearCache('wechat');
                
                Db::commit();
            } catch (Exception $e) {
                Db::rollback();
                $this->error($e->getMessage());
            }
            
            $this->success();
        }
        
        $this->error(__('Invalid parameters'));
    }

    /**
     * 获取配置标题
     */
    protected function getConfigTitle($key)
    {
        $titles = [
            'wechat_app_enabled' => '微信App登录开关',
            'wechat_app_appid' => '微信App AppID',
            'wechat_app_secret' => '微信App Secret',
            'wechat_mini_enabled' => '微信小程序登录开关',
            'wechat_mini_appid' => '小程序AppID',
            'wechat_mini_secret' => '小程序Secret',
            'wechat_official_enabled' => '微信公众号登录开关',
            'wechat_official_appid' => '公众号AppID',
            'wechat_official_secret' => '公众号Secret',
            'wechat_pay_enabled' => '微信支付开关',
            'wechat_pay_mchid' => '商户号',
            'wechat_pay_key' => '支付密钥',
            'wechat_pay_cert_pem' => '支付证书cert',
            'wechat_pay_key_pem' => '支付证书key',
            'wechat_pay_notify_url' => '支付回调地址',
            'wechat_transfer_enabled' => '企业付款开关',
            'wechat_transfer_mchid' => '企业付款商户号',
            'wechat_transfer_key' => '企业付款密钥',
            'wechat_transfer_cert_pem' => '企业付款证书cert',
            'wechat_transfer_key_pem' => '企业付款证书key',
            'wechat_auto_register' => '自动注册',
            'wechat_bind_mobile' => '强制绑定手机',
        ];
        
        return $titles[$key] ?? $key;
    }

    /**
     * 获取配置说明
     */
    protected function getConfigTip($key)
    {
        $tips = [
            'wechat_app_enabled' => '是否开启微信App登录功能',
            'wechat_app_appid' => '微信开放平台移动应用AppID',
            'wechat_app_secret' => '微信开放平台移动应用Secret',
            'wechat_mini_enabled' => '是否开启微信小程序登录功能',
            'wechat_mini_appid' => '微信小程序AppID',
            'wechat_mini_secret' => '微信小程序Secret',
            'wechat_official_enabled' => '是否开启微信公众号登录功能',
            'wechat_official_appid' => '微信公众号AppID',
            'wechat_official_secret' => '微信公众号Secret',
            'wechat_pay_enabled' => '是否开启微信支付功能',
            'wechat_pay_mchid' => '微信支付商户号',
            'wechat_pay_key' => '微信支付API密钥',
            'wechat_pay_cert_pem' => '微信支付证书cert内容',
            'wechat_pay_key_pem' => '微信支付证书key内容',
            'wechat_pay_notify_url' => '微信支付异步回调地址',
            'wechat_transfer_enabled' => '是否开启微信企业付款功能（用于提现）',
            'wechat_transfer_mchid' => '企业付款商户号（可与支付商户号相同）',
            'wechat_transfer_key' => '企业付款API密钥',
            'wechat_transfer_cert_pem' => '企业付款证书cert内容',
            'wechat_transfer_key_pem' => '企业付款证书key内容',
            'wechat_auto_register' => '微信登录时如果用户不存在是否自动注册',
            'wechat_bind_mobile' => '微信登录后是否强制绑定手机号',
        ];
        
        return $tips[$key] ?? '';
    }

    /**
     * 测试配置
     */
    public function test()
    {
        $type = $this->request->post('type', 'app');
        
        $result = [
            'success' => false,
            'message' => '',
        ];
        
        try {
            switch ($type) {
                case 'app':
                    $config = SystemConfigService::getWechatAppConfig();
                    if (empty($config['appid']) || empty($config['secret'])) {
                        throw new Exception('AppID或Secret未配置');
                    }
                    $result['success'] = true;
                    $result['message'] = '配置正确';
                    break;
                    
                case 'mini':
                    $config = SystemConfigService::getWechatMiniConfig();
                    if (empty($config['appid']) || empty($config['secret'])) {
                        throw new Exception('AppID或Secret未配置');
                    }
                    // 测试获取access_token
                    $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid={$config['appid']}&secret={$config['secret']}";
                    $response = $this->httpGet($url);
                    if (isset($response['errcode'])) {
                        throw new Exception($response['errmsg'] ?? '获取access_token失败');
                    }
                    $result['success'] = true;
                    $result['message'] = '配置正确，access_token获取成功';
                    break;
                    
                case 'official':
                    $config = SystemConfigService::getWechatOfficialConfig();
                    if (empty($config['appid']) || empty($config['secret'])) {
                        throw new Exception('AppID或Secret未配置');
                    }
                    // 测试获取access_token
                    $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid={$config['appid']}&secret={$config['secret']}";
                    $response = $this->httpGet($url);
                    if (isset($response['errcode'])) {
                        throw new Exception($response['errmsg'] ?? '获取access_token失败');
                    }
                    $result['success'] = true;
                    $result['message'] = '配置正确，access_token获取成功';
                    break;
                    
                default:
                    throw new Exception('未知类型');
            }
        } catch (\Exception $e) {
            $result['message'] = $e->getMessage();
        }
        
        $this->success('', null, $result);
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
}
