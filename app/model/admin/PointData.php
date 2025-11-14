<?php

namespace app\model\admin;

use laytp\BaseModel;
use think\model\concern\SoftDelete;

class PointData extends BaseModel
{
    use SoftDelete;

    //模型名
    protected $name = 'point_data';

    public function user()
    {
        return $this->belongsTo('app\model\admin\UserList','uid','id')->removeOption('soft_delete');
    }

    public function merchant()
    {
        return $this->belongsTo('app\model\admin\Merchant','merchant_id','id')->removeOption('soft_delete');
    }

    public function app()
    {
        return $this->belongsTo('app\model\admin\App','app_id','id')->removeOption('soft_delete');
    }

    public function channel()
    {
        return $this->belongsTo('app\model\admin\Channel','channel_id','id')->removeOption('soft_delete');
    }

    public function page()
    {
        return $this->belongsTo('app\model\admin\AppBuryingPointPage','last_page_id','id')->removeOption('soft_delete');
    }

    public function course()
    {
        return $this->belongsTo('app\model\admin\Course','course_id','id')->removeOption('soft_delete');
    }

    public function customer()
    {
        return $this->belongsTo('app\model\admin\Customer','customer_id','id')->removeOption('soft_delete');
    }

    public function banner()
    {
        return $this->belongsTo('app\model\admin\Banner','banner_id','id')->removeOption('soft_delete');
    }

    public function article()
    {
        return $this->belongsTo('app\model\admin\Article','article_id','id')->removeOption('soft_delete');
    }

    public function userPage()
    {
        return $this->belongsTo('app\model\admin\AppBuryingPointPage','page_id','id')->removeOption('soft_delete');
    }

    public function userEvent()
    {
        return $this->belongsTo('app\model\admin\AppBuryingPointEvent','event_id','id')->removeOption('soft_delete');
    }

    public function event()
    {
        return $this->belongsTo('app\model\admin\AppBuryingPointEvent','event_id','id')->removeOption('soft_delete');
    }

    public function advertising()
    {
        return $this->belongsTo('app\model\admin\Advertising','advertising_id','id')->removeOption('soft_delete');
    }

    public function forFlow()
    {
        return $this->belongsTo('app\model\admin\ForFlow','for_flow_id','id')->removeOption('soft_delete');
    }

}