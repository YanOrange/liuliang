<?php

namespace app\model\admin;

use laytp\BaseModel;
use think\model\concern\SoftDelete;

class AssignThreadQueueLog extends BaseModel
{
    use SoftDelete;
    //模型名
    protected $name = 'assign_thread_queue_log';
}