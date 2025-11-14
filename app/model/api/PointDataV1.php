<?php
/**
 * 埋点表模型
 */

namespace app\model\api;

use laytp\BaseModel;
use think\model\concern\SoftDelete;
use app\lib\api\exception\Exception;
class PointDataV1 extends BaseModel
{
    use SoftDelete;
    //模型名
    protected $name = 'point_data_v1';

}
