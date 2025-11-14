<?php
/**
 * 后台应用分类表模型
 */

namespace app\model\admin;

use laytp\BaseModel;
use think\model\concern\SoftDelete;

class JzdCustomerVolumeAssign extends BaseModel
{
    use SoftDelete;

    //模型名
    protected $name = 'jzd_customer_volume_assign';

    public function customer()
    {
        return $this->belongsTo('app\model\admin\Customer','customer_id','id')->bind(['nickname','account_name','thread_status','status','day_night_shift','daily_intake_time_period'])->removeOption('soft_delete');
    }

    public function organization()
    {
        return $this->belongsTo('app\model\admin\MerchantOrganization', 'merchant_organization_id','id')->bind(['name'])->removeOption('soft_delete');
    }
}
