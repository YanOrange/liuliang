<?php

namespace app\controller\admin;

use app\lib\api\city\IpCity;
use app\lib\api\exception\Exception;
use app\lib\api\service\CustomerService;
use app\lib\api\service\WeightService;
use app\lib\api\sms\MarketingSms;
use app\lib\api\wxmini\WxMiniCusqrcode;
use app\lib\api\wxmini\WxMiniCusqrcodeV2;
use app\model\admin\Customer;
use app\model\admin\MobileGetCityinfo;
use app\model\api\Course;
use app\model\admin\Thread;
use app\model\api\v2\GatherUserInfo as GatherUserInfoModel;
use app\service\admin\AuthServiceFacade;
use app\service\admin\UserServiceFacade;
use app\validate\admin\thread\ThreadTransformation as ThreadTransformationValidate;
use laytp\controller\Backend;
use laytp\library\CommonFun;
use think\facade\Db;
use think\facade\Event;
use think\facade\Session;
use app\model\admin\Merchant;
use app\model\admin\UserList;
use app\model\admin\User;
use think\facade\Queue;
use app\model\admin\AssignThreadQueueLog;
use app\model\admin\App;
use app\model\admin\Channel;
use app\model\admin\GatherUserInfo;
use app\model\admin\UserProfile;

class ThreadUserMerchant extends Backend
{
    protected $model;//当前模型对象
    protected $isAssignFlag = false;

    protected $noNeedAuth = ['*'];
    protected $noNeedLogin = [''];

    protected function _initialize()
    {
        $this->model = new \app\model\admin\Thread();
    }

    //关联商户渠道
    public function channelList()
    {
        $loginUserInfo = UserServiceFacade::getUserInfo();
        $merchantIds = explode(',',$loginUserInfo['merchant_ids']);
        $appClassIds = Merchant::whereIn('id',$merchantIds)->group('app_class_id')->column('app_class_id');
        $appIds = App::whereIn('app_class_id',$appClassIds)->column('id');
//        $channelList = Channel::whereIn('app_id',$appIds)
//            ->field('id,channel_name')
//            ->order('id desc')
//            ->select();
        $channelList = Channel::field('id,channel_name')
            ->order('id desc')
            ->select();
        return $this->success('获取成功',$channelList);
    }

    public function isHasShopList()
    {
        $gatherInfoData = [];
        $gatherUserList = GatherUserInfo::where('id',8)->find();
        if(!empty($gatherUserList['gather_info_json'])){
            $gatherInfoData = json_decode($gatherUserList['gather_info_json'],true);
        }
        return $this->success('获取成功',$gatherInfoData);
    }

    public function zhaiwuLeixingList()
    {
        $gatherInfoData = [];
        $gatherUserList = GatherUserInfo::where('id',18)->find();
        if(!empty($gatherUserList['gather_info_json'])){
            $gatherInfoData = json_decode($gatherUserList['gather_info_json'],true);
        }
        return $this->success('获取成功',$gatherInfoData);
    }

    public function zhaiwuMoneyList()
    {
        $gatherInfoData = [];
        $gatherUserList = GatherUserInfo::where('id',19)->find();
        if(!empty($gatherUserList['gather_info_json'])){
            $gatherInfoData = json_decode($gatherUserList['gather_info_json'],true);
        }
        return $this->success('获取成功',$gatherInfoData);
    }

    //创建线索用户
    public function addUser()
    {
        $loginUserInfo = UserServiceFacade::getUserInfo();
        $post     = CommonFun::filterPostData($this->request->post());
        $validate = new ThreadTransformationValidate();
        if (!$validate->scene('addUser')->check($post)) return $this->error($validate->getError());
        $channelInfo = \app\model\admin\Channel::with(['app'])->where('id',$post['channel_id'])->find();
        $userInfo = UserList::where('phone',$post['phone'])->where('status',1)->where('channel_id',$channelInfo->id)->find();
        if(!empty($userInfo)){
            return $this->error('用户手机号已存在');
        }
        Db::startTrans();
        try {
            $is_test = 0;
            if (strpos($post['nickname'], '测试') !== false || substr($post['phone'],0,2) === '11') {
                $is_test = 1;
            }
            $cityInfo = MobileGetCityinfo::getMobileCityInfo($post['phone']);
            $gatherData = [];
            if(!empty($post['is_has_computer_id'])){
                $gatherData[] = '6='.$post['is_has_computer_id'];
            }
            if(!empty($post['is_like_games'])){
                $gatherData[] = '7='.$post['is_like_games'];
            }
            if(!empty($post['is_has_shop_id'])){
                $gatherData[] = '8='.$post['is_has_shop_id'];
            }
            if(!empty($post['is_has_dspdh_id'])){
                $gatherData[] = '9='.$post['is_has_dspdh_id'];
            }
            if(!empty($post['zhaiwu_leixing'])){
                $gatherData[] = '20='.$post['zhaiwu_leixing'];
            }
            if(!empty($post['zhaiwu_monney'])){
                $gatherData[] = '21='.$post['zhaiwu_monney'];
            }
            $data = [
                'phone' => $post['phone'],
                'nickname' => $post['nickname'],
                'wx_nickname' => $post['wx_nickname'],
                'phone_end_number' => substr($post['phone'],-4),
                'avatar' => 'https://thirdwx.qlogo.cn/mmopen/vi_32/Q3auHgzwzM5m1Qs3zFcia65qHeGlw4Oia540vgEjk1FZhlDHviatk5HC9rh6qT2jmYV5cTGQg13eJ7NAJ9DvqicVjA/132',
                'channel' => $channelInfo['channel_name'],
                'channel_id' => $channelInfo->id,
                'app_id' => $channelInfo->app_id,
                'app_class_id' => $channelInfo->app->app_class_id,
                'province' => $cityInfo['province'] ?? '',
                'city' => $cityInfo['city'] ?? '',
                'age_range_id' => $post['age_range_id'] ?? 2,
                'identity_id' => $post['identify_id'],
                'education_id' => $post['education_id'],
                'sex' => $post['sex'],
                'login_time' => date('Y-m-d H:i:s'),
                'admin_id' => $loginUserInfo['id'],
                'custom_fields' => implode(',',$gatherData),
                'is_test' => $is_test
            ];
            $user = UserList::create($data);
            $post['uid'] = $user->id;
            $userProfile = UserProfile::create($post);
            if (!$user || !$userProfile) throw new \Exception('数据库异常，操作失败');
            Db::commit();
            return $this->success('操作成功',['uid' => $user->id]);
        } catch (\Exception $e) {
            Db::rollback();
            return $this->error('数据库异常，操作失败');
        }
    }

    public function getMessage()
    {
        $post = $this->request->post();
        $validate = new ThreadTransformationValidate();
        if (!$validate->scene('getMessage')->check($post)) return $this->error($validate->getError());
        $threadInfo = Thread::field('id,uid,course_id')
            ->with(['course' => function($query){
                $query->field('id,title');
            }])
            ->where('id',$post['thread_id'])
            ->find();
        if(empty($threadInfo)){
            return $this->error('线索信息不存在');
        }
        if(isset($threadInfo->course->title) && !empty($threadInfo->course->title)){
            $data['content'] = "【旭翱】同学你好，感谢你报名【{$threadInfo->course->title}】课程，我是你的辅导老师，为了更好为你提供服务请前往微信与老师沟通。";
            return $this->success('获取成功',$data);
        }
        return $this->error('获取失败');
    }

    //发送信息
    public function sendMessage()
    {
        $post = $this->request->param();
        $validate = new ThreadTransformationValidate();
        if (!$validate->scene('sendMessage')->check($post)) return $this->error($validate->getError());
        $redis = get_redis();
        $threadInfo = Thread::field('id,uid,course_id')
            ->with(['course' => function($query){
                $query->field('id,title');
            }, 'user' => function($query){
                $query->field('id,phone');
            }])
            ->where('id',$post['thread_id'])
            ->find();
        if(empty($threadInfo)){
            return $this->error('线索信息不存在');
        }
        $redisKey = 'assign_merchant_thread_user_'.$threadInfo->user->phone;
//        if($redis->exists($redisKey)){
//            return $this->error('请勿重复发送');
//        }
        $data = [];
        $marketingSms = new MarketingSms();
        if(isset($threadInfo->course->title) && !empty($threadInfo->course->title) && isset($threadInfo->user->phone) && !empty($threadInfo->user->phone)){
            $data['mobile'] = $threadInfo->user->phone;
            $data['content'] = "【旭翱】同学你好，感谢你报名【{$threadInfo->course->title}】课程，我是你的辅导老师，为了更好为你提供服务请前往微信与老师沟通。";
            $ret = $marketingSms->sendSmsMarketingMessages($data);
            $ret = json_decode($ret,true);
            if($ret['code'] == 0){
                if(!$redis->exists($redisKey)) {
                    $redis->set($redisKey, $data['mobile'], 18000);
                }
                Thread::where('id',$post['thread_id'])->save(['is_sms' => 1]);
                return $this->success('发送成功');
            }else{
                return $this->error('发送失败');
            }
        }
        return $this->error('发送失败');
    }

    //客服APP手动分配商户
    public function assignMerchantDetail()
    {
        $uid = $this->request->post('uid');
        $userInfo = UserList::where('id',$uid)->find()->toArray();
        $gatherInfo = GatherUserInfoModel::getFormatGatherInfo($userInfo['age_range_id'], 'age_range_id');
        $ageRangeOrg = !empty($gatherInfo['name']) ? $gatherInfo['name'] : '';
        $ageRange = !empty($gatherInfo['name']) ? '"' . $gatherInfo['name'] . '"' : '';
        $merchantList = Merchant::where('is_switch', 1)
            ->where('is_source', 2)
            ->where('is_form', 0)
            ->where('is_assign', 1)
            ->where('app_class_id', $userInfo['app_class_id'])
            ->where("age_range_weight_json->'$." . $ageRange . "'", '>', 0)
            ->field('id,is_source,age_range_weight_json,assign_thread_limit_nums')
            ->select()
            ->toArray();
        foreach($merchantList as $key => $item){
            if($item['assign_thread_limit_nums'] > 0){
                $assignThreadNums = Thread::where('merchant_id',$item['id'])->where('is_assign',3)->whereTime('create_time','today')->count();
                if($assignThreadNums > $item['assign_thread_limit_nums']){
                    unset($merchantList[$key]);
                }
            }
        }
        if(empty($merchantList)){
            $loginUserInfo = UserServiceFacade::getUserInfo();
            $merchantIds = explode(',',$loginUserInfo['merchant_ids']);
            $merchantList = Merchant::whereIn('id',$merchantIds)
                ->where('is_assign', 1)
                ->where('app_class_id', $userInfo['app_class_id'])
                ->where("age_range_weight_json->'$." . $ageRange . "'", '>', 0)
                ->field('id,is_source,age_range_weight_json,assign_thread_limit_nums')
                ->select()
                ->toArray();
        }
        if(empty($merchantList)) {
            $merchantList = Merchant::where('is_switch', 1)
                ->where('is_source', 1)
                ->where('is_assign', 1)
                ->where('app_class_id', $userInfo['app_class_id'])
                ->where("age_range_weight_json->'$." . $ageRange . "'", '>', 0)
                ->field('id,is_source,age_range_weight_json,assign_thread_limit_nums')
                ->select()
                ->toArray();
        }
        $data = [];
        $merchantId = 0;
        if (!empty($merchantList)) {
            foreach ($merchantList as $item) {
                $weightArr = isset($item['age_range_weight_json']) && !empty($item['age_range_weight_json']) ? json_decode($item['age_range_weight_json'], true) : [];
                $age_range_weight = isset($weightArr[$ageRangeOrg]) && !empty($weightArr[$ageRangeOrg]) ? $weightArr[$ageRangeOrg] : 0;
                $isApplyMerchant = Thread::where('uid', $userInfo['id'])->where('merchant_id', $item['id'])->count();
                $assignThreadNums = 0;
                if($item['assign_thread_limit_nums'] > 0){
                    $assignThreadNums = Thread::where('merchant_id',$item['id'])->where('is_assign',3)->whereTime('create_time','today')->count();
                }
                if ($age_range_weight > 0 && $isApplyMerchant <= 0 && ($item['assign_thread_limit_nums'] == 0 || $assignThreadNums < $item['assign_thread_limit_nums'])) {
                    $data[] = [
                        'id' => $item['id'],
                        'is_source' => $item['is_source'],
                        'weight' => $age_range_weight,
                    ];
                }
            }
            $merchantId = (new WeightService)->initData($data);
        }
        $info['uid'] = $uid;
        $info['assign']['merchant_id'] = 0;
        $info['assign']['merchant_name'] = '';
        $info['assign']['customer_id'] = 0;
        $info['assign']['customer_name'] = '';
        $info['assign']['customer_qr_code'] = '';
        $info['assign']['customer_openlink_url'] = '';
        $info['is_assign_flag'] = $this->isAssignFlag;
        $merchantInfo = Merchant::where('id', $merchantId)->field('id,merchant_name,app_class_id')->find();
        $courseId = Course::where('merchant_id', $merchantId)->where('course_type',0)->value('id');
        if(!empty($merchantInfo)) {
            $customerId = (new CustomerService)->getCustomerServiceId($merchantId, $info['uid']);
            $customerInfo = Customer::withTrashed()->field('id,nickname,qr_code')->find($customerId);
            $wxMiniConfig = (new WxMiniCusqrcode())->actionSqrcode(getJwtToken($info['uid']), $courseId, $merchantInfo->app_class_id,$userInfo['channel']);
            $openlink = $wxMiniConfig['openlink_url'] ?? '';
            $info['assign']['merchant_id'] = $merchantInfo->id;
            $info['assign']['merchant_name'] = $merchantInfo->merchant_name;
            $info['assign']['customer_id'] = isset($customerInfo->id) ? $customerInfo->id : 0;
            $info['assign']['customer_name'] = isset($customerInfo->nickname) ? $customerInfo->nickname : '';
            $info['assign']['customer_qr_code'] = isset($customerInfo->qr_code) ? $customerInfo->qr_code : '';
            $info['assign']['customer_openlink_url'] = $openlink;
            $info['is_assign_flag'] = true;
        }
        return $this->success('获取成功', $info);
    }

    //分配线索
    public function assignThread()
    {
        $loginUserInfo = UserServiceFacade::getUserInfo();
        $post     = CommonFun::filterPostData($this->request->param());
        $validate = new ThreadTransformationValidate();
        if (!$validate->scene('assignMerchant')->check($post)) return $this->error($validate->getError());
        $userInfo = UserList::where('id',$post['uid'])->find();
        $channelInfo = Channel::where('channel_name',$userInfo->channel)->field('id,source_id')->find();
        if(!empty($userInfo)){
            $courseInfo = Course::where('merchant_id',$post['merchant_id'])->field('id,entry_fee')->find();
            $courseEntryFee = !empty($courseInfo) ? $courseInfo->entry_fee : 0;
            $redis = get_redis();
            $type = $courseEntryFee > 0 ? 2 : 1;
            $redisKey = 'assign_thread_merchant_key_'.$post['merchant_id'].'_type_'.$type.'_source_'.$channelInfo->source_id.'_class_'.$userInfo['app_class_id'];
            $tarMerchantInfo = Merchant::where('id',$post['merchant_id'])->find();
            if(empty($tarMerchantInfo) || $tarMerchantInfo->is_switch == 0){
                return $this->error('分配商户异常或未开启进量');
            }
            $threadCount = $this->model->where('uid',$post['uid'])->where('merchant_id',$post['merchant_id'])->count();
            if($threadCount > 0){
                return $this->error('该用户已报名商户课程');
            }
            $customerInfo = Customer::withTrashed()->find($post['customer_id']);
            if (empty($customerInfo)) {
                return $this->error('分配客服不存在');
            }

            $cityInfo = IpCity::getIpToCity();
            $gatherInfo = GatherUserInfoModel::getFormatGatherInfo($userInfo->age_range_id, 'age_range_id');
            $ageRange = $gatherInfo['name'];
            $weightArr = isset($tarMerchantInfo->age_range_weight_json) && !empty($tarMerchantInfo->age_range_weight_json) ? json_decode($tarMerchantInfo->age_range_weight_json, true) : [];
            $weight = isset($weightArr[$ageRange]) && !empty($weightArr[$ageRange]) ? $weightArr[$ageRange] : 0;
            if($weight <= 0){
                return $this->error('年龄权重不匹配');
            }
            $courseInfo = Course::where('merchant_id',$post['merchant_id'])->field('id,entry_fee')->find();
            $courseId = !empty($courseInfo) ? $courseInfo->id : 0;
            $courseEntryFee = !empty($courseInfo) ? $courseInfo->entry_fee : 0;
            $threadPriceInfo = \app\model\api\Merchant::getMerchantThreadPrice($tarMerchantInfo);

            $redisMerchantKey = env('redis.merchant_amount_redis_v2_key'). $tarMerchantInfo->id;
            if (!$redis->exists($redisMerchantKey)) {
                $redis->set($redisMerchantKey, floatToInt($tarMerchantInfo->residue_amount));
            }
            $threadMemberListRedisKey = env('redis.assign_thread_member_LIST_redis_key');
            $redis->watch($redisMerchantKey);
            $merchantStore = $redis->get($redisMerchantKey);
            $redis->del($threadMemberListRedisKey);

            $threadPrice = $userInfo->is_test == 1 ? 0 : floatToInt($threadPriceInfo['thread_price']);
            if ($merchantStore < $threadPrice) {
                return $this->error('商户余额不足');
            }
            $redis->multi();
            $redis->decrBy($redisMerchantKey, $threadPrice);
            $result = $redis->exec();
            $data = [];
            if($type == 1){
                $threadType = isset($merchantInfo->is_jump_miniprogram) ? ($merchantInfo->is_jump_miniprogram > 0 ? 3 : 1) : 0;
            }else{
                $threadType = isset($merchantInfo->is_jump_miniprogram) ? ($merchantInfo->is_jump_miniprogram > 0 ? 4 : 2) : 0;
            }
            try {
                if($result) {
                    $data['uid'] = $userInfo->id;
                    $data['course_id'] = $courseId;
                    $data['entry_fee'] = $courseEntryFee;
                    $data['merchant_id'] = $tarMerchantInfo->id;
                    $data['customer_id'] = $customerInfo->id;
                    $data['province'] = $cityInfo['province_name'] ?? '';
                    $data['city'] = $cityInfo['city_name'] ?? '';
                    $data['age'] = $ageRange;
                    $data['channel'] = $userInfo->channel;
                    $data['thread_price'] = $threadPriceInfo['thread_price'];
                    $data['thread_price_type'] = $threadPriceInfo['thread_price_type'];
                    $data['channel_id'] = $userInfo['channel_id'];
                    $data['app_id'] = $userInfo['app_id'];
                    $data['app_class_id'] = $userInfo['app_class_id'];
                    $data['thread_type'] = $threadType;
                    $data['is_free_try'] = $tarMerchantInfo->is_free_try;
                    $data['age_id'] = $userInfo->age_range_id;
                    $data['is_test'] = $userInfo->is_test;
                    $data['is_discern_qrcode'] = isset($post['is_discern_qrcode']) ? $post['is_discern_qrcode'] : 1;
                    if($tarMerchantInfo->is_source == 2){
                        //$data['source'] = $channelInfo->source_id;
                        $data['is_assign'] = 3;
                    }
                    $data['assign_mode'] = 1;
                    $data['admin_id'] = $loginUserInfo['id'];
                    $data['merchant_admin_id'] = $tarMerchantInfo->admin_ids;
                    $data['create_time'] = time();
                    $data['update_time'] = time();
                    $retId = Thread::insertGetId($data);
                    $threadInfo = Thread::where('id',$retId)->find();
                    if($retId) {
                        $redis->Incr($redisKey, 1);
                        //更新目标商户
                        Event::trigger('ApplySuccessAfter', ['merchant' => $tarMerchantInfo, 'thread' => ['uid' => $userInfo->id, 'customerId' => $customerInfo->id, 'thread_price' => $threadPriceInfo['thread_price']]]);
                        //自动分配线索
//                        if($tarMerchantInfo->is_source == 2) {
//                            Event::trigger('AssignSuccessAfter', ['orgMerchant' => $tarMerchantInfo, 'tarMerchant' => $tarMerchantInfo, 'thread' => $threadInfo]);
//                        }
                        return $this->success('分配成功');
                    }
                    $redis->incrBy($redisMerchantKey, $threadPrice);
                    return $this->error('分配失败');
                }
            }catch (\Exception $e){
                $redis->incrBy($redisMerchantKey, $threadPrice);
                return $this->error('分配失败：'.$e->getMessage());
            }
        }
    }

    //用户年龄
    public function ageRange()
    {
        return $this->success('获取成功',\app\model\admin\GatherUserInfo::ageRange());
    }

    //身份
    public function identify()
    {
        return $this->success('获取成功',\app\model\admin\GatherUserInfo::identify());
    }

    //学历
    public function education()
    {
        return $this->success('获取成功',\app\model\admin\GatherUserInfo::education());
    }
}