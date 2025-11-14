<?php

namespace app\model\api;

use laytp\BaseModel;
use think\model\concern\SoftDelete;

class PlanDetailData extends BaseModel
{
    use SoftDelete;
    //模型名
    protected $name = 'plan_detail_data';

    public function appClass()
    {
        return $this->belongsTo('app\model\api\AppClass','app_class_id','id')
            ->bind(['app_class_name'])
            ->removeOption('soft_delete');
    }
}