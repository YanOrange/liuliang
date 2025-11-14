<?php
/**
 * 后台轮播图表模型
 */

namespace app\model\admin;

use laytp\BaseModel;
use think\model\concern\SoftDelete;

class PromotionMethod extends BaseModel
{
    use SoftDelete;

    //模型名
    protected $name = 'promotion_method';

}
