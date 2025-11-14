<?php
/**
 * 后台应用表模型
 */

namespace app\model\admin;

use laytp\BaseModel;
use think\model\concern\SoftDelete;
use app\lib\api\exception\ExceptionStd;
use app\lib\api\vres\Aes;
class DistributionCollectionOrderBusiness extends BaseModel
{
    use SoftDelete;

    //模型名
    protected $name = 'distribution_collection_order';

    public function getPayTimeAttr($value, $data)
    {
        return !empty($data['pay_time']) ? date('Y-m-d H:i:s',$data['pay_time']) : '';
    }

    public function user()
    {
        return $this->belongsTo('\app\model\admin\UserListExternal', 'uid')->removeOption('soft_delete');
    }

    public function merchant()
    {
        return $this->belongsTo('\app\model\admin\Merchant', 'merchant_id')->removeOption('soft_delete');
    }

    public function customer()
    {
        return $this->belongsTo('\app\model\admin\Customer', 'customer_id')->removeOption('soft_delete');
    }

    public function channel()
    {
        return $this->belongsTo('\app\model\admin\Channel', 'channel_id')->removeOption('soft_delete');
    }

}
