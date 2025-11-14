<?php
/**
 * 后台新媒体平台表模型
 */

namespace app\model\admin;

use laytp\BaseModel;
use think\model\concern\SoftDelete;
class NewMediaPlatform extends BaseModel
{
    use SoftDelete;

    //模型名
    protected $name = 'new_media_platform';

}
