<?php
/**
 * 后台应用分类表模型
 */

namespace app\model\admin;

use laytp\BaseModel;
use think\model\concern\SoftDelete;

class JzdCustomerVolumeAssignLog extends BaseModel
{
    use SoftDelete;

    //模型名
    protected $name = 'jzd_customer_volume_assign_log';

}
