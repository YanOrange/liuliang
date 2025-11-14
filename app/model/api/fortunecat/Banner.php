<?php
/**
 * 轮播图表模型
 */

namespace app\model\api\fortunecat;

use laytp\BaseModel;
use think\model\concern\SoftDelete;

class Banner extends BaseModel
{
    use SoftDelete;
    //模型名
    protected $name = 'banner';

    //轮播图列表
    public static function getMerchantBannerList($merchantId = 0,$channelInfo)
    {
        $where[] = ['status','=',1];
        if($merchantId){
            $where[] = ['merchant_id','=',$merchantId];
        }
        $bannerList = self::where($where)
            ->where('app_id',$channelInfo['app_id'])
            ->where('is_many_organization',$channelInfo['is_many_organization'])
            ->order(['sort'=>'desc','id'=>'desc'])
            ->field('id,image,jump_mode,jump_mode_json,jump_url')
            ->select()->toArray();
        if(empty($bannerList)){
            $bannerList = self::where('type', 1)
                ->whereFindInSet('channel_ids',$channelInfo['channel_id'])
                ->where('status', 1)
                ->field('id,image,jump_mode,jump_mode_json,jump_url')
                ->order(['sort'=>'desc','id'=>'desc'])
                ->select()->toArray();
        }
        return !empty($bannerList) ? $bannerList : [];
    }

    public function getJumpModeJsonAttr($value, $data)
    {
        $jumpModeJson = !empty($data['jump_mode_json']) ? json_decode($data['jump_mode_json'],true) : [];
        return $jumpModeJson;
    }
}
