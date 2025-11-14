<?php

namespace app\model\admin;

use app\model\api\Course;
use app\model\admin\AccompanyingSku;
use laytp\BaseModel;
use think\model\concern\SoftDelete;

class AccompanyingUserInfo extends BaseModel
{
    use SoftDelete;

    //模型
    protected $name = 'accompanying_user_info';

    protected $append = [
        'hospital_sku'
    ];

    public function hospital()
    {
        return $this->belongsTo('app\model\admin\AccompanyingHospital','hospital_id','id')->bind(['hospital_name'])->removeOption('soft_delete');
    }

    public function getHospitalSkuAttr($value,$data)
    {
        if(!empty($data['hospital_sku_id'])){
            $hospitalSku = AccompanyingSku::whereIn('id',explode(',',$data['hospital_sku_id']))->column('title');
            $data['hospital_sku'] = implode(',',$hospitalSku);
        }
        return $data['hospital_sku'] ?? '';
    }

    public function getPhoneAttr($value,$data)
    {
        return phoneEncryption($data['phone']);
    }


    
}