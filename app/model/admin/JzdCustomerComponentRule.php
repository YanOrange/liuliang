<?php
/**
 * 后台应用分类表模型
 */

namespace app\model\admin;

use laytp\BaseModel;
use think\model\concern\SoftDelete;

class JzdCustomerComponentRule extends BaseModel
{
    use SoftDelete;

    //模型名
    protected $name = 'jzd_customer_component_rule';

    public function customer()
    {
        return $this->belongsTo('app\model\admin\Customer','customer_id','id')->bind(['nickname'])->removeOption('soft_delete');
    }

    public function organization()
    {
        return $this->belongsTo('app\model\admin\MerchantOrganization', 'merchant_organization_id','id')->bind(['name'])->removeOption('soft_delete');
    }
}
