<?php
namespace app\model\api\notify;

use laytp\BaseModel;
use think\model\concern\SoftDelete;

class OppoNotifyLog extends BaseModel
{
    use SoftDelete;

    //模型名
    protected $name = 'oppo_notify_log';
}