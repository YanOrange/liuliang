<?php
/**
 * 后台课程标签表模型
 */

namespace app\model\admin\part;

use laytp\BaseModel;
use think\model\concern\SoftDelete;
use app\model\admin\part\Course;
class PartCourseTag extends BaseModel
{
    use SoftDelete;

    //模型名
    protected $name = 'part_course_tag';

    protected $append = [
        'quote_numbers'
    ];
    //引用次数
    public function getQuoteNumbersAttr($value, $data)
    {
        $quoteNumbers = 0;
        if (isset($data['id']) && !empty($data['id'])) {
            $quoteNumbers = Course::whereFindInSet('tag_ids', $data['id'])->count();
        }
        return $quoteNumbers;
    }
}
