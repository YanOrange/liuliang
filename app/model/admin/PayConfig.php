<?php
/**
 * 后台支付配置表模型
 */

namespace app\model\admin;

use laytp\BaseModel;
use think\model\concern\SoftDelete;

class PayConfig extends BaseModel
{
    use SoftDelete;

    //模型名
    protected $name = 'pay_config';

    // 追加属性

}
