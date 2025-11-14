<?php
/**
 * 渠道表模型
 */

namespace app\model\api\learn;

use laytp\BaseModel;
use app\lib\api\service\WeightService;
use app\model\api\Channel;
use think\model\concern\SoftDelete;
use app\lib\api\exception\Exception;
class LearnCourseVideoRecord extends BaseModel
{
    use SoftDelete;
    //模型名
    protected $name = 'learn_course_video_record';

    public static function addCourseVideoRecord($params)
    {
        extract($params);
        $courseVideoRecordInfo = self::where('uid',$GLOBALS['uid'])
            ->where('course_section_id',$course_section_id)
            ->find();
        if(!empty($courseVideoRecordInfo)){
            if($play_duration > $courseVideoRecordInfo->play_duration){
                $courseVideoRecordInfo->play_duration = $play_duration;
                $courseVideoRecordInfo->save();
            }
        }else{
            self::create([
                'uid' => $GLOBALS['uid'],
                'course_id' => $course_id,
                'course_section_id' => $course_section_id,
                'video_duration' => $video_duration,
                'play_duration' => $play_duration
            ]);
        }
        return [];
    }

}
