<?php
/**
 * 后台轮播图表模型
 */

namespace app\model\admin\part;

use laytp\BaseModel;
use think\model\concern\SoftDelete;

class Banner extends BaseModel
{
    use SoftDelete;

    //模型名
    protected $name = 'banner';


}
