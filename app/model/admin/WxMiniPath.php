<?php
/**
 * 微信小程序路径
 * @date 2022-10-18
 * @author chenlele
 */

namespace app\model\admin;

use laytp\BaseModel;
use think\model\concern\SoftDelete;

class WxMiniPath extends BaseModel
{
    use SoftDelete;

    //模型名
    protected $name = 'wxmini_path';
}