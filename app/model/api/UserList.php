<?php
/**
 * 用户表模型
 */

namespace app\model\api;

use app\lib\api\city\IpCity;
use app\model\admin\NoJumpWechatPhone;
use app\model\api\Channel;
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
use app\model\api\v2\UserProfile;
use app\model\api\v2\GatherUserInfo;
use app\lib\api\other\CourseJumpWx;
use app\model\api\single\SingleCourse;
use app\model\api\Thread;

class UserList extends BaseModel
{
    use SoftDelete;
    //模型名
    protected $name = 'user_list';
//    protected $hidden = [
//        'wxopenid',
//        'app_id',
//    ];
    const ALOWFIELDS = ['phone', 'nickname', 'avatar'];
    const Phone = [
        "13011111111",
        "13022222222",
        "13033333333",
        "13044444444",
        "13055555555",
        "13066666666",
        "13077777777",
        "13088888888",
        "13099999999",
        "13010101010",
        "13001010101"
    ];

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
        $params['phone']  = $phone;
        return self::loginPhoneCaptcha($params, false);
    }
    //绑定微信
    public static function bindWx($params = [])
    {
        $userInfo = self::find($GLOBALS['uid']);
        $wxUserinfo = WxAuth::wxAuthLogin($params);
        if (empty($userInfo)) {
            new ExceptionStd('用户信息不存在');
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

    //手机验证码登录
    public static function loginPhoneCaptcha($params = [], $checkCode = true)
    {
        extract($params);

        //获取是否是测试号码；
        //测试账号
        $testPhoneArr = Config::load("extra/test/userphone", "extra") ?? [];

        $isTestStatus = 0;
        if(in_array($phone,$testPhoneArr)){
            $isTestStatus = 1;
        }

        $oaid = $oaid ?? '';
        $nickname = isset($nickname) && !empty($nickname) ? $nickname : '设置昵称';
        $channelInfo = Channel::getChannelAppClass($channel);
        if ($isTestStatus==0 && $checkCode) {
//            Captcha::checkCaptcha(['phone' => $phone, 'type' => 1], $captcha);
        }
        $userInfo = self::where('phone', $phone)->where('status', 1)->where('channel_id', $channelInfo['channel_id'])->find();
        if (!empty($userInfo)) {
            $userInfo->login_time = date("Y-m-d H:i:s");
            $userInfo->login_ip = request()->ip();
            $userInfo->app_version = $app_version ?? '';
            $userInfo->save();
            $token = getJwtToken($userInfo->id);
            $userInfo = self::getUserInfo(['uid' => $userInfo->id, 'oaid' => $oaid]);
            $userInfo['token'] = $token;
            $userInfo['app_record_number'] = '渝ICP备2024030274号-3A';
//            $userInfo['campaign_id'] = TodayReceiveMonitorData::where('oaid', $oaid)->where('channel', $channelInfo['channel_name'])->field('campaign_id')->find()['campaign_id'];
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
                if ($isTestStatus ==1  || strpos($nickname, '测试') !== false || substr($phone,0,2) === '11' || substr($phone,0,3) === '120' || in_array($phone, self::Phone) ) {
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
                    'machine_type' => $machine_type ?? ''
                ]);

                UserProfile::create([
                    'uid' => $user->id,
                ]);
                Db::commit();
                $token = getJwtToken($user->id);
                $userInfo = self::getUserInfo(['uid' => $user->id,'oaid' => $oaid]);
                $userInfo['token'] = $token;
                $userInfo['app_record_number'] = '渝ICP备2024030274号-3A';
                $oaid = $oaid ?? '';
                if (empty($oaid)) {
                    $oaid = AdvertiserCallbackRecord::where('channel_name', $channel)->where('app_bundle_id', $app_bundle_id)->where('cvType', 'active')->order('create_time', 'desc')->value('oaid');
                }
                $callBackData = [
                    'user' => ['channel' => $channel, 'oaid' => $oaid, 'app_bundle_id' => $app_bundle_id],
                    'dataType' => 'register',
                ];
                event('UserCallbackRecord', $callBackData);//广告主回传
                self::checkChannelAge($channel);
//                $userInfo['campaign_id'] = TodayReceiveMonitorData::where('oaid', $oaid)->where('channel', $channel)->field('campaign_id')->find();
                return $userInfo;
            } catch (\Exception $e) {
                Db::rollback();
                new ExceptionStd($e->getMessage());
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

    //获取用户信息
    public static function getUserInfo($params = [])
    {
        if (!isset($params['oaid'])) {$params['oaid'] = '';}
        extract($params);
        $userInfoObj = self::find(isset($GLOBALS['uid']) ? $GLOBALS['uid'] : $uid);
        if (empty($userInfoObj)) {
            new ExceptionStd('用户不存在');
        }
        $userInfo = $userInfoObj->toArray();
        $user = array_intersect_key($userInfo, array_flip(self::ALOWFIELDS));
        $channelInfo = Channel::where('channel_name', $userInfo['channel'])->field('retention_page_desc,user_material_btn_desc,is_vest_bag,capital_page_position,app_version,is_wx_auth,auth_wx_version')->find();
        $userMaterialBtnDesc = $channelInfo['user_material_btn_desc'];
        $isVestBag =  self::checkIsVestBag($channelInfo, $userInfo);
        $user['avatar'] = isset($userInfo['avatar']) && !empty($userInfo['avatar']) ? $userInfo['avatar'] : "http://cdnwm.yuluojishu.com/uploads/20220507/334dfa7ab34e224a3e70fe2a149dfa05.png";
        $user['is_wx_auth'] = $isVestBag ? 0 : self::checkIsWxAuth($channelInfo,$userInfo);
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
        $data['campaign_id'] = TodayReceiveMonitorData::where('oaid', $oaid)->where('channel', $userInfo['channel'])->field('campaign_id')->find();
        $thread = Thread::where('uid', $userInfo['id'])->where('channel', $userInfo['channel'])->find();
        $data['thread'] = [
            'debt_range' => $thread['debt_range'] ?? '',
            'time' => $thread['create_time'] ?? '',
            'userName' => $userInfo['nickname']
        ];
        $data['birth_date'] = isset($userInfo['birth_date']) && !empty($userInfo['birth_date']) ? $userInfo['birth_date'] : "";
        $data['id'] = isset($userInfo['id']) && !empty($userInfo['id']) ? $userInfo['id'] : 0;
        return $data;
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
            $gatherUserInfoList = GatherUserInfo::field('id,field,title,gather_info_json')->whereIn('id', $gatherInfoArrIds)->select()->toArray();
            $gatherUserInfoList = self::getMysqlDataInSort($gatherInfoArrIds, $gatherUserInfoList, $gatherUserInfoData);
            foreach ($gatherUserInfoList as &$value) {
                $field = $value['field'];
                if ((isset($user[$field]) && $user[$field] > 0) || (isset($userInfoObj->profile->$field) && $userInfoObj->profile->$field > 0)) {
                    $i++;
                }
                $selectedId = isset($user[$field]) && $user[$field] > 0 ? $user[$field] : (isset($userInfoObj->profile->$field) && $userInfoObj->profile->$field > 0 ? $userInfoObj->profile->$field : 0);
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

    //编辑用户信息
    public static function editUserInfo($params = [])
    {
        extract($params);
        $user = self::find($GLOBALS['uid']);
        if (empty($user)) {
            new ExceptionStd('用户不存在');
        }
        if (isset($nickname) && !empty($nickname)) {
            $user->nickname = $nickname;
        }
        if (isset($avatar) && !empty($avatar)) {
            $user->avatar = $avatar;
        }
        if (isset($age_range_id) && !empty($age_range_id)) {
            $user->age_range_id = $age_range_id;
        }
        if (isset($identity_id) && !empty($identity_id)) {
            $user->identity_id = $identity_id;
        }
        if (isset($education_id) && !empty($education_id)) {
            $user->education_id = $education_id;
        }
        if (isset($birth_date) && !empty($birth_date)) {
            $user->birth_date = $birth_date;
        }
        if (isset($is_has_computer_id) && !empty($is_has_computer_id)) {
            $userConfig = Config::load("extra/user", "extra");
            if (in_array($user->channel, $userConfig['sex_channel'])) {
                $user->sex = $is_has_computer_id;
            } elseif (in_array($user->channel, $userConfig['filter_study_goal_channel'])) {
                $user->study_goal_id = $is_has_computer_id;
            } elseif(in_array($user->channel, $userConfig['filter_shop_channel'])) {
                $user->is_has_shop_id = $is_has_computer_id;
            } else {
                $user->is_has_computer_id = $is_has_computer_id;
            }
        }
        if ($user->save() !== false) {
            return self::getUserInfo();
        }
        new ExceptionStd('信息修改失败');
    }

    //更换手机号
    public static function changePhone($params)
    {
        extract($params);
        Captcha::checkCaptcha(['phone' => $phone, 'type' => 3], $captcha);
        $checkPhone = self::where('phone', $phone)->where('status', 1)->count();
        if ($checkPhone) {
            new ExceptionStd("手机号已存在");
        }
        $user  = self::find($GLOBALS['uid']);
        $user->phone = $phone;
        $ret = $user->save();
        if ($ret !== false) {
            return self::getUserInfo();
        }
        new ExceptionStd("更换手机号失败");
    }

    //渠道跳转微信判断
    public static function getChannelJumpWechat($channel)
    {
        $isJumpMiniprogram = 1;
        $channelInfo = Channel::where('channel_name',$channel)->field('id,is_jump_miniprogram,jump_wechat_version')->find();
        $appVersion = UserList::where('id', $GLOBALS['uid'])->value('app_version');
        if (isset($channelInfo['jump_wechat_version']) && !empty($channelInfo['jump_wechat_version']) &&  $channelInfo['jump_wechat_version'] == $appVersion) {
            $isJumpMiniprogram = $channelInfo['is_jump_miniprogram'];
        }
        return $isJumpMiniprogram;
    }

    //注销
    public static function logoutUser($params = [])
    {
        extract($params);
        $user = self::find($GLOBALS['uid']);
        if (empty($user)) {
            new Exception('用户不存在');
        }
        $user->status = 2;
        $ret = $user->save();
    }

    //注销
    public static function codeLogoutUser($params = [])
    {
        extract($params);
        if(!isset($captcha) || empty($captcha)){
            new Exception('验证码不能为空');
        }
        $user = self::find($GLOBALS['uid']);
        if (empty($user)) {
            new Exception('用户不存在');
        }

        //获取是否是测试号码；
        //测试账号
        $testPhoneArr = Config::load("extra/test/userphone", "extra") ?? [];

        if(in_array($user['phone'],$testPhoneArr)){
            $user->delete_time = time();
        }else{
            Captcha::checkCaptcha(['phone' => $user['phone'], 'type' => 4], $captcha);
        }
        $user->status = 2;
        $ret = $user->save();
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
}
