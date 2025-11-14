<?php

namespace app\model\api;

use laytp\BaseModel;
use think\model\concern\SoftDelete;
use app\lib\api\exception\Exception;

class XiaomiConfig extends BaseModel
{
    use SoftDelete;
    
    //模型名
    protected $name = 'xiaomi_config';
}