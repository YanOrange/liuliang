<?php
/**
 * 后台兼职表模型
 */

namespace app\model\admin\part;

use app\model\admin\App;
use laytp\BaseModel;
use think\model\concern\SoftDelete;
use app\model\admin\part\PartClass;
use app\model\admin\part\PartCourseTag;
use think\facade\Config;
class Course extends BaseModel
{
    use SoftDelete;

    //模型名1
    protected $name = 'course';

    // 追加属性
    protected $append = [
        'tag_names',
        'app_names',
        'cate_names',
        'compensation_desc',
        'live_time',
        'live_status',
    ];
    public function getLiveStatusAttr($value, $data)
    {
        if (isset($data['live_start_time']) && isset($data['live_end_time'])) {
            $startTime = $data['live_start_time'];
            $endTime = $data['live_end_time'];
            $nowTime = time();
            if ($nowTime >= $startTime && $nowTime <= $endTime) {
                return '直播中';
            } elseif($nowTime < $startTime) {
                return '待直播';
            } else {
                return '已结束';
            }
        }
        return '-';
    }
    public function getLiveTimeAttr($value, $data)
    {
        if (isset($data['live_start_time']) && isset($data['live_end_time'])) {
            return date("Y/m/d H:i:s", $data['live_start_time']) . '~' . date("Y/m/d H:i:s", $data['live_end_time']);
        }
        return '-';
    }
    public function getCompensationDescAttr($value, $data)
    {
        if (isset($data['compensation']) && !empty($data['compensation'])) {
            $compensation = $data['compensation'];
            if (isset($data['compensation_type']) && !empty($data['compensation_type'])) {
                $compensationType = $data['compensation_type'];
                $manyorganizationConfig = Config::load("extra/manyorganization", "extra");
                $compensationTypeList = array_column($manyorganizationConfig['compensation_type_list'], 'name', 'value');
                $compensationTypeDesc = isset($compensationTypeList[$compensationType]) ? $compensationTypeList[$compensationType] : '';
                return '¥' . $compensation .'/'. $compensationTypeDesc;
            }
            return '¥' . $compensation;
        }
        return '-';
    }
    public function getTagNamesAttr($value, $data)
    {
        if (isset($data['tag_ids']) && !empty($data['tag_ids'])) {
            $tagArray = explode(',', $data['tag_ids']);
            $tagNames = PartCourseTag::field('tag_name')->whereIn('id', $tagArray)->select()->toArray();
            if (!empty($tagNames)) {
                $tagNamesList = array_column($tagNames, 'tag_name');
                return implode('、', $tagNamesList);
            }
        }
        return '-';
    }
    public function getCateNamesAttr($value, $data)
    {
        if (isset($data['part_class_ids']) && !empty($data['part_class_ids'])) {
            $cateArray = explode(',', $data['part_class_ids']);
            $cateNames = PartClass::field('part_class_name')->whereIn('id', $cateArray)->select()->toArray();
            if (!empty($cateNames)) {
                $cateNamesList = array_column($cateNames, 'part_class_name');
                return implode('、', $cateNamesList);
            }
        }
        return '-';
    }
    public function getAppNamesAttr($value, $data)
    {
        if (isset($data['app_ids']) && !empty($data['app_ids'])) {
            $appArray = explode(',', $data['app_ids']);
            $appNames = App::field('app_name')->whereIn('id', $appArray)->select()->toArray();
            if (!empty($appNames)) {
                $appNamesList = array_column($appNames, 'app_name');
                return implode('、', $appNamesList);
            }
        }
        return '-';
    }
    public function merchant()
    {
        return $this->belongsTo('app\model\admin\Merchant','merchant_id','id')->removeOption('soft_delete');
    }

    public function class()
    {
        return $this->belongsTo('app\model\admin\AppClass', 'app_class_id','id')->removeOption('soft_delete');
    }

}
