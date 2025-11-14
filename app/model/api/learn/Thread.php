<?php
/**
 * 报名表模型
 */

namespace app\model\api\learn;

use laytp\BaseModel;
use think\model\concern\SoftDelete;
use app\lib\api\exception\Exception;
use app\model\api\learn\LearnCourse;
use app\model\api\Channel;

class Thread extends BaseModel
{
    use SoftDelete;
    //模型名
    protected $name = 'thread';

    public static function getMyCourse($params)
    {
        extract($params);
        $channelInfo = Channel::getChannelAppClass($channel);
        $threadCourse = self::withCount(['section' => function($query){
                $query->where('section_pid','>',0);
            }])->withCount(['record' => function($query){
                $query->where('uid',$GLOBALS['uid']);
            }])->with(['course' => function($query){
                    $query->with(['teacher' => function($query){
                        $query->field('id,teacher_name');
                    }])->field('id,title,teacher_id,desc_image,course_type');
            }])
            ->where('uid',$GLOBALS['uid'])
            ->where('channel_id',$channelInfo['channel_id'])
            ->where('learn_course_id','>',0)
            ->field('id,learn_course_id')
            ->paginate(10)
            ->toArray();
        return $threadCourse;
    }

    public function course()
    {
        return $this->belongsTo('app\model\api\learn\LearnCourse','learn_course_id','id')->removeOption('soft_delete');
    }

    public function section()
    {
        return $this->belongsTo('app\model\api\learn\LearnCourseSection', 'learn_course_id','course_id')->removeOption('soft_delete');
    }

    public function record()
    {
        return $this->belongsTo('app\model\api\learn\LearnCourseVideoRecord', 'learn_course_id','course_id')->removeOption('soft_delete');
    }

}