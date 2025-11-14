<?php

namespace app\model\admin;

use laytp\BaseModel;
use think\model\concern\SoftDelete;

class AppBuryingPointEvent extends BaseModel
{
    use SoftDelete;

    protected $name = 'app_point_event';

    public function page()
    {
        return $this->belongsTo('app\model\admin\AppBuryingPointPage','page_id','id')->removeOption('soft_delete');
    }

    public function admUser()
    {
        return $this->belongsTo('app\model\admin\User','admin_id','id')->removeOption('soft_delete');
    }
}