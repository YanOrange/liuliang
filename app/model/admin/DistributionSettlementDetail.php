<?php
/**
 * 后台应用表模型
 */

namespace app\model\admin;

use laytp\BaseModel;
use think\model\concern\SoftDelete;
use app\lib\api\exception\ExceptionStd;
use app\lib\api\vres\Aes;
class DistributionSettlementDetail extends BaseModel
{
    use SoftDelete;

    //模型名
    protected $name = 'distribution_settlement_detail';

    public function getSettlementPeriodAttr($value, $data)
    {
        return !empty($data['settlement_period']) ? '第'.$data['settlement_period'].'周期' : '';
    }

}
