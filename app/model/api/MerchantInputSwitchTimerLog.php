<?php

namespace app\model\api;

use laytp\BaseModel;
use think\model\concern\SoftDelete;

class MerchantInputSwitchTimerLog extends BaseModel
{
    use SoftDelete;

    //模型名
    protected $name = 'merchant_input_switch_timer_log';
}