<?php
/**
 * 首页
 */

namespace app\model\api\overdue;
use app\lib\api\service\MerchantServiceOverdue;
use app\model\api\Channel;
use app\model\api\Course as CourseModel;
use app\model\api\h5\HorseRaceLamp;
use app\model\api\LandingPage;
use app\model\api\Thread;
use app\model\api\UserList;
use laytp\BaseModel;

class Course extends BaseModel
{
    //首页
    public static function getCourseLandingPage($params = [])
    {
        extract($params);
        $courseId = isset($course_id) && !empty($course_id) ? $course_id : 0;
        if(!empty($courseId)){
            $merchantId = CourseModel::where('id',$courseId)->value('merchant_id');
        }else{
            $merchantInfo = MerchantServiceOverdue::getOverdueMerchant($channel);
            $merchantId = $merchantInfo['merchant_id'];
        }
        if (empty($merchantId)) {
            $merchantId = Thread::where('uid', $GLOBALS['uid'])->value('merchant_id');
        }
        $channelInfo = Channel::getChannelAppClass($channel);
        if(!empty($channelInfo) && !empty($merchantId)){
            return self::getMerchantCourse($merchantId, $channelInfo);
        }
    }

    public static function getMerchantCourse($merchantId = 0,$channelInfo)
    {
        $courseInfo = CourseModel::withTrashed()->field('id,title,video_cover_image,video_url,video_burning_time,entry_fee,btn_desc,virtual_apply_nums,merchant_id,landing_page_btn_image,confirm_btn_desc_background_color,confirm_btn_desc_font_color,confirm_btn_desc')->where('merchant_id', $merchantId)->whereFindInSet('app_ids',$channelInfo['app_id'])->where('course_type',0)->order('id desc')->find();
        if (!empty($courseInfo)) {
            $redis = get_redis();
            $redis->set(env('redis.user_landing_page_redis_key') . $GLOBALS['uid'], $merchantId);
            $landingPageList = [];
            $landingPage = LandingPage::withTrashed()->with(['course' => function($query){
                $query->field('id,btn_desc,video_url,merchant_id,entry_fee,virtual_apply_nums,confirm_btn_desc_background_color,confirm_btn_desc_font_color,confirm_btn_desc');
            }])->field('id,landing_image,lamp_back_image,end_image,desc_image,lamp_font_color,is_lamp,course_id')->where('course_id', $courseInfo->id)->find();
            if (!empty($landingPage)) {
                unset($landingPage['course']['apply_nums']);
                $landingPage['horse_race_lamp'] = self::getHorseRaceLamp($landingPage);
                $landingPage['is_affirm_page'] = $courseInfo['entry_fee'] > 0 ? $channelInfo['pay_landing_page_affirm'] : $channelInfo['free_landing_page_affirm'];
                $userInfo = UserList::find($GLOBALS['uid']);
                if ($channelInfo['channel_name'] == 'xchmh_oppo' && in_array($userInfo['age_range_id'], [4,5,6])) {
                    if ($courseInfo['entry_fee'] > 0) {
                        $landingPage['landing_image'] = env('yxgxjzoppolandingpage.paylandingpage');
                        $landingPage['desc_image'] = env('yxgxjzoppolandingpage.paydescimage');
                    } else {
                        $landingPage['landing_image'] = env('yxgxjzoppolandingpage.freelandingpage');
                        $landingPage['desc_image'] = env('yxgxjzoppolandingpage.freedescimage');
                    }
                }
                $landingPageList = [$landingPage];

            }
            $courseInfo['is_apply'] = Thread::where('uid',$GLOBALS['uid'])->where('merchant_id',$merchantId)->count() > 0 ? 1 : 0;
            $courseInfo['landing_page_list'] = ($courseInfo['is_apply'] || !$channelInfo['is_landing_page']) ? [] : $landingPageList;
        }
        return [
            'courseInfo' => $courseInfo ?? new \stdClass(),
        ];
    }

    public static function getHorseRaceLamp($data)
    {
        $horseRaceLamp = [];
        if($data['is_lamp'] == 1) {
            $horseRaceLamp = HorseRaceLamp::field('nickname,phone,times')->order('times', 'asc')->select();
            if (!empty($horseRaceLamp)) {
                foreach ($horseRaceLamp as &$val) {
                    $phone_xing = substr($val->phone, 4, 4);  //获取手机号中间四位
                    $val['nickname'] = subNickname($val->nickname);
                    $val['phone'] = str_replace($phone_xing, '****', $val->phone);  //用****进行替换
                    $val['times'] = $val->times . '分钟前';
                }
            }
        }
        return $horseRaceLamp;
    }

}
