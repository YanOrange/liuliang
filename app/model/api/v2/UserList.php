<?php
/**
 * 用户表模型
 */

namespace app\model\api\v2;

use app\lib\api\city\IpCity;
use app\lib\api\service\MerchantServiceOverdue;
use app\model\admin\NoJumpWechatPhone;
use app\model\api\Channel;
use app\model\api\Course;
use app\model\api\ReceiveMonitorData;
use app\model\api\Thread;
use app\model\api\TodayPlanDetailData;
use app\model\api\TodayVivoPlanDetailData;
use app\model\api\v2\GatherUserInfo;
use app\model\api\ViolatingWords;
use app\services\api\YqPortalService;
use laytp\BaseModel;
use think\model\concern\SoftDelete;
use app\lib\api\exception\Exception;
use app\lib\api\exception\ExceptionStd;
use think\facade\Event;
use think\facade\Db;
use app\lib\api\oneclicklogin\OneClickPhoneLogin;
use think\facade\Config;
use app\lib\api\wx\WxAuth;
use app\model\api\Captcha;
use app\model\api\AdvertiserCallbackRecord;
use app\model\api\Merchant;
use app\model\api\App;
use app\model\api\TodayReceiveMonitorData;
use app\lib\api\other\CourseJumpWx;
use app\model\admin\Thread as AdminThread;
use app\model\api\ChannelConfig;
use app\model\api\InviteThreadUser;
use app\model\api\PersonalTransferAgreement;

class UserList extends BaseModel
{
    use SoftDelete;

    //模型名
    protected $name = 'user_list';
    const ALOWFIELDS = ['phone', 'nickname', 'avatar'];

    //是否是马甲包
    public static function checkIsVestBag($channel = null, $user = null)
    {
        $isVestBag = 0;
        if (!empty($channel) && !empty($user)) {
            if ((isset($channel['app_version']) && !empty($channel['app_version'])) && (isset($user['app_version']) && !empty($user['app_version']))) {
                $channelVersionArr = explode(',',$channel['app_version']);
                if(in_array($user['app_version'], $channelVersionArr)){
                    $isVestBag  = isset($channel['is_vest_bag']) ? $channel['is_vest_bag'] : 0;
                }
            }
        }
        return $isVestBag;
    }

    //是否是马甲包
    public static function checkIsJumpMiniprogram($user = null)
    {
        $noJumpphoneInfo = 0;
        if (!empty($user) && !empty($user['phone']) && !empty($user['wx_nickname'])) {
            $noJumpphoneInfo = NoJumpWechatPhone::whereOr([['phone', '=', $user['phone']], ['wx_nickname', '=', $user['wx_nickname']]])->count();
        }
        return $noJumpphoneInfo;
    }

    //是否需要授权微信
    public static function checkIsWxAuth($channel = null, $user = null)
    {
        $isWxAuth = $user['app_class_id'] == 9 ? 0 : 1;
        if (!empty($channel) && !empty($user)) {
            if ((isset($channel['auth_wx_version']) && !empty($channel['auth_wx_version'])) && (isset($user['app_version']) && !empty($user['app_version']))) {
                if($channel['auth_wx_version'] == $user['app_version']){
                    $isWxAuth = $channel['is_wx_auth'];
                }
            }
        }
        //$isWxAuth = Channel::where('id', $user['channel_id'])->value('is_wx_auth');
        return !empty($user['wxopenid']) ? 0 : (is_int($isWxAuth) ? $isWxAuth : 1);
    }

    //本机号码一键登陆
    public static function oneClickPhoneLogin($params = [])
    {
        extract($params);
        $phone = (new OneClickPhoneLogin())->oneClickCheck(['token' => $token, 'accessToken' => $accessToken], $app_bundle_id);
        $params['phone'] = $phone;
        return self::loginPhoneCaptcha($params, false);
    }

    //绑定微信
    public static function bindWx($params = [])
    {
        $userInfo = self::find($GLOBALS['uid']);
        $wxUserinfo = WxAuth::wxAuthLogin($params);
        $type = isset($params['type']) ? $params['type'] : 1;
        if (empty($userInfo)) {
            new ExceptionStd('用户信息不存在');
        }
        if ($type == 2) {
            $userInfo->nickname = $wxUserinfo['nickname'];
        }
        $userInfo->wxopenid = $wxUserinfo['openid'];
        $userInfo->wx_nickname = $wxUserinfo['nickname'];
        $userInfo->avatar = $wxUserinfo['headimgurl'];
        $ret = $userInfo->save();
        if ($ret !== false) {
            return self::getUserInfo(['uid' => $GLOBALS['uid']]);
        }
        new ExceptionStd('系统异常');
    }

    //莓茶微信授权登陆
    public static function bindWxLogin($params = [])
    {
        extract($params);
        $channelInfo = Channel::getChannelAppClass($channel);
        $wxUserinfo = WxAuth::wxAuthLogin($params);
        if(isset($wxUserinfo['errcode'])){
            new ExceptionStd($wxUserinfo['errmsg']);
        }
        $userInfo = self::where('wxopenid', $wxUserinfo['openid'])->where('status', 1)->where('channel_id', $channelInfo['channel_id'])->find();
        if (!empty($userInfo)) {
            $userInfo->login_time = date("Y-m-d H:i:s");
            $userInfo->login_ip = request()->ip();
            $userInfo->app_version = $app_version ?? '';
            $userInfo->save();
            $token = getJwtToken($userInfo->id);
            $userInfo = self::getUserInfo(['uid' => $userInfo->id]);
            $userInfo['token'] = $token;
            return $userInfo;
        } else {
            $cityInfo = IpCity::getIpToCity();
            Db::startTrans();
            try {
                $is_switch = 0;
                $is_test = 0;
                $merchant = Merchant::where('is_switch', 1)->where('is_source', 2)->where('app_class_id',$channelInfo['app_class_id'])->count();
                if ($merchant > 0) $is_switch = 1;
                if ($wxUserinfo == '我是一朵花') $is_test = 1;
                $oaid_two = $oaid ?? '';
                if (!empty($oaid_two) && strpos($channel, 'vivo') !== false) $oaid_two = md5($oaid_two);
                $user = self::create([
                    'nickname' => $wxUserinfo['nickname'],
                    'wxopenid' => $wxUserinfo['openid'],
                    'wx_nickname' => $wxUserinfo['nickname'],
                    'avatar' => $wxUserinfo['headimgurl'],
                    'login_time' => date("Y-m-d H:i:s"),
                    'login_ip' => request()->ip(),
                    'channel' => $channel,
                    'store' => $channelInfo['store'],
                    'app_bundle_id' => $app_bundle_id,
                    'oaid' => $oaid ?? '',
                    'md5_oaid' => isset($oaid) && !empty($oaid) ? md5($oaid) : '',
                    'idfa' => $idfa ?? '',
                    'channel_id' => $channelInfo['channel_id'],
                    'app_id' => $channelInfo['app_id'],
                    'app_class_id' => $channelInfo['app_class_id'],
                    'is_switch' => $is_switch,
                    'province' => $cityInfo['province_name'] ?? '',
                    'city' => $cityInfo['city_name'] ?? '',
                    'is_test' => $is_test ?? 0,
                    'oaid_two' => $oaid_two,
                    'age_range_id' => self::checkChannelAge($channel),  // 逾期版本默认年龄段 chenlele 22.09.21
                    'app_version' => $app_version ?? '',
                    'is_search_plan' => self::userSearchType($oaid,$channel,$app_bundle_id),
                    'machine_type' => $machine_type ?? '',
                    'imei' => $imei ?? ''
                ]);

                UserProfile::create([
                    'uid' => $user->id,
                ]);
                Db::commit();
                $token = getJwtToken($user->id);
                $userInfo = self::getUserInfo(['uid' => $user->id]);
                $userInfo['token'] = $token;
                $oaid = $oaid ?? '';
                $oppoConfig = Config::load('extra/oppo', 'extra');
                $bindPhoneAdvertiserCollbackChannel = $oppoConfig['bindPhoneAdvertiserCollbackChannel'];
                if(!in_array($channel,$bindPhoneAdvertiserCollbackChannel)) {
                    if (empty($oaid)) {
                        $oaid = AdvertiserCallbackRecord::where('channel_name', $channel)->where('app_bundle_id', $app_bundle_id)->where('cvType', 'active')->order('create_time', 'desc')->value('oaid');
                    }
                    $callBackData = [
                        'user' => ['channel' => $channel, 'oaid' => $oaid, 'app_bundle_id' => $app_bundle_id],
                        'dataType' => 'register',
                    ];
                    event('UserCallbackRecord', $callBackData);//广告主回传
                }
                self::checkChannelAge($channel);
                return $userInfo;
            } catch (\Exception $e) {
                Db::rollback();
                new ExceptionStd($e->getMessage());
            }
        }
    }

    //手机号验证码
    public static function checkPhoneCaptcha($params = [], $checkCode = true)
    {
        extract($params);
        if ($checkCode) {
            Captcha::checkCaptcha(['phone' => $phone, 'type' => 1], $captcha);
        }
        $channelInfo = Channel::getChannelAppClass($channel);
        $user = self::where('phone', $phone)->where('status', 1)->where('channel_id', $channelInfo['channel_id'])->find();
        if(!empty($user)){
            new ExceptionStd('手机号已存在');
        }
        $userInfo = self::find($GLOBALS['uid']);
        if (empty($userInfo)) {
            new ExceptionStd('用户不存在');
        }
        $is_test = 0;
        if (substr($phone,0,2) === '11') $is_test = 1;
        $userInfo->phone = $phone;
        $userInfo->phone_end_number = substr($phone, -4);
        $userInfo->is_test = $is_test;
        $userInfo->save();
        $oaid = $user->oaid ?? '';
        $oppoConfig = Config::load('extra/oppo', 'extra');
        $bindPhoneAdvertiserCollbackChannel = $oppoConfig['bindPhoneAdvertiserCollbackChannel'];
        if(in_array($channel,$bindPhoneAdvertiserCollbackChannel)) {
            if (empty($oaid)) {
                $oaid = AdvertiserCallbackRecord::where('channel_name', $channel)->where('app_bundle_id', $app_bundle_id)->where('cvType', 'active')->order('create_time', 'desc')->value('oaid');
            }
            $callBackData = [
                'user' => ['channel' => $channel, 'oaid' => $oaid, 'app_bundle_id' => $app_bundle_id],
                'dataType' => 'register',
            ];
            event('UserCallbackRecord', $callBackData);//广告主回传
        }
    }

    //手机验证码登录
    public static function loginPhoneCaptcha($params = [], $checkCode = true)
    {
        extract($params);
        $checkPhoneCount = PhoneBlacklist::where('phone', $phone)->count();
        if ($checkPhoneCount > 0) {
            new ExceptionStd('网络异常'.$phone);
        }
        /*$checkPhoneCount = self::whereDay('create_time')->where('phone', $phone)->where('status', 1)->count();
        if ($checkPhoneCount >= 3) {
            self::where('phone', $phone)->save(['status' => 0]);
            PhoneBlacklist::create(['phone' => $phone]);
            new ExceptionStd('网络异常');
        }*/
        $nickname = isset($nickname) && !empty($nickname) ? $nickname : '设置昵称';
        $channelInfo = Channel::getChannelAppClass($channel);
        if ($checkCode) {
            Captcha::checkCaptcha(['phone' => $phone, 'type' => 1], $captcha);
        }
        $violatingWords = ViolatingWords::column('words');
        if(in_array($nickname,$violatingWords)){
            new ExceptionStd('昵称不规范');
        }
        $isLogin = 0;
        $userInfo = self::where('phone', $phone)->where('status', 1)->where('channel_id', $channelInfo['channel_id'])->find();
        if (!empty($userInfo)) {
            $isLogin = 1;
            $userInfo->login_time = date("Y-m-d H:i:s");
            $userInfo->login_ip = request()->ip();
            $userInfo->app_version = $app_version ?? '';
            $userInfo->save();
            $token = getJwtToken($userInfo->id);
            $userInfo = self::getUserInfo(['uid' => $userInfo->id]);
            $userInfo['token'] = $token;
            return $userInfo;
        } else {
            $cityInfo = IpCity::getIpToCity();
            Db::startTrans();
            try {
                $is_switch = 0;
                $is_test = 0;
                $merchant = Merchant::where('is_switch', 1)->where('is_source', 2)->where('app_class_id',$channelInfo['app_class_id'])->count();
                if ($merchant > 0) {
                    $is_switch = 1;
                }
                if (strpos($nickname, '测试') !== false || substr($phone,0,2) === '11' || $phone == '13777571709') {
                    $is_test = 1;
                }
                //$oldUser = self::where('phone', $phone)->find();
                $oaid_two = $oaid ?? '';
                if (!empty($oaid_two) && strpos($channel, 'vivo') !== false)
                    $oaid_two = md5($oaid_two);
                $user = self::create([
                    'phone' => $phone,
                    'nickname' => $nickname,
                    'login_time' => date("Y-m-d H:i:s"),
                    'login_ip' => request()->ip(),
                    'channel' => $channel,
                    'store' => $channelInfo['store'],
                    'app_bundle_id' => $app_bundle_id,
                    'oaid' => $oaid ?? '',
                    'md5_oaid' => isset($oaid) && !empty($oaid) ? md5($oaid) : '',
                    'idfa' => $idfa ?? '',
                    'channel_id' => $channelInfo['channel_id'],
                    'app_id' => $channelInfo['app_id'],
                    'app_class_id' => $channelInfo['app_class_id'],
                    'is_switch' => $is_switch,
                    'province' => $cityInfo['province_name'] ?? '',
                    'city' => $cityInfo['city_name'] ?? '',
                    'is_test' => $is_test ?? 0,
                    'is_search_plan' => self::userSearchType($oaid ?? '',$channel,$app_bundle_id),
                    'oaid_two' => $oaid_two,
                    'phone_end_number' => substr($phone, -4),
                    'age_range_id' => self::checkChannelAge($channel),  // 逾期版本默认年龄段 chenlele 22.09.21
                    'app_version' => $app_version ?? '',
                    'machine_type' => $machine_type ?? '',
                    'imei' => $imei ?? ''
                ]);

                UserProfile::create([
                    'uid' => $user->id,
                ]);
                Db::commit();
                $token = getJwtToken($user->id);
                $userInfo = self::getUserInfo(['uid' => $user->id]);
                $userInfo['token'] = $token;
                $oaid = $oaid ?? '';
                if (empty($oaid)) {
                    $oaid = AdvertiserCallbackRecord::where('channel_name', $channel)->where('app_bundle_id', $app_bundle_id)->where('cvType', 'active')->order('create_time', 'desc')->value('oaid');
                }
                $callBackData = [
                    'user' => ['channel' => $channel, 'oaid' => $oaid, 'app_bundle_id' => $app_bundle_id,'phone'=>$phone],
                    'dataType' => 'register',
                ];
                event('UserCallbackRecord', $callBackData);//广告主回传
                self::checkChannelAge($channel);
                /*$startTime = strtotime(date("Y-m-d") . ' 08:00:00');
                $endTime = strtotime(date("Y-m-d") . ' 23:00:00');
                $nowTime = time();
                if (strpos($channel, "huawei") !== false && $channelInfo['app_class_id'] == 9 && $user->is_test == 0 && $isLogin == 0 && $nowTime >= $startTime && $nowTime <= $endTime) {
                    $courseIdData = [167,269,438,487];
                    $courseId = $courseIdData[array_rand($courseIdData)];
                    \app\model\api\Thread::registerApplyCourse(['course_id' => $courseId], $user->id);
                }*/
                return $userInfo;
            } catch (\Exception $e) {
                Db::rollback();
                new ExceptionStd('登陆失败');
            }
        }
    }

    //检测渠道是否勾选年龄段字段，没勾选默认返回一个2的年龄id
    public static function checkChannelAge($channel = null)
    {
        $defaultAgeId = 2;
        if (empty($channel)) {
            return $defaultAgeId;
        }
        $gatherUserInfoIds = Channel::getFieldByChannelName($channel, 'gather_user_info_ids');
        if (empty($gatherUserInfoIds)) {
            return $defaultAgeId;
        }
        $gatherUserInfoArr = json_decode($gatherUserInfoIds, true);
        $data = self::seacharr_by_value($gatherUserInfoArr, 'pid', '1');
        if (empty($data)) {
            return $defaultAgeId;
        }
        if ($channel == 'xbxyhxh_ios') {
            return 2;
        }
        return 0;
    }

    //二维数组查找
    public static function seacharr_by_value($array, $index, $value)
    {
        $newarray = [];
        if(is_array($array) && count($array)>0) {
            foreach(array_keys($array) as $key){
                $temp[$key] = $array[$key][$index];
                if ($temp[$key] == $value){
                    $newarray[$key] = $array[$key];
                }
            }
        }
        return $newarray;
    }
    //手机验证码登录（单机构2.5）
    public static function loginPhoneCaptchaV2($params = [], $checkCode = true)
    {
        extract($params);
        $nickname = isset($nickname) && !empty($nickname) ? $nickname : '设置昵称';
        $channelInfo = Channel::getChannelAppClass($channel);
        if ($checkCode) {
            Captcha::checkCaptcha(['phone' => $phone, 'type' => 1], $captcha);
        }
        $violatingWords = ViolatingWords::column('words');
        if(in_array($nickname,$violatingWords)){
            new ExceptionStd('昵称不规范');
        }
        $userInfo = self::where('phone', $phone)->where('status', 1)->where('channel_id', $channelInfo['channel_id'])->find();
        if (!empty($userInfo)) {
            $userInfo->login_time = date("Y-m-d H:i:s");
            $userInfo->login_ip = request()->ip();
            $userInfo->app_version = $app_version ?? '';
            $userInfo->save();
            $token = getJwtToken($userInfo->id);
            $userInfo = self::getUserInfo(['uid' => $userInfo->id]);
            $userInfo['token'] = $token;
            return $userInfo;
        } else {
            $cityInfo = IpCity::getIpToCity();
            Db::startTrans();
            try {
                $is_switch = 0;
                $is_test = 0;
                $merchant = Merchant::where('is_switch', 1)->where('is_source', 2)->where('app_class_id',$channelInfo['app_class_id'])->count();
                if ($merchant > 0) {
                    $is_switch = 1;
                }
                if (strpos($nickname, '测试') !== false || substr($phone,0,2) === '11') {
                    $is_test = 1;
                }
                //$oldUser = self::where('phone', $phone)->find();
                $isSearchPlan = 0;
                $oaid_two = $oaid ?? '';
                $oaid = $oaid ?? '';
                if (!empty($oaid_two) && strpos($channel, 'vivo') !== false)
                    $oaid_two = md5($oaid_two);
                $user = self::create([
                    'phone' => $phone,
                    'nickname' => $nickname,
                    'login_time' => date("Y-m-d H:i:s"),
                    'login_ip' => request()->ip(),
                    'channel' => $channel,
                    'store' => $channelInfo['store'],
                    'app_bundle_id' => $app_bundle_id,
                    'oaid' => $oaid ?? '',
                    'md5_oaid' => isset($oaid) && !empty($oaid) ? md5($oaid) : '',
                    'idfa' => $idfa ?? '',
                    'channel_id' => $channelInfo['channel_id'],
                    'app_id' => $channelInfo['app_id'],
                    'app_class_id' => $channelInfo['app_class_id'],
                    'is_switch' => $is_switch,
                    'province' => $cityInfo['province_name'] ?? '',
                    'city' => $cityInfo['city_name'] ?? '',
                    'is_test' => $is_test ?? 0,
                    'is_search_plan' => self::userSearchType($oaid,$channel,$app_bundle_id),
                    'oaid_two' => $oaid_two,
                    'phone_end_number' => substr($phone, -4),
                    'age_range_id' => self::checkChannelAge($channel),  // 逾期版本默认年龄段 chenlele 22.09.21
                    'app_version' => $app_version ?? '',
                    'machine_type' => $machine_type ?? '',
                    'imei' => $imei ?? '',
                ]);

                UserProfile::create([
                    'uid' => $user->id,
                ]);
                Db::commit();
                $token = getJwtToken($user->id);
                $userInfo = self::getUserInfo(['uid' => $user->id]);
                $userInfo['token'] = $token;

                if (empty($oaid)) {
                    $oaid = AdvertiserCallbackRecord::where('channel_name', $channel)->where('app_bundle_id', $app_bundle_id)->where('cvType', 'active')->order('create_time', 'desc')->value('oaid');
                }
                $callBackData = [
                    'user' => ['channel' => $channel, 'oaid' => $oaid, 'app_bundle_id' => $app_bundle_id],
                    'dataType' => 'register',
                ];
                event('UserCallbackRecord', $callBackData);//广告主回传
                self::checkChannelAge($channel);
                if (strpos($channel, "huawei") !== false && $channelInfo['app_class_id'] == 9 && $user->is_test == 0) {
                    $courseIdData = [167,269,438,487];
                    $courseId = $courseIdData[array_rand($courseIdData)];
                    \app\model\api\Thread::registerApplyCourse(['course_id' => $courseId], $user->id);
                }
                return $userInfo;
            } catch (\Exception $e) {
                echo $e->getMessage();
                echo $e->getLine();
                Db::rollback();
                new ExceptionStd('登陆失败');
            }
        }
    }

    //收集信息1
    public static function getGatherInfoList($user, $userInfoObj)
    {
        $formatgGatherUser = self::formatgGatherUser();
        $gatherUserInfoList = []; //收集页信息列表
        $i = 0;
        $gatherUserInfoIds = Channel::getFieldByChannelName($user['channel'], 'gather_user_info_ids');
        if (!empty($gatherUserInfoIds)) {
            $gatherUserInfoData = json_decode($gatherUserInfoIds, true);
            $gatherInfoArrIds = array_column($gatherUserInfoData, 'pid');
            if(in_array(1,$gatherInfoArrIds) && in_array(3,$gatherInfoArrIds)){
                $gatherInfoArrIds[array_search("1",$gatherInfoArrIds)] = "3";
                $gatherInfoArrIds[array_search("3",$gatherInfoArrIds)] = "1";
            }
            $gatherUserInfoList = GatherUserInfo::field('id,field,title,gather_info_json')->whereIn('id', $gatherInfoArrIds)->select()->toArray();
            $gatherUserInfoList = self::getMysqlDataInSort($gatherInfoArrIds, $gatherUserInfoList, $gatherUserInfoData);
            foreach ($gatherUserInfoList as &$value) {
                $field = $value['field'];
                if ((isset($user[$field]) && $user[$field] > 0) || (isset($userInfoObj->profile->$field) && $userInfoObj->profile->$field > 0)) {
                    $i++;
                }
                $selectedId = isset($user[$field]) && $user[$field] > 0 ? $user[$field] : (isset($userInfoObj->profile->$field) && $userInfoObj->profile->$field > 0 ? $userInfoObj->profile->$field : 0);
                //  $value['sort'] = (int)$value['sort'];
                $value['selected_id'] = $selectedId;
                $value['selected_text'] = isset($formatgGatherUser[$field][$selectedId]) ? $formatgGatherUser[$field][$selectedId] : '';
            }
        }
        return [
            'gather_info_list' => $gatherUserInfoList,
            'is_perfection_info' => $i >= count($gatherUserInfoList) ? 0 : 1,
        ];
    }

    //解决mysql in排序问题
    public static function getMysqlDataInSort($inData = [], $data = [], $gatherUserInfoData = [])
    {
        $list = [];
        if (!empty($inData) && !empty($data) && !empty($gatherUserInfoData)) {
            $gatherUserInfoData = array_column($gatherUserInfoData, null, 'pid');
            $tempArr = array_column($data, null, 'id');
            foreach ($inData as $val) {
                if (isset($tempArr[$val]) && !empty($tempArr[$val]) && isset($gatherUserInfoData[$val]) && !empty($gatherUserInfoData[$val])) {
                    $cidStr = $gatherUserInfoData[$val]['cid'];
                    if (!empty($cidStr)) {
                        $cidArr = explode(',', $cidStr);
                        $gatherInfoArrList = json_decode($tempArr[$val]['gather_info_json'], true);
                        foreach ($gatherInfoArrList as &$value) {
                            $value['sort'] = (int)$value['sort'];
                        }
                        $gatherInfoArrList = array_column($gatherInfoArrList, null, 'id');
                        $tempGatherData = [];
                        foreach ($cidArr as $v) {
                            $tempGatherData[] = $gatherInfoArrList[$v];
                        }
                        $key = array_column(array_values($tempGatherData), 'sort');
                        array_multisort($key, SORT_DESC, $tempGatherData);
                        $tempArr[$val]['gather_info_json'] = $tempGatherData;
                    }
                    $list[] = $tempArr[$val];
                }
            }
        }
        return $list;
    }

    //获取用户信息
    public static function getUserInfo($params = [])
    {
        extract($params);
        $userInfoObj = self::find(isset($GLOBALS['uid']) ? $GLOBALS['uid'] : $uid);
        if (empty($userInfoObj)) {
            new ExceptionStd('用户不存在');
        }
        $userInfo = $userInfoObj->toArray();
        $user = array_intersect_key($userInfo, array_flip(self::ALOWFIELDS));
        $channelInfo = Channel::where('channel_name', $userInfo['channel'])->field('retention_page_desc,user_material_btn_desc,is_vest_bag,capital_page_position,app_version,is_wx_auth,auth_wx_version')->find();
        $channelConfig = ChannelConfig::where('channel_id', $userInfo['channel_id'])->find();
        $userMaterialBtnDesc = $channelInfo['user_material_btn_desc'];
        $isVestBag =  self::checkIsVestBag($channelInfo, $userInfo);
        $user['avatar'] = isset($userInfo['avatar']) && !empty($userInfo['avatar']) ? $userInfo['avatar'] : (isset($channelConfig['chat_avatar']) && !empty($channelConfig['chat_avatar']) ? $channelConfig['chat_avatar'] : 'http://cdnwm.yuluojishu.com/20230908/25100e498c098060da5db59de9dbacbc.jpg');
        $user['credit_num'] = $userInfo['credit_num'] ?? 0;
        $user['is_wx_auth'] = $isVestBag ? 0 : self::checkIsWxAuth($channelInfo,$userInfo);
        $user['is_audit'] = 0;
        $user['user_material_btn_desc'] = $userMaterialBtnDesc;
        if (!empty($channelInfo) && !empty($channelInfo['retention_page_desc'])) {
            $user['retention_page_desc'] = json_decode($channelInfo['retention_page_desc']);
        } else {
            $user['retention_page_desc'] = [
                '花七秒钟让我们了解你',
                '以便帮你推荐合适的教学老师'
            ];
        }
        $user['capital_page_position'] = isset($channelInfo['capital_page_position']) ? $channelInfo['capital_page_position'] : Channel::CAPITAL_PAGE_POSITION_LOGIN;
        $user['is_vest_bag'] = $isVestBag;
        $user['is_phone_captcha'] = !empty($userInfo['phone']) ? 0 : 1;
        $data = array_merge($user, self::getGatherInfoList($userInfo, $userInfoObj));
        $data['is_perfection_info'] = $isVestBag ? 0 : $data['is_perfection_info'];

        $checkApply = Thread::where('uid', $userInfoObj['id'])->find();
        $isJumpMiniprogram = CourseJumpWx::getCourseJumpWxStatus($checkApply->course_id ?? 0, null, $userInfoObj['id']);
        $applyInfo = [
            'is_apply' => !empty($checkApply) ? 1 : 0,
            'is_jump_miniprogram' => $isJumpMiniprogram,
            'course_id' => $checkApply ? $checkApply->course_id : 0,
            'btn_desc' => !empty($checkApply) ? ($isJumpMiniprogram == 1 ? '查看微信' : '在线聊天') : '',
            'customer_link' => !empty($checkApply)  ? Thread::getCustomerLink(['thread_id' => $checkApply->id], isset($GLOBALS['uid']) ? $GLOBALS['uid'] : $uid)['customer_link'] : ''
        ];
        $data['applyInfo'] = $applyInfo;
        $data['calculator_url'] = 'https://fangdai.114city.cn/people.html';
        $data['overdue_credit_img'] = 'http://cdnwm.yuluojishu.com/uploads/20240307/837812c1289f5f1c23cbb46aba698135.png';
        $isInvite = InviteThreadUser::where('sup_user_id', $GLOBALS['uid'] ?? $uid)->count();
        $data['is_invite'] = $isInvite ? 1 : 0;
        //背景图I
        $data['back_img'] = 'http://cdnwm.yuluojishu.com/uploads/20231116/4d61094a5ff67bd60a0ee8d01c32eba9.png';
        $data['business_introduction'] = '高效专业的债务逾期处理平台，1v1法务在线沟通，24小时快速出解决方案专业处理网贷，信用卡逾期为什么选择我们: 专注处理债务逾期十年。规避逾期风险减免罚息，减少还款压力，高效协商，7天快速高效办理可帮您债务延期，债务减免只还本金，减免利息罚息，停催免诉。帮助上万负债人成功上岸!';
        $data['yq_nums_data'] = [
            "consult_nums"  => 15322,
            "need_nums" => 6320,
            "ashore_nums" => 5530
        ];
        return $data;
    }

    //编辑用户信息
    public static function editUserInfo($params = [])
    {
        Db::startTrans();
        try {
            extract($params);
            $user = self::find($GLOBALS['uid']);
            if (empty($user)) {
                new ExceptionStd('用户不存在');
            }
            if(isset($nickname) && !empty($nickname)){
                $violatingWords = ViolatingWords::column('words');
                if(in_array($nickname,$violatingWords)){
                    new ExceptionStd('昵称不规范');
                }
            }
            $userColumnList = UserProfile::getColumnList();
            $userProfileData = [];
            foreach ($params as $t => $p) {
                if (in_array($t, $userColumnList)) {
                    $userProfileData[$t] = $p;
                }
            }
            $customFieldsStr = '';
            if (!empty($user->profile) && !empty($userProfileData)) {
                $user->profile->save($userProfileData);
                $gatherUserInfoList = GatherUserInfo::field('id,field')->select()->toArray();
                $gatherUserInfoData = array_column($gatherUserInfoList, 'id', 'field');
                foreach ($userColumnList as $val) {
                    if (isset($user->profile->$val) && $user->profile->$val > 0) {
                        if (isset($gatherUserInfoData[$val])) {
                            $customFieldsStr .= $gatherUserInfoData[$val] . '=' . $user->profile->$val . ',';
                        }
                    }
                }
            }

            //$params['custom_fields'] = !empty($customFieldsStr) ? rtrim($customFieldsStr, ',') : $customFieldsStr;
            $params['custom_fields'] = !empty($customFieldsStr) ? rtrim($customFieldsStr, ',') : $user->custom_fields;
            $user->save($params);
            Db::commit();
            return self::getUserInfo();
        } catch (\Exception $e) {
            Db::rollback();
            new ExceptionStd('信息修改失败');
        }
    }

    //组装收集信息数据
    public static function formatgGatherUser()
    {
        $gatherUserInfoList = GatherUserInfo::field('id,field,title,gather_info_json')->select()->toArray();
        if (!empty($gatherUserInfoList)) {
            $gatherUserInfoData = array_column($gatherUserInfoList, 'gather_info_json', 'field');
            foreach($gatherUserInfoData as $key => $val) {
                $data = json_decode($val,true);
                $gatherUserInfoData[$key] = !empty($data) ? array_column($data,'name','id') : [];
            }
            return $gatherUserInfoData;
        }
    }
    public function profile()
    {
        return $this->hasOne('app\model\api\v2\UserProfile', 'uid');
    }

    //搜索分发用户
    public static function userSearchType($oaid = null,$channel = null,$app_bundle_id = null)
    {
        $isSearchPlan = 0;
        $receiveOaid = $oaid ?? '';
        if(!empty($receiveOaid)){
            if(strpos($channel, 'vivo') !== false) $receiveOaid = md5($receiveOaid);
            $receiveData = TodayReceiveMonitorData::where('oaid', $receiveOaid)
                ->where('channel', $channel)
                ->where('app_bundle_id', $app_bundle_id)
                ->order('id desc')
                ->field('id,adid')
                ->find();
            if(!empty($receiveData)){
                if(strpos($channel, 'vivo') !== false){
                    $vivoSsPlanDetail = TodayVivoPlanDetailData::where('ad_id',$receiveData->adid)->field('consume_type')->find();
                    if(!empty($vivoSsPlanDetail)){
                        $isSearchPlan = $vivoSsPlanDetail['consume_type'];
                    }
                }
                if(strpos($channel, 'oppo') !== false){
                    $oppoSsPlanDetail = TodayPlanDetailData::where('ad_id',$receiveData->adid)->field('consume_type')->find();
                    if(!empty($oppoSsPlanDetail)){
                        $isSearchPlan = $oppoSsPlanDetail['consume_type'];
                    }
                }
            }
        }
        return $isSearchPlan;
    }

    //逾期个人信息声明
    public static function overduePersonalInfoStatement($params = [])
    {
        $uid = $GLOBALS['uid'] ?? 0;
        $userInfo = UserList::find($uid);
        if (isset($params['source']) && $params['source'] == 'h5') {
            $channelId = $params['channel'];
            $companyName = "浙江臻尚律师事务所";
            $merchantId = 0;
        } elseif ($params['channel'] == 'zy_gdtapp_yqmfzx_az') {
            $channelId = 206;
            $companyName = '河南焕知律师事务所';
            $merchantId = 0;
        } else {
            $channel = $params['channel'];
            $channelInfo = Channel::getChannelAppClass($channel);
            $channelId = $channelInfo['channel_id'] ?? 0;

            if ($userInfo['app_id'] == 32) {
                $merchantId = 298;
            } else {
                $merchantInfo = curlPost('http://szmmcrm.yuluojishu.com/admin.api.external_thread_yl/getCustomerInfo',[
                    'money_range' => $params['money_range'] ?? '',
                    'phone' => $userInfo['phone'],
                    'is_test' => 0,
                    'is_appstore' => 1,
                    'is_media' => 0,
                    'is_reduce_thread_num' => 0
                ]);
                $merchantId = json_decode($merchantInfo,true)['data']['merchant_id'];
            }

            $companyName = Merchant::where('id', $merchantId)->value('company_name');
        }

        $personInfoStatement = PersonalTransferAgreement::whereFindInSet('channel_ids',$channelId)->order('create_time', 'desc')->field('content')->find();
        $personInfoStatement = str_replace("《动态获取公司名称》", $companyName, $personInfoStatement->toArray()['content']);
        return ['companyName' => $companyName,'personInfoStatement' => $personInfoStatement, 'merchantId' => $merchantId];
    }
}
