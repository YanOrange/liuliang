<?php

namespace app\model\admin;

use laytp\BaseModel;
use think\model\concern\SoftDelete;

class NoJumpWechatPhone extends BaseModel
{
    use SoftDelete;
    //模型名
    protected $name = 'no_jump_wechat_phone';
}