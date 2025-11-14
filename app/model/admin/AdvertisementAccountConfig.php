<?php
/**
 * 后台应用分类表模型
 */

namespace app\model\admin;

use laytp\BaseModel;
use think\model\concern\SoftDelete;

class AdvertisementAccountConfig extends BaseModel
{
    use SoftDelete;

    //模型名
    protected $name = 'advertisement_account_config';
}
