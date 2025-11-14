<?php

namespace app\model\api\fortunecat;

use laytp\BaseModel;
use think\model\concern\SoftDelete;

class UserList extends BaseModel
{
    use SoftDelete;

    protected $name = 'user_list';

}