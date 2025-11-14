<?php
/**
 * 后台应用分类表模型
 */

namespace app\model\api;

use laytp\BaseModel;
use think\model\concern\SoftDelete;

class AdvertiserCallbackChannel extends BaseModel
{
    use SoftDelete;

    //模型名
    protected $name = 'advertiser_callback_channel';

}
