<?php

namespace app\model\api;

use laytp\BaseModel;
use think\model\concern\SoftDelete;
use app\lib\api\exception\Exception;
class VivoDataOrigin extends BaseModel
{
    use SoftDelete;
    //模型名
    protected $name = 'vivo_data_origin';
}