<?php

namespace app\model\api;

use laytp\BaseModel;
use think\model\concern\SoftDelete;
use app\model\api\Channel;

class AsoIdfaCallback extends BaseModel
{
    use SoftDelete;

    protected $name = 'aso_idfa_callback';

}