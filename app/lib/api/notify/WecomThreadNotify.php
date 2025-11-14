<?php
namespace app\lib\api\notify;


use think\Exception;
use WorkWeixin\callback\WorkWeixinCallback;

class WecomThreadNotify {

    private $encodingAesKey;
    private $corpId;
    private $token;

    public function __construct($config)
    {
        $this->corpId           = $config['corpid'] ?? '';
        $this->token            = $config['token'] ?? '';
        $this->encodingAesKey   = $config['encodingAesKey'] ?? '';

    }

    public function verify($msgSignature, $timestamp, $nonce, $echostr)
    {
        // 1. 排序参数
        $array = array($this->token, $timestamp, $nonce, $echostr);
        sort($array, SORT_STRING);
        $str = implode($array);

        // 2. 计算SHA1签名
        $sha1Str = sha1($str);

        // 3. 验证签名是否匹配
        if ($sha1Str !== $msgSignature) {
//            throw new Exception('签名验证失败');
        }
    }

    public function decryptEchoStr($echoStr, $encodingAESKey) {
        $key = base64_decode($encodingAESKey . "=");
        $iv = substr($key, 0, 16); // AES初始向量为前16字节

        // 解密（兼容PKCS7填充）
        $decrypted = openssl_decrypt(
            base64_decode($echoStr),
            'AES-256-CBC',
            $key,
            OPENSSL_RAW_DATA,
            $iv
        );

        // 移除填充和XML格式头尾
        $result = $this->stripPKCS7Padding($decrypted);
        $result = substr($result, 16); // 去掉随机前缀的16字节
        $result = substr($result, 0, -strlen($this->corpId)); // 去掉企业ID后缀

        return $result;
    }

    /**
     * 移除 PKCS7 填充
     * @param string $data 解密后的原始数据（含填充）
     * @return string 移除填充后的明文
     */
    private function stripPKCS7Padding($data) {
        if (empty($data)) {
            return '';
        }

        // 获取最后一个字符的ASCII值（即填充长度）
        $pad = ord(substr($data, -1));

        // 验证填充长度是否合法（1到块大小之间）
        $blockSize = 32; // AES-256-CBC的块大小为16字节，此处企业微信可能使用32？需确认实际块大小
        if ($pad < 1 || $pad > $blockSize) {
            return $data; // 无效填充，直接返回原始数据（可能未填充）
        }

        // 检查末尾的填充字符是否一致
        if (substr_count(substr($data, -$pad), chr($pad)) !== $pad) {
            return $data; // 填充不合法，返回原始数据
        }

        // 移除填充
        return substr($data, 0, -$pad);
    }

    function decryptEncryptData($encryptData) {
        // 1. 解码AES Key
        $key = base64_decode($this->encodingAesKey . "="); // 补全=号并解码

        // 2. 提取初始向量（IV为前16字节）
        $iv = substr($key, 0, 16);

        // 3. 解密数据（AES-256-CBC模式）
        $decrypted = openssl_decrypt(
            base64_decode($encryptData), // 先对Encrypt数据做base64解码
            'AES-256-CBC',
            $key,
            OPENSSL_RAW_DATA, // 原始数据模式
            $iv
        );

        // 4. 处理PKCS7填充
        $pad = ord(substr($decrypted, -1)); // 获取填充长度
        $decrypted = substr($decrypted, 0, -$pad); // 移除填充

        // 5. 去除16字节随机前缀
        $content = substr($decrypted, 16);

        // 6. 校验并去除企业ID后缀
        $corpIdLength = strlen($this->corpId);
        $contentCorpId = substr($content, -$corpIdLength);
//        if ($contentCorpId !== $this->corpId) {
//            throw new Exception("企业ID校验失败，实际后缀: {$contentCorpId}");
//        }

        return substr($content, 0, -$corpIdLength); // 明文XML
    }
}