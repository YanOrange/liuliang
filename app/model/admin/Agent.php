<?php
/**
 * 后台供应商表模型
 */

namespace app\model\admin;

use laytp\BaseModel;
use think\model\concern\SoftDelete;

class Agent extends BaseModel
{
    use SoftDelete;

    //模型名
    protected $name = 'agent';
}
