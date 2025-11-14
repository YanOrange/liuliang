<?php
/**
 * 后台轮播图表模型
 */

namespace app\model\admin;

use laytp\BaseModel;
use think\model\concern\SoftDelete;

class JzdStagingList extends BaseModel
{
    use SoftDelete;

    //模型名
    protected $name = 'jzd_staging_list';

    public function getPaybackTimeAttr($value, $data)
    {
        return date('Y-m-d',strtotime($data['payback_time']));
    }

    public function getStagingPlatformAttr($value, $data)
    {
        $stagingPlatform = '';
        $platformArr = ['','校企服','诚学信付','倍好付','启辰宝'];
        $stagingPlatform = $platformArr[$data['staging_platform']] ?? '';
        return $stagingPlatform;
    }

    public function getUserMobileAttr($value, $data)
    {
        return phoneEncryption($data['user_mobile']);;
    }

    public function customer()
    {
        return $this->belongsTo('app\model\admin\Customer','customer_id','id')->removeOption('soft_delete');
    }

}
