<?php

namespace app\model\api\overdue;

use app\lib\api\service\MerchantServiceJob;
use app\lib\api\service\MerchantServiceOverdue;
use app\model\admin\NoJumpWechatPhone;
use app\model\api\Article;
use app\model\api\Channel;
use app\model\api\LandingPage;
use app\model\api\UserList;
use laytp\BaseModel;
use think\facade\Config;
use app\model\api\fortunecat\Banner;
use app\model\api\ArticleNews;
use app\model\api\Thread;
use app\model\api\overdue\OverdueOnshoreUser;
use app\model\api\Merchant;
use think\facade\Request;
use app\model\api\Course;
use app\model\api\overdue\OverdueTeam;
use app\lib\api\other\CourseJumpWx;
use app\lib\api\other\CommonCourseV2;
use app\lib\api\other\RandHorseData;
use app\model\api\v2\GatherUserInfo;
use app\model\api\single\SingleCourse;
use app\lib\api\other\CheckApply;

class Show extends BaseModel
{
    //债务还款优化首页
    public static function getZwhkyhFirstPage($params = [])
    {
        extract($params);
        $applyInfo  = CommonCourseV2::getCommonCourseToCourseId($channel);
        $channelInfo = Channel::getChannelAppClass($channel);
        $homeImage = Channel::where('channel_name', $channel)->value('overdue_version_home_image');
        $homeTopImage = empty($homeImage) || empty($homeShowImage = json_decode($homeImage, true)[0]) ? 'http://cdnwm.yuluojishu.com/uploads/20230620/405ea7823ce192c825a31d7fc25d99d9.png' : $homeShowImage;
        $iconNameData = ['债务重组', '协商还款', '停息挂账', '停止催收', '二次分期'];
       // $slideShowImageList = \app\model\api\v2\Banner::getMerchantBannerList($applyInfo['merchant_id'], $channelInfo);
        $slideShowImageList = [
            ['id' => 101, 'image' => 'http://cdnwm.yuluojishu.com/uploads/20230609/46acd78c64c61e01464bd91d023e28bb.png', 'jump_mode' => 0, 'jump_url' => '', 'jump_mode_json' => ['module_id' => 12, 'course_id' => 360]]
        ];
       // $newsList  = Article::field('id, title')->whereFindInSet('merchant_id', $applyInfo['merchant_id'])->select();
        $newsList  = Article::field('id, title')->whereIn('id', [104,282,103])->select();

        //$horseMobileData = RandHorseData::getRandHorsePhoneData();
        $horseMobileData = [];
        return compact('homeTopImage','iconNameData', 'applyInfo', 'slideShowImageList', 'newsList', 'horseMobileData');
    }
    //首页
    public static function mainPage($params = [])
    {
        extract($params);
        $pictureData = [
            ['id' => 1, 'picture_img' => 'http://cdnwm.yuluojishu.com/uploads/20230710/5a58ea8ea49f0526ec747c67ce5919cd.jpg'],
            ['id' => 2, 'picture_img' => 'http://cdnwm.yuluojishu.com/uploads/20230710/9ab12f6af84fcdc61d3aed8fd2c7a84b.jpg'],
            ['id' => 3, 'picture_img' => 'http://cdnwm.yuluojishu.com/uploads/20230710/2123ceea65bcb77d92f6875d2ba58048.jpg'],
        ];
        $attorneyData = \app\model\api\overdue\OverdueTeam::field('id,nickname,avator as head_portrait,work_year,auth_label,order_num')->select()->toArray();
        return [
            'picture_data' => $pictureData,
            'attorney_data' => $attorneyData,
            'middle_picture' => 'http://cdnwm.yuluojishu.com/uploads/20220720/27efc4c93828a5e4e6489eccea5d823f.jpg',
            'consult_data' => CommonCourseV2::getCommonCourseToCourseId($channel),
        ];
    }
    public static function homePage($params = [])
    {
        extract($params);
        $channel = isset($channel) && !empty($channel) ? $channel : '';
        $channelInfo = Channel::getChannelAppClass($channel);
        $data = [];
        $isApply = 0;
        if($channel) {
            $data['title'] = '专注债务逾期咨询';
            $data['titleDesc'] = '专业处理债务 1对1解决逾期方案';
            $data['bannerList'] = self::getMerchantBannerList($channelInfo);
            $data['showImageList'] = [
                ['id' => 1, 'image' => 'http://cdnwm.yuluojishu.com/uploads/20230109/e00571a0d8fd930914e019db26af3275.png'],
                ['id' => 2, 'image' => 'http://cdnwm.yuluojishu.com/uploads/20230109/002f9540f26025d9acf1df103c2173f5.png'],
                ['id' => 3, 'image' => 'http://cdnwm.yuluojishu.com/uploads/20230109/fe7a6ba8473c4e5d1cef430d997ba1e3.png'],
                ['id' => 4, 'image' => 'http://cdnwm.yuluojishu.com/uploads/20230109/288b85d26a463f1f70ecb28695c13de6.png'],
                ['id' => 5, 'image' => 'http://cdnwm.yuluojishu.com/uploads/20230203/392d4a299e0aceba2ac1ca6be04e8f5e.png'],
                ['id' => 6, 'image' => 'http://cdnwm.yuluojishu.com/uploads/20230203/5316371b7b81dda55bb909b3a310f69a.png'],
                ['id' => 7, 'image' => 'http://cdnwm.yuluojishu.com/uploads/20230203/93ee3876e4096a2ae2c850239a231198.png'],
                ['id' => 8, 'image' => 'http://cdnwm.yuluojishu.com/uploads/20230203/7829eef123a20270aa59d437eaa5e773.png'],
            ];
            $merchantCourse = MerchantServiceOverdue::getOverdueMerchant($channel);
            $thread = Thread::where('uid', $GLOBALS['uid'])->order('id desc')->find();
            if (empty($thread)) {
                $merchantCourse = MerchantServiceOverdue::getOverdueMerchant($channel);
            }
            $apply_success_msg = Merchant::where('id', isset($thread->merchant_id) ? $thread->merchant_id : $merchantCourse['merchant_id'])->value('apply_success_msg');
            $data['showImage'] = [
                'course_id' => isset($thread->course_id) ? $thread->course_id : $merchantCourse['course_id'],
                'is_jump_miniprogram' => isset($thread->course_id) ? CourseJumpWx::getCourseJumpWxStatus($thread->course_id, $channel) : CourseJumpWx::getCourseJumpWxStatus($merchantCourse['course_id'], $channel),
                'apply_success_msg' => $apply_success_msg,
                'is_apply' => !empty($thread) ? 1 : 0,
                'title' => [],
                'image' => $channel == 'zwyqyhpt_ios' ? 'http://cdnwm.yuluojishu.com/uploads/20230223/c6aa74892414c8bc3ba2f1d2db882597.gif' : 'http://cdnwm.yuluojishu.com/uploads/20230111/b1b3e07cd3d092f7175bd98299082748.gif'
            ];
            $data['ashoreList'] = OverdueOnshoreUser::field('phone,process_amount,disembark_time')->limit(10)->select();
            $data['partnerList'] = [
                ['id' => 1, 'image' => 'http://cdnwm.yuluojishu.com/uploads/20230109/94b57829f347aa32a936fe5cab3037e0.png'],
                ['id' => 2, 'image' => 'http://cdnwm.yuluojishu.com/uploads/20230109/9b7fb8c5d8162daed3f9fcbb0a4107df.png'],
                ['id' => 3, 'image' => 'http://cdnwm.yuluojishu.com/uploads/20230109/fae3c7944f8fa6deea03817867213cf7.png'],
                ['id' => 4, 'image' => 'http://cdnwm.yuluojishu.com/uploads/20230109/b8fda8107606dadab0aa9724ba0d6ac9.png'],
            ];
            $data['topImageList'] = [
                'http://cdnwm.yuluojishu.com/uploads/20230608/96e615eb2b573fb15ebb3bea9260ff77.png',
                'http://cdnwm.yuluojishu.com/uploads/20230608/9e916c6a6a8ede38958fc6ebbc91b1d2.png',
                'http://cdnwm.yuluojishu.com/uploads/20230608/6af965984c66799324c3dcd24e9c7fb8.png',
                'http://cdnwm.yuluojishu.com/uploads/20230608/a544d1cd5bc65a22845bfe6e978f8168.png',
            ];
            $data['teamList'] = OverdueTeam::getOverdueTeamList();
            $data['caseList'] = self::getArticleNews($channel);
        }
        return $data;
    }

    public static function homePageV2($params = [])
    {
        extract($params);
        $channelInfo = Channel::getChannelAppClass($channel);
//        $data['retentionInfo'] = [];
//        $gatherUserInfo = GatherUserInfo::whereIn('id',[18,19,24])
//            ->field('id,title,gather_info_json')
//            ->select()->toArray();
//        foreach($gatherUserInfo as &$item){
//            $item['gather_info_json'] = json_decode($item['gather_info_json'],true);
//        }
//        $data['retentionInfo'] = $gatherUserInfo;
        $data['retentionButtonDesc'] = ['立即咨询处理逾期','放心咨询，隐私保障'];
        $data['overdueCaseReport'] = self::getEntrustedMediation($params);
        $data['ashoreCalendar'] = [
            'ashore_time' => date('Y年m月d日'),
            'apply_num' => 90,
            'ashore_num' => 81
        ];
        $data['overdueShowPoster'] = [
            'poster_title' => "逾期了没钱还？\n停息挂账 延期2-3年再还！",
            'poster_desc' => '',
            'poster_image' => '',
        ];
        $courseIdArr = [167,269,438];
        $merchantCourse = Course::whereIn('merchant_id',[142,195,229])
            ->where('course_type',0)
            ->field('id,video_url')
            ->select()
            ->toArray();
        $data['overdueShowIcon'] = [
            ['title' => '信用卡逾期','course_id' => $merchantCourse[0]['id'] ?? $courseIdArr[0],
                'apply_num' => '已有1534人报名','content' => '停息挂账、二次分期',
                'icon_image' => 'http://cdnwm.yuluojishu.com/uploads/20230721/2fc63fe076b630c9d8046d9375c96bfd.png'],
            ['title' => '网贷逾期','course_id' => $merchantCourse[1]['id'] ?? $courseIdArr[1],
                'apply_num' => '已有1723人报名','content' => '停止暴力催收、减免债务',
                'icon_image' => 'http://cdnwm.yuluojishu.com/uploads/20230721/e9e80fb5286e94497728e4df66afb183.png'],
            ['title' => '银行信贷逾期','course_id' => $merchantCourse[2]['id'] ?? $courseIdArr[2],
                'apply_num' => '已有1262人报名','content' => '重组分期、协商还款',
                'icon_image' => 'http://cdnwm.yuluojishu.com/uploads/20230721/154a57b29f78da7ed73b28e5fa9742b0.png'],
            ['title' => '其他逾期','course_id' => $merchantCourse[2]['id'] ?? $courseIdArr[2],
                'apply_num' => '已有1345人报名','content' => '帮你重新规划债务',
                'icon_image' => 'http://cdnwm.yuluojishu.com/uploads/20230721/ad766bbb1343de31208499db865dec1c.png'],
        ];
        $data['overdueNews'] = ArticleNews::field('id,image as video_cover_image,title,content')
            ->where('app_class_id', $channelInfo['app_class_id'])
            ->where('status', 1)
            ->order('id', 'desc')
            ->limit(3)
            ->select()
            ->toArray();
        foreach($data['overdueNews'] as &$item){
            $item['content_desc'] = $item['title'];
            unset($item['content']);
        }
        $data['overdueVideo'] = SingleCourse::field('id,title,video_cover_image,video_url,course_desc')
            ->whereFindInSet('app_ids', $channelInfo['app_id'])
            ->where('status', 1)
            ->order('id', 'desc')
            ->limit(3)
            ->select()
            ->toArray();
        foreach($data['overdueVideo'] as $key => &$item){
            $item['course_id'] = $merchantCourse[$key]['id'] ?? $courseIdArr[$key];
        }
        $data['lawFirmList'] = OverdueTeam::where('status',1)
            ->where('type',2)
            ->field('id,nickname,appointment,avator,speciality,label')
            ->select()
            ->toArray();
        $data['lawTeamList'] = OverdueTeam::where('status',1)
            ->where('type',1)
            ->field('id,nickname,appointment,avator,speciality,label')
            ->select()
            ->toArray();

        $merchantCourse = MerchantServiceOverdue::getOverdueMerchant($channel);
        $thread = Thread::where('uid', $GLOBALS['uid'])->order('id desc')->find();
        if (empty($thread)) {
            $merchantCourse = MerchantServiceOverdue::getOverdueMerchant($channel);
        }
        $apply_success_msg = Merchant::where('id', isset($thread->merchant_id) ? $thread->merchant_id : $merchantCourse['merchant_id'])->value('apply_success_msg');
        $courseId = isset($thread->course_id) ? $thread->course_id : $merchantCourse['course_id'];
        $course = Course::field('id,video_url,btn_desc')->where('id',$courseId)->find();
        $data['btn_gif'] = 'http://cdnwm.yuluojishu.com/uploads/20240103/6d11e414c1a48800e94e838e1a98b547.gif';
        $checkApplyData = CheckApply::checkApplyCount($channel);
        $data['showCourse'] = [
            'course_id' => $courseId,
            'video_url' => $course->video_url ?? '',
            'is_jump_miniprogram' => isset($thread->course_id) ? CourseJumpWx::getCourseJumpWxStatus($thread->course_id, $channel) : CourseJumpWx::getCourseJumpWxStatus($merchantCourse['course_id'], $channel),
            'apply_success_msg' => $apply_success_msg,
            'btn_desc' => $course->btn_desc ?? '立即咨询债务解决方案',
            'is_apply' => !empty($thread) ? 1 : 0,
            'customer_link' => $checkApplyData['isCheckApply'] ? Thread::getCustomerLink(['course_id' => $checkApplyData['userThread']['course_id']])['customer_link'] : '',
            'is_yq_customer_link_affirm' =>$checkApplyData['isCheckApply'] ? Thread::where('uid', $GLOBALS['uid'])->order('id desc')->value('is_wecom_qrcode') : 0,
        ];
        return $data;
    }

    //逾期首页V3
    public static function homePageV3($params = [])
    {
        extract($params);
        $channelInfo = Channel::getChannelAppClass($channel);
        $data['title'] = '立马搞定逾期';
        $data['ashoreCalendar'] = [
            'apply_num' => 90,
            'ashore_num' => 81,
            'ashore_image' => ''
        ];
        $data['showAshore'] = SingleCourse::field('id,title,video_url,course_desc')
            ->whereFindInSet('app_ids', $channelInfo['app_id'])
            ->where('status', 1)
            ->order('id', 'desc')
            ->find();
        $data['showIcon'] = [
            ['icon_title' => '个人债务','course_id' => 167,'icon_image' => ''],
            ['icon_title' => '催收处理','course_id' => 269,'icon_image' => ''],
            ['icon_title' => '债务重组','course_id' => 438,'icon_image' => ''],
        ];
        $data['overdueMarket'] = ArticleNews::field('id,title')
            ->where('app_class_id', $channelInfo['app_class_id'])
            ->where('status', 1)
            ->order('id', 'desc')
            ->limit(3)
            ->select()
            ->toArray();
        $data['overdueCase'] = ArticleNews::field('id,image,content')
            ->where('app_class_id', $channelInfo['app_class_id'])
            ->where('status', 1)
            ->order('id', 'desc')
            ->limit(3)
            ->select()
            ->toArray();
        $merchantCourse = MerchantServiceOverdue::getOverdueMerchant($channel);
        $thread = Thread::where('uid', $GLOBALS['uid'])->order('id desc')->find();
        if (empty($thread)) {
            $merchantCourse = MerchantServiceOverdue::getOverdueMerchant($channel);
        }
        $apply_success_msg = Merchant::where('id', isset($thread->merchant_id) ? $thread->merchant_id : $merchantCourse['merchant_id'])->value('apply_success_msg');
        $courseId = isset($thread->course_id) ? $thread->course_id : $merchantCourse['course_id'];
        $courseInfo = Course::where('id',$courseId)->field('id,video_url,btn_desc')->find();
        $data['showCourse'] = [
            'course_id' => $courseId,
            'video_url' => $courseInfo->video_url,
            'is_jump_miniprogram' => isset($thread->course_id) ? CourseJumpWx::getCourseJumpWxStatus($thread->course_id, $channel) : CourseJumpWx::getCourseJumpWxStatus($merchantCourse['course_id'], $channel),
            'apply_success_msg' => $apply_success_msg,
            'btn_desc' => $course->btn_desc ?? '立即咨询债务解决方案',
            'is_apply' => !empty($thread) ? 1 : 0,
        ];

        return $data;
    }

    //逾期服务
    public static function overdueService($params = [])
    {
        extract($params);
        $data['showBanner'] = [
            'title' => ['专业律师更高校','全流程服务'],
            'conten_desc' => '添加咨询，快一步解决逾期',
            'btn_desc' => '立即咨询'
        ];
        $data['informationEssential'] = [
            ['title' => '逾期资料', 'image' => ''],
            ['title' => '用户保护中心', 'image' => '']
        ];
        $data['collaborationProcess'] = [
            ['title' => '提交信息', 'content_desc' => '提交逾期资料快人一步',
                'image' => ''],
            ['title' => '免费咨询', 'content_desc' => '提交逾期资料快人一步',
                'image' => ''],
            ['title' => '定制方案', 'content_desc' => '提交逾期资料快人一步',
                'image' => ''],
            ['title' => '签订协议', 'content_desc' => '提交逾期资料快人一步',
                'image' => ''],
            ['title' => '支付定金', 'content_desc' => '提交逾期资料快人一步',
                'image' => ''],
            ['title' => '完成协商', 'content_desc' => '提交逾期资料快人一步',
                'image' => '']
        ];
        $merchantCourse = MerchantServiceOverdue::getOverdueMerchant($channel);
        $thread = Thread::where('uid', $GLOBALS['uid'])->order('id desc')->find();
        if (empty($thread)) {
            $merchantCourse = MerchantServiceOverdue::getOverdueMerchant($channel);
        }
        $apply_success_msg = Merchant::where('id', isset($thread->merchant_id) ? $thread->merchant_id : $merchantCourse['merchant_id'])->value('apply_success_msg');
        $courseId = isset($thread->course_id) ? $thread->course_id : $merchantCourse['course_id'];
        $courseInfo = Course::where('id',$courseId)->field('id,video_url,btn_desc')->find();
        $data['showCourse'] = [
            'course_id' => $courseId,
            'video_url' => $courseInfo->video_url,
            'is_jump_miniprogram' => isset($thread->course_id) ? CourseJumpWx::getCourseJumpWxStatus($thread->course_id, $channel) : CourseJumpWx::getCourseJumpWxStatus($merchantCourse['course_id'], $channel),
            'apply_success_msg' => $apply_success_msg,
            'btn_desc' => $course->btn_desc ?? '立即咨询债务解决方案',
            'is_apply' => !empty($thread) ? 1 : 0,
        ];
        return $data;
    }

    //课程详情
    public static function courseDetail($params = [])
    {
        extract($params);
        $applyInfo = Thread::where('uid', $GLOBALS['uid'])->order('id desc')->find();
        $channelInfo = Channel::getChannelAppClass($channel);
//        if (!empty($applyInfo)) {
//            return [
//                'course_id' => $applyInfo->course_id,
//                'is_jump_miniprogram' => CourseJumpWx::getCourseJumpWxStatus($applyInfo->course_id, $channel),
//                'is_apply' => 1,
//                'landingPage' => new \stdClass(),
//                'btn_desc' => '立即咨询债务解决方案',
//            ];
//        }
//        $merchantList = MerchantServiceJob::getMerchantIsPayCount($channelInfo);
//        $merchantId = MerchantServiceJob::sortMerchantList($merchantList, $channelInfo, $entryFee);
        $course = \app\model\api\vestbag\Course::field('id,video_url,btn_desc')->where('id',$course_id)->where('course_type',0)->find();
        $landingPage = LandingPage::field('id,landing_image,end_image,desc_image,course_id')->where('course_id', $course_id)->find();
        if (!empty($landingPage)) {
            $landingPage = $landingPage->toArray();
            $landingPage['video_url'] =  isset($course->video_url) ? $course->video_url : '';
        }
        return [
            'course_id' => $course_id,
            'video_url' => $course->video_url,
            'is_jump_miniprogram' =>  CourseJumpWx::getCourseJumpWxStatus($course_id, $channel),
            'is_apply' => !empty($applyInfo) ? 1 : 0,
            'landingPage' => !empty($landingPage) ? $landingPage : new \stdClass(),
            'btn_desc' => isset($course->btn_desc) && !empty($course->btn_desc) ? $course->btn_desc : '立即咨询债务解决方案',
        ];
    }

    public static function getEntrustedMediation($params = [])
    {
        extract($params);
        $data = [
            [
                'title' => '信用卡债务调解业务',
                'introduce' => '信用卡逾期金融调解法律咨询业务',
                'describe' => '已累计帮助4.6万人上岸，平均1天处理61个案件'
            ],
            [
                'title' => '网贷债务调解业务',
                'introduce' => '网贷逾期金融调解法律咨询业务',
                'describe' => '已累计帮助4.1万人上岸，平均1天处理57个案件'
            ],
            [
                'title' => '其它债务调解业务',
                'introduce' => '其它逾期金融调解法律咨询业务',
                'describe' => '已累计帮助3.5万人上岸，平均1天处理49个案件'
            ]
        ];
        return $data;
    }

    //banner图
    public static function getMerchantBannerList($channelInfo){
        $bannerList = Banner::where('type', 0)
            ->whereFindInSet('channel_ids',$channelInfo['channel_id'])
            ->where('status', 1)
            ->field('id,image')
            ->order(['sort'=>'desc','id'=>'desc'])
            ->limit(3)
            ->select()->toArray();
        return $bannerList;
    }

    //案例展示
    public static function getArticleNews($channel = null)
    {
        if(isset($channel) && !empty($channel)) {
            $channelInfo = Channel::getChannelAppClass($channel);
            $articleNews = ArticleNews::field('id,title,content,image')
                ->where('app_class_id', $channelInfo['app_class_id'])
                ->where('status', 1)
                ->order('id', 'desc')
                ->limit(4)
                ->select()
                ->toArray();
        }else{
            $articleNews = ArticleNews::field('id,title,content,image')
                ->where('status', 1)
                ->order('id', 'desc')
                ->limit(4)
                ->select()
                ->toArray();
        }
        if (!empty($articleNews)) {
            foreach ($articleNews as &$val) {
                $content = str_replace("&quot;", "", $val['content']);
                $content = html_entity_decode($content);
                $val['content'] = richText($content);
                $themeImages = [];
                if (!empty($val['theme_images'])) {
                    $images = explode(', ', $val['theme_images']);
                    foreach ($images as $v) {
                        $themeImages[] = preg_replace('/\s+/', '', $v);
                    }
                }
                $val['theme_images'] = $themeImages;
                $val['content_desc'] = getEditContentText($val['content']);
            }
        }
        return $articleNews;
    }

    //是否跳转小程序
    public static function getIsJumpMiniprogram($merchant,$channel)
    {
        $noJumpphoneInfo = 0;
        $isJumpMiniprogram = 0;
        //手机号或微信不跳转微信小程序
        if (isset($GLOBALS['uid'])) {
            $userInfo = UserList::where('id', $GLOBALS['uid'])->field('phone,wx_nickname')->find();
            if (!empty($userInfo['phone']) && !empty($userInfo['wx_nickname'])) {
                $noJumpphoneInfo = NoJumpWechatPhone::whereOr([['phone', '=', $userInfo['phone']], ['wx_nickname', '=', $userInfo['wx_nickname']]])->count();
            }
            if ($noJumpphoneInfo > 0) {
                $isJumpMiniprogram = 0;
                return $isJumpMiniprogram;
            }
        }

        $isJumpMiniprogram = isset($merchant['is_jump_miniprogram']) ? $merchant['is_jump_miniprogram'] : $isJumpMiniprogram;
        //通过渠道判断是否跳转微信小程序
        if(isset($channel) && !empty($channel) && $isJumpMiniprogram == 1){
            $isJumpMiniprogram = self::getChannelJumpWechat($channel);
        }
        return $isJumpMiniprogram;

    }

    //渠道跳转微信判断
    public static function getChannelJumpWechat($channel)
    {
        $isJumpMiniprogram = 1;
        $channelInfo = Channel::where('channel_name',$channel)->field('id,is_jump_miniprogram,jump_wechat_version')->find();
        $appVersion = UserList::where('id', $GLOBALS['uid'])->value('app_version');
        if (isset($channelInfo['jump_wechat_version']) && !empty($channelInfo['jump_wechat_version']) &&  $channelInfo['jump_wechat_version'] == $appVersion) {
            $isJumpMiniprogram = $channelInfo['is_jump_miniprogram'];
        }
        return $isJumpMiniprogram;
    }

    //获取商户线索课程
    public static function getMerchantCourse($channelInfo,$isApply = 0,$courseId = 0)
    {
        $merchantInfo = [];
        $merchantThread = Thread::with(['merchant' => function($query){
            $query->field('id,is_source,is_jump_miniprogram,apply_success_msg');
        }])
            ->where('uid',$GLOBALS['uid'])
            ->where('channel_id',$channelInfo['channel_id'])
            ->field('id,course_id,merchant_id')
            ->order('create_time desc')
            ->select()->toArray();
        if(!empty($merchantThread)){
            $sourceWArr = $sourceNArr = [];
            foreach ($merchantThread as $item) {
                if ($item['merchant']['is_source'] == 1) {   // 站内
                    $sourceNArr[] = $item['merchant'];
                } else {
                    $sourceWArr[] = $item['merchant'];
                }
            }
            $merchantArr = !empty($sourceWArr) ? $sourceWArr : $sourceNArr;
            $merchantInfo = $merchantArr[0] ?? [];
            $courseId = Course::where('merchant_id',$merchantInfo['id'])->where('course_type',0)->value('id');
            $isApply = 1;
        }
        return ['is_apply' => $isApply,'course_id' => $courseId,'merchantInfo' => $merchantInfo];
    }

}