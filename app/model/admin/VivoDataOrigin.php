<?php
/**
 * 后台vivo数据源表模型
 */

namespace app\model\admin;

use laytp\BaseModel;
use think\model\concern\SoftDelete;

class VivoDataOrigin extends BaseModel
{
    use SoftDelete;

    //模型名
    protected $name = 'vivo_data_origin';

    // 追加属性
    protected $append = [
    ];

}
