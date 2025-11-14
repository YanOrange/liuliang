<?php

namespace app\model\admin;

use laytp\BaseModel;
use think\model\concern\SoftDelete;

class SingleVideoArticle  extends BaseModel
{
    use SoftDelete;
    //模型名
    protected $name = 'single_video_article';
    // 追加属性
    protected $append = [
        'app_names'
    ];

    public function getAppNamesAttr($value, $data) {
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


    public function course()
    {
        return $this->belongsTo('app\model\admin\single\Course','course_id','id')->removeOption('soft_delete');
    }

    public function tag()
    {
        return $this->belongsTo('app\model\admin\PartCourseTag','tag_id','id')->removeOption('soft_delete');
    }

    public function class()
    {
        return $this->belongsTo('app\model\admin\PartClass','class_id','id')->removeOption('soft_delete');
    }

}