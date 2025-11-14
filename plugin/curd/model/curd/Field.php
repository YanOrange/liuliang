<?php
/**
 * 后台系统配置模型
 */

namespace plugin\curd\model\curd;

use laytp\BaseModel;
use think\model\concern\SoftDelete;

class Field extends BaseModel
{
    use SoftDelete;

    protected $name = 'plugin_curd_field';

    protected function getAdditionAttr($addition)
    {
        return json_decode($addition, true);
    }

    protected function getRelationAttr($relation)
    {
        return json_decode($relation, true);
    }
}