<?php
/**
 * 后台应用表模型
 */

namespace app\model\admin;

use laytp\BaseModel;
use think\model\concern\SoftDelete;
class ChannelCustomer extends BaseModel
{
    use SoftDelete;

    //模型名
    protected $name = 'channel_customer';

    public function channel()
    {
        return $this->belongsTo('app\model\admin\Channel','channel_id','id')->field('id,channel_name')->removeOption('soft_delete');
    }
}
