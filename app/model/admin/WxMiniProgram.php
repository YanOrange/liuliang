<?php
/**
 * 微信小程序
 * @date 2022-10-08
 * @author chenlele
 */

namespace app\model\admin;

use laytp\BaseModel;
use think\model\concern\SoftDelete;

class WxMiniProgram extends BaseModel
{
    use SoftDelete;

    //模型名
    protected $name = 'wxmini_program';
}