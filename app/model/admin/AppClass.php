<?php
/**
 * 后台应用分类表模型
 */

namespace app\model\admin;

use laytp\BaseModel;
use think\model\concern\SoftDelete;

class AppClass extends BaseModel
{
    use SoftDelete;

    //模型名
    protected $name = 'app_class';

}
