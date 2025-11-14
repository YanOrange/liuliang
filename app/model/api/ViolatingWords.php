<?php
/**
 * 应用分类表模型
 */

namespace app\model\api;

use laytp\BaseModel;
use think\model\concern\SoftDelete;
use app\lib\api\exception\Exception;

class ViolatingWords extends BaseModel
{
    use SoftDelete;
    //模型名
    protected $name = 'violating_words';

}
