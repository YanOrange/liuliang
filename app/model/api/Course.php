<?php
/**
 * 课程表模型
 */

namespace app\model\api;

use app\lib\api\other\CommonCourse;
use app\lib\api\service\WeightService;
use app\model\api\Channel;
use app\model\api\Thread;
use app\model\api\v2\Banner;
use app\model\api\v2\GatherUserInfo as GatherUserInfoModel;
use laytp\BaseModel;
use think\model\concern\SoftDelete;
use app\lib\api\exception\Exception;
use think\facade\Queue;
use think\facade\Request;
use app\model\admin\NoJumpWechatPhone;
use think\facade\Db;
use app\lib\api\service\MerchantServiceJob;
use app\lib\api\other\CourseJumpWx;
use app\model\api\UserList;

class Course extends BaseModel
{
    use SoftDelete;

    //模型名
    protected $name = 'course';
    protected $hidden = [
        'merchant_id',
        'virtual_apply_nums',
    ];
    protected $append = [
        'is_jump_miniprogram',
        'apply_success_msg',
        'apply_nums',
        'is_under_eighteen_apply',
    ];

    protected $no_jump_miniprogram_channel = ['quxueps_vivo'];

    public static function getFeeCondition($channelInfo = [])
    {
        $where = [];
        $merchantData = MerchantServiceJob::getMerchantIsPayCount($channelInfo);
        if ($merchantData['outsideMerchantCount'] > 0) {
            if ($merchantData['payMerchantNums'] > 0 && $merchantData['freeMerchantNums'] == 0) {
                $where[] = ['entry_fee','>',0];
            } else if ($merchantData['freeMerchantNums'] > 0 && $merchantData['payMerchantNums'] == 0) {
                $where[] = ['entry_fee','=',0];
            }
        } else {
            if ($merchantData['payMerchantNums'] > 0 && $merchantData['freeMerchantNums'] == 0) {
                $where[] = ['entry_fee','>',0];
            } else if($merchantData['freeMerchantNums'] > 0 && $merchantData['payMerchantNums'] == 0) {
                $where[] = ['entry_fee','=',0];
            }
        }
        return $where;
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
    public function getApplyNumsAttr($value, $data)
    {
       // $applyNums = Thread::where('course_id', $data['id'])->count();
       // return isset($data['virtual_apply_nums']) ? $data['virtual_apply_nums'] + $applyNums : $applyNums;
        return isset($data['virtual_apply_nums']) ? $data['virtual_apply_nums'] : 0;
    }

    //是否跳转小程序
    public function getIsJumpMiniprogramAttr($value, $data)
    {
        /*$noJumpphoneInfo = 0;
        $isJumpMiniprogram = 1;
        $channel = Request::post('channel', '');
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

        if (isset($channel) && !empty($channel) && in_array($channel, $this->no_jump_miniprogram_channel)) {
            $isJumpMiniprogram = 0;
            return $isJumpMiniprogram;
        } else {
            $isJumpMiniprogram = isset($this->merchant->is_jump_miniprogram) ? $this->merchant->is_jump_miniprogram : $isJumpMiniprogram;
            //通过渠道判断是否跳转微信小程序
            if(isset($channel) && !empty($channel) && $isJumpMiniprogram == 1){
                $isJumpMiniprogram = self::getChannelJumpWechat($channel);
            }
            return $isJumpMiniprogram;
            //return isset($this->merchant->is_jump_miniprogram) ? $this->merchant->is_jump_miniprogram : $isJumpMiniprogram;
        }*/
        if(!isset($GLOBALS['uid'])){
            return 0;
        }else {
            return CourseJumpWx::getCourseJumpWxStatus(isset($data['id']) ? $data['id'] : 0);
        }
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

    public function getApplySuccessMsgAttr($value, $data)
    {
        return isset($this->merchant->apply_success_msg) ? $this->merchant->apply_success_msg : '请及时添加老师微信';
    }

    public function merchant()
    {
        return $this->belongsTo('app\model\api\Merchant', 'merchant_id', 'id')->removeOption('soft_delete');
    }

    public function LandingPage()
    {
        return $this->belongsTo('app\model\api\LandingPage', 'id', 'course_id')->removeOption('soft_delete');
    }

    /**
     * 逾期列表
     * @date 2022-09-19
     * @return array
     */
    public static function getOverdue($params)
    {
        extract($params);

        // 前端：渠道名称获取到渠道信息
        $channelInfo = Channel::getChannelAppClassOverdue($channel);
        if (!$channelInfo) return [];

        // 全报名标识
        $bannerArr = [];
        $hasAllSignUP = false;
        $uid = $GLOBALS['uid'];
        $overdueDesc = ($channelInfo['overdue_version_home_desc']) ? json_decode($channelInfo['overdue_version_home_desc'], true) : [];

        $appId = $channelInfo['app_id'];
        $courseModel = new \app\model\api\Course();
        $name = $courseModel->getName();
        $tableName = env('database.prefix') . $name;

        // 站内 | 站外
        $userInfo = UserList::where('id', $uid)->field('age_range_id, is_search_plan')->find();
        $gatherInfo = GatherUserInfoModel::getFormatGatherInfo($userInfo->age_range_id, 'age_range_id');
        $ageRange = !empty($gatherInfo['name']) ? '"' . $gatherInfo['name'] . '"' : '';
        $outsideMerchantCount = $courseModel->whereExists(function ($query) use ($tableName, $ageRange) {
            $merchantTableName = (new \app\model\api\Merchant())->getName();
            $query = $query->table(env('database.prefix') . $merchantTableName)
                ->where(env('database.prefix') . $merchantTableName . '.id=' . $tableName . '.merchant_id');
            $query->where('is_source', 2);
            $query->where('is_switch', 1);
            $query->where("age_range_weight_json->'$." . $ageRange . "'", '>', 0);
            return $query;
        })->where('course_type', 0)
            ->whereFindInSet('app_ids', $appId)
            ->count();

        // 好课推荐列表
        $courseObj = $courseModel->whereExists(function ($query) use ($tableName, $ageRange, $outsideMerchantCount) {
            $merchantTableName = (new \app\model\api\Merchant())->getName();
            $query = $query->table(env('database.prefix') . $merchantTableName)
                ->where(env('database.prefix') . $merchantTableName . '.id=' . $tableName . '.merchant_id');
            $query->where('is_source', $outsideMerchantCount ? 2 : 1);
            $query->where('is_switch', 1);
            $query->where("age_range_weight_json->'$." . $ageRange . "'", '>', 0);
            return $query;
        })->where('status', '=', 1)
            ->where('course_type', '=', 0)
            ->whereFindInSet('app_ids', $appId)
            ->field('id, title, virtual_apply_nums, tag_names, merchant_id, landing_page_btn_image,apply_succeed_wx_btn,course_thumbnail_image,btn_desc,entry_fee,video_url, video_cover_image')
            ->visible(['id', 'title', 'virtual_apply_nums', 'tag_names', 'merchant_id', 'apply_succeed_wx_btn','landing_page_btn_image', 'course_thumbnail_image', 'btn_desc', 'entry_fee', 'video_url', 'video_cover_image'])
            ->with(['LandingPage' => function ($query) {
                $query->field('id,landing_image,lamp_back_image,end_image,desc_image,lamp_font_color,is_lamp,course_id');
            }]);

        if (!$courseObj->count()) {
            return self::_data($overdueDesc, $bannerArr, [], $hasAllSignUP);
        }
        $courseArr = $courseObj->select()->toArray();

        // 轮播图
        $bannerArr = Db::name('banner')
            ->where('is_many_organization', '=', 6/*env('YUQICLASS.v1')*/)
            ->where('status', '=', 1)
            ->where('delete_time', '=', 0)
            ->field('image')->select()->toArray();

        // 用户是否已报名
        $threadArr = Db::name('thread')
            ->field('course_id, merchant_id')
            ->where('course_id', 'in', array_column($courseArr, 'id'))
            ->where('uid', '=', $uid)
            ->select()->toArray();
        // $signUpCourseIds = array_column($threadArr, 'course_id');
        $merchantIds = array_column($threadArr, 'merchant_id');     // 一个商户多个课程，报名一个商户的课程，该商户的其他课程都为已报名状态

        foreach ($courseArr as &$item) {    // 该用户已报名
            $item['LandingPage'] = isset($item['LandingPage']) && !empty($item['LandingPage']) ? [$item['LandingPage']] : [];
            // $isApply = in_array($item['id'], $signUpCourseIds) ? 1 : 0;
            $isApply = in_array($item['merchant_id'], $merchantIds) ? 1 : 0;
            $item['is_apply'] = $isApply;
            $item['btn_desc'] = $isApply ? ($item['is_jump_miniprogram'] ? $item['apply_succeed_wx_btn'] : '') : $item['btn_desc'];

            if ($item['tag_names']) {   // 只展示4个标签
                $tagsNameArr = explode(',', $item['tag_names']);
                $item['tag_names'] = $tagsNameArr;
                if (count($tagsNameArr) > 4) {
                    $tags = [];
                    for ($i = 0; $i <= count($tagsNameArr); $i++) {
                        if ($i > 3) continue;
                        $tags[] = $tagsNameArr[$i];
                    }
                    $item['tag_names'] = $tags;
                }
            }

            if (empty($item['course_thumbnail_image'])) {
                $item['course_thumbnail_image'] = $item['video_cover_image'];
            }
        }
        return self::_data($overdueDesc, $bannerArr, $courseArr, $hasAllSignUP);
    }

    // 返回数组
    protected static function _data($overdueDesc, $bannerArr, $courseArr, $hasAllSignUP = '')
    {
        return [
            'overdueTitle' => isset($overdueDesc[0]) ? $overdueDesc[0] : '',
            'overdueDesc' => isset($overdueDesc[1]) ? $overdueDesc[1] : '',
            'bannerList' => ($bannerArr) ? array_column($bannerArr, 'image') : [],
            'courseArr' => $courseArr,
            'msgSignUP' => ($hasAllSignUP) ? '您已咨询' : ''
        ];
    }

    /**
     * 逾期列表 v2版本
     * @date 2022-09-19
     * @return array
     */
    public static function getOverdueV2($params)
    {
        extract($params);

        // 前端：渠道名称获取到渠道信息
        $channelInfo = Channel::getChannelAppClassOverdue($channel);
        if (!$channelInfo) return [];

        // 应用分类：线索加价梯度(元)
        $appId = $channelInfo['app_id'];
        $appClass = Db::name('app_class')->where('id', $channelInfo['app_class_id'])->field('id, thread_raise_price_grads')->find();
        $gradsPrice = isset($appClass['thread_raise_price_grads']) ? $appClass['thread_raise_price_grads'] : 1;

        // v2全报名标识
        $hasAllSignUP = false;
        $uid = $GLOBALS['uid'];
        $overdueImages = ($channelInfo['overdue_version_home_image']) ? json_decode($channelInfo['overdue_version_home_image'], true) : [];

        $courseModel = new \app\model\api\Course();
        $name = $courseModel->getName();
        $tableName = env('database.prefix') . $name;

        // 站内 | 站外
        $userInfo = UserList::where('id', $uid)->field('age_range_id, is_search_plan')->find();
        $gatherInfo = GatherUserInfoModel::getFormatGatherInfo($userInfo->age_range_id, 'age_range_id');
        $ageRange = !empty($gatherInfo['name']) ? '"' . $gatherInfo['name'] . '"' : '';

        // 好课推荐列表
        $courseObj = $courseModel->whereExists(function ($query) use ($tableName, $ageRange) {
            $merchantTableName = (new \app\model\api\Merchant())->getName();
            $query = $query->table(env('database.prefix') . $merchantTableName)->where(env('database.prefix') . $merchantTableName . '.id=' . $tableName . '.merchant_id');
            $query->where('is_switch', 1);
            $query->where("age_range_weight_json->'$." . $ageRange . "'", '>', 0);
            return $query;
        })->where('status', '=', 1)
            ->where('course_type', '=', 0)
            ->whereFindInSet('app_ids', $appId)
            ->field('id, title, virtual_apply_nums, tag_names, merchant_id, apply_succeed_wx_btn,landing_page_btn_image, course_thumbnail_image,btn_desc,entry_fee,video_url, video_cover_image')
            ->visible(['id', 'title', 'virtual_apply_nums', 'tag_names', 'merchant_id', 'apply_succeed_wx_btn','landing_page_btn_image', 'course_thumbnail_image', 'btn_desc', 'entry_fee', 'video_url', 'video_cover_image'])
            ->with(['merchant' => function ($query) {
                $query->field('id, peak_price, is_source, is_jump_miniprogram');    // 高峰线索单价
            }, 'LandingPage' => function ($query) {
                $query->field('id,landing_image,lamp_back_image,end_image,desc_image,lamp_font_color,is_lamp,course_id');
            }]);

        // 轮播图
        $bannerArr = Db::name('banner')
            ->where('is_many_organization', '=', 7/*env('YUQICLASS.v2')*/)
            ->where('status', '=', 1)
            ->where('delete_time', '=', 0)
            ->field('image')->select()->toArray();

        // 统计好课推荐总数
        $allCourseCount = $courseObj->count();
        if (!$allCourseCount) {
            return self::_dataV2($overdueImages, $bannerArr, $hasAllSignUP, []);
        }
        $courseArr = $courseObj->select()->toArray();

        // 用户是否已报名
        $threadArr = Db::name('thread')
            ->field('course_id, merchant_id')
            ->where('course_id', 'in', array_column($courseArr, 'id'))
            ->where('uid', '=', $uid)
            ->select()->toArray();
        $signUpCourseIds = array_column($threadArr, 'course_id');
        $signUpMerchantIds = array_column($threadArr, 'merchant_id');

        // 商户高峰线索单价
        $merchantPeakPriceArr = [];
        foreach ($courseArr as $info) {
            if (isset($info['merchant'])) {
                $merchant = $info['merchant'];
                $merchantPeakPriceArr[] = ['price' => $merchant['peak_price'], 'id' => $merchant['id'], 'is_source' => $merchant['is_source']];
            }
        }
        array_multisort($merchantPeakPriceArr, SORT_DESC);

        $sourceWArr = $sourceNArr = [];
        foreach ($merchantPeakPriceArr as $item) {
            if (!in_array($item['id'], $signUpMerchantIds)) {
                if ($item['is_source'] == 1) {   // 站内
                    $sourceNArr[] = $item;
                } else {
                    $sourceWArr[] = $item;
                }
            }
        }

        $num = 0;
        $merchantPeakPriceArr = ($sourceWArr) ? $sourceWArr : $sourceNArr;
        $peakPriceArr = [];                                         // 求周期数
        for ($i = count($merchantPeakPriceArr) - 1; $i >= 0; $i--) {
            $current = $merchantPeakPriceArr[$i];
            if ($i == count($merchantPeakPriceArr) - 1) {           // 价格最低的为1
                $peakPriceArr[] = [
                    'id' => $current['id'],
                    'price' => $current['price'],
                    'weight' => 1
                ];
                $num = 1;
            }

            if (!isset($merchantPeakPriceArr[$i - 1])) continue;    // 防止下标为负数

            $last = $merchantPeakPriceArr[$i - 1];
            $weightNum = intval(($last['price'] - $current['price']) / $gradsPrice);

            $num += $weightNum;
            $peakPriceArr[] = [
                'id' => $last['id'],
                'price' => $last['price'],
                'weight' => $num
            ];
        }

        // 权重随机返回商户ID
        $weight = new WeightService();
        $random = $weight->initData($peakPriceArr);

        $course = [];
        foreach ($courseArr as &$item) {    // 该用户已报名

            // 展示一个未报名的课程
            if (in_array($item['id'], $signUpCourseIds)) continue;

            if ($item['merchant_id'] == $random) {
                $item['LandingPage'] = isset($item['LandingPage']) && !empty($item['LandingPage']) ? [$item['LandingPage']] : [];

                $isApply = in_array($item['id'], $signUpCourseIds) ? 1 : 0;
                $item['is_apply'] = $isApply;
                $item['btn_desc'] = $isApply ? ($item['is_jump_miniprogram'] ? $item['apply_succeed_wx_btn'] : '') : $item['btn_desc'];

                if (empty($item['course_thumbnail_image'])) {
                    $item['course_thumbnail_image'] = $item['video_cover_image'];
                }

                $course = $item;
            }
        }

        // v2 是否全报名
        if ($allCourseCount == count($threadArr) || !$course) {
            $hasAllSignUP = true;
        }

        return self::_dataV2($overdueImages, $bannerArr, $hasAllSignUP, $course);
    }

    // 返回数组
    protected static function _dataV2($overdueImages, $bannerArr, $hasAllSignUP, $course)
    {
        return [
            'overdueImages' => $overdueImages,
            'bannerList' => ($bannerArr) ? array_column($bannerArr, 'image') : '',
            'msgSignUP' => ($hasAllSignUP) ? '您已咨询' : '',
            'isApply' => ($hasAllSignUP),
            'course' => $course
        ];
    }
    public function apply()
    {
        return $this->hasMany('app\model\admin\Thread','part_course_id','id')->whereDay('create_time')->where('uid', $GLOBALS['uid']);

    }
    //课程详情
    public static function getCourseDetailV3($params = [])
    {
        extract($params);
        $courseInfo = self::field('id,title,video_cover_image,video_url,virtual_apply_nums,score,course_chapter_json')->find($course_id);
        if (!empty($courseInfo)) {
            $courseInfo = $courseInfo->toArray();
            $courseInfo['course_chapter_json'] = !empty($courseInfo['course_chapter_json']) ? json_decode($courseInfo['course_chapter_json'], true) : [];
            $course = CommonCourse::getCommonCourseToCourseId($course_id, isset($channel) && !empty($channel) ? $channel : UserList::where('id', $GLOBALS['uid'])->value('channel'));
            $courseInfo['id'] = $course['course_id'];
            $courseInfo['part_course_id'] = $course_id;
            $courseInfo['is_apply'] = $course['is_apply'];
            $courseInfo['is_jump_miniprogram'] = $course['is_jump_miniprogram'];
            return $courseInfo;
        }
        new Exception('课程不存在或已下架');
    }
    public static function CourseDetailV3($params = [])
    {
        extract($params);
        $applyInfo = Thread::where('uid', $GLOBALS['uid'])->where('part_course_id', $course_id)->whereDay('create_time')->order('id desc')->find();
        $channelInfo = Channel::getChannelAppClass($channel);
        if (!empty($applyInfo)) {
            $isJumpWx = self::getChannelJumpWechat($channel);
            if ($isJumpWx) {
                $isJumpWx = Merchant::where('id', $applyInfo->merchant_id)->value('is_jump_miniprogram');
            }
            new Exception('已经领取过了', 101, 200, ['course_id' => $applyInfo->course_id, 'is_jump_miniprogram' => $isJumpWx]);
        }
        $entryFee = self::where('id', $course_id)->value('entry_fee');
        $merchantList = MerchantServiceJob::getMerchantIsPayCount($channelInfo);
        $merchantIdData = Thread::where('uid', $GLOBALS['uid'])->group('merchant_id')->column('merchant_id');

        if (!empty($merchantIdData)) {
            $merchantData = $entryFee > 0 ? $merchantList['tempPayMerchantData'] : $merchantList['tempFreeMerchantData'];
            foreach ($merchantData as $item => $val) {
                if (in_array($val['id'], $merchantIdData)) {
                    unset($merchantData[$item]);
                }
            }
            $merchantDataList = array_values($merchantData);
            if (!empty($merchantDataList)) {
                $randKey = array_rand($merchantDataList);
                $merchantId = $merchantDataList[$randKey]['id'];
            } else {
                $applyInfo = Thread::where('uid', $GLOBALS['uid'])->order('id desc')->find();
                if (!empty($applyInfo)) {
                    $isJumpWx = self::getChannelJumpWechat($channel);
                    if ($isJumpWx) {
                        $isJumpWx = Merchant::where('id', $applyInfo->merchant_id)->value('is_jump_miniprogram');
                    }
                    new Exception('已经领取过了', 101, 200, ['course_id' => $applyInfo->course_id, 'is_jump_miniprogram' => $isJumpWx]);
                }
            }
        } else {
            $merchantId = MerchantServiceJob::sortMerchantList($merchantList, $channelInfo, $entryFee);
        }
        $course = Course::field('id,entry_fee')->where('merchant_id',$merchantId)->where('course_type',0)->find();
        $data = self::field('id,title,video_cover_image,video_url,apply_succeed_wx_btn,video_burning_time,entry_fee,btn_desc,virtual_apply_nums,landing_page_btn_image,confirm_btn_desc_background_color,confirm_btn_desc_font_color,confirm_btn_desc')->with(['LandingPage' => function($query){
            $query->field('id,landing_image,lamp_back_image,end_image,desc_image,lamp_font_color,is_lamp,course_id');
        }])->where('id', $course->id)->find()->toArray();
        $data['is_affirm_page'] = $course->entry_fee > 0 ? $channelInfo['pay_landing_page_affirm'] : $channelInfo['free_landing_page_affirm'];
        $data['id'] = $course_id;
       // $data['LandingPage']['course_id'] = $course_id;
        return $data;
    }
    //PR V3首页列表
    public static function getCourseListV3($params = [])
    {
        extract($params);
        $channelInfo = Channel::getChannelAppClass($channel);
        $merchantList = MerchantServiceJob::getMerchantIsPayCount($channelInfo);
        extract($merchantList);
        $where = [];
        if ($payMerchantNums > 0 && $freeMerchantNums == 0) {
            $where[] = ['entry_fee','>',0];
        } else if($freeMerchantNums > 0 && $payMerchantNums == 0) {
            $where[] = ['entry_fee','=',0];
        }
        $courseList = self::field('id,title,video_cover_image,entry_fee')->where($where)->where('app_class_id', $channelInfo['app_class_id'])->where('course_type', 5)->where('app_class_id', $channelInfo['app_class_id'])->select()->toArray();
        //return $courseList;

       /* var_dump($courseList);die;
        $courseModel = (new self());
        $name = $courseModel->getName();
        $tableName = env('database.prefix') . $name;
        $userInfo = UserList::where('id',$GLOBALS['uid'])->field('app_class_id,phone,age_range_id,is_search_plan')->find();
        $ageRangeId = $userInfo->age_range_id;
        $gatherInfo = GatherUserInfoModel::getFormatGatherInfo($ageRangeId, 'age_range_id');
        $ageRange = !empty($gatherInfo['name']) ? '"'.$gatherInfo['name'].'"' : '';
        $channelInfo = Channel::getChannelAppClass($channel);
        $outsideMerchantCount = $courseModel->where($tableName . '.course_type', 0)->whereExists(function ($query) use ($tableName, $ageRange) {
            $merchantTableName = (new \app\model\api\Merchant())->getName();
            $query = $query->table(env('database.prefix') .$merchantTableName)->where(env('database.prefix') . $merchantTableName . '.id=' .   $tableName . '.merchant_id');
            $query->where('is_switch', 1)->where("age_range_weight_json->'$.".$ageRange."'",'>',0);
            $query->where('is_source', 2);
            return $query;
        })->whereExists(function ($query) use ($tableName) {
            $landingPageTableName = (new \app\model\api\LandingPage())->getName();
            $query = $query->table(env('database.prefix') .$landingPageTableName)->where(env('database.prefix') . $landingPageTableName . '.course_id=' .   $tableName . '.id');
            return $query;
        })->count();
        $courseList = $courseModel->field('id,title,video_cover_image,video_url,apply_succeed_wx_btn,video_burning_time,entry_fee,btn_desc,virtual_apply_nums,merchant_id,landing_page_btn_image,confirm_btn_desc_background_color,confirm_btn_desc_font_color,confirm_btn_desc')->with(['merchant' => function($query){
            $query->field('id');
        },'LandingPage' => function($query){
            $query->field('id,landing_image,lamp_back_image,end_image,desc_image,lamp_font_color,is_lamp,course_id');
        }])->withCount(['apply'])->where($tableName . '.course_type', 0)->whereFindInSet('channel_ids', $channelInfo['channel_id'])->whereExists(function ($query) use ($tableName, $ageRange, $outsideMerchantCount) {
            $merchantTableName = (new \app\model\api\Merchant())->getName();
            $query = $query->table(env('database.prefix') .$merchantTableName)->where(env('database.prefix') . $merchantTableName . '.id=' .   $tableName . '.merchant_id');
            $query->where('is_switch', 1)->where("age_range_weight_json->'$.".$ageRange."'",'>',0);
            $query->where('is_source', $outsideMerchantCount ? 2 : 1);
            return $query;
        })->whereExists(function ($query) use ($tableName) {
            $landingPageTableName = (new \app\model\api\LandingPage())->getName();
            $query = $query->table(env('database.prefix') .$landingPageTableName)->where(env('database.prefix') . $landingPageTableName . '.course_id=' .   $tableName . '.id');
            return $query;
        })->select()->toArray();
        foreach ($courseList as &$val) {
            $val['is_affirm_page'] = $val['entry_fee'] > 0 ? $channelInfo['pay_landing_page_affirm'] : $channelInfo['free_landing_page_affirm'];
        }*/
        $studyPlanDateList = Thread::where('uid', $GLOBALS['uid'])->column('create_time');
        foreach ($studyPlanDateList as &$val) {
            $val = date('Y-m-d', $val);
        }
        $imageData = [
            '1' => ['http://cdnwm.yuluojishu.com/uploads/20230320/efd05639f67d0ba1591ab1a6ac1bd033.png','http://cdnwm.yuluojishu.com/uploads/20230320/f173b3c3a8e0ad471c28011d57ff9935.png'],
            '10' => ['http://cdnwm.yuluojishu.com/uploads/20230320/28dab25ef19cf8885e077530ba7a41c0.png','http://cdnwm.yuluojishu.com/uploads/20230320/f173b3c3a8e0ad471c28011d57ff9935.png'],
            '22' => ['http://cdnwm.yuluojishu.com/uploads/20230320/0c1efade6c7ca14a01f048b05619bee8.pn','http://cdnwm.yuluojishu.com/uploads/20230320/f173b3c3a8e0ad471c28011d57ff9935.png'],
        ];
        $merchantId = 177;
        return [
            'courseList' => $courseList,
            'imageList' => isset($imageData[$channelInfo['app_class_id']]) ? $imageData[$channelInfo['app_class_id']] : [],
            'bannerList' => Banner::getMerchantBannerList($merchantId,$channelInfo),
            'studyPlanDateList' => $studyPlanDateList,
        ];
    }
    //学习列表
    public static function getStudyList($params)
    {
        extract($params);
        $channelInfo = Channel::getChannelAppClass($channel);
        $merchantList = MerchantServiceJob::getMerchantIsPayCount($channelInfo);
        extract($merchantList);
        $where = [];
        if ($payMerchantNums > 0 && $freeMerchantNums == 0) {
            $where[] = ['entry_fee','>',0];
        } else if($freeMerchantNums > 0 && $payMerchantNums == 0) {
            $where[] = ['entry_fee','=',0];
        }
        $courseList = self::field('id,title,video_cover_image,entry_fee')->where($where)->where('app_class_id', $channelInfo['app_class_id'])->where('course_type', 5)->where('app_class_id', $channelInfo['app_class_id'])->select()->toArray();
        /*$courseModel = (new self());
        $name = $courseModel->getName();
        $tableName = env('database.prefix') . $name;
        $userInfo = UserList::where('id',$GLOBALS['uid'])->field('app_class_id,phone,age_range_id,is_search_plan')->find();
        $ageRangeId = $userInfo->age_range_id;
        $gatherInfo = GatherUserInfoModel::getFormatGatherInfo($ageRangeId, 'age_range_id');
        $ageRange = !empty($gatherInfo['name']) ? '"'.$gatherInfo['name'].'"' : '';
        $channelInfo = Channel::getChannelAppClass($channel);
        $outsideMerchantCount = $courseModel->where($tableName . '.course_type', 0)->whereExists(function ($query) use ($tableName, $ageRange) {
            $merchantTableName = (new \app\model\api\Merchant())->getName();
            $query = $query->table(env('database.prefix') .$merchantTableName)->where(env('database.prefix') . $merchantTableName . '.id=' .   $tableName . '.merchant_id');
            $query->where('is_switch', 1)->where("age_range_weight_json->'$.".$ageRange."'",'>',0);
            $query->where('is_source', 2);
            return $query;
        })->whereExists(function ($query) use ($tableName) {
            $landingPageTableName = (new \app\model\api\LandingPage())->getName();
            $query = $query->table(env('database.prefix') .$landingPageTableName)->where(env('database.prefix') . $landingPageTableName . '.course_id=' .   $tableName . '.id');
            return $query;
        })->count();
        $courseList = $courseModel->field('id,title,video_cover_image,video_url,apply_succeed_wx_btn,video_burning_time,entry_fee,btn_desc,virtual_apply_nums,merchant_id,landing_page_btn_image,confirm_btn_desc_background_color,confirm_btn_desc_font_color,confirm_btn_desc')->with(['merchant' => function($query){
            $query->field('id');
        },'LandingPage' => function($query){
            $query->field('id,landing_image,lamp_back_image,end_image,desc_image,lamp_font_color,is_lamp,course_id');
        }])->withCount(['apply'])->where($tableName . '.course_type', 0)->whereFindInSet('channel_ids', $channelInfo['channel_id'])->whereExists(function ($query) use ($tableName, $ageRange, $outsideMerchantCount) {
            $merchantTableName = (new \app\model\api\Merchant())->getName();
            $query = $query->table(env('database.prefix') .$merchantTableName)->where(env('database.prefix') . $merchantTableName . '.id=' .   $tableName . '.merchant_id');
            $query->where('is_switch', 1)->where("age_range_weight_json->'$.".$ageRange."'",'>',0);
            $query->where('is_source', $outsideMerchantCount ? 2 : 1);
            return $query;
        })->whereExists(function ($query) use ($tableName) {
            $landingPageTableName = (new \app\model\api\LandingPage())->getName();
            $query = $query->table(env('database.prefix') .$landingPageTableName)->where(env('database.prefix') . $landingPageTableName . '.course_id=' .   $tableName . '.id');
            return $query;
        })->select()->toArray();
        $freeCourseData = [];
        $courseData = [];
        foreach ($courseList as $val) {
            $val['is_affirm_page'] = $val['entry_fee'] > 0 ? $channelInfo['pay_landing_page_affirm'] : $channelInfo['free_landing_page_affirm'];
            if ($val['entry_fee'] > 0) {
                $courseData[] = $val;
            } else {
                $freeCourseData[] = $val;
            }
        }*/
        $freeCourseData = [];
        $courseData = [];
        foreach ($courseList as $val) {
            if ($val['entry_fee'] > 0) {
                $courseData[] = $val;
            } else {
                $freeCourseData[] = $val;
            }
        }
        $tempData = [];
        if (!empty($courseData)) {
            $tempData[] =  ['className' => '进阶课', 'courseList' => $courseData];
        }
        if (!empty($freeCourseData)) {
            $tempData[] =  ['className' => '免费公开课', 'courseList' => $freeCourseData];
        }
        $videoInfoData = [
            '1' => [
                'video_url' => 'http://cdnwm.yuluojishu.com/20220526/d5d1a3d85a3ce535d3b37127c4abfb8b.mp4',
                'video_cover_image' => 'http://cdnwm.yuluojishu.com/uploads/20220511/bd3c087e2fd965cd3eb51b1e9b2f96c4.jpg',
                'price' => '3699.00',
                'title' => '小白做配音走上变现之路',
                'subtitle' => '声音变现',
            ],
            '10' => [
                'video_url' => 'http://cdnwm.yuluojishu.com/20231214/90e6c68e5d76d8726d610ab120c22116.mp4',
                'video_cover_image' => 'http://cdnwm.yuluojishu.com/uploads/20220712/5225b7f6b043ed8c9715a1141905e35d.jpg',
                'price' => '6780',
                'title' => '游戏原画从零到精通',
                'subtitle' => '原画插画怎样学？',
            ],
            '22' => [
                'video_url' => 'http://cdnwm.yuluojishu.com/20220926/58dedaf216f82c5ac91aad7e9ad5c893.mp4',
                'video_cover_image' => 'http://cdnwm.yuluojishu.com/uploads/20220926/e2a6fbe5c93c15410bf62ad1d0d75255.jpg',
                'price' => '4699',
                'title' => '视频剪辑变现之路',
                'subtitle' => '剪辑师的快乐',
            ],
        ];
        return [
            'videoInfo' => isset($videoInfoData[$channelInfo['app_class_id']]) ? $videoInfoData[$channelInfo['app_class_id']] : [],
            'courseData' => $tempData,
        ];
    }
    //我的兼职
    public static function myJobList($params = [])
    {
        extract($params);
        $model = new Thread();
        $name = $model->getName();
        $tableName = env('database.prefix') . $name;
        $myJobList = $model->field('course_id,part_course_id')->with(['course' => function($query){
            $query->field('id');
        },'myJob' => function($query){
            $query->field('id,title,compensation,merchant_id,tag_ids,btn_desc,compensation_type,content,virtual_apply_nums');
        }])->withJoin(['myJob'], 'inner')->where($tableName . '.uid', $GLOBALS['uid'])->where($tableName . '.part_course_id', '>', 0)->where('myJob.course_type', 1)->select()->toArray();
        foreach ($myJobList as &$val) {
            $val['myJob']['content_desc'] = isset($val['myJob']['content']) && !empty($val['myJob']['content']) ? getEditContentText($val['myJob']['content']) : '';
            $val['course']['btn_desc'] = isset($val['course']['is_jump_miniprogram']) ? ($val['course']['is_jump_miniprogram'] > 0 ? '在线沟通' : '在线沟通') : '在线沟通';
            unset($val['myJob']['content']);
        }
        return $myJobList;
    }

    //我的课程
    public static function myCourseList($params = [])
    {
        extract($params);
        $model = new Thread();
        $name = $model->getName();
        $tableName = env('database.prefix') . $name;
        $myCourseList = $model->field('course_id,part_course_id')->with(['course' => function($query){
            $query->field('id');
        },'myCourseList' => function($query){
            $query->field('id,title,video_cover_image');
        }])->withJoin(['myCourseList'], 'inner')->where($tableName . '.uid', $GLOBALS['uid'])->where($tableName . '.part_course_id', '>', 0)->where('myCourseList.course_type', 5)->select()->toArray();
        foreach ($myCourseList as &$val) {
            $val['course']['btn_desc'] = isset($val['course']['is_jump_miniprogram']) ? ($val['course']['is_jump_miniprogram'] > 0 ? '在线沟通' : '在线沟通') : '在线沟通';
            unset($val['myJob']['content']);
        }
        return $myCourseList;
    }
}
