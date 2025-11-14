<?php

namespace app\model\api\fortunecat;

use app\lib\api\other\CourseJumpWx;
use app\lib\api\other\UserCity;
use app\lib\api\service\MerchantServiceJob;
use app\lib\api\service\MerchantServiceOverdue;
use app\model\api\Channel;
use app\model\api\Merchant;
use app\model\api\Thread;
use app\model\api\UserList;
use laytp\BaseModel;
use think\facade\Config;
use app\model\api\fortunecat\Banner;
use app\model\api\fortunecat\Course;
use app\model\api\fortunecat\PartCourseTag;
use app\model\api\v2\GatherUserInfo as GatherUserInfoModel;
class Show extends BaseModel
{
    public static function homePage($params = [])
    {
        extract($params);
        $channel = isset($channel) && !empty($channel) ? $channel : '';
        $partClassId = isset($part_class_id) && !empty($part_class_id) ? $part_class_id : 8;
        $merchantId = isset($merchant_id) && !empty($merchant_id) ? $merchant_id : 0;
        $channelInfo = Channel::getChannelAppClass($channel);
        $appId = $channelInfo['app_id'];
        $data = [];
        $where = [];
        $where1 = [];
        $limit_num = 10;
        if($channel && $appId) {
            $where[] = ['status','=',1];
            $where[] = ['course_type','=',1];
            //$where[] = ['app_class_id','=',$channelInfo['app_class_id']];
            $where[] = ['channel_ids','find in set',$channelInfo['channel_id']];
            if ($partClassId) {
                $where1[] = ['part_class_ids','find in set',$partClassId];
            } else {
                $where[] = ['is_recommend','=',1];
            }
//            $ageRangeId = UserList::where('id', $GLOBALS['uid'])->value('age_range_id');
//            $gatherInfo = GatherUserInfoModel::getFormatGatherInfo($ageRangeId, 'age_range_id');
//            $ageRange = !empty($gatherInfo['name']) ? '"'.$gatherInfo['name'].'"' : '';
            $bannerList = Banner::getMerchantBannerList($merchantId,$channelInfo);
            $courseModel =  new \app\model\api\fortunecat\Course();
//            $name = strtolower($courseModel->getName());
//            $tableName = env('database.prefix') . $name;
//            $courseCount  = $courseModel->whereExists(function ($query) use ($tableName,$ageRange) {
//                $merchantTableName = (new \app\model\admin\Merchant())->getName();
//                $query = $query->table(env('database.prefix') .$merchantTableName)->where(env('database.prefix') . $merchantTableName . '.id=' .   $tableName . '.merchant_id');
//                $query->where('is_source', 2);
//                $query->where('is_switch', 1);
//                $query->where("age_range_weight_json->'$.".$ageRange."'",'>',0);
//                return $query;
//            })
//                ->where($where)
//                ->count();
//            if($courseCount > 0){
//                $courseList  = $courseModel->whereExists(function ($query) use ($tableName,$ageRange) {
//                    $merchantTableName = (new \app\model\admin\Merchant())->getName();
//                    $query = $query->table(env('database.prefix') .$merchantTableName)->where(env('database.prefix') . $merchantTableName . '.id=' .   $tableName . '.merchant_id');
//                    $query->where('is_source', 2);
//                    //$query->where('is_switch', 1);
//                    $query->where("age_range_weight_json->'$.".$ageRange."'",'>',0);
//                    return $query;
//                })
//                    ->with(['merchant' => function($query){
//                        $query->field('id,is_switch,company_name');
//                    }])
//                    ->where($where)
//                    ->field('id,title,sort,compensation,merchant_id,tag_ids,btn_desc,compensation_type')
//                    ->order(['sort'=>'desc','id'=>'desc'])
//                    ->paginate($limit_num)
//                    ->toArray();
//            }else{
//                $courseList  = $courseModel->whereExists(function ($query) use ($tableName,$ageRange) {
//                    $merchantTableName = (new \app\model\admin\Merchant())->getName();
//                    $query = $query->table(env('database.prefix') .$merchantTableName)->where(env('database.prefix') . $merchantTableName . '.id=' .   $tableName . '.merchant_id');
//                    //$query->where('is_switch', 1);
//                    $query->where("age_range_weight_json->'$.".$ageRange."'",'>',0);
//                    return $query;
//                })
//                    ->with(['merchant' => function($query){
//                        $query->field('id,is_switch,company_name');
//                    }])
//                    ->where($where)
//                    ->field('id,title,sort,compensation,merchant_id,tag_ids,btn_desc,compensation_type')
//                    ->order(['sort'=>'desc','id'=>'desc'])
//                    ->paginate($limit_num)
//                    ->toArray();
//            }
            $merchantData = MerchantServiceJob::getMerchantIsPayCount($channelInfo);
            if($merchantData['outsideMerchantCount'] > 0){
                if($merchantData['payMerchantNums'] > 0 && $merchantData['freeMerchantNums'] == 0){
                    $where[] = ['entry_fee','>',0];
                }else if($merchantData['freeMerchantNums'] > 0 && $merchantData['payMerchantNums'] == 0){
                    $where[] = ['entry_fee','=',0];
                }
            }else{
                if($merchantData['payMerchantNums'] > 0 && $merchantData['freeMerchantNums'] == 0){
                    $where[] = ['entry_fee','>',0];
                }else if($merchantData['freeMerchantNums'] > 0 && $merchantData['payMerchantNums'] == 0){
                    $where[] = ['entry_fee','=',0];
                }
            }
            $courseList  = $courseModel->with(['merchant' => function($query){
                $query->field('id,is_switch,company_name');
            }])
                ->where($where)
                ->where($where1)
                ->field('id,title,sort,compensation,merchant_id,tag_ids,btn_desc,compensation_type,content,virtual_apply_nums')
                ->order(['sort'=>'desc','id'=>'desc'])
                ->paginate($limit_num)
                ->toArray();
            $courseTitle = '配音';
            if($channelInfo['app_class_id'] == 10) $courseTitle = '原画';
            if($channelInfo['app_class_id'] == 22) $courseTitle = '兼职';
            if($channelInfo['app_class_id'] == 23) $courseTitle = 'ai绘画';
            $courseShowList  = $courseModel->with(['merchant' => function($query){
                $query->field('id,is_switch,company_name');
            }])
                ->where($where)
                ->whereFindInSet('part_class_ids',8)
                ->where('title','like','%'.$courseTitle.'%')
                ->field('id,title,sort,compensation,merchant_id,tag_ids,btn_desc,compensation_type,content,virtual_apply_nums')
                ->order(['sort'=>'desc','id'=>'desc'])
                ->limit(3)
                ->select()
                ->toArray();
            foreach ($courseList['data'] as &$val) {
                $val['content_desc'] = isset($val['content']) && !empty($val['content']) ? getEditContentText($val['content']) : '';
                unset($val['content']);
            }
            $data['bannerList'] = !empty($bannerList) ? $bannerList : [];
            if(isset($courseList['data']) && !empty($courseList['data'])){
                //foreach($courseList['data'] as $val){
                //    $isSwitch[] = isset($val['merchant']['is_switch']) ? $val['merchant']['is_switch'] : 0;
                //}
                //array_multisort($isSwitch, SORT_DESC,array_column($courseList['data'], 'sort'),SORT_DESC,array_column($courseList['data'], 'id'),SORT_DESC, $courseList['data']);
                $data['courseList'] = isset($courseList['data']) && !empty($courseList['data']) ? $courseList['data'] : [];
                $data['courseShowList'] = !empty($courseShowList) ? $courseShowList : [];
            } else {
                $data['courseList'] = [];
                $data['courseShowList'] = [];
            }
        }
        return $data;
    }

    public static function trainCourseList($params = [])
    {
        extract($params);
        $channelInfo = Channel::getChannelAppClass($channel);
        $courseModel =  new Course();
        $whereCon = " status = 1";
        $whereCon .= " and course_type = 2";
        $whereCon .= " and find_in_set({$channelInfo['channel_id']},channel_ids)";
        if(!empty($partClassId)) {
            $whereCon .= " and find_in_set({$partClassId}, part_class_ids)";
        }
        $merchantData = MerchantServiceJob::getMerchantIsPayCount($channelInfo);
        if($merchantData['outsideMerchantCount'] > 0){
            if($merchantData['payMerchantNums'] > 0 && $merchantData['freeMerchantNums'] == 0){
                $whereCon .= " and entry_fee > 0";
            }else if($merchantData['freeMerchantNums'] > 0 && $merchantData['payMerchantNums'] == 0){
                $whereCon .= " and entry_fee = 0";
            }
        }else{
            if($merchantData['payMerchantNums'] > 0 && $merchantData['freeMerchantNums'] == 0){
                $whereCon .= " and entry_fee > 0";
            }else if($merchantData['freeMerchantNums'] > 0 && $merchantData['payMerchantNums'] == 0){
                $whereCon .= " and entry_fee = 0";
            }
        }
        $recommendCourse = $courseModel->with(['merchant' => function ($query) {
            $query->field('id,is_switch,company_name');
        },
            'courseTag' => function($query){
                $query->field('id,tag_name,tag_color');
            }
        ])
            ->where($whereCon)
            ->field('id,title,video_cover_image,entry_fee,virtual_apply_nums')
            ->order(['sort' => 'desc', 'id' => 'desc'])
            ->limit(6)
            ->select()
            ->toArray();
        if(!empty($recommendCourse)){
            foreach($recommendCourse as &$val){
                $isSwitch[] = isset($val['merchant']['is_switch']) ? $val['merchant']['is_switch'] : 0;
                $val['courseTag'] = $val['courseTag'] ?? new \stdClass();
                if ($val['entry_fee'] > 0 &&  UserCity::checkCity($channel)) {
                    $val['entry_fee'] = '0.00';
                }
                unset($val['part_course_tag_names']);
                unset($val['courseTag']);
            }
        }
        return $recommendCourse;
    }

    //原画兼案例
    public static function showCourseCase($params = [])
    {
        extract($params);
        $data['caseChannel'] = [
            'title' => '免费报名成为学员',
            'avator' => 'http://cdnwm.yuluojishu.com/uploads/20230720/f06de4c7c40c0af592355bfb9456fd2f.png',
            'content' => '一键开启你的报名兼职',
            'btn_desc' => '去报名'
        ];
        $data['workExhibition'] = [
            [
                'unit_price' => '接单价2400',
                'part_serie' => 'AIGC系列',
                'learn_call' => '报名学习前：小白',
                'avator' => 'http://cdnwm.yuluojishu.com/uploads/20230720/a361ef09c1a3b0744c05f57b67f0a1d7.jpg',
                'work_image' => 'http://cdnwm.yuluojishu.com/uploads/20230720/6eafdd50a15c904ec7f8e34afcb106eb.png',
                'nickname' => '欧阳上官'
            ],
            [
                'unit_price' => '接单价2700',
                'part_serie' => 'AIGC系列',
                'learn_call' => '报名学习前：小白',
                'avator' => 'http://cdnwm.yuluojishu.com/uploads/20230720/e9b302d4bf40a6a77d8eb27a2f3212d6.jpg',
                'work_image' => 'http://cdnwm.yuluojishu.com/uploads/20230720/d867019ae3ea4e56a5420be67a31f91c.png',
                'nickname' => '端木硕'
            ],
            [
                'unit_price' => '接单价3200',
                'part_serie' => 'AIGC系列',
                'learn_call' => '报名学习前：小白',
                'avator' => 'http://cdnwm.yuluojishu.com/uploads/20230720/cf1548eeb90f4fac39421c2c48fee4a5.jpg',
                'work_image' => 'http://cdnwm.yuluojishu.com/uploads/20230720/64ced08514c14cb223f2fa7f58b2ef42.png',
                'nickname' => '皇甫虎'
            ],
            [
                'unit_price' => '接单价3000',
                'part_serie' => 'AIGC系列',
                'learn_call' => '报名学习前：小白',
                'avator' => 'http://cdnwm.yuluojishu.com/uploads/20230720/a4c9536e7ab1a23408251e4cbb8f6719.jpg',
                'work_image' => 'http://cdnwm.yuluojishu.com/uploads/20230720/b07d0358f6eb90121cd22042c574d561.jpg',
                'nickname' => '司徒静'
            ],
        ];
        $data['showPoster'] = [
            'poster_image' => 'http://cdnwm.yuluojishu.com/uploads/20230720/d8daed62e608d5b6f3fb2a52f841332a.png',
            'poster_image_min' => 'http://cdnwm.yuluojishu.com/uploads/20230720/bd6441625879f6cb77261883b7b4fd8c.png',
            'poster_image_desc' => 'http://cdnwm.yuluojishu.com/uploads/20230720/419cd0e5d63fc4a649502312af93ac9f.png',
            'btn_desc' => '报名免费带你走上设计之路'
        ];
        $data['applyLearn'] = [
            'imageShow' => [
                'http://cdnwm.yuluojishu.com/uploads/20230720/3c25d653e8f74066cf2de2c167a9c9e0.png',
            ],
            'btn_desc' => '报名挑战',
            'workImage' => [
                'http://cdnwm.yuluojishu.com/uploads/20230720/7a42347fee73a1e38d9148d3bdddd4f4.png',
                'http://cdnwm.yuluojishu.com/uploads/20230720/07a2b9f501615f4374e2d1d5f347f9aa.png',
                'http://cdnwm.yuluojishu.com/uploads/20230720/3d206b984650e8d9f722142b5886a66f.png'
            ]
        ];
        $data['bottomPoster'] = [
            'poster_image' => 'http://cdnwm.yuluojishu.com/uploads/20230720/b68537539d97ede6244755b6684d3928.png',
            'poster_desc' => "从小白到职业\n只差这一步",
            'btn_desc' => '马上报名'
        ];
        $data['learnerExperience'] = [
            [
                'avator' => 'http://cdnwm.yuluojishu.com/uploads/20230720/cc5ce3de6697d332c63299d1e966e4dd.jpg',
                'career_nickname' => '概念设计师',
                'learn_time' => '学习3个月',
                'content_experience' => '还没开始学习这门课程时，我是一个缺乏对原画知识了解的新手小白，几个月的原画基础班已经顺利结课了老师们在讲课的时候往往用最精辟、最生动的语言深入浅出的教导我们，让我们深刻理解每一个绘画知识...'
            ],
            [
                'avator' => 'http://cdnwm.yuluojishu.com/uploads/20230720/01c26ebab78f1cd306a432928b1ff9ba.jpg',
                'career_nickname' => '场景设计师',
                'learn_time' => '学习2个月',
                'content_experience' => '一切的开始都应该是兴趣驱动，如果喜欢原画，就立刻开始，不要纠结所谓的天分问题，热爱就是最好的天分。在我的学习过程中，老师也教会了我很多以前我从未发现的问题，从观察方法到画画技法都教的很细致...'
            ],
            [
                'avator' => 'http://cdnwm.yuluojishu.com/uploads/20230720/ca52597208d01a620ddc3cd0087c62e4.jpg',
                'career_nickname' => '结构设计师',
                'learn_time' => '学习3个月',
                'content_experience' => '相比之前，我已经可以画一些简单的原创作品。在这里，跟大家分享一些我学学员的小经验：在学习的过程中，我们要勇敢地向老师“提问”，不懂就要问，不要害怕。要懂得去提升审美，培养审美...'
            ]
        ];
        $data['employmentEnterprises'] = [
            'image' => 'http://cdnwm.yuluojishu.com/uploads/20230720/5447fa4974f747d2af2f9127cbe60cc4.png'
        ];
        $courseInfo = self::getTrainCourseInfo($params);
        $thread = Thread::where('uid', $GLOBALS['uid'])->where('app_class_id',10)->order('id desc')->find();
        $apply_success_msg = Merchant::where('id', isset($thread->merchant_id) ? $thread->merchant_id : $courseInfo->merchant_id)->value('apply_success_msg');
        $data['showCourse'] = [
            'course_id' => $courseInfo->id,
            'part_course_id' => $courseInfo->id,
            'is_jump_miniprogram' => isset($courseInfo->id) ? CourseJumpWx::getCourseJumpWxStatus($courseInfo->course_id, $channel) : CourseJumpWx::getCourseJumpWxStatus($thread->course_id, $channel),
            'apply_success_msg' => $apply_success_msg,
            'is_apply' => !empty($thread) ? 1 : 0,
        ];
        return $data;
    }

    public static function getTrainCourseInfo($params = [])
    {
        extract($params);
        $channelInfo = Channel::getChannelAppClass($channel);
        $courseModel =  new Course();
        $whereCon = " status = 1";
        $whereCon .= " and course_type = 2";
        $whereCon .= " and find_in_set({$channelInfo['channel_id']},channel_ids)";
        if(!empty($partClassId)) {
            $whereCon .= " and find_in_set({$partClassId}, part_class_ids)";
        }
        $merchantData = MerchantServiceJob::getMerchantIsPayCount($channelInfo);
        if($merchantData['outsideMerchantCount'] > 0){
            if($merchantData['payMerchantNums'] > 0 && $merchantData['freeMerchantNums'] == 0){
                $whereCon .= " and entry_fee > 0";
            }else if($merchantData['freeMerchantNums'] > 0 && $merchantData['payMerchantNums'] == 0){
                $whereCon .= " and entry_fee = 0";
            }
        }else{
            if($merchantData['payMerchantNums'] > 0 && $merchantData['freeMerchantNums'] == 0){
                $whereCon .= " and entry_fee > 0";
            }else if($merchantData['freeMerchantNums'] > 0 && $merchantData['payMerchantNums'] == 0){
                $whereCon .= " and entry_fee = 0";
            }
        }
        $recommendCourse = $courseModel->where($whereCon)
            ->field('id,title,merchant_id,video_cover_image,entry_fee,virtual_apply_nums')
            ->order(['sort' => 'desc', 'id' => 'desc'])
            ->find();
        return $recommendCourse;
    }


}