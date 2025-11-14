<?php

namespace plugin\ueditor\controller;

use app\service\ConfServiceFacade;
use laytp\library\UploadDomain;
use plugin\ali_oss\service\Oss;
use plugin\qiniu_kodo\service\Kodo;
use laytp\controller\Backend;
use think\facade\Config;
use think\facade\Env;
use think\facade\Filesystem;

class Common extends Backend
{
    protected $noNeedLogin = ['*'];

    /**
     * ueditor上传接口
     */
    public function upload()
    {
        $config = json_decode(preg_replace("/\/\*[\s\S]+?\*\//", "", file_get_contents(root_path() . "/public/static/plugin/ueditor/php/config.json")), true);

        $laytpUploadConfig = ConfServiceFacade::groupGet('system.upload');
        $arrMimeType = explode(',', $laytpUploadConfig['mime']);
        foreach ($arrMimeType as $k => $v) {
            $arrMimeType[$k] = '.' . $v;
        }
        $config['imageAllowFiles'] = $arrMimeType;

        $typeDict = ['b' => 0, 'k' => 1, 'kb' => 1, 'm' => 2, 'mb' => 2, 'gb' => 3, 'g' => 3];
        preg_match('/(\d+)(\w+)/', $laytpUploadConfig['size'], $matches);
        $type = strtolower($matches[2]);
        $size = (int)$laytpUploadConfig['size'] * pow(1024, isset($typeDict[$type]) ? $typeDict[$type] : 0);
        $config['imageMaxSize'] = $size;
        $config['scrawlMaxSize'] = $size;
        $config['catcherMaxSize'] = $size;
        $config['videoMaxSize'] = $size;
        $config['fileMaxSize'] = $size;
        $config['maxSize'] = $size;
        $config['allowFiles'] = $arrMimeType;

        $action = $this->request->param('action');
        switch ($action) {
            case 'config':
                return json($config);
                break;
            /* 上传图片 */
            case 'uploadimage':
                break;
            /* 上传涂鸦 */
            case 'uploadscrawl':
                $config["oriName"] = "scrawl.png";
            /* 上传视频 */
            case 'uploadvideo':
                $config["oriName"] = "scrawl.png";
            /* 上传文件 */
            case 'uploadfile':
                break;
        }

        try {
            $uploadType = env('upload.uploadtype', 'local');
            if (!in_array($uploadType, ['local', 'ali-oss', 'qiniu-kodo'])) {
                return $this->error($uploadType . '上传方式未定义');
            }
            $file = $this->request->file('laytpUEditorUploadFile'); // 获取上传的文件
            if (!$file) {
                return $this->error('上传失败,请选择需要上传的文件');
            }
            $fileExt = strtolower($file->getOriginalExtension());
            $uploadDomain = new UploadDomain();
            if (!$uploadDomain->check($file->getOriginalName(), $file->getSize(), $fileExt, $file->getMime())) {
                return $this->error($uploadDomain->getError());
            }
            $saveName = date("Ymd") . "/" . md5(uniqid(mt_rand())) . ".{$fileExt}";
            /**
             * 不能以斜杆开头
             *  - 因为OSS存储时，不允许以/开头
             */
            $uploadDir = 'uploads';
            $object = $uploadDir . '/' . $saveName;//设置了上传目录的上传文件名

            $inputValue = "";
            //上传至七牛云
            if ($uploadType == 'qiniu-kodo') {
                if(ConfServiceFacade::get('qiniuKodo.conf.switch') != 1){
                    return $this->error('未开启七牛云KODO存储，请到七牛云KODO配置中开启');
                }
                $kodoConf = [
                    'accessKey' => ConfServiceFacade::get('qiniuKodo.conf.accessKey'),
                    'secretKey' => ConfServiceFacade::get('qiniuKodo.conf.secretKey'),
                    'bucket' => ConfServiceFacade::get('qiniuKodo.conf.bucket'),
                    'domain' => ConfServiceFacade::get('qiniuKodo.conf.domain'),
                ];
                $kodo = Kodo::instance();
                $kodoRes = $kodo->upload($file->getPathname(), $object, $kodoConf);
                if ($kodoRes) {
                    $inputValue = $kodoRes;
                } else {
                    return $this->error($kodo->getError());
                }
            }

            //上传至阿里云
            if ($uploadType == 'ali-oss') {
                /*if(ConfServiceFacade::get('system.aliOss.switch') != 1){
                    return $this->error('未开启阿里云OSS存储，请到阿里云OSS配置中开启');
                }*/
                $ossConf = [
                    'accessKeyID' => env('alioss.accesskeyid'),//ConfServiceFacade::get('aliOss.conf.accessKeyID'),
                    'accessKeySecret' => env('alioss.accesskeysecret'),//ConfServiceFacade::get('aliOss.conf.accessKeySecret'),
                    'bucket' => env('alioss.bucket'),//ConfServiceFacade::get('aliOss.conf.bucket'),
                    'endpoint' => env('alioss.endpoint'),//ConfServiceFacade::get('aliOss.conf.endpoint'),
                    'domain' => env('alioss.domain'),//ConfServiceFacade::get('aliOss.conf.domain'),
                ];
                $oss = Oss::instance();
                $ossUploadRes = $oss->upload($file->getPathname(), $object, $ossConf);
                if ($ossUploadRes) {
                    $inputValue = $ossUploadRes;
                } else {
                    return $this->error($oss->getError());
                }
            }

            //本地上传
            if ($uploadType == 'local') {
                $uploadDir = ltrim('/', $uploadDir);
                $saveName = Filesystem::putFileAs('/' . $uploadDir, $file, '/' . $object);
                $staticDomain = Env::get('domain.static');
                if ($staticDomain) {
                    $inputValue = $staticDomain . '/storage/' . $saveName;
                } else {
                    $inputValue = request()->domain() . '/static/storage/' . $saveName;
                }
            }

            return json(['url' => $inputValue, 'state' => 'SUCCESS']);
        } catch (\Exception $e) {
            return $this->exceptionError($e);
        }
    }
}