<?php

namespace app\model\api;

use laytp\BaseModel;
use think\model\concern\SoftDelete;

class MyCourse extends BaseModel
{
    use SoftDelete;
    //模型名
    protected $name = 'course';

    public function merchant()
    {
        return $this->belongsTo('app\model\api\Merchant','merchant_id','id')->removeOption('soft_delete');
    }
}