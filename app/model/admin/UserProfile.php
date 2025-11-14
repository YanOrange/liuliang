<?php

namespace app\model\admin;

use laytp\BaseModel;
use think\model\concern\SoftDelete;

class UserProfile extends BaseModel
{
    //模型名
    protected $name = 'user_profile';
}