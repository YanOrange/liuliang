<?php

namespace app\model\admin;

use laytp\BaseModel;
use think\model\concern\SoftDelete;

class EHomePageCard extends BaseModel
{
    use SoftDelete;

    protected $name = 'e_home_page_card';

    public function page()
    {
        return $this->belongsTo('app\model\admin\AppBuryingPointPage','page','id')->removeOption('soft_delete');
    }

    public function app()
    {
        return $this->belongsTo('app\model\admin\App','app_id','id')->removeOption('soft_delete');
    }

    public function channel()
    {
        return $this->belongsTo('app\model\admin\Channel','channel_id','id')->removeOption('soft_delete');
    }

    public function lastPage()
    {
        return $this->belongsTo('app\model\admin\AppBuryingPointPage','last_page_id','id')->removeOption('soft_delete');
    }

    public function user()
    {
        return $this->belongsTo('app\model\admin\UserList','uid','id')->removeOption('soft_delete');
    }

}