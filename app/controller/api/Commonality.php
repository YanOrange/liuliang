<?php

namespace app\controller\api;

use app\service\ConfServiceFacade;
use laytp\library\Random;
use laytp\library\UploadDomain;
use plugin\ali_oss\service\Oss;
use laytp\controller\Api;
use think\facade\Env;
use think\facade\Filesystem;
use OSS\OssClient;

class Commonality extends Api
{
    public $noNeedLogin = [''];
    public $noNeedCheckSign = ['uploadFile','uploadBase64File'];
    //上传接口
    public function uploadFile()
    {
        try {
            $file = $this->request->file('uploadFile'); // 获取上传的文件
            if (!$file) {
                return $this->error('上传失败，请选择需要上传的文件');
            }
            $fileExt = strtolower($file->getOriginalExtension());
            $uploadDomain = new UploadDomain();
            if (!$uploadDomain->check($file->getOriginalName(), $file->getSize(), $fileExt, $file->getMime())) {
                return $this->error($uploadDomain->getError());
            }
            $saveName = 'uploads/'. date("Ymd") . "/" . md5(uniqid(mt_rand())) . ".{$fileExt}";
            /**
             * 不能以斜杆开头
             *  - 因为OSS存储时，不允许以/开头
             */
            $uploadDir = $this->request->param('dir');
            $object = $uploadDir ? $uploadDir . '/' . $saveName : $saveName;//设置了上传目录的上传文件名
            $ossConf = [
                'accessKeyID' => env('alioss.accesskeyid'),
                'accessKeySecret' => env('alioss.accesskeysecret'),
                'bucket' => env('alioss.bucket'),
                'endpoint' => env('alioss.endpoint'),
                'domain' => env('alioss.domain'),
            ];
            $oss = Oss::instance();
            $ossUploadRes = $oss->upload($file->getPathname(), $object, $ossConf);
            if ($ossUploadRes) {
                $inputValue = $ossUploadRes;
            } else {
                return $this->error($oss->getError());
            }
            return $this->success('上传成功', [
                'url' => $inputValue,
            ]);
        } catch (\Exception $e) {
            return $this->exceptionError($e);
        }
    }

    //base64上传接口
    public function uploadBase64File()
    {
        try{
            $base64_image_content = $this->request->param('uploadFile'); // 获取上传的文件
            //匹配出图片的格式
            if (preg_match('/^(data:\s*image\/(\w+);base64,)/', $base64_image_content,$base64_imgs)){
                $base64_image = explode(',',$base64_image_content);
                $fileExt = $base64_imgs[2];
                $saveName = 'uploads/'. date("Ymd") . "/" . md5(uniqid(mt_rand())) . ".{$fileExt}";
                $uploadDir = $this->request->param('dir');
                $object = $uploadDir ? $uploadDir . '/' . $saveName : $saveName;//设置了上传目录的上传文件名
                //配置OSS基本配置
                $ossConf = [
                    'accessKeyID' => env('alioss.accesskeyid'),
                    'accessKeySecret' => env('alioss.accesskeysecret'),
                    'bucket' => env('alioss.bucket'),
                    'endpoint' => env('alioss.endpoint'),
                    'domain' => env('alioss.domain'),
                ];
                $ossClient = new OssClient($ossConf['accessKeyID'], $ossConf['accessKeySecret'], $ossConf['endpoint']);
                #执行阿里云上传
                $ossUploadRes = $ossClient->putObject($ossConf['bucket'], $object,base64_decode($base64_image[1]));
                if ($ossUploadRes) {
                    $inputValue = isset($ossUploadRes['info']['url']) ? str_replace('http://weimiaoedu.oss-cn-beijing.aliyuncs.com', $ossConf['domain'], $ossUploadRes['info']['url']) : '';
                } else {
                    return $this->error('上传失败');
                }
                return $this->success('上传成功', [
                    'url' => $inputValue,
                ]);
            }else{
                return $this->error('图片错误');
            }
        } catch (\Exception $e) {
            return $this->exceptionError($e);
        }
    }

}
