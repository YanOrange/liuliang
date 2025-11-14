<?php

namespace app\model\admin;

use laytp\BaseModel;
use think\model\concern\SoftDelete;
class SmsMerchantSwitchMessage extends BaseModel
{
    use SoftDelete;
    //模型名
    protected $name = 'sms_merchant_switch_message';

}