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
class LandingPageService
{
    public static function getExpose($appClassid = 0, $appInfo = [], $channelId)
    {
        $landingPageId = 0;
        $appId = $appInfo['app_id'];
        $isManyOrganization = $appInfo['is_many_organization'];
        $threadCount = Thread::where('uid', $GLOBALS['uid'])->where('app_id', $appId)->count();
        //  $user = UserList::where('id', $GLOBALS['uid'])->find();
        // $isSwitch = Merchant::where('id', 14)->where('is_switch', 1)->value('is_switch');
        //var_dump($GLOBALS['uid']);die;
        //  var_dump($user->id);
        // $count = Merchant::where('is_switch', 1)->where('is_source', 2)->where('app_class_id', 3)->whereFindInSet('is_many_organization', $isManyOrganization)->count();
        // var_dump($count);
        if ($threadCount > 0) {
            return $landingPageId;
        }  /*else if($user->identity_id == 4 && $user->age_range_id > 1 && $isSwitch) {
            $course = Course::where('merchant_id', 14)->where('status', 1)->find();
            if ($course) {
                $landingPage = LandingPage::where('course_id', $course->id)->find();
                if ($landingPage) {
                    $landingPageId = $landingPage->id;
                    $redis = get_redis();
                    $redis->set(env('redis.user_landing_page_redis_key') . $GLOBALS['uid'], 14);
                }
            }
            return $landingPageId;
        } else if ($user->is_has_computer_id == 2 && in_array($user->age_range_id, [2,3,4]) && $count > 0 && in_array($user->channel, ['kuaixuepr_oppo','kuaixuepr_vivo'])) {
            return self::getLandingPageId(3,$isManyOrganization);
        }*/ else {
            return self::getLandingPageId($appClassid, $isManyOrganization, $channelId);
        }

        /*$redisKey = env('redis.landing_page_redis_key') . $appClassid;
        $redis = get_redis();
        if (!$redis->exists($redisKey)) {  //初始化赋值
            $landingPageId = self::initExpose($redis, $redisKey, $appClassid);
        } else {
            $list = $redis->hGetAll($redisKey);
            $data = [];
            $sumExposeNum = 0;
            $ageRangeId = UserList::where('id', $GLOBALS['uid'])->value('age_range_id');
            $gatherInfo = GatherUserInfoModel::getFormatGatherInfo($ageRangeId, 'age_range_id');
            $ageRange = $gatherInfo['name'];
            foreach ($list as $val) {
                $arr = json_decode($val, true);
                $weight = isset($arr['age_range_weight'][$ageRange]) && !empty($arr['age_range_weight'][$ageRange]) ? $arr['age_range_weight'][$ageRange] : 0;
                if ($arr['expose_num'] > 0 && $arr['is_show'] && $weight > 0) {
                    $data[] = [
                        'id' => $arr['id'],
                        'weight' => $weight,
                    ];
                    $sumExposeNum += $arr['expose_num'];
                }
            }
            if ($sumExposeNum <= 0) { //当所有的落地页的曝光都为0时,重新初始化
                $landingPageId = self::initExpose($redis, $redisKey, $appClassid);
            } else {
                if (!empty($data)) {
                    $randLandingPageId = (new WeightService)->initData($data);//根据权重匹配数据
                    self::setReduceExposeNum($redis, $redisKey, $randLandingPageId);
                    $landingPageId = $randLandingPageId;
                }
            }
        }
        return $landingPageId;*/
    }
    public static function getLandingPageId($appClassid, $isManyOrganization, $channelId)
    {
        $userInfo = UserList::where('id', $GLOBALS['uid'])->field('age_range_id,is_search_plan')->find();
        $landingPageList = LandingPage::with(['course'=> function($query) use($isManyOrganization){
            $query->field('id,merchant_id');
            $query->with(['merchant' => function($query) use($isManyOrganization){
                $query->field('id,age_range_weight_json,is_source,thread_period_num,search_rate,totay_thread_limit_nums');
                $query->where('is_switch', 1);
                $query->whereFindInSet('is_many_organization', $isManyOrganization);
            }]);
        }])->whereFindInSet('channel_ids', $channelId)->where('is_show', 1)->field('id,expose_num,is_show,course_id')->select();
        $landingPageID = 0;
        $ageRangeId = $userInfo->age_range_id;
        $gatherInfo = GatherUserInfoModel::getFormatGatherInfo($ageRangeId, 'age_range_id');
        $ageRange = $gatherInfo['name'];
        if (empty($ageRange)) {
            file_put_contents('./lg4.txt', $GLOBALS['uid'] .'-'.$channelId.PHP_EOL, FILE_APPEND);
            return $landingPageID;
        }
        if (empty($landingPageList)) {
            file_put_contents('./lg1.txt', $GLOBALS['uid'] .'-'.$ageRangeId.'-'.$channelId.PHP_EOL, FILE_APPEND);
        }
        $data = [];
        foreach ($landingPageList as $val) {
            if (isset($val['course']['merchant']['id'])) {
                $val['course']['merchant']['merchant_search_rate'] = 0;
                if($userInfo->is_search_plan && $val['course']['merchant']['search_rate'] > 0) {
                    //$merchantThread = Thread::where('merchant_id', $val['course']['merchant']['id'])->whereDay('create_time')->where('is_search_plan', 1)->count();
                    //获取当天线索量（不包含测试用户）--start
                    $threadModel = new Thread();
                    $threadTableName = env('database.prefix').$threadModel->getName();
                    $merchantThread = $threadModel->whereExists(function ($query) use ($threadTableName){
                        $userTableName = env('database.prefix').(new UserList())->getName();
                        $query->table($userTableName)->field('id,is_test')->where($userTableName. '.id=' .   $threadTableName . '.uid');
                        $query->where('is_test',0);
                    })
                        ->whereTime('create_time', 'today')
                        ->where('merchant_id',  $val['course']['merchant']['id'])
                        ->where('is_search_plan', 1)
                        ->count();
                    //获取当天线索量（不包含测试用户）--end
                    $val['course']['merchant']['merchant_search_rate'] = $merchantThread > 0 ? round($merchantThread / $val['course']['merchant']['totay_thread_limit_nums'], 2) * 100 : 0;
                }
                $weightArr = isset($val['course']['merchant']['age_range_weight_json']) && !empty($val['course']['merchant']['age_range_weight_json']) ? json_decode($val['course']['merchant']['age_range_weight_json'], true) : [];
                $weight = isset($weightArr[$ageRange]) && !empty($weightArr[$ageRange]) ? $weightArr[$ageRange] : 0;
                if($val['course']['merchant']['search_rate'] > 0 && $val['course']['merchant']['search_rate'] > $val['course']['merchant']['merchant_search_rate']){
                    $weight = $weight + (int)$val['course']['merchant']['merchant_search_rate'];
                }
                if ($weight > 0) {
                    $data[] = [
                        'id' => $val['id'],
                        'is_source' => $val['course']['merchant']['is_source'],
                        'thread_period_num' => $val['course']['merchant']['thread_period_num'],
                        'weight' => $weight,
                        'merchant_id' => $val['course']['merchant']['id'],
                        'search_rate' => $val['course']['merchant']['search_rate'],
                        'merchant_search_rate' => (int)$val['course']['merchant']['merchant_search_rate'],
                        'is_search_plan' => $userInfo->is_search_plan
                    ];
                }
            }
        }
        if (empty($data)) {
            file_put_contents('./lg3.txt', $GLOBALS['uid'] .'-'.$ageRangeId.'-'.$channelId.PHP_EOL, FILE_APPEND);
        }
        $ret = self::filterArr($data);
        if (empty($ret)) {
            file_put_contents('./lg2.txt', $GLOBALS['uid'] .'-'.$ageRangeId.'-'.$channelId.PHP_EOL, FILE_APPEND);
        }
        return $ret;
    }
    public static function filterArr($data)
    {
        if (empty($data)) {
            return 0;
        }
        $sourceY = 0;
        $sourceN = 0;
        $searchY = 0;
        foreach ($data as $key => $value) {
            if ($value['is_source'] == 1) {
                $sourceN++;
            }
            if ($value['is_source'] == 2) {
                $sourceY++;
            }
            if($value['is_search_plan'] == 1 && $value['search_rate'] > 0 && $value['search_rate'] > $value['merchant_search_rate']){
                $searchY++;
            }
        }
        if ($sourceY > 0 && $sourceN > 0) {
            foreach ($data as $item => $val) {
                if ($val['is_source'] == 1) {
                    unset($data[$item]);
                }
                if ($val['is_source'] == 2 && $searchY > 0){
                    if ($val['merchant_search_rate'] >= $val['search_rate']){
                        unset($data[$item]);
                    }
                }
            }
        }
        foreach ($data as $key => $value) {
            if ($value['is_source'] == 2 && $searchY > 0){
                if ($value['merchant_search_rate'] >= $value['search_rate']){
                    unset($data[$key]);
                }
            }
        }
        $redis = get_redis();
        $data = array_values($data);
        $sumThreadPeriodResidueNum = 0;
        foreach ($data as $val) {
            $redisKey = env('redis.thread_period_num_redis_key') . $val['merchant_id'];
            if (!$redis->exists($redisKey)) {
                $redis->hMset($redisKey, ['thread_period_num' => $val['thread_period_num'], 'thread_period_residue_num' => $val['thread_period_num']]);
                $redis->expireAt($redisKey, mktime('23',59,59, date('m'),date('d'),date('Y')));
            }
            $sumThreadPeriodResidueNum+= $redis->hGet($redisKey, 'thread_period_residue_num');
        }
        $newData = [];
        if ($sumThreadPeriodResidueNum > 0) {
            foreach ($data as $item) {
                $redisKey = env('redis.thread_period_num_redis_key') . $item['merchant_id'];
                if ($redis->hGet($redisKey, 'thread_period_residue_num') > 0) {
                    $newData[] = $item;
                }
            }
            if (empty($newData)) {
                foreach ($data as $v) {
                    $redisKey = env('redis.thread_period_num_redis_key') . $v['merchant_id'];
                    $redis->hMset($redisKey, ['thread_period_num' => $v['thread_period_num'], 'thread_period_residue_num' => $v['thread_period_num']]);
                    $redis->expireAt($redisKey, mktime('23',59,59, date('m'),date('d'),date('Y')));
                    $newData[] = $v;
                }
            }
        } else {
            foreach ($data as $item) {
                $redisKey = env('redis.thread_period_num_redis_key') . $item['merchant_id'];
                $redis->hMset($redisKey, ['thread_period_num' => $item['thread_period_num'], 'thread_period_residue_num' => $item['thread_period_num']]);
                $redis->expireAt($redisKey, mktime('23',59,59, date('m'),date('d'),date('Y')));
                $newData[] = $item;
            }
        }
        $landingPageId = (new WeightService)->initData($newData);
        $courseId = LandingPage::withTrashed()->where('id', $landingPageId)->value('course_id');
        $courseId = $courseId ?? 0;
        $merchantId = Course::withTrashed()->where('id', $courseId)->value('merchant_id');
        if (!empty($merchantId)) {
            $redis->set(env('redis.user_landing_page_redis_key') . $GLOBALS['uid'], $merchantId);
        }
        return $landingPageId;
    }
    //初始化落地页曝光
    public static function initExpose($redis, $redisKey, $appClassid)
    {
        $landingPageList = LandingPage::with(['course'=> function($query){
            $query->field('id,merchant_id');
            $query->with(['merchant' => function($query){
                $query->field('id,age_range_weight_json,is_source');
            }]);
        }])->where('app_class_id', $appClassid)->field('id,expose_num,is_show,course_id')->select()->toArray();
        $landingPageID = 0;
        $ageRangeId = UserList::where('id', $GLOBALS['uid'])->value('age_range_id');
        $gatherInfo = GatherUserInfoModel::getFormatGatherInfo($ageRangeId, 'age_range_id');
        $ageRange = $gatherInfo['name'];
        if (empty($ageRange)) {
            return $landingPageID;
        }
        if (!empty($landingPageList)) {
            $redis->del($redisKey);
            foreach ($landingPageList as $val) {
                $redis->hSet($redisKey, $val['id'], json_encode([
                    'id' => $val['id'],
                    'expose_num' => $val['expose_num'],
                    'is_show' => $val['is_show'],
                    'is_source' =>  isset($val['course']['merchant']['is_source']) ? $val['course']['merchant']['is_source'] : 1,
                    'age_range_weight' => isset($val['course']['merchant']['age_range_weight_json']) && !empty($val['course']['merchant']['age_range_weight_json']) ? json_decode($val['course']['merchant']['age_range_weight_json'], true) : [],
                ], JSON_UNESCAPED_UNICODE));
            }
            $list = $redis->hGetAll($redisKey);
            $data = [];
            foreach ($list as $val) {
                $arr = json_decode($val, true);
                $weight = isset($arr['age_range_weight'][$ageRange]) && !empty($arr['age_range_weight'][$ageRange]) ? $arr['age_range_weight'][$ageRange] : 0;
                if ($arr['expose_num'] > 0 && $arr['is_show'] && $weight > 0) {
                    $data[] = [
                        'id' => $arr['id'],
                        'weight' => $weight,
                        'is_source' => $arr['is_source'],
                    ];
                }
            }
            $randLandingPageId = (new WeightService)->initData($data);
            if (!empty($randLandingPageId)) {
                self::setReduceExposeNum($redis, $redisKey, $randLandingPageId);
                $landingPageID = $randLandingPageId;
            }
        }
        return $landingPageID;
    }
    //过滤站内商户
    public static function filterMerchant($data = [])
    {
        if (!empty($data)) {
            $sourceY = 0;
            $sourceN = 0;
            foreach($data as $key => $val)
            {
                if ($val['is_source'] == 2) {
                    $sourceY++;
                }
                if ($val['is_source'] == 1) {
                    $sourceN++;
                }
            }
            if ($sourceY >0 && $sourceN > 0) {
                foreach ($data as $item => $value) {

                }
            }

            $data = array_values($data);
        }

        return $data;
    }
    //减少落地页曝光次数,降低权重
    public static function setReduceExposeNum($redis, $redisKey, $landing_page_id = 0)
    {
        $exposeInfo = $redis->hget($redisKey, $landing_page_id);
        if (!empty($exposeInfo)) {
            $courseId = LandingPage::withTrashed()->where('id', $landing_page_id)->value('course_id');
            $courseId = $courseId ?? 0;
            $merchantId = Course::withTrashed()->where('id', $courseId)->value('merchant_id');
            if (!empty($merchantId)) {
                $redis->set(env('redis.user_landing_page_redis_key') . $GLOBALS['uid'], $merchantId);
            }
            $exposeInfo = json_decode($exposeInfo, true);
            if ($exposeInfo['expose_num'] > 0) {
                $exposeInfo['expose_num'] -= 1;
                $redis->hSet($redisKey, $landing_page_id, json_encode($exposeInfo));
            }
        }
    }
}