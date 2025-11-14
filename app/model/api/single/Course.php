<?php

namespace app\model\api\single;

use laytp\BaseModel;
use think\model\concern\SoftDelete;

class Course extends BaseModel
{
    use SoftDelete;
    //模型名
    protected $name = 'course';

    public function merchant()
    {
        return $this->belongsTo('app\model\admin\Merchant','merchant_id','id')->removeOption('soft_delete');
    }
}