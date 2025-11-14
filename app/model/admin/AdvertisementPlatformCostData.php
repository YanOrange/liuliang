<?php
/**
 * 后台应用分类表模型
 */

namespace app\model\admin;

use laytp\BaseModel;
use think\model\concern\SoftDelete;

class AdvertisementPlatformCostData extends BaseModel
{
    use SoftDelete;

    //模型名
    protected $name = 'advertisement_platform_cost_data';

    public function app()
    {
        return $this->belongsTo('app\model\admin\App','app_id','id')->field('id,app_name')->removeOption('soft_delete');
    }
    public function class()
    {
        return $this->belongsTo('app\model\admin\AppClass','app_class_id','id')->removeOption('soft_delete');
    }
    public function channel()
    {
        return $this->belongsTo('app\model\admin\Channel','channel_id','id')->removeOption('soft_delete');
    }
    public function adminUser()
    {
        return $this->belongsTo('app\model\admin\User','admin_user_id','id')->removeOption('soft_delete');
    }
    public function adminUserUpdate()
    {
        return $this->belongsTo('app\model\admin\User','admin_modify_uid','id')->removeOption('soft_delete');
    }
}
