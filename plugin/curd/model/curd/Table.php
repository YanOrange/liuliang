<?php
/**
 * 后台系统配置模型
 */

namespace plugin\curd\model\curd;

use laytp\BaseModel;
use think\model\concern\SoftDelete;

class Table extends BaseModel
{
    use SoftDelete;

    protected $name = 'plugin_curd_table';
}