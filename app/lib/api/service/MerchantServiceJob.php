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
use app\model\api\Channel;

use app\model\api\AppClass;
use think\facade\Db;

class MerchantServiceJob
{

    //获取收费和付费的商户的数量
    public static function getMerchantIsPayCount($appInfo = [])
    {
        $userInfo = UserList::where('id', $GLOBALS['uid'])->field('id,age_range_id,phone,is_search_plan,channel,app_class_id')->find();
        $gatherInfo = GatherUserInfoModel::getFormatGatherInfo($userInfo->age_range_id, 'age_range_id');
        $ageRange = !empty($gatherInfo['name']) ? '"'.$gatherInfo['name'].'"' : '';
        $ageRangeText = !empty($gatherInfo['name']) ? $gatherInfo['name'] : '';
        $courseModel =  new \app\model\api\Course();
        $name = $courseModel->getName();
        $tableName = env('database.prefix') . $name;
        $merchantIdArr = null;
        if (!empty($userInfo->phone)) {
            $uidArr = UserList::where('app_class_id', $appInfo['app_class_id'])->where('phone', $userInfo->phone)->where('id','<>', $GLOBALS['uid'])->column('id');
            if (!empty($uidArr)) {
                $merchantIdArr = Thread::whereIn('uid', $uidArr)->where('app_class_id', $appInfo['app_class_id'])->column('merchant_id');
            }
        }
        $outsideMerchantCount  = $courseModel->whereExists(function ($query) use ($tableName, $ageRange,$appInfo, $userInfo,$merchantIdArr) {
            $appMerchantChannelInput = appMerchantChannelInput($userInfo->channel);
            $merchantTableName = (new \app\model\api\Merchant())->getName();
            $query = $query->table(env('database.prefix') . $merchantTableName)->where(env('database.prefix') . $merchantTableName . '.id=' .   $tableName . '.merchant_id');
            /* $query->where([['is_source','=', 2],['is_switch','=',1],['app_class_id','=',$appInfo['app_class_id']]]);
             //$query->where('is_switch', 1);
             if($appMerchantChannelInput){
                 $query->whereOr('id','in',[177,251]);
             }*/
            $appClassId = $appInfo['app_class_id'];
            $where = "(is_source = 2 and is_switch = 1 and app_class_id=$appClassId)";
            if($appMerchantChannelInput && $userInfo->app_class_id == 10 && $userInfo['age_range_id'] > 0){
                $where.= " OR (id in (177)) ";
            }
            $query->where($where);
            //  $query->where('landing_page_thread_switch', 1);
            // $query->whereFindInSet('is_many_organization', $appInfo['is_many_organization']);
            if ($userInfo['age_range_id'] > 0 ) {
                $query->where("age_range_weight_json->'$.".$ageRange."'",'>',0);
            }
            if (!empty($merchantIdArr)) {
                $query->whereNotIn('id', $merchantIdArr);
            }
            return $query;
        })
            ->where('course_type', 0)
            // ->whereFindInSet('app_ids', $appInfo['app_id'])
            ->count();
        $merchantList  = $courseModel->whereExists(function ($query) use ($tableName, $ageRange, $outsideMerchantCount, $appInfo, $userInfo, $merchantIdArr) {
            $merchantTableName = (new \app\model\api\Merchant())->getName();
            $query = $query->table(env('database.prefix') . $merchantTableName)->where(env('database.prefix') . $merchantTableName . '.id=' .   $tableName . '.merchant_id');
            /* $query->where('is_source', $outsideMerchantCount ? 2 : 1);
             $query->where('is_switch', 1);
             $query->where('app_class_id', $appInfo['app_class_id']);*/
            /*$query->where([['is_source','=', $outsideMerchantCount ? 2 : 1],['is_switch','=',1],['app_class_id','=',$appInfo['app_class_id']]]);
            $
            if($appMerchantChannelInput){
                $query->whereOr('id','in',[177,251]);
            }*/
            $appMerchantChannelInput = appMerchantChannelInput($userInfo->channel);
            $isSource = $outsideMerchantCount ? 2 : 1;
            $appClassId = $appInfo['app_class_id'];
            $where = "(is_source = $isSource and is_switch = 1 and app_class_id=$appClassId)";
            if($appMerchantChannelInput && $userInfo->app_class_id == 10 && $userInfo['age_range_id'] > 0){
                $where.= " OR (id in (177)) ";
            }
            $query->where($where);
            //$query->whereFindInSet('is_many_organization', $appInfo['is_many_organization']);
            if ($userInfo['age_range_id'] > 0 ) {
                $query->where("age_range_weight_json->'$.".$ageRange."'",'>',0);
            }
            if (!empty($outsideMerchantCount) && !empty($merchantIdArr)) {
                $query->whereNotIn('id', $merchantIdArr);
            }
            return $query;
        })
            ->with(['merchant' => function($query){
                $query->field('id,age_range_weight_json,peak_price,intervene_thread_period_num,landing_page_thread_switch,min_search_user_ratio,max_search_user_ratio');
            }])
            ->field('id,video_url,entry_fee,merchant_id')
            ->where('course_type', 0)
            //->whereFindInSet('app_ids', $appInfo['app_id'])
            ->select()
            ->toArray();
        $freeMerchantNums= 0; //免费线索商户数量
        $payMerchantNums = 0; //付费线索商户数量
        $tempFreeMerchantData = [];
        $tempPayMerchantData = [];
        foreach ($merchantList as $val) {
            $weightArr = json_decode($val['merchant']['age_range_weight_json'], true);
            $weight = isset($weightArr[$ageRangeText]) && !empty($weightArr[$ageRangeText]) ? $weightArr[$ageRangeText] : 0;
            $arr = [
                'id' => $val['merchant']['id'],
                'course_id' => $val['id'],
                'weight' => $weight > 0 ? $weight : ($userInfo['age_range_id'] <= 0 ? 1 : $weight),
                'peak_price' => $val['merchant']['peak_price'],
                'min_search_user_ratio' => $val['merchant']['min_search_user_ratio'],
                'max_search_user_ratio' => $val['merchant']['max_search_user_ratio'],
                'intervene_thread_period_num' => $val['merchant']['intervene_thread_period_num'],
                'landing_page_thread_switch' => $val['merchant']['landing_page_thread_switch']
            ];
            if ($val['entry_fee'] > 0) {
                $tempPayMerchantData[] = $arr;
                $payMerchantNums++;
            } else {
                $tempFreeMerchantData[] = $arr;
                $freeMerchantNums++;
            }
        }
        return compact("tempFreeMerchantData","tempPayMerchantData", "freeMerchantNums", "payMerchantNums","outsideMerchantCount");
    }


    //根据商户竞价计算商户的线周期数量
    public static function sortMerchantList($merchantList = [], $appInfo = [], $entryFee = 0)
    {
        extract($merchantList);
        $allMerchantListData = array_merge($tempFreeMerchantData, $tempPayMerchantData);
        if (empty($allMerchantListData)) {
            return false;
        }
        extract($appInfo);
        $key = array_column(array_values($tempFreeMerchantData), 'peak_price');
        array_multisort($key, SORT_DESC, $tempFreeMerchantData);

        $key = array_column(array_values($tempPayMerchantData), 'peak_price');
        array_multisort($key, SORT_DESC, $tempPayMerchantData);
        $allMerchantList = [];
        $count = count($tempFreeMerchantData);
        $count = $count - 1 ;
        $threadRaisePriceGrads = AppClass::where('id', $appInfo['app_class_id'])->value('thread_raise_price_grads');
        $threadRaisePriceGrads = $threadRaisePriceGrads ?? 0;
        for ($i = $count; $i >= 0; $i--) { //免费线索周期
            if ($i == $count) {
                $tempFreeMerchantData[$i]['thread_period_num'] = 1;
            } else {
                $lastThreadPeriodNum = $tempFreeMerchantData[$i + 1]['thread_period_num'];
                $lastPeakPrice = $tempFreeMerchantData[$i + 1]['peak_price'];
                $tempPriceSpread = $tempFreeMerchantData[$i]['peak_price'] - $lastPeakPrice;
                $threadPeriodNum = $threadRaisePriceGrads == 0 ? 0 : ($tempPriceSpread ==0 ? $lastThreadPeriodNum : floor($tempPriceSpread / $threadRaisePriceGrads) + $lastThreadPeriodNum);
                $tempFreeMerchantData[$i]['thread_period_num'] = $threadPeriodNum;
            }
            $allMerchantList[] = $tempFreeMerchantData[$i];
        }
        $count = count($tempPayMerchantData);
        $count = $count - 1 ;
        for ($i = $count; $i >= 0; $i--) { //付费线索周期
            if ($i == $count) {
                $tempPayMerchantData[$i]['thread_period_num'] = 1;
            } else {
                $lastThreadPeriodNum = $tempPayMerchantData[$i + 1]['thread_period_num'];
                $lastPeakPrice = $tempPayMerchantData[$i + 1]['peak_price'];
                $tempPriceSpread = $tempPayMerchantData[$i]['peak_price'] - $lastPeakPrice;
                $threadPeriodNum = $threadRaisePriceGrads == 0 ? 0 : ($tempPriceSpread ==0 ? $lastThreadPeriodNum : floor($tempPriceSpread / $threadRaisePriceGrads) + $lastThreadPeriodNum);
                $tempPayMerchantData[$i]['thread_period_num'] = $threadPeriodNum;
            }
            $allMerchantList[] = $tempPayMerchantData[$i];
        }

        foreach($allMerchantList as &$val) {
            if ($val['intervene_thread_period_num'] > 0) {
                $val['thread_period_num'] = ($val['thread_period_num'] + $val['intervene_thread_period_num']);
            }
        }
        $threadPeriodNumList = array_column($allMerchantList, 'thread_period_num', 'id');
        $redis = get_redis();
        $merchantPeriodNum = 0;
        $merchantList = $entryFee > 0 ? $tempPayMerchantData : $tempFreeMerchantData;
        $tempMerchantData = [];
        $countMerchant = count($merchantList);
        $noLandingPageCount = 0;
        foreach ($merchantList as $item => $v) {
            /*if ($countMerchant > 1 && $v['landing_page_thread_switch'] == 0) {
                unset($merchantList[$item]);
            }*/
            if ($v['landing_page_thread_switch'] == 0) {
                $noLandingPageCount++;
            }
        }
        foreach ($merchantList as $item => $v) {
            if ($countMerchant > 1 && $v['landing_page_thread_switch'] == 0 && $countMerchant != $noLandingPageCount) {
                unset($merchantList[$item]);
            }
        }
        //file_put_contents('./channel11.txt', $channel_id);
        //$channelInfo->id
        $merchantList = array_values($merchantList);
        foreach ($merchantList as $val) {
            $redisKey = env('redis.merchant_thread_period_num_app_redis_key'). $appInfo['channel_id'] .'_'. $val['id'];
            if (!$redis->exists($redisKey)) { //设置商户落地页线索周期
                $redis->hMset($redisKey, [
                    'id' => $val['id'],
                    'expose_period_num' => $threadPeriodNumList[$val['id']],
                    'residue_period_num' => $threadPeriodNumList[$val['id']],
                    'weight' =>  $val['weight'],
                ]);
                $redis->expireAt($redisKey, mktime('23',59,59, date('m'),date('d'),date('Y')));
            }
            $redisData = $redis->hGetAll($redisKey);
            if ($redisData['residue_period_num'] > 0) {
                $tempMerchantData[] = [
                    'id' => $redisData['id'],
                    'weight' =>  $redisData['weight'],
                ];
            }
            $merchantPeriodNum+=$redisData['residue_period_num'];
        }
        file_put_contents('./channelaaa.txt', json_encode($merchantList));
        if ($merchantPeriodNum <= 0) {
            foreach ($merchantList as $val) {
                $redisKey = env('redis.merchant_thread_period_num_app_redis_key'). $appInfo['channel_id'] .'_'. $val['id'];
                $redis->hMset($redisKey,[
                    'id' => $val['id'],
                    'expose_period_num' => $threadPeriodNumList[$val['id']],
                    'residue_period_num' => $threadPeriodNumList[$val['id']],
                    'weight' =>  $val['weight'],
                ]);
                $redis->expireAt($redisKey, mktime('23',59,59, date('m'),date('d'),date('Y')));
            }
            $merchantId = (new WeightService)->initData(self::getSearchUserRatio($merchantList));
        } else {
            $merchantId = (new WeightService)->initData(self::getSearchUserRatio($tempMerchantData));
        }
        return $merchantId;
    }

    public static function getSearchUserRatio($merchantList = [])
    {
        if (!empty($merchantList)) {
            $merchantIdList = array_column($merchantList, 'id');
            $isSearchPlan = UserList::where('id', $GLOBALS['uid'])->value('is_search_plan');
            $merchantData = Merchant::field('id,min_search_user_ratio,max_search_user_ratio,today_user_search_ratio')->whereIn('id', $merchantIdList)->select()->toArray();
            foreach ($merchantList as $key => $val) {
                foreach ($merchantData as $item) {
                    if ($val['id'] == $item['id']) {
                        $merchantList[$key]['min_search_user_ratio'] = $item['min_search_user_ratio'];
                        $merchantList[$key]['max_search_user_ratio'] = $item['max_search_user_ratio'];
                        $merchantList[$key]['today_user_search_ratio'] = $item['today_user_search_ratio'];
                    }
                }
            }
            $tempMerchantData = [];
            foreach ($merchantList as $t) {
                if ($isSearchPlan == 1) {
                    if ($t['min_search_user_ratio'] > 0 && $t['today_user_search_ratio'] < $t['min_search_user_ratio'] ) {
                        $tempMerchantData[] = $t;
                    }
                } else {
                    if ($t['max_search_user_ratio'] > 0 && $t['today_user_search_ratio'] > $t['max_search_user_ratio'] ) {
                        $tempMerchantData[] = $t;
                    }
                }
            }
            return !empty($tempMerchantData) ? $tempMerchantData : $merchantList;
        }
        return $merchantList;
    }
}

