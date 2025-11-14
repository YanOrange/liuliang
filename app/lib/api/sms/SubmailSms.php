<?php

namespace app\lib\api\sms;

use Darabonba\OpenApi\Models\Config;
use AlibabaCloud\SDK\Dysmsapi\V20170525\Dysmsapi;
use AlibabaCloud\SDK\Dysmsapi\V20170525\Models\SendSmsRequest;
use \Exception;
use AlibabaCloud\Tea\Exception\TeaError;
use AlibabaCloud\Tea\Utils\Utils;
use AlibabaCloud\Tea\Utils\Utils\RuntimeOptions;

class SubmailSms
{

    public function sendMessage($params = [])
    {
        try {
            $header = [
                'Content-Type:application/json',
            ];
            $body = [
                'appid'=>$params['appid'],
                'content'=>$params['content'],
                'multi'=>$params['multi'],
                'signature'=>$params['signature'],
                'project'=>$params['project'],
            ];
            $response = self::curlPost($params['sendurl'],$header,http_build_query($body));
            return $response;
        } catch (\Exception $e) {
            new \app\lib\api\exception\Exception($e->getMessage());
        }
    }

    public function curlPost($url,$headers,$body)
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $body);//设置请求体
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($curl, CURLOPT_HEADER, false);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'POST');//使用一个自定义的请求信息来代替"GET"或"HEAD"作为HTTP请求。(这个加不加没啥影响)
        curl_setopt($curl, CURLOPT_TIMEOUT,1);
        $data = curl_exec($curl);
        if($data === false){
            return false;
        }else{
            return $data;
        }

    }
}