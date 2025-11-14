<?php
/**
 * 应用表模型
 */

namespace app\model\api;

use laytp\BaseModel;
use think\model\concern\SoftDelete;
use app\lib\api\exception\Exception;
class App extends BaseModel
{
    use SoftDelete;
    //模型名
    protected $name = 'app';

    public function appClass()
    {
        return $this->belongsTo('app\model\api\AppClass','app_class_id','id')->removeOption('soft_delete');
    }

}
