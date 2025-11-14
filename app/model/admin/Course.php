<?php
/**
 * 后台课程表模型
 */

namespace app\model\admin;

use app\model\admin\App;
use laytp\BaseModel;
use think\model\concern\SoftDelete;

class Course extends BaseModel
{
    use SoftDelete;

    //模型名
    protected $name = 'course';

    // 追加属性
    protected $append = [
        'app_names',
        'merchant_course_title'
    ];
    public function getAppNamesAttr($value, $data)
    {
        if (!empty($data['app_ids'])) {
            $appArray = explode(',', $data['app_ids']);
            $appNames = App::field('app_name')->whereIn('id', $appArray)->select()->toArray();
            if (!empty($appNames)) {
                $appNamesList = array_column($appNames, 'app_name');
                return implode('、', $appNamesList);
            }
        }
        return '-';
    }

    public function getMerchantCourseTitleAttr($value, $data)
    {
        if (!empty($data['merchant_id'])) {
            $merchant = Merchant::field('merchant_name')->whereIn('id', $data['merchant_id'])->find();
            if (!empty($merchant)) {
                return '['.$merchant['merchant_name'].']'.$data['title'];
            }
        }
        return $data['title'];
    }

    public function merchant()
    {
        return $this->belongsTo('app\model\admin\Merchant','merchant_id','id')->removeOption('soft_delete');
    }

    public function class()
    {
        return $this->belongsTo('app\model\admin\AppClass','app_class_id','id')->removeOption('soft_delete');

    }

}
