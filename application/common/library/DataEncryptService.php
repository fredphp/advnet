<?php

namespace app\common\library;

use think\Log;

/**
 * 数据加密服务
 *
 * 使用 AES-256-CBC 对 API 响应的 data 字段进行加密
 * 前端使用相同的 key + iv 进行解密
 *
 * 加密流程：JSON → AES-256-CBC → Base64
 * 解密流程：Base64 → AES-256-CBC → JSON
 */
class DataEncryptService
{
    /**
     * 加密算法
     */
    const CIPHER = 'AES-256-CBC';

    /**
     * 加密数据
     *
     * @param mixed $data 待加密的数据（数组或对象）
     * @return string Base64 编码的密文
     */
    public static function encrypt($data)
    {
        try {
            $key = self::getKey();
            $iv = self::getIv();

            $json = json_encode($data, JSON_UNESCAPED_UNICODE);
            if ($json === false) {
                Log::warning('DataEncrypt: JSON编码失败');
                return json_encode($data);
            }

            $encrypted = openssl_encrypt($json, self::CIPHER, $key, OPENSSL_RAW_DATA, $iv);
            if ($encrypted === false) {
                Log::warning('DataEncrypt: AES加密失败 - ' . openssl_error_string());
                return $json;
            }

            return base64_encode($encrypted);
        } catch (\Throwable $e) {
            Log::error('DataEncrypt encrypt异常: ' . $e->getMessage());
            return json_encode($data);
        }
    }

    /**
     * 解密数据
     *
     * @param string $encrypted Base64 编码的密文
     * @return mixed 解密后的原始数据
     */
    public static function decrypt($encrypted)
    {
        try {
            $key = self::getKey();
            $iv = self::getIv();

            $decoded = base64_decode($encrypted, true);
            if ($decoded === false) {
                Log::warning('DataEncrypt: Base64解码失败');
                return null;
            }

            $decrypted = openssl_decrypt($decoded, self::CIPHER, $key, OPENSSL_RAW_DATA, $iv);
            if ($decrypted === false) {
                Log::warning('DataEncrypt: AES解密失败 - ' . openssl_error_string());
                return null;
            }

            return json_decode($decrypted, true);
        } catch (\Throwable $e) {
            Log::error('DataEncrypt decrypt异常: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * 获取加密密钥（32字节，从系统配置或固定值）
     * 密钥必须与前端保持一致
     */
    private static function getKey()
    {
        // 优先从系统配置读取，配置键名：ad.encrypt_key
        $configKey = SystemConfigService::get('ad.encrypt_key', null, '');
        if (!empty($configKey)) {
            return self::padKey($configKey);
        }

        // 默认密钥（32字节）
        return 'advnet2024secure@aes256key!#data';
    }

    /**
     * 获取初始化向量（16字节，从系统配置或固定值）
     * IV 必须与前端保持一致
     */
    private static function getIv()
    {
        // 优先从系统配置读取，配置键名：ad.encrypt_iv
        $configIv = SystemConfigService::get('ad.encrypt_iv', null, '');
        if (!empty($configIv)) {
            return self::padIv($configIv);
        }

        // 默认IV（16字节）
        return 'advnet@iv16byte';
    }

    /**
     * 将密钥填充/截断到32字节
     */
    private static function padKey($key)
    {
        if (strlen($key) >= 32) {
            return substr($key, 0, 32);
        }
        return str_pad($key, 32, "\0");
    }

    /**
     * 将IV填充/截断到16字节
     */
    private static function padIv($iv)
    {
        if (strlen($iv) >= 16) {
            return substr($iv, 0, 16);
        }
        return str_pad($iv, 16, "\0");
    }

    /**
     * 是否启用数据加密
     * 通过系统配置 ad.data_encrypt 控制开关
     *
     * @return bool
     */
    public static function isEnabled()
    {
        return (bool)SystemConfigService::get('ad.data_encrypt', null, 1);
    }
}
