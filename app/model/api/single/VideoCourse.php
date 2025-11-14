<?php

namespace app\model\api\single;

use app\model\api\single\Thread;
use laytp\BaseModel;
use think\model\concern\SoftDelete;
use app\model\api\Channel;
use app\model\api\single\SingleCourse;
use app\model\api\single\Collect;

class VideoCourse extends BaseModel
{
    use SoftDelete;
    //表名
    protected $name = 'single_video_article';

    protected $append = [
        'is_apply',
        'is_like'
    ];

    //视频列表
    public static function getVideoList($params)
    {
        extract($params);
        $video_id = isset($video_id) ? $video_id : 0;
        $channelInfo = Channel::getChannelAppClass($channel);
        $videoList = [];
        if(!empty($channelInfo)){
            $videoList = self::with(['course' => function($query){
                    $query->field('id,title,apply_btn_image');
                }])
                ->where('status',1)
                ->where('type',1)
                ->where('id','<>',$video_id)
                ->whereFindInSet('app_ids',$channelInfo['app_id'])
                ->field('id,title,video_image,video_url,course_id,like_nums,share_nums')
                ->order(['sort'=>'desc','id'=>'desc'])
                ->select()
                ->toArray();
            $videoOne = self::with(['course' => function($query){
                    $query->field('id,title,apply_btn_image');
                }])
                ->where('id',$video_id)
                ->field('id,title,video_image,video_url,course_id,like_nums,share_nums')
                ->find()
                ->toArray();
            array_unshift($videoList,$videoOne);
            if(!empty($videoList)){
                foreach($videoList as &$val){
                    $val['is_select'] = 0;
                    if($video_id == $val['id']){
                        $val['is_select'] = 1;
                    }
                }
            }
        }
        return $videoList;
    }

    //课程落地页
    public static function getCourseDetail($params)
    {
        extract($params);
        $courseInfo = SingleCourse::where('id',$course_id)
            ->where('status',1)
            ->field('id,title,entry_fee,video_cover_image,video_entry_image,apply_btn_image,apply_course_image')
            ->find();
        if(!empty($courseInfo)) {
            $courseInfo['is_apply'] = 0;
            $courseInfo['is_descern_qrcode'] = 0;
            $courseInfo['is_jump_miniprogram'] = 0;
            $courseInfo['confirm_success_title'] = '你已成功报名该课程';
            $threadInfo = Thread::with(['merchant' => function ($query) {
                $query->field('id,is_jump_miniprogram');
            }])
                ->where('uid', $GLOBALS['uid'])
                ->where('course_id', $course_id)
                ->field('id,merchant_id,is_discern_qrcode')
                ->order('id desc')
                ->find();
            if (!empty($threadInfo)) {
                $courseInfo['is_apply'] = 1;
                $courseInfo['btn_desc'] = isset($courseInfo['merchant']['is_jump_miniprogram']) && $courseInfo['merchant']['is_jump_miniprogram'] == 1 ? '添加老师微信' : '添加老师微信';
                $courseInfo['is_discern_qrcode'] = isset($threadInfo['is_discern_qrcode']) ? $threadInfo['is_discern_qrcode'] : 0;
                $courseInfo['is_jump_miniprogram'] = isset($threadInfo['merchant']['is_jump_miniprogram']) ? $threadInfo['merchant']['is_jump_miniprogram'] : 0;
            }
        }
        return $courseInfo;
    }

    public function getIsApplyAttr($value,$data)
    {
        $isApply = 0;
        $threadCount  = Thread::where('uid',$GLOBALS['uid'])->where('course_id',$data['course_id'])->count();
        if($threadCount > 0) {
            $isApply = 1;
        }
        return $isApply;
    }

    public function getIsLikeAttr($value,$data)
    {
        $isLike = 0;
        $threadCount  = Collect::where('uid',$GLOBALS['uid'])->where('video_id',$data['id'])->count();
        if($threadCount > 0) {
            $isLike = 1;
        }
        return $isLike;
    }

    public static function setLikeNum($params)
    {
        extract($params);
        $likeCourse = Collect::where('uid',$GLOBALS['uid'])->where('video_id',$video_id)->find();
        if(empty($likeCourse)){
            $isLike = 1;
            Collect::create([
                'uid' => $GLOBALS['uid'],
                'video_id' => $video_id,
                'cource_type' => 3
            ]);
            self::where('id',$video_id)->save(['like_nums' => ['inc',1]]);
        }else{
            $isLike = 0;
            Collect::where('uid',$GLOBALS['uid'])->where('video_id',$video_id)->delete();
            self::where('id',$video_id)->where('play_nums','>',0)->save(['like_nums' => ['dec',1]]);
        }
        return ['is_like' => $isLike];
    }

    public function course()
    {
        return $this->belongsTo('app\model\api\single\SingleCourse','course_id','id')
            ->removeOption('soft_delete');
    }
}