<?php
/**
 * 后台商户表模型
 */

namespace app\model\api;

use app\model\api\v2\GatherUserInfo as GatherUserInfoModel;
use laytp\BaseModel;
use think\model\concern\SoftDelete;
use app\model\api\Thread;
use think\facade\Config;

class Merchant extends BaseModel
{
    use SoftDelete;

    //模型名
    protected $name = 'merchant';

    protected $append = [];


    //获取商户时间段线索单价
    public static function getMerchantThreadPrice($merchantInfo = null, $channelInfo = null)
    {
        $threadPrice = 0;
        if (!empty($merchantInfo)) {
            $statusTime = checkThreadRangeTime();
//            if (in_array($merchantInfo->id, [142,195,229,242,245,246,252,258,259]) && isset($channelInfo['cost_price']) && $channelInfo['cost_price'] > 0) {
//                return ['thread_price' => $channelInfo['cost_price'], 'thread_price_type' => $statusTime ? Thread::THREAD_PRICE_TYPE_PEAK : Thread::THREAD_PRICE_TYPE_LEISURE];
//            }
            if (isset($channelInfo['cost_price']) && $channelInfo['cost_price'] > 0) {
                return ['thread_price' => $channelInfo['cost_price'], 'thread_price_type' => $statusTime ? Thread::THREAD_PRICE_TYPE_PEAK : Thread::THREAD_PRICE_TYPE_LEISURE];
            } else {
                if ($statusTime) {
                    return ['thread_price' => $merchantInfo->peak_price, 'thread_price_type' => Thread::THREAD_PRICE_TYPE_PEAK];
                }
                return ['thread_price' => $merchantInfo->leisure_price, 'thread_price_type' => Thread::THREAD_PRICE_TYPE_LEISURE];
            }
        }
        return ['thread_price' => $threadPrice, 'thread_price_type' => Thread::THREAD_PRICE_TYPE_LEISURE];
    }

    //获取多机构商户
    public static function getMerchantList($params = [])
    {
        extract($params);
        $userInfo = UserList::where('id',$GLOBALS['uid'])->field('app_class_id,phone,age_range_id,is_search_plan,channel,app_class_id')->find();
        $channelInfo = Channel::getChannelAppClass($channel);
        $isManyOrganization = $channelInfo['is_many_organization'];
        $ageRangeId = $userInfo->age_range_id;
        $merchantList = [];
        $merchantModel = new self();
        //if($isManyOrganization == 2){
        $merchantIdArr = null;
        if (!empty($userInfo->phone)) {
            $uidArr = UserList::where('app_class_id', $channelInfo['app_class_id'])->where('phone', $userInfo->phone)->where('id','<>', $GLOBALS['uid'])->whereTime('create_time', 'today')->column('id');
            if (!empty($uidArr)) {
                $merchantIdArr = Thread::whereIn('uid', $uidArr)->where('app_class_id', $channelInfo['app_class_id'])->column('merchant_id');
                if (in_array(197, $merchantIdArr) && !in_array(194, $merchantIdArr)) {
                    $merchantIdArr[] = 194;
                }
                if (in_array(194, $merchantIdArr) && !in_array(197, $merchantIdArr)) {
                    $merchantIdArr[] = 197;
                }
            }
        }
        $gatherInfo = GatherUserInfoModel::getFormatGatherInfo($ageRangeId, 'age_range_id');
        $ageRange = $gatherInfo['name'];
        $name = strtolower($merchantModel->getName());
        $tableName = env('database.prefix') . $name;
        $appMerchantChannelInput = appMerchantChannelInput($userInfo->channel);
        if($appMerchantChannelInput && $userInfo->app_class_id == 10){
            $merchantList  = $merchantModel->whereExists(function ($query) use ($tableName,$ageRange,$channelInfo) {
                $courseTableName = (new \app\model\admin\Course())->getName();
                $query = $query->table(env('database.prefix') .$courseTableName)->where(env('database.prefix') . $courseTableName . '.merchant_id=' .   $tableName . '.id');
                $query->where('status', 1);
              //  $query->whereFindInSet('channel_ids', $channelInfo['channel_id']);
                return $query;
            })
                ->where([['is_switch','=', 1],['app_class_id','=',$channelInfo['app_class_id']]])
                //->where('app_class_id',$channelInfo['app_class_id'])
                ->whereOr('id','in',[177])
                //->whereFindInSet('is_many_organization',$isManyOrganization)
                ->field('id,merchant_name as company_name,age_range_weight_json,is_source,search_rate,totay_thread_limit_nums,today_18to25_thread_limit_nums')
                ->order(['rank' => 'asc','peak_price' => 'desc'])
                ->select()
                ->toArray();
        }else{
            $merchantList  = $merchantModel->whereExists(function ($query) use ($tableName,$ageRange,$channelInfo) {
                $courseTableName = (new \app\model\admin\Course())->getName();
                $query = $query->table(env('database.prefix') .$courseTableName)->where(env('database.prefix') . $courseTableName . '.merchant_id=' .   $tableName . '.id');
                $query->where('status', 1);
              //  $query->whereFindInSet('channel_ids', $channelInfo['channel_id']);
                return $query;
            })
                ->where('is_switch',1)
                ->where('app_class_id',$channelInfo['app_class_id'])
                //->whereFindInSet('is_many_organization',$isManyOrganization)
                ->field('id,merchant_name as company_name,age_range_weight_json,is_source,search_rate,totay_thread_limit_nums,today_18to25_thread_limit_nums')
                ->order(['rank' => 'asc','peak_price' => 'desc'])
                ->select()
                ->toArray();
        }
        $redis = get_redis();
        foreach ($merchantList as $k => $v) {
            if ($v['is_source'] == 2) {
                $today18to25ThreadLimitNums = $redis->get(env('redis.merchant_totay_18to25_thread_num_redis_key') . $v['id']);
                if ($v['today_18to25_thread_limit_nums'] > 0 && $today18to25ThreadLimitNums > 0 && in_array($ageRangeId, [2,3]) && $v['today_18to25_thread_limit_nums'] <= $today18to25ThreadLimitNums) {
                    $checkThread = Thread::where('uid', $GLOBALS['uid'])->where('merchant_id', $v['id'])->count();
                    if (!$checkThread) {
                        unset($merchantList[$k]);
                    }
                }
            }
        }
        $merchantList = array_values($merchantList);
        if ($merchantIdArr) {
            $merchantIdArr = array_values(array_unique($merchantIdArr));
            foreach ($merchantList as $k => $v) {
                if ($v['is_source'] == 2 && in_array($v['id'], $merchantIdArr)) {
                    unset($merchantList[$k]);
                }
            }
        }
        $merchantList = array_values($merchantList);
//            $merchantList = self::where('is_switch',1)
//                ->where('app_class_id',$channelInfo['app_class_id'])
//                ->whereFindInSet('is_many_organization',$isManyOrganization)
//                ->field('id,company_name,age_range_weight_json,is_source')
//                ->select()
//                ->toArray();
        if(!empty($merchantList)) {
            $sourceY = 0;
            $sourceN = 0;
            foreach ($merchantList as $key => &$val) {
                $ageRangeWeight = json_decode($val['age_range_weight_json'], true);
                unset($merchantList[$key]['age_range_weight_json']);
                $val['age_range_weight'] = isset($ageRangeWeight[$ageRange]) && !empty($ageRangeWeight[$ageRange]) ? $ageRangeWeight[$ageRange] : 0;
                if ($userInfo['age_range_id'] > 0 ) {
                    if($val['age_range_weight'] > 0) {
                        if ($val['is_source'] == 1) {
                            $sourceN++;
                        }
                        if ($val['is_source'] == 2) {
                            $sourceY++;
                        }
                    }else{
                        unset($merchantList[$key]);
                    }
                } else {
                    if ($val['is_source'] == 1) {
                        $sourceN++;
                    }
                    if ($val['is_source'] == 2) {
                        $sourceY++;
                    }
                }

            }
            if ($sourceY > 0 && $sourceN > 0) {
                foreach ($merchantList as $item => $value) {
                    if ($value['is_source'] == 1) {
                        unset($merchantList[$item]);
                    }
                }
            }
            $merchantList = array_values($merchantList);
            //$ageRangeWeightArr = array_column($merchantList, 'age_range_weight');
            // $merchantSearchRateArr = array_column($merchantList, 'merchant_search_rate');
            //array_multisort($merchantSearchRateArr,SORT_ASC,$ageRangeWeightArr, SORT_DESC, $merchantList);
        }
        //}
        return $merchantList;
    }

}
