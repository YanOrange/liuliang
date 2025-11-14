<?php
/**
 * 后台应用表模型
 */

namespace app\model\admin;

use laytp\BaseModel;
use app\lib\api\exception\ExceptionStd;
class BiClassQrcodeNumber extends BaseModel
{
    //模型名
    protected $name = 'bi_class_qrcode_number';

    public function admin()
    {
        return $this->belongsTo('app\model\admin\User','admin_id','id')->removeOption('soft_delete');
    }
}
