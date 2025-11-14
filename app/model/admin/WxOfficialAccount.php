<?php
/**
 * 后台应用表模型
 */

namespace app\model\admin;

use laytp\BaseModel;
use think\model\concern\SoftDelete;
use app\lib\api\exception\ExceptionStd;
use app\lib\api\vres\Aes;
class WxOfficialAccount extends BaseModel
{
    use SoftDelete;

    //模型名
    protected $name = 'wx_official_account';

    public function admin()
    {
        return $this->belongsTo('app\model\admin\User','admin_id','id')->removeOption('soft_delete');
    }
    public function platform()
    {
        return $this->belongsTo('app\model\admin\NewMediaPlatform','platform_id','id')->removeOption('soft_delete');
    }
}
