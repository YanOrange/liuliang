<?php
/**
 * 后台部门表模型
 */

namespace app\model\api;

use laytp\BaseModel;
use think\model\concern\SoftDelete;

class Department extends BaseModel
{
    use SoftDelete;

    //模型名
    protected $name = 'department';

    protected $append = [];


}
