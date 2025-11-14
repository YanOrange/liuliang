<?php

namespace app\model\admin;

use laytp\BaseModel;
use think\model\concern\SoftDelete;
class SourceChannelRate extends BaseModel
{
    use SoftDelete;
    //模型名
    protected $name = 'source_channel_rate';

    public function channel()
    {
        return $this->belongsTo('app\model\admin\SourceChannel','source_id','id')->removeOption('soft_delete');
    }

    public function class()
    {
        return $this->belongsTo('app\model\admin\AppClass','app_class_id','id')->removeOption('soft_delete');
    }
}