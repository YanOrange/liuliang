<?php

namespace app\lib\service\common;

/**
 * curl请求
 */
class CurlService {

    # post请求
    public static function post($url, $body)
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($body));//设置请求体1
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($curl, CURLOPT_HEADER, false);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'POST');//使用一个自定义的请求信息来代替"GET"或"HEAD"作为HTTP请求。(这个加不加没啥影响)
        curl_setopt($curl, CURLOPT_TIMEOUT, 3);
        $data = curl_exec($curl);

        return ($data === false) ? false : $data;
    }

    # get请求
    public static function get($url, $data)
    {
        $url .= ( stripos($url, '?') !== false ? '&' : '?' ) . http_build_query($data);

        return file_get_contents($url);
    }

    # post请求
    public static function postJson($url, $body)
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($body));//设置请求体1
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($curl, CURLOPT_HEADER, false);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'POST');//使用一个自定义的请求信息来代替"GET"或"HEAD"作为HTTP请求。(这个加不加没啥影响)
        curl_setopt($curl, CURLOPT_TIMEOUT, 3);
        $data = curl_exec($curl);

        return ($data === false) ? false : $data;
    }

    /**
     * curl - get
     */
    public static function getRequest($url, $query = [], $options = [])
    {
        $options['query'] = $query;

        return self::request('get', $url, $options);
    }


    /**
     * curl - post
     * @param string $url HTTP请求URL地址
     * @param array $data POST请求数据
     * @param array $header 请求头
     * @param array $options CURL参数
     * @return bool|string
     */
    public static function postRequest($url, $data = [], $header = [], $options = [])
    {
        $options['header']  = $header;
        $options['data']    = $data;

        return self::request('post', $url, $options);
    }

    /**
     * CURL 网络请求
     */
    private static function request($method, $url, $options = [], $timeout = 60)
    {
        $ch = curl_init();  # 初始化

        # 请求类型
        if (strtolower($method) === 'get') {

            $url .= ( stripos($url, '?') !== false ? '&' : '?' ) . http_build_query($options['query']);

        }elseif (strtolower($method) === 'post') {

            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $options['data']);
        }

        # header头信息
        if (isset($options['header']) && !empty($options['header'])) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $options['header']);
        }

        # 证书文件设置
        if (isset($options['ssl_cer']) && !empty($options['ssl_cer']) && file_exists($options['ssl_cer'])) {
            curl_setopt($ch, CURLOPT_SSLCERTTYPE, 'PEM');
            curl_setopt($ch, CURLOPT_SSLCERT, $options['ssl_cer']);
        }
        if (isset($options['ssl_key']) && !empty($options['ssl_key']) && file_exists($options['ssl_key'])) {
            curl_setopt($ch, CURLOPT_SSLKEYTYPE, 'PEM');
            curl_setopt($ch, CURLOPT_SSLKEY, $options['ssl_key']);
        }

        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        list($content, $status) = [curl_exec($ch), curl_getinfo($ch), curl_close($ch)];

        return (intval($status["http_code"]) === 200) ? $content : false;
    }
}