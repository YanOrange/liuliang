<?php

namespace app\model\api\single;

use app\lib\api\other\CourseJumpWx;
use app\lib\api\service\MerchantServiceOverdue;
use app\model\api\Channel;
use app\model\api\fortunecat\UserList;
use app\model\api\Merchant;
use app\model\api\single\Thread;
use laytp\BaseModel;
use think\facade\Request;
use think\model\concern\SoftDelete;
use app\model\api\single\StudyVideoResourse;
use app\model\api\single\Evaluate;
use app\lib\api\other\CommonCourse;
use app\lib\api\other\CommonCourseV2;

use app\model\api\Course;
use app\lib\api\exception\ExceptionStd;
use app\model\api\Article;
class SingleCourse extends BaseModel
{
    use SoftDelete;
    //表名
    protected $name = 'single_course';

    protected $hidden = [
        'merchant'
    ];

    protected $append = [
        'is_under_eighteen_apply'
    ];

    protected $no_jump_miniprogram_channel = ['quxueps_vivo'];

    public static function getCourseDetail($params = [])
    {
        extract($params);
        $courseInfo = self::where('id',$course_id)
            ->field('id,title,video_cover_image,video_url,entry_fee,virtual_apply_nums,course_desc,content,btn_desc,course_ids,video_resource_ids,flow_desc,confirm_copy_desc')
            ->find();
        if(!empty($courseInfo)) {
            $courseInfo['is_apply'] = 0;
            $courseInfo['is_descern_qrcode'] = 0;
            $courseInfo['is_jump_miniprogram'] = 0;
            $courseInfo['confirm_success_title'] = '你已成功报名该课程';
            $courseInfo['confirm_success_desc'] = '你已成功锁定限量课程名额，请立即添加老师微信，老师会在24小时内容与你沟通，请注意你的手机';
            $courseInfo['video_resource_list'] = [];
            $courseInfo['course_list'] = [];
            $courseInfo['evaluate_list'] = [];
            $courseInfo->flow_desc = !empty($courseInfo->flow_desc) ? json_decode($courseInfo->flow_desc,true) : [];
            $courseInfo->confirm_copy_desc = !empty($courseInfo->confirm_copy_desc) ? json_decode($courseInfo->confirm_copy_desc,true) : [];
           /* $threadInfo = Thread::with(['merchant' => function($query){
                    $query->field('id,is_jump_miniprogram');
                }])
                ->where('uid',$GLOBALS['uid'])
                ->where('course_id',$course_id)
                ->field('id,merchant_id,is_discern_qrcode')
                ->order('id desc')
                ->find();
            if(!empty($threadInfo)){
                $courseInfo['is_apply'] = 1;
                $courseInfo['btn_desc'] = isset($courseInfo['merchant']['is_jump_miniprogram']) && $courseInfo['merchant']['is_jump_miniprogram'] == 1 ? '添加老师微信' : '添加老师微信';
                $courseInfo['is_discern_qrcode'] = isset($threadInfo['is_discern_qrcode']) ? $threadInfo['is_discern_qrcode'] : 0;
                $courseInfo['is_jump_miniprogram'] = isset($threadInfo['merchant']['is_jump_miniprogram']) ? $threadInfo['merchant']['is_jump_miniprogram'] : 0;
            }*/
            $courseInfo['course'] = CommonCourse::getCommonCourseToCourseId($courseInfo->id, isset($channel) && !empty($channel) ? $channel : UserList::where('id', $GLOBALS['uid'])->value('channel'));
            if(!empty($courseInfo->video_resource_ids)){
                $videoResourceIds = explode(',',$courseInfo->video_resource_ids);
                $videoResourceList = StudyVideoResourse::whereIn('id',$videoResourceIds)
                    ->field('id,video_title,video_url')
                    ->order('id desc')
                    ->select()
                    ->toArray();
                $courseInfo['video_resource_list'] = $videoResourceList;
            }
            if(!empty($courseInfo->course_ids)){
                $courseIds = explode(',',$courseInfo->course_ids);
                $courseList = self::whereIn('id',$courseIds)
                    ->field('id,title,video_cover_image,course_desc,entry_fee,virtual_apply_nums')
                    ->order('id desc')
                    ->select()
                    ->toArray();
                $courseInfo['course_list'] = [];//$courseList;
            }
            $evaluateList = Evaluate::where('be_evaluated_id',$course_id)
                ->where('status',1)
                ->where('be_evaluated_type',1)
                ->field("nickname,avatar,score,content,create_time")
                ->order('id desc')
                ->limit(20)
                ->select()
                ->toArray();
            $courseInfo['evaluate_list'] = $evaluateList;
        }
        return $courseInfo;
    }

    /**
     * 单商户2.0学习课程列表
     */
    public static function getStudyCourseList20($params = [])
    {
        extract($params);
        $channelInfo = Channel::getChannelAppClass($channel);
        $where = Course::getFeeCondition($channelInfo);
        $caseList = self::field('id,title,course_desc,video_cover_image,btn_desc')
            ->where('status', 1)
          //  ->where('course_type' , 2)
            ->whereFindInSet('app_ids', $channelInfo['app_id'])
            ->where($where)
            ->limit(2)
            ->select()
            ->toArray();
        $handpickList = self::field('id,title,course_desc,content,video_cover_image,btn_desc')
            ->where('status', 1)
          //  ->where('course_type' , 2)
            ->whereFindInSet('app_ids', $channelInfo['app_id'])
            ->where($where)
            ->select()
            ->toArray();
        $tempData = [
           "1" => ['topBgImage' => 'http://cdnwm.yuluojishu.com/uploads/20230531/a8b9f686c631705c704d53f6c1133d4f.png', 'topIconTitleList' =>  [
            ['id' => isset($handpickList[0]['id']) ? $handpickList[0]['id'] : 0,'icon_title' => '初级教程'],
            ['id' => isset($handpickList[1]['id']) ? $handpickList[1]['id'] : 0,'icon_title' => '中级教程'],
            ['id' => isset($handpickList[2]['id']) ? $handpickList[2]['id'] : 0,'icon_title' => '数据库'],
        ]],
           "9" => ['topBgImage' => 'http://cdnwm.yuluojishu.com/uploads/20230606/12ada8e89db0f9568d0dc88bfb5175ec.png','topIconTitleList' =>  [
            ['id' => isset($handpickList[0]['id']) ? $handpickList[0]['id'] : 0,'icon_title' => '债务重组'],
            ['id' => isset($handpickList[1]['id']) ? $handpickList[1]['id'] : 0,'icon_title' => '停息挂账'],
            ['id' => isset($handpickList[2]['id']) ? $handpickList[2]['id'] : 0,'icon_title' => '延期还款'],
        ]],
           "10" => ['topBgImage' => 'http://cdnwm.yuluojishu.com/uploads/20230531/a8b9f686c631705c704d53f6c1133d4f.png','topIconTitleList' =>  [
            ['id' => isset($handpickList[0]['id']) ? $handpickList[0]['id'] : 0,'icon_title' => '初级教程'],
            ['id' => isset($handpickList[1]['id']) ? $handpickList[1]['id'] : 0,'icon_title' => '中级教程'],
            ['id' => isset($handpickList[2]['id']) ? $handpickList[2]['id'] : 0,'icon_title' => '数据库'],
        ]],
           "12" => ['topBgImage' => 'http://cdnwm.yuluojishu.com/uploads/20230531/a8b9f686c631705c704d53f6c1133d4f.png','topIconTitleList' =>  [
            ['id' => isset($handpickList[0]['id']) ? $handpickList[0]['id'] : 0,'icon_title' => '初级教程'],
            ['id' => isset($handpickList[1]['id']) ? $handpickList[1]['id'] : 0,'icon_title' => '中级教程'],
            ['id' => isset($handpickList[2]['id']) ? $handpickList[2]['id'] : 0,'icon_title' => '数据库'],
        ]],
           "15" => ['topBgImage' => 'http://cdnwm.yuluojishu.com/uploads/20230531/a8b9f686c631705c704d53f6c1133d4f.png','topIconTitleList' =>  [
            ['id' => isset($handpickList[0]['id']) ? $handpickList[0]['id'] : 0,'icon_title' => '初级教程'],
            ['id' => isset($handpickList[1]['id']) ? $handpickList[1]['id'] : 0,'icon_title' => '中级教程'],
            ['id' => isset($handpickList[2]['id']) ? $handpickList[2]['id'] : 0,'icon_title' => '数据库'],
        ]],
        ];
        foreach($handpickList as &$item){
            $item['content'] = getEditContentText($item['content'],300);
        }
        $topBgImage = isset($tempData[$channelInfo['app_class_id']]['topBgImage']) ? $tempData[$channelInfo['app_class_id']]['topBgImage'] : 'http://cdnwm.yuluojishu.com/uploads/20230531/a8b9f686c631705c704d53f6c1133d4f.png';
        $topIconTitleList = isset($tempData[$channelInfo['app_class_id']]['topIconTitleList']) ? $tempData[$channelInfo['app_class_id']]['topIconTitleList'] : [];
        return compact('caseList', 'handpickList', 'topBgImage', 'topIconTitleList');
    }

    /**
     * 单商户2.0学习课程详情
     */
    public static function getStudyCourseDetail20($params = [])
    {
        extract($params);
        $courseInfo = self::field('id,title,video_cover_image,video_url,entry_fee,content,btn_desc,teacher_avatar,teacher_name,teacher_desc')->find($course_id);
        if (empty($courseInfo)) {
            new ExceptionStd('课程不存在或已下架');
        }
        $channelInfo = Channel::getChannelAppClass($channel);
        $courseInfo = $courseInfo->toArray();
        unset($courseInfo['is_under_eighteen_apply']);
        $course = CommonCourseV2::getCommonCourseToCourseId($channel);
        $courseInfo['is_jump_miniprogram'] = $course['is_jump_miniprogram'];
        $courseInfo['is_apply'] = $course['is_apply'];
        if ($course['is_apply']) {
            $courseInfo['btn_desc'] = $course['is_jump_miniprogram'] == 1 ? '立即添加微信' : '在线沟通';
        }
        $courseInfo['part_course_id'] = $courseInfo['id'];
        $courseInfo['id'] = $course['course_id'];
        $courseInfo['articleList'] = Article::getMerchantArticleList($course['merchant_id'], $channelInfo);
        return $courseInfo;

    }

    //单机构课程详情
    public static function getSingleCourseDetail($params = [])
    {
        extract($params);
        $courseInfo = self::where('id', $course_id)
            ->field('id,title,video_cover_image,video_url,course_desc,content')
            ->find();
        $courseInfo['shouCourse'] = [];
        if (!empty($courseInfo)) {
            $merchantCourse = MerchantServiceOverdue::getOverdueMerchant($channel);
            $thread = \app\model\api\Thread::where('uid', $GLOBALS['uid'])->order('id desc')->find();
            if (empty($thread)) {
                $merchantCourse = MerchantServiceOverdue::getOverdueMerchant($channel);
            }
            $apply_success_msg = Merchant::where('id', isset($thread->merchant_id) ? $thread->merchant_id : $merchantCourse['merchant_id'])->value('apply_success_msg');
            $courseId = isset($thread->course_id) ? $thread->course_id : $merchantCourse['course_id'];
            $course = Course::field('id,video_url,btn_desc')->where('id',$courseId)->find();
            $courseInfo['shouCourse'] = [
                'course_id' => $courseId,
                'video_url' => $course->video_url ?? '',
                'is_jump_miniprogram' => isset($thread->course_id) ? CourseJumpWx::getCourseJumpWxStatus($thread->course_id, $channel) : CourseJumpWx::getCourseJumpWxStatus($merchantCourse['course_id'], $channel),
                'apply_success_msg' => $apply_success_msg,
                'btn_desc' => $course->btn_desc ?? '立即咨询债务解决方案',
                'is_apply' => !empty($thread) ? 1 : 0,
            ];
        }
        return $courseInfo;
    }

    //报名人数
    public function getIsUnderEighteenApplyAttr($value, $data)
    {
        $isUnderEighteenApply = 1;
        $channel = request()->post('channel');
        if (!empty($channel)) {
            $isUnderEighteenApply = Channel::where('channel_name', $channel)->value('is_under_eighteen_apply');
        }
        return $isUnderEighteenApply ?? 0;

    }
    //报名人数
    public function getVirtualApplyNumsAttr($value, $data)
    {
        $applyNums = Thread::where('course_id', $data['id'])->count();
        return isset($data['virtual_apply_nums']) ? $data['virtual_apply_nums'] + $applyNums : $applyNums;
    }

}