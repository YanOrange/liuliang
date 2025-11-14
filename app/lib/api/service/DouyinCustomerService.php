<?php

namespace app\lib\api\service;
use app\lib\api\service\WeightService;

//课程报名客服分配
class DouyinCustomerService
{
    protected $customerLinks = [
        ['id' => 1,'customer_id' => 2974,'link' => 'https://work.weixin.qq.com/ca/cawcdeaaf45e1dbbec'],
        ['id' => 2,'customer_id' => 2277,'link' => 'https://work.weixin.qq.com/ca/cawcde203d3aab0025'],
        ['id' => 3,'customer_id' => 3555,'link' => 'https://work.weixin.qq.com/ca/cawcded2c35e9863f2'],
        ['id' => 4,'customer_id' => 3670,'link' => 'https://work.weixin.qq.com/ca/cawcde8624788e1d4c'],
        ['id' => 5,'customer_id' => 3460,'link' => 'https://work.weixin.qq.com/ca/cawcded31e112d7d69'],
        ['id' => 6,'customer_id' => 3452,'link' => 'https://work.weixin.qq.com/ca/cawcde567af7f4cfd6'],
        ['id' => 7,'customer_id' => 3634,'link' => 'https://work.weixin.qq.com/ca/cawcde7468f803382d'],
        ['id' => 8,'customer_id' => 3565,'link' => 'https://work.weixin.qq.com/ca/cawcde23de90a50ba2'],
        ['id' => 9,'customer_id' => 3557,'link' => 'https://work.weixin.qq.com/ca/cawcde1a51d0188288'],
        ['id' => 10,'customer_id' => 3643,'link' => 'https://work.weixin.qq.com/ca/cawcde65250bfa137f'],
        ['id' => 11,'customer_id' => 3770,'link' => 'https://work.weixin.qq.com/ca/cawcde619c6949faa0'],
        ['id' => 12,'customer_id' => 2880,'link' => 'https://work.weixin.qq.com/ca/cawcde1aec60815e27'],
        ['id' => 13,'customer_id' => 3235,'link' => 'https://work.weixin.qq.com/ca/cawcde808d506a7647'],
        ['id' => 14,'customer_id' => 3589,'link' => 'https://work.weixin.qq.com/ca/cawcdefaf6980d19f1'],
        ['id' => 15,'customer_id' => 3769,'link' => 'https://work.weixin.qq.com/ca/cawcdeba1c02b611f1'],
        ['id' => 16,'customer_id' => 3554,'link' => 'https://work.weixin.qq.com/ca/cawcdea5cce89c652c'],
        ['id' => 17,'customer_id' => 3845,'link' => 'https://work.weixin.qq.com/ca/cawcde77adad41dd59'],
        ['id' => 18,'customer_id' => 3631,'link' => 'https://work.weixin.qq.com/ca/cawcdea702eecd054e'],
        ['id' => 19,'customer_id' => 3621,'link' => 'https://work.weixin.qq.com/ca/cawcdea18f6ebec8a2'],
        ['id' => 20,'customer_id' => 3580,'link' => 'https://work.weixin.qq.com/ca/cawcdebaae9f0d22bf'],
        ['id' => 21,'customer_id' => 3609,'link' => 'https://work.weixin.qq.com/ca/cawcded29f54b8e549'],
        ['id' => 22,'customer_id' => 3727,'link' => 'https://work.weixin.qq.com/ca/cawcdeb8a853d7fd40'],
        ['id' => 23,'customer_id' => 3665,'link' => 'https://work.weixin.qq.com/ca/cawcde0987ebeca883'],
        ['id' => 24,'customer_id' => 3692,'link' => 'https://work.weixin.qq.com/ca/cawcde75872b97a5e9'],
        ['id' => 25,'customer_id' => 3663,'link' => 'https://work.weixin.qq.com/ca/cawcdeedf9bb8de27f'],
        ['id' => 26,'customer_id' => 3344,'link' => 'https://work.weixin.qq.com/ca/cawcde89809faacfb8'],
        ['id' => 27,'customer_id' => 3790,'link' => 'https://work.weixin.qq.com/ca/cawcdee976fe606a81'],
        ['id' => 28,'customer_id' => 3389,'link' => 'https://work.weixin.qq.com/ca/cawcdec1ff94cd40b0'],
    ];

    public function getCustomerServiceId()
    {
        $redis = get_redis();
        $redisKey = env('redis.H5_DOUYIN_CUSTOMER_REDIS_KEY');
        $dataSourceY = [];
        if(!empty($customerLinks)){
            foreach($customerLinks as $item) {
                $dataSourceY[] = [
                    'id' => $item['id'],
                    'weight' => 1,
                ];
            }
            if(!empty($dataSourceY)) {
                if (!$redis->exists($redisKey)) {  //初始化赋值
                    foreach($dataSourceY as $item){
                        $dataSourceSzmZwz = [
                            'id' => $item['id'],
                            'weight' => 1,
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
                                'weight' => 1,
                                'period_weight' => 1]));
                        }
                    }
                    $dataSourceRedis = $redis->hGetAll($redisKey);
                    $dataSourceRedisY = [];
                    foreach($dataSourceRedis as $val){
                        $arr = json_decode($val, true);
                        if($arr['period_weight']){
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
                                'weight' => 1,
                                'period_weight' => 1
                            ];
                            $redis->hset($redisKey,$item['id'],json_encode($dataSourceSzmZwz));
                        }
                    }
                }
                $expireTime = mktime('23',59,59, date('m'),date('d'),date('Y'));
                $redis->expireAt($redisKey, $expireTime);
            }
            $customerId = (new WeightService)->initData($dataSourceY);
        }
        $customerIds = array_column($this->customerLinks, 'customer_id');
        $randomKey = array_rand($customerIds);
        $customerId = $customerId ?? $customerIds[$randomKey];
        self::setCustomerWeight($redis, $redisKey, $customerId);
        return $customerId;
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

    //设置已分配商户
    public static function setCustomerWeight($redis, $redisKey, $customerId = 0)
    {
        $exposeInfo = $redis->hget($redisKey, $customerId);
        if (!empty($exposeInfo)) {
            $exposeInfo = json_decode($exposeInfo, true);
            $exposeInfo['period_weight'] = 0;
            $redis->hSet($redisKey, $customerId, json_encode($exposeInfo));
        }
    }
}