<?php
namespace app\model\api\notify;

use laytp\BaseModel;
use think\model\concern\SoftDelete;

class NotifyThreadSyncLog extends BaseModel
{
    use SoftDelete;

    //模型名
    protected $name = 'notify_thread_sync_log';
}