<?php
/**
 * 线索操作日志文件表模型
 */

namespace app\model\admin\thread;

use laytp\BaseModel;
use think\model\concern\SoftDelete;

class ThreadLogFiles extends BaseModel
{
    use SoftDelete;

    //模型名
    protected $name = 'thread_log_files';


}
