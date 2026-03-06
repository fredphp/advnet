<?php

namespace app\common\library;

use think\Db;
use think\Exception;
use think\Cache;
use think\Log;
use app\common\model\DeviceFingerprint;
use app\common\model\RiskBlacklist;

/**
 * 设备指纹服务
 * 
 * 功能：
 * 1. 设备指纹生成与验证
 * 2. 设备风险评估
 * 3. 多账户检测
 * 4. 模拟器/Root/Hook检测
 */
class DeviceFingerprintService
{
    // 缓存前缀
    const CACHE_PREFIX = 'device:';
    
    // 设备类型
    const DEVICE_TYPE_IOS = 'ios';
    const DEVICE_TYPE_ANDROID = 'android';
    const DEVICE_TYPE_WEB = 'web';
    const DEVICE_TYPE_OTHER = 'other';
    
    /**
     * @var string 设备ID
     */
    protected $deviceId;
    
    /**
     * @var array 设备信息
     */
    protected $deviceInfo = [];
    
    /**
     * 注册设备指纹
     * 
     * @param int $userId 用户ID
     * @param array $deviceData 设备数据
     * @return array
     */
    public function register($userId, array $deviceData)
    {
        $result = [
            'device_id' => '',
            'risk_score' => 0,
            'risk_level' => 'safe',
            'warnings' => [],
        ];
        
        try {
            // 生成设备指纹
            $this->deviceInfo = $deviceData;
            $this->deviceId = $this->generateDeviceId($deviceData);
            
            // 计算设备特征哈希
            $deviceHash = $this->generateDeviceHash($deviceData);
            
            // 检测设备环境风险
            $riskDetection = $this->detectDeviceRisk($deviceData);
            
            // 查找或创建设备记录
            $device = DeviceFingerprint::where('device_id', $this->deviceId)->find();
            
            if ($device) {
                // 更新设备信息
                $device->user_id = $userId;
                $device->last_login_time = time();
                $device->last_login_ip = request()->ip();
                $device->login_count = $device->login_count + 1;
                $device->root_detected = $riskDetection['root_detected'] ? 1 : 0;
                $device->emulator_detected = $riskDetection['emulator_detected'] ? 1 : 0;
                $device->hook_detected = $riskDetection['hook_detected'] ? 1 : 0;
                $device->proxy_detected = $riskDetection['proxy_detected'] ? 1 : 0;
                $device->vpn_detected = $riskDetection['vpn_detected'] ? 1 : 0;
                $device->risk_score = $riskDetection['risk_score'];
                $device->risk_level = $riskDetection['risk_level'];
                
                // 更新关联账户
                $accountIds = json_decode($device->account_ids ?: '[]', true);
                if (!in_array($userId, $accountIds)) {
                    $accountIds[] = $userId;
                    $device->account_ids = json_encode($accountIds);
                    $device->account_count = count($accountIds);
                }
                
                $device->save();
            } else {
                // 创建新设备记录
                $device = new DeviceFingerprint();
                $device->device_id = $this->deviceId;
                $device->device_hash = $deviceHash;
                $device->user_id = $userId;
                $device->device_type = $this->detectDeviceType($deviceData);
                $device->device_brand = $deviceData['brand'] ?? '';
                $device->device_model = $deviceData['model'] ?? '';
                $device->os_version = $deviceData['os_version'] ?? '';
                $device->app_version = $deviceData['app_version'] ?? '';
                $device->screen_resolution = $deviceData['screen_resolution'] ?? '';
                $device->network_type = $deviceData['network_type'] ?? '';
                $device->carrier = $deviceData['carrier'] ?? '';
                $device->root_detected = $riskDetection['root_detected'] ? 1 : 0;
                $device->emulator_detected = $riskDetection['emulator_detected'] ? 1 : 0;
                $device->hook_detected = $riskDetection['hook_detected'] ? 1 : 0;
                $device->proxy_detected = $riskDetection['proxy_detected'] ? 1 : 0;
                $device->vpn_detected = $riskDetection['vpn_detected'] ? 1 : 0;
                $device->risk_score = $riskDetection['risk_score'];
                $device->risk_level = $riskDetection['risk_level'];
                $device->account_count = 1;
                $device->account_ids = json_encode([$userId]);
                $device->login_count = 1;
                $device->last_login_time = time();
                $device->last_login_ip = request()->ip();
                $device->save();
            }
            
            // 更新IP关联账户
            $this->updateIpDeviceRelation(request()->ip(), $userId);
            
            $result['device_id'] = $this->deviceId;
            $result['risk_score'] = $riskDetection['risk_score'];
            $result['risk_level'] = $riskDetection['risk_level'];
            $result['warnings'] = $riskDetection['warnings'];
            
        } catch (Exception $e) {
            Log::error('DeviceFingerprint register error: ' . $e->getMessage());
        }
        
        return $result;
    }
    
    /**
     * 生成设备ID
     * 
     * @param array $deviceData
     * @return string
     */
    protected function generateDeviceId(array $deviceData)
    {
        // 如果客户端已提供设备ID，优先使用
        if (!empty($deviceData['device_id'])) {
            return $deviceData['device_id'];
        }
        
        // 基于多个特征生成唯一ID
        $features = [
            $deviceData['brand'] ?? '',
            $deviceData['model'] ?? '',
            $deviceData['os_version'] ?? '',
            $deviceData['screen_resolution'] ?? '',
            $deviceData['device_name'] ?? '',
            $deviceData['mac_address'] ?? '',  // iOS: identifierForVendor, Android: Android ID
        ];
        
        $featureString = implode('|', $features);
        return 'DEV_' . hash('sha256', $featureString);
    }
    
    /**
     * 生成设备特征哈希
     * 
     * @param array $deviceData
     * @return string
     */
    protected function generateDeviceHash(array $deviceData)
    {
        // 用于检测同一设备使用不同账号
        $features = [
            $deviceData['brand'] ?? '',
            $deviceData['model'] ?? '',
            $deviceData['os_version'] ?? '',
            $deviceData['screen_resolution'] ?? '',
            $deviceData['cpu_info'] ?? '',
            $deviceData['memory_info'] ?? '',
            $deviceData['disk_info'] ?? '',
        ];
        
        return hash('md5', implode('|', $features));
    }
    
    /**
     * 检测设备类型
     * 
     * @param array $deviceData
     * @return string
     */
    protected function detectDeviceType(array $deviceData)
    {
        $platform = strtolower($deviceData['platform'] ?? '');
        
        if (strpos($platform, 'ios') !== false || strpos($platform, 'iphone') !== false || strpos($platform, 'ipad') !== false) {
            return self::DEVICE_TYPE_IOS;
        }
        
        if (strpos($platform, 'android') !== false) {
            return self::DEVICE_TYPE_ANDROID;
        }
        
        if (strpos($platform, 'web') !== false || strpos($platform, 'browser') !== false) {
            return self::DEVICE_TYPE_WEB;
        }
        
        return self::DEVICE_TYPE_OTHER;
    }
    
    /**
     * 检测设备风险
     * 
     * @param array $deviceData
     * @return array
     */
    protected function detectDeviceRisk(array $deviceData)
    {
        $riskScore = 0;
        $warnings = [];
        
        // 1. Root/越狱检测
        $rootDetected = false;
        if (!empty($deviceData['is_rooted']) || !empty($deviceData['is_jailbroken'])) {
            $rootDetected = true;
            $riskScore += 30;
            $warnings[] = '检测到Root/越狱设备';
        }
        
        // 2. 模拟器检测
        $emulatorDetected = $this->detectEmulator($deviceData);
        if ($emulatorDetected) {
            $riskScore += 50;
            $warnings[] = '检测到模拟器环境';
        }
        
        // 3. Hook框架检测
        $hookDetected = $this->detectHook($deviceData);
        if ($hookDetected) {
            $riskScore += 40;
            $warnings[] = '检测到Hook框架';
        }
        
        // 4. 代理检测
        $proxyDetected = !empty($deviceData['is_proxy']);
        if ($proxyDetected) {
            $riskScore += 20;
            $warnings[] = '检测到代理网络';
        }
        
        // 5. VPN检测
        $vpnDetected = !empty($deviceData['is_vpn']);
        if ($vpnDetected) {
            $riskScore += 15;
            $warnings[] = '检测到VPN连接';
        }
        
        // 6. 异常时间检测
        if (!empty($deviceData['time_offset'])) {
            $offset = abs($deviceData['time_offset']);
            if ($offset > 300) { // 时间偏差超过5分钟
                $riskScore += 10;
                $warnings[] = '设备时间异常';
            }
        }
        
        // 7. 调试模式检测
        if (!empty($deviceData['is_debuggable'])) {
            $riskScore += 15;
            $warnings[] = '应用处于调试模式';
        }
        
        // 8. 多开应用检测
        if (!empty($deviceData['is_virtual_app'])) {
            $riskScore += 35;
            $warnings[] = '检测到应用多开环境';
        }
        
        // 9. 设备信息异常检测
        if ($this->isDeviceInfoAnomalous($deviceData)) {
            $riskScore += 20;
            $warnings[] = '设备信息异常';
        }
        
        // 确定风险等级
        $riskLevel = 'safe';
        if ($riskScore >= 80) {
            $riskLevel = 'blacklist';
        } elseif ($riskScore >= 50) {
            $riskLevel = 'dangerous';
        } elseif ($riskScore >= 30) {
            $riskLevel = 'suspicious';
        }
        
        return [
            'root_detected' => $rootDetected,
            'emulator_detected' => $emulatorDetected,
            'hook_detected' => $hookDetected,
            'proxy_detected' => $proxyDetected,
            'vpn_detected' => $vpnDetected,
            'risk_score' => $riskScore,
            'risk_level' => $riskLevel,
            'warnings' => $warnings,
        ];
    }
    
    /**
     * 检测模拟器
     * 
     * @param array $deviceData
     * @return bool
     */
    protected function detectEmulator(array $deviceData)
    {
        // 客户端直接报告
        if (!empty($deviceData['is_emulator'])) {
            return true;
        }
        
        // Android模拟器特征检测
        $emulatorIndicators = [
            'generic', 'vbox', 'bluestacks', 'nox', 'andy', 'genymotion',
            'sdk', 'emulator', 'simulator', 'x86', 'goldfish'
        ];
        
        $model = strtolower($deviceData['model'] ?? '');
        $brand = strtolower($deviceData['brand'] ?? '');
        $device = strtolower($deviceData['device'] ?? '');
        $product = strtolower($deviceData['product'] ?? '');
        $manufacturer = strtolower($deviceData['manufacturer'] ?? '');
        
        foreach ($emulatorIndicators as $indicator) {
            if (strpos($model, $indicator) !== false ||
                strpos($brand, $indicator) !== false ||
                strpos($device, $indicator) !== false ||
                strpos($product, $indicator) !== false ||
                strpos($manufacturer, $indicator) !== false) {
                return true;
            }
        }
        
        // iOS模拟器检测
        if (!empty($deviceData['platform']) && strpos(strtolower($deviceData['platform']), 'simulator') !== false) {
            return true;
        }
        
        // 检查设备特征异常
        if (!empty($deviceData['cpu_abi'])) {
            $cpuAbi = strtolower($deviceData['cpu_abi']);
            // 模拟器通常使用x86架构
            if (strpos($cpuAbi, 'x86') !== false && $deviceData['device_type'] !== 'web') {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * 检测Hook框架
     * 
     * @param array $deviceData
     * @return bool
     */
    protected function detectHook(array $deviceData)
    {
        // 客户端直接报告
        if (!empty($deviceData['has_hook'])) {
            return true;
        }
        
        // 检测常见Hook框架
        $hookPackages = [
            'de.robv.android.xposed.installer',
            'io.va.exposed',
            'org.lsposed.manager',
            'com.topjohnwu.magisk',
            'com.saurik.substrate',
            'com.android.vending.billing.InAppBillingService.COIN',
        ];
        
        $installedPackages = $deviceData['installed_packages'] ?? [];
        foreach ($hookPackages as $package) {
            if (in_array($package, $installedPackages)) {
                return true;
            }
        }
        
        // 检测Hook痕迹
        $hookTraces = $deviceData['hook_traces'] ?? [];
        if (!empty($hookTraces)) {
            return true;
        }
        
        return false;
    }
    
    /**
     * 检测设备信息异常
     * 
     * @param array $deviceData
     * @return bool
     */
    protected function isDeviceInfoAnomalous(array $deviceData)
    {
        // 检测设备信息是否过于简单或明显伪造
        $model = $deviceData['model'] ?? '';
        $brand = $deviceData['brand'] ?? '';
        
        // 设备型号为空或过于简单
        if (empty($model) || strlen($model) < 3) {
            return true;
        }
        
        // 常见伪造特征
        $fakeIndicators = ['test', 'fake', 'unknown', 'null', 'none'];
        foreach ($fakeIndicators as $indicator) {
            if (strpos(strtolower($model), $indicator) !== false ||
                strpos(strtolower($brand), $indicator) !== false) {
                return true;
            }
        }
        
        // 屏幕分辨率异常
        $resolution = $deviceData['screen_resolution'] ?? '';
        if (!empty($resolution)) {
            $parts = explode('x', $resolution);
            if (count($parts) == 2) {
                $width = intval($parts[0]);
                $height = intval($parts[1]);
                // 分辨率为0或异常小
                if ($width <= 0 || $height <= 0 || $width < 200 || $height < 200) {
                    return true;
                }
            }
        }
        
        return false;
    }
    
    /**
     * 更新IP设备关联
     * 
     * @param string $ip
     * @param int $userId
     */
    protected function updateIpDeviceRelation($ip, $userId)
    {
        try {
            $ipRisk = \app\common\model\IpRisk::where('ip', $ip)->find();
            
            if ($ipRisk) {
                $accountIds = json_decode($ipRisk->account_ids ?: '[]', true);
                if (!in_array($userId, $accountIds)) {
                    $accountIds[] = $userId;
                    $ipRisk->account_ids = json_encode($accountIds);
                    $ipRisk->account_count = count($accountIds);
                    $ipRisk->last_request_time = time();
                    $ipRisk->save();
                }
            } else {
                // 创建IP记录
                $ipInfo = $this->getIpInfo($ip);
                $ipRisk = new \app\common\model\IpRisk();
                $ipRisk->ip = $ip;
                $ipRisk->ip_type = filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) ? 'ipv6' : 'ipv4';
                $ipRisk->account_count = 1;
                $ipRisk->account_ids = json_encode([$userId]);
                $ipRisk->country = $ipInfo['country'] ?? '';
                $ipRisk->province = $ipInfo['province'] ?? '';
                $ipRisk->city = $ipInfo['city'] ?? '';
                $ipRisk->isp = $ipInfo['isp'] ?? '';
                $ipRisk->last_request_time = time();
                $ipRisk->save();
            }
        } catch (\Exception $e) {
            // 忽略错误
        }
    }
    
    /**
     * 获取IP信息
     * 
     * @param string $ip
     * @return array
     */
    protected function getIpInfo($ip)
    {
        // 使用离线IP库或API获取IP信息
        // 这里返回空数组，实际应集成IP库
        return [];
    }
    
    /**
     * 检查设备多账户
     * 
     * @param string $deviceId
     * @return array
     */
    public function checkMultiAccount($deviceId)
    {
        $device = DeviceFingerprint::where('device_id', $deviceId)->find();
        
        if (!$device) {
            return [
                'is_multi_account' => false,
                'account_count' => 0,
                'account_ids' => [],
            ];
        }
        
        $accountIds = json_decode($device->account_ids ?: '[]', true);
        
        return [
            'is_multi_account' => count($accountIds) > 1,
            'account_count' => count($accountIds),
            'account_ids' => $accountIds,
        ];
    }
    
    /**
     * 检查设备是否被封禁
     * 
     * @param string $deviceId
     * @return bool
     */
    public function isBanned($deviceId)
    {
        // 检查黑名单
        $blacklist = RiskBlacklist::where('type', 'device')
            ->where('value', $deviceId)
            ->where('enabled', 1)
            ->where(function ($query) {
                $query->whereNull('expire_time')
                    ->whereOr('expire_time', '>', time());
            })
            ->find();
        
        if ($blacklist) {
            return true;
        }
        
        // 检查设备风险等级
        $device = DeviceFingerprint::where('device_id', $deviceId)->find();
        if ($device && $device['risk_level'] == 'blacklist') {
            return true;
        }
        
        return false;
    }
    
    /**
     * 封禁设备
     * 
     * @param string $deviceId
     * @param string $reason
     * @param int $duration 持续时间(秒)，0表示永久
     */
    public function banDevice($deviceId, $reason, $duration = 0)
    {
        $blacklist = new RiskBlacklist();
        $blacklist->type = 'device';
        $blacklist->value = $deviceId;
        $blacklist->reason = $reason;
        $blacklist->source = 'auto';
        $blacklist->expire_time = $duration > 0 ? time() + $duration : null;
        $blacklist->save();
        
        // 更新设备风险等级
        DeviceFingerprint::where('device_id', $deviceId)->update([
            'risk_level' => 'blacklist',
            'ban_expire_time' => $duration > 0 ? time() + $duration : null,
        ]);
    }
    
    /**
     * 获取设备风险信息
     * 
     * @param string $deviceId
     * @return array
     */
    public function getDeviceRiskInfo($deviceId)
    {
        $device = DeviceFingerprint::where('device_id', $deviceId)->find();
        
        if (!$device) {
            return [
                'exists' => false,
                'risk_score' => 0,
                'risk_level' => 'safe',
            ];
        }
        
        return [
            'exists' => true,
            'device_id' => $device['device_id'],
            'device_type' => $device['device_type'],
            'device_brand' => $device['device_brand'],
            'device_model' => $device['device_model'],
            'risk_score' => $device['risk_score'],
            'risk_level' => $device['risk_level'],
            'root_detected' => $device['root_detected'],
            'emulator_detected' => $device['emulator_detected'],
            'hook_detected' => $device['hook_detected'],
            'proxy_detected' => $device['proxy_detected'],
            'vpn_detected' => $device['vpn_detected'],
            'account_count' => $device['account_count'],
            'login_count' => $device['login_count'],
        ];
    }
    
    /**
     * 生成设备挑战码
     * 用于验证客户端是否真实
     * 
     * @param string $deviceId
     * @return array
     */
    public function generateChallenge($deviceId)
    {
        $challenge = bin2hex(random_bytes(32));
        $timestamp = time();
        $expireTime = $timestamp + 60; // 60秒有效
        
        // 缓存挑战码
        $cacheKey = self::CACHE_PREFIX . 'challenge:' . $deviceId;
        Cache::set($cacheKey, [
            'challenge' => $challenge,
            'timestamp' => $timestamp,
        ], 60);
        
        return [
            'challenge' => $challenge,
            'timestamp' => $timestamp,
            'expire_time' => $expireTime,
        ];
    }
    
    /**
     * 验证挑战码响应
     * 
     * @param string $deviceId
     * @param string $response 客户端响应
     * @return bool
     */
    public function verifyChallenge($deviceId, $response)
    {
        $cacheKey = self::CACHE_PREFIX . 'challenge:' . $deviceId;
        $challengeData = Cache::get($cacheKey);
        
        if (!$challengeData) {
            return false;
        }
        
        // 验证响应
        // 客户端应该用特定算法处理挑战码并返回
        // 这里简化为直接比对
        $expectedResponse = hash('sha256', $challengeData['challenge'] . $deviceId);
        
        // 清除挑战码
        Cache::rm($cacheKey);
        
        return $response === $expectedResponse;
    }
    
    /**
     * 获取设备统计
     * 
     * @param string $deviceId
     * @return array
     */
    public function getDeviceStats($deviceId)
    {
        $device = DeviceFingerprint::where('device_id', $deviceId)->find();
        
        if (!$device) {
            return null;
        }
        
        // 获取今日行为统计
        $today = date('Y-m-d');
        $behaviorStats = Db::name('user_behavior_stat')
            ->whereIn('user_id', json_decode($device['account_ids'] ?: '[]', true))
            ->where('stat_date', $today)
            ->select();
        
        $totalVideoWatch = 0;
        $totalTaskComplete = 0;
        $totalWithdraw = 0;
        $totalViolation = 0;
        
        foreach ($behaviorStats as $stat) {
            $totalVideoWatch += $stat['video_watch_count'];
            $totalTaskComplete += $stat['task_complete_count'];
            $totalWithdraw += $stat['withdraw_amount'];
            $totalViolation += $stat['violation_count'];
        }
        
        return [
            'device_id' => $deviceId,
            'account_count' => $device['account_count'],
            'login_count' => $device['login_count'],
            'today_video_watch' => $totalVideoWatch,
            'today_task_complete' => $totalTaskComplete,
            'today_withdraw' => $totalWithdraw,
            'today_violation' => $totalViolation,
            'risk_score' => $device['risk_score'],
            'risk_level' => $device['risk_level'],
        ];
    }
}
