<?php
/**
 * 后台系统配置模型
 */

namespace plugin\apidoc\model;

use laytp\BaseModel;
use think\model\concern\SoftDelete;

class Apidoc extends BaseModel
{
    use SoftDelete;
    protected $name = 'plugin_apidoc';
}