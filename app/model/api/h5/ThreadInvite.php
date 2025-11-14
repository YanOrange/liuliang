<?php

namespace app\model\api\h5;

use app\lib\api\advertiser\H5OppoAdvertiser;
use app\lib\api\advertiser\H5VivoAdvertiser;
use app\lib\api\advertiser\H5DouyinAdvertiser;
use app\lib\api\city\IpCity;
use app\lib\api\exception\Exception;
use app\lib\api\exception\ExceptionStd;
use app\lib\api\service\CustomerService;
use app\model\api\Channel;
use app\model\api\Customer;
use app\model\api\CustomerQrcodeLog;
use app\model\api\Merchant;
use app\model\api\v2\GatherUserInfo as GatherUserInfoModel;
use app\model\api\v2\UserProfile;
use laytp\BaseModel;
use think\facade\Event;
use think\model\concern\SoftDelete;
use app\lib\api\wxmini\WxMiniSqrcode;
use app\model\api\Course;
use app\model\admin\ThreadExternal;
use app\model\api\Thread;
use app\model\api\InviteThreadUser;

class ThreadInvite extends BaseModel
{
    use SoftDelete;
    //表名
    protected $name = 'thread';

    //留资项
    public static function getGatherInfoData($token)
    {
        $uid = checkJwtToken($token);
        $gatherInfoData = [];
        $userInfo = UserList::where('id',$uid)->field('id,phone,channel_id')->find();
        if(!empty($userInfo)){
            $gatherUserInfoIds = Channel::getFieldById($userInfo->channel_id,'gather_user_info_ids');
            if(!empty($gatherUserInfoIds)){
                $gatherInfoData = ForFlow::getGatherInfoList($gatherUserInfoIds);
            }
        }
        return $gatherInfoData;
    }

    //是否已报名
    public static function checkApplyForFlow($phone)
    {
        $checkApplyCourse = 0;
        $uid = UserList::where('phone', $phone)->value('id');
        if(!empty($uid)){
            $courseModel =  new \app\model\api\Thread();
            $name = $courseModel->getName();
            $tableName = env('database.prefix') . $name;
            $checkApplyCourse  = (new self)->whereExists(function ($query) use ($tableName) {
                $merchantTableName = (new \app\model\api\Merchant())->getName();
                $query = $query->table(env('database.prefix') . $merchantTableName)->where(env('database.prefix') . $merchantTableName . '.id=' .   $tableName . '.merchant_id');
                $query->where('is_source', 2);
                return $query;
            })
                ->where('uid', $uid)
                ->count();
        }
        return $checkApplyCourse ? 1 : 0;
    }

    //获取已报名课程客服二维码
    public static function getApplyQrCode($params = [])
    {
        extract($params);
        $qrcode_image = '';
        $merchant = [];
        if($phone == 'cf55555555'){
            $customer['qrcode_image'] = Customer::where('id',$for_flow_id)->value('qr_code');
            return $customer;
        }
        if(mb_strlen($phone) > 11){
            $userInfo = UserList::where('h5_uid', $phone)->where('flow_id', '>',0)->order('id desc')->field('id,h5_uid')->find();
        }else{
            $userInfo = UserList::where('phone', $phone)->where('flow_id', '>',0)->order('id desc')->field('id,h5_uid')->find();
        }
        $uid = $userInfo->id;
        $h5_uid = $userInfo->h5_uid;
        $appClassId = ForFlow::where('id',$for_flow_id)->value('app_class_id');
        if($appClassId == 9) {
            $name = strtolower((new self())->getName());
            $tableName = env('database.prefix') . $name;
            $outMerchantCustomer = self::whereExists(function ($query) use ($tableName) {
                $merchantTableName = (new \app\model\api\Merchant())->getName();
                $query = $query->table(env('database.prefix') . $merchantTableName)->where(env('database.prefix') . $merchantTableName . '.id=' . $tableName . '.merchant_id');
                $query->where('is_source', 2);
                return $query;
            })->where('uid', $uid)->where('flow_id', '>', 0)->count();
            $customer = self::whereExists(function ($query) use ($tableName, $outMerchantCustomer) {
                $merchantTableName = (new \app\model\api\Merchant())->getName();
                $query = $query->table(env('database.prefix') . $merchantTableName)->where(env('database.prefix') . $merchantTableName . '.id=' . $tableName . '.merchant_id');
                $query->where('is_source', $outMerchantCustomer ? 2 : 1);
                return $query;
            })->where('uid', $uid)->where('flow_id', '>', 0)->order('id desc')->field('customer_id,merchant_id')->find();
            if(empty($customer) && !empty($h5_uid)){
                $uids = UserList::where('h5_uid',$h5_uid)->where('flow_id','>',0)->order('id desc')->limit(5)->column('id');
                $customer = self::whereExists(function ($query) use ($tableName, $outMerchantCustomer) {
                    $merchantTableName = (new \app\model\api\Merchant())->getName();
                    $query = $query->table(env('database.prefix') . $merchantTableName)->where(env('database.prefix') . $merchantTableName . '.id=' . $tableName . '.merchant_id');
                    $query->where('is_source', 2);
                    return $query;
                })->whereIn('uid', $uids)->where('flow_id', '>', 0)->order('id desc')->field('customer_id,merchant_id')->find();
            }
        }else{
            $customer = self::where('uid', $uid)->where('flow_id', '>',0)->order('id desc')->field('customer_id,merchant_id')->find();
        }
        if (!empty($customer['customer_id'])) {
            $qrcode_image = Customer::where('id', $customer['customer_id'])->value('qr_code');
        }
        if (!empty($customer['merchant_id'])) {
            $merchant = Merchant::where('id', $customer['merchant_id'])->field('customer_qrcode_explain,customer_explain_status')->find();
        }
        $data['qrcode_explain'] = isset($merchant['customer_qrcode_explain']) && !empty($merchant['customer_qrcode_explain']) ? json_decode($merchant['customer_qrcode_explain']) : [];
        $data['explain_status'] = $merchant['customer_explain_status'] ?? 0;
        $data['qrcode_image'] = !empty($qrcode_image) ? (strpos($qrcode_image, 'https') !== false ? $qrcode_image : str_replace('http', 'https', $qrcode_image)).'?x-oss-process=image/resize,m_fill,w_200,quality,q_60' : '';
        $data['top_process_desc'] = '找对律师，快速处理';
        $data['process_desc'] = ['延后还款', '减免息费', '专业诉调', '维护权益', '马上回款', '专业顾问'];
        $data['warm_reminder'] = '金牌律师1对1解决您的问题';
        $data['app_name'] = '';
        if(empty($qrcode_image)){
            CustomerQrcodeLog::create([
                'content' => json_encode(['uid' => $uid,'phone' => $phone,'for_flow_id' => $for_flow_id]),
            ]);
        }
        self::where('uid',$uid)->where('flow_id',$for_flow_id)->update(['is_enter_miniprogram_page' => 1]);
        return $data;
    }

    //长按识别二维码
    public static function discernQrCode($params = [])
    {
        extract($params);
        if(mb_strlen($phone) > 11){
            $userInfo = UserList::where('h5_uid', $phone)->where('flow_id', '>',0)->field('id,channel')->order('id desc')->find();
        }else{
            $userInfo = UserList::where('phone', $phone)->where('flow_id', '>',0)->field('id,channel')->order('id desc')->find();
        }
        $forFlowInfo = ForFlow::where('id',$for_flow_id)->field('id,is_need_phone')->find();
        if(!empty($userInfo)) {
            $uid = $userInfo->id;
            $channel = $userInfo->channel;
            $threadInfo = self::where('uid', $uid)->where('flow_id', $for_flow_id)->where('is_discern_qrcode',0)->order('id desc')->field('id,merchant_id,customer_id,thread_price')->find();
            if(!empty($threadInfo)) {
                $threadId = $threadInfo->id;
                $merchantInfo = Merchant::where('id', $threadInfo->merchant_id)->find();
                self::where('id', $threadId)->update(['is_discern_qrcode' => 1, 'is_real_qrcode' => 1]);
                $threadExternalId = ThreadExternal::where('wm_uid', $uid)->where('flow_id', $for_flow_id)->order('id desc')->value('id');
                ThreadExternal::where('id', $threadExternalId)->update(['is_discern_qrcode' => 1, 'is_real_qrcode' => 1]);
                if (in_array($for_flow_id, [48,55,60,61,72,145,146])) {
                    $H5OppoAdvertiser = new H5OppoAdvertiser();
                    $H5OppoAdvertiser->h5OppoAdvertiserQrcodeCollback($for_flow_id);
                }
                if ($channel == 'jdh5_douyin1' || $channel == 'jdh5_douyin2' || $channel == 'jdh5_douyin5') {
                    $H5DouyinAdvertiser = new H5DouyinAdvertiser();
                    $H5DouyinAdvertiser->h5DouyinAdvertiserQrcodeCollback($for_flow_id,$phone);
                }
//            if ($channel == 'yqh5_vivo2') {
//                $H5VivoAdvertiser = new H5VivoAdvertiser();
//                $H5VivoAdvertiser->h5VivoAdvertiseQrcodeCollback($for_flow_id);
//            }
                if ($forFlowInfo->is_need_phone == 0 && ($merchantInfo->id == 142 || $merchantInfo->id == 195) && $userInfo->is_test == 0) {
                    $threadPriceInfo['thread_price'] = $threadInfo->thread_price;
                    $redis = get_redis();
                    $redisKey = env('redis.merchant_amount_redis_v2_key') . $merchantInfo->id;
                    if (!$redis->exists($redisKey)) {
                        $redis->set($redisKey, floatToInt($merchantInfo->residue_amount));
                    }
                    $redis->watch($redisKey);
                    $merchantStore = $redis->get($redisKey);
                    $threadPrice = floatToInt($threadPriceInfo['thread_price']);
                    if ($merchantStore > $threadPrice) {
                        $redis->multi();
                        $redis->decrBy($redisKey, $threadPrice);
                        $result = $redis->exec();
                        if ($result) {
                            $ret = Thread::where('id', $threadId)->update(['is_test' => 0]);
                            if ($ret) {
                                Event::trigger('ApplySuccessAfter', ['merchant' => $merchantInfo, 'thread' => ['uid' => $userInfo->id, 'customerId' => $threadInfo->customer_id, 'thread_price' => $threadPriceInfo['thread_price']]]);
                                event('ApplyThreadSuccessAfter', ['threadId' => $threadId]);
                                if (strstr($channel, 'vivo') !== false) {
                                    $H5VivoAdvertiser = new H5VivoAdvertiser();
                                    $H5VivoAdvertiser->h5VivoAdvertiseQrcodeCollback($for_flow_id);
                                }
                                if (strstr($channel, 'oppo') !== false) {
                                    $H5OppoAdvertiser = new H5OppoAdvertiser();
                                    $H5OppoAdvertiser->h5OppoAdvertiserQrcodeCollback($for_flow_id);
                                }
                            } else {
                                $redis->incrBy($redisKey, $threadPrice);
                            }
                        } else {
                            $redis->incrBy($redisKey, $threadPrice);
                        }
                    }
                }
            }
        }
    }

    //免费报名
    public static function freeApplyInvite($params = [],$token = '')
    {
        extract($params);
        try {
            $userInfo = self::createUserList($params,$token);
            $threadId = Thread::where('uid',$userInfo->id)->value('id');
            $channelInfo = Channel::with(['app' => function($query){
                $query->field('id,app_class_id');
            }])
                ->where('id',$userInfo->channel_id)
                ->find();
            $debtMoneyRange = self::getDebtMoneyRange($userInfo->id,$channelInfo['app']['app_class_id']);
            $merchantCustomerId = self::getMerchantServiceId(0,$debtMoneyRange['money_range'],$userInfo);
            $merchantId = $merchantCustomerId['merchant_id'];
            $customerId = $merchantCustomerId['customer_id'];
            $merchantInfo = Merchant::find($merchantId);
            if (self::checkApplyForFlow($userInfo->phone)) {
                //$customerLinkInfo = Thread::getCustomerLink(['thread_id' => $threadId]);
                return ['is_apply' => 1, 'is_jump_miniprogram' => $merchantInfo->is_jump_miniprogram ?? 1, 'customer_link' => '1234'];
            }
            if (empty($merchantInfo) || $merchantInfo->is_switch == 0) {
                new Exception('该名额已结束');
            }

            $courseId = Course::where('merchant_id',$merchantId)->value('id');
            if(empty($courseId)){
                $courseId = 0;
            }
            if(!$customerId) {
                $customerId = (new CustomerService)->getCustomerServiceId($merchantId, $userInfo->id);
            }

            $gatherInfo = GatherUserInfoModel::getFormatGatherInfo($userInfo->age_range_id, 'age_range_id');
            $ageRange = $gatherInfo['name'];

            $cityInfo = IpCity::getIpToCity();
            $threadPriceInfo = Merchant::getMerchantThreadPrice($merchantInfo, $channelInfo);
            $redis = get_redis();
            $redisKey = env('redis.merchant_amount_redis_v2_key'). $merchantInfo->id;
            if (!$redis->exists($redisKey)) {
                $redis->set($redisKey, floatToInt($merchantInfo->residue_amount));
            }
            $redis->watch($redisKey);
            $merchantStore = $redis->get($redisKey);
            $threadPrice = $userInfo->is_test == 1 ? 0 : floatToInt($threadPriceInfo['thread_price']);
            if ($merchantStore < $threadPrice) {
                new Exception('该名额已结束');
            }
            $redis->multi();
            $redis->decrBy($redisKey, $threadPrice);
            $result = $redis->exec();

            if($result) {
                $ret = self::create([
                    'uid' => $userInfo->id,
                    'course_id' => $courseId,
                    'merchant_id' => $merchantId,
                    'customer_id' => $customerId,
                    'province' => $cityInfo['province_name'] ?? '',
                    'city' => $cityInfo['city_name'] ?? '',
                    'age' => $ageRange,
                    'channel' => $channelInfo['channel_name'],
                    'channel_id' => $channelInfo['id'],
                    'app_id' => $channelInfo['app']['id'],
                    'app_class_id' => $channelInfo['app']['app_class_id'],
                    'thread_price' => $threadPriceInfo['thread_price'],
                    'thread_price_type' => $threadPriceInfo['thread_price_type'],
                    'thread_price_origin' => $threadPriceInfo['thread_price'],
                    'thread_type' => isset($merchantInfo->is_jump_miniprogram) ? ($merchantInfo->is_jump_miniprogram > 0 ? 3 : 1) : 0,
                    'source' => $channelInfo['source_id'],
                    'source_id' => 0,
                    'is_free_try' => $merchantInfo->is_free_try,
                    'is_test' => $userInfo->is_test,
                    'age_id' => $userInfo->age_range_id,
                    'merchant_admin_id' => $merchantInfo->admin_ids,
                    'is_origin' => 1,
                    'cost_price'=>$channelInfo['cost_price'],
                    'debt_range' => $debtMoneyRange['debt_range'],
                    'money_range' => $debtMoneyRange['money_range'],
                    'overdue_time' => $debtMoneyRange['overdue_time'],
                    'adid' => $adid ?? '',
                    'h5_apply_phone_system' => getPhoneSystem()
                ]);
                if($ret) {
                    if($userInfo->is_test == 0){
                        Event::trigger('ApplySuccessAfter', ['merchant' => $merchantInfo, 'thread' => ['uid' => $userInfo->id, 'customerId' => $customerId, 'thread_price' => $threadPriceInfo['thread_price']]]);
                    }
                    if(($merchantInfo->is_filiale == 1) && $userInfo->is_test == 0){
                        event('ApplyThreadSuccessAfter', ['threadId' => $ret->id]);
                    }
                    $customerLinkInfo = Thread::getCustomerLink(['thread_id' => $ret->id],$userInfo->id);
                    return ['is_apply' => 0,'is_jump_miniprogram' => $merchantInfo->is_jump_miniprogram, 'customer_link' => $customerLinkInfo['customer_link'] ?? ''];
                }
                $redis->incrBy($redisKey, $threadPrice);
                new Exception('该名额已结束');
            }
            new Exception('该名额已结束');
        } catch (\Exception $e) {
            new Exception($e->getMessage().'---'.$e->getLine().'---'.$e->getFile());
        }
    }

    //获取商户
    public static function getMerchantServiceId($isMedia = 0, $moneyRange = '', $userInfo = null)
    {
        $merchantId = 0;
        $customerId = 0;
        $merchantCustomerInfo = json_decode(curlPost(env('szmzwz.customer_id_url'), ['is_media' => 0, 'is_appstore' => 1, 'money_range' => $moneyRange, 'phone' => $userInfo->phone, 'merchant_id' => 0, 'is_test' => $userInfo->is_test ?? 0, 'is_appoint_merchant' => 0, 'is_reduce_thread_num' => 1]), true);
        if (!empty($merchantCustomerInfo) && $merchantCustomerInfo['code'] == 200) {
            $merchantId = $merchantCustomerInfo['data']['merchant_id'] ?? 0;
            $customerId = $merchantCustomerInfo['data']['id'] ?? 0;
        }
        return ['merchant_id' => $merchantId,'customer_id' => $customerId];
    }

    //添加注册用户信息
    public static function createUserList($params = [],$token)
    {
        extract($params);
        $inviteUid = checkJwtToken($token);
        $userInfo = UserList::where('phone',$phone)->find();
        $inviteUserInfo = UserList::where('id',$inviteUid)->find();
        $channelInfo = Channel::with(['app' => function($query){
            $query->field('id,app_class_id');
        }])
            ->where('id',$inviteUserInfo->channel_id)
            ->field('id,app_id,channel_name,gather_user_info_ids')
            ->find();
        if(!empty($userInfo)){
            return $userInfo;
        }else {
            try {
                $is_switch = 0;
                $merchant = Merchant::where('is_switch', 1)->where('is_source', 2)->count();
                if ($merchant > 0) {
                    $is_switch = 1;
                }
                $is_test = 0;
                if (substr($phone,0,2) === '11' || in_array($phone,['13777571709','18996893283'])) {
                    $is_test = 1;
                }
                //收集信息
                $gatherInfoData = [];
                $gatherInfoDataStrData = [];
                $gatherInfoDataStr = '';
                $gatherInfoSetData = ForFlow::getGatherInfoList($channelInfo['gather_user_info_ids']);
                if(!empty($gatherInfoSetData)){
                    foreach ($gatherInfoSetData as $key => $val){
                        $field = $val['field'];
                        if($field != 'age_range_id' && $field != 'identity_id' && $field != 'education_id' && $field != 'sex'){
                            if(isset($field)){
                                $gatherInfoData[$field] = $field;
                                $gatherInfoDataStrData[] = $val['id'].'='.$params[$field];
                            }
                        }
                    }
                }
                if(!empty($gatherInfoData)){
                    $gatherInfoDataStr = implode(',',$gatherInfoDataStrData);
                }
                $cityInfo = IpCity::getIpToCity();
                //收集信息
                $user = UserList::create([
                    'phone' => $phone,
                    'nickname' => $nickname ?? '',
                    'login_time' => date("Y-m-d H:i:s"),
                    'login_ip' => request()->ip(),
                    'channel' => $channelInfo->channel_name,
                    'channel_id' => $channelInfo->id,
                    'app_id' => $channelInfo->app->id,
                    'app_class_id' => $channelInfo->app->app_class_id,
                    'sex' => isset($sex) ? $sex : 0,
                    'age_range_id' => isset($age_range_id) ? $age_range_id : 2,
                    'identity_id' => isset($identity_id) ? $identity_id : 0,
                    'education_id' => isset($education_id) ? $education_id : 0,
                    'is_has_computer_id' => isset($is_has_computer_id) ? $is_has_computer_id : 0,
                    'study_goal_id' => isset($study_goal_id) ? $study_goal_id : 0,
                    'zhaiwu_leixing' => isset($zhaiwu_leixing) ? $zhaiwu_leixing : 1,
                    'zhaiwu_monney' => isset($zhaiwu_monney) ? $zhaiwu_monney : 1,
                    'yuqi_pingtaiid' => isset($yuqi_pingtaiid) ? $yuqi_pingtaiid : 0,
                    'cuishou_zhuangtai' => isset($cuishou_zhuangtai) ? $cuishou_zhuangtai : 0,
                    'is_switch' => $is_switch,
                    'is_test' => $is_test,
                    'source' => 2,
                    'phone_end_number' => substr($phone,-4),
                    'custom_fields' => $gatherInfoDataStr,
                    'imei' => $imei ?? '',
                    'h5_uid' => $h5_uid ?? '',
                    'province' => $cityInfo['province_name'] ?? '',
                    'city' => $cityInfo['city_name'] ?? ''
                ]);
                InviteThreadUser::create([
                    'sup_user_id' => $inviteUserInfo->id,
                    'sub_user_id' => $user->id
                ]);
                //保存收集信息
                $gatherInfoData['uid'] = $user->id;
                UserProfile::create($gatherInfoData);

                $userInfo = UserList::find($user->id);
                return $userInfo;
            }catch (\Exception $e){
                new ExceptionStd($e->getMessage());
            }
        }

    }

    public static function getDebtMoneyRange($uid = 0,$appClassId = 0)
    {
        $overdueTimeName = '';
        $debtRangeName = $appClassId == 9 ? '信用卡' : '';
        $moneyRangeName = $appClassId == 9 ? '1万以下' : '';
        $userInfo = UserList::where('id',$uid)->field('id,custom_fields')->find();
        $debtRangeList = GatherUserInfoModel::where('id',18)->find();
        $moneyRangeList = GatherUserInfoModel::where('id',19)->find();
        $overdueTimeList = GatherUserInfoModel::where('id',24)->find();
        $debtRangeArr = self::gatherUserInfo($debtRangeList);
        $moneyRangeArr = self::gatherUserInfo($moneyRangeList);
        $overdueTimeArr = self::gatherUserInfo($overdueTimeList);
        $customFields = explode(',',$userInfo['custom_fields']);
        $debtRange = array_values(array_intersect($debtRangeArr['gatherUserArr'],$customFields));
        $moneyRange = array_values(array_intersect($moneyRangeArr['gatherUserArr'],$customFields));
        $overdueTime = array_values(array_intersect($overdueTimeArr['gatherUserArr'],$customFields));
        if(!empty($debtRange) && isset($debtRange[0])){
            $debtRangeIds = explode('=',$debtRange[0]);
            $debtRangeName = isset($debtRangeArr['gatherNameArr'][$debtRangeIds[1]]) ? $debtRangeArr['gatherNameArr'][$debtRangeIds[1]] : '';
        }
        if(!empty($moneyRange) && isset($moneyRange[0])) {
            $moneyRangeIds = explode('=', $moneyRange[0]);
            $moneyRangeName = isset($moneyRangeArr['gatherNameArr'][$moneyRangeIds[1]]) ? $moneyRangeArr['gatherNameArr'][$moneyRangeIds[1]] : '';
        }
        if(!empty($overdueTime) && isset($overdueTime[0])) {
            $overdueTimeIds = explode('=', $overdueTime[0]);
            $overdueTimeName = isset($overdueTimeArr['gatherNameArr'][$overdueTimeIds[1]]) ? $overdueTimeArr['gatherNameArr'][$overdueTimeIds[1]] : '';
        }
        $data['debt_range'] = $debtRangeName;
        $data['money_range'] = $moneyRangeName;
        $data['overdue_time'] = $overdueTimeName;
        return $data;
    }

    public static function gatherUserInfo($gatherUserList)
    {
        $gatherUserArr = [];
        $gatherNameArr = [];
        if(!empty($gatherUserList)) {
            $gatherInfoJson = json_decode($gatherUserList['gather_info_json'],true);
            foreach($gatherInfoJson as $val){
                $gatherUserArr[] = $gatherUserList['id'].'='.$val['id'];
                $gatherNameArr[$val['id']] = $val['name'];
            }
        }
        $data['gatherUserArr'] = $gatherUserArr;
        $data['gatherNameArr'] = $gatherNameArr;
        return $data;
    }

    public function user()
    {
        return $this->belongsTo('app\model\api\h5\UserList','uid','id')->removeOption('soft_delete');
    }

    public static function getRandomStr($length)
    {
        $str = 'abcdefjhighlmnopqrstuvwxyzABCDEFJHIGKLMNOPQRSTUVWXYZ0123456789';
        $len = strlen($str) - 1;
        $randStr = '';
        for($i = 0;$i < $length;$i++){
            $num = mt_rand(0,$len);
            $randStr .= $str[$num];
        }
        return $randStr;
    }

    public static function customerWxLink($params = [])
    {
        extract($params);
        $merchantId = Customer::where('id',$customer_id)->value('merchant_id');
        $wxMiniConfig = (new WxMiniSqrcode())->actionSqrcode('cf55555555', $customer_id, 'h5_customer_wx_'.$merchantId,'h5_customer_wx_'.$merchantId);
        return $wxMiniConfig['openlink_url'];
    }
}