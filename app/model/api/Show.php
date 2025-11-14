<?php
/**
 * 首页
 */

namespace app\model\api;
use app\model\admin\NoJumpWechatPhone;
use app\model\api\customer\User;
use app\model\api\h5\HorseRaceLamp;
use app\model\api\v2\GatherUserInfo as GatherUserInfoModel;
use laytp\BaseModel;
use app\lib\api\exception\Exception;
use app\model\api\Thread;
use app\model\api\Banner;
use app\model\api\Article;
use app\model\api\Course;
use app\model\api\LandingPage;
use app\model\api\Channel;
use app\model\api\Merchant;
use app\model\api\UserList;
use think\facade\Config;
use app\lib\api\service\WeightService;
use app\lib\api\service\LandingPageService;
use think\facade\Db;
use app\lib\api\service\MerchantServiceV2;
use app\lib\api\wxmini\WxMiniCusqrcode;
use think\facade\Request;
use app\lib\api\other\UserCity;



class Show extends BaseModel
{
    public static function getCourseInfo($params = [])
    {
        extract($params);
        $userInfo = UserList::find($GLOBALS['uid']);
        $courseInfo = Course::withTrashed()->find($course_id);
        if (empty($courseInfo)) {
            new Exception('该课程名额已满');
        }
        if (!$userInfo->age_range_id) {
            new Exception('请填写年龄段');
        }
        $count = Thread::where('uid', $GLOBALS['uid'])->where('course_id', $courseInfo->id)->where('merchant_id', $courseInfo->merchant_id)->count();
        if ($count) {
            new Exception('你已经报名过该课程了', 103);
        }
        $merchantInfo = Merchant::find($courseInfo->merchant_id);
        if (empty($merchantInfo)) {
            new Exception('该课程名额已满');
        }
        $channelInfo = Channel::getChannelAppClass($channel);
        $gatherInfo = GatherUserInfoModel::getFormatGatherInfo($userInfo->age_range_id, 'age_range_id');
        $ageRange = $gatherInfo['name'];
        $weightArr = isset($merchantInfo->age_range_weight_json) && !empty($merchantInfo->age_range_weight_json) ? json_decode($merchantInfo->age_range_weight_json, true) : [];
        $weight = isset($weightArr[$ageRange]) && !empty($weightArr[$ageRange]) ? $weightArr[$ageRange] : 0;

        $request = Request::instance();
        $token = $request->header('token');

        if ($userInfo->age_range_id > 0 && $userInfo->age_range_id != 1) {
            $landingPageId = LandingPage::where('course_id', $courseInfo->id)->whereFindInSet('channel_ids', $channelInfo['channel_id'])->where('landing_page_type', 1)->value('id');
            if (isset($channelInfo['mc_h5_url']) && !empty($channelInfo['mc_h5_url'])) {
                $wxMiniConfig = (new WxMiniCusqrcode())->actionSqrcode($token, $courseInfo->id, $userInfo->app_class_id, $channel);
            }
            return [
                'course_id' => $courseInfo->id,
                'landing_page_id' => $landingPageId ? $landingPageId : 0,
                'entry_fee' => $courseInfo->entry_fee > 0 && UserCity::checkCity($channel) ? '0.00' :  $courseInfo->entry_fee,
                'is_jump_miniprogram' => $courseInfo->is_jump_miniprogram,
                'apply_success_msg' => $courseInfo->apply_success_msg,
                'is_under_eighteen_apply' => $courseInfo->is_under_eighteen_apply,
                'wxmini_link' => isset($wxMiniConfig['openlink_url']) ? $wxMiniConfig['openlink_url'] : '',

            ];
        } else {
            $channelInfoData = Channel::where('channel_name', $channel)->find();
            $appId = isset($channelInfoData['app_id']) && !empty($channelInfoData['app_id']) ? $channelInfoData['app_id'] : 0;
            $appInfo = App::where('id', $appId)->find();
            $data = MerchantServiceV2::getMerchantIsPayCount(['appInfo' => $appInfo, 'channelInfo' => $channelInfoData]);
            $merchantList = $courseInfo->entry_fee < 0.01 ? (!empty($data['tempFreeMerchantData']) ? $data['tempFreeMerchantData'] : $data['tempPayMerchantData']) : (!empty($data['tempPayMerchantData']) ? $data['tempPayMerchantData'] : $data['tempFreeMerchantData']);
            if (empty($merchantList)) {
                new Exception('该课程名额已满');
            }
            // 用户是否已报名
            $threadArr = Db::name('thread')
                ->where('course_id', 'in', array_column($merchantList, 'course_id'))
                ->where('uid', '=', $GLOBALS['uid'])
                ->column('course_id');
            foreach ($merchantList as $key => $val) {
                if (in_array($val['course_id'], $threadArr)) {
                    unset($merchantList[$key]);
                }
            }
            if (empty($merchantList)) {
                new Exception('该课程名额已满');
            }
            $merchantList = array_values($merchantList);
            $merchantId = (new WeightService)->initData($merchantList);
            if (empty($merchantId)) {
                new Exception('该课程名额已满');
            }
            $courseInfoData = Course::where('merchant_id', $merchantId)->whereFindInSet('app_ids', $appId)->where('course_type', 0)->find();
            if (!empty($courseInfoData)) {
                $courseInfo = $courseInfoData;
            } else {
                $courseInfo = Course::where('merchant_id', $merchantId)->where('course_type', 0)->find();
            }
            if (empty($courseInfo)) {
                new Exception('该课程名额已满');
            }
            $landingPageId = LandingPage::where('course_id', $courseInfo->id)->whereFindInSet('channel_ids', $channelInfo['channel_id'])->where('landing_page_type', 1)->value('id');
            if (isset($channelInfo['mc_h5_url']) && !empty($channelInfo['mc_h5_url'])) {
                $wxMiniConfig = (new WxMiniCusqrcode())->actionSqrcode($token, $courseInfo->id, $userInfo->app_class_id, $channel);
            }
            return [
                'course_id' => $courseInfo->id,
                'landing_page_id' => $landingPageId ? $landingPageId : 0,
                'entry_fee' => $courseInfo->entry_fee > 0 && UserCity::checkCity($channel) ? '0.00' :  $courseInfo->entry_fee,
                'is_jump_miniprogram' => $courseInfo->is_jump_miniprogram,
                'apply_success_msg' => $courseInfo->apply_success_msg,
                'is_under_eighteen_apply' => $courseInfo->is_under_eighteen_apply,
                'wxmini_link' => isset($wxMiniConfig['openlink_url']) ? $wxMiniConfig['openlink_url'] : '',
            ];
        }
    }



    //首页
    public static function homePage($params = [])
    {
        extract($params);
        $whereCon = [];
        $isChange = isset($is_change) && !empty($is_change) ? $is_change : 0;
        $appClassId = isset($app_class_id) && !empty($app_class_id) ? $app_class_id : 0;
        $homeMerchantId = isset($merchant_id) && !empty($merchant_id) ? $merchant_id : 0;
        $channelInfo = Channel::getChannelAppClass($channel);
        //多机构版本
        if(!empty($homeMerchantId) && $channelInfo['is_many_organization'] == 2){
            $redis = get_redis();
            $merchantId = $redis->get(env('redis.user_landing_page_redis_key') . $GLOBALS['uid']);
            $merchantId = !empty($homeMerchantId) ? $homeMerchantId : $merchantId;
            $whereMro['uid'] = ['=', $GLOBALS['uid']];
            $whereMro['merchant_id'] = ['=', $merchantId];
            $whereMro['app_id'] = ['=', $channelInfo['app_id']];
            $threadInfo = Thread::where($whereMro)->order('id asc')->find();
            //已报名
            if (!empty($threadInfo) && $threadInfo->course_id > 0) {
                $courseInfo = Course::withTrashed()->field('id,title,video_cover_image,video_url,apply_succeed_wx_btn,video_burning_time,entry_fee,btn_desc,virtual_apply_nums,merchant_id,landing_page_btn_image,confirm_btn_desc_background_color,confirm_btn_desc_font_color,confirm_btn_desc')->find($threadInfo->course_id);
                if (!empty($courseInfo)) {
                    $courseInfo['is_apply'] = 1;
                    $courseInfo['landing_page_list'] = [];
                    $courseInfo['btn_desc'] = $courseInfo['is_jump_miniprogram'] == 1 ? $courseInfo['apply_succeed_wx_btn'] : '';
                    $courseInfo['customer_link'] = Thread::getCustomerLink(['thread_id' => $threadInfo->id])['customer_link'];
                }
                return [
                    'bannerList' => Banner::getMerchantBannerList($threadInfo->merchant_id, $channelInfo),
                    'courseInfo' => $courseInfo,
                    'articleList' => Article::getMerchantArticleList($threadInfo->merchant_id, $channelInfo),
                ];
            } else{
                return self::getMerchantCourse($merchantId,$channelInfo);
            }
        }else {
            //单机构版本
            if ($appClassId) {
                $whereCon['app_class_id'] = ['=', $appClassId];
            } else {
                $whereCon['app_id'] = ['=', $channelInfo['app_id']];
            }
            $threadInfo = Thread::where('uid', $GLOBALS['uid'])->where($whereCon)->order('id asc')->find();
            $redis = get_redis();
            $merchantId = $redis->get(env('redis.user_landing_page_redis_key') . $GLOBALS['uid']);
            //已报名
            if (!empty($threadInfo) && $threadInfo->course_id > 0) {
                $courseInfo = Course::withTrashed()->field('id,title,video_cover_image,apply_succeed_wx_btn,video_url,video_burning_time,entry_fee,btn_desc,virtual_apply_nums,merchant_id,landing_page_btn_image,confirm_btn_desc_background_color,confirm_btn_desc_font_color,confirm_btn_desc')->find($threadInfo->course_id);
                if (!empty($courseInfo)) {
                    $courseInfo['is_apply'] = 1;
                    $courseInfo['landing_page_list'] = [];
                    $courseInfo['btn_desc'] = $courseInfo['is_jump_miniprogram'] == 1 ? $courseInfo['apply_succeed_wx_btn'] : '';
                    $courseInfo['customer_link'] = Thread::getCustomerLink(['thread_id' => $threadInfo->id])['customer_link'];
                }
                return [
                    'bannerList' => Banner::getMerchantBannerList($threadInfo->merchant_id,$channelInfo),
                    'courseInfo' => $courseInfo,
                    'articleList' => Article::getMerchantArticleList($threadInfo->merchant_id, $channelInfo),
                ];
            } else if ($merchantId > 0 && $isChange == 0 && !$appClassId) {
                return self::getMerchantCourse($merchantId, $channelInfo);
            } else {
                return self::changeMerchant($params, $channelInfo, $appClassId);
            }
        }
    }


    public static function getMerchantCourse($merchantId = 0,$channelInfo)
    {
        $courseInfo = Course::withTrashed()->field('id,title,video_cover_image,video_url,video_burning_time,entry_fee,btn_desc,virtual_apply_nums,merchant_id,landing_page_btn_image,confirm_btn_desc_background_color,confirm_btn_desc_font_color,confirm_btn_desc')->where('merchant_id', $merchantId)->where('course_type',0)->order('id desc')->find();
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
                if ($channelInfo['app_class_id'] == 15 && UserCity::checkCity($channelInfo['channel_name'])) {
                    if ($courseInfo['entry_fee'] > 0) {
                        $landingPage['landing_image'] = env('yxgxjzoppolandingpage.gx_pay_landingpage');
                    } else {
                        $landingPage['landing_image'] = env('yxgxjzoppolandingpage.gx_free_landingpage');
                    }
                }
                if ($courseInfo['entry_fee'] > 0 && UserCity::checkCity($channelInfo['channel_name'])) {
                    $landingPage['desc_image'] = env('yxgxjzoppolandingpage.desc_img');
                    $courseInfo['entry_fee'] = '0.00';
                }
                if ($courseInfo['entry_fee'] == 0 && UserCity::checkCity($channelInfo['channel_name'])) {
                    $landingPage['desc_image'] = env('yxgxjzoppolandingpage.free_desc_img');
                }
                $landingPageList = [$landingPage];

            }
            $courseInfo['is_apply'] = 0;
            $courseInfo['landing_page_list'] = $landingPageList;
            $courseInfo['customer_link'] = '';
        }
        return [
            'bannerList' => Banner::getMerchantBannerList($merchantId,$channelInfo),
            'courseInfo' => $courseInfo ?? new \stdClass(),
            'articleList' => Article::getMerchantArticleList($merchantId, $channelInfo),
        ];
    }
    public static function changeMerchant($params = [], $channelInfo, $appClassId)
    {
        extract($params);
        $landingPageId = 0;
        $courseId = 0;
        $user = UserList::where('id', $GLOBALS['uid'])->find();
        $count = Merchant::where('is_switch', 1)->where('is_source', 2)->where('app_class_id', 3)->count();
        if (!$appClassId) {
            if ($user->is_has_computer_id == 2 && in_array($user->age_range_id, [2,3,4]) && $count > 0 && in_array($user->channel, ['kuaixuepr_oppo','kuaixuepr_vivo'])) {
                $landingPageId = LandingPageService::getExpose(3, $channelInfo);
                if ($landingPageId) {
                    $courseId = LandingPage::where('id', $landingPageId)->value('course_id');
                }
            }
        }
        $appId = Channel::withTrashed()->where('channel_name', $channel)->value('app_id');
        $appId = $appId ?? 0;
        $merWhere = " 1 = 1 ";
        if ($courseId) {
            $where = " id = {$courseId} ";
        } else if ($appClassId){
            $where = " find_in_set({$appId}, app_ids) ";
            $merWhere .= " AND app_class_id = {$appClassId} ";
        } else {
            $where = " find_in_set({$appId}, app_ids) ";
        }
        // var_dump($merWhere);die;
        $courseMernchantList = Course::with(['merchant' => function($query) use ($merWhere){
            $query->where('is_switch', 1);
            $query->where($merWhere);
            $query->whereFindInSet('is_many_organization', 1);
        }])->where('status',  1)->where('course_type', 0)->where($where)->select();
        if (!empty($courseMernchantList)) {
            $merchantList = [];
            $sourceY = 0;
            $sourceN = 0;
            $ageRangeId = UserList::where('id', $GLOBALS['uid'])->value('age_range_id');
            $gatherInfo = GatherUserInfoModel::getFormatGatherInfo($ageRangeId, 'age_range_id');
            $ageRange = $gatherInfo['name'];
            foreach ($courseMernchantList as $val) {
                if (isset($val['merchant']['id'])) {
                    $ageRangeWeight = json_decode($val['merchant']['age_range_weight_json'],true);
                    $weight = isset($ageRangeWeight[$ageRange]) && !empty($ageRangeWeight[$ageRange]) ? $ageRangeWeight[$ageRange] : 0;
                    if ($weight > 0) {
                        if ($val['merchant']['is_source'] == 2) {
                            $sourceY++;
                        }
                        if ($val['merchant']['is_source'] == 1) {
                            $sourceN++;
                        }
                        $merchantList[] = [
                            'id' => $val['merchant']['id'],
                            'weight' => $weight,
                            'is_source' => $val['merchant']['is_source'],
                            'app_class_id' => $val['merchant']['app_class_id'],
                        ];
                    }
                }
            }
            if (!empty($merchantList)) {
                if ($sourceN > 0) {
                    foreach ($merchantList as $key => $value)
                    {
                        if ($value['is_source'] == 1 && $sourceY > 0) {
                            unset($merchantList[$key]);
                        }
                    }
                }
                $merchantList = array_values($merchantList);
                if (count($merchantList) > 1) {
                    foreach ($merchantList as $key => $val)
                    {
                        if ($val['app_class_id'] == 3) {
                            if (in_array($user->channel, ['kuaixuepr_oppo','kuaixuepr_vivo'])) {
                                if ($user->is_has_computer_id != 2 || !in_array($user->age_range_id, [2,3,4])) {
                                    unset($merchantList[$key]);
                                }
                            }
                        }

                    }
                }
                $merchantList = array_values($merchantList);
                $merchantId = (new WeightService)->initData($merchantList);
                return self::getMerchantCourse($merchantId, $channelInfo);
            }
            return [
                'bannerList' => [],
                'courseInfo' => new \stdClass(),
                'articleList' => [],
            ];
        }
        return [
            'bannerList' => [],
            'courseInfo' => new \stdClass(),
            'articleList' => [],
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
