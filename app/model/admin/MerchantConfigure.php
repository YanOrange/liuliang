<?php

namespace app\model\admin;

use laytp\BaseModel;
use think\facade\Config;

class MerchantConfigure extends BaseModel
{
    //模型名
    protected $name = 'merchant_configure';
    protected $userCategory;

    protected $append = [
        'age_range',
        'identify',
        'education'
    ];

    public function merchant()
    {
        return $this->belongsTo('app\model\admin\Merchant','merchant_id','id')->removeOption('soft_delete');
    }

    public function getAgeRangeAttr($value, $data)
    {
        if (!empty($data['age_range_ids'])) {
            $this->userCategory = Config::load("extra/user","extra");
            $ageRangeIdArray = explode(',', $data['age_range_ids']);
            $ageRangeList = array_column($this->userCategory['age_range_list'], 'age_range', 'id');
            $ageRangeNames = $this->arrayKeysSearch($ageRangeList,$ageRangeIdArray,'');
            if (!empty($ageRangeNames)) {
                return implode('、', $ageRangeNames);
            }
        }
        return '-';
    }

    public function getIdentifyAttr($value, $data)
    {
        if (!empty($data['identify_ids'])) {
            $this->userCategory = Config::load("extra/user","extra");
            $identifyIdArray = explode(',', $data['identify_ids']);
            $identifyList = array_column($this->userCategory['identity_list'], 'identity', 'id');
            $identifyNames = $this->arrayKeysSearch($identifyList,$identifyIdArray,'');
            if (!empty($identifyNames)) {
                return implode('、', $identifyNames);
            }
        }
        return '-';
    }

    public function getEducationAttr($value, $data)
    {
        if (!empty($data['education_ids'])) {
            $this->userCategory = Config::load("extra/user","extra");
            $educationIdArray = explode(',', $data['education_ids']);
            $educationList = array_column($this->userCategory['education_list'], 'education', 'id');
            $educationNames = $this->arrayKeysSearch($educationList,$educationIdArray,'');
            if (!empty($educationNames)) {
                return implode('、', $educationNames);
            }
        }
        return '-';
    }

    /** 获取键对应的值
    * @param array $array 源数组
    * @param array $keys 要提取的键数组
    * @param string $index 二维组中指定提取的字段(唯一)
    * @return array
    */
    public function arrayKeysSearch($array, $keys, $index='')
    {

        if(empty($array))
            return $array;
        if (!empty($index) && count($array) != count($array, COUNT_RECURSIVE))
            $array = array_column($array, null, $index);
        $list = array();
        foreach ($keys as $key) {
            if (isset($array[$key]))
                $list[$key] = $array[$key];
        }
        return $list;
    }
}