<?php
/**
 * 后台应用分类表模型
 */

namespace app\model\admin;

use laytp\BaseModel;
use think\model\concern\SoftDelete;

class PlanConsumeCheck extends BaseModel
{
    use SoftDelete;

    //模型名
    protected $name = 'plan_consume_check';

    protected $append = [
        'total_consume_diff',
        'plantime'
    ];

    public function getTotalConsumeDiffAttr($value,$data)
    {
        return round($data['flow_consume'] - $data['total_consume'],2);
    }

}
