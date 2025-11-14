<?php

namespace app\lib\api\vres;
use app\lib\api\exception\ExceptionStd;
/**
 * RSA加解密
 */
class Rsa
{
    /**
     * 生成 sha256WithRSA 签名
     * @param string $content 需要加密的字符串
     * @param string $privateKey 私钥
     * @return string
     */
    public static function getRsaSign($content, $privateKey = '') {
        //$privateKey = chunk_split($privateKey, 64, "\n");
        if (strstr($privateKey, 'BEGIN PRIVATE KEY') === false) {
            $privateKey = "-----BEGIN PRIVATE KEY-----\n" . $privateKey . "\n-----END PRIVATE KEY-----\n";
        }
        $key = openssl_get_privatekey($privateKey);
        if (!$key) {
            new ExceptionStd('系统参数配置错误');
        }
        openssl_sign($content, $signature, $key, "SHA256");
        openssl_free_key($key);
        $sign = base64_encode($signature);
        return $sign;
    }

    /**
     * 验证sha256WithRSA签名
     * @param string $content 验证的字符串
     * @param string $sign 签名
     * @param string $publicKey 公钥
     * @return string
     */
    public static function rsaVerify($content, $sign, $publicKey = ''){
        //$publicKey = chunk_split($publicKey, 64, "\n");
        if (strstr($publicKey, 'BEGIN PUBLIC KEY') === false) {
            $publicKey = "-----BEGIN PUBLIC KEY-----\n" . $publicKey . "-----END PUBLIC KEY-----\n";
        }
        $key = openssl_get_publickey($publicKey);
        if (!$key) {
            new ExceptionStd('系统参数配置错误');
        }
        $ok = openssl_verify($content, base64_decode($sign), $key, 'SHA256');
        openssl_free_key($key);
        return $ok;
    }

    /**
     * 数据私钥加密
     * @param string $orignData 加密的字符串
     * @param string $privateKey 公钥
     * @return string
     */
    public static function rsaEncrypt($orignData, $privateKey = '')
    {
        if (strstr($privateKey, 'BEGIN PRIVATE KEY') === false) {
            $privateKey = "-----BEGIN PRIVATE KEY-----\n" . $privateKey . "\n-----END PRIVATE KEY-----\n";
        }
        $key = openssl_get_privatekey($privateKey);
        if (!$key) {
            new ExceptionStd('系统参数配置错误');
        }
        $encryptData = '';
        //用私钥加密
        openssl_private_encrypt($orignData, $encryptData, $privateKey);
        $orignData = base64_encode($encryptData);
        return $orignData;
    }

    /**
     * 公钥解密
     * @param string $encryptData 加密的字符串
     * @param string $publicKey 公钥
     * @return string
     */
    public static function rsaDecrypt($encryptData, $key = '', $keyType = 'public')
    {
        if ($keyType == 'public') {
            $res = "-----BEGIN PUBLIC KEY-----\n" .
                wordwrap($key, 64, "\n", true) .
                "\n-----END PUBLIC KEY-----";
            $rsaKey = openssl_pkey_get_public($res);
        } else {
            $res = "-----BEGIN PRIVATE KEY-----\n" .
                wordwrap($key, 64, "\n", true) .
                "\n-----END PRIVATE KEY-----";
            $rsaKey = openssl_pkey_get_private($res);
        }
        $crypto = '';
        foreach (str_split(hex2bin($encryptData), 128) as $chunk) {
            if ($keyType == 'public') {
                openssl_public_decrypt($chunk, $decryptData, $rsaKey);
                $crypto .= $decryptData;
            } else {
                openssl_private_decrypt($chunk, $decryptData, $rsaKey);
                $crypto .= $decryptData;
            }

        }
        return json_decode($crypto, true) ? json_decode($crypto, true) : json_decode(base64_decode($crypto), true);

    }

}
