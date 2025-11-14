<?php

namespace app\model\admin\website;
use laytp\BaseModel;
use think\model\concern\SoftDelete;
class WebsiteLeaveMessage extends BaseModel
{
    use SoftDelete;

    //模型名
    protected $name = 'website_leave_message';

    public function followUser()
    {
        return $this->belongsTo('app\model\admin\User','follow_id','id')->field('id,nickname')->removeOption('soft_delete');
    }
    public function getFollowTimeAttr($value, $data)
    {
        return $value ? date("Y-m-d H:i:s", $value) : '-';
    }

}