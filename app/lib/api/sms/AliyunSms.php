<?php

namespace app\lib\api\sms;

use Darabonba\OpenApi\Models\Config;
use AlibabaCloud\SDK\Dysmsapi\V20170525\Dysmsapi;
use AlibabaCloud\SDK\Dysmsapi\V20170525\Models\SendSmsRequest;
use \Exception;
use AlibabaCloud\Tea\Exception\TeaError;
use AlibabaCloud\Tea\Utils\Utils;
use AlibabaCloud\Tea\Utils\Utils\RuntimeOptions;

class AliyunSms
{
    protected $signName = '';
    protected $templateCode = '';
    protected $accessKeyId = '';
    protected $accessKeySecret = '';

    /**
     * 使用AK&SK初始化账号Client
     * @param string $accessKeyId
     * @param string $accessKeySecret
     * @return Dysmsapi Client
     */
    public function createClient($accessKeyId, $accessKeySecret)
    {
        $config = new Config([
            // 您的 AccessKey ID
            "accessKeyId" => $accessKeyId,
            // 您的 AccessKey Secret
            "accessKeySecret" => $accessKeySecret
        ]);
        // 访问的域名
        $config->endpoint = "dysmsapi.aliyuncs.com";
        return new Dysmsapi($config);
    }

    /**
     * @param string[] $args
     * @return void
     */
    public function sendCode($args)
    {
        try {
            $client = self::createClient($this->accessKeyId, $this->accessKeySecret);
            $templateParam = json_encode(['code' => $args['code']]);
            $sendSmsRequest = new SendSmsRequest([
                'phoneNumbers' => $args['phone'],
                'signName' => isset($args['channel']) && $args['channel'] == 'yqzw_huawei' ? '狂花' : $this->signName,
                'templateCode' => $this->templateCode,
                'templateParam' => $templateParam,
            ]);
            $runtime = new RuntimeOptions([]);
            // 复制代码运行请自行打印 API 的返回值
            $response = $client->sendSmsWithOptions($sendSmsRequest, $runtime);
            $response->toMap();
            return $response;
        }catch (Exception $error) {
            if (!($error instanceof TeaError)) {
                $error = new TeaError([], $error->getMessage(), $error->getCode(), $error);
            }
            new Exception('验证码发送异常');
        }
    }
}