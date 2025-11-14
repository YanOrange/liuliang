<?php

namespace app\lib\api\sms;
use laytp\library\Http;
use app\lib\api\exception\Exception;
//发送验证码
class Smsode
{
    private $templateId = ''; //模板ID
    private $account = '';//实际账号
    private $hwpassword = '';//实际密码
    private $url = ''; //APP接入地址+接口访问URI

    public function sendCode($params = [])
    {
        try {
            //全局号码格式(不包含国家码),示例:15123456789,多个号码之间用英文逗号分隔
            $mobiles = array($params['mobiles']);
            //单变量模板示例:模板内容为"您的验证码是${param1}"时,$templateParas['param1']='参数值'
            $templateParas['code']=(string)$params['code'];
            //请求Headers
            $headers = [
                'Content-Type: application/json;charset=UTF-8'
            ];

            $requestLists['mobiles']=$mobiles;
            $requestLists['templateId']=$this->templateId;
            $requestLists['templateParas']=$templateParas;
            $requestLists['signature']='【旭翱】';

            //请求Body
            $data['account']=$this->account;
            $data['password']=$this->hwpassword;
            $data['requestLists']=array($requestLists);

            $context_options = [
                'http' => [
                    'method' => 'POST',
                    'header'=> $headers,
                    'content' => json_encode($data, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT),
                    'ignore_errors' => true,
                    'timeout' => 2,
                ],
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false
                ] //为防止因HTTPS证书认证失败造成API调用失败，需要先忽略证书信任问题
            ];
            $response = file_get_contents($this->url, false, stream_context_create($context_options));
            return $response;
        } catch (\Exception $e) {
            new Exception('验证码发送异常');
        }
    }
}