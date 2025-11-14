<?php
/**
 * 小米华为消耗
 * @date 2022-11-01
 * @author chenlele
 */

namespace app\model\admin;

use laytp\BaseModel;
use think\model\concern\SoftDelete;

class BiCost extends BaseModel
{
    use SoftDelete;

    //模型名
    protected $name = 'bi_xiaomihuawei_cost';

    public function getPlantimeAttr($value, $data)
    {
        return !empty($value) ? date('Y-m-d', $value) : '-';
    }
}