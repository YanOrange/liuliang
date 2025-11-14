<?php
/**
 * 埋点页面表模型
 */

namespace app\model\api;

use laytp\BaseModel;
use think\model\concern\SoftDelete;
use app\lib\api\exception\Exception;
class AppBuryingPointPage extends BaseModel
{
    use SoftDelete;
    //模型名
    protected $name = 'app_burying_point_page';
}
