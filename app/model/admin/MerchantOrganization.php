<?php
/**
 * 后台支付配置表模型
 */

namespace app\model\admin;

use laytp\BaseModel;
use think\model\concern\SoftDelete;

class MerchantOrganization extends BaseModel
{
    use SoftDelete;

    //模型名
    protected $name = 'merchant_organization';

    public function customer()
    {
        return $this->belongsTo('app\model\admin\customer','id','merchant_organization_id')->removeOption('soft_delete');
    }

}