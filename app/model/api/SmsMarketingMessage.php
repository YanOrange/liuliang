<?php

namespace app\model\api;

use laytp\BaseModel;
use think\model\concern\SoftDelete;
class SmsMarketingMessage extends BaseModel
{
    use SoftDelete;
    //模型名
    protected $name = "sms_marketing_message";
}