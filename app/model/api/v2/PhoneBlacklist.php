<?php
/**
 * 手机号黑名单表模型
 */

namespace app\model\api\v2;

use laytp\BaseModel;
use think\model\concern\SoftDelete;

class PhoneBlacklist extends BaseModel
{
    use SoftDelete;

    //模型名
    protected $name = 'phone_blacklist';

}
