<?php
/**
 * 后台应用表模型
 */

namespace app\model\admin;

use laytp\BaseModel;
use think\model\concern\SoftDelete;
use app\lib\api\exception\ExceptionStd;
use app\lib\api\vres\Aes;
class DistributionPartnerUser extends BaseModel
{
    use SoftDelete;

    //模型名
    protected $name = 'distribution_partner_user';

    public function channel()
    {
        return $this->belongsTo('app\model\admin\Channel','channel_id','id')->removeOption('soft_delete');
    }

}
