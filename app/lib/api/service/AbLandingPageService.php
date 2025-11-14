<?php

namespace app\lib\api\service;
use app\lib\api\service\WeightService;
use app\model\api\h5\HorseRaceLamp;
use app\model\api\LandingPage;
use app\model\api\Course;
use think\facade\Config;
class AbLandingPageService
{
    //落地页ab方案
    public static function getAbLandingList($landingPageId = 0, $isAb = 0)
    {
        $ab = null;
        $landingPageInfo = LandingPage::find($landingPageId);
        $redis = get_redis();
        $redisKey = env('redis.landing_page_list_ab_redis_key');
        if ($isAb && !empty($landingPageInfo) && $landingPageInfo->is_abscheme && ($landingPageInfo->a_expose_num > 0 || $landingPageInfo->b_expose_num > 0) ) {
            $filterData = self::filterData($landingPageInfo, $landingPageId, $redis, $redisKey);
            extract($filterData);
            if ($sumPeriodNum > 0) {
                $tempFilterData = $redisData;
            } else {
                self::initExpose($landingPageId, $redis, $redisKey);
                $filterData = self::filterData($landingPageInfo, $landingPageId, $redis, $redisKey);
                extract($filterData);
                $tempFilterData = $redisData;
            }
            $ab = (new WeightService())->initData($tempFilterData);
            if (!empty($ab)) {
                self::setReduceExposeNum($redis, $redisKey, $landingPageId, $ab);
            }
        }
        return self::getAbLandingInfo($ab, $landingPageInfo);
    }

    public static  function getAbLandingInfo($ab, $landingPageInfo)
    {
        $data = [];
        if (!empty($landingPageInfo) && !empty($ab)) {
            $data = [
                'landing_image' => $ab == 'a' ? explode(',', $landingPageInfo->a_landing_images) : explode(',', $landingPageInfo->b_landing_images),
                'lamp_back_image' => $ab == 'a' ? $landingPageInfo->a_lamp_back_image : $landingPageInfo->b_lamp_back_image,
                'end_image' => $ab == 'a' ? $landingPageInfo->a_end_image : $landingPageInfo->b_end_image,
                'desc_image' => $ab == 'a' ? $landingPageInfo->a_desc_image : $landingPageInfo->b_desc_image,
                'lamp_font_color' => $ab == 'a' ? $landingPageInfo->a_lamp_font_color : $landingPageInfo->b_lamp_font_color,
                'video_url' => $ab == 'a' ? $landingPageInfo->a_video_url : $landingPageInfo->b_video_url,
                'is_lamp' => $ab == 'a' ? $landingPageInfo->a_is_lamp : $landingPageInfo->b_is_lamp,
                'is_ab' => $ab,
                'horse_race_lamp' => self::getHorseRaceLamp($landingPageInfo->a_is_lamp),
            ];
        }
        return $data;
    }

    public static function filterData($landingPageInfo = [], $landingPageId = 0, $redis = null, $redisKey = null)
    {
        $sumPeriodNum = 0;
        $redisData = [];
        if (!empty($landingPageInfo)) {
            if ($landingPageInfo->a_expose_num > 0) {
                $aPeriodNum = $redis->get($redisKey . 'a_' . $landingPageId);
                $sumPeriodNum += $aPeriodNum > 0 ? $aPeriodNum : 0;
                if ($aPeriodNum > 0) {
                    $redisData[] = ['id' => 'a', 'weight' => $aPeriodNum];
                }
            }
            if ($landingPageInfo->b_expose_num > 0) {
                $bPeriodNum = $redis->get($redisKey . 'b_' . $landingPageId);
                $sumPeriodNum += $bPeriodNum > 0 ? $bPeriodNum : 0;
                if ($bPeriodNum > 0) {
                    $redisData[] = ['id' => 'b', 'weight' => $bPeriodNum];
                }
            }
        }
        return compact('sumPeriodNum', 'redisData');
    }
    //初始化AB落地页曝光
    public static function initExpose($landingPageId = 0, $redis = null, $redisKey = null)
    {
        $landingPageInfo = LandingPage::find($landingPageId);
        if (!empty($landingPageInfo)) {
            if ($landingPageInfo->a_expose_num > 0) {
                $redis->set($redisKey . 'a_' . $landingPageId, $landingPageInfo->a_expose_num);
                $redis->expireAt($redisKey, mktime('23',59,59, date('m'),date('d'),date('Y')));
            }
            if ($landingPageInfo->b_expose_num > 0) {
                $redis->set($redisKey . 'b_' . $landingPageId, $landingPageInfo->b_expose_num);
                $redis->expireAt($redisKey, mktime('23',59,59, date('m'),date('d'),date('Y')));
            }
        }
    }
    //减少落地页周期数
    public static function setReduceExposeNum($redis, $redisKey, $landingPageId = 0, $ab = null)
    {
        if (!empty($ab)) {
            $exposeNum = $redis->get($redisKey . $ab .'_' . $landingPageId);
            if ($exposeNum > 0) {
                $redis->decrby($redisKey . $ab .'_' . $landingPageId , 1);
            }
        }
    }

    public static function getHorseRaceLamp($isLamp = 0)
    {
        $horseRaceLamp = [];
        if ($isLamp == 1) {
            $horseRaceLamp = HorseRaceLamp::field('nickname,phone,times')->order('times', 'asc')->select();
            if (!empty($horseRaceLamp)) {
                foreach ($horseRaceLamp as &$val) {
                    $phone_xing = substr($val->phone, 4, 4);  //获取手机号中间四位
                    $val['nickname'] = subNickname($val->nickname);
                    $val['phone'] = str_replace($phone_xing, '****', $val->phone);  //用****进行替换
                    $val['times'] = $val->times . '分钟前';
                }
            }
        }
        return $horseRaceLamp;
    }
}

