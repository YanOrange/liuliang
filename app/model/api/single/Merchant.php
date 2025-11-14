<?php
/**
 * 后台商户表模型
 */

namespace app\model\api\single;

use app\model\api\Thread;
use app\model\api\v2\GatherUserInfo as GatherUserInfoModel;
use laytp\BaseModel;
use think\model\concern\SoftDelete;
use app\model\api\UserList;
use app\model\api\Channel;

class Merchant extends BaseModel
{
    use SoftDelete;

    //模型名
    protected $name = 'merchant';

    protected $append = [];

    //获取单机构2.0商户（课程、资源、消息）
    public static function getMerchantIds($channelInfo)
    {
        $userInfo = UserList::where('id',$GLOBALS['uid'])->field('app_class_id,age_range_id')->find();
        $isManyOrganization = $channelInfo['is_many_organization'];
        $ageRangeId = $userInfo->age_range_id;
        $merchantList = [];
        $merchantModel = new self();
        if(!empty($ageRangeId)){
            $gatherInfo = GatherUserInfoModel::getFormatGatherInfo($ageRangeId, 'age_range_id');
            $ageRange = !empty($gatherInfo['name']) ? $gatherInfo['name'] : '';
            $ageRangeMer = !empty($gatherInfo['name']) ? '"'.$gatherInfo['name'].'"' : '';
            $merchantList  = $merchantModel->where('is_switch',1)
                ->where('app_class_id',$channelInfo['app_class_id'])
                ->where("age_range_weight_json->'$.".$ageRangeMer."'",'>',0)
                ->whereFindInSet('is_many_organization',$isManyOrganization)
                ->field('id,company_name,age_range_weight_json,is_source')
                ->select()
                ->toArray();
            if(!empty($merchantList)) {
                $sourceY = 0;
                $sourceN = 0;
                foreach ($merchantList as $key => &$val) {
                    $ageRangeWeight = json_decode($val['age_range_weight_json'], true);
                    unset($merchantList[$key]['age_range_weight_json']);
                    $val['age_range_weight'] = isset($ageRangeWeight[$ageRange]) && !empty($ageRangeWeight[$ageRange]) ? $ageRangeWeight[$ageRange] : 0;
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
                }
                if ($sourceY > 0 && $sourceN > 0) {
                    foreach ($merchantList as $item => $value) {
                        if ($value['is_source'] == 1) {
                            unset($merchantList[$item]);
                        }
                    }
                }
                $merchantList = array_values($merchantList);
                $ageRangeWeightArr = array_column($merchantList, 'age_range_weight');
                array_multisort($ageRangeWeightArr, SORT_DESC, $merchantList);
            }
        }
        return $merchantList;
    }

    //获取单机构2.0商户（课程、资源、消息）
    public static function getMerchantIdsV2($appInfo)
    {
        $userInfo = UserList::where('id', $GLOBALS['uid'])->field('id,age_range_id,phone,is_search_plan')->find();
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
            $merchantTableName = (new \app\model\api\Merchant())->getName();
            $query = $query->table(env('database.prefix') . $merchantTableName)->where(env('database.prefix') . $merchantTableName . '.id=' .   $tableName . '.merchant_id');
            $query->where('is_source', 2);
            $query->where('is_switch', 1);
            //  $query->where('landing_page_thread_switch', 1);
            //$query->whereFindInSet('is_many_organization', $appInfo['is_many_organization']);
            if ($userInfo['age_range_id'] > 0 ) {
                $query->where("age_range_weight_json->'$.".$ageRange."'",'>',0);
            }
            if (!empty($merchantIdArr)) {
                $query->whereNotIn('id', $merchantIdArr);
            }
            return $query;
        })
            ->where('course_type', 0)
            ->whereFindInSet('app_ids', $appInfo['app_id'])
            ->count();
        $merchantList  = $courseModel->whereExists(function ($query) use ($tableName, $ageRange, $outsideMerchantCount, $appInfo, $userInfo, $merchantIdArr) {
            $merchantTableName = (new \app\model\api\Merchant())->getName();
            $query = $query->table(env('database.prefix') . $merchantTableName)->where(env('database.prefix') . $merchantTableName . '.id=' .   $tableName . '.merchant_id');
            $query->where('is_source', $outsideMerchantCount ? 2 : 1);
            $query->where('is_switch', 1);
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
            ->whereFindInSet('app_ids', $appInfo['app_id'])
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

}
