<?php

namespace app\model\admin;

use laytp\BaseModel;
use think\model\concern\SoftDelete;

class AppUserStartRecord extends BaseModel
{
    use SoftDelete;

    //模型名
    protected $name = 'app_user_start_record';
}