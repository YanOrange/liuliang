<?php
namespace plugin\ali_oss\service;

use AlibabaCloud\Client\AlibabaCloud;
use AlibabaCloud\Client\Exception\ClientException;
use AlibabaCloud\Client\Exception\ServerException;
use laytp\traits\Error;
use OSS\OssClient;

class Oss
{
    use Error;

    protected static $instance;

    /**
     * 单例
     * @return static
     */
    public static function instance()
    {
        if (is_null(self::$instance)) {
            self::$instance = new static();
        }

        return self::$instance;
    }

    /**
     * 服务端上传文件
     * @param $localFileName string 已经上传到服务器的文件名
     * @param $saveFileName string  要保存到OSS的文件名
     * @param $config array  阿里云OSS配置
     * @return bool
     * @throws Exception
     */
    public function upload($localFileName, $saveFileName, $config){
        try{
            $accessKey = $config['accessKeyID'];
            if (!$accessKey) {
                $this->setError('上传失败，阿里云OSS的accessKey为空，请到.env文件进行配置');
                return false;
            }
            $secretKey = $config['accessKeySecret'];
            if (!$secretKey) {
                $this->setError('上传失败，阿里云OSS的secretKey为空，请到.env文件进行配置');
                return false;
            }
            $endpoint = $config['endpoint'];
            if (!$endpoint) {
                $this->setError('上传失败，阿里云OSS的endpoint为空，请到.env文件进行配置');
                return false;
            }
            $bucket = $config['bucket'];
            if (!$bucket) {
                $this->setError('上传失败，阿里云OSS的bucket为空，请到.env文件进行配置');
                return false;
            }
            $domain = $config['domain'];
            if (!$domain) {
                $this->setError('上传失败，阿里云OSS的domain为空，请到.env文件进行配置');
                return false;
            }
            $ossClient = new OssClient($accessKey, $secretKey, $endpoint);
            $ossClient->uploadFile($bucket, $saveFileName, $localFileName);
            return $domain . '/' . $saveFileName;
        }catch (\Exception $e){
            $this->setError('上传失败，'.$e->getMessage());
            return false;
        }
    }

    /**
     * 获取一个临时凭证用于让客户端进行上传操作
     * @param $config
     * @return bool
     */
    public function sts($config){
        $accessKey = $config['accessKeyID'];
        if (!$accessKey) {
            $this->setError('上传失败，阿里云STS的accessKey为空，请到.env文件进行配置');
            return false;
        }
        $secretKey = $config['accessKeySecret'];
        if (!$secretKey) {
            $this->setError('上传失败，阿里云STS的secretKey为空，请到.env文件进行配置');
            return false;
        }
        $ARN = $config['ARN'];
        if (!$ARN) {
            $this->setError('上传失败，阿里云STS的ARN为空，请到.env文件进行配置');
            return false;
        }
        $endpoint = $config['endpoint'];
        if (!$endpoint) {
            $this->setError('上传失败，阿里云STS的endpoint为空，请到.env文件进行配置');
            return false;
        }
        $domain = $config['domain'];
        if (!$domain) {
            $this->setError('上传失败，阿里云STS的domain为空，请到.env文件进行配置');
            return false;
        }
        $bucket = $config['bucket'];
        if (!$bucket) {
            $this->setError('上传失败，阿里云STS的bucket为空，请到.env文件进行配置');
            return false;
        }

        try {
            AlibabaCloud::accessKeyClient($accessKey, $secretKey)
                ->regionId('cn-hangzhou')
                ->asDefaultClient();
            $result = AlibabaCloud::rpc()
                ->product('Sts')
                ->scheme('https') // https | http
                ->version('2015-04-01')
                ->action('AssumeRole')
                ->method('POST')
                ->host('sts.aliyuncs.com')
                ->options([
                    'query' => [
                        'RegionId' => $endpoint,
                        'RoleArn' => $ARN,
                        'RoleSessionName' => "upload",
                    ],
                ])
                ->request();
            $resultArr = $result->toArray();
            $resultArr['Credentials']['endpoint'] = $endpoint;
            $resultArr['Credentials']['domain'] = $domain;
            $resultArr['Credentials']['bucket'] = $bucket;
            return $resultArr['Credentials'];
        } catch (ClientException $e) {
            $this->setError('ClientException:'.$e->getMessage());
            return false;
        } catch (ServerException $e) {
            $this->setError('ServerException:'.$e->getMessage());
            return false;
        }
    }
}