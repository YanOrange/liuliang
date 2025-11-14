<?php

namespace app\lib\api\service;

use app\lib\api\service\WeightService;
use app\model\api\single\Merchant;
use app\model\api\single\Thread;
use app\model\api\v2\GatherUserInfo as GatherUserInfoModel;

//课程报名商户分配
class MerchantService
{
    public function getMerchantServiceId($merchantIds = '',$ageRangeId = 0)
    {
        $merchantId = 0;
        $merchantIds = explode(',',$merchantIds);
        $gatherInfo = GatherUserInfoModel::getFormatGatherInfo($ageRangeId, 'age_range_id');
        $ageRange = !empty($gatherInfo['name']) ? $gatherInfo['name'] : '';
        $ageRangeMer = !empty($gatherInfo['name']) ? '"'.$gatherInfo['name'].'"' : '';
        $merchantList = Merchant::where('is_switch',1)
            ->whereIn('id',$merchantIds)
            ->where("age_range_weight_json->'$.".$ageRangeMer."'",'>',0)
            ->field('id,is_source,age_range_weight_json')
            ->select();
        $dataSourceY = [];
        $dataSourceN = [];
        if(!empty($merchantList)){
            foreach($merchantList as $item) {
                $weightArr = isset($item['age_range_weight_json']) && !empty($item['age_range_weight_json']) ? json_decode($item['age_range_weight_json'], true) : [];
                $age_range_weight = isset($weightArr[$ageRange]) && !empty($weightArr[$ageRange]) ? $weightArr[$ageRange] : 0;
                $isApplyMerchant = Thread::where('uid',$GLOBALS['uid'])->where('merchant_id',$item['id'])->count();
                if ($age_range_weight > 0) {
                    if ($item['is_source'] == 1) {
                        $dataSourceN[] = [
                            'id' => $item['id'],
                            'is_source' => $item['is_source'],
                            'weight' => $age_range_weight,
                        ];
                    }
                    if ($item['is_source'] == 2 && $isApplyMerchant <= 0) {
                        $dataSourceY[] = [
                            'id' => $item['id'],
                            'is_source' => $item['is_source'],
                            'weight' => $age_range_weight,
                        ];
                    }
                }
            }
            $data = !empty($dataSourceY) ? $dataSourceY : $dataSourceN;
            $merchantId = (new WeightService)->initData($data);
        }
        return $merchantId;
    }
}