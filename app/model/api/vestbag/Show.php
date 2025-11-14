<?php
/**
 * 首页
 */

namespace app\model\api\vestbag;
use app\model\admin\NoJumpWechatPhone;
use app\model\api\Article;
use app\model\api\Banner;
use app\model\api\Channel;
use app\model\api\Course;
use app\model\api\h5\HorseRaceLamp;
use app\model\api\LandingPage;
use app\model\api\Merchant;
use app\model\api\Thread;
use app\model\api\v2\UserList;
use app\model\api\v2\GatherUserInfo as GatherUserInfoModel;
use laytp\BaseModel;
use app\lib\api\service\WeightService;
use app\lib\api\service\LandingPageService;
use app\lib\api\exception\Exception;
use app\model\api\vestbag\PartJobThread;
use app\model\api\Customer;
use app\lib\api\service\CustomerService;
use app\lib\api\city\IpCity;
use app\model\api\overdue\OverdueTeam;
use app\model\api\vestbag\Course as CourseModel;
use app\model\api\vestbag\OverduePlan as OverduePlanModel;
use app\model\api\ArticleNews;

class Show extends BaseModel
{

    //获取兼职列表
    public static function getPartJobList($params = [])
    {
        extract($params);
        $channelInfo = Channel::getChannelAppClass($channel);
        extract($channelInfo);
        $partJobModel =  new \app\model\api\fortunecat\Course();
        $courseList = $partJobModel->field('id,title,sort,compensation,merchant_id,tag_ids,btn_desc,compensation_type')->where('app_class_id', $app_class_id)->where('status', 1)->where('course_type', 1)->order(['sort' => 'desc','id' => 'desc'])->select();
        return $courseList;
    }
    //获取兼职详情
    public static function getPartJobDetail($params = [])
    {
        extract($params);
        $partJobModel =  new \app\model\api\fortunecat\Course();
        $partJobDetail  = $partJobModel->field('id,title,compensation,part_class_ids,tag_ids,virtual_apply_nums,content,confirm_copy_desc,compensation_type,confirm_btn_desc')->find($part_job_id);
        if (empty($partJobDetail)) {
            new Exception('兼职任务不存在或已下架');
        }
        $channelInfo = Channel::getChannelAppClass($channel);
        extract($channelInfo);
        $customerId = PartJobThread::where('uid', $GLOBALS['uid'])->where('course_id', $part_job_id)->value('customer_id');
        $partJobDetail['is_apply'] = $customerId ? 1 : 0;
        $partJobDetail['qr_code'] = $customerId ? Customer::where('id', $customerId)->value('qr_code') : '';
        $partJobDetail['recommend_part_job_list'] = $partJobModel->field('id,title,sort,compensation,merchant_id,tag_ids,btn_desc,compensation_type')->where('app_class_id', $app_class_id)->where('status', 1)->where('course_type', 1)->order(['sort' => 'desc','id' => 'desc'])->limit(2)->select()->toArray();

        return $partJobDetail;
    }

    //获取兼职二维码
    public static function getPartJobQrcode()
    {
        extract($params);
        $customerId = PartJobThread::where('uid', $GLOBALS['uid'])->where('course_id', $part_job_id)->value('customer_id');
        $qrCode = '';
        if (!empty($customerId)) {
            $qrCode = Customer::where('id', $customerId)->value('qr_code');
        }
        return ['qr_code' => $qrCode];
    }
    //兼职报名
    public static function parJobApply($params = [])
    {
        //echo 11111;die;
        extract($params);
        $channelInfo = Channel::getChannelAppClass($channel);
        extract($channelInfo);
        $merchantInfo = Merchant::where('app_class_id', $app_class_id)->where('is_switch', 1)->where('is_source', 1)->find();
        $partJobModel =  new \app\model\api\fortunecat\Course();
        $partJobDetail  = $partJobModel->field('id,title,compensation,part_class_ids,tag_ids,virtual_apply_nums,content,confirm_copy_desc,compensation_type,confirm_btn_desc')->find($part_job_id);
        $checkApply = PartJobThread::where('uid', $GLOBALS['uid'])->where('course_id', $part_job_id)->count();
        if ($checkApply) {
            new Exception('您已经报名过了！');
        }
        if (empty($partJobDetail)) {
            new Exception('兼职任务不存在或已下架');
        }
        if (empty($merchantInfo)) {
            new Exception('兼职任务不存在或已下架');
        }
        $customerId = (new CustomerService)->getCustomerServiceId($merchantInfo->id);
        $cityInfo = IpCity::getIpToCity();
        $userInfo = UserList::find($GLOBALS['uid']);
        $gatherInfo = GatherUserInfoModel::getFormatGatherInfo($userInfo->age_range_id, 'age_range_id');
        $ageRange = $gatherInfo['name'];
        $threadPriceInfo = Merchant::getMerchantThreadPrice($merchantInfo);
        $ret = PartJobThread::create([
            'uid' => $GLOBALS['uid'],
            'course_id' => $part_job_id,
            'entry_fee' => 0,
            'merchant_id' => $merchantInfo->id,
            'customer_id' => $customerId ?? 0,
            'province' => $cityInfo['province_name'] ?? '',
            'city' => $cityInfo['city_name'] ?? '',
            'age' => $ageRange,
            'channel' => $channel,
            'store' => '',
            'thread_price' => $threadPriceInfo['thread_price'],
            'thread_price_type' => $threadPriceInfo['thread_price_type'],
            'channel_id' => $channel_id,
            'app_id' => $app_id,
            'app_class_id' => $app_class_id,
        ]);
        $qrCode = Customer::where('id', $customerId)->value('qr_code');
        if ($ret) {
            return ['qr_code' => $qrCode];
        }
        new Exception('系统异常');
    }
    public static function homePageV2($params = [])
    {
        extract($params);
        $images = [
            ['id' => 0, 'image' => 'http://cdnwm.yuluojishu.com/uploads/20230424/633ec9e8880c3e4c8fe3980c51f8de86.png'],
            ['id' => 0, 'image' => 'http://cdnwm.yuluojishu.com/uploads/20230424/5b49dfcd6ab85787d6b7c873743793c7.png'],
            ['id' => 0, 'image' => 'http://cdnwm.yuluojishu.com/uploads/20230424/1a8042c4f1ef54914cd4d7a25d2ed3c9.png'],
            ['id' => 0, 'image' => 'http://cdnwm.yuluojishu.com/uploads/20230324/5764caa814a78b08607684a099bbc631.png'],
            ['id' => 1, 'image' => 'http://cdnwm.yuluojishu.com/uploads/20230325/f8a913cdf733c99b32be44a6dcaee5aa.png'],
            ['id' => 2, 'image' => 'http://cdnwm.yuluojishu.com/uploads/20230324/d9801a3016b6f12632ad5924f3d8ac30.png'],
            ['id' => 3, 'image' => 'http://cdnwm.yuluojishu.com/uploads/20230324/cd01e876b58ce49536c5fb594e21cafc.png'],
        ];
        $overdueTeam = OverdueTeam::getOverdueTeamList();
        return [
            'images' => $images,
            'overdueTeam' => $overdueTeam,
            'applyInfo' => CourseModel::courseDetail($channel, 0, $app_version),
            'planList' => [],
        ];
    }
    //逾期v4首页
    public static function getIosYqIndex($params = [])
    {
        extract($params);
        //banner图
        $pictureList = [
            'http://cdnwm.yuluojishu.com/uploads/20230420/c3d955f65c2cd50e9f1c7f234057f0fa.png',
        ];
        //逾期方案
        $entrustData = [
            ['picture' => 'http://cdnwm.yuluojishu.com/uploads/20230420/ab2b973129a5c4594881c82484e87303.png', 'title' => '专业逾期咨询顾问/合理规划还款方式', 'labelData' => ['1v1定制', '避免起诉'], 'signUpNums' => 55, 'btn_desc' => '债务解决方案'],
            ['picture' => 'http://cdnwm.yuluojishu.com/uploads/20230420/1a19c0875f8e05f50e0d4c1956acd521.png', 'title' => '专业逾期咨询顾问/助你早日上岸', 'labelData' => ['减免罚息', '停止催收'], 'signUpNums' => 61, 'btn_desc' => '债务解决方案'],
        ];
        $nicknameJobList = [
            ['nickname' => '张先生', 'job' => '自由职业者'],
            ['nickname' => '李女士', 'job' => '自媒体'],
            ['nickname' => '吕先生', 'job' => '自由职业者'],
            ['nickname' => '田先生', 'job' => '公职事业编'],
            ['nickname' => '杨女士', 'job' => '宝妈'],
            ['nickname' => '王先生', 'job' => '工作党'],
        ];
        //文章
        $articleData = ArticleNews::getArticleNews(['channel' => $channel]);
        foreach ($articleData as $key => $val) {
            $articleData[$key]['nickname'] = $nicknameJobList[$key]['nickname'];
            $articleData[$key]['job'] = $nicknameJobList[$key]['job'];
        }
        $yqDurationPicture = [
            'http://cdnwm.yuluojishu.com/uploads/20230420/16df71f045bc59046af3e972decdc321.png',
            'http://cdnwm.yuluojishu.com/uploads/20230420/09f699ca9a96c2c6c30d9504a3180359.png',
            'http://cdnwm.yuluojishu.com/uploads/20230420/8fcd736ca454ff1e0242adce2c0b5302.png',
            'http://cdnwm.yuluojishu.com/uploads/20230420/f9c26f98c467a4aa82767f0a386f57ea.png',
        ];
        $applyForData = [];
        //申请信息
        for ($i = 0; $i < 100; $i++) {
            $applyForData[] = ['apply_time' => rand(1,5) . '分钟前', 'user' => '用户'. rand(1000,9999)];
        }

        return [
            'picture_list' => $pictureList,
            'yq_scheme_data' => $entrustData,
            'yq_duration_picture' => $yqDurationPicture,
            'article_data' => !empty($articleData) ? $articleData : [],
            'apply_for_data' => $applyForData,
            'bg_picture' => 'http://cdnwm.yuluojishu.com/uploads/20230420/13ea57d6a9b4b2f80df4b8d0199abd56.png',
            'applyInfo' => CourseModel::courseDetail($channel, 0, $app_version),
        ];
    }
    //逾期v4委托列表
    public static function getIosYqEntrustList($params = [])
    {
        extract($params);
        $entrustData = [
            ['picture' => 'http://cdnwm.yuluojishu.com/uploads/20230420/d5afa68e783b5a4002ecf012e7915f61.png', 'title' => '专业逾期咨询顾问/带你摆脱债务困境', 'labelData' => ['停止催收', '合法合规'], 'signUpNums' => 41, 'btn_desc' => '债务解决方案'],
            ['picture' => 'http://cdnwm.yuluojishu.com/uploads/20230420/745a86115ef2af5402bff6d2f4e2a720.png', 'title' => '专业逾期咨询顾问/帮你回归正常生活', 'labelData' => ['合法合规', '避免起诉'], 'signUpNums' => 35, 'btn_desc' => '债务解决方案'],
            ['picture' => 'http://cdnwm.yuluojishu.com/uploads/20230420/cb562636ecad0396b85028bb71cabe15.png', 'title' => '专业逾期咨询顾问/合理合规停止催收', 'labelData' => ['减免罚息', '1v1定制'], 'signUpNums' => 25, 'btn_desc' => '债务解决方案'],
            ['picture' => 'http://cdnwm.yuluojishu.com/uploads/20230420/e3b51d002a62a1dc3652468a81b89b82.png', 'title' => '专业逾期咨询顾问/摆脱催收利滚利', 'labelData' => ['避免起诉', '合法合规', '减免罚息违约金', '1v1定制'], 'signUpNums' => 51, 'btn_desc' => '债务解决方案'],
            ['picture' => 'http://cdnwm.yuluojishu.com/uploads/20230420/ab2b973129a5c4594881c82484e87303.png', 'title' => '专业逾期咨询顾问/合理规划还款方式', 'labelData' => ['1v1定制', '避免起诉'], 'signUpNums' => 55, 'btn_desc' => '债务解决方案'],
            ['picture' => 'http://cdnwm.yuluojishu.com/uploads/20230420/1a19c0875f8e05f50e0d4c1956acd521.png', 'title' => '专业逾期咨询顾问/助你早日上岸', 'labelData' => ['减免罚息', '停止催收'], 'signUpNums' => 61, 'btn_desc' => '债务解决方案'],
        ];
        return [
            'bg_picture' => 'http://cdnwm.yuluojishu.com/uploads/20230420/a9f10e5096f872524aa370a876709122.png',
            'entrustData' => $entrustData,
            'applyInfo' => CourseModel::courseDetail($channel, 0, $app_version),
        ];
    }
    //首页
    public static function homePage($params = [])
    {
        extract($params);
        $channelInfo = Channel::getChannelAppClass($channel);
        //多机构商户
        $merchantList = Merchant::getMerchantList(['channel' => $channel]);
        $merchantIds = array_column($merchantList,'id');
        $where[] = ['status','=',1];
        $where[] = ['course_type','=',0];
        $title = $title ?? '';
        if($title){
            $where[] = ['title','like','%'.$title.'%'];
        }
        $courseList = Course::where('merchant_id','in',$merchantIds)
            ->where($where)
            ->field('id,title,video_cover_image,video_url,content,apply_succeed_wx_btn,video_burning_time,entry_fee,btn_desc,virtual_apply_nums,merchant_id,landing_page_btn_image')
            ->select();
        if(!empty($courseList)){
            $courseList = $courseList->toArray();
            $courseIds = array_column($courseList,'id');
            $whereMro['uid'] = ['=', $GLOBALS['uid']];
            $whereMro['merchant_id'] = ['in', $merchantIds];
            $whereMro['app_id'] = ['=', $channelInfo['app_id']];
            $whereMro['course_id'] = ['in', $courseIds];
            $threadCourseIds = Thread::where('uid',$GLOBALS['uid'])
                ->whereIn('merchant_id',$merchantIds)
                ->where('app_id',$channelInfo['app_id'])
                ->whereIn('course_id',$courseIds)
                ->column('course_id');
            foreach($courseList as &$item){
                $item['is_apply'] = 0;
                if(in_array($item['id'],$threadCourseIds)){
                    $item['is_apply'] = 1;
                    $item['btn_desc'] = $item['is_jump_miniprogram'] == 1 ? $item['apply_succeed_wx_btn'] : '';
                }
            }
        }
        if($title){
            $data['courseList'] = $courseList;
        }else{
            $data['bannerList'] = self::getMerchantBannerList($merchantIds);
            $data['courseList'] = $courseList;
        }
        return $data;
    }

    public static function getMerchantCourse($params)
    {
        extract($params);
        $courseInfo = [];
        $channelInfo = Channel::getChannelAppClass($channel);
        $courseInfoData = Course::withTrashed()->field('id,title,video_cover_image,video_url,video_burning_time,entry_fee,btn_desc,virtual_apply_nums,merchant_id,landing_page_btn_image')->where('id', $course_id)->whereFindInSet('app_ids',$channelInfo['app_id'])->where('course_type',0)->order('id desc')->find();
        if (!empty($courseInfoData)) {
            $courseInfo = $courseInfoData;
        } else {
            $courseInfo = Course::withTrashed()->field('id,title,video_cover_image,video_url,video_burning_time,entry_fee,btn_desc,virtual_apply_nums,merchant_id,landing_page_btn_image')->where('id', $course_id)->where('course_type',0)->order('id desc')->find();
        }
        if (!empty($courseInfo)) {
            $landingPageList = [];
            $landingPage = LandingPage::withTrashed()->with(['course' => function($query){
                $query->field('id,btn_desc,video_url,merchant_id,entry_fee,virtual_apply_nums');
            }])->field('id,landing_image,lamp_back_image,end_image,desc_image,lamp_font_color,is_lamp,course_id')->whereFindInSet('channel_ids', $channelInfo['channel_id'])->where('course_id', $courseInfo->id)->find();

            if (!empty($landingPage)) {
                unset($landingPage['course']['apply_nums']);
                $userInfo = UserList::where('id',$GLOBALS['uid'])->field('id,phone,wx_nickname')->find();
                $noJumpphoneInfo = UserList::checkIsJumpMiniprogram($userInfo);
                if ($noJumpphoneInfo > 0) {
                    $landingPage['desc_image'] = "http://cdnwm.yuluojishu.com/uploads/20220920/3643a0c027dac8d83328072c4d359fa9.png";
                }
                $landingPage['horse_race_lamp'] = self::getHorseRaceLamp($landingPage);
                $landingPage['is_affirm_page'] = $courseInfo['entry_fee'] > 0 ? $channelInfo['pay_landing_page_affirm'] : $channelInfo['free_landing_page_affirm'];

                $landingPageList = [$landingPage];
            }
            $courseInfo['is_apply'] = 0;
            $courseInfo['landing_page_list'] = $landingPageList;
        }
        return $courseInfo;
    }

    //轮播图列表
    public static function getMerchantBannerList($merchantIds = [])
    {
        $bannerLists = [];
        foreach($merchantIds as $merchantId){
            $bannerList = banner::where('status',1)
                ->whereFindInSet('merchant_id',$merchantId)
                ->order('sort desc')
                ->field('id,image,jump_mode,jump_mode_json,jump_url')
                ->group('merchant_id')
                ->select();
            if(!empty($bannerList)){
                $bannerLists[] = $bannerList->toArray();
            }
        }
        $bannerLists1 = [];
        foreach($bannerLists as $item){
            foreach($item as $val){
                if(!empty($val)){
                    $bannerLists1[] = $val;
                }
            }
        }

        foreach($bannerLists1 as $key => &$val){
            if(isset($val['jump_mode_json']) && !empty($val['jump_mode_json'])){
                if(!empty($val['jump_mode_json'])) {
                    $jumpModeJson = json_decode($val['jump_mode_json'],true);
                    if ($jumpModeJson['module_id'] == 1) {
                        $threadCount = Thread::where('uid', $GLOBALS['uid'])->where('course_id', $jumpModeJson['course_id'])->count();
                        if ($threadCount > 0) {
                            $val['jump_mode'] = 0;
                        }
                    }
                }
            }
        }
        return !empty($bannerLists1) ? $bannerLists1 : [];
    }

    public static function getHorseRaceLamp($data)
    {
        $horseRaceLamp = [];
        if($data['is_lamp'] == 1) {
            $horseRaceLamp = HorseRaceLamp::field('nickname,phone,times')->order('times', 'asc')->select();
            if (!empty($horseRaceLamp)) {
                foreach ($horseRaceLamp as &$val) {
                    $phone_xing = substr($val->phone, 4, 4);  //获取手机号中间四位
                    $val['nickname'] = subNickname($val->nickname);
                    $val['phone'] = str_replace($phone_xing, '****', $val->phone);  //用****进行替换
                    $val['times'] = $val->times . '分钟前';
                }
            }
        }
        return $horseRaceLamp;
    }

}
