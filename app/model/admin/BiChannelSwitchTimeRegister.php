<?php

namespace app\model\admin;

use laytp\BaseModel;
use think\model\concern\SoftDelete;
class BiChannelSwitchTimeRegister extends BaseModel
{
    //模型名
    protected $name = 'bi_channel_switch_time_register';

    public function getUnixReleaseTimeAttr($value, $data)
    {
        if (!empty($data['unix_release_time'])) {
            return date('Y-m-d H:i:s',$data['unix_release_time']);
        }
        return '-';
    }

    public function getUnixCloseTimeAttr($value, $data)
    {
        if (!empty($data['unix_close_time'])) {
            return date('Y-m-d H:i:s',$data['unix_close_time']);
        }
        return '-';
    }

    public function getUnixCloseTime1Attr($value, $data)
    {
        if (!empty($data['unix_close_time1'])) {
            return date('Y-m-d H:i:s',$data['unix_close_time1']);
        }
        return '-';
    }

    public function app()
    {
        return $this->belongsTo('app\model\admin\App','app_id','id')->field('id,app_name,app_class_id')->removeOption('soft_delete');
    }

    public function appClass()
    {
        return $this->belongsTo('app\model\admin\AppClass','app_class_id','id')->field('id,app_class_name')->removeOption('soft_delete');
    }

    public function addAdmin()
    {
        return $this->belongsTo('\app\model\admin\User', 'add_admin_id','id')->removeOption('soft_delete');
    }

    public function upAdmin()
    {
        return $this->belongsTo('\app\model\admin\User', 'up_admin_id','id')->removeOption('soft_delete');
    }
}