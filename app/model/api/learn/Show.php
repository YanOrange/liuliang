<?php
/**
 * 首页
 */

namespace app\model\api\learn;
use app\model\api\Channel;
use app\model\api\Thread;
use laytp\BaseModel;
use app\lib\api\exception\Exception;
use think\facade\Config;
use app\lib\api\service\WeightService;
use app\model\api\learn\LearnCourse;
use app\model\api\learn\LearnCourseSection;
use app\model\api\Article;
use app\model\api\learn\Article as LearnArticle;
use app\model\admin\LearnBanner;
use app\model\api\AppClass;

class Show extends BaseModel
{
    //首页
    public static function homePage($params = [])
    {
        extract($params);
        $channelInfo = Channel::getChannelAppClass($channel);
        $data['banner_image'] = LearnBanner::where('status',1)
            ->whereFindInSet('channel_ids',$channelInfo['channel_id'])
            ->value('image');
        $article = Article::whereFindInSet('channel_ids',$channelInfo['channel_id'])
            //->where('article_type',4)
            ->field('id,title,content')
            ->order('id desc')
            ->find();
        $data['article_top'] = [
            'username' => LearnArticle::userNameAvatar()['name'][rand(0,6)],
            'avatar' => LearnArticle::userNameAvatar()['avatar'][rand(0,6)],
            'title' => $article['title'],
            'content' => $article['content']
        ];
        $appClassName = AppClass::where('id',$channelInfo['app_class_id'])->value('app_class_name');
        $data['middle_class_icon'] = [
            ['id' => 2,'icon' => 'http://cdnwm.yuluojishu.com/uploads/20230526/cf2883c37ee39bd171b44e53e7f2d2a6.png','text' => $appClassName.'教学','value' => 1],
            ['id' => 3,'icon' => 'http://cdnwm.yuluojishu.com/uploads/20230526/85574f9e1becf4374d5052ec12b4b817.png','text' => '职场测评','value' => '/pages/Home/components/place/place'],
            ['id' => 4,'icon' => 'http://cdnwm.yuluojishu.com/uploads/20230526/37a2e986018babadd6eee279a7994ac7.png','text' => '兴趣副学','value' => '/pages/Home/components/Micourses'],
        ];
        $courseSection = LearnCourseSection::with(['course' => function($query){
            $query->field('id,title,teacher_id,entry_fee,desc_image');
            $query->with(['teacher' => function($query1){
                $query1->field('id,teacher_name');
            }]);
        }])
            ->where('section_pid','>',0)
            ->whereTime('video_live_time','>',date('Y-m-d H:i'))
            ->field('course_id,video_live_time')
            ->group('course_id')
            ->order('video_live_time desc')
            ->limit(6)
            ->select()->toArray();
        $courseSectionNew = array_chunk($courseSection, 3);
        $courseSectionLive =  $courseSectionNew[0] ?? [];
        $courseSectionGroup =  isset($courseSectionNew[1]) && count($courseSectionNew[1]) > 0 ? $courseSectionNew[1] : $courseSectionLive;
        $data['live_open_course'] = [];
        $data['group_open_course'] = [];
        if(!empty($courseSectionLive)){
            foreach($courseSectionLive as $item){
                $data['live_open_course'][] =[
                    'id' => $item['course_id'],
                    'title' => $item['course']['title'] ?? '',
                    'teacher_name' => $item['course']['teacher']['teacher_name'] ?? '',
                    'video_live_time' => $item['video_live_time'] ?? date('Y-m-d H:i'),
                    'entry_fee' => $item['course']['entry_fee'] ?? 0,
                    'desc_image' => $item['course']['desc_image'] ?? '',
                    'group_num' => 2,
                    'group_avatar' => 'http://cdnwm.yuluojishu.com/uploads/20220510/83332417f43f2a95246d26a17ac49b53.jpg',
                    'joint_num' => LearnCourseSection::where('course_id',$item['course_id'])
                        ->where('section_pid','>',0)
                        ->count()
                ];
            }
        }
        if(!empty($courseSectionGroup)){
            foreach($courseSectionGroup as $item){
                $data['group_open_course'][] =[
                    'id' => $item['course_id'],
                    'title' => $item['course']['title'] ?? '',
                    'teacher_name' => $item['course']['teacher']['teacher_name'] ?? '',
                    'video_live_time' => $item['video_live_time'] ?? date('Y-m-d H:i'),
                    'entry_fee' => $item['course']['entry_fee'] ?? 0,
                    'desc_image' => $item['course']['desc_image'] ?? '',
                    'group_num' => 2,
                    'group_avatar' => 'http://cdnwm.yuluojishu.com/uploads/20220510/83332417f43f2a95246d26a17ac49b53.jpg',
                    'joint_num' => LearnCourseSection::where('course_id',$item['course_id'])
                        ->where('section_pid','>',0)
                        ->count()
                ];
            }
        }
        $data['interest_question'] = LearnInterestQuestion::where('status',1)
            ->field('id,interest_title,question_image,evaluation_num')
            ->order('id desc')
            ->limit(4)
            ->select()
            ->toArray();
        $data['online_course_list1'] = LearnCourse::where('course_type',1)
            ->where('status',1)
            ->whereFindInSet('channel_ids',$channelInfo['channel_id'])
            ->where('online_class_id',12)
            ->field('id,title,desc_image,entry_fee')
            ->order('sort desc id desc')
            ->select()
            ->toArray();
        if(!empty($data['online_course_list1'])){
            foreach($data['online_course_list1'] as &$item){
                $item['credit'] = rand(1,5);
                $item['course_type'] = '视频学习课';
            }
        }
        $data['online_course_list2'] = LearnCourse::where('course_type',1)
            ->where('status',1)
            ->whereFindInSet('channel_ids',$channelInfo['channel_id'])
            ->where('online_class_id',13)
            ->field('id,title,desc_image,entry_fee')
            ->order('sort desc id desc')
            ->select()
            ->toArray();
        if(!empty($data['online_course_list2'])){
            foreach($data['online_course_list2'] as &$item){
                $item['credit'] = rand(1,5);
                $item['course_type'] = '视频学习课';
            }
        }
        $courseInfo = LearnCourse::where('course_type',2)
            ->where('status',1)
            ->whereFindInSet('channel_ids',$channelInfo['channel_id'])
            ->field('id,desc_image')
            ->find();
        $data['showPop'] = [
            'course_id' => $courseInfo['id'],
            'pop_image' => $courseInfo['pop_image'],
            'is_show' => 0
        ];
        return $data;
    }

}
