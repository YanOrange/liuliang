<?php
/**
 * 后台应用表模型
 */

namespace app\model\admin;

use laytp\BaseModel;
use think\model\concern\SoftDelete;
use app\lib\api\exception\ExceptionStd;
use app\lib\api\vres\Aes;
class App extends BaseModel
{
    use SoftDelete;

    //模型名
    protected $name = 'app';

    //获取初始化配置文件
    public static function getInitConfig($params = [])
    {
        extract($params);
        $initConfig = self::where('android_bundle_id|ios_bundle_id', '=', $app_bundle_id)->field('alipay_app_id,wxpay_app_id,wxmini_original_id,yd_business_id')->find();
        $channelInfo = Channel::where('channel_name',$channel)->field('id,startup_page_image,app_home_desc')->find();
        if (empty($initConfig) || empty($channelInfo)) {
            new ExceptionStd('初始化配置文件失败');
        }
        $initConfig = $initConfig->toArray();
        return ['data' => Aes::aesEncrypt(json_encode($initConfig), env('sign.aeskey')),'startup_page_image' => $channelInfo->startup_page_image,'app_home_desc' => json_decode($channelInfo->app_home_desc,true)];
    }
    public function class()
    {
        return $this->belongsTo('app\model\admin\AppClass','app_class_id','id')->removeOption('soft_delete');
    }
}
