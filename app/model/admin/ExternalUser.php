<?php
/**
 * 后台外部表模型
 */

namespace app\model\admin;

use laytp\BaseModel;
use laytp\library\UploadDomain;
use think\model\concern\SoftDelete;

class ExternalUser extends BaseModel
{
    use SoftDelete;

    //模型名
    protected $name = 'external_user';



    public function avatarFile(){
        return $this->belongsTo('app\model\Files','avatar','id');
    }

    // 定义默认头像
    public function getAvatarFileAttr($value){
        if(!$value){
            return [
                'id' => "",
                'filename' => '默认头像',
                'path' => UploadDomain::getDefaultAvatar(),
            ];
        }else{
            return $value;
        }
    }
}
