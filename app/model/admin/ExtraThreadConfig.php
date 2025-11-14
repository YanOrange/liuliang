<?php


namespace app\model\admin;

use laytp\BaseModel;
use think\model\concern\SoftDelete;

class ExtraThreadConfig extends BaseModel
{
    use SoftDelete;

    //模型名
    protected $name = 'extra_thread_config';

    public function channel()
    {
        return $this->belongsTo('app\model\admin\Channel','channel_id','id')->removeOption('soft_delete');
    }

}
