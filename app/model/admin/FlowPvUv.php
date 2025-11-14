<?php
/**
 * 后台pv uv表模型
 */

namespace app\model\admin;

use laytp\BaseModel;
use think\model\concern\SoftDelete;

class FlowPvUv extends BaseModel
{
    use SoftDelete;

    //模型名
    protected $name = 'flow_pv_uv';

    public function getStartTimeAttr($value, $data)
    {
        return !empty($value) ? date('Y-m-d H:i:s', $value) : '-';
    }

    public function user()
    {
        return $this->belongsTo('app\model\admin\UserList','uid','id')->removeOption('soft_delete');
    }
    public function thread()
    {
        return $this->belongsTo('app\model\admin\Thread','thread_id','id')->removeOption('soft_delete');
    }

}
