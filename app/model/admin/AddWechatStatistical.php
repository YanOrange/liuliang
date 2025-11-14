<?php

namespace app\model\admin;

use laytp\BaseModel;
use think\model\concern\SoftDelete;

class AddWechatStatistical extends BaseModel
{
    use SoftDelete;

    //模型
    protected $name = 'add_wechat_statistical';


}