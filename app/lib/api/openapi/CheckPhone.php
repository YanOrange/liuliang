<?php

namespace app\lib\api\openapi;

use app\lib\api\exception\Exception;

class CheckPhone
{
    private $host = "https://slymempt.market.alicloudapi.com/mobile_empty";
    private $appCode = "475642fe0dc3426aaf54ed2db7a0b43c";

    public function h5FlowCheckPhone($mobile,$isNeedCaptcha = 0)
    {
        if(empty($mobile)){
            new Exception('请输入手机号');
        }
        if (!$isNeedCaptcha && (env('flow.ip') != Request()->ip())) {
            if (!preg_match("/^1[3456789]\d{9}$/", $mobile)) {
                new Exception('手机号错误');
            }
            $res = json_decode($this->phoneHttp($mobile), true);//手机号状态：0 空号；1 实号；2 停机；3 库无；4 沉默号；5 风险号
            if (isset($res['code']) && $res['code'] == 200 && isset($res['data']['status']) && !in_array($res['data']['status'],[1,5])) {
                new Exception('请输入正确的手机号');
            }
          //  var_dump($res);
            /*if ( $res['code'] == 0 && $res['data']['result'] == 2) {
                new Exception('请确认手机号有效');
            }*/
        }
    }

    public function phoneHttp($mobile)
    {
        $headers = [
            "Authorization:APPCODE " . $this->appCode
        ];
        $querys = "mobile=".$mobile;
        $url = $this->host . "?" . $querys;

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'GET');
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl, CURLOPT_FAILONERROR, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_TIMEOUT,1);
        if (1 == strpos("$".$this->host, "https://"))
        {
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        }
        $data = curl_exec($curl);
        if($data === false){
            echo 'Curl error: ' . curl_error($curl);
        }else{
            return $data;
        }
    }
}