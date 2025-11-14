<?php

namespace app\lib\api\service;

use app\model\api\CustomerClickId;
use app\model\api\GdtMarketingClue;
use app\lib\api\tenxun\MarketingPushApi;

class H5CustomerLinkService
{
    public static function getCustomerServiceId($params = [],$channel = null)
    {
        //逾期商户销售
        $customerLinksV1 = [
            ['id' => 1,'customer_id' => 2466,'link' => 'https://work.weixin.qq.com/ca/cawcde521f40ee21f1'],
            ['id' => 2,'customer_id' => 2848,'link' => 'https://work.weixin.qq.com/ca/cawcdecdf035c173a0'],
            ['id' => 3,'customer_id' => 2458,'link' => 'https://work.weixin.qq.com/ca/cawcdeb0df7b984f32'],
            ['id' => 4,'customer_id' => 2777,'link' => 'https://work.weixin.qq.com/ca/cawcde721110781dc8'],
        ];

        //水之纪商户销售
        $customerLinksV2 = [
            ['id' => 1,'customer_id' => 3023,'link' => 'https://work.weixin.qq.com/ca/cawcdefa737ab0c309'],
            ['id' => 2,'customer_id' => 3167,'link' => 'https://work.weixin.qq.com/ca/cawcde4efae204f6a6'],
            ['id' => 3,'customer_id' => 3069,'link' => 'https://work.weixin.qq.com/ca/cawcde4aa14a55bc77'],
            ['id' => 4,'customer_id' => 3025,'link' => 'https://work.weixin.qq.com/ca/cawcdec08677ddaa13'],
            ['id' => 5,'customer_id' => 3082,'link' => 'https://work.weixin.qq.com/ca/cawcde4cace1efab6d'],
            ['id' => 6,'customer_id' => 3154,'link' => 'https://work.weixin.qq.com/ca/cawcdebf8c4ffae652'],
            ['id' => 7,'customer_id' => 3204,'link' => 'https://work.weixin.qq.com/ca/cawcde29136d18bb28'],
        ];

        $customerLinks = [
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
            ['id' => 13,'customer_id' => 1880,'link' => 'https://work.weixin.qq.com/ca/cawcdeddb35a8697df'],
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

        $redis = get_redis();
        $redisKey = 'zhenshang_szj_customer_link_service_v6';
        $dataSourceY = [];
        if(!empty($customerLinks)){
            foreach($customerLinks as $item) {
                $dataSourceY[] = [
                    'id' => $item['id'],
                    'weight' => 1,
                ];
            }
            if(!empty($dataSourceY)) {
                if (!$redis->exists($redisKey)) {  //初始化赋值123
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
            $customerKey = (new WeightService)->initData($dataSourceY);
        }
        self::setMerchantWeight($redis, $redisKey, $customerKey);
        $clickId = $params['clickId'] ?? '';
        $traceId = $params['traceid'] ?? '';
        $clickId = !empty($clickId) ? $clickId : $traceId;
        $clickInfo = CustomerClickId::where('click_id',$clickId)->find();
        if(!empty($clickId) && empty($clickInfo)){
            CustomerClickId::create([
                'channel' => $channel,
                'customer_id' => $customerLinks[$customerKey - 1]['customer_id'],
                'click_id' => $clickId
            ]);
            $gdtMarketingClue = GdtMarketingClue::where('click_id',$clickId)->order('id desc')->find();
            if(!empty($gdtMarketingClue)){
                $clueRequestParams = json_decode($gdtMarketingClue->clue_request_params,true);
                (new MarketingPushApi())->addGdtMarketingUser($clueRequestParams);
            }
        }
        $redis->set('gdt_thread_customer_id',$customerLinks[$customerKey - 1]['customer_id']);
        return $customerLinks[$customerKey - 1]['link'].'?customer_channel='.$clickId ?? 'EmptyClickId';
    }

    public static function szmCustomerServiceId($params = [])
    {
        $channel = 'yqh5_sm_jf_5';
        if(isset($params['sm_web_host']) && $params['sm_web_host'] == 'szmflow.szmfw.cn'){
            $channel = 'yqh5_sm_jf_1';
        }
        //逾期商户销售
        $customerLinks = [
            ['id' => 1,'customer_id' => 2277,'nickname'=> '高级法务顾问胡老师','link' => 'https://work.weixin.qq.com/ca/cawcde52a9774a9ff8'],
            ['id' => 2,'customer_id' => 3143,'nickname'=> '高级法务顾问左老师','link' => 'https://work.weixin.qq.com/ca/cawcde0a29d80dbe2d'],
        ];
        $redis = get_redis();
        $redisKey = 'szm_customer_link_service_v2';
        $dataSourceY = [];
        if(!empty($customerLinks)){
            foreach($customerLinks as $item) {
                $dataSourceY[] = [
                    'id' => $item['id'],
                    'weight' => 1,
                ];
            }
            if(!empty($dataSourceY)) {
                if (!$redis->exists($redisKey)) {  //初始化赋值123
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
            $customerKey = (new WeightService)->initData($dataSourceY);
        }
        self::setMerchantWeight($redis, $redisKey, $customerKey);
        $clickId = $params['clickId'] ?? '';
        $clickInfo = CustomerClickId::where('click_id',$clickId)->find();
        CustomerClickId::create([
            'channel' => $channel,
            'customer_id' => $customerLinks[$customerKey - 1]['customer_id'],
            'client_id' => $clickId
        ]);
        $gdtMarketingClue = GdtMarketingClue::where('click_id',$clickId)->order('id desc')->find();
        if(!empty($gdtMarketingClue)){
            $clueRequestParams = json_decode($gdtMarketingClue->clue_request_params,true);
            (new MarketingPushApi())->addGdtMarketingUser($clueRequestParams);
        }
        //$redis = get_redis();
        $customerList = ['nickname' => $customerLinks[$customerKey - 1]['nickname'], 'link' => $customerLinks[$customerKey - 1]['link'].'?customer_channel='.$channel];
        //$redis->set('szm_customer_nickname',json_encode($customerList));
        return $customerList;
    }

    //山之名销售昵称
    public static function szmCustomerNickname($params = [])
    {
        $customerNickname = self::szmCustomerServiceId($params);
        return $customerNickname;
    }

    //设置已分配商户
    public static function setMerchantWeight($redis, $redisKey, $customerId = 0)
    {
        $exposeInfo = $redis->hget($redisKey, $customerId);
        if (!empty($exposeInfo)) {
            $exposeInfo = json_decode($exposeInfo, true);
            $exposeInfo['period_weight'] = 0;
            $redis->hSet($redisKey, $customerId, json_encode($exposeInfo));
        }
    }

}