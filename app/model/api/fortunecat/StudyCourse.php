<?php

namespace app\model\api\fortunecat;

use app\lib\api\exception\Exception;
use app\model\api\Channel;
use laytp\BaseModel;
use think\model\concern\SoftDelete;
use app\model\api\fortunecat\Thread;
use app\model\api\fortunecat\StudyVideoResource;
use app\model\api\fortunecat\StudyVideoResourceFinish;

class StudyCourse extends BaseModel
{
    use SoftDelete;
    //模型
    protected $name = 'course';

    /**
     * 学习课程列表
     */
    public static function getStudyCourseList($params = [])
    {
        extract($params);
        $channel = isset($channel) && !empty($channel) ? $channel : '';
        $limit_num = 10;
        $data = [];
        if($channel) {
            $where['uid'] = ['=',$GLOBALS['uid']];
            $studyCourse = Thread::with(['course' => function($query){
                    $query->where('course_type',2);
                    $query->field('id,title,video_cover_image,video_resource_ids');
                }])
                ->where($where)
                ->field('id,course_id')
                ->order('id desc')
                ->paginate($limit_num)
                ->toArray();
            $studyCourseList = isset($studyCourse['data']) && !empty($studyCourse['data']) ? $studyCourse['data'] : [];
            if(!empty($studyCourseList)) {
                foreach($studyCourseList as $key => $val){
                    $studyCourseList[$key]['title'] = isset($val['course']['title']) ? $val['course']['title'] : '';
                    $studyCourseList[$key]['video_cover_image'] = isset($val['course']['video_cover_image']) ? $val['course']['video_cover_image'] : '';
                    $studyCourseList[$key]['video_resource_ids'] = isset($val['course']['video_resource_ids']) ? $val['course']['video_resource_ids'] : '';
                    $studyCourseList[$key]['study_finish_rate'] = '0%';
                    if(!empty($val['video_resource_ids'])){
                        $studyResourceIds = explode(',',$val['video_resource_ids']);
                        $studyVideoResourceStatus = StudyVideoResourceFinish::where('uid',$GLOBALS['uid'])->whereIn('video_resource_id',$studyResourceIds)->count();
                        $studyCourseList[$key]['study_finish_rate'] = $studyVideoResourceStatus > 0 ? round($studyVideoResourceStatus/count($studyResourceIds),2).'%' : '0%';
                    }
                    unset($studyCourseList[$key]['course']);
                    unset($studyCourseList[$key]['video_resource_ids']);
                    if(empty($studyCourseList[$key]['title'])){
                        unset($studyCourseList[$key]);
                    }
                }
                $data = array_values($studyCourseList);
            }
        }
        return $data;
    }

    /**
     * 学习课程详情
     */
    public static function getStudyCourseDetail($params = [])
    {
        extract($params);
        $course_id = isset($course_id) && !empty($course_id) ? $course_id : 0;
        $thread_id = isset($thread_id) && !empty($thread_id) ? $thread_id : 0;
        $channel = isset($channel) && !empty($channel) ? $channel : '';
        $courseInfo = [];
        if($thread_id && $channel){
            $courseInfo = Thread::with(['course' => function($query){
                    $query->where('course_type',2);
                    $query->field('id,title,video_cover_image,is_force_wx,gather_form_json,video_resource_ids');
                }])
                ->where('id',$thread_id)
                ->field('id,course_id')
                ->find();
            if(!empty($courseInfo)){
                $courseInfo = $courseInfo->toArray();
                $courseInfo['title'] = isset($courseInfo['course']['title']) ? $courseInfo['course']['title'] : '';
                $courseInfo['video_cover_image'] = isset($courseInfo['course']['video_cover_image']) ? $courseInfo['course']['video_cover_image'] : '';
                $courseInfo['is_force_wx'] = isset($courseInfo['course']['is_force_wx']) ? $courseInfo['course']['is_force_wx'] : 0;
                $courseInfo['is_force_wx'] = isset($courseInfo['course']['is_force_wx']) ? $courseInfo['course']['is_force_wx'] : 0;
                $courseInfo['video_resource_ids'] = isset($courseInfo['course']['video_resource_ids']) ? $courseInfo['course']['video_resource_ids'] : '';
                $courseInfo['gather_form_json'] = isset($courseInfo['course']['gather_form_json']) && !empty($courseInfo['course']['gather_form_json']) ? json_decode($courseInfo['course']['gather_form_json'],true) : [];
                $courseInfo['study_task_total'] = 0;
                $courseInfo['study_task_finish'] = 0;
                $videoResourceIds = !empty($courseInfo['video_resource_ids']) ? explode(',',$courseInfo['video_resource_ids']) : [];
                $courseInfo['study_video_resource'] = StudyVideoResource::whereIn('id',$videoResourceIds)
                    ->field('id,video_title,video_url,study_nums')
                    ->select();
                if(!empty($courseInfo['study_video_resource'])){
                    foreach($courseInfo['study_video_resource'] as &$val)
                    {
                        $studyVideoResourceFinish = StudyVideoResourceFinish::where('uid',$GLOBALS['uid'])
                            ->where('thread_id',$thread_id)
                            ->where('video_resource_id',$val['id'])
                            ->find();
                        $val['is_finish'] = !empty($studyVideoResourceFinish) ? 1 : 0;
                        $courseInfo['study_task_finish'] = !empty($studyVideoResourceFinish) ? $courseInfo['study_task_finish']++ : $courseInfo['study_task_finish'] + 0;
                    }
                }
                foreach ($courseInfo['gather_form_json'] as $key => $val){
                    if((isset($val['form_name']) && empty($val['form_name'])) || (isset($val['form_href']) && empty($val['form_href']))){
                        unset($courseInfo['gather_form_json'][$key]);
                    }
                }
                array_values($courseInfo['gather_form_json']);
                if(count($videoResourceIds) > 0) $courseInfo['study_task_total'] = count($videoResourceIds);
                unset($courseInfo['course']);
                unset($courseInfo['video_resource_ids']);
            }
        }
        return $courseInfo ?? new \stdClass();
    }

    /**
     * 学习完成1
    */
    public static function setStudyVideoFinish($params = [])
    {
        extract($params);
        $course_id = isset($course_id) && !empty($course_id) ? $course_id : 0;
        $thread_id = isset($thread_id) && !empty($thread_id) ? $thread_id : 0;
        $video_resource_id = isset($video_resource_id) && !empty($video_resource_id) ? $video_resource_id : 0;
        if(!empty($thread_id) && !empty($video_resource_id)){
            $studyVideoResource = StudyVideoResourceFinish::where('thread_id',$thread_id)
                ->where('video_resource_id',$video_resource_id)
                ->find();
            if(empty($studyVideoResource)){
                try{
                    StudyVideoResourceFinish::create([
                        'uid' => $GLOBALS['uid'],
                        'thread_id' => $thread_id,
                        'video_resource_id' => $video_resource_id,
                        'is_finish' => 1
                    ]);
                }catch(\Exception $e){
                    new Exception($e->getMessage());
                }
            }
        }
        return [];
    }
}