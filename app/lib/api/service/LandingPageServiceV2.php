<?php

namespace app\lib\api\service;
use app\lib\api\service\WeightService;
use app\model\api\LandingPage;
use app\model\api\Course;
use app\model\api\UserList;
use app\model\api\v2\GatherUserInfo as GatherUserInfoModel;
use think\facade\Config;
use app\model\api\Thread;
use app\model\api\Merchant;
use app\model\api\App;
use app\lib\api\service\MerchantServiceV2;
class LandingPageServiceV2
{
    public static function getExpose($params = [])
    {
        $landingPageId = 0;
        extract($params);
        $threadCount = Thread::where('uid', $GLOBALS['uid'])->where('channel_id', $channelInfo->id)->count();
        if ($threadCount > 0) {
            return $landingPageId;
        }
        return self::getLandingInfo($params);
    }

    //获取落地页信息
    public static function getLandingInfo($params)
    {
        extract($params);
        $merchantParam = MerchantServiceV2::getMerchantIsPayCount($params);
        extract($merchantParam);
        $redisKey = env('redis.landing_page_list_redis_key') . $appInfo->id;
        $redis = get_redis();
        $landingPageId = self::getLandingPageId($redis, $redisKey, $merchantParam, $params);
        $courseData = MerchantServiceV2::sortMerchantList($merchantParam, $params,$landingPageId);
        return compact("landingPageId", "courseData");
    }
    //获取落地页数据
    public static function getLandingPageData($redis, $redisKey, $merchantParam, $params)
    {
        if (!$redis->exists($redisKey)) { //初始化落地页赋值
            self::initExpose($redis, $redisKey, $params);
        }
        extract($merchantParam);
        //商户付费状态 1同时存在付费和免费的商户 2只存在免费商户 3只存在付费商户
        $state = ($freeMerchantNums && $payMerchantNums) ? 1 : ($freeMerchantNums ? 2 : 3);
        $temp = self::filterLandingPageData($redis, $redisKey, $state);
        extract($temp);
        if ($sumExposeNum <= 0) {
            self::initExpose($redis, $redisKey, $params, true, $state);
            $temp = self::filterLandingPageData($redis, $redisKey, $state);
        }
        return $temp;
    }
    //过滤落地页数据
    public static function filterLandingPageData($redis, $redisKey,  $state)
    {
        $landPageList = $redis->hGetAll($redisKey);
        $sumExposeNum = 0;
        $data = [];
        foreach ($landPageList as $val) {
            $arr = json_decode($val, true);
            $newData = [
                'id' => $arr['id'],
                'weight' => $arr['weight'],
            ];
            if ($state == 1) {
                if ($arr['residue_period_num'] > 0  && $arr['weight'] > 0) {
                    $data[] = $newData;
                    $sumExposeNum += $arr['residue_period_num'];
                }
            } else if ($state == 2) {
                if ($arr['residue_period_num'] > 0  && $arr['weight'] > 0 && !$arr['is_pay']) {
                    $data[] = $newData;
                    $sumExposeNum += $arr['residue_period_num'];
                }
            } else {
                if ($arr['residue_period_num'] > 0  && $arr['weight'] > 0 && $arr['is_pay']) {
                    $data[] = $newData;
                    $sumExposeNum += $arr['residue_period_num'];
                }
            }
        }
        return compact("data", "sumExposeNum");
    }
    //获取落地页id
    public static function getLandingPageId($redis, $redisKey, $merchantParam, $params)
    {
        $temp = self::getLandingPageData($redis, $redisKey, $merchantParam, $params);
        extract($temp);
        if (empty($data)) {
            return 0;
        }
        $landingPageId = (new WeightService)->initData($data);
        self::setReduceExposeNum($redis, $redisKey, $landingPageId);
        return $landingPageId;
    }
    //初始化落地页曝光
    public static function initExpose($redis, $redisKey, $params = [], $flag = false, $state = 0)
    {
        extract($params);
        $con = " 1 = 1";
        if ($state == 2 || $state == 3) {
            if ($state == 2) {
                $con.= " AND is_pay = 0";
            }
            if ($state == 3) {
                $con.= " AND is_pay = 1";
            }
        }
        $landingPageList = LandingPage::field('id,expose_period_num,is_pay,weight,is_show')->where('landing_page_type', 2)->where($con)->where('app_id', $appInfo->id)->select()->toArray();
        if (!empty($landingPageList)) {
            if (!$flag) {
                $redis->del($redisKey);
            }
            foreach ($landingPageList as $val) {
                $redis->hSet($redisKey, $val['id'], json_encode([
                    'id' => $val['id'],
                    'expose_period_num' => $val['expose_period_num'],
                    'residue_period_num' => $val['expose_period_num'],
                    'weight' =>  $val['weight'],
                    'is_pay' => $val['is_pay'],
                    'is_show' => $val['is_show'],
                ]));
            }
            $redis->expireAt($redisKey, mktime('23',59,59, date('m'),date('d'),date('Y')));
            return  $redis->hGetAll($redisKey);
        }
    }


    //减少落地页周期数
    public static function setReduceExposeNum($redis, $redisKey, $landingPageId = 0)
    {
        $exposeInfo = $redis->hget($redisKey, $landingPageId);
        if (!empty($exposeInfo)) {
            $exposeInfo = json_decode($exposeInfo, true);
            if ($exposeInfo['residue_period_num'] > 0) {
                $exposeInfo['residue_period_num'] -= 1;
                $redis->hSet($redisKey, $landingPageId, json_encode($exposeInfo));
            }
        }
    }


}

