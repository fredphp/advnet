<?php

namespace app\common\library;

use think\Log;

/**
 * 数据加密服务（纯 PHP 实现，不依赖 openssl/mcrypt）
 *
 * 加密算法：XOR 流密码 + 字符位置偏移 + Base64
 * 前端使用相同的 key 进行解密
 *
 * 加密流程：JSON → XOR加密 → 字符偏移 → Base64
 * 解密流程：Base64 → 字符反偏移 → XOR解密 → JSON
 */
class DataEncryptService
{
    /**
     * ★ 加密密钥（前后端必须完全一致）
     */
    const SECRET_KEY = 'advnet2024@secure#aes256!key#data';

    /**
     * 加密数据
     *
     * @param mixed $data 待加密的数据（数组或对象）
     * @return string 加密后的字符串
     */
    public static function encrypt($data)
    {
        try {
            $json = json_encode($data, JSON_UNESCAPED_UNICODE);
            if ($json === false) {
                Log::warning('DataEncrypt: JSON编码失败');
                return json_encode($data);
            }

            return self::xorEncrypt($json, self::SECRET_KEY);
        } catch (\Throwable $e) {
            Log::error('DataEncrypt encrypt异常: ' . $e->getMessage());
            return json_encode($data);
        }
    }

    /**
     * 解密数据
     *
     * @param string $encrypted 加密字符串
     * @return mixed 解密后的原始数据
     */
    public static function decrypt($encrypted)
    {
        try {
            $json = self::xorDecrypt($encrypted, self::SECRET_KEY);
            if ($json === false) {
                Log::warning('DataEncrypt: 解密失败');
                return null;
            }
            return json_decode($json, true);
        } catch (\Throwable $e) {
            Log::error('DataEncrypt decrypt异常: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * XOR 流加密 + 位置偏移
     *
     * 算法：每个字符与密钥对应位 XOR，再加位置偏移量
     *
     * @param string $data 明文
     * @param string $key 密钥
     * @return string Base64 编码的密文
     */
    private static function xorEncrypt($data, $key)
    {
        $keyLen = strlen($key);
        $result = '';
        $dataLen = strlen($data);

        for ($i = 0; $i < $dataLen; $i++) {
            // XOR with key
            $char = ord($data[$i]) ^ ord($key[$i % $keyLen]);
            // 加位置偏移（增加扩散性）
            $offset = ($i + ord($key[($i * 3) % $keyLen])) % 256;
            $char = ($char + $offset) % 256;
            $result .= chr($char);
        }

        return base64_encode($result);
    }

    /**
     * XOR 流解密 + 位置反偏移
     *
     * @param string $encrypted Base64 编码的密文
     * @param string $key 密钥
     * @return string|false 明文
     */
    private static function xorDecrypt($encrypted, $key)
    {
        $decoded = base64_decode($encrypted, true);
        if ($decoded === false) {
            return false;
        }

        $keyLen = strlen($key);
        $result = '';
        $dataLen = strlen($decoded);

        for ($i = 0; $i < $dataLen; $i++) {
            $char = ord($decoded[$i]);
            // 反位置偏移
            $offset = ($i + ord($key[($i * 3) % $keyLen])) % 256;
            $char = ($char - $offset + 256) % 256;
            // XOR with key
            $char = $char ^ ord($key[$i % $keyLen]);
            $result .= chr($char);
        }

        return $result;
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
