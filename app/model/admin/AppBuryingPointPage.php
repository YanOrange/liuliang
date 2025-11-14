<?php

namespace app\model\admin;

use laytp\BaseModel;
use think\model\concern\SoftDelete;

class AppBuryingPointPage extends BaseModel
{
    use SoftDelete;

    protected $name = 'app_point_page';

    public function channel()
    {
        return $this->belongsTo('app\model\admin\Channel','channel_id','id')->removeOption('soft_delete');
    }

    public function app()
    {
        return $this->belongsTo('app\model\admin\App','app_id','id')->removeOption('soft_delete');
    }

    public function admUser()
    {
        return $this->belongsTo('app\model\admin\User','admin_id','id')->removeOption('soft_delete');
    }
}