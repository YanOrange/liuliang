<?php

namespace app\model\api\infoflow;

use laytp\BaseModel;
use think\model\concern\SoftDelete;

class UserList extends BaseModel
{
    use SoftDelete;
    //表名
    protected $name = 'user_list';
}