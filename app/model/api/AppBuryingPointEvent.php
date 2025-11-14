<?php
/**
 * 埋点事件表模型
 */

namespace app\model\api;

use laytp\BaseModel;
use think\model\concern\SoftDelete;
use app\lib\api\exception\Exception;
class AppBuryingPointEvent extends BaseModel
{
    use SoftDelete;
    //模型名
    protected $name = 'app_burying_point_event';
}
