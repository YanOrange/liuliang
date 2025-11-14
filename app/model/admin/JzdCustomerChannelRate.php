<?php
/**
 * 后台应用分类表模型
 */

namespace app\model\admin;

use laytp\BaseModel;
use think\model\concern\SoftDelete;

class JzdCustomerChannelRate extends BaseModel
{
    use SoftDelete;

    //模型名
    protected $name = 'jzd_customer_channel_rate';

    public function admin()
    {
        return $this->belongsTo('app\model\admin\User','admin_id','id')->removeOption('soft_delete');
    }


}
