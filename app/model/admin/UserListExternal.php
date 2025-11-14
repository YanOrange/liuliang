<?php
/**
 * 后台用户表模型
 */

namespace app\model\admin;

use laytp\BaseModel;
use think\model\concern\SoftDelete;

class UserListExternal extends BaseModel
{
    use SoftDelete;

    //模型名
    protected $name = 'user_list_external';

    public function channelpro()
    {
        return $this->belongsTo('app\model\admin\Channel','channel_id','id')->removeOption('soft_delete');
    }
    public function app()
    {
        return $this->belongsTo('app\model\admin\App','app_id','id')->field('id,app_name')->removeOption('soft_delete');
    }
    public function class()
    {
        return $this->belongsTo('app\model\admin\AppClass','app_class_id','id')->removeOption('soft_delete');
    }
    public function flow()
    {
        return $this->belongsTo('app\model\admin\ForFlow','flow_id','id')->removeOption('soft_delete');
    }

    public function appUserStart()
    {
        return $this->belongsTo('app\model\admin\AppUserStartRecord','flow_id','id')->removeOption('soft_delete');
    }
}
