<?php
/**
 * 渠道表模型
 */

namespace app\model\api\learn;

use app\lib\api\service\PartMerchantService;
use app\model\admin\LearnBanner;
use laytp\BaseModel;
use app\lib\api\service\WeightService;
use app\model\api\Channel;
use think\model\concern\SoftDelete;
use app\lib\api\exception\Exception;
use app\model\api\Course;
use app\model\api\Merchant;
use app\model\api\fortunecat\PartClass;

class LearnCourse extends BaseModel
{
    use SoftDelete;
    //模型名
    protected $name = 'learn_course';

    public static function getCourseDetail($params)
    {
        extract($params);
        $channelInfo = Channel::getChannelAppClass($channel);
        $courseInfo = self::with(['teacher' => function($query){
            $query->field('id,teacher_name,teacher_image');
        },'section' => function($query){
            $query->where('section_pid','>',0)
                ->whereTime('video_live_time','>',date('Y-m-d H:i'))
                ->field('id,course_id,video_live_time')
                ->order('video_live_time desc');
        }])
            ->where('id',$course_id)
            ->field('id,title,desc_image,entry_fee,teacher_id,virtual_apply_nums,content,purchase_notes,course_type')
            ->find();
        $courseSection = LearnCourseSection::with(['joint' => function($query) use($course_id){
            $query->field('id,section_title,video_type,section_video_url')
                ->order('id asc');
        }])->where('section_pid',0)
            ->where('course_id',$course_id)
            ->field('id,section_title')
            ->order('id asc')
            ->paginate(50)
            ->each(function($item){
                    $item['joint'] = LearnCourseSection::where('section_pid',$item['id'])
                        ->field('id,section_title,video_type,video_live_time,section_video_url')
                        ->select()
                        ->toArray();
                    return $item;
                })
            ->toArray();
        $finishJoint = 0;
        foreach($courseSection['data'] as &$item){
            foreach($item['joint'] as &$item1){
                $courseVideoRecord = LearnCourseVideoRecord::where('uid',$GLOBALS['uid'])
                    ->where('course_section_id',$item1['id'])
                    ->find();
                $item1['is_play'] = 0;
                $item1['video_duration'] = '';
                $item1['play_duration_rate'] = '';
                if(!empty($courseVideoRecord)){
                    if($courseVideoRecord->play_duration > 0 && $courseVideoRecord->video_duration > 0){
                        if($courseVideoRecord->play_duration == $courseVideoRecord->video_duration){
                            $finishJoint++;
                        }
                        $item1['video_duration'] = convert($courseVideoRecord->video_duration);
                        $item1['play_duration_rate'] = round(($courseVideoRecord->play_duration/$courseVideoRecord->video_duration) * 100,2).'%';
                    }
                }
                if(strtotime($item1['video_live_time']) <= time()){
                    $item1['is_play'] = 1;
                }
                $item1['video_live_time'] = date('m月d日',strtotime($item1['video_live_time']));
            }
        }
        if(!empty($courseInfo)) $courseInfo = $courseInfo->toArray();
        $partMerchantService = new PartMerchantService();
        $applyMerchant = $partMerchantService->getPartMerchantId($course_id,$channelInfo);
        $merchantId = $applyMerchant['merchant_id'];
        $courseInfo['course_id'] = Course::where('merchant_id',$merchantId)->where('course_type',0)->value('id');
        $courseInfo['next_course_video'] = LearnCourseSection::where('course_id',$course_id)
            ->where('section_pid','>',0)
            ->where('id','>',$courseInfo['section']['id'])
            ->field('id,section_video_url')
            ->order('id asc')
            ->find();
        $courseInfo['course_tag'] = '永久可看';
        $courseInfo['is_apply'] = Thread::where('learn_course_id',$course_id)->where('uid',$GLOBALS['uid'])->count() > 0 ? 1 : Thread::where('course_id',$courseInfo['course_id'])->where('uid',$GLOBALS['uid'])->count() > 0 ? 1 : 0;
        $courseInfo['btn_desc'] = '立即报名';
        $courseInfo['is_jump_miniprogram'] = Merchant::where('id',$merchantId)->value('is_jump_miniprogram');
        $courseInfo['is_collect'] = LearnCourseCollect::where('uid',$GLOBALS['uid'])
            ->where('course_id',$course_id)
            ->count() > 0 ? 1 : 0;
        $totalJoint = LearnCourseSection::where('course_id',$course_id)
            ->where('section_pid','>',0)
            ->count();
        $courseInfo['finish_joint'] = $finishJoint;
        $courseInfo['total_joint'] = $totalJoint;
        $data['courseInfo'] = $courseInfo;
        $data['courseSection'] = $courseSection['data'];
        return $data;
    }

    public static function getCourseList($params)
    {
        extract($params);
        $where = [];
        if(isset($app_class_id) && is_numeric($app_class_id)){
            $where[] = ['app_class_id','=',$app_class_id];
        }
        $channelInfo = Channel::getChannelAppClass($channel);
        $courseClass = self::with(['class' => function($query){
            $query->field('id,app_class_name');
        }])
            ->where('status',1)
            ->where('course_type',2)
            ->whereFindInSet('channel_ids',$channelInfo['channel_id'])
            ->field('id,app_class_id')
            ->group('app_class_id')
            ->select()
            ->toArray();
        if(!empty($courseClass)){
            foreach($courseClass as $item){
                $data['class'][] = $item['class'];
            }
        }
        $data['courseImage'] = LearnBanner::where('status',1)
            ->whereFindInSet('channel_ids',$channelInfo['channel_id'])
            ->where('banner_type',4)
            ->value('image');
        $data['course'] = self::with(['teacher' => function($query){
            $query->field('id,teacher_name');
        },'section' => function($query){
            $query->where('section_pid','>',0)
                ->whereTime('video_live_time','>',date('Y-m-d H:i'))
                ->field('id,course_id,video_live_time')
                ->order('video_live_time desc');
        }])
            ->where($where)
            ->where('status',1)
            ->where('course_type',2)
            ->whereFindInSet('channel_ids',$channelInfo['channel_id'])
            ->field('id,title,desc_image,entry_fee,teacher_id,virtual_apply_nums,content,purchase_notes')
            ->paginate(10)
            ->toArray();
        return $data;
    }

    public static function getLearnCourseList($params)
    {
        extract($params);
        $where = [];
        if(isset($title) && !empty($title)){
            $where[] = ['title','like','%'.$title.'%'];
        }
        if(isset($class_id) && !empty($class_id)){
            $where[] = ['online_class_id','=',$class_id];
        }
        $channelInfo = Channel::getChannelAppClass($channel);
        $data['courseImage'] = LearnBanner::where('status',1)
            ->whereFindInSet('channel_ids',$channelInfo['channel_id'])
            ->where('banner_type',1)
            ->value('image');
        $courseList = self::where($where)
            ->where('status',1)
            //->where('course_type',2)
            ->whereFindInSet('channel_ids',$channelInfo['channel_id'])
            ->field('id,title,desc_image,course_type')
            ->paginate(10)
            ->toArray();
        $data['courseList'] = $courseList;
        return $data;
    }

    public static function getCourseClass($params)
    {
        extract($params);
        $courseClass = self::with(['partClass' => function($query){
            $query->field('id,part_class_name');
        }])
            ->where('status',1)
            //->where('course_type',2)
            //->whereFindInSet('channel_ids',$channelInfo['channel_id'])
            ->field('id,online_class_id')
            ->group('online_class_id')
            ->select()
            ->toArray();
        $data = [];
        foreach($courseClass as $item){
            if(!empty($item['partClass'])){
                $data[] = $item['partClass'];
            }
        }
        array_unshift($data,['id' => 0,'part_class_name' => '全部课程']);
        return $data;
    }

    public static function getTeacherInfo($params)
    {
        extract($params);
        $teacherInfo = LearnTeacher::where('id',$teacher_id)
            ->field('id,teacher_name,teacher_image,teacher_tags,qualification,experience')
            ->find();
        if(!empty($teacherInfo)){
            $teacherInfo['teacher_tags'] = json_decode($teacherInfo['teacher_tags'],true);
        }
        return $teacherInfo;
    }

    //收藏网课
    public static function collectCourse($params)
    {
        extract($params);
        $collectCourse = LearnCourseCollect::where('uid',$GLOBALS['uid'])
            ->where('course_id',$course_id)
            ->find();
        $isCollect = 0;
        if(!empty($collectCourse)){
            $collectCourse->delete_time = time();
            $collectCourse->save();
        }else {
            $isCollect = 1;
            LearnCourseCollect::create([
                'uid' => $GLOBALS['uid'],
                'course_id' => $course_id
            ]);
        }
        return ['is_collect' => $isCollect];
    }

    public function teacher()
    {
        return $this->belongsTo('app\model\api\learn\LearnTeacher','teacher_id','id')->removeOption('soft_delete');
    }

    public function section()
    {
        return $this->belongsTo('app\model\api\learn\LearnCourseSection','id','course_id')->removeOption('soft_delete');
    }

    public function class()
    {
        return $this->belongsTo('app\model\api\AppClass','app_class_id','id')->removeOption('soft_delete');
    }

    public function partClass()
    {
        return $this->belongsTo('app\model\api\fortunecat\PartClass', 'online_class_id','id')->removeOption('soft_delete');
    }

}
