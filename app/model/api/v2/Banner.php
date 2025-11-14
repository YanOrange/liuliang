<?php
/**
 * 轮播图表模型
 */

namespace app\model\api\v2;

use laytp\BaseModel;
use think\model\concern\SoftDelete;
use app\lib\api\exception\Exception;
use app\model\api\Channel;
use app\model\api\App;
use app\model\api\Thread;
use app\model\api\Course;
class Banner extends BaseModel
{
    use SoftDelete;
    //模型名
    protected $name = 'banner';

    //protected $hidden = ['merchant_id'];

    //轮播图列表
    public static function getMerchantBannerList($merchantId = 0,$channelInfo)
    {
        $bannerList = self::where('status',1)
            ->whereFindInSet('merchant_id',$merchantId)
            //->where('is_many_organization',$channelInfo['is_many_organization'])
            ->whereFindInSet('channel_ids',$channelInfo['channel_id'])
            ->order('sort desc')
            ->field('id,image,merchant_id,jump_mode,jump_mode_json,jump_url')
            ->limit(5)
            ->select()->toArray();
        if(empty($bannerList)){
            $bannerList = self::field('id,image,jump_mode,jump_mode_json,jump_url,merchant_id')->whereFindInSet('merchant_id', $merchantId)->where('status', 1)->where('type', 0)->order('sort desc')->limit(5)->select();
        }
        foreach($bannerList as &$val) {
            $merchantIds = explode(',', $val['merchant_id']);
            $threadCount = 0;
            $courseId = 0;
            if (count($merchantIds) == 1) {
                $threadCount = Thread::where('uid', $GLOBALS['uid'])->where('merchant_id', $val['merchant_id'])->count();
                $courseId = Course::where('status', 1)->where('merchant_id', $val['merchant_id'])->value('id');
            }
            $val['jump_mode'] = $threadCount > 0 || count($merchantIds) > 1 ? 0 : $val['jump_mode'];
            $val['jump_mode_json'] = [
                'module_id' => 1,
                'course_id' => $courseId
            ];
            unset($val['merchant_id']);
//            if(isset($val['jump_mode_json']) && !empty($val['jump_mode_json'])){
//                $jumpModeJson = $val['jump_mode_json'];
//                if($jumpModeJson['module_id'] == 1){
//                    $threadCount = Thread::where('uid',$GLOBALS['uid'])->where('course_id',$jumpModeJson['course_id'])->count();
//                    if($threadCount > 0){
//                        $val['jump_mode'] = 0;
//                    }
//                }
//            }
        }
        return !empty($bannerList) ? $bannerList : [];
    }
}
