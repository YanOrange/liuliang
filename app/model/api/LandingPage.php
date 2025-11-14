<?php
/**
 * 落地页表模型
 */

namespace app\model\api;

use app\model\admin\NoJumpWechatPhone;
use app\model\api\customer\User;
use app\model\api\h5\HorseRaceLamp;
use laytp\BaseModel;
use think\facade\Config;
use think\model\concern\SoftDelete;
use app\lib\api\exception\Exception;
use app\model\api\Channel;
use app\model\api\App;
use app\lib\api\service\LandingPageService;
use app\lib\api\service\LandingPageServiceV3;
use app\model\api\v2\UserList;
use think\facade\Db;
use app\lib\api\service\AbLandingPageService;
use app\lib\api\other\UserCity;

class LandingPage extends BaseModel
{
    use SoftDelete;
    //模型名
    protected $name = 'landing_page';

    protected $append = [
        'horse_race_lamp',
        'check_is_jump_miniprogram'
        //  'is_jump_miniprogram',
        //  'apply_success_msg',
    ];
    public function getLdylzBtnColorAttr($value, $data)
    {
        return isset($data['ldylz_btn_color']) ? (strpos($data['ldylz_btn_color'], '#') === false ? '#' . $data['ldylz_btn_color'] : $data['ldylz_btn_color'] ) : '';
    }
    public function getLdylzOptionColorAttr($value, $data)
    {
        return isset($data['ldylz_option_color']) ? (strpos($data['ldylz_option_color'], '#') === false ? '#' . $data['ldylz_option_color'] : $data['ldylz_option_color'] ) : '';
    }
    public function getApplySuccessMsgAttr($value, $data)
    {
        return '报名成功';
    }
    //是否跳转小程序
    public function getIsJumpMiniprogramAttr($value, $data)
    {
        $isJumpMiniprogram = 1;
        return isset($this->course->merchant->is_jump_miniprogram) ? $this->course->merchant->is_jump_miniprogram : $isJumpMiniprogram;
    }
    public function getCheckIsJumpMiniprogramAttr($value, $data)
    {
        $userInfo = UserList::where('id',empty($GLOBALS['uid']) ? 0 : $GLOBALS['uid'])->field('id,phone,wx_nickname')->find();
        return UserList::checkIsJumpMiniprogram($userInfo);
    }
    //落地页主图
    public function getLandingImageAttr($value, $data)
    {
        $userInfo = UserList::where('id',empty($GLOBALS['uid']) ? 0 : $GLOBALS['uid'])->field('id,phone,wx_nickname,app_class_id')->find();
        if ($this->check_is_jump_miniprogram > 0) {
            $landPageConfig = Config::load('extra/landpageimage', 'extra');
            return $landPageConfig['land_page_image'][$userInfo['app_class_id']] ?? $data['landing_image'] ;
        }
        return $data['landing_image'];
    }

    //落地页介绍图
    public function getDescImageAttr($value, $data)
    {
        if ($this->check_is_jump_miniprogram > 0) {
            $landPageConfig = Config::load('extra/landpageimage', 'extra');
            return $landPageConfig['desc_image'] ?? $data['desc_image'] ;
        }
        return $data['desc_image'];
    }

    //落地页尾图
    public function getEndImageAttr($value, $data)
    {
        if ($this->check_is_jump_miniprogram > 0) {
            return '';
        }
        return $data['end_image'];
    }

    //落地页列表
    public static function getLandingPageList($params = [], $landingPageExpose = true)
    {
        extract($params);
        $channelInfoData = Channel::where('channel_name', $channel)->find();
        $userInfoData = UserList::find($GLOBALS['uid']);
        if (UserList::checkIsVestBag($channelInfoData,$userInfoData)) { //为马甲包不展示落地页
            return  [];
        }
        $channelInfo = Channel::where('channel_name', $channel)->where('is_landing_page', 1)->find();
        $appId = isset($channelInfo['app_id']) && !empty($channelInfo['app_id']) ? $channelInfo['app_id'] : 0;
        $appInfo = App::where('id', $appId)->where('is_landing_page', 1)->find();
        $userInfo = UserList::where('id',$GLOBALS['uid'])->field('id,phone,wx_nickname,is_test,age_range_id')->find();
        $landingPageList = [];
        if (!empty($channelInfo) && !empty($appInfo)) {
            $result = LandingPageServiceV3::getExpose(['appInfo' => $appInfo, 'channelInfo' => $channelInfo]);
            if ($result) {
                extract($result);
                if ($landingPageId && $courseData) {
                    $landingPageList = self::field('id,app_id,landing_image,lamp_back_image,end_image,desc_image,not_wx_landing_image,not_wx_desc_image,lamp_font_color,is_lamp,video_url,btn_desc,btn_gif,ldylz_option_btn_desc,ldylz_btn_color,ldylz_option_color,video_cover_image')->find($landingPageId);
                    $landingPageList = $landingPageList->toArray();
                    if ($channel == 'xchmh_oppo' && in_array($userInfo['age_range_id'], [4,5,6])) {
                        if ($courseData->entry_fee > 0) {
                            $landingPageList['landing_image'] = env('yxgxjzoppolandingpage.paylandingpage');
                            $landingPageList['desc_image'] = env('yxgxjzoppolandingpage.paydescimage');
                        } else {
                            $landingPageList['landing_image'] = env('yxgxjzoppolandingpage.freelandingpage');
                            $landingPageList['desc_image'] = env('yxgxjzoppolandingpage.freedescimage');
                        }
                    }
                    if ($appInfo['app_class_id'] == 15 && UserCity::checkCity($channel)) {
                        if ($courseData->entry_fee > 0) {
                            $landingPageList['landing_image'] = env('yxgxjzoppolandingpage.gx_pay_landingpage');
                        } else {
                            $landingPageList['landing_image'] = env('yxgxjzoppolandingpage.gx_free_landingpage');
                        }
                    }
                    if ($courseData->entry_fee > 0 && UserCity::checkCity($channel)) {
                        $landingPageList['desc_image'] = env('yxgxjzoppolandingpage.desc_img');
                        $courseData->entry_fee = '0.00';
                    }
                    if ($courseData->entry_fee == 0 && UserCity::checkCity($channel)) {
                        $landingPageList['desc_image'] = env('yxgxjzoppolandingpage.free_desc_img');
                    }
                    //不跳转微信小程序
                    if (!$courseData->is_jump_miniprogram) {
                        $landingPageList['landing_image'] = !empty($landingPageList['not_wx_landing_image']) ? $landingPageList['not_wx_landing_image'] : $landingPageList['landing_image'];
                        $landingPageList['desc_image'] = !empty($landingPageList['not_wx_desc_image']) ? $landingPageList['not_wx_desc_image'] : $landingPageList['desc_image'];
                    }
                    $landingPageList['course_id'] = $courseData->id;
                    $courseData['video_url'] = $landingPageList['video_url'];
                    $courseData['btn_desc'] = $landingPageList['btn_desc'];
                    $landingPageList['is_affirm_page'] = $courseData['entry_fee'] > 0 ? $channelInfo->pay_landing_page_affirm : $channelInfo->free_landing_page_affirm;
                    $landingPageList['course'] = $courseData->toArray();
                    $landingPageList['ab_landing_list'] = AbLandingPageService::getAbLandingList($landingPageId, isset($is_ab) ? $is_ab : 0);
                    unset($landingPageList['btn_desc']);
                    unset($landingPageList['video_url']);
                }
            }
            if (!empty($landingPageList)) {
                if ($landingPageExpose) {
                    $data = [
                        'landing_page_id' => $landingPageList['id'],
                        'course_id' => $landingPageList['course_id'],
                        'user_id'  => $GLOBALS['uid'],
                        'channel' => $channel,
                        'system_type' => 2,
                        'app_id' => $appInfo['id'],
                        'app_class_id' => $appInfo['app_class_id'],
                        'channel_id' => $channelInfo['id'],
                        'is_test' => $userInfo['is_test'],
                        'day_channel_id' => date('Ymd').$channelInfo['id'],
                        'course_pay_price' => $courseData['entry_fee'],
                    ];
                    event('LandingPageExpose',$data);//落地页曝光
                }
                $landingPageList = [$landingPageList];
            }
        }
        if ($channel == 'yxyh_huawei') {
                    file_put_contents('./ldy.txt', isset($landingPageList[0]['course_id']) ? $landingPageList[0]['course_id'] : 0);

        }
        return $landingPageList;
    }

    public function getHorseRaceLampAttr($value, $data)
    {
        $horseRaceLamp = [];
        if(isset($data['is_lamp']) && $data['is_lamp'] == 1) {
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

    public function course()
    {
        return $this->belongsTo('app\model\api\Course', 'course_id', 'id');
    }

}