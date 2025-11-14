<?php

namespace app\model\api\single;

use laytp\BaseModel;
use think\model\concern\SoftDelete;

class AppMerchantMessageUser extends BaseModel
{
    use SoftDelete;
    //模型名
    protected $name = 'app_merchant_message_user';
}