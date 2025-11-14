<?php
/**
 * 后台客服表模型
 */

namespace app\model\admin;

use laytp\BaseModel;
use think\model\concern\SoftDelete;

class Customer extends BaseModel
{
    use SoftDelete;

    //模型名
    protected $name = 'customer';

    public function merchant()
    {
        return $this->belongsTo('app\model\admin\Merchant','merchant_id','id')->removeOption('soft_delete');
    }
    public function thread()
    {
        return $this->hasMany('app\model\admin\Thread','customer_id','id')->whereDay('create_time')->where('is_test', 0)->where('is_assign', 0)->where('is_special_channel_customer', 0)->where('assign_mode', 0)->where('is_register', 0);
    }
    //无线线索
    public function validThreadNums()
    {
        return $this->hasMany('app\model\admin\Thread','customer_id','id')->whereDay('create_time')->where('is_valid', 0)->where('is_register', 0);
    }
    public function organization()
    {
        return $this->belongsTo('app\model\admin\MerchantOrganization','merchant_organization_id','id')->removeOption('soft_delete');
    }
}
