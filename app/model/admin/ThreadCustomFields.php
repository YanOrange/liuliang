<?php
/**
 * 客户操作日志表模型
 */

namespace app\model\admin;

use laytp\BaseModel;
use think\model\concern\SoftDelete;

class ThreadCustomFields extends BaseModel
{
    use SoftDelete;

    //模型名
    protected $name = 'thread_custom_fields';


}
