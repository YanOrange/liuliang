<?php

namespace app\lib\api\service;
use app\model\api\Customer;
use app\lib\api\service\WeightService;
use app\model\api\Merchant;
//课程报名客服分配
class CustomerServiceV7
{
    public function getChannelCustomerServiceId($data,$channel = null,$merchantId)
    {
        $serviceData = [];
        if(!checkYxyhChannel($channel)){
            return $data;
        }
        $customerServiceList = Customer::where('merchant_id', $merchantId)->where('thread_status',1)->field('id,daily_intake_limit_nums,thread_status,weight')->select();
        $redis = get_redis();
        $redisKey = env('redis.jzd_channel_customer_redis_key') . $merchantId;
        if (!empty($customerServiceList)) {
            foreach ($customerServiceList as $val) {
                $redisData = $redis->hget($redisKey, $val['id']);
                if (!$redis->exists($redisKey) || empty($redisData)) {
                    $redis->hSet($redisKey, $val['id'], json_encode([
                        'id' => $val['id'],
                        'weight' => $val['weight'],
                        'period_thread_num' => 1
                    ]));
                }
            }
            $expireTime = mktime('23', 59, 59, date('m'), date('d'), date('Y'));
            $redis->expireAt($redisKey, $expireTime);
            $list = $redis->hGetAll($redisKey);
            $channelCustomerIds = [];
            foreach($list as $key => $val){
                $arr = json_decode($val,true);
                if($arr['period_thread_num'] == 1){
                    $channelCustomerIds[] = $arr['id'];
                }
            }
            if(empty($channelCustomerIds)){
                foreach($list as $key => $val){
                    $arr = json_decode($val,true);
                    $redisData = json_decode($redis->hget($redisKey, $arr['id']),true);
                    $redisData['period_thread_num'] = 1;
                    $redis->hSet($redisKey, $arr['id'], json_encode($redisData));
                    $channelCustomerIds[] = $arr['id'];
                }
            }
            foreach($data as $item){
                if(in_array($item['id'],$channelCustomerIds)){
                    $serviceData[] = $item;
                }
            }
            if(empty($serviceData)){
                foreach($list as $key => $val){
                    $arr = json_decode($val,true);
                    $redisData = json_decode($redis->hget($redisKey, $arr['id']),true);
                    $redisData['period_thread_num'] = 1;
                    $redis->hSet($redisKey, $arr['id'], json_encode($redisData));
                    $channelCustomerIds[] = $arr['id'];
                }
            }
        }
        return !empty($serviceData) ? $serviceData : $data;

    }

    //设置客服分配周期
    public static function setCustomerPeriodNum($redis, $serviceId = 0,$merchantId)
    {
        $redisKey = env('redis.jzd_channel_customer_redis_key') . $merchantId;
        $exposeInfo = $redis->hget($redisKey, $serviceId);
        if (!empty($exposeInfo)) {
            $exposeInfo = json_decode($exposeInfo, true);
            $exposeInfo['period_thread_num'] = 0;
            $redis->hSet($redisKey, $serviceId, json_encode($exposeInfo));
        }
    }
}