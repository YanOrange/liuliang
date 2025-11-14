<?php
/**
 * 客服表模型
 */

namespace app\model\api;

use laytp\BaseModel;
use think\model\concern\SoftDelete;

class CustomerQrcodeLog extends BaseModel
{
    use SoftDelete;

    //模型名
    protected $name = 'customer_qrcode_log';

}