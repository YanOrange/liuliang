<?php
/**
 * 评价表模型
 */

namespace app\model\admin\single;

use laytp\BaseModel;
use think\model\concern\SoftDelete;

class Evaluate extends BaseModel {
    use SoftDelete;

    //模型名
    protected $name = 'evaluate';

    // 追加属性
    protected $append = [
        'course_title',
        'resource_title',
    ];

    public function getCourseTitleAttr($value, $data) {
        if (!empty($data['be_evaluated_id'])) {
            $course = Course::field('title')->where('id', $data['be_evaluated_id'])->find();
            if (!empty($course)) {
                return $course->title;
            }
        }
        return '-';
    }

    public function getResourceTitleAttr($value, $data) {
        if (!empty($data['be_evaluated_id'])) {
            $resource = Resource::field('title')->where('id', $data['be_evaluated_id'])->find();
            if (!empty($resource)) {
                return $resource->title;
            }
        }
        return '-';
    }
}
