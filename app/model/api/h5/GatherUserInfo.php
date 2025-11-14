<?php
/**
 * 用户收集信息表模型
 */

namespace app\model\api\h5;

use laytp\BaseModel;
use think\model\concern\SoftDelete;
use app\lib\api\exception\Exception;
class GatherUserInfo extends BaseModel
{
    use SoftDelete;
    //模型名
    protected $name = 'gather_user_info';

    //根据id获取name值
    public static function getFormatGatherInfo($selectedId = 0, $field = 'age_range_id')
    {
        $name = '';
        $gatherInfoList = [];
        $gatherInfo = [];
        $gatherInfoData = self::field('title,field,gather_info_json')->where('field', $field)->find();
        if (!empty($gatherInfoData)) {
            if (isset($gatherInfoData['gather_info_json']) && !empty($gatherInfoData['gather_info_json'])) {
                $gatherInfoList = json_decode($gatherInfoData['gather_info_json'], true);
            }
            $gatherInfo = array_column($gatherInfoList, 'name', 'id');
        }
        return [
        //    'gatherInfoList' => $gatherInfoList,
            'name' => is_array($gatherInfo) && isset($gatherInfo[$selectedId]) ? $gatherInfo[$selectedId] : $name,
        ];
    }
}
