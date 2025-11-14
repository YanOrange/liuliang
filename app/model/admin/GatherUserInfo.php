<?php

namespace app\model\admin;

use laytp\BaseModel;
use think\model\concern\SoftDelete;

class GatherUserInfo extends BaseModel
{
    use SoftDelete;
    //模型名
    protected $name = 'gather_user_info';

    protected $append = [
        'gather_info'
    ];

    public function getGatherInfoAttr($value, $data)
    {
        $gatherInfoJson = '';
        $gatherInfo = !empty($data['gather_info_json']) ? json_decode($data['gather_info_json'],true) : [];
        if(!empty($gatherInfo)) {
            $gatherInfo = array_column($gatherInfo, 'name');
            $gatherInfoJson = implode(',', $gatherInfo);
        }
        return $gatherInfoJson;
    }

    public static function ageRange()
    {
        $ageRangeList = '';
        $gatherInfoJson = GatherUserInfo::where('id',1)->value('gather_info_json');
        if(!empty($gatherInfoJson)) {
            $ageRangeList = json_decode($gatherInfoJson, true);
        }
        return $ageRangeList;
    }

    public static function identify()
    {
        $identityList = '';
        $gatherInfoJson = GatherUserInfo::where('id',2)->value('gather_info_json');
        if(!empty($gatherInfoJson)) {
            $identityList = json_decode($gatherInfoJson, true);
        }
        return $identityList;
    }

    public static function education()
    {
        $educationList = '';
        $gatherInfoJson = GatherUserInfo::where('id',3)->value('gather_info_json');
        if(!empty($gatherInfoJson)) {
            $educationList = json_decode($gatherInfoJson, true);
        }
        return $educationList;
    }

}