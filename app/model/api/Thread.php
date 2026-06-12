<?php
/**
 * 报名表模型
 */

namespace app\model\api;

use app\lib\api\service\PartMerchantService;
use app\model\admin\ThreadExternal;
use app\model\api\Customer;
use app\model\api\h5\ForFlow;
use app\model\api\UserList;
use laytp\BaseModel;
use think\model\concern\SoftDelete;
use app\lib\api\exception\Exception;
use app\model\api\Course;
use app\model\api\Merchant;
use app\model\api\Channel;
use app\model\api\CustomerQrcodeLog;
use app\lib\api\service\CustomerService;
use app\lib\api\service\CustomerServiceOverdueChannel;
use app\lib\api\city\IpCity;
use think\facade\Event;
use think\facade\Db;
use think\facade\Config;
use app\model\api\v2\GatherUserInfo as GatherUserInfoModel;
use app\lib\api\service\MerchantServiceJob;
use app\model\admin\OverdueAppCustomer;
use app\lib\api\callback\AdvertiserCallbackApi;
use app\model\admin\UserProfile;
use think\facade\Log;


class Thread extends BaseModel
{
    use SoftDelete;
    //模型名
    protected $name = 'thread';
    //线索单价类型
    const THREAD_PRICE_TYPE_LEISURE = 1; //闲时
    const THREAD_PRICE_TYPE_PEAK = 2; //高峰
    protected $hidden = [];

    //免费报名
    public static function registerApplyCourse($params = [], $uid)
    {
        try {
            extract($params);
            $userInfo = UserList::find($uid);
            $channelInfo = Channel::getChannelAppClass($userInfo->channel);
            $courseInfo = Course::find($course_id);
            if (empty($courseInfo) || $courseInfo->status == 0) {
                return;
            }
            $isCallback = 0;
            $partCourseId = 0;
            $merchantId = 0;
            $customerId = 0;
            $debtMoneyRange = self::getDebtMoneyRange($uid, $channelInfo['app_class_id']);
            if ($channelInfo['app_class_id'] == 9) {
                $channelCus = OverdueAppCustomer::where('channel_id', $channelInfo['channel_id'])->find();
                //  var_dump($channelCus);die;
                if (!empty($channelCus)) {
                    $merchantCus = (new CustomerServiceOverdueChannel)->getCustomerServiceId($channelInfo['channel_id']);
                    if ($merchantCus['customer_id'] && $merchantCus['merchant_id']) {
                        $merchantId = $merchantCus['merchant_id'] ?? 0;
                        $customerId = $merchantCus['customer_id'] ?? 0;
                    }
                    if (self::checkApplyMerchant($merchantId)) {
                        return;
                    }
                } else {
                    $merchantCustomerInfo = json_decode(curlPost(env('szmzwz.customer_id_url'), ['is_ecommerce' => 1, 'is_appstore' => 0, 'money_range' => $debtMoneyRange['money_range'], 'phone' => $userInfo->phone, 'merchant_id' => $courseInfo->merchant_id, 'is_reduce_thread_num' => 1]), true);
                    if (!empty($merchantCustomerInfo) && $merchantCustomerInfo['code'] == 200) {
                        $merchantId = $merchantCustomerInfo['data']['merchant_id'] ?? 0;
                        $customerId = $merchantCustomerInfo['data']['id'] ?? 0;
                    } else {
                        $merchantId = $courseInfo->merchant_id;
                    }
                }

                if (empty($merchantId) && empty($customerId)) {
                    return;
                }
                $isCallback = 1;
                $source_id = 47;
            }
            //    echo 11111;die;
            if ($channelInfo['is_under_eighteen_apply'] == 0 && $userInfo->age_range_id == 1) {
                if ($isCallback)
                    curlPost(env('szmzwz.customer_thread_num_url'), ['is_ecommerce' => 1, 'service_id' => $customerId]);
                return;
            }
            if ($channelInfo['app_class_id'] == 9 && !empty($merchantId)) {
                $merchantInfo = Merchant::find($merchantId);
            } else {
                $merchantInfo = Merchant::find($courseInfo->merchant_id);
            }
            if (empty($merchantInfo) || $merchantInfo->is_switch == 0) {
                if ($isCallback)
                    curlPost(env('szmzwz.customer_thread_num_url'), ['is_ecommerce' => 1, 'service_id' => $customerId]);
                return;
            }
            //  var_dump($merchantInfo);die;
            $gatherInfo = GatherUserInfoModel::getFormatGatherInfo($userInfo->age_range_id, 'age_range_id');
            $ageRange = $gatherInfo['name'];
            $weightArr = isset($merchantInfo->age_range_weight_json) && !empty($merchantInfo->age_range_weight_json) ? json_decode($merchantInfo->age_range_weight_json, true) : [];
            $weight = isset($weightArr[$ageRange]) && !empty($weightArr[$ageRange]) ? $weightArr[$ageRange] : 0;
            $threadPriceInfo = Merchant::getMerchantThreadPrice($merchantInfo, $channelInfo);
            $threadPriceInfo['thread_price'] = 12;
            //    var_dump($threadPriceInfo);die;
            //$cityInfo = IpCity::getIpToCity($userInfo->login_ip);
            //var_dump($cityInfo);die;
            // var_dump($customerId);die;
            $cityInfo = null;
            $redis = get_redis();
            $redisKey = env('redis.merchant_amount_redis_v2_key') . $merchantInfo->id;
            if ($merchantInfo->is_filiale == 1 && $merchantInfo->app_class_id == 9) {
                $redisKey = env('redis.merchant_amount_redis_v2_key') . 248;
            }
            if (!$redis->exists($redisKey)) {
                $redis->set($redisKey, floatToInt($merchantInfo->residue_amount));
            }
            $redis->watch($redisKey);
            $merchantStore = $redis->get($redisKey);
            $threadPrice = $userInfo->is_test == 1 ? 0 : floatToInt($threadPriceInfo['thread_price']);
            if ($merchantStore < $threadPrice) {
                if ($isCallback)
                    curlPost(env('szmzwz.customer_thread_num_url'), ['is_ecommerce' => 1, 'service_id' => $customerId]);
                return;
            }
            $redis->multi();
            $redis->decrBy($redisKey, $threadPrice);
            $result = $redis->exec();
            if ($result) {
                if ($channelInfo['app_class_id'] != 9 || $customerId == 0) {
                    $customerId = (new CustomerService)->getCustomerServiceId($merchantInfo->id, 0, $userInfo->channel);
                }
                //$debtMoneyRange = self::getDebtMoneyRange($uid,$channelInfo['app_class_id']);
                $ret = self::create([
                    'uid' => $uid,
                    'course_id' => $courseInfo->id,
                    'part_course_id' => isset($part_course_id) && !empty($part_course_id) ? $part_course_id : $partCourseId,
                    'learn_course_id' => $learn_course_id ?? 0,
                    'entry_fee' => $courseInfo->entry_fee,
                    'merchant_id' => $merchantInfo->is_filiale ? 248 : $merchantInfo->id,
                    'customer_id' => $customerId ?? 0,
                    'province' => $cityInfo['province_name'] ?? '',
                    'city' => $cityInfo['city_name'] ?? '',
                    'age' => $ageRange,
                    'channel' => $userInfo->channel,
                    'store' => $channelInfo['store'],
                    'thread_price' => $threadPriceInfo['thread_price'],
                    'thread_price_type' => $threadPriceInfo['thread_price_type'],
                    'thread_price_origin' => $threadPriceInfo['thread_price'],
                    'channel_id' => $channelInfo['channel_id'],
                    'app_id' => $channelInfo['app_id'],
                    'app_class_id' => $channelInfo['app_class_id'],
                    'landing_page_id' => $landing_page_id ?? 0,
                    'thread_type' => isset($merchantInfo->is_jump_miniprogram) ? ($merchantInfo->is_jump_miniprogram > 0 ? 3 : 1) : 0,
                    'is_many_organization' => $channelInfo['is_many_organization'],
                    'is_search_plan' => $userInfo->is_search_plan,
                    'is_free_try' => $merchantInfo->is_free_try,
                    'is_test' => $userInfo->is_test,
                    'age_id' => $userInfo->age_range_id,
                    'merchant_admin_id' => $merchantInfo->admin_ids,
                    'front_sale_id' => $merchantInfo->front_sale_id,
                    'cost_price' => $channelInfo['cost_price'],
                    'is_origin' => 1,
                    'source' => $channelInfo['source_id'],
                    'app_version' => $app_version ?? '',
                    'debt_range' => '',
                    'money_range' => '',
                    'overdue_time' => '',
                    'day_channel_id' => date('Ymd') . $channelInfo['channel_id'],
                    'is_zero_capital_landing_page' => $courseInfo->entry_fee > 0 ? 1 : 0,
                    'source_id' => $source_id ?? 0,
                    'is_register' => 1,
                ]);
                if ($merchantInfo->is_filiale) {
                    $merchantInfo = Merchant::find(248);
                }
                if ($ret) {
                    Event::trigger('ApplySuccessAfter', [
                        'merchant' => $merchantInfo,
                        'thread' => [
                            'uid' => $userInfo->id,
                            'customerId' => $customerId,
                            'thread_price' => $threadPriceInfo['thread_price'],
                            'threadId' => $ret->id
                        ]
                    ]);
                    event('ApplyThreadSuccessAfter', ['threadId' => $ret->id]);
                    $oppoConfig = Config::load('extra/oppo', 'extra');
                    $platformAttributionCollbackChannel = $oppoConfig['platformAttributionCollbackChannel'];
                    $isOppoVivoChannel = 0;
                    if (
                        $channelInfo['channel_name'] == 'yqzwgj_vivo' || ($channelInfo['channel_name'] == 'dkyqcl_vivo' && ($userInfo->zhaiwu_leixing == 2 || $userInfo->zhaiwu_leixing == 4)) ||
                        ($channelInfo['channel_name'] == 'yqzw_oppo' && ($userInfo->zhaiwu_leixing == 2 || $userInfo->zhaiwu_leixing == 4)) ||
                        ($channelInfo['channel_name'] == 'yqjjcs_oppo' && ($userInfo->zhaiwu_leixing == 2 || $userInfo->zhaiwu_leixing == 4))
                    )
                        $isOppoVivoChannel = 1;
                    if (($channelInfo['app_class_id'] == 9 && (($isOppoVivoChannel && $userInfo->zhaiwu_monney != 7) || !$isOppoVivoChannel)) || in_array($userInfo->channel, $platformAttributionCollbackChannel)) {
                        $callBackData = [
                            'user' => $userInfo,
                            'dataType' => 'pay',
                        ];
                        // if ($userInfo->is_test == 0 && $userInfo->age_range_id > 1) {
                        if ($userInfo->is_test == 0) {
                            /* event('UserCallbackRecord', $callBackData);//广告主回传
                             if ($userInfo->channel == 'lmgdyq_douyin') {
                                 $callBackData = [
                                     'user' => $userInfo,
                                     'dataType' => 'submit',
                                 ];
                                 event('UserCallbackRecord', $callBackData);//广告主回传
                             }*/
                        }
                    }
                    return;
                }
                $redis->incrBy($redisKey, $threadPrice);
                if ($isCallback)
                    curlPost(env('szmzwz.customer_thread_num_url'), ['is_ecommerce' => 1, 'service_id' => $customerId]);
                return;
            }
            if ($isCallback)
                curlPost(env('szmzwz.customer_thread_num_url'), ['is_ecommerce' => 1, 'service_id' => $customerId]);
            return;
        } catch (\Exception $e) {
            // new Exception($e->getMessage().'---'.$e->getLine().'----'.$e->getFile());
        }
    }

    //是否已报名
    public static function checkApplyCourse($courseId = 0)
    {
        if (!isset($GLOBALS['uid'])) {
            return 0;
        }
        $checkApplyCourse = self::where('uid', $GLOBALS['uid'])->where('course_id|part_course_id', $courseId)->whereDay('create_time')->count();
        if (!$checkApplyCourse) {
            $beforeTime = strtotime(date('Y-m-d H:i:s', strtotime('-7 days')));
            $userInfo = UserList::where('id', $GLOBALS['uid'])->field('id,channel,channel_id')->find();
            $applyThreadCount = self::where('uid', $GLOBALS['uid'])->where('channel_id', $userInfo->channel_id)->where('create_time', '>', $beforeTime)->count();
            $checkApplyCourse = $applyThreadCount ? 1 : 0;
        }
        return $checkApplyCourse ? 1 : 0;
    }

    //是否已报名商户
    public static function checkApplyMerchant($merchantId = 0)
    {
        if (!isset($GLOBALS['uid'])) {
            return 0;
        }
        $checkApplyCourse = self::where('uid', $GLOBALS['uid'])->where('merchant_id', $merchantId)->whereDay('create_time')->count();
        return $checkApplyCourse ? 1 : 0;
    }

    //是否已报名（兼职课程1）
    public static function checkApplyJobCourse($merchantId = 0)
    {
        if (!isset($GLOBALS['uid'])) {
            return 0;
        }
        $checkApplyCourse = 0;
        $merchantInfo = Merchant::where('id', $merchantId)->field('id,is_source')->find();
        if (!empty($merchantInfo) && $merchantInfo->is_source == 2) {
            $checkApplyCourse = self::where('uid', $GLOBALS['uid'])->where('merchant_id', $merchantId)->count();
        }
        return $checkApplyCourse ? 1 : 0;
    }

    //获取已报名课程客服二维码
    public static function getApplyQrCode($params = [])
    {
        extract($params);
        $qrcode_image = '';
        $merchant = [];
        //$customerId = self::where('uid', $GLOBALS['uid'])->where('course_id', $course_id)->order('id desc')->value('customer_id');
        if (strstr($GLOBALS['uid'], ',')) {
            $uid = substr($GLOBALS['uid'], 0, strrpos($GLOBALS['uid'], ','));
            $model = new ThreadExternal();
        } else {
            $uid = $GLOBALS['uid'];
            $model = new Thread();
        }
        $where[] = ['uid', '=', $uid];
        $where[] = ['course_id|part_course_id', '=', $course_id];
        $customer = $model::where($where)->order('id desc')->field('customer_id,merchant_id')->find();
        if (empty($customer)) {
            $customer = self::getApplyPartQrCode();
        }
        if (!empty($customer['customer_id'])) {
            $qrcode_image = Customer::where('id', $customer['customer_id'])->value('qr_code');
        }
        if (!empty($customer['merchant_id'])) {
            $merchant = Merchant::where('id', $customer['merchant_id'])->field('customer_qrcode_explain,customer_explain_status')->find();
        }
        $data['qrcode_explain'] = isset($merchant['customer_qrcode_explain']) && !empty($merchant['customer_qrcode_explain']) ? json_decode($merchant['customer_qrcode_explain']) : [];
        $data['explain_status'] = $merchant['customer_explain_status'] ?? 0;
        $data['qrcode_image'] = !empty($qrcode_image) ? (strpos($qrcode_image, 'https') !== false ? $qrcode_image : str_replace('http', 'https', $qrcode_image)) . '?x-oss-process=image/resize,m_fill,w_240,h_240' : '';
        $data['top_process_desc'] = '找对律师，快速处理';
        $data['process_desc'] = ['延后还款', '减免息费', '专业诉调', '维护权益', '马上回款', '专业顾问'];
        $data['warm_reminder'] = '金牌律师1对1解决您的问题';

        if (empty($qrcode_image)) {
            CustomerQrcodeLog::create([
                'content' => json_encode(['uid' => $uid, 'course_id' => $course_id, 'merchant_id' => $customer['merchant_id']]),
            ]);
        }
        return $data;
    }
    //获取客服获客链接
    public static function getCustomerLink($params = [], $uid = 0)
    {
        extract($params);
        $customerLink = '';
        $threadId = 0;
        $isWecomQrcode = 0;
        Log::info('获客链接');
        if (\app\lib\api\other\CourseJumpWx::getCourseJumpWxStatus($course_id ?? 0, '', $GLOBALS['uid'] ?? $uid)) {
            echo 11111;
            $channelId = UserList::where('id', $GLOBALS['uid'] ?? $uid)->value('channel_id');
            $isCustomerLink = Channel::where('id', $channelId)->value('is_customer_link');
            // var_dump($isCustomerLink);
            if ($isCustomerLink) {
                if (isset($course_id) && !empty($course_id)) {
                    $where[] = ['uid', '=', $GLOBALS['uid'] ?? $uid];
                    $where[] = ['course_id|part_course_id', '=', $course_id];
                    $customer = self::where($where)->order('id desc')->field('id,customer_id,merchant_id,is_wecom_qrcode')->find();
                    // var_dump($customer);
                    if (empty($customer)) {
                        $customer = self::where('uid', $GLOBALS['uid'] ?? $uid)->order('id desc')->field('id,customer_id,merchant_id,is_wecom_qrcode')->find();//self::getApplyPartQrCode();
                    }
                    $customerInfo = customer::where('id', $customer['customer_id'] ?? 0)->find();
                    if (!empty($customerInfo)) {
                        $isCustomerLink = Merchant::where('id', $customerInfo->merchant_id)->value('is_customer_link');
                        if ($isCustomerLink == 1) {
                            $threadId = $customer['id'] ?? 0;
                            $customerLink = $customerInfo['customer_link'];
                        }
                        $isWecomQrcode = $customer['is_wecom_qrcode'] ?? 0;
                    }
                    //    var_dump($customerLink);

                }
                if (isset($thread_id) && !empty($thread_id)) {
                    $threadInfo = self::find($thread_id);
                    if (!empty($threadInfo)) {
                        $customerInfo = customer::where('id', $threadInfo->customer_id)->find();
                        if (!empty($customerInfo)) {
                            $isCustomerLink = Merchant::where('id', $customerInfo->merchant_id)->value('is_customer_link');
                            if ($isCustomerLink == 1) {
                                $customerLink = $customerInfo['customer_link'];
                                $threadId = $threadInfo['id'];
                            }
                        }
                    }
                }
            }
        }
        $customerLink = trim($customerLink);
        return ['is_wecom_qrcode' => $isWecomQrcode ?? 0, 'customer_link' => filter_var($customerLink, FILTER_VALIDATE_URL) !== false ? 'weixin://biz/ww/profile/' . $customerLink . '?customer_channel=WORK' . '#thread_id=' . $threadId : ''];
    }

    //已报名兼职获取客服二维码
    public static function getApplyPartQrCode()
    {
        $threadModel = new \app\model\api\Thread();
        $name = $threadModel->getName();
        $tableName = env('database.prefix') . $name;
        //$threadCount = $threadModel->where('uid',$GLOBALS['uid'])->where('is_discern_qrcode',0)->count();
        $threadCount = $threadModel->whereExists(function ($query) use ($tableName) {
            $merchantTableName = (new \app\model\api\Merchant())->getName();
            $query = $query->table(env('database.prefix') . $merchantTableName)->where(env('database.prefix') . $merchantTableName . '.id=' . $tableName . '.merchant_id');
            $query->where('is_source', 2);
            return $query;
        })
            ->where('uid', $GLOBALS['uid'])
            ->where('is_discern_qrcode', 0)
            ->count();
        if ($threadCount > 0) {
            $customer = $threadModel->whereExists(function ($query) use ($tableName) {
                $merchantTableName = (new \app\model\api\Merchant())->getName();
                $query = $query->table(env('database.prefix') . $merchantTableName)->where(env('database.prefix') . $merchantTableName . '.id=' . $tableName . '.merchant_id');
                $query->where('is_source', 2);
                return $query;
            })
                ->where('uid', $GLOBALS['uid'])
                ->where('is_discern_qrcode', 0)
                ->order('id desc')
                ->field('id,customer_id,merchant_id')
                ->find();
        } else {
            $customer = $threadModel->where('uid', $GLOBALS['uid'])->field('id,customer_id,merchant_id')->order('id desc')->find();
        }
        return $customer;
    }

    //获取已报名课程客服二维码多机构
    public static function getApplyQrCodeMore($params = [])
    {
        self::getApplyQrCode($params);
    }
    //莓茶商户补量
    public static function mcMerchantFillAmount($threadInfo)
    {
        if (!empty($threadInfo)) {
            if ($threadInfo->app_class_id == 17 && $threadInfo->is_fill_amount == 1) { //莓茶类目
                $redis = get_redis();
                $merchant = Merchant::find($threadInfo->merchant_id);
                $merchant->residue_amount -= $threadInfo->thread_price;
                if ($merchant->save() !== false) {
                    $redisKey = env('redis.merchant_amount_redis_v2_key') . $threadInfo->merchant_id;
                    if (!$redis->exists($redisKey)) {
                        $redis->set($redisKey, floatToInt($merchant->residue_amount));
                    }
                    $redis->watch($redisKey);
                    $redis->multi();
                    $redis->decrBy($redisKey, floatToInt($threadInfo->thread_price));
                    $result = $redis->exec();
                }
            }
        }
    }
    //长按识别二维码
    public static function discernQrCode($params = [])
    {
        extract($params);
        $userInfo = UserList::where('id', $GLOBALS['uid'])->find();
        $threadInfo = self::where('uid', $GLOBALS['uid'])->where('course_id|part_course_id', $course_id)->field('id')->find();
        $merchantId = Course::where('id', $course_id)->value('merchant_id');
        if (empty($threadInfo)) {
            $threadInfo = self::where('uid', $GLOBALS['uid'])->where('merchant_id', $merchantId)->order('id desc')->find();
            if (!empty($threadInfo)) {
                $threadInfo->is_discern_qrcode = 1;
                $threadInfo->is_real_qrcode = 1;
                $threadInfo->save();
                //self::mcMerchantFillAmount($threadInfo);
            }
        } else {

            self::where('uid', $GLOBALS['uid'])->where('course_id|part_course_id', $course_id)->update(['is_discern_qrcode' => 1, 'is_real_qrcode' => 1]);
            //$threadInfo = self::where('uid', $GLOBALS['uid'])->where('course_id', $course_id)->order('id desc')->find();
            /*$threadInfo->is_discern_qrcode = 1;
            $threadInfo->is_real_qrcode = 1;
            $threadInfo->save();
            self::mcMerchantFillAmount($threadInfo);*/
        }
        if (($merchantId == 142 || $merchantId == 195 || $merchantId == 177 || $merchantId == 185 || $merchantId == 229 || $merchantId == 242 || $merchantId == 245 || $merchantId == 246 || $merchantId == 251) && $userInfo->is_test == 0) {
            ThreadExternal::where('wm_uid', $GLOBALS['uid'])->where('course_id|part_course_id', $course_id)->update(['is_discern_qrcode' => 1, 'is_real_qrcode' => 1]);
        }
        $oppoConfig = Config::load('extra/oppo', 'extra');
        if (in_array($userInfo->channel, $oppoConfig['payWechatChannel'])) {
            $callBackData = [
                'user' => $userInfo,
                'dataType' => 'pay',
            ];
            // if ($userInfo->is_test == 0 && $userInfo->age_range_id > 1) {
            if ($userInfo->is_test == 0) {
                event('UserCallbackRecord', $callBackData);//广告主回传
            }
        }
        return;
    }
    //免费报名
    public static function freeApplyCourse($params = [])
    {
        try {
            extract($params);
            $part_course_id = (isset($part_course_id) && !empty($part_course_id)) ? $part_course_id : ((isset($part_courser_id) && !empty($part_courser_id)) ? $part_courser_id : 0);
            if (isset($part_course_id) && !empty($part_course_id)) {
                $isAllowApply = Course::where('id', $part_course_id)->where('course_type', 1)->value('is_allow_apply');
                if ($isAllowApply === 0) {
                    new Exception('请注意接听电话');
                }

            }
            if (isset($learn_course_id) && !empty($learn_course_id)) {
                $learnThread = Thread::where('uid', $GLOBALS['uid'])->where('learn_course_id', $learn_course_id)->count();
                if ($learnThread) {
                    new Exception('请勿重复报名，法务老师会尽快联系你');
                }
            }

            $userInfo = UserList::find($GLOBALS['uid']);
            if (in_array($userInfo->channel, ['lmgdyq_huawei', 'zwhkyh_huawei', 'dkyqcl_huawei'])) {
                $countThread = self::where('uid', $GLOBALS['uid'])->whereDay('create_time')->count();
                if ($countThread >= 3) {
                    new Exception('请勿重复报名，法务老师会尽快联系你');
                }
            }
            $channelInfo = Channel::getChannelAppClass($userInfo->channel);
            if ($channelInfo['app_class_id'] != 9) {
                if (self::checkApplyCourse($course_id)) {
                    new Exception('该课程已报名过');
                }
            }
            $courseInfo = Course::find($course_id);
            if (empty($courseInfo)) {
                new Exception('名额已满');
            }
            $isCallback = 0;
            $partCourseId = 0;
            $merchantId = 0;
            $customerId = 0;
            $isOverdueRedis = 0;
            $redis = get_redis();
            //$userInfo = UserList::find($GLOBALS['uid']);
            //$channelInfo= Channel::getChannelAppClass($userInfo->channel);
            $debtMoneyRange = self::getDebtMoneyRange($GLOBALS['uid'], $channelInfo['app_class_id']);
            if ($channelInfo['app_class_id'] == 9) {
                $channelCus = OverdueAppCustomer::where('channel_id', $channelInfo['channel_id'])->find();
                if (!empty($channelCus)) {
                    $merchantCus = (new CustomerServiceOverdueChannel)->getCustomerServiceId($channelInfo['channel_id']);
                    if ($merchantCus['customer_id'] && $merchantCus['merchant_id']) {
                        $merchantId = $merchantCus['merchant_id'] ?? 0;
                        $customerId = $merchantCus['customer_id'] ?? 0;
                    }
                    if (self::checkApplyMerchant($merchantId)) {
                        new Exception('该课程已报名过');
                    }
                } else {
                    $redisOverdueKey = env('redis.OVERDUE_MERCHANT_CUSTOMER_REDIS_KEY') . $channelInfo['app_class_id'];
                    $redisData = $redis->get($redisOverdueKey);
                    $isAppointMerchant = appointMerchantChannel($userInfo->channel ?? '');
                    if (!$redis->exists($redisOverdueKey) || empty($redisData) || $isAppointMerchant) {
                        $merchantCustomerInfo = json_decode(curlPost(env('szmzwz.customer_id_url'), ['is_media' => 0, 'is_appstore' => 1, 'money_range' => $debtMoneyRange['money_range'], 'phone' => $userInfo->phone, 'merchant_id' => $courseInfo->merchant_id, 'is_test' => $userInfo->is_test ?? 0, 'is_appoint_merchant' => $isAppointMerchant ?? 0, 'is_reduce_thread_num' => 1]), true);
                        if (!empty($merchantCustomerInfo) && $merchantCustomerInfo['code'] == 200) {
                            $merchantId = $merchantCustomerInfo['data']['merchant_id'] ?? 0;
                            $customerId = $merchantCustomerInfo['data']['id'] ?? 0;
                        } else {
                            $merchantId = $courseInfo->merchant_id;
                        }
                    } else {
                        $redisData = json_decode($redisData, true);
                        $redisData['merchant_id'] = $redisData['merchant_id'] ?? 0;
                        $redisData['customer_id'] = $redisData['customer_id'] ?? 0;
                        $redisData['is_use'] = $redisData['is_use'] ?? 0;
                        $merchantCustomerInfo = json_decode(curlPost(env('szmzwz.customer_id_url'), ['is_media' => 0, 'is_appstore' => 1, 'money_range' => $debtMoneyRange['money_range'], 'phone' => $userInfo->phone, 'merchant_id' => $redisData['merchant_id'], 'is_test' => $userInfo->is_test ?? 0, 'is_appoint_merchant' => 1, 'is_reduce_thread_num' => 1]), true);
                        if ($redisData['merchant_id'] && $redisData['is_use'] == 0) {
                            $merchantId = $redisData['merchant_id'] ?? 0;
                            if (!empty($merchantCustomerInfo) && $merchantCustomerInfo['code'] == 200) {
                                $customerId = $merchantCustomerInfo['data']['id'] ?? 0;
                            }
                        } else {
                            if (!empty($merchantCustomerInfo) && $merchantCustomerInfo['code'] == 200) {
                                $merchantId = $merchantCustomerInfo['data']['merchant_id'] ?? 0;
                                $customerId = $merchantCustomerInfo['data']['id'] ?? 0;
                            } else {
                                $merchantId = $courseInfo->merchant_id;
                            }
                        }
                        $isOverdueRedis = 1;
                    }
                }

                if (empty($merchantId) && empty($customerId)) {
                    new Exception('名额已满');
                }
                $isCallback = 1;
                if ($merchantId == 142)
                    $source_id = 33;
                if ($merchantId == 195)
                    $source_id = 34;
                if ($merchantId == 229)
                    $source_id = 35;
                if ($merchantId == 242)
                    $source_id = 28;
                if ($merchantId == 245)
                    $source_id = 42;
                if ($merchantId == 246)
                    $source_id = 45;
                if ($merchantId == 252)
                    $source_id = 55;
                if ($merchantId == 258)
                    $source_id = 62;
                if ($merchantId == 259)
                    $source_id = 67;
                if ($merchantId == 177)
                    $source_id = 50;
                if ($merchantId == 251)
                    $source_id = 56;
                //                if (self::checkApplyMerchant($merchantId)) {
//                    new Exception('请勿重复报名，法务老师会尽快联系你' . $merchantId);
//                }
            }
            //借贷商户
            if ($channelInfo['app_class_id'] == 26) {
                $merchantCustomerInfo = json_decode(curlPost(env('szmzwz.ZHIYUN_MERCHANT_CUSTOMER_ID_URL'), ['is_media' => 0, 'is_appstore' => 1, 'phone' => $userInfo->phone, 'merchant_id' => $courseInfo->merchant_id, 'is_test' => $userInfo->is_test ?? 0]), true);
                if (!empty($merchantCustomerInfo) && $merchantCustomerInfo['code'] == 200) {
                    $merchantId = $merchantCustomerInfo['data']['merchant_id'] ?? 0;
                    $customerId = $merchantCustomerInfo['data']['id'] ?? 0;
                } else {
                    $merchantId = $courseInfo->merchant_id;
                }
                $source_id = 3;
            }

            if ($channelInfo['is_under_eighteen_apply'] == 0 && $userInfo->age_range_id == 1) {
                if ($isCallback)
                    curlPost(env('szmzwz.customer_thread_num_url'), ['is_media' => $data['is_media'] ?? 0, 'service_id' => $customerId, 'is_test' => $userInfo->is_test ?? 0]);
                new Exception('该课程仅对符合年龄的人群开放');
            }
            //  file_put_contents('./post.txt', json_encode($params), FILE_APPEND );

            if ($courseInfo->course_type > 0) {
                $partCourseId = $course_id;
                //$merchantService = new MerchantServiceJob();
                //$merchantList = $merchantService::getMerchantIsPayCount($channelInfo);
                //$merchantId = $merchantService::sortMerchantList($merchantList,$channelInfo,$courseInfo->entry_fee);
                $partMerchantService = new PartMerchantService();
                $applyMerchant = $partMerchantService->getPartMerchantId($course_id, $channelInfo);
                $merchantId = $applyMerchant['merchant_id'];
                if (self::checkApplyJobCourse($merchantId)) {
                    new Exception('报名成功');
                }
                $merchantInfo = Merchant::find($merchantId);
                $courseInfo = Course::where('merchant_id', $merchantId)->where('course_type', 0)->find();
                if (empty($courseInfo)) {
                    new Exception('该课程已结束报名a' . $merchantId);
                }
            } else {
                if (($channelInfo['app_class_id'] == 9 || $channelInfo['app_class_id'] == 26) && !empty($merchantId)) {
                    $merchantInfo = Merchant::find($merchantId);
                } else {
                    $merchantInfo = Merchant::find($courseInfo->merchant_id);
                }
            }
            $appMerchantChannelInput = appMerchantChannelInput($userInfo->channel);
            if (empty($merchantInfo) || ($merchantInfo->is_switch == 0 && !$appMerchantChannelInput)) {
                if ($isCallback)
                    curlPost(env('szmzwz.customer_thread_num_url'), ['is_media' => $data['is_media'] ?? 0, 'service_id' => $customerId, 'is_test' => $userInfo->is_test ?? 0]);
                new Exception('该课程已结束报名1' . $merchantId);
            }
            /*if ($courseInfo->entry_fee > 0) {
                if ($merchantInfo['capital_landing_page_share_merchant_ratio1'] <= 0 || $merchantInfo['capital_landing_page_share_merchant_ratio2'] <= 0) {
                    new Exception('该课程需要先支付后报名');
                }
            }*/
            //$userInfo = UserList::find($GLOBALS['uid']);
            //$channelInfo = Channel::getChannelAppClass($userInfo->channel);
            if ($channelInfo['is_many_organization'] == 3) {
                $checkApplyMerchant = self::where('uid', $GLOBALS['uid'])->where('merchant_id', $merchantInfo->id)->where('is_many_organization', 3)->count();
                if ($checkApplyMerchant >= 3) {
                    if ($isCallback)
                        curlPost(env('szmzwz.customer_thread_num_url'), ['is_media' => $data['is_media'] ?? 0, 'service_id' => $customerId, 'is_test' => $userInfo->is_test ?? 0]);
                    new Exception('名额已满');
                }
            }

            $gatherInfo = GatherUserInfoModel::getFormatGatherInfo($userInfo->age_range_id, 'age_range_id');
            $ageRange = $gatherInfo['name'];
            $weightArr = isset($merchantInfo->age_range_weight_json) && !empty($merchantInfo->age_range_weight_json) ? json_decode($merchantInfo->age_range_weight_json, true) : [];
            $weight = isset($weightArr[$ageRange]) && !empty($weightArr[$ageRange]) ? $weightArr[$ageRange] : 0;
            if ($weight <= 0) {
                file_put_contents('./age.txt', json_encode($weightArr));
                if ($isCallback)
                    curlPost(env('szmzwz.customer_thread_num_url'), ['is_media' => $data['is_media'] ?? 0, 'service_id' => $customerId, 'is_test' => $userInfo->is_test ?? 0]);
                new Exception('你不满足报名要求');
            }
            $cityInfo = IpCity::getIpToCity();
            $threadPriceInfo = Merchant::getMerchantThreadPrice($merchantInfo, $channelInfo);
            /*if($merchantId == 242 && (strstr($userInfo->channel, 'oppo') !== false || strstr($userInfo->channel, 'vivo') !== false)){
                $threadPriceInfo['thread_price'] = 100;
            }*/
            //$redis = get_redis();
            $redisKey = env('redis.merchant_amount_redis_v2_key') . $merchantInfo->id;
            if ($merchantInfo->is_filiale == 1 && $merchantInfo->app_class_id == 9) {
                $redisKey = env('redis.merchant_amount_redis_v2_key') . 248;
            }
            if (!$redis->exists($redisKey)) {
                $redis->set($redisKey, floatToInt($merchantInfo->residue_amount));
            }
            $redis->watch($redisKey);
            $merchantStore = $redis->get($redisKey);
            $threadPrice = $userInfo->is_test == 1 ? 0 : floatToInt($threadPriceInfo['thread_price']);
            if ($merchantStore < $threadPrice) {
                if ($isCallback)
                    curlPost(env('szmzwz.customer_thread_num_url'), ['is_media' => $data['is_media'] ?? 0, 'service_id' => $customerId, 'is_test' => $userInfo->is_test ?? 0]);
                new Exception('该课程名额已满1');
            }
            $redis->multi();
            $redis->decrBy($redisKey, $threadPrice);
            $result = $redis->exec();
            if ($result) {
                if ($channelInfo['app_class_id'] != 9 || $customerId == 0) {
                    $customerId = (new CustomerService)->getCustomerServiceId($merchantInfo->id, 0, $userInfo->channel);
                    $customerId = $channelInfo['channel_name'] == 'zmxyh_ios' ? 1941 : $customerId;
                }
                if ($merchantId == 177 || $merchantId == 251) {
                    $customerInfo = Customer::where('id', $customerId)->field('daily_intake_limit_nums,app_intake_limit_nums,increase_intake_limit_nums')->find();
                    $appThreadNum = Thread::where('customer_id', $customerId)->where('is_origin', 1)->where('is_test', 0)->whereDay('create_time')->count();
                    if ($appThreadNum > $customerInfo->app_intake_limit_nums && $customerInfo->increase_intake_limit_nums > 0) {
                        $isOrigin = 4;
                    }
                }
                //$debtMoneyRange = self::getDebtMoneyRange($GLOBALS['uid'],$channelInfo['app_class_id']);
                $ret = self::create([
                    'uid' => $GLOBALS['uid'],
                    'course_id' => $courseInfo->id,
                    'part_course_id' => isset($part_course_id) && !empty($part_course_id) ? $part_course_id : $partCourseId,
                    'learn_course_id' => $learn_course_id ?? 0,
                    'entry_fee' => $courseInfo->entry_fee,
                    'merchant_id' => $merchantInfo->is_filiale == 1 && $merchantInfo->app_class_id == 9 ? 248 : $merchantInfo->id,
                    'customer_id' => $customerId ?? 0,
                    'province' => $cityInfo['province_name'] ?? '',
                    'city' => $cityInfo['city_name'] ?? '',
                    'age' => $ageRange,
                    'channel' => $userInfo->channel,
                    'store' => $channelInfo['store'],
                    'thread_price' => $threadPriceInfo['thread_price'],
                    'thread_price_type' => $threadPriceInfo['thread_price_type'],
                    'thread_price_origin' => $threadPriceInfo['thread_price'],
                    'channel_id' => $channelInfo['channel_id'],
                    'app_id' => $channelInfo['app_id'],
                    'app_class_id' => $channelInfo['app_class_id'],
                    'landing_page_id' => $landing_page_id ?? 0,
                    'thread_type' => isset($merchantInfo->is_jump_miniprogram) ? ($merchantInfo->is_jump_miniprogram > 0 ? 3 : 1) : 0,
                    'is_many_organization' => $channelInfo['is_many_organization'],
                    'is_search_plan' => $userInfo->is_search_plan,
                    'is_free_try' => $merchantInfo->is_free_try,
                    'is_test' => $userInfo->is_test,
                    'age_id' => $userInfo->age_range_id,
                    'merchant_admin_id' => $merchantInfo->admin_ids,
                    'front_sale_id' => $merchantInfo->front_sale_id,
                    'cost_price' => $channelInfo['cost_price'],
                    'is_origin' => $isOrigin ?? 1,
                    'source' => $channelInfo['source_id'],
                    'app_version' => $app_version ?? '',
                    'debt_range' => $debtMoneyRange['debt_range'],
                    'money_range' => $debtMoneyRange['money_range'],
                    'overdue_time' => $debtMoneyRange['overdue_time'],
                    'day_channel_id' => date('Ymd') . $channelInfo['channel_id'],
                    'is_zero_capital_landing_page' => $courseInfo->entry_fee > 0 ? 1 : 0,
                    'source_id' => $source_id ?? 0,
                    'show_landing_page_id' => $show_landing_page_id ?? 0,
                    'is_special_channel_customer' => 0,
                ]);
                if ($merchantInfo->is_filiale == 1 && $merchantInfo->app_class_id == 9) {
                    $merchantInfo = Merchant::find(248);
                }
                if ($ret) {
                    if ($isOverdueRedis)
                        $redis->del($redisOverdueKey);
                    Event::trigger('ApplySuccessAfter', [
                        'merchant' => $merchantInfo,
                        'thread' => [
                            'uid' => $userInfo->id,
                            'customerId' => $customerId,
                            'thread_price' => $threadPriceInfo['thread_price'],
                            'threadId' => $ret->id
                        ]
                    ]);
                    if (($merchantInfo->is_filiale == 1) && $userInfo->is_test == 0) {
                        event('ApplyThreadSuccessAfter', ['threadId' => $ret->id]);
                    }
                    $oppoConfig = Config::load('extra/oppo', 'extra');
                    $platformAttributionCollbackChannel = $oppoConfig['platformAttributionCollbackChannel'];
                    $isOppoVivoChannel = 0;
                    if (
                        $channelInfo['channel_name'] == 'yqzwgj_vivo' || ($channelInfo['channel_name'] == 'dkyqcl_vivo' && ($userInfo->zhaiwu_leixing == 2 || $userInfo->zhaiwu_leixing == 4)) ||
                        ($channelInfo['channel_name'] == 'yqzw_oppo' && ($userInfo->zhaiwu_leixing == 2 || $userInfo->zhaiwu_leixing == 4)) ||
                        ($channelInfo['channel_name'] == 'yqjjcs_oppo' && ($userInfo->zhaiwu_leixing == 2 || $userInfo->zhaiwu_leixing == 4))
                    )
                        $isOppoVivoChannel = 1;
                    if (($channelInfo['app_class_id'] == 9 && (($isOppoVivoChannel && $userInfo->zhaiwu_monney != 7) || !$isOppoVivoChannel)) || in_array($userInfo->channel, $platformAttributionCollbackChannel)) {
                        $callBackData = [
                            'user' => $userInfo,
                            'dataType' => 'pay',
                        ];
                        // if ($userInfo->is_test == 0 && $userInfo->age_range_id > 1) {
                        if ($userInfo->is_test == 0) {
                            event('UserCallbackRecord', $callBackData);//广告主回传
                            if ($userInfo->channel == 'lmgdyq_douyin') {
                                $callBackData = [
                                    'user' => $userInfo,
                                    'dataType' => 'submit',
                                ];
                                event('UserCallbackRecord', $callBackData);//广告主回传
                            }
                        }
                    }
                    $customerLinkInfo = self::getCustomerLink(['thread_id' => $ret->id]);
                    return [['customer_link' => $customerLinkInfo['customer_link'] ?? '']];
                }
                $redis->incrBy($redisKey, $threadPrice);
                if ($isCallback)
                    curlPost(env('szmzwz.customer_thread_num_url'), ['is_media' => $data['is_media'] ?? 0, 'service_id' => $customerId, 'is_test' => $userInfo->is_test ?? 0]);
                new Exception('该课程名额已满2');
            }
            if ($isCallback)
                curlPost(env('szmzwz.customer_thread_num_url'), ['is_media' => $data['is_media'] ?? 0, 'service_id' => $customerId, 'is_test' => $userInfo->is_test ?? 0]);
            new Exception('该课程名额已满3');
        } catch (\Exception $e) {
            new Exception($e->getMessage() . '---' . $e->getLine() . '----' . $e->getFile());
        }
    }

    //获取已报名客服手机号
    public static function getApplyCustomerPhone($params = [])
    {
        extract($params);
        $mobile = '';
        $is_apply = 0;
        $customerId = self::where('uid', $GLOBALS['uid'])
            ->where('channel', $channel)
            ->order('id desc')
            ->field('id,customer_id,course_id')
            ->find();
        if (!empty($customerId)) {
            $is_apply = 1;
            $customerInfo = Customer::where('id', $customerId->customer_id)->field('id,login_mobiles,customer_link')->find();
        }
        $mobile = $customerInfo->login_mobiles ?? '';
        $courseJumpWx = new \app\lib\api\other\CourseJumpWx();
        $customerLinkInfo = self::getCustomerLink(['thread_id' => $customerId->id ?? 0]);
        return ['phone' => $mobile, 'is_apply' => $is_apply, 'is_jump_miniprogram' => $courseJumpWx::getCourseJumpWxStatus($customerId->course_id ?? 0, '', 0, isset($ip) ? $ip : ''), 'customer_link' => $customerLinkInfo['customer_link'] ?? ''];
    }

    //获取已报名客服手机号
    public static function setServiceWechat($params = [])
    {
        extract($params);
        if (isset($course_id) && !empty($course_id)) {
            $thread = self::where('uid', $GLOBALS['uid'])
                ->where('course_id', $course_id)
                ->where('is_discern_qrcode', 0)
                ->order('id desc')
                ->find();
            if (!empty($thread)) {
                $thread->is_service_wechat = 1;
                $thread->save();
            }
        }
        return true;
    }

    public static function getDebtMoneyRange($uid = 0, $appClassId = 0)
    {
        $overdueTimeName = '';
        $debtRangeName = $appClassId == 9 ? '信用卡' : '';
        $moneyRangeName = $appClassId == 9 ? '1万以下' : '';
        $userInfo = UserList::where('id', $uid)->field('id,app_class_id,custom_fields')->find();
        $debtRangeList = GatherUserInfoModel::where('id', 1)->find();
        $moneyRangeList = GatherUserInfoModel::where('id', 2)->find();
        $overdueTimeList = GatherUserInfoModel::where('id', 3)->find();
        $debtRangeArr = self::gatherUserInfo($debtRangeList);
        $moneyRangeArr = self::gatherUserInfo($moneyRangeList);
        $overdueTimeArr = self::gatherUserInfo($overdueTimeList);
        $customFields = explode(',', $userInfo['custom_fields']);
        $debtRange = array_values(array_intersect($debtRangeArr['gatherUserArr'], $customFields));
        $moneyRange = array_values(array_intersect($moneyRangeArr['gatherUserArr'], $customFields));
        $overdueTime = array_values(array_intersect($overdueTimeArr['gatherUserArr'], $customFields));
        if (!empty($debtRange) && isset($debtRange[0])) {
            $debtRangeIds = explode('=', $debtRange[0]);
            $debtRangeName = isset($debtRangeArr['gatherNameArr'][$debtRangeIds[1]]) ? $debtRangeArr['gatherNameArr'][$debtRangeIds[1]] : '';
        }
        if (!empty($moneyRange) && isset($moneyRange[0])) {
            $moneyRangeIds = explode('=', $moneyRange[0]);
            $moneyRangeName = isset($moneyRangeArr['gatherNameArr'][$moneyRangeIds[1]]) ? $moneyRangeArr['gatherNameArr'][$moneyRangeIds[1]] : '';
        }
        if (!empty($overdueTime) && isset($overdueTime[0])) {
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
        if (!empty($gatherUserList)) {
            $gatherInfoJson = json_decode($gatherUserList['gather_info_json'], true);
            foreach ($gatherInfoJson as $val) {
                $gatherUserArr[] = $gatherUserList['id'] . '=' . $val['id'];
                $gatherNameArr[$val['id']] = $val['name'];
            }
        }
        $data['gatherUserArr'] = $gatherUserArr;
        $data['gatherNameArr'] = $gatherNameArr;
        return $data;
    }

    /**
     * 保存用户提交的线索信息
     *
     * @param array $params
     * @return void
     */
    public static function saveFreeApply($params = [])
    {
        Log::info('保存用户提交的线索信息', $params);

        // 获取 merchant_id，未传/空字符串都视为无筛选
        $merchant_id = $params['merchant_id'] ?? null;
        extract($params);

        //请求地址
        $mmcrm_url = env('MMCRMURL.MMCRM_URL') ?? '';

        // ====================== ThinkPHP 随机查询 + 动态商户筛选 ======================
        $customerQuery = Customer::where('thread_status', 1)
            ->where('is_test', 0);

        // 只有 merchant_id 不为空、不是空字符串时，才加商户筛选
        if (!is_null($merchant_id) && $merchant_id !== '') {
            $customerQuery->where('merchant_id', $merchant_id);
        }

        // ThinkPHP 随机获取一条数据（真正可用）
        $res = $customerQuery->orderRaw('rand()')->find();

        Log::info('随机获取的客服数据111', $res ? $res->toArray() : []);
        Log::info($res ? $res->toArray() : []);
        try {
            // 用户信息
            $userInfo = UserList::where('id', $GLOBALS['uid'])->find();
            if (!$userInfo) {
                new Exception('用户信息不存在');
            }
            $userInfo = $userInfo->toArray();

            $businessType = 0;

            $channelId = $userInfo['channel_id'] ?? 0;
            $channelInfo = Channel::where('id', $channelId)->find();
            $threadPrice = $channelInfo['cost_price'] ?? 0;

            // 查询线索信息，判断是否留资
            $thread = Thread::where('uid', $GLOBALS['uid'])->where('app_id', $userInfo['app_id'])->where('channel_id', $userInfo['channel_id'])->order('id desc')->find();
            if ($thread && $thread->handling_progress !== 3) {
                $customerLink = Customer::where('id', $thread->customer_id)->find();

                return [
                    // 'customer_link' => $customerLink->customer_link ?? '',
                    'customer_link' => '',
                ];
            }

            // 更新用户信息
            $profile = [];
            foreach ($params['data'] as $key => $value) {
                $profile[$value['field']] = intval($value['value']);
            }
            UserProfile::update($profile, ['uid' => $GLOBALS['uid']]);

            $data = [];
            $threadType = 0;
            if ($userInfo['is_test'] == 1) {
                Thread::create([
                    'uid' => $GLOBALS['uid'],
                    'merchant_id' => $params['merchant_id'] ?? 0,
                    'channel' => $params['channel'] ?? '',
                    'debt_range' => $params['data'][0]['name'] ?? '',
                    'money_range' => $params['data'][1]['name'] ?? '',
                    'overdue_time' => $params['data'][2]['name'] ?? '',
                    'app_version' => $params['app_version'] ?? '',
                    'show_landing_page_id' => $params['show_landing_page_id'] ?? 0,
                    'province' => $params['province_name'] ?? '',
                    'city' => $params['city_name'] ?? '',
                    'age' => $params['age'] ?? '',
                    'channel_id' => $userInfo['channel_id'] ?? 0,
                    'app_id' => $userInfo['app_id'] ?? 0,
                    'app_class_id' => $userInfo['app_class_id'] ?? 0,
                    'landing_page_id' => $params['landing_page_id'] ?? 0,
                    'customer_id' => 0,
                    'source_id' => 0,
                    'thread_type' => $threadType
                ]);

                return [
                    // 'customer_link' => 'https://work.weixin.qq.com/ca/cawcde0708935b39de',
                    'customer_link' => '',
                ];

            }

            // 赋值随机获取的客服数据
            Log::info('随机获取的客服数据', $res ? $res->toArray() : []);
            $data = $res;

            $res = Thread::create([
                'uid' => $GLOBALS['uid'],
                'merchant_id' => $params['merchant_id'] ?? 0,
                'channel' => $params['channel'] ?? '',
                'debt_range' => $params['data'][0]['name'] ?? '',
                'money_range' => $params['data'][1]['name'] ?? '',
                'overdue_time' => $params['data'][2]['name'] ?? '',
                'app_version' => $params['app_version'] ?? '',
                'show_landing_page_id' => $params['show_landing_page_id'] ?? 0,
                'province' => $params['province_name'] ?? '',
                'city' => $params['city_name'] ?? '',
                'age' => $params['age'] ?? '',
                'channel_id' => $userInfo['channel_id'] ?? 0,
                'app_id' => $userInfo['app_id'] ?? 0,
                'app_class_id' => $userInfo['app_class_id'] ?? 0,
                'landing_page_id' => $params['landing_page_id'] ?? 0,
                'customer_id' => $data['id'] ?? 0,
                'source_id' => $sourceId ?? 0,
                'thread_type' => $threadType,
                'thread_price' => $threadPrice,
                'thread_price_origin' => $threadPrice,
            ]);

            $callBackData = [
                'user' => $userInfo,
                'dataType' => 'pay',
            ];
            event('UserCallbackRecord', $callBackData);//广告主回传

            Log::info('保存用户提交的线索信息结果', $res->toArray());
            if (!$res && $data['id']) {
                Log::info('服务器异常，稍后重试');
                new Exception('服务器异常，稍后重试');
            }

            

            $thread = Thread::where('uid', $GLOBALS['uid'])->where('app_id', $userInfo['app_id'])->where('channel_id', $userInfo['channel_id'])->order('id desc')->find();
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

            if (!$data) {
                Log::info('获取客服信息失败，稍后将会有客服联系您，请注意接听电话。');
                new Exception('获取客服信息失败，稍后将会有客服联系您，请注意接听电话。');
            }
            return [
                // 'customer_link' => $data ? $data['customer_link'] : '',
                'customer_link' => '',
            ];
        } catch (\Exception $e) {
            new Exception('服务器异常，稍后再试(' . $e->getMessage() . ')');
        }
    }

    /**
     * 根据用户线索，判断是否提交过线索，若提交过则返回客户链接
     *
     * @param [type] $params
     * @return void
     */
    public static function getMerLink($params)
    {
        $userInfo = UserList::where('id', $GLOBALS['uid'])->field('id,app_class_id,app_id,channel_id,phone,is_test')->find();
        if (!$userInfo) {
            new Exception('用户信息不存在');
        }

        $customer = '';
        // 查询线索信息，判断是否留资
        $thread = Thread::where('uid', $GLOBALS['uid'])->where('app_id', $userInfo['app_id'])->where('channel_id', $userInfo['channel_id'])->order('id desc')->find();
        if ($thread && $thread->handling_progress !== 3) {
            $customer = Customer::where('id', $thread->customer_id)->find();
        }

        if ($userInfo['is_test'] == 1) {
            // return ['customer_link' => $thread ? 'https://work.weixin.qq.com/ca/cawcde0708935b39de' : ''];
            return ['customer_link' => ''];
        }

        if (!$customer && $thread) {
            new Exception('获取客服信息失败，稍后将会有客服联系您，请注意接听电话。');
        }
        return [
            // 'customer_link' => $customer['customer_link'] ?? '',
            'customer_link' => ''
        ];
    }

    public static function curlPost($url, $body)
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($body));//设置请求体1
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($curl, CURLOPT_HEADER, false);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'POST');//使用一个自定义的请求信息来代替"GET"或"HEAD"作为HTTP请求。(这个加不加没啥影响)
        curl_setopt($curl, CURLOPT_TIMEOUT, 3);
        $data = curl_exec($curl);
        if ($data === false) {
            return false;
        } else {
            return $data;
        }
    }

    public static function saveEmploymentContract($params = [])
    {
        extract($params);
        try {
            $link = '';
            $redis = get_redis();
            // 用户信息
            $userInfo = UserList::where('id', $GLOBALS['uid'])->find();
            if (!$userInfo) {
                new Exception('用户信息不存在');
            }
            $userInfo = $userInfo->toArray();

            $app = App::where('id', $userInfo['app_id'])->find();
            // 查询线索信息，判断是否留资
            $thread = Thread::where('uid', $GLOBALS['uid'])->where('app_id', $userInfo['app_id'])->where('channel_id', $userInfo['channel_id'])->order('id desc')->find();
            if ($thread && $thread->handling_progress !== 3) {
                new Exception('请勿重复留资');
            }

            // 更新用户信息
            $profile = [];
            foreach ($params['data'] as $key => $value) {
                $profile[$value['field']] = intval($value['value']);
            }
            UserProfile::update($profile, ['uid' => $GLOBALS['uid']]);

            $merchant = Merchant::where('id', $params['merchant_id'] ?? 0)->where('residue_amount', '>', 0)->find();
            if (!$merchant) {
                new Exception('商户信息不存在');
            }

            $customer = Customer::where('merchant_id', $params['merchant_id'] ?? 0)->where('thread_status', 1)->field('id')->select();
            if ($customer)
                $customerIds = array_column($customer->toArray(), 'id');
            $ids = json_decode($redis->get('Employment_Contract_Customer_Ids'));
            $arrId = array_values(array_diff($customerIds, $ids ?? []));
            if ($arrId) {
                $link = Customer::where('id', $arrId[0])->field('id,customer_link')->find();
                if ($redis->exists('Employment_Contract_Customer_Ids')) {
                    array_push($ids, $arrId[0]);
                    $redis->set('Employment_Contract_Customer_Ids', json_encode($ids));
                } else {
                    $redis->set('Employment_Contract_Customer_Ids', json_encode([$arrId[0]]));
                }
            } else {
                $redis->del('Employment_Contract_Customer_Ids');
                $link = Customer::where('id', $customerIds[0])->field('id,customer_link')->find();
                $redis->set('Employment_Contract_Customer_Ids', json_encode([$customerIds[0]]));
            }

            //如果是测试用户
            if ($userInfo['is_test'] == 1) {
                $link['id'] = 0;
            }

            $gatherUserInfo = implode(',', array_column($params['data'], 'name'));
            Thread::create([
                'uid' => $GLOBALS['uid'],
                'merchant_id' => $params['merchant_id'] ?? 0,
                'channel' => $params['channel'] ?? '',
                'app_version' => $params['app_version'] ?? '',
                'show_landing_page_id' => $params['show_landing_page_id'] ?? 0,
                'province' => $params['province_name'] ?? '',
                'city' => $params['city_name'] ?? '',
                'age' => $params['age'] ?? '',
                'channel_id' => $userInfo['channel_id'] ?? 0,
                'app_id' => $userInfo['app_id'] ?? 0,
                'app_class_id' => $userInfo['app_class_id'] ?? 0,
                'landing_page_id' => $params['landing_page_id'] ?? 0,
                'customer_id' => $link['id'] ?? 0,
                'source_id' => 0,
                'gather_user_info' => $gatherUserInfo,
                'thread_type' => 1 #劳动仲裁
            ]);

            $merchant = \app\model\api\Merchant::where('id', $params['merchant_id'])->find();
            Merchant::update(['residue_amount' => $merchant['residue_amount'] - $merchant['thread_price_origin']], ['id' => $merchant['id']]);

            $callBackData = [
                'user' => ['channel' => $params['channel'], 'oaid' => $userInfo['oaid'], 'app_bundle_id' => $app->android_bundle_id ?? 0],
                'dataType' => 'submit',
            ];
            // 小米关键行为回传
            if (strstr($args['user']['channel'], 'xiaomi') !== false) {
                $callBackData['dataType'] = 'key_behavior';
            }
            event('UserCallbackRecord', $callBackData);//广告主回传

        } catch (\Exception $e) {
            new Exception($e->getMessage());
        }
        return ['customer_link' => $link['customer_link'] ?? ''];
    }


    public function merchant()
    {
        return $this->belongsTo('app\model\api\Merchant', 'merchant_id', 'id')->removeOption('soft_delete');
    }
    public function course()
    {
        return $this->belongsTo('app\model\api\Course', 'course_id', 'id')->removeOption('soft_delete');
    }
    public function myCourse()
    {
        return $this->belongsTo('app\model\api\MyCourse', 'course_id', 'id')->removeOption('soft_delete');
    }
    public function customer()
    {
        return $this->belongsTo('app\model\api\Customer', 'customer_id', 'id')->removeOption('soft_delete');
    }
    public function user()
    {
        return $this->belongsTo('app\model\api\UserList', 'uid', 'id')->removeOption('soft_delete');
    }
    public function myJob()
    {
        return $this->belongsTo('app\model\api\fortunecat\Course', 'part_course_id', 'id')->where('course_type', 1)->removeOption('soft_delete');
    }

    public function myCourseList()
    {
        return $this->belongsTo('app\model\api\fortunecat\Course', 'part_course_id', 'id')->where('course_type', 5)->removeOption('soft_delete');
    }
}