<?php
/**
 * 课程收藏表模型
 */

namespace app\model\api;

use laytp\BaseModel;
use think\model\concern\SoftDelete;
use app\lib\api\exception\Exception;
use app\model\api\CourseLabel;
use app\model\api\Course;
use think\facade\Db;
class CourseCollect extends BaseModel
{
    use SoftDelete;
    //模型名
    protected $name = 'course_collect';
    protected $hidden = [
        'uid',
    ];

    //是否收藏
    public static function checkCourseCollect($params = [])
    {
        extract($params);
        if (!isset($GLOBALS['uid']))  return 0;
        $isCourseCollect = self::where('uid', $GLOBALS['uid'])->where('course_id', $course_id)->count();
        return $isCourseCollect ? 1 : 0;
    }
    //获取课程收藏列表
    public static function getCourseCollectList($params = [])
    {
        extract($params);
        return self::field('id,course_id,uid')
            ->with(['course'])
            ->where('uid', $GLOBALS['uid'])
            ->order('id desc')
            ->paginate($pagesize);
    }

    //添加课程收藏
    public static function addCourseCollect($params = [])
    {
        extract($params);
        $checkCourse = Course::where('id', $course_id)->count();
        if (!$checkCourse) {
            new Exception('收藏的课程不存在');
        }
        if (self::checkCourseCollect($params)) {
            new Exception('该课程已经收藏过了');
        }
        $ret = self::create([
            'uid' => $GLOBALS['uid'],
            'course_id' => $course_id,
        ]);
        if ($ret !== false) {
            Course::where('id',$course_id)->update(['collect_nums' => Db::raw('collect_nums+1')]);
            return;
        }
        new Exception('收藏失败');
    }
    //取消课程收藏
    public static function cancelCourseCollect($params = [])
    {
        extract($params);
        $courseCollect = self::where('uid', $GLOBALS['uid'])->where('course_id', $course_id)->find();
        if ($courseCollect) {
            $ret = $courseCollect->force()->delete();
            if ($ret !== false) {
                Course::where('id',$course_id)->where('collect_nums', '>', 0)->update(['collect_nums' => Db::raw('collect_nums-1')]);
                return;
            }
            new Exception('取消收藏失败');
        }
        new Exception('取消收藏的课程不存在');
    }
    public function course()
    {
        return $this->belongsTo('app\model\api\Course','course_id','id')->field('id,title,label_ids,compensation,compensation_type')->removeOption('soft_delete');
    }
}
