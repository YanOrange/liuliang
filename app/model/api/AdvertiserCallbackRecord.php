<?php

namespace app\model\api;

use laytp\BaseModel;
use think\model\concern\SoftDelete;
use app\lib\api\exception\Exception;
class AdvertiserCallbackRecord extends BaseModel
{
//模型名
    protected $name = 'advertiser_callback_record';
}