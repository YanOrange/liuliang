<?php

namespace app\model\api\h5;

use app\lib\api\advertiser\H5OppoAdvertiser;
use app\lib\api\advertiser\H5VivoAdvertiser;
use app\lib\api\advertiser\H5DouyinAdvertiser;
use app\lib\api\advertiser\H5WeiboAdvertiser;
use app\lib\api\advertiser\H5GdtAdvertiser;
use app\lib\api\city\IpCity;
use app\lib\api\exception\Exception;
use app\lib\api\exception\ExceptionStd;
use app\lib\api\payapi\Alipay;
use app\lib\api\payapi\Wxpay;
use app\lib\api\service\CustomerService;
use app\lib\api\service\DouyinCustomerService;
use app\lib\api\service\WeightService;
use app\model\api\Captcha;
use app\model\api\Channel;
use app\model\api\CourseOrder;
use app\model\api\Customer;
use app\model\api\CustomerQrcodeLog;
use app\model\api\Merchant;
use app\model\api\h5\UserList;
use app\model\api\ThreadAdvertisementData;
use app\model\api\v2\GatherUserInfo as GatherUserInfoModel;
use app\model\api\v2\UserProfile;
use laytp\BaseModel;
use think\facade\Config;
use think\facade\Db;
use think\facade\Event;
use think\model\concern\SoftDelete;
use app\lib\api\wxmini\WxMiniSqrcode;
use app\model\api\Course;
use app\model\admin\ThreadExternal;
use app\lib\api\openapi\CheckPhone;

class Thread extends BaseModel
{
    use SoftDelete;
    //表名
    protected $name = 'thread';

    /**
     *
     * @param array $params
     * @return int|mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public static function getPayApplyForFlowStatus($params = []){
        extract($params);
        $courseOrder = CourseOrder::field('pay_status')->where('order_sn', $order_sn)->find();
        $pay_status = 1;
        if (!empty($courseOrder)) {
            $pay_status = $courseOrder['pay_status'];
        }
        return $pay_status;
    }

    //付费报名
    public static function payApplyForFlow($params = []) {
        extract($params);
        $ageRangeId = $age_range_id ?? 2;

        //获取是否是测试号码；
        //测试账号
        $testPhoneArr = Config::load("extra/test/userphone", "extra") ?? [];

        $isTestStatus = 0;
        if(in_array($phone,$testPhoneArr)){
            $isTestStatus = 1;
        }

        Db::startTrans();
        try {
            if (self::checkApplyForFlow($phone, $for_flow_id)) {
                new Exception('已领取名额');
            }
            $forFlowInfo = ForFlow::find($for_flow_id);
            if (empty($forFlowInfo) || $forFlowInfo->status == 0) {
                new Exception('该名额已结束');
            }
            $price = $forFlowInfo['price'];
            if (!$price) {
                new Exception('该渠道报名为免费报名');
            }
            $merchantId = self::getMerchantServiceId($forFlowInfo->merchant_ids, $ageRangeId);
            $merchantInfo = Merchant::find($merchantId);
            if (empty($merchantInfo) || $merchantInfo->is_switch == 0) {
                new Exception('该名额已结束');
            }

            $userInfo = UserList::where('phone', $phone)->where('flow_id', $for_flow_id)->find();
            $channelInfo = Channel::with(['app' => function($query){
                $query->field('id,app_class_id');
            }])
                ->where('id',$channel)->find()->toArray();
            if (empty($userInfo)) {
                $userInfo = self::createUserList($params,1,null,$isTestStatus);
            }
            $customerId = (new CustomerService)->getCustomerServiceId($merchantId, $userInfo->id);
            $customerInfo = Customer::withTrashed()->find($customerId);
            if (empty($customerInfo)) {
                new Exception('该名额已结束');
            }

            $gatherInfo = GatherUserInfoModel::getFormatGatherInfo($userInfo->age_range_id, 'age_range_id');
            $ageRange = $gatherInfo['name'];
            $weightArr = isset($merchantInfo->age_range_weight_json) && !empty($merchantInfo->age_range_weight_json) ? json_decode($merchantInfo->age_range_weight_json, true) : [];
            $weight = isset($weightArr[$ageRange]) && !empty($weightArr[$ageRange]) ? $weightArr[$ageRange] : 0;
            if ($weight <= 0) {
                new Exception('该名额已结束');
            }
            $order_sn = create_order_sn();
            $gatherInfo = GatherUserInfoModel::getFormatGatherInfo($userInfo->age_range_id, 'age_range_id');
            $ageRange = $gatherInfo['name'];
            $app_bundle_id = 'h5forflow';
            $threadPriceInfo = Merchant::getMerchantThreadPrice($merchantInfo, $channelInfo);
            $courseOrderData = [
                'uid' => $userInfo['id'],
                'merchant_id' => $merchantInfo->id,
                'course_id' => 0,
                'order_sn' => $order_sn,
                'total_amount' => $price,
                'original_price' => $price,
                'channel' => $userInfo->channel,
                'app_bundle_id' => $app_bundle_id,
                'for_flow_id' => $for_flow_id,
                'pay_type' => $pay_type,
                'ip' => request()->ip(),
                'age' => $ageRange,
                'channel_id' => $channelInfo['id'],
                'app_id' => $channelInfo['app']['id'],
                'app_class_id' => $channelInfo['app']['app_class_id'],
                'landing_page_id' => $landing_page_id ?? 0,
                'thread_price' => $threadPriceInfo['thread_price'],
                'thread_price_type' => $threadPriceInfo['thread_price_type'],
                'thread_price_origin' => !empty($merchantInfo->thread_price_origin) ? $merchantInfo->thread_price_origin : $threadPriceInfo['thread_price'],
            ];
            $courseOrder = new CourseOrder();
            $orderRes = $courseOrder->save($courseOrderData);
            if(!$orderRes){
                new Exception('该名额已结束');
            }
            $orderParams = [
                'total_amount' => $price,
                'order_sn' => $order_sn,
                'desc' => '商品订单' . $order_sn,
                'app_bundle_id' => $app_bundle_id,
                'system_type' => $system_type ?? '',
            ];
            $return_url = '';
            $wxMiniConfig = (new WxMiniSqrcode())->actionSqrcode($userInfo->phone, $for_flow_id, $merchantInfo->app_class_id,$userInfo->channel);
            if(isset($merchantInfo->is_jump_miniprogram) && $merchantInfo->is_jump_miniprogram){
                $return_url = $wxMiniConfig['openlink_url'];
            }else{
                $return_url = env('flow.link').'?for_flow_id='.$for_flow_id.'&channel='.$channel;
            }
            Db::commit();
            return [
                'order_sn'=>$order_sn,
                'pay_url'=>self::getPayParams($pay_type, $orderParams),
                'return_url'=>$return_url,
                'is_jump_miniprogram'=>$merchantInfo->is_jump_miniprogram,
                'wx_mini_config' => $wxMiniConfig,
            ];
        } catch (\Exception $e) {
            Db::rollback();
            new Exception('领取失败（'. $e->getMessage().'）');
        }
    }

    //获取支付参数
    public static function getPayParams($payType = null, $orderParams = []) {
        if ($payType == 'alipay') {
            $payParams = Alipay::h5ForFlowAliAppPay($orderParams);
            return $payParams;
        }
        if ($payType == 'wxpay') {
            $payParams = Wxpay::WeixinJSBridge($orderParams);
            return $payParams;
        }
    }

    //是否已报名
    public static function checkApplyForFlow($phone,$forFlowId = 0,$merchantId = 0,$h5_uid = '')
    {
        $checkApplyCourse = 0;
        $forFlowInfo = ForFlow::where('id',$forFlowId)->find();
        if(mb_strlen($phone) > 11){
            $uid = UserList::where('h5_uid', $phone)->where('flow_id', '>',0)->order('id desc')->value('id');
        }else{
            $uid = UserList::where('phone', $phone)->where('flow_id', '>',0)->order('id desc')->value('id');
        }
        if(!empty($uid)){
            // 线索model
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
                ->where('flow_id', $forFlowId)
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
                if (in_array($for_flow_id, [48,55,60,61,72,146,149])) {
                    file_put_contents('h5_weibo_callback.txt',$channel,FILE_APPEND);
                    $H5OppoAdvertiser = new H5OppoAdvertiser();
                    $H5OppoAdvertiser->h5OppoAdvertiserQrcodeCollback($for_flow_id);
                }
                if ($channel == 'jdh5_douyin1' || $channel == 'jdh5_douyin2' || $channel == 'jdh5_douyin5' || $channel == 'yqxxl_douyin1') {
                    $H5DouyinAdvertiser = new H5DouyinAdvertiser();
                    $H5DouyinAdvertiser->h5DouyinAdvertiserQrcodeCollback($for_flow_id,$phone);
                }
                if ($channel == 'yqh5_gdt7') {
                    $H5GdtAdvertiser = new H5GdtAdvertiser();
                    $H5GdtAdvertiser->h5GdtAdvertiserOverdueCollback($userInfo,'SCANCODE');
                }
                // if ($channel == 'yqh5_weibo6') {
                //     $H5WeiboAdvertiser = new H5WeiboAdvertiser();
                //     $H5WeiboAdvertiser->h5WeiboAdvertiserQrcodeCollback($for_flow_id);
                // }
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
    public static function freeApplyForFlow($params = [])
    {
        extract($params);
        $ageRangeId = $age_range_id ?? 2;
        $isCallback = 0;
        try {

            //获取是否是测试号码；
            //测试账号
            $testPhoneArr = Config::load("extra/test/userphone", "extra") ?? [];
            //请求地址
            $mmcrm_url = env('MMCRMURL.MMCRM_URL') ?? 'http://szmmcrm.yuluojishu.com';

            $isTestStatus = 0;
            if(in_array($phone,$testPhoneArr) || in_array($for_flow_id, [2])){
                $isTestStatus = 1;
            }

            $forFlowInfo = ForFlow::find($for_flow_id);
            $forFlowInfo->is_data = $forFlowInfo->is_no_data_jump_miniprogram == 1 ? 0 : $forFlowInfo->is_data;
            if ($forFlowInfo->is_data) {
                if($forFlowInfo->is_nickname == 1){
                    if(!isset($nickname) || empty($nickname)){
                        new Exception('请输入贵姓');
                    }
                }
                if( !$isTestStatus && $forFlowInfo->is_need_phone == 1){
                    $checkPhone = new CheckPhone();
                    $checkPhone->h5FlowCheckPhone($phone,$forFlowInfo->is_need_captcha);
                }
                if(!$isTestStatus && $forFlowInfo->is_need_captcha == 1){
                    if(!isset($captcha) || empty($captcha)){
                        new Exception('请输入验证码');
                    }
                    Captcha::checkCaptcha(['phone' => $phone, 'type' => 5], $captcha);
                }
            }

            $h5_uid = md5(request()->ip().$h5_uid);
            if($forFlowInfo->is_need_phone == 0 || $forFlowInfo->is_data == 0){
                $userInfo = UserList::where('h5_uid',$h5_uid)->where('flow_id','<>',0)->order('id desc')->find();
            }else{
                $userInfo = UserList::where('phone',$phone)->where('flow_id','<>',0)->order('id desc')->find();
            }
            if(empty($userInfo)){
                $userInfo = self::createUserList($params,$forFlowInfo->is_need_phone,$forFlowInfo,$isTestStatus);
            }
            // 检查是否报名
            $data = Thread::where('uid', $userInfo->id)->find();
            if (self::checkApplyForFlow($userInfo->phone,$for_flow_id) && $data['handling_progress'] !== 3) {
                $customer = Customer::where('id', $data['customer_id'])->find();
                return ['customer_link' => $customer['customer_link'], 'thread_id' => $data['id']];
            }

            $channelInfo = Channel::with(['app' => function($query){
                $query->field('id, app_class_id');
            }])
                ->where('id',$channel)
                ->find();
            $debtMoneyRange = self::getDebtMoneyRange($userInfo->id,$channelInfo['app']['app_class_id']);

            # 行销单独处理
            if (in_array($forFlowInfo['page_type'], [5,6])) {
                # 记录留资请求记录
                $state = "channel_{$channel}_{$h5_uid}";

                if($for_flow_id == 37) {
                    $result = ['customer_link' => "https://work.weixin.qq.com/ca/cawcdeabf3ac86cb48?customer_channel={$state}", 'thread_id' => 0];
                } else {
                    $result = ['customer_link' => "https://work.weixin.qq.com/ca/cawcdef4c8c326e8e4?customer_channel={$state}", 'thread_id' => 0];
                }

                SubmitFundsRecord::create([
                    'phone'             => $phone ?? '',
                    'wx_number'         => $wx_number ?? '',
                    'wx_nickname'       => $wx_nickname ?? '',
                    'h5_uid'            => $h5_uid,
                    'request_ip'        => request()->ip(),
                    'channel'           => $channelInfo['channel_name'] ?? '',
                    'channel_id'        => $channel,
                    'flow_id'           => $for_flow_id,
                    'wecom_link'        => $result['customer_link'],
                    'wecom_link_state'  => $state,
                    'customer_id'       => 0,
                    'user_list_id'      => $userInfo ? $userInfo->id : 0,
                    'thread_id'         => $result['thread_id'],
                    'request_data'      => json_encode($params),
                    'response_data'     => json_encode($result),
                ]);

                return $result;
            }

            $isFangdai = 0;
            if($isTestStatus){
                //是测试账号，默认数据
                $merchantCustomerId = [
                    'merchant_id' => 229,
                    'customer_id' => 2521,
                    'is_callback' => 0,
                    'customer_link' => 'https://work.weixin.qq.com/ca/cawcded0744346fa19',
                    ];
            } else {
                $isDouyin       = $userInfo['app_id'] == 10 ? 1 : 0; #抖音线索
                $isBzhanxhs     = $userInfo['app_id'] == 13 ? 1 : 0; #B站小红书线索
                $isFangdai      = $userInfo['app_id'] == 14 ? 1 : 0; #房贷信息流
                $specialAppoint = $forFlowInfo->special_appoint ?? 1; # 是否特殊进量

                if ($isFangdai) {
                    //房贷信息流 随机分给固定的销售
                    //皇建坤：https://work.weixin.qq.com/ca/cawcde7e0c07d7050c
                    //陈雅诗：https://work.weixin.qq.com/ca/cawcde0b59aef48b72
                    $merchantCustomerIdArr = [
                        [
                            'merchant_id' => 271,
                            'customer_id' => 4572,
                            'is_callback' => 0,
                            'customer_link' => 'https://work.weixin.qq.com/ca/cawcde7e0c07d7050c',
                        ],
                        [
                            'merchant_id' => 271,
                            'customer_id' => 4586,
                            'is_callback' => 0,
                            'customer_link' => 'https://work.weixin.qq.com/ca/cawcde0b59aef48b72',
                        ],
                    ];
                    $num = rand(0, 1);
                    $merchantCustomerId = $merchantCustomerIdArr[$num];
                } elseif ($specialAppoint == 2) {

                    $requestRst = file_get_contents($mmcrm_url.'/api.ImportWecom/getInputCustomer?merchant_id=' . 272);
                    $requestRst = json_decode($requestRst, true);
                    $requestRst = $requestRst['data'] ?? [];

                    $merchantCustomerId = [
                        'merchant_id' => $requestRst['merchant_id'] ?? 0,
                        'customer_id' => $requestRst['customer_id'] ?? 0,
                        'is_callback' => 0,
                        'customer_link' => $requestRst['customer_link'] ?? '',
                    ];
                } elseif ($specialAppoint == 3) {

                    //融投放 随机分给固定的销售
                    //皇建坤：https://work.weixin.qq.com/ca/cawcde7e0c07d7050c
                    //陈雅诗：https://work.weixin.qq.com/ca/cawcde0b59aef48b72
                    $merchantCustomerIdArr = [
                        [
                            'merchant_id' => 277,
                            'customer_id' => 6329,
                            'is_callback' => 0,
                            'customer_link' => 'https://work.weixin.qq.com/ca/cawcdec3b95d636e1f',
                        ],
                        [
                            'merchant_id' => 277,
                            'customer_id' => 5040,
                            'is_callback' => 0,
                            'customer_link' => 'https://work.weixin.qq.com/ca/cawcde52b94f2a9d83',
                        ],
                        [
                            'merchant_id' => 277,
                            'customer_id' => 6332,
                            'is_callback' => 0,
                            'customer_link' => 'https://work.weixin.qq.com/ca/cawcdefd09193d9544',
                        ],
                        [
                            'merchant_id' => 277,
                            'customer_id' => 6333,
                            'is_callback' => 0,
                            'customer_link' => 'https://work.weixin.qq.com/ca/cawcdefee28fb1dd2d',
                        ],
                        [
                            'merchant_id' => 277,
                            'customer_id' => 6328,
                            'is_callback' => 0,
                            'customer_link' => 'https://work.weixin.qq.com/ca/cawcde1c087cee7ac1',
                        ],
                    ];
                    $num = rand(0, count($merchantCustomerIdArr) - 1);
                    $merchantCustomerId = $merchantCustomerIdArr[$num];

                } else {
                    $merchantCustomerId = self::getMerchantServiceId($forFlowInfo->merchant_ids, $ageRangeId, $userInfo->id ?? 0, 0, $debtMoneyRange['money_range'], ['is_douyin' => $isDouyin, 'is_bzhanxhs' => $isBzhanxhs]);
                }
            }
            $merchantId = $merchantCustomerId['merchant_id'];
            $customerId = $merchantCustomerId['customer_id'];
            $isCallback = $merchantCustomerId['is_callback'];
            $customerLink = $merchantCustomerId['customer_link'] ?? "https://work.weixin.qq.com/ca/cawcdef02fae4e282a";
            $merchantInfo = Merchant::find($merchantId);
            if (empty($forFlowInfo) || $forFlowInfo->status == 0) {
                if($isCallback) curlPost($mmcrm_url.'/admin.api.external_thread_yl/fallbackThreadNum',['is_media' => 0,'service_id' => $customerId]);
                new Exception('该名额已结束');
            }
            if (empty($merchantInfo) || $merchantInfo->is_switch == 0) {
                if($isCallback) curlPost($mmcrm_url.'/admin.api.external_thread_yl/fallbackThreadNum',['is_media' => 0,'service_id' => $customerId]);
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
                if($isCallback) curlPost($mmcrm_url.'/admin.api.external_thread_yl/fallbackThreadNum',['is_media' => 0,'service_id' => $customerId]);
                new Exception('该名额已结束');
            }
            $redis->multi();
            $redis->decrBy($redisKey, $threadPrice);
            $result = $redis->exec();

            if($result) {
                Db::startTrans();
                $threadType = 0;
                $sourceId = 0;
                if ($userInfo['app_class_id'] == 29) $threadType = 2; #应用分类29是律师事务所
                if ($userInfo['app_class_id'] == 30) $threadType = 1; #应用分类30是劳动仲裁
                if ($userInfo['app_id'] == 10 || $userInfo['app_id'] == 14) $sourceId = 114; #抖音线索
                if ($userInfo['app_id'] == 13) $sourceId = 122; #B站小红书线索
                $ret = self::create([
                    'uid' => $userInfo->id,
                    'course_id' => $courseId,
                    'flow_id' => $for_flow_id,
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
                    'thread_type' => $threadType,
                    'source' => $channelInfo['source_id'],
                    'source_id' => $sourceId,
                    'is_free_try' => $merchantInfo->is_free_try,
                    'is_test' => $userInfo->is_test,
                    'age_id' => $userInfo->age_range_id,
                    'merchant_admin_id' => $merchantInfo->admin_ids,
                    'is_origin' => 2,
                    'cost_price'=>$channelInfo['cost_price'],
                    'debt_range' => $debtMoneyRange['debt_range'],
                    'money_range' => $debtMoneyRange['money_range'],
                    'overdue_time' => $debtMoneyRange['overdue_time'],
                    'adid' => $adid ?? '',
                    'h5_apply_phone_system' => getPhoneSystem(),

                ]);
                if($ret) {
                    if($userInfo->is_test == 0){
                        Event::trigger('ApplySuccessAfter', ['merchant' => $merchantInfo, 'thread' => ['uid' => $userInfo->id, 'customerId' => $customerId, 'thread_price' => $threadPriceInfo['thread_price']]]);
                    }
                    FlowPvUv::threadPvUv(['uid' => $userInfo->id, 'nickname' => $userInfo->nickname, 'channel' => $channel, 'for_flow_id' => $for_flow_id, 'start_time' => $start_time, 'thread_id' => $ret->id]);

                    $userInfo = $userInfo->toArray();
                    $thread = \app\model\api\Thread::where('id', $ret['id'])->order('id desc')->find();
                    $threadInfo = $thread->toArray();
                    $threadInfo['is_super_a'] = 0;
                    $threadInfo['is_within_three_days'] = 0;
                    $threadId = $threadInfo['id'];
                    unset($threadInfo['id']);
                    unset($userInfo['id']);
                    $userInfo['create_time'] = $userInfo['update_time'] = strtotime($userInfo['create_time']);
                    $threadInfo['create_time'] = $threadInfo['update_time'] = strtotime($threadInfo['create_time']);
                    $threadInfo['wm_uid'] = $threadInfo['uid'];
                    $threadInfo['init_customer_id'] = $threadInfo['customer_id'];
                    $threadInfo['init_merchant_id'] = $threadInfo['merchant_id'];
                    $threadInfo['inside_thread_id'] = $threadId;

                    $urlParams = isset($params['url_params']) && $params['url_params'] ? json_decode($params['url_params'], true) : [];
                    ThreadAdvertisementData::create([
                        'thread_id'     => $ret['id'],
                        'track_id'      => $urlParams['track_id'] ?? '',
                        'account_id'    => $urlParams['account_id'] ?? '',
                        'campaign_id'   => $urlParams['campaign_id'] ?? '',
                        'creative_id'   => $urlParams['creative_id'] ?? '',
                        'url_params'    => json_encode($urlParams),
                    ]);

                    if (! $isCallback && !$isFangdai) {
                        $merchant = \app\model\api\Merchant::where('id', $merchantId)->find();
                        Merchant::update(['residue_amount' => $merchant['residue_amount'] - $merchant['thread_price_origin']], ['id' => $merchant['id']]);
                        Db::commit();
                        return ['customer_link' => $customerLink, 'thread_id' => $threadId];
                    }

                    $res = \Qiniu\json_decode(curlPost($mmcrm_url.'/admin.api.external_thread_yl/importExternalThreadYl', http_build_query(['user_info' => $userInfo, 'thread_info' => $threadInfo])),true);
                    if ($res['code'] == 200) {
                        $merchant = \app\model\api\Merchant::where('id', $merchantId)->find();
                        Merchant::update(['residue_amount' => $merchant['residue_amount'] - $merchant['thread_price_origin']], ['id' => $merchant['id']]);
                        Db::commit();
                        return ['customer_link' => $customerLink, 'thread_id' => $threadId];
                    }
                    Db::rollback();
                    curlPost($mmcrm_url.'/admin.api.external_thread_yl/fallbackThreadNum',['is_media' => 0,'service_id' => $customerId]);
                    new Exception('服务器出现异常，请稍后再试');
                }
                $redis->incrBy($redisKey, $threadPrice);
                if($isCallback) curlPost($mmcrm_url.'/admin.api.external_thread_yl/fallbackThreadNum',['is_media' => 0,'service_id' => $customerId]);
                new Exception('该名额已结束');
            }
            if($isCallback) curlPost($mmcrm_url.'/admin.api.external_thread_yl/fallbackThreadNum',['is_media' => 0,'service_id' => $customerId]);
            new Exception('该名额已结束');
        } catch (\Exception $e) {
            Db::rollback();
            if($isCallback) curlPost($mmcrm_url.'/admin.api.external_thread_yl/fallbackThreadNum',['is_media' => 0,'service_id' => $customerId ?? 0]);
            new Exception($e->getMessage());
        }
    }

    //计算报名商户重复率1
    public static function userMerchantSameRate($uId = 0,$merchantId = 0)
    {
        $isApplyMerchant = 0;
        $szmUserIds = Thread::where('merchant_id',142)->whereDay('create_time')->column('uid');
        $zwzUserIds = Thread::where('merchant_id',195)->whereDay('create_time')->column('uid');
        $szmThreadNum = Thread::where('merchant_id',142)->whereDay('create_time')->count();
        $zwzThreadNum = Thread::where('merchant_id',195)->whereDay('create_time')->count();
        //$userIds = array_diff($szmUserIds,$zwzUserIds);
        $sameUserIds = self::intersection($szmUserIds,$zwzUserIds);
        $sameCount = count($sameUserIds);
        $szmThreadRate = $szmThreadNum > 0 && $sameCount > 0 ? round($sameCount/$szmThreadNum,2) * 100 : 0;
        $zwzThreadRate = $zwzThreadNum > 0 && $sameCount > 0 ? round($sameCount/$zwzThreadNum,2) * 100 : 0;
        if($merchantId == 142){
            $zwzThread = Thread::where('uid',$uId)->where('merchant_id',195)->whereDay('create_time')->count();
            if($zwzThread > 0 && $zwzThreadRate > 7){
                $isApplyMerchant = 1;
            }
        }
        if($merchantId == 195){
            $szmThread = Thread::where('uid',$uId)->where('merchant_id',142)->whereDay('create_time')->count();
            if($szmThread > 0 && $szmThreadRate > 7){
                $isApplyMerchant = 1;
            }
        }
        return $isApplyMerchant;
    }

    //两数组的交集
    public static function intersection($nums1, $nums2) {
        $res = [];
        for($i=0;$i<count($nums1);$i++){
            if(in_array($nums1[$i],$nums2)){
                $res[] = $nums1[$i];
            }
        }
        return array_unique($res);
    }

    //获取商户
    public static function getMerchantServiceId($merchantIds = '',$ageRangeId = 0, $uid = 0,$isMedia = 0,$moneyRange = '', $params = [])
    {
        $redis = get_redis();
        $redisKey = env('FLOW.H5_FLOW_ALLOW_APPLY_MERCHANT_KEY');
        $merchantId = 0;
        $customerId = 0;
        $customerLink = '';
        $isCallback = 0;

        $isDouyin      = isset($params['is_douyin']) && $params['is_douyin'] ? $params['is_douyin'] : 0;
        $isBzhanxhs    = isset($params['is_bzhanxhs']) && $params['is_bzhanxhs'] ? $params['is_bzhanxhs'] : 0;
        if ($isDouyin || $isBzhanxhs) {
            $merchantRes = json_decode(curlPost('testzyxmcrm.zhiyunjishu.cn/admin.api.external_thread_yl/getCustomerInfo',
                [
                    'money_range' => $moneyRange,
                    'is_test' => 0,
                    'is_appoint_merchant' => $merchantId ? 1 : 0,
                    'merchant_id' => $merchantId ?? 0,
                    'is_ecommerce' => 0,
                    'is_appstore' => 0,
                    'is_reduce_thread_num' => 1,
                    'is_douyin' => $isDouyin,
                    'is_bzhanxhs' => $isBzhanxhs,
                ]),true);
            if($merchantRes['code'] == 200){
                $merchantId = $merchantRes['data']['merchant_id'] ?? $merchantId;
                $customerId = $merchantRes['data']['id'] ?? 0;
                $customerLink = $merchantRes['data']['customer_link'] ?? '';
                $isCallback = 1;
            }
            return ['merchant_id' => $merchantId,'customer_id' => $customerId,'is_callback' => $isCallback, 'customer_link' => $customerLink];
        }

        $merchantIds = explode(',',$merchantIds);
        $gatherInfo = GatherUserInfoModel::getFormatGatherInfo($ageRangeId, 'age_range_id');
        $ageRange = !empty($gatherInfo['name']) ? $gatherInfo['name'] : '';
        $ageRangeMer = !empty($gatherInfo['name']) ? '"'.$gatherInfo['name'].'"' : '';
        $merchantIdArr = [];
        if (!empty($uid)) {
            $userInfo = UserList::where('id',$uid)->field('id,channel,is_test')->find();
            $merchantIdArr = Thread::where('uid', $uid)->whereIn('merchant_id',$merchantIds)->whereDay('create_time')->column('merchant_id');
        }
        if(strstr($userInfo->channel, 'yqxxl_douyin') !== false){
            $customerId = (new DouyinCustomerService())->getCustomerServiceId();
            $merchantId = Customer::where('id',$customerId)->value('merchant_id');
            return ['merchant_id' => $merchantId,'customer_id' => $customerId,'is_callback' => 0];
        }
        $merchantValue = array_diff($merchantIds,$merchantIdArr);
        if (! $merchantValue) {
            $merchantIds = $merchantIds[0];
        }else {
            $merchantIds = $merchantValue;
        }

        $merchantList = Merchant::where('is_switch',1)
            ->whereIn('id',$merchantIds)
            ->where("age_range_weight_json->'$.".$ageRangeMer."'",'>',0)
            ->field('id,is_source,age_range_weight_json')
            ->select();
        $dataSourceY = [];
        $dataSourceN = [];
        $dataSourceYId = [];
        if(!empty($merchantList)){
            foreach($merchantList as $item) {
                $weightArr = isset($item['age_range_weight_json']) && !empty($item['age_range_weight_json']) ? json_decode($item['age_range_weight_json'], true) : [];
                $age_range_weight = isset($weightArr[$ageRange]) && !empty($weightArr[$ageRange]) ? $weightArr[$ageRange] : 0;
                if ($age_range_weight > 0) {
                    if ($item['is_source'] == 1) {
                        $dataSourceN[] = [
                            'id' => $item['id'],
                            'is_source' => $item['is_source'],
                            'weight' => $age_range_weight,
                        ];
                    }
                    if ($item['is_source'] == 2) {
                        $dataSourceY[] = [
                            'id' => $item['id'],
                            'is_source' => $item['is_source'],
                            'weight' => $age_range_weight,
                        ];
                        $dataSourceYId[] = $item['id'];
                    }
                }
            }
            if(!empty($dataSourceY) && count($dataSourceY) > 1) {
                if (!$redis->exists($redisKey)) {  //初始化赋值123
                    foreach($dataSourceY as $item){
                        $dataSourceSzmZwz = [
                            'id' => $item['id'],
                            'weight' => $item['weight'],
                            'period_weight' => 1
                        ];
                        $redis->hset($redisKey,$item['id'],json_encode($dataSourceSzmZwz));
                    }
                } else {
                    foreach($dataSourceY as $item){
                        $redisData = $redis->hget($redisKey,$item['id']);
                        if(empty($redisData)){
                            $redis->hSet($redisKey, $item['id'], json_encode([
                                'id' => $item['id'],
                                'weight' => $item['weight'],
                                'period_weight' => 1]));
                        }
                    }
                    $dataSourceRedis = $redis->hGetAll($redisKey);
                    $dataSourceRedisY = [];
                    foreach($dataSourceRedis as $val){
                        $arr = json_decode($val, true);
                        if($arr['period_weight'] && in_array($arr['id'],$dataSourceYId)){
                            $dataSourceRedisY[] = [
                                'id' => $arr['id'],
                                'weight' => $arr['weight']
                            ];
                        }
                    }
                    if(!empty($dataSourceRedisY)){
                        $dataSourceY = $dataSourceRedisY;
                    }else{
                        foreach($dataSourceY as $item){
                            $dataSourceSzmZwz = [
                                'id' => $item['id'],
                                'weight' => $item['weight'],
                                'period_weight' => 1
                            ];
                            $redis->hset($redisKey,$item['id'],json_encode($dataSourceSzmZwz));
                        }
                    }
                }
                $expireTime = mktime('23',59,59, date('m'),date('d'),date('Y'));
                $redis->expireAt($redisKey, $expireTime);
            }
            $isCallback = 0;
            $data = !empty($dataSourceY) ? $dataSourceY : $dataSourceN;
            $merchantId = (new WeightService)->initData($data);
//            if($merchantId == 142 || $merchantId == 195 || $merchantId == 229 || $merchantId == 242 || $merchantId == 245 || $merchantId == 246 || $merchantId == 252 || $merchantId == 258 || $merchantId == 259){
//                $merchantRes = json_decode(curlPost('http://testzyxmcrm.zhiyunjishu.cn/admin.api.external_thread_yl/getCustomerInfo',
//                    [
//                        'money_range' => $moneyRange,
//                        'is_test' => 1,
//                        'is_appoint_merchant' => $params['merchant_id'] ? 1 : 0,
//                        'merchant_id' => $params['merchant_id'] ?? 0,
//                        'is_ecommerce' => 0,
//                        'is_appstore' => 0,
//                        'is_reduce_thread_num' => 1
//                    ]),true);
//                if($merchantRes['code'] == 200){
//                    $merchantId = $merchantRes['data']['merchant_id'] ?? $merchantId;
//                    $customerId = $merchantRes['data']['id'] ?? 0;
//                    $isCallback = 1;
//                }
//            }
//            return [
//                'money_range' => $moneyRange,
//                'is_test' => 0,
//                'is_appoint_merchant' => $merchantId ? 1 : 0,
//                'merchant_id' => $merchantId ?? 0,
//                'is_ecommerce' => 0,
//                'is_appstore' => 0,
//                'is_reduce_thread_num' => 1
//            ];
            $isDouyin = isset($params['is_douyin']) && $params['is_douyin'] ? $params['is_douyin'] : 0;
            $merchantRes = json_decode(curlPost('http://testzyxmcrm.zhiyunjishu.cn/admin.api.external_thread_yl/getCustomerInfo',
                [
                    'money_range' => $moneyRange,
                    'is_test' => 0,
                    'is_appoint_merchant' => $merchantId ? 1 : 0,
                    'merchant_id' => $merchantId ?? 0,
                    'is_ecommerce' => 0,
                    'is_appstore' => 0,
                    'is_reduce_thread_num' => 1,
                    'is_douyin' => $isDouyin,
                    'is_bzhanxhs' => $isBzhanxhs,
                ]),true);
            if($merchantRes['code'] == 200){
                $merchantId = $merchantRes['data']['merchant_id'] ?? $merchantId;
                $customerId = $merchantRes['data']['id'] ?? 0;
                $customerLink = $merchantRes['data']['customer_link'] ?? '';
                $isCallback = 1;
            }
        }
        return ['merchant_id' => $merchantId,'customer_id' => $customerId,'is_callback' => $isCallback, 'customer_link' => $customerLink];
    }

    //设置已分配商户
    public static function setMerchantWeight($redis, $redisKey, $merchantId = 0)
    {
        $exposeInfo = $redis->hget($redisKey, $merchantId);
        if (!empty($exposeInfo)) {
            $exposeInfo = json_decode($exposeInfo, true);
            $exposeInfo['period_weight'] = 0;
            $redis->hSet($redisKey, $merchantId, json_encode($exposeInfo));
        }
    }

    //添加注册用户信息1
    public static function createUserList($params = [],$isNeedPhone = 1, $forFlowInfo = null,$isTestStatus=0)
    {
        extract($params);
        $ip = request()->ip();
        $h5_uid = md5($ip.$h5_uid);
        $forFlowInfo->is_data = $forFlowInfo->is_no_data_jump_miniprogram == 1 ? 0 : $forFlowInfo->is_data;
        if($isNeedPhone == 0 || $forFlowInfo->is_data == 0){
            $userInfo = UserList::where('h5_uid',$h5_uid)->where('flow_id','>',0)->order('id desc')->find();
        }else{
            $userInfo = UserList::where('phone',$phone)->where('flow_id','>',0)->order('id desc')->find();
        }
        $channelInfo = Channel::with(['app' => function($query){
            $query->field('id,app_class_id');
        }])
            ->where('id',$channel)
            ->field('id,app_id,channel_name,gather_user_info_ids')
            ->find();
        if(!empty($userInfo)){
            return $userInfo;
        }else {
            try {
                $phone = isset($phone) && !empty($phone) ? $phone : '22222222222';
                $nickname = $nickname ?? '';
                $is_switch = 0;
                $merchant = Merchant::where('is_switch', 1)->where('is_source', 2)->count();
                if ($merchant > 0) {
                    $is_switch = 1;
                }
                $oldUser = UserList::where('phone', $phone)->find();
                if (empty($nickname)) {
                    $nickname = UserList::whereLike('nickname', '匿名%')->order('id desc')->value('nickname');
                    if (!empty($nickname)) {
                        $number = (int)mb_substr($nickname, 2);
                        $number = $number + 1;
                        $nickname = '匿名' . $number;
                    } else {
                        $nickname = '匿名1';
                    }
                }
                $is_test = 0;
                if ($isTestStatus || strpos($nickname, '测试') !== false || substr($phone,0,2) === '11' || $phone == '13777571709' || request()->ip() == env('flow.ip')) {
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
                            if(isset($$field)){
                                $gatherInfoData[$field] = $$field;
                                $gatherInfoDataStrData[] = $val['id'].'='.$$field;
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
                    'nickname' => $nickname,
                    'login_time' => date("Y-m-d H:i:s"),
                    'login_ip' => request()->ip(),
                    'channel' => $channelInfo->channel_name,
                    'channel_id' => $channelInfo->id,
                    'app_id' => $channelInfo->app->id,
                    'app_class_id' => $channelInfo->app->app_class_id,
                    'wx_nickname' => isset($oldUser->wx_nickname) ? $oldUser->wx_nickname : '',
                    'avatar' => isset($oldUser->avatar) ? $oldUser->avatar : '',
                    'wxopenid' => isset($oldUser->wxopenid) ? $oldUser->wxopenid : '',
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
                    'fd_money' => isset($fd_money) ? $fd_money : 0,
                    'fd_overdue' => isset($fd_overdue) ? $fd_overdue : 0,
                    'fd_amount' => isset($fd_amount) ? $fd_amount : 0,
                    'is_switch' => $is_switch,
                    'is_test' => $is_test,
                    'flow_id' => $for_flow_id,
                    'source' => 2,
                    'phone_end_number' => substr($phone,-4),
                    'custom_fields' => $gatherInfoDataStr,
                    'imei' => $imei ?? '',
                    'h5_uid' => $h5_uid ?? '',
                    'province' => $cityInfo['province_name'] ?? '',
                    'city' => $cityInfo['city_name'] ?? '',
                    'jyd_overdue' => isset($jyd_overdue) ? $jyd_overdue : 0,
                    'jyd_PayAbility' => isset($jyd_PayAbility) ? $jyd_PayAbility : 0,
                    'jyd_amount' => isset($jyd_amount) ? $jyd_amount : 0,
                    'jyd_demand' => isset($jyd_demand) ? $jyd_demand : 0,
                ]);
                //保存收集信息
                $gatherInfoData['uid'] = $user->id;
                UserProfile::create($gatherInfoData);

                $userInfo = UserList::find($user->id);
                return $userInfo;
            }catch (\Exception $e){
                new ExceptionStd('领取失败');
            }
        }

    }

    public static function getDebtMoneyRange($uid = 0,$appClassId = 0)
    {
        $overdueTimeName = '';
        $debtRangeName = $appClassId == 9 ? '信用卡' : '';
        $moneyRangeName = $appClassId == 9 ? '1万以下' : '';
        $userInfo = UserList::where('id',$uid)->field('id,custom_fields')->find();
        $debtRangeList = GatherUserInfoModel::where('field','zw_mold')->find();
        $moneyRangeList = GatherUserInfoModel::where('field','zhaiwu_monney')->find();
        $overdueTimeList = GatherUserInfoModel::where('field','zhaiwu_zhuangtai')->find();
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

    public static function getCustomerLink($customerId = 0,$threadId = 0)
    {
        $customerLinkList = [
            2974 => 'https://work.weixin.qq.com/ca/cawcdeaaf45e1dbbec',
            2277 => 'https://work.weixin.qq.com/ca/cawcde203d3aab0025',
            3555 => 'https://work.weixin.qq.com/ca/cawcded2c35e9863f2',
            3670 => 'https://work.weixin.qq.com/ca/cawcde8624788e1d4c',
            3460 => 'https://work.weixin.qq.com/ca/cawcded31e112d7d69',
            3452 => 'https://work.weixin.qq.com/ca/cawcde567af7f4cfd6',
            3634 => 'https://work.weixin.qq.com/ca/cawcde7468f803382d',
            3565 => 'https://work.weixin.qq.com/ca/cawcde23de90a50ba2',
            3557 => 'https://work.weixin.qq.com/ca/cawcde1a51d0188288',
            3643 => 'https://work.weixin.qq.com/ca/cawcde65250bfa137f',
            3770 => 'https://work.weixin.qq.com/ca/cawcde619c6949faa0',
            2880 => 'https://work.weixin.qq.com/ca/cawcde1aec60815e27',
            3235 => 'https://work.weixin.qq.com/ca/cawcde808d506a7647',
            3589 => 'https://work.weixin.qq.com/ca/cawcdefaf6980d19f1',
            3769 => 'https://work.weixin.qq.com/ca/cawcdeba1c02b611f1',
            3554 => 'https://work.weixin.qq.com/ca/cawcdea5cce89c652c',
            3845 => 'https://work.weixin.qq.com/ca/cawcde77adad41dd59',
            3631 => 'https://work.weixin.qq.com/ca/cawcdea702eecd054e',
            3621 => 'https://work.weixin.qq.com/ca/cawcdea18f6ebec8a2',
            3580 => 'https://work.weixin.qq.com/ca/cawcdebaae9f0d22bf',
            3609 => 'https://work.weixin.qq.com/ca/cawcded29f54b8e549',
            3727 => 'https://work.weixin.qq.com/ca/cawcdeb8a853d7fd40',
            3665 => 'https://work.weixin.qq.com/ca/cawcde0987ebeca883',
            3692 => 'https://work.weixin.qq.com/ca/cawcde75872b97a5e9',
            3663 => 'https://work.weixin.qq.com/ca/cawcdeedf9bb8de27f',
            3344 => 'https://work.weixin.qq.com/ca/cawcde89809faacfb8',
            3790 => 'https://work.weixin.qq.com/ca/cawcdee976fe606a81',
            3389 => 'https://work.weixin.qq.com/ca/cawcdec1ff94cd40b0',
        ];
        $customerLink = $customerLinkList[$customerId] ?? '';
        return !empty($customerLink) ? 'weixin://biz/ww/profile/' . $customerLink . '?customer_channel=WORK' . '#thread_id=' . $threadId: '';
    }
}