<?php

namespace app\lib\api\vres;
use app\lib\api\exception\ExceptionStd;

/**
 * AES加解密
 */
class Aes
{    
    /**
     * aes加密
	 * @param string $string 需要加密的字符串
	 * @param string $key 密钥
	 * @return string
 	 */
    public static function aesEncrypt($data, $key = null)
	{
	    //openssl_encrypt 加密不同Mcrypt，对秘钥长度要求，超出16加密结果不变
	    $data = openssl_encrypt($data, 'AES-128-ECB', $key, OPENSSL_RAW_DATA);
	    if (!$data) {
	    	new ExceptionStd('系统参数配置错误');
	    }
		return base64_encode($data);

	}
	/**
     * aes解密
	 * @param string $string 需要解密的字符串
	 * @param string $key 密钥
	 * @return string
	 */
	public static function aesDecrypt($string, $key = null)
	{
	    $string = base64_decode($string);
	    $decrypted = openssl_decrypt($string, 'AES-128-ECB', $key, OPENSSL_RAW_DATA);
	    if (!$decrypted) {
	    	new ExceptionStd('系统参数配置错误');
	    }
	    return $decrypted;
	}

}
