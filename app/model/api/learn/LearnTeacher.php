<?php
/**
 * 渠道表模型
 */

namespace app\model\api\learn;

use laytp\BaseModel;
use app\lib\api\service\WeightService;
use app\model\api\Channel;
use think\model\concern\SoftDelete;
use app\lib\api\exception\Exception;
class LearnTeacher extends BaseModel
{
    use SoftDelete;
    //模型名
    protected $name = 'learn_teacher';


}
