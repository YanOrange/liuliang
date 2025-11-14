<?php

namespace app\lib\api\openapi;

use app\lib\api\city\IpCity;
use app\lib\api\exception\Exception;
use app\lib\api\service\CustomerService;
use app\model\admin\MobileGetCityinfo;
use app\model\admin\UserProfile;
use app\model\api\Channel;
use app\model\api\Course;
use app\model\api\Merchant;
use app\model\api\UserList;
use app\model\api\v2\GatherUserInfo as GatherUserInfoModel;
use app\service\admin\UserServiceFacade;
use app\validate\admin\thread\ThreadTransformation as ThreadTransformationValidate;
use laytp\library\CommonFun;
use think\facade\Db;
use think\facade\Event;
use think\facade\Request;
use app\model\api\Thread;
use app\lib\api\http\Http as HttpRequest;
class Douyin
{
    use HttpRequest;

    public function toolsClubApi()
    {
        $params = Request()->post();
        file_put_contents('douyin.txt',json_encode($params));
        try {
            extract($params);
            $merchantId = 142;
            $uid = $this->addUser($params);
            if(!$uid){
                return false;
            }
            $threadCount = Thread::where('uid',$uid)->where('merchant_id',$merchantId)->count();
            if($threadCount > 0){
                return false;
            }
            $courseInfo = Course::where('merchant_id',$merchantId)->find();
            if (empty($courseInfo) || $courseInfo->status == 0) {
                return false;
            }
            $merchantInfo = Merchant::find($merchantId);
            if (empty($merchantInfo) || $merchantInfo->is_switch == 0) {
                return false;
            }
            $userInfo = UserList::find($GLOBALS['uid']);
            $channelInfo = Channel::getChannelAppClass($userInfo->channel);

            $gatherInfo = GatherUserInfoModel::getFormatGatherInfo($userInfo->age_range_id, 'age_range_id');
            $ageRange = $gatherInfo['name'];
            $weightArr = isset($merchantInfo->age_range_weight_json) && !empty($merchantInfo->age_range_weight_json) ? json_decode($merchantInfo->age_range_weight_json, true) : [];
            $weight = isset($weightArr[$ageRange]) && !empty($weightArr[$ageRange]) ? $weightArr[$ageRange] : 0;
            if($weight <= 0){
                return false;
            }
            $cityInfo = IpCity::getIpToCity();
            $threadPriceInfo = Merchant::getMerchantThreadPrice($merchantInfo);
            $redis = get_redis();
            $redisKey = env('redis.merchant_amount_redis_v2_key'). $merchantInfo->id;
            if (!$redis->exists($redisKey)) {
                $redis->set($redisKey, floatToInt($merchantInfo->residue_amount));
            }
            $redis->watch($redisKey);
            $merchantStore = $redis->get($redisKey);
            $threadPrice = $userInfo->is_test == 1 ? 0 : floatToInt($threadPriceInfo['thread_price']);
            if ($merchantStore < $threadPrice) {
                return false;
            }
            $redis->multi();
            $redis->decrBy($redisKey, $threadPrice);
            $result = $redis->exec();
            if ($result) {
                $customerId = (new CustomerService)->getCustomerServiceId($courseInfo->merchant_id);
                $debtMoneyRange = self::getDebtMoneyRange($GLOBALS['uid']);
                $ret = self::create([
                    'uid' => $GLOBALS['uid'],
                    'course_id' => $courseInfo->id,
                    'entry_fee' => $courseInfo->entry_fee,
                    'merchant_id' => $courseInfo->merchant_id,
                    'customer_id' => $customerId ?? 0,
                    'province' => $cityInfo['province_name'] ?? '',
                    'city' => $cityInfo['city_name'] ?? '',
                    'age' => $ageRange,
                    'channel' => $userInfo->channel,
                    'store' => $channelInfo['store'],
                    'thread_price' => $threadPriceInfo['thread_price'],
                    'thread_price_type' => $threadPriceInfo['thread_price_type'],
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
                    'debt_range' => $debtMoneyRange['debt_range'],
                    'money_range' => $debtMoneyRange['money_range'],
                    'day_channel_id' => date('Ymd').$channelInfo['channel_id'],
                    'is_zero_capital_landing_page' => $courseInfo->entry_fee > 0 ? 1 : 0
                ]);
                if ($ret) {
                    Event::trigger('ApplySuccessAfter', ['merchant' => $merchantInfo, 'thread' => [
                        'uid' => $userInfo->id,'customerId' => $customerId,'thread_price' => $threadPriceInfo['thread_price'],'threadId' => $ret->id]
                    ]);
                    if($merchantInfo->id == 142 && $userInfo->is_test == 0){
                        event('ApplyThreadSuccessAfter', ['threadId' => $ret->id]);
                    }
                    if ($userInfo->channel == 'lmgdyq_oppo'){
                        $callBackData = [
                            'user' => $userInfo,
                            'dataType' => 'pay',
                        ];
                        if($userInfo->is_test == 0 && $userInfo->age_range_id > 1){
                            event('UserCallbackRecord', $callBackData);//广告主回传
                        }
                    }
                    return json_encode(['code' => 0,'message' => 'success']);
                }
                $redis->incrBy($redisKey, $threadPrice);
                return false;
            }
            return false;
        } catch (\Exception $e) {
            return false;
            //new Exception($e->getMessage().'-'.$e->getLine());
        }
    }

    public function addUser($post = [])
    {
        $validate = new ThreadTransformationValidate();
        if (!$validate->scene('addUser')->check($post)) return $this->error($validate->getError());
        $channelInfo = \app\model\admin\Channel::with(['app'])->where('id',$post['channel_id'])->find();
        $userInfo = \app\model\admin\UserList::where('phone',$post['phone'])->where('status',1)->where('channel_id',$channelInfo->id)->find();
        if(!empty($userInfo)){
            return false;
        }
        Db::startTrans();
        try {
            $cityInfo = MobileGetCityinfo::getMobileCityInfo($post['phone']);
            $gatherData = [];
            if(!empty($post['is_has_computer_id'])){
                $gatherData[] = '6='.$post['is_has_computer_id'];
            }
            if(!empty($post['is_like_games'])){
                $gatherData[] = '7='.$post['is_like_games'];
            }
            if(!empty($post['is_has_shop_id'])){
                $gatherData[] = '8='.$post['is_has_shop_id'];
            }
            if(!empty($post['is_has_dspdh_id'])){
                $gatherData[] = '9='.$post['is_has_dspdh_id'];
            }
            if(!empty($post['zhaiwu_leixing'])){
                $gatherData[] = '20='.$post['zhaiwu_leixing'];
            }
            if(!empty($post['zhaiwu_monney'])){
                $gatherData[] = '21='.$post['zhaiwu_monney'];
            }
            $data = [
                'phone' => $post['phone'],
                'nickname' => $post['nickname'],
                'wx_nickname' => $post['wx_nickname'],
                'phone_end_number' => substr($post['phone'],-4),
                'avatar' => 'https://thirdwx.qlogo.cn/mmopen/vi_32/Q3auHgzwzM5m1Qs3zFcia65qHeGlw4Oia540vgEjk1FZhlDHviatk5HC9rh6qT2jmYV5cTGQg13eJ7NAJ9DvqicVjA/132',
                'channel' => $channelInfo['channel_name'],
                'channel_id' => $channelInfo->id,
                'app_id' => $channelInfo->app_id,
                'app_class_id' => $channelInfo->app->app_class_id,
                'province' => $cityInfo['province'] ?? '',
                'city' => $cityInfo['city'] ?? '',
                'age_range_id' => $post['age_range_id'],
                'identity_id' => $post['identify_id'],
                'education_id' => $post['education_id'],
                'sex' => $post['sex'],
//                'zw_mold' => $post['zw_mold'],
//                'zw_money' => $post['zw_money'],
                'login_time' => date('Y-m-d H:i:s'),
                'custom_fields' => implode(',',$gatherData)
            ];
            $user = UserList::create($data);
            $post['uid'] = $user->id;
            $userProfile = UserProfile::create($post);
            if (!$user || !$userProfile) return false;
            Db::commit();
            return $user->id;
        } catch (\Exception $e) {
            Db::rollback();
            return false;
        }
    }
}