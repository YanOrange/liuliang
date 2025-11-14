<?php

namespace app\model\api\fortunecat;

use laytp\BaseModel;
use think\model\concern\SoftDelete;

class Thread extends BaseModel
{
    use SoftDelete;

    protected $name = 'thread';

    public function course()
    {
        return $this->belongsTo('app\model\api\Course','course_id','id')->removeOption('soft_delete');
    }

}