<?php
/**
 * 后台线索模型
 */

namespace app\model\admin;

use laytp\BaseModel;
use think\model\concern\SoftDelete;

class ThreadSyncLog extends BaseModel
{
    use SoftDelete;

    //模型名
    protected $name = 'thread_sync_log';
}
