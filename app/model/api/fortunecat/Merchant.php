<?php

namespace app\model\api\fortunecat;

use laytp\BaseModel;
use think\model\concern\SoftDelete;

class Merchant extends BaseModel
{
    use SoftDelete;

    protected $name = 'merchant';
}