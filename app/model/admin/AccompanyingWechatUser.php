<?php
/**
 * 后台应用分类表模型
 */

namespace app\model\admin;

use laytp\BaseModel;
use think\model\concern\SoftDelete;

class AccompanyingWechatUser extends BaseModel
{
    use SoftDelete;

    //模型名
    protected $name = 'accompanying_wechat_user';

    public function getPhoneAttr($value,$data)
    {
        return phoneEncryption($data['phone']);
    }

    public function getWechatAccountAttr($value,$data)
    {
        return wxNumberEncryption($data['wechat_account']);
    }

}
