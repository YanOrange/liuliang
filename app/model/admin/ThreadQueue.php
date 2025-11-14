<?php

namespace app\model\admin;

use think\model\concern\SoftDelete;
use laytp\BaseModel;

class ThreadQueue extends BaseModel
{
    use SoftDelete;
    //模型名
    protected $name = 'thread_queue';
}