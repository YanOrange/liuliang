<?php

namespace app\model\admin;

use laytp\BaseModel;
use think\model\concern\SoftDelete;

class SpeedSlowChannelMerchant extends BaseModel
{
    use SoftDelete;

    //模型名
    protected $name = 'speed_slow_channel_merchant';

    public function channel()
    {
        return $this->belongsTo('app\model\admin\Channel','channel_id','id')->field('id,channel_name')->removeOption('soft_delete');
    }
    public function merchant()
    {
        return $this->belongsTo('app\model\admin\Merchant','merchant_id','id')->field('id,merchant_name')->removeOption('soft_delete');
    }

}
