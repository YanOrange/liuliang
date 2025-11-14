<?php
/**
 * 后台轮播图表模型
 */

namespace app\model\admin;

use laytp\BaseModel;
use think\model\concern\SoftDelete;

class Banner extends BaseModel
{
    use SoftDelete;

    //模型名
    protected $name = 'banner';

    // 追加属性
    protected $append = [
        'merchant_names',
        'module_name',
        'course_title'
    ];
    public function getMerchantNamesAttr($value, $data)
    {
        if (!empty($data['merchant_id'])) {
            $merchantArray = explode(',', $data['merchant_id']);
            $merchantNames = Merchant::field('merchant_name')->whereIn('id', $merchantArray)->select()->toArray();
            if (!empty($merchantNames)) {
                $merchantNamesList = array_column($merchantNames, 'merchant_name');
                return implode('、', $merchantNamesList);
            }
        }
        return '-';
    }

    public function getModuleNameAttr($value, $data)
    {
        $moduleName = '';
        if (!empty($data['jump_mode_json'])) {
            $jump_mode_json = !empty($data['jump_mode_json']) ? json_decode($data['jump_mode_json'],true) : [];
            $moduleId = isset($jump_mode_json['module_id']) ? $jump_mode_json['module_id'] : 0;
            $moduleName = AppModule::where('id',$moduleId)->value('module_name');
        }
        return $moduleName;
    }

    public function getCourseTitleAttr($value, $data)
    {
        $courseTitle = '';
        if (!empty($data['jump_mode_json'])) {
            $jump_mode_json = !empty($data['jump_mode_json']) ? json_decode($data['jump_mode_json'],true) : [];
            $courseId = isset($jump_mode_json['course_id']) ? $jump_mode_json['course_id'] : 0;
            $courseTitle = Course::where('id',$courseId)->value('title');
        }
        return $courseTitle;
    }


    /*public function merchant()
    {
        return $this->belongsTo('app\model\admin\Merchant','merchant_id','id')->removeOption('soft_delete');
    }*/

}
