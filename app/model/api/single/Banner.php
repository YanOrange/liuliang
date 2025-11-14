<?php

namespace app\model\api\single;

use think\model\concern\SoftDelete;
use laytp\BaseModel;
use app\model\api\Channel;

class Banner extends BaseModel
{
    use SoftDelete;
    //模型名
    protected $name = 'banner';

    public static function getBannerList($params = [])
    {
        extract($params);
        $channelInfo = Channel::getChannelAppClass($channel);
        $bannerList = self::where('status',1)
            ->whereFindInSet('channel_ids',$channelInfo['channel_id'])
            //->where('is_many_organization',$channelInfo['is_many_organization'])
            ->order('sort desc')
            ->field('id,image,jump_mode,jump_mode_json,jump_url')
            ->limit(3)
            ->select();
        return $bannerList;
    }

    public function getJumpModeJsonAttr($value, $data)
    {
        $jumpModeJson = !empty($data['jump_mode_json']) ? json_decode($data['jump_mode_json'],true) : [];
        if(!empty($jumpModeJson)){
            $jumpModeJson['entry_fee'] = 0;
            if($jumpModeJson['module_id'] == 6){
                $jumpModeJson['entry_fee'] = SingleCourse::where('id',$jumpModeJson['course_id'])->value('entry_fee');
            }
        }
        return $jumpModeJson;
    }
}