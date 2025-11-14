<?php

namespace app\model\api\infoflow;

use laytp\BaseModel;
use think\model\concern\SoftDelete;

class AppFlow extends BaseModel
{
    use SoftDelete;

    protected $name = 'app_flow';

}