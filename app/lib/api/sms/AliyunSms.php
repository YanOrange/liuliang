<?php

namespace app\lib\api\sms;

use Darabonba\OpenApi\Models\Config;
use AlibabaCloud\SDK\Dysmsapi\V20170525\Dysmsapi;
use AlibabaCloud\SDK\Dysmsapi\V20170525\Models\SendSmsRequest;
use Exception;
use AlibabaCloud\Tea\Exception\TeaError;
use AlibabaCloud\Tea\Utils\Utils\RuntimeOptions; // 修正导入路径

class AliyunSms
{
    protected $signName = '';
    protected $templateCode = '';
    protected $accessKeyId = '';
    protected $accessKeySecret = '';

    /**
     * 构造函数：初始化配置
     * @param array $config 阿里云短信配置
     */
    public function __construct(array $config)
    {
        $this->accessKeyId = $config['accessKeyId'] ?? '';
        $this->accessKeySecret = $config['accessKeySecret'] ?? '';
        $this->signName = $config['signName'] ?? '';
        $this->templateCode = $config['templateCode'] ?? '';

        // 验证配置必填项
        if (empty($this->accessKeyId) || empty($this->accessKeySecret) || empty($this->signName) || empty($this->templateCode)) {
            throw new Exception('阿里云短信配置不完整');
        }
    }

    /**
     * 使用AK&SK初始化账号Client（改为实例方法，或保持static并传参）
     * @return Dysmsapi Client
     */
    public function createClient()
    {
        $config = new Config([
            "accessKeyId" => $this->accessKeyId,
            "accessKeySecret" => $this->accessKeySecret
        ]);
        $config->endpoint = "dysmsapi.aliyuncs.com";
        return new Dysmsapi($config);
    }

    /**
     * 发送验证码
     * @param array $args 参数（phone, code, channel）
     * @return object 阿里云接口返回结果
     * @throws Exception
     */
    public function sendCode($args)
    {
        // 验证必填参数
        if (empty($args['phone']) || empty($args['code'])) {
            throw new Exception('手机号或验证码不能为空');
        }

        try {
            // 实例化Client（改为$this->调用实例方法）
            $client = $this->createClient();
            
            $templateParam = json_encode(['code' => $args['code']]);
            $sendSmsRequest = new SendSmsRequest([
                'phoneNumbers' => $args['phone'],
                'signName' => (isset($args['channel']) && $args['channel'] == 'yqzw_huawei') ? '狂花' : $this->signName,
                'templateCode' => $this->templateCode,
                'templateParam' => $templateParam,
            ]);

            $runtime = new RuntimeOptions([]);
            $response = $client->sendSmsWithOptions($sendSmsRequest, $runtime);
            
            // 阿里云接口返回的结果需转为可访问的格式（如toArray或toMap）
            $result = $response->toMap();
            
            // 主动判断阿里云返回的错误码（如Code不是OK）
            if ($result['body']['Code'] !== 'OK') {
                throw new Exception('阿里云接口返回错误：' . ($result['body']['Message'] ?? '未知错误'));
            }

            return $result; // 返回处理后的结果

        } catch (TeaError $error) {
            // 阿里云SDK的TeaError异常
            throw new Exception('短信发送失败：' . $error->getMessage());
        } catch (Exception $error) {
            // 其他异常
            throw new Exception('短信发送异常：' . $error->getMessage());
        }
    }
}