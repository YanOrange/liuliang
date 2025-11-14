<?php

namespace app\controller\admin;

use app\lib\api\exception\Exception;
use app\lib\api\service\CustomerService;
use app\lib\api\service\CustomerServiceV6;
use app\lib\api\service\WeightService;
use app\lib\api\wxmini\WxMiniCusqrcode;
use app\model\admin\Customer;
use app\model\admin\GatherUserInfo;
use app\model\api\Channel;
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
use app\model\Conf;
use app\lib\api\wxmini\WxMiniCusqrcodeV2;

class ThreadTransformation extends Backend
{
    protected $model;//当前模型对象

    protected $isAssignFlag = false;

    protected $noNeedAuth = ['*'];
    protected $noNeedLogin = [''];

    protected function _initialize()
    {
        $this->model = new \app\model\admin\Thread();
    }

    public function index()
    {
        $loginUserInfo = UserServiceFacade::getUserInfo();
        $loginId = $loginUserInfo['id'];
        $roleIds =AuthServiceFacade::getAuthUserRole($loginId);
        $order = $this->buildOrder();
        //$whereCon[] = ['course_id','>',0];

        // 客户权限 chenlele 2022-09-03
        $isCustomer = false;
        $isCustomerLeader = false;
        if ($loginId != 1) {
            // 10 - 客服主管 || 11 - 客服干事
            if (in_array(env('ROLE.CUSTOMERLEADER'), $roleIds) || in_array(env('ROLE.CUSTOMERGANSHI'), $roleIds)) {
                $isCustomer = true;
            }
            if (in_array(env('ROLE.CUSTOMERLEADER'), $roleIds)) {
                $isCustomerLeader = true;
            }
        }

        $whereCon = [];
        $data = $this->buildSearch(false,$whereCon)->with(['user','merchant','customer' => function($query){
            $query->field('id,nickname,wechat_number');
        },'class' => function($query){
            $query->field('id,app_class_name');
        },'admin' => function($query){
            $query->field('id,nickname');
        }])->order('is_assign asc,id desc');
        $allData = $this->request->param('all_data');
        $gatherUserList = $this->gatherUserList();
        if ($allData) {
            $data = $data->select();
        } else {
            $limit = $this->request->param('limit', 10);
            $data = $data->paginate($limit)->each(function($item, $key) use($gatherUserList){
                $customField = [];
                if(!empty($item['user']['custom_fields'])){
                    $customField = explode(',',$item['user']['custom_fields']);
                }
                $item['user']['is_has_computer_id'] = '';
                $item['user']['zhaiwu_leixing'] = '';
                $item['user']['zhaiwu_monney'] = '';
                $item['user']['need_id'] = '';
                $item['user']['cuishou_zhuangtai'] = '';
                foreach($customField as $val){
                    $gatherInfoData = $this->inArrayKey($gatherUserList,$val,'pid_cid_key');
                    if(isset($gatherInfoData[0])) {
                        $item['user'][$gatherInfoData[0]['field']] = isset($gatherInfoData[0]['name']) ? $gatherInfoData[0]['name'] : '';
                    }
                }
                return $item;
            })->toArray();
        }
        foreach($data['data'] as $key => &$val){
            $val['isCustomer'] = $isCustomer;   // chenlele 0903
            $val['isCustomerLeader'] = $isCustomerLeader;
            $val['user']['phone'] = $val['user']['phone'] ? substr_replace($val['user']['phone'], '****', 3, 4) : '';
            $val['is_under_eighteen_thread'] = $loginUserInfo['is_under_eighteen_thread'];
        }
        return $this->success('数据获取成功', $data);
    }

    //管理员关联商户
    public function merchantList()
    {
        $loginUserInfo = UserServiceFacade::getUserInfo();
        $merchantIds = explode(',',$loginUserInfo['merchant_ids']);
        $whereCon[] = ['id','in',$merchantIds];
        $data = Merchant::where($whereCon)->order('id asc');
        $allData = $this->request->param('all_data');
        if ($allData) {
            $data = $data->select();
        } else {
            $limit = $this->request->param('limit', 10);
            $data = $data->paginate($limit)->toArray();
        }
        return $this->success('数据获取成功', $data);
    }

    //分配商户列表
    public function assignMerchantList()
    {
        $threadId = $this->request->param('thread_id');
        $info = $this->model->with(['user' => function($query){
            $query->field('id,phone,nickname,wx_nickname,age_range_id,identity_id,education_id,is_has_computer_id,need_id,cuishou_id,zhaiwu_leixing,zhaiwu_monney,cuishou_zhuangtai');
        }])
            ->findOrEmpty($threadId)
            ->toArray();
        $userIds = UserList::where('phone',$info['user']['phone'])->column('id');
        $entryFee = 0;
        if($info['entry_fee'] > 0){
            $entryFee = 0.01;
            $isAssignRule = Conf::where('id',32)->value('value');
        }else {
            $isAssignRule = Conf::where('id', 31)->value('value');
        }
        $gatherInfo = GatherUserInfoModel::getFormatGatherInfo($info['user']['age_range_id'], 'age_range_id');
        $ageRange = !empty($gatherInfo['name']) ? '"' . $gatherInfo['name'] . '"' : '';
        if($isAssignRule == 1){
            $data = Merchant::alias('mer')
                ->join('course cor','cor.merchant_id = mer.id and cor.entry_fee = '.$entryFee,'inner')
                ->where('is_switch', 1)
                ->where('is_source', 2)
                //->where('is_form', 0)
                ->where('is_assign', 1)
                ->where('app_class_id', $info['app_class_id'])
                ->where("age_range_weight_json->'$." . $ageRange . "'", '>', 0)
                ->field('mer.id,merchant_name,is_jump_miniprogram,assign_thread_limit_nums,manual_assign_ratio,minimum_rate,customer_assign_thread_nums')
                ->order('id asc');
        }else {
            $data = Merchant::where('is_switch', 1)
                ->where('is_source', 2)
                //->where('is_form', 0)
                ->where('is_assign', 1)
                ->where('app_class_id', $info['app_class_id'])
                ->where("age_range_weight_json->'$." . $ageRange . "'", '>', 0)
                ->field('id,merchant_name,is_jump_miniprogram,assign_thread_limit_nums,manual_assign_ratio,minimum_rate,customer_assign_thread_nums')
                ->order('id asc');
        }
        $allData = $this->request->param('all_data');
        if ($allData) {
            $data = $data->select();
        } else {
            $limit = $this->request->param('limit', 100);
            $data = $data->paginate($limit)->toArray();
        }

        if(!empty($data['data'])) {
            foreach ($data['data'] as $key => $val) {
                $customerThreadNums = Thread::where('merchant_id',$val['id'])
                    ->where('is_assign',3)
                    ->where('is_test',0)
                    ->where('create_time')
                    ->count();
                $count = Thread::whereIn('uid', $userIds)->where('merchant_id', $val['id'])->count();
                //当天手动分配线索数
                $todayAssignThreadNums = Thread::where('merchant_id',$val['id'])
                    ->where('is_assign',3)
                    ->whereIn('assign_mode',[1,2,4])
                    ->whereTime('create_time','today')
                    ->count();
                //当天商户App自然进量
                $todayAppThreadNum = Thread::where('merchant_id',$val['id'])
                    ->where('is_assign',0)
                    ->where('is_test',0)
                    ->whereTime('create_time','today')
                    ->count();
                //商户手动分配比值
                $manualAssignRatio = !empty($val['manual_assign_ratio']) ? json_decode($val['manual_assign_ratio'],true) : [3,1];
                //按照分配比值计算商户自然量
                $todayAppThreadNumRate = $manualAssignRatio[0] > 0 && $manualAssignRatio[1] > 0 ? intval($todayAppThreadNum / $manualAssignRatio[0]) * $manualAssignRatio[1] : 0;
//                if ($count > 0 || ($val['assign_thread_limit_nums'] > 0 && $todayAssignThreadNums >= $val['assign_thread_limit_nums']) || ($manualAssignRatio[1] <= 0 || ($manualAssignRatio[0] > 0 && $todayAppThreadNumRate <= $todayAssignThreadNums))) {
//                    unset($data['data'][$key]);
//                }
                //当天商户线索总数
                $todayThreadTotal = Thread::where('merchant_id',$val['id'])
                    ->where('is_test',0)
                    ->where('assign_mode','<',5)
                    ->whereTime('create_time','today')
                    ->count();
                //当天商户线索加V数
                $todayThreadMicroNum = Thread::where('merchant_id',$val['id'])
                    ->where('is_discern_qrcode',1)
                    ->where('is_real_qrcode',1)
                    ->where('is_test',0)
                    ->where('assign_mode','<',5)
                    ->whereTime('create_time','today')
                    ->count();
                //当天商户线索加微率
                $todayThreadMicroRate = $todayThreadTotal > 0 && $todayThreadMicroNum > 0 ? round($todayThreadMicroNum / $todayThreadTotal * 100,2) : 0;
                if($val['is_jump_miniprogram'] == 1) {
                    if ($count <= 0 && ($val['assign_thread_limit_nums'] == 0 || $todayAssignThreadNums < $val['assign_thread_limit_nums']) && (($manualAssignRatio[0] <= 0 && $manualAssignRatio[1] > 0) || $todayAppThreadNumRate > $todayAssignThreadNums) && ($val['minimum_rate'] <= 0 || $todayThreadMicroRate >= $val['minimum_rate']) && ($val['customer_assign_thread_nums'] > 0 && $customerThreadNums < $val['customer_assign_thread_nums'])) {
                        $data['data'][$key] = $val;
                    } else {
                        //unset($data['data'][$key]);
                    }
                }else{
                    if ($count <= 0 && ($val['assign_thread_limit_nums'] == 0 || $todayAssignThreadNums < $val['assign_thread_limit_nums']) && (($manualAssignRatio[0] <= 0 && $manualAssignRatio[1] > 0) || $todayAppThreadNumRate > $todayAssignThreadNums) && ($val['customer_assign_thread_nums'] > 0 && $customerThreadNums < $val['customer_assign_thread_nums'])) {
                        $data['data'][$key] = $val;
                    } else {
                        unset($data['data'][$key]);
                    }
                }
            }
            $data['data'] = array_values($data['data']);
        }
        if($info['app_class_id'] == 10){
            $feiyuMerchantInfo = Merchant::where('id',251)->field('id,merchant_name,is_jump_miniprogram,assign_thread_limit_nums,manual_assign_ratio,minimum_rate')->find()->toArray();
            $data['data'] = array_merge($data['data'],[$feiyuMerchantInfo]);
        }
        return $this->success('数据获取成功', $data);
    }

    //分配补量商户列表
    public function assignSupplementMerchantList($threadId = 0)
    {
        $threadId = !empty($threadId) ? $threadId : $this->request->param('thread_id');
        $info = $this->model->with(['user' => function($query){
            $query->field('id,phone,nickname,wx_nickname,age_range_id,identity_id,education_id,is_has_computer_id,need_id,cuishou_id,zhaiwu_leixing,zhaiwu_monney,cuishou_zhuangtai');
        }])
            ->findOrEmpty($threadId)
            ->toArray();
        $userIds = UserList::where('phone',$info['user']['phone'])->column('id');
        $entryFee = 0;
        if($info['entry_fee'] > 0){
            $entryFee = 0.01;
            $isAssignRule = Conf::where('id',32)->value('value');
        }else {
            $isAssignRule = Conf::where('id', 31)->value('value');
        }
        $gatherInfo = GatherUserInfoModel::getFormatGatherInfo($info['user']['age_range_id'], 'age_range_id');
        $ageRange = !empty($gatherInfo['name']) ? '"' . $gatherInfo['name'] . '"' : '';
        if($isAssignRule == 1){
            $data = Merchant::alias('mer')
                ->join('course cor','cor.merchant_id = mer.id and cor.entry_fee = '.$entryFee,'inner')
                //->where('is_switch', 1)
                ->where('is_source', 2)
                ->where('is_form', 0)
                ->where('is_assign', 1)
                ->where('customer_supplement','>',0)
                ->where('app_class_id', $info['app_class_id'])
                ->where("age_range_weight_json->'$." . $ageRange . "'", '>', 0)
                ->field('mer.id,merchant_name,assign_thread_limit_nums,manual_assign_ratio,minimum_rate,customer_supplement')
                ->order('id asc');
        }else {
            $data = Merchant::where('is_source', 2)
                ->where('is_form', 0)
                ->where('is_assign', 1)
                ->where('customer_supplement','>',0)
                ->where('app_class_id', $info['app_class_id'])
                ->where("age_range_weight_json->'$." . $ageRange . "'", '>', 0)
                ->field('id,merchant_name,assign_thread_limit_nums,manual_assign_ratio,minimum_rate,customer_supplement')
                ->order('id asc');
        }
        $allData = $this->request->param('all_data');
        if ($allData) {
            $data = $data->select();
        } else {
            $limit = $this->request->param('limit', 100);
            $data = $data->paginate($limit)->toArray();
        }
        if(!empty($data['data'])) {
            foreach ($data['data'] as $key => $val) {
                $count = Thread::whereIn('uid', $userIds)->where('merchant_id', $val['id'])->whereDay('create_time')->count();
                //当天手动分配线索数
                $todayAssignThreadNums = Thread::where('merchant_id',$val['id'])
                    ->where('is_assign',3)
                    ->whereIn('assign_mode',5)
                    ->where('admin_id','>',0)
                    ->whereTime('create_time','today')
                    ->count();
                if ($count <= 0 && ($val['customer_supplement'] > 0 && $val['customer_supplement'] > $todayAssignThreadNums)) {
                    $data['data'][$key] = $val;
                }else{
                    unset($data['data'][$key]);
                }
            }
            $data['data'] = array_values($data['data']);
            if(!empty($data['data'])) $this->isAssignFlag = true;
        }
        return $this->success('数据获取成功', $data);
    }

    public function getMerchantCustomer()
    {
        $merchantId = $this->request->param('merchant_id');
        $uId = $this->request->param('uid');
        $channel = UserList::where('id',$uId)->value('channel');
        $data['merchant_id'] = 0;
        $data['merchant_name'] = '';
        $data['customer_id'] = 0;
        $data['customer_name'] = '';
        $data['customer_qr_code'] = '';
        $data['customer_openlink_url'] = '';
        $data['qywx_customer_openlink_url'] = '';
        $merchantInfo = Merchant::where('id', $merchantId)->field('id,merchant_name,app_class_id,customer_assign_thread_nums')->find();
        $courseId = Course::where('merchant_id', $merchantId)->where('course_type',0)->value('id');
        $customerThreadNums = Thread::where('merchant_id',$merchantId)
            ->where('is_assign',3)
            ->where('is_test',0)
            ->where('create_time')
            ->count();
        if(!empty($merchantInfo)) {
            if($merchantId == 177 || $merchantId == 251){
                if($customerThreadNums >= $merchantInfo->customer_assign_thread_nums){
                    $customerId = (new CustomerServiceV6)->assignCustomerServiceData($merchantId);
                }else{
                    $customerId = (new CustomerServiceV6)->getCustomerServiceId($merchantId, $uId);
                }
            }else{
                $customerId = (new CustomerService)->getCustomerServiceId($merchantId, $uId);
            }
            $customerInfo = Customer::withTrashed()->field('id,nickname,qr_code,customer_link')->find($customerId);
            $h5UrlType = env('CUSTOMER.H5URL_TYPE');
            if($h5UrlType == 1){
                $openlink = (new WxMiniCusqrcodeV2())->getH5ShortUrl(getJwtToken($uId), $courseId);
            }else{
                $wxMiniConfig = (new WxMiniCusqrcode())->actionSqrcode(getJwtToken($uId), $courseId, $merchantInfo->app_class_id,$channel);
                $openlink = $wxMiniConfig['openlink_url'] ?? '';
            }
            $data['merchant_id'] = $merchantInfo->id;
            $data['merchant_name'] = $merchantInfo->merchant_name;
            $data['customer_id'] = isset($customerInfo->id) ? $customerInfo->id : 0;
            $data['customer_name'] = isset($customerInfo->nickname) ? $customerInfo->nickname : '';
            $data['customer_qr_code'] = isset($customerInfo->qr_code) ? $customerInfo->qr_code : '';
            $data['customer_openlink_url'] = $openlink;
            $data['qywx_customer_openlink_url'] = $customerInfo->customer_link ?? '';
        }
        return $this->success('数据获取成功', $data);
    }

    // 构建查询条件
    private function buildSearch($isDelete = false, $whereCon = [])
    {
        $loginUserInfo = UserServiceFacade::getUserInfo();
        $roleIds =AuthServiceFacade::getAuthUserRole($loginUserInfo['id']);
        $filter = $this->request->param('search_param');
        $filter = !empty($filter) ? $filter : [];
        $whereCon = !empty($whereCon) ? $whereCon : [];
        extract($filter);
        extract($whereCon);
        $whereCon1 = [];
        $merchantIds = explode(',',$loginUserInfo['merchant_ids']);
        $name = $this->model->getName();
        $tableName = env('database.prefix') . $name;
        if($loginUserInfo['id'] != 1 && !in_array(13,$roleIds)){
            $whereCon1[] = [$tableName.'.merchant_id','in',$merchantIds];
        }
        if ($isDelete) {
            $threadModel = $this->model->onlyTrashed();
        } else {
            $threadModel = $this->model;
        }
        if (!empty($whereCon))
        {
            $threadModel = $threadModel->where($whereCon);
        }
        $field = ['id','merchant_id','course_id','uid','customer_id','app_class_id','admin_id','is_assign','thread_type','create_time'];
        $threadModel = $threadModel->field($field)->withJoin(['user' => function($query){
            $query->withField(['id','phone','nickname','wx_nickname','avatar','age_range_id','identity_id','education_id']);
        },'merchant' => function($query){
            $query->withField(['id','merchant_name']);
        }],'inner');
        $threadModel = $threadModel->where('merchant.is_source', 1);
        if (isset($is_assign) && !empty($is_assign)) {
            if($is_assign == 1){
                $threadModel = $threadModel->where($tableName.'.is_assign', 1)->where($whereCon1);
            }
            if($is_assign == 2){
                $threadModel = $threadModel->where($tableName.'.is_assign', 0)->where($whereCon1);
            }
            if($is_assign == 3){
                if($loginUserInfo['id'] != 1 && !in_array(13,$roleIds)){
                    $threadModel = $threadModel->where($tableName.'.is_assign', 3)->where($tableName.'.admin_id',$loginUserInfo['id']);
                }else{
                    $threadModel = $threadModel->where($tableName.'.is_assign', 3);
                }
            }
        }else{
            $threadModel = $threadModel->whereIn($tableName.'.is_assign', [0,1])->where($whereCon1);
        }
        if (isset($thread_tag_ids) && !empty($thread_tag_ids)) {
            $threadModel = $threadModel->whereFindInSet($tableName.'.thread_tag_ids', $thread_tag_ids);
        }
        if (isset($merchant_id) && !empty($merchant_id)) {
            $merchant_id = explode(',',$merchant_id);
            $threadModel = $threadModel->where($tableName.'.merchant_id', 'in', $merchant_id);
        }
        if (isset($course_id) && !empty($course_id)) {
            $threadModel = $threadModel->where($tableName.'.course_id', '=', $course_id);
        }
        if (isset($customer_id) && !empty($customer_id)) {
            $threadModel = $threadModel->where($tableName.'.customer_id', '=', $customer_id);
        }
        if (isset($channel_id) && !empty($channel_id)) {
            $threadModel = $threadModel->where($tableName.'.channel_id', '=', $channel_id);
        }
        if (isset($app_id) && !empty($app_id)) {
            $threadModel = $threadModel->where($tableName.'.app_id', '=', $app_id);
        }
        if (isset($app_class_id) && !empty($app_class_id)) {
            $threadModel = $threadModel->where($tableName.'.app_class_id', '=', $app_class_id);
        }
        if (isset($is_discern_qrcode) && is_numeric($is_discern_qrcode)) {
            $threadModel = $threadModel->where($tableName.'.is_discern_qrcode', '=', $is_discern_qrcode);
        }
        if (isset($thread_type) && is_numeric($thread_type)) {
            $threadModel = $threadModel->where($tableName.'.thread_type', '=', $thread_type);
        }

        if($loginUserInfo['is_under_eighteen_thread'] == 1) {
            $threadModel = $threadModel->where($tableName.'age', '=', '未满18岁');
        }else{
            $threadModel = $threadModel->where($tableName.'.age', '<>', '未满18岁');
        }
        if (isset($age_range) && !empty($age_range)) {
            $threadModel = $threadModel->where($tableName.'.age', '=', $age_range);
        }

        $threadModel = $threadModel->where($tableName.'.is_test', '=', 0);
        if (isset($phone) && !empty($phone)) {
            $threadModel = $threadModel->where('user.phone', '=', $phone);
        }
        if (isset($phone_end_number) && !empty($phone_end_number)) {
            $threadModel = $threadModel->where('user.phone_end_number', '=', $phone_end_number);
        }
        if (isset($user_nickname) && !empty($user_nickname)) {
            $threadModel = $threadModel->where('user.nickname', '=', $user_nickname);
        }
        if (isset($user_wx_nickname) && !empty($user_wx_nickname)) {
            $threadModel = $threadModel->where('user.wx_nickname', '=', $user_wx_nickname);
        }
//        if (isset($age_range_id) && !empty($age_range_id)) {
//            $threadModel = $threadModel->where('user.age_range_id', '=', $age_range_id);
//        }
        if (isset($identity_id) && !empty($identity_id)) {
            $threadModel = $threadModel->where('user.identity_id', '=', $identity_id);
        }
        if (isset($education_id) && !empty($education_id)) {
            $threadModel = $threadModel->where('user.education_id', '=', $education_id);
        }
        if (isset($is_has_computer_id) && !empty($is_has_computer_id)) {
            $threadModel = $threadModel->where('user.is_has_computer_id', '=', $is_has_computer_id);
        }
        if (isset($cus_wechat_number) && !empty($cus_wechat_number)) {
            $customerId = Customer::where('wechat_number',$cus_wechat_number)->value('id');
            $threadModel = $threadModel->where($tableName.'.customer_id','=',$customerId);
        }
        if (isset($create_time) && !empty($create_time)) {
            list($startTime, $endTime) = explode(' - ', $create_time);
            $threadModel = $threadModel->where($tableName.'.create_time', 'between', strtotime($startTime). ','. strtotime($endTime));
        }
        return $threadModel;
    }

    public function gatherUserList()
    {
        $gatherUserArr = [];
        $gatherUserAll = [];
        $gatherUserList = GatherUserInfo::select()->toArray();
        if(!empty($gatherUserList)) {
            foreach ($gatherUserList as $item){
                $gatherInfoJson = json_decode($item['gather_info_json'],true);
                $gatherInfoData = [];
                foreach($gatherInfoJson as $val){
                    $gatherInfoData[] = [
                        'id' => $item['id'],
                        'field' => $item['field'],
                        'pid_cid_key' => $item['id'].'='.$val['id'],
                        'name' => $val['name']
                    ];
                }
                $gatherUserArr[] = $gatherInfoData;
            }
            foreach($gatherUserArr as $item){
                foreach($item as $val){
                    $gatherUserAll[] = $val;
                }
            }
        }
        return $gatherUserAll;
    }

    public function inArrayKey($array, $inarray, $field){
        if(!is_array($inarray)){
            $inarray = explode(',', $inarray);
        }
        $arr = [];
        foreach($array as $key=>$value){
            if(in_array($value[$field], $inarray)){
                $arr[] = $value;
            }
        }
        return $arr;
    }

    //自动分配详情（一对一）
    public function assignDetailOne()
    {
        $id  = $this->request->param('id');
        $assignMode  = $this->request->param('assign_mode');
        if($assignMode == 5) $this->assignSupplementMerchantList($id);
        $info = $this->model->with(['merchant' => function($query){
            $query->field('id,merchant_name');
        },
            'course' => function($query){
                $query->field('id,title');
            },
            'user' => function($query){
                $query->field('id,phone,avatar,nickname,wx_nickname,age_range_id,identity_id,education_id,is_has_computer_id,need_id,cuishou_id,zhaiwu_leixing,zhaiwu_monney,cuishou_zhuangtai,channel');
            },
            'app' => function($query){
                $query->field('id,app_name');
            },
            'class' => function($query){
                $query->field('id,app_class_name');
            }])->findOrEmpty($id)->toArray();
        $info['user']['phone'] = isset($info['user']['phone']) && !empty($info['user']['phone']) ? substr_replace($info['user']['phone'], '****', 3, 4) : '';
        if(!empty($info)) {
            $userIds = UserList::where('phone',$info['user']['phone'])->column('id');
            if($info['thread_type'] == 1){
                $info['thread_type'] = '0元纯表单';
            }
            if($info['thread_type'] == 2){
                $info['thread_type'] = '1分纯表单';
            }
            if($info['thread_type'] == 3){
                $info['thread_type'] = '0元加微信';
            }
            if($info['thread_type'] == 4){
                $info['thread_type'] = '1分加微信';
            }
            $info['assign']['merchant_id'] = 0;
            $info['assign']['merchant_name'] = '';
            $info['assign']['customer_id'] = 0;
            $info['assign']['customer_name'] = '';
            $info['assign']['customer_qr_code'] = '';
            $info['assign']['customer_openlink_url'] = '';
            $info['assign']['qywx_customer_openlink_url'] = '';
            $info['is_assign_flag'] = $this->isAssignFlag;
            if($info['is_assign'] == 3) {
                $merchantInfo = Merchant::where('id',$info['merchant_id'])->field('id,merchant_name,app_class_id')->find();
                $customerInfo = Customer::withTrashed()->field('id,nickname,qr_code')->find($info['customer_id']);
                $adminInfo = User::where('id',$info['admin_id'])->field('nickname')->find();
                $info['assign']['merchant_id'] = $merchantInfo->id;
                $info['assign']['merchant_name'] = $merchantInfo->merchant_name;
                $info['assign']['customer_id'] = $customerInfo->id;
                $info['assign']['customer_name'] = $customerInfo->nickname;
                $info['assign']['customer_qr_code'] = $customerInfo->qr_code;
                $info['assign']['admin_username'] = isset($adminInfo->nickname) ? $adminInfo->nickname : '自动分配';
            }
            if($info['is_assign'] == 0 || $info['is_assign'] == 1) {
                $gatherInfo = GatherUserInfoModel::getFormatGatherInfo($info['user']['age_range_id'], 'age_range_id');
                $ageRangeOrg = !empty($gatherInfo['name']) ? $gatherInfo['name'] : '';
                $ageRange = !empty($gatherInfo['name']) ? '"' . $gatherInfo['name'] . '"' : '';
                $entryFee = 0;
                if($info['entry_fee'] > 0){
                    $entryFee = 0.01;
                    $isAssignRule = Conf::where('id',32)->value('value');
                }else {
                    $isAssignRule = Conf::where('id', 31)->value('value');
                }
                if($isAssignRule > 0){
                    $merchantList = Merchant::alias('mer')
                        ->join('course cor','cor.merchant_id = mer.id and cor.entry_fee = '.$entryFee,'inner')
                        ->where('is_switch', 1)
                        ->where('is_source', 2)
                        ->where('is_form', 0)
                        ->where('is_assign', 1)
                        ->where('app_class_id', $info['app_class_id'])
                        ->where("age_range_weight_json->'$." . $ageRange . "'", '>', 0)
                        ->field('mer.id,is_source,is_jump_miniprogram,age_range_weight_json,assign_thread_limit_nums,manual_assign_ratio,minimum_rate')
                        ->select()
                        ->toArray();
                }else{
                    $merchantList = Merchant::where('is_switch', 1)
                        ->where('is_source', 2)
                        ->where('is_form', 0)
                        ->where('is_assign', 1)
                        ->where('app_class_id', $info['app_class_id'])
                        ->where("age_range_weight_json->'$." . $ageRange . "'", '>', 0)
                        ->field('id,is_source,is_jump_miniprogram,age_range_weight_json,assign_thread_limit_nums,manual_assign_ratio,minimum_rate')
                        ->select()
                        ->toArray();
                }
                $data = [];
                $merchantId = 0;
                if (!empty($merchantList)) {
                    foreach ($merchantList as $item) {
                        $weightArr = isset($item['age_range_weight_json']) && !empty($item['age_range_weight_json']) ? json_decode($item['age_range_weight_json'], true) : [];
                        $age_range_weight = isset($weightArr[$ageRangeOrg]) && !empty($weightArr[$ageRangeOrg]) ? $weightArr[$ageRangeOrg] : 0;
                        $isApplyMerchant = Thread::whereIn('uid', $userIds)->where('merchant_id', $item['id'])->count();
//                        if($item['assign_thread_limit_nums'] > 0){
//                            $assignThreadNums = Thread::where('merchant_id',$item['id'])->where('is_assign',3)->whereIn('assign_mode',[1,2,4])->whereTime('create_time','today')->count();
//                        }
                        //当天客服分配线索数量
                        $assignThreadNums = Thread::where('merchant_id',$item['id'])->where('is_assign',3)->whereIn('assign_mode',[1,2,4])->whereTime('create_time','today')->count();
                        //当天商户App自然进量
                        $todayAppThreadNum = Thread::where('merchant_id',$item['id'])
                            ->where('is_assign',0)
                            ->where('is_test',0)
                            ->whereTime('create_time','today')
                            ->count();
                        //商户手动分配比值
                        $manualAssignRatio = !empty($item['manual_assign_ratio']) ? json_decode($item['manual_assign_ratio'],true) : [3,1];
                        //按照分配比值计算商户自然量
                        $todayAppThreadNumRate = $manualAssignRatio[0] > 0 && $manualAssignRatio[1] > 0 ? intval($todayAppThreadNum / $manualAssignRatio[0]) * $manualAssignRatio[1] : 0;
                        //当天商户线索总数
                        $todayThreadTotal = Thread::where('merchant_id',$item['id'])
                            ->where('is_test',0)
                            ->where('assign_mode','<',5)
                            ->whereTime('create_time','today')
                            ->count();
                        //当天商户线索加V数
                        $todayThreadMicroNum = Thread::where('merchant_id',$item['id'])
                            ->where('is_discern_qrcode',1)
                            ->where('is_test',0)
                            ->where('assign_mode','<',5)
                            ->whereTime('create_time','today')
                            ->count();
                        //当天商户线索加微率
                        $todayThreadMicroRate = $todayThreadTotal > 0 && $todayThreadMicroNum > 0 ? round($todayThreadMicroNum / $todayThreadTotal * 100,2) : 0;
                        if($item['is_jump_miniprogram'] == 1) {
                            if ($age_range_weight > 0 && $isApplyMerchant <= 0 && ($item['assign_thread_limit_nums'] == 0 || $assignThreadNums < $item['assign_thread_limit_nums']) && (($manualAssignRatio[0] <= 0 && $manualAssignRatio[1] > 0) || $todayAppThreadNumRate > $assignThreadNums) && ($item['minimum_rate'] <= 0 || $todayThreadMicroRate >= $item['minimum_rate'])) {
                                $data[] = [
                                    'id' => $item['id'],
                                    'is_source' => $item['is_source'],
                                    'weight' => $age_range_weight,
                                ];
                            }
                        }else{
                            if ($age_range_weight > 0 && $isApplyMerchant <= 0 && ($item['assign_thread_limit_nums'] == 0 || $assignThreadNums < $item['assign_thread_limit_nums']) && (($manualAssignRatio[0] <= 0 && $manualAssignRatio[1] > 0) || $todayAppThreadNumRate > $assignThreadNums)) {
                                $data[] = [
                                    'id' => $item['id'],
                                    'is_source' => $item['is_source'],
                                    'weight' => $age_range_weight,
                                ];
                            }
                        }
                    }
                    $merchantId = (new WeightService)->initData($data);
                }
                $merchantInfo = Merchant::where('id', $merchantId)->field('id,merchant_name,app_class_id')->find();
                if(!empty($merchantInfo)) {
                    $customerId = (new CustomerService)->getCustomerServiceId($merchantId, $info['uid']);
                    $customerInfo = Customer::withTrashed()->field('id,nickname,qr_code,customer_link')->find($customerId);
                    $courseId = Course::where('merchant_id', $merchantId)->where('course_type',0)->value('id');
                    $h5UrlType = env('CUSTOMER.H5URL_TYPE');
                    if($h5UrlType == 1){
                        $openlink = (new WxMiniCusqrcodeV2())->getH5ShortUrl(getJwtToken($info['uid']), $courseId);
                    }else{
                        $wxMiniConfig = (new WxMiniCusqrcode())->actionSqrcode(getJwtToken($info['uid']), $courseId, $merchantInfo->app_class_id,$info['user']['channel']);
                        $openlink = $wxMiniConfig['openlink_url'] ?? '';
                    }
                    $info['assign']['merchant_id'] = $merchantInfo->id;
                    $info['assign']['merchant_name'] = $merchantInfo->merchant_name;
                    $info['assign']['customer_id'] = isset($customerInfo->id) ? $customerInfo->id : 0;
                    $info['assign']['customer_name'] = isset($customerInfo->nickname) ? $customerInfo->nickname : '';
                    $info['assign']['customer_qr_code'] = isset($customerInfo->qr_code) ? $customerInfo->qr_code : '';
                    $info['assign']['customer_openlink_url'] = $openlink;
                    $info['assign']['qywx_customer_openlink_url'] = $customerInfo->customer_link ?? '';
                    $info['is_assign_flag'] = true;
                }
            }
            if($info['app_class_id'] == 10){
                $info['is_assign_flag'] = true;
            }
        }
        return $this->success('获取成功', $info);
    }

    //自动分配详情（一对多）
    public function assignDetailMore()
    {
        $id  = $this->request->param('id');
        $info = $this->model->with(['merchant' => function($query){
            $query->field('id,merchant_name');
        },
            'course' => function($query){
                $query->field('id,title');
            },
            'user' => function($query){
                $query->field('id,phone,avatar,nickname,wx_nickname,age_range_id,identity_id,education_id,is_has_computer_id,need_id,cuishou_id,zhaiwu_leixing,zhaiwu_monney,cuishou_zhuangtai,channel');
            },
            'app' => function($query){
                $query->field('id,app_name');
            },
            'class' => function($query){
                $query->field('id,app_class_name,assign_merchant_num');
            }])->findOrEmpty($id)->toArray();
        $info['user']['phone'] = isset($info['user']['phone']) && !empty($info['user']['phone']) ? substr_replace($info['user']['phone'], '****', 3, 4) : '';
        if(!empty($info)) {
            $userIds = UserList::where('phone',$info['user']['phone'])->column('id');
            if($info['thread_type'] == 1){
                $info['thread_type'] = '0元纯表单';
            }
            if($info['thread_type'] == 2){
                $info['thread_type'] = '1分纯表单';
            }
            if($info['thread_type'] == 3){
                $info['thread_type'] = '0元加微信';
            }
            if($info['thread_type'] == 4){
                $info['thread_type'] = '1分加微信';
            }
            $info['assign'] = [];
            $info['is_assign_flag'] = false;

            if($info['is_assign'] == 3) {
                $merchantInfo = Merchant::where('id',$info['merchant_id'])->field('id,merchant_name')->find();
                $customerInfo = Customer::withTrashed()->field('id,nickname,qr_code')->find($info['customer_id']);
                $adminInfo = User::where('id',$info['admin_id'])->field('nickname')->find();
                $info['assign']['merchant_id'] = $merchantInfo->id;
                $info['assign']['merchant_name'] = $merchantInfo->merchant_name;
                $info['assign']['customer_id'] = $customerInfo->id;
                $info['assign']['customer_name'] = $customerInfo->nickname;
                $info['assign']['customer_qr_code'] = $customerInfo->qr_code;
                $info['assign']['admin_username'] = isset($adminInfo->nickname) ? $adminInfo->nickname : '自动分配';
            }
            if($info['is_assign'] == 0 || $info['is_assign'] == 1) {
                $gatherInfo = GatherUserInfoModel::getFormatGatherInfo($info['user']['age_range_id'], 'age_range_id');
                $ageRangeOrg = !empty($gatherInfo['name']) ? $gatherInfo['name'] : '';
                $ageRange = !empty($gatherInfo['name']) ? '"' . $gatherInfo['name'] . '"' : '';
                $entryFee = 0;
                if($info['entry_fee'] > 0){
                    $entryFee = 0.01;
                    $isAssignRule = Conf::where('id',32)->value('value');
                }else {
                    $isAssignRule = Conf::where('id', 31)->value('value');
                }
                if($isAssignRule > 0){
                    $merchantList = Merchant::alias('mer')
                        ->join('course cor','cor.merchant_id = mer.id and cor.entry_fee = '.$entryFee,'inner')
                        ->where('is_switch', 1)
                        ->where('is_source', 2)
                        ->where('is_form', 0)
                        ->where('is_assign', 1)
                        ->where('app_class_id', $info['app_class_id'])
                        ->where('assign_upper_limit_rate ', '>',0)
                        ->where("age_range_weight_json->'$." . $ageRange . "'", '>', 0)
                        ->field('mer.id,is_source,is_jump_miniprogram,age_range_weight_json,totay_thread_limit_nums,assign_thread_limit_nums,assign_upper_limit_rate,manual_assign_ratio,minimum_rate')
                        ->select()
                        ->toArray();
                }else {
                    $merchantList = Merchant::where('is_switch', 1)
                        ->where('is_source', 2)
                        ->where('is_form', 0)
                        ->where('is_assign', 1)
                        ->where('app_class_id', $info['app_class_id'])
                        ->where('assign_upper_limit_rate ', '>', 0)
                        ->where("age_range_weight_json->'$." . $ageRange . "'", '>', 0)
                        ->field('id,is_source,is_jump_miniprogram,age_range_weight_json,totay_thread_limit_nums,assign_thread_limit_nums,assign_upper_limit_rate,manual_assign_ratio,minimum_rate')
                        ->select()
                        ->toArray();
                }
                $data = [];
                if (!empty($merchantList)) {
                    foreach ($merchantList as $item) {
                        $weightArr = isset($item['age_range_weight_json']) && !empty($item['age_range_weight_json']) ? json_decode($item['age_range_weight_json'], true) : [];
                        $age_range_weight = isset($weightArr[$ageRangeOrg]) && !empty($weightArr[$ageRangeOrg]) ? $weightArr[$ageRangeOrg] : 0;
                        $isApplyMerchant = Thread::whereIn('uid', $userIds)->where('merchant_id', $item['id'])->count();
                        //当天客服手动分配线索数量
                        $todayAssignThreadNums = Thread::where('merchant_id',$item['id'])
                            ->where('is_assign',3)
                            ->whereIn('assign_mode',[1,2,4])
                            ->whereTime('create_time','today')
                            ->count();
                        //当天自动分配线索数量（一对多）
                        $manualAssignThreadNums = Thread::where('merchant_id',$item['id'])
                            ->where('is_assign',3)
                            ->where('assign_mode',4)
                            ->whereTime('create_time','today')
                            ->count();
                        //当天需求分配线索数 商户分配线索上限比率
                        $todayThreadDemandNum = intval($item['totay_thread_limit_nums'] * ($item['assign_upper_limit_rate'] / 100));

                        //当天商户App自然进量
                        $todayAppThreadNum = Thread::where('merchant_id',$item['id'])
                            ->where('is_assign',0)
                            ->where('is_test',0)
                            ->whereTime('create_time','today')
                            ->count();
                        //商户手动分配比值
                        $manualAssignRatio = !empty($item['manual_assign_ratio']) ? json_decode($item['manual_assign_ratio'],true) : [3,1];
                        //按照分配比值计算商户自然量
                        $todayAppThreadNumRate = $manualAssignRatio[0] > 0 && $manualAssignRatio[1] > 0 ? intval($todayAppThreadNum / $manualAssignRatio[0]) * $manualAssignRatio[1] : 0;
                        //当天商户线索总数
                        $todayThreadTotal = Thread::where('merchant_id',$item['id'])
                            ->where('is_test',0)
                            ->where('assign_mode','<',5)
                            ->whereTime('create_time','today')
                            ->count();
                        //当天商户线索加V数
                        $todayThreadMicroNum = Thread::where('merchant_id',$item['id'])
                            ->where('is_discern_qrcode',1)
                            ->where('is_test',0)
                            ->where('assign_mode','<',5)
                            ->whereTime('create_time','today')
                            ->count();
                        //当天商户线索加微率
                        $todayThreadMicroRate = $todayThreadTotal > 0 && $todayThreadMicroNum > 0 ? round($todayThreadMicroNum / $todayThreadTotal * 100,2) : 0;
                        if($item['is_jump_miniprogram'] == 1) {
                            if ($age_range_weight > 0 && $isApplyMerchant <= 0 && ($item['assign_thread_limit_nums'] == 0 || $todayAssignThreadNums < $item['assign_thread_limit_nums']) && $todayThreadDemandNum > $manualAssignThreadNums && (($manualAssignRatio[0] <= 0 && $manualAssignRatio[1] > 0) || $todayAppThreadNumRate > $todayAssignThreadNums) && ($item['minimum_rate'] <= 0 || $todayThreadMicroRate >= $item['minimum_rate'])) {
                                $weight = $item['totay_thread_limit_nums'] > 0 && $item['totay_thread_limit_nums'] / 100 > 1 ? intval($item['totay_thread_limit_nums'] / 100) : 1;
                                $data[] = [
                                    'id' => $item['id'],
                                    'is_source' => $item['is_source'],
                                    'weight' => $weight,
                                ];
                            }
                        }else{
                            if ($age_range_weight > 0 && $isApplyMerchant <= 0 && ($item['assign_thread_limit_nums'] == 0 || $todayAssignThreadNums < $item['assign_thread_limit_nums']) && $todayThreadDemandNum > $manualAssignThreadNums && (($manualAssignRatio[0] <= 0 && $manualAssignRatio[1] > 0) || $todayAppThreadNumRate > $todayAssignThreadNums)) {
                                $weight = $item['totay_thread_limit_nums'] > 0 && $item['totay_thread_limit_nums'] / 100 > 1 ? intval($item['totay_thread_limit_nums'] / 100) : 1;
                                $data[] = [
                                    'id' => $item['id'],
                                    'is_source' => $item['is_source'],
                                    'weight' => $weight,
                                ];
                            }
                        }
                    }
                    $merchantIds = [];
                    $merchantId = 0;
                    $num = count($data) >= $info['class']['assign_merchant_num'] ? $info['class']['assign_merchant_num'] : count($data);
                    for($i=0;$i<$num;$i++){
                        if ($i == 0) {
                            $merchantId = (new WeightService)->initData($data);
                        } else{
                            $data = self::delByValue($data,$merchantId);
                            $merchantId = (new WeightService)->initData($data);
                        }
                        $merchantIds[] = $merchantId;
                    }
                    $merchantList = Merchant::whereIn('id',$merchantIds)->field('id,merchant_name,app_class_id')->select()->toArray();
                }
                foreach($merchantList as $merchantInfo) {
                    if (!empty($merchantInfo)) {
                        $customerId = (new CustomerService)->getCustomerServiceId($merchantInfo['id'], $info['uid']);
                        $customerInfo = Customer::withTrashed()->field('id,nickname,qr_code,customer_link')->find($customerId);
                        $courseId = Course::where('merchant_id', $merchantInfo['id'])->where('course_type',0)->value('id');
                        $h5UrlType = env('CUSTOMER.H5URL_TYPE');
                        if($h5UrlType == 1){
                            $openlink = (new WxMiniCusqrcodeV2())->getH5ShortUrl(getJwtToken($info['uid']), $courseId);
                        }else{
                            $wxMiniConfig = (new WxMiniCusqrcode())->actionSqrcode(getJwtToken($info['uid']), $courseId, $merchantInfo['app_class_id'],$info['user']['channel']);
                            $openlink = $wxMiniConfig['openlink_url'] ?? '';
                        }
                        $info['assign'][] = [
                            'merchant_id' => $merchantInfo['id'],
                            'merchant_name' => $merchantInfo['merchant_name'],
                            'customer_id' => isset($customerInfo->id) ? $customerInfo->id : 0,
                            'customer_name' => isset($customerInfo->nickname) ? $customerInfo->nickname : '',
                            'customer_qr_code' => isset($customerInfo->qr_code) ? $customerInfo->qr_code : '',
                            'customer_openlink_url' => $openlink,
                            'qywx_customer_openlink_url' => $customerInfo->customer_link ?? '',
                        ];
                    }
                    $info['is_assign_flag'] = true;
                }
            }
        }
        return $this->success('获取成功', $info);
    }

    public function delByValue($arr,$value)
    {
        if(!is_array($arr)){
            return $arr;
        }
        foreach($arr as $k => $v){
            if($v['id'] == $value){
                unset($arr[$k]);
            }
        }
        return $arr;
    }

    //分配线索
    public function assignThread()
    {
        $loginUserInfo = UserServiceFacade::getUserInfo();
        $post     = CommonFun::filterPostData($this->request->param());
        if($post['is_submit'] != 1){
            return $this->error('请先生成二维码');
        }
        $validate = new ThreadTransformationValidate();
        if (!$validate->scene('assign')->check($post)) return $this->error($validate->getError());
        $thread = $this->model->where('id',$post['thread_id'])->find();
        if(!empty($thread)){
            if($thread->is_origin == 2 && empty($post['wx_nickname'])){
                return $this->error('用户昵称不能为空');
            }
            $courseInfo = Course::where('merchant_id',$post['merchant_id'])->where('course_type',0)->field('id,entry_fee')->find();
            $courseId = !empty($courseInfo) ? $courseInfo->id : 0;
            $courseEntryFee = !empty($courseInfo) ? $courseInfo->entry_fee : 0;
            $redis = get_redis();
            $type = $courseEntryFee > 0 ? 2 : 1;
            $channelInfo = Channel::where('id',$thread->channel_id)->field('id,source_id')->find();
            $redisKey = 'assign_thread_merchant_key_'.$post['merchant_id'].'_type_'.$type.'_source_'.$channelInfo->source_id.'_class_'.$thread->app_class_id;
            $orgMerchantInfo = Merchant::where('id',$thread['merchant_id'])->find();
            if(empty($orgMerchantInfo) || $orgMerchantInfo->is_source == 2){
                return $this->error('原商户不符合分配规则');
            }
            $tarMerchantInfo = Merchant::where('id',$post['merchant_id'])->find();
            if (isset($post['assign_mode']) && $post['assign_mode'] != 5) {
                /*if(empty($tarMerchantInfo) || $tarMerchantInfo->is_switch == 0){
                    return $this->error('分配商户异常或未开启进量');
                }*/
            }
            $threadCount = $this->model->where('uid',$thread['uid'])->where('merchant_id',$post['merchant_id'])->count();
            if($threadCount > 0){
                return $this->error('该用户已报名商户课程');
            }

            $customerInfo = Customer::withTrashed()->find($post['customer_id']);
            if($tarMerchantInfo->is_jump_miniprogram == 1) {
                if (empty($customerInfo)) {
                    return $this->error('分配客服不存在');
                }
            }

            $userInfo = UserList::find($thread['uid']);
            $userIds = UserList::where('phone',$userInfo->phone)->column('id');
            $threadPhoneCount = $this->model->whereIn('uid',$userIds)->where('merchant_id',$post['merchant_id'])->count();
            if($threadPhoneCount > 0){
                return $this->error('该手机号已报名商户课程');
            }
            if(isset($post['wx_nickname']) && !empty($post['wx_nickname'])){
                $userInfo->wx_nickname = $post['wx_nickname'];
                $userInfo->save();
            }
            $gatherInfo = GatherUserInfoModel::getFormatGatherInfo($userInfo->age_range_id, 'age_range_id');
            $ageRange = $gatherInfo['name'];
            $weightArr = isset($tarMerchantInfo->age_range_weight_json) && !empty($tarMerchantInfo->age_range_weight_json) ? json_decode($tarMerchantInfo->age_range_weight_json, true) : [];
            $weight = isset($weightArr[$ageRange]) && !empty($weightArr[$ageRange]) ? $weightArr[$ageRange] : 0;
            if($weight <= 0){
                return $this->error('年龄权重不匹配');
            }
            $threadPriceInfo = \app\model\api\Merchant::getMerchantThreadPrice($tarMerchantInfo);

            $redisMerchantKey = env('redis.merchant_amount_redis_v2_key'). $tarMerchantInfo->id;
            if (!$redis->exists($redisMerchantKey)) {
                $redis->set($redisMerchantKey, floatToInt($tarMerchantInfo->residue_amount));
            }
            $threadMemberListRedisKey = env('redis.assign_thread_member_LIST_redis_key');
            $redis->watch($redisMerchantKey);
            $merchantStore = $redis->get($redisMerchantKey);
            $redis->del($threadMemberListRedisKey);
            if ($redis->sismember($threadMemberListRedisKey, $thread->id)) {
                return $this->error('该线索被占用');
            }
            $threadPrice = $userInfo->is_test == 1 ? 0 : floatToInt($threadPriceInfo['thread_price']);
            if ($merchantStore < $threadPrice) {
                return $this->error('商户余额不足');
            }
            $redis->sadd($threadMemberListRedisKey,$thread->id);
            $redis->multi();
            $redis->decrBy($redisMerchantKey, $threadPrice);
            $result = $redis->exec();
            $thread = $thread->toArray();
            $threadId = $thread['id'];
            $orgCreateTime = strtotime($thread['create_time']);
            unset($thread['id']);
            unset($thread['tag_names']);
            if($type == 1){
                $threadType = isset($tarMerchantInfo->is_jump_miniprogram) ? ($tarMerchantInfo->is_jump_miniprogram > 0 ? 3 : 1) : 0;
            }else{
                $threadType = isset($tarMerchantInfo->is_jump_miniprogram) ? ($tarMerchantInfo->is_jump_miniprogram > 0 ? 4 : 2) : 0;
            }
            //当天客服手动分配线索数量
            $todayAssignThreadNums = Thread::where('merchant_id',$tarMerchantInfo->id)
                ->where('is_assign',3)
                ->whereIn('assign_mode',[1,2,4])
                ->whereTime('create_time','today')
                ->count();
            //当天客服手动分配线索加V数
            $todayAssignThreadMicroNums = Thread::where('merchant_id',$tarMerchantInfo->id)
                ->where('is_discern_qrcode',1)
                ->where('is_assign',3)
                ->whereIn('assign_mode',[1,2,4])
                ->whereTime('create_time','today')
                ->count();
            $todayAssignThreadMicroRate = $todayAssignThreadNums > 0 && $todayAssignThreadMicroNums > 0 ? round(($todayAssignThreadMicroNums / $todayAssignThreadNums * 100),2) : 0;
            $isDiscernQrcode = isset($post['is_discern_qrcode']) ? $post['is_discern_qrcode'] : 1;
            $isDiscernQrcode = $tarMerchantInfo->maximum_virtual_micro_rate > 0 && $todayAssignThreadMicroRate <= $tarMerchantInfo->maximum_virtual_micro_rate ? 1 : $isDiscernQrcode;
            try {
                if($result) {
                    $thread['is_discern_qrcode'] = $tarMerchantInfo->is_jump_miniprogram == 1 ? $isDiscernQrcode : 0;
                    $thread['merchant_id'] = $tarMerchantInfo->id;
                    $thread['customer_id'] = $customerInfo->id ?? 0;
                    $thread['course_id'] = $courseId;
                    $thread['entry_fee'] = $courseEntryFee;
                    $thread['thread_price'] = $threadPriceInfo['thread_price'];
                    $thread['thread_price_type'] = $threadPriceInfo['thread_price_type'];
                    $thread['thread_price_origin'] = !empty($tarMerchantInfo->thread_price_origin) ? $tarMerchantInfo->thread_price_origin : $threadPriceInfo['thread_price'];
                    $thread['source'] = 4;
                    $thread['is_assign'] = 3;
                    $thread['assign_mode'] = isset($post['assign_mode']) ? $post['assign_mode'] : 1;
                    $thread['thread_type'] = $threadType;
                    $thread['is_free_try'] = $tarMerchantInfo->is_free_try;
                    $thread['age_id'] = $userInfo->age_range_id;
                    $thread['admin_id'] = $loginUserInfo['id'];
                    $thread['merchant_admin_id'] = $tarMerchantInfo->admin_ids;
                    $thread['build_mode'] = $post['build_mode'];
                    $thread['is_real_qrcode'] = $tarMerchantInfo->maximum_virtual_micro_rate > 0 && $todayAssignThreadMicroRate <= $tarMerchantInfo->maximum_virtual_micro_rate ? 0 : 1;
                    $thread['create_time'] = time();
                    $thread['update_time'] = time();
                    $retId = Thread::insertGetId($thread);
                    if($retId) {
                        $redis->Incr($redisKey, 1);
                        $redis->sRem($threadMemberListRedisKey,$threadId);
                        Thread::where('id',$threadId)->update(['is_assign' => 1]);
                        //更新目标商户
                        Event::trigger('ApplySuccessAfter', ['merchant' => $tarMerchantInfo, 'thread' => ['uid' => $userInfo->id, 'customerId' => $customerInfo->id ?? 0, 'thread_price' => $threadPriceInfo['thread_price']]]);
                        //自动分配线索
                        //Event::trigger('AssignSuccessAfter', ['orgMerchant' => $orgMerchantInfo,'tarMerchant' => $tarMerchantInfo, 'thread' => $orgThread]);
                        //同步线索
                        if($tarMerchantInfo->is_filiale == 1){
                            event('ApplyThreadSuccessAfter', ['threadId' => $retId]);
                        }
                        AssignThreadQueueLog::create([
                            'thread_id' => $retId,
                            'org_thread_id' => $threadId,
                            'org_merchant_id' => $orgMerchantInfo->id,
                            'tar_merchant_id' => $tarMerchantInfo->id,
                            'org_customer_id' => $thread['customer_id'],
                            'tar_customer_id' => $customerInfo->id ?? 0,
                            'thread_price' => $thread['thread_price'],
                            'admin_id' => $loginUserInfo['id'],
                            'assign_mode' => isset($post['assign_mode']) ? $post['assign_mode'] : 1,
                            'channel_id' => $thread['channel_id'],
                            'app_id' => $thread['app_id'],
                            'app_class_id' => $thread['app_class_id'],
                            'build_mode' => $post['build_mode'],
                            'org_thread_price' => $thread['thread_price'],
                            'org_create_time' => $orgCreateTime
                        ]);
                        if($tarMerchantInfo->id == 177 || $tarMerchantInfo->id == 251) {
                            (new CustomerServiceV6())->setResidueThreadNum($redis,env('redis.assign_thread_customer_redis_key').$tarMerchantInfo->id,$customerInfo->id);
                        }
                        return $this->success('分配成功');
                    }
                    $redis->incrBy($redisMerchantKey, $threadPrice);
                    $redis->sRem($threadMemberListRedisKey,$thread->id);
                    return $this->error('分配失败');
                }
            }catch (\Exception $e){
                $redis->incrBy($redisMerchantKey, $threadPrice);
                return $this->error('分配失败：'.$e->getMessage());
            }
        }

    }

    public function assignThreadV2()
    {
        $loginUserInfo = UserServiceFacade::getUserInfo();
        $post     = CommonFun::filterPostData($this->request->param());
        if($post['is_submit'] != 1){
            return $this->error('请先生成二维码');
        }
        $validate = new ThreadTransformationValidate();
        if (!$validate->scene('assign')->check($post)) return $this->error($validate->getError());
        $thread = $this->model->where('id',$post['thread_id'])->find();
        if(!empty($thread)){
            $thread = $thread->toArray();
            if($thread['is_origin'] == 2 && empty($post['wx_nickname'])){
                return $this->error('用户昵称不能为空');
            }
            $userInfo = UserList::find($thread['uid']);
            $userIds = UserList::where('phone',$userInfo->phone)->column('id');
            if (isset($post['wx_nickname']) && !empty($post['wx_nickname'])) {
                $userInfo->wx_nickname = $post['wx_nickname'];
                $userInfo->save();
            }
            $merchantIds = explode(',',$post['merchant_id']);
            $customerIds = explode(',',$post['customer_id']);
            $assignMode = count($merchantIds) > 1 ? 4 : 2;
            foreach($merchantIds as $key => $merchantId) {
                if(empty($merchantId)){
                    continue;
                }
                $courseInfo = Course::where('merchant_id', $merchantId)->where('course_type',0)->field('id,entry_fee')->find();
                $courseId = !empty($courseInfo) ? $courseInfo->id : 0;
                $courseEntryFee = !empty($courseInfo) ? $courseInfo->entry_fee : 0;
                $redis = get_redis();
                $type = $courseEntryFee > 0 ? 2 : 1;
                $channelInfo = Channel::where('id', $thread['channel_id'])->field('id,source_id')->find();
                $redisKey = 'assign_thread_merchant_key_' . $merchantId . '_type_' . $type . '_source_' . $channelInfo->source_id . '_class_' . $thread['app_class_id'];
                $orgMerchantInfo = Merchant::where('id', $thread['merchant_id'])->find();
                if (empty($orgMerchantInfo) || $orgMerchantInfo->is_source == 2) {
                    file_put_contents('assign_error.txt','原商户不符合分配规则'."\n",FILE_APPEND);
                    continue;
                }
                $tarMerchantInfo = Merchant::where('id', $merchantId)->find();
                if (empty($tarMerchantInfo) || $tarMerchantInfo->is_switch == 0) {
                    file_put_contents('assign_error.txt','分配商户异常或未开启进量'."\n",FILE_APPEND);
                    continue;
                }
                $threadCount = $this->model->where('uid', $thread['uid'])->where('merchant_id', $merchantId)->count();
                if ($threadCount > 0) {
                    file_put_contents('assign_error.txt','该用户已报名商户课程'."\n",FILE_APPEND);
                    continue;
                }

                $threadPhoneCount = $this->model->whereIn('uid', $userIds)->where('merchant_id', $merchantId)->count();
                if ($threadPhoneCount > 0) {
                    file_put_contents('assign_error.txt','该手机号已报名商户课程'."\n",FILE_APPEND);
                    continue;
                }
                $customerId = isset($customerIds[$key]) ? $customerIds[$key] : 0;
                $customerInfo = Customer::where('merchant_id',$merchantId)->where('id',$customerId)->count();
                if($tarMerchantInfo->is_jump_miniprogram == 1) {
                    if ($customerInfo <= 0) {
                        file_put_contents('assign_error.txt', '分配商户客服不匹配' . "\n", FILE_APPEND);
                        continue;
                    }
                }
                $gatherInfo = GatherUserInfoModel::getFormatGatherInfo($userInfo->age_range_id, 'age_range_id');
                $ageRange = $gatherInfo['name'];
                $weightArr = isset($tarMerchantInfo->age_range_weight_json) && !empty($tarMerchantInfo->age_range_weight_json) ? json_decode($tarMerchantInfo->age_range_weight_json, true) : [];
                $weight = isset($weightArr[$ageRange]) && !empty($weightArr[$ageRange]) ? $weightArr[$ageRange] : 0;
                if ($weight <= 0) {
                    file_put_contents('assign_error.txt','年龄权重不匹配'."\n",FILE_APPEND);
                    continue;
                }
                $threadPriceInfo = \app\model\api\Merchant::getMerchantThreadPrice($tarMerchantInfo);

                $redisMerchantKey = env('redis.merchant_amount_redis_v2_key') . $tarMerchantInfo->id;
                if (!$redis->exists($redisMerchantKey)) {
                    $redis->set($redisMerchantKey, floatToInt($tarMerchantInfo->residue_amount));
                }
                $threadMemberListRedisKey = env('redis.assign_thread_member_LIST_redis_key');
                $redis->watch($redisMerchantKey);
                $merchantStore = $redis->get($redisMerchantKey);
                $redis->del($threadMemberListRedisKey);
                if ($redis->sismember($threadMemberListRedisKey, $thread['id'])) {
                    file_put_contents('assign_error.txt','该线索被占用'."\n",FILE_APPEND);
                    continue;
                }
                $threadPrice = $userInfo->is_test == 1 ? 0 : floatToInt($threadPriceInfo['thread_price']);
                if ($merchantStore < $threadPrice) {
                    file_put_contents('assign_error.txt','商户余额不足'."\n",FILE_APPEND);
                    continue;
                }
                $redis->sadd($threadMemberListRedisKey, $thread['id']);
                $redis->multi();
                $redis->decrBy($redisMerchantKey, $threadPrice);
                $result = $redis->exec();
                $orgCreateTime = strtotime($thread['create_time']);
                if ($type == 1) {
                    $threadType = isset($tarMerchantInfo->is_jump_miniprogram) ? ($tarMerchantInfo->is_jump_miniprogram > 0 ? 3 : 1) : 0;
                } else {
                    $threadType = isset($tarMerchantInfo->is_jump_miniprogram) ? ($tarMerchantInfo->is_jump_miniprogram > 0 ? 4 : 2) : 0;
                }
                //当天客服手动分配线索数量
                $todayAssignThreadNums = Thread::where('merchant_id',$tarMerchantInfo->id)
                    ->where('is_assign',3)
                    ->whereIn('assign_mode',[1,2,4])
                    ->whereTime('create_time','today')
                    ->count();
                //当天客服手动分配线索加V数
                $todayAssignThreadMicroNums = Thread::where('merchant_id',$tarMerchantInfo->id)
                    ->where('is_discern_qrcode',1)
                    ->where('is_assign',3)
                    ->whereIn('assign_mode',[1,2,4])
                    ->whereTime('create_time','today')
                    ->count();
                $todayAssignThreadMicroRate = $todayAssignThreadNums > 0 && $todayAssignThreadMicroNums > 0 ? round(($todayAssignThreadMicroNums / $todayAssignThreadNums * 100),2) : 0;
                $isDiscernQrcode = isset($post['is_discern_qrcode']) ? $post['is_discern_qrcode'] : 1;
                $isDiscernQrcode = $todayAssignThreadMicroRate <= $tarMerchantInfo->maximum_virtual_micro_rate ? 1 : $isDiscernQrcode;
                try {
                    if ($result) {
                        $threadData = [
                            'uid' => $thread['uid'],
                            'course_id' => $courseId,
                            'entry_fee' => $courseEntryFee,
                            'merchant_id' => $tarMerchantInfo->id,
                            'customer_id' => $customerId ?? 0,
                            'province' => $thread['province'] ?? '',
                            'city' => $thread['city'] ?? '',
                            'age' => $thread['age'],
                            'channel' => $thread['channel'],
                            'store' => $thread['store'],
                            'thread_price' => $threadPriceInfo['thread_price'],
                            'thread_price_type' => $threadPriceInfo['thread_price_type'],
                            'thread_price_origin' => !empty($tarMerchantInfo->thread_price_origin) ? $tarMerchantInfo->thread_price_origin : $threadPriceInfo['thread_price'],
                            'channel_id' => $thread['channel_id'],
                            'app_id' => $thread['app_id'],
                            'app_class_id' => $thread['app_class_id'],
                            'landing_page_id' => $thread['landing_page_id'],
                            'is_assign' => 3,
                            'assign_mode' => $assignMode,
                            'thread_type' => $threadType,
                            'is_many_organization' => $thread['is_many_organization'],
                            'is_search_plan' => $thread['is_search_plan'],
                            'is_free_try' => $tarMerchantInfo->is_free_try,
                            'age_id' => $thread['age_id'],
                            'admin_id' => $loginUserInfo['id'],
                            'merchant_admin_id' => $tarMerchantInfo->admin_ids,
                            'cost_price' => $thread['cost_price'],
                            'is_origin' => $thread['is_origin'],
                            'source' => $thread['source'],
                            'is_discern_qrcode' => $tarMerchantInfo->is_jump_miniprogram == 1 ? $isDiscernQrcode : 0,
                            'is_real_qrcode' => $tarMerchantInfo->maximum_virtual_micro_rate > 0 && $todayAssignThreadMicroRate <= $tarMerchantInfo->maximum_virtual_micro_rate ? 0 : 1,
                            'build_mode' => $post['build_mode'],
                            'create_time' => time(),
                            'update_time' => time(),
                        ];
                        $retId = Thread::insertGetId($threadData);
                        if ($retId) {
                            $redis->Incr($redisKey, 1);
                            $redis->sRem($threadMemberListRedisKey, $thread['id']);
                            //更新目标商户
                            Event::trigger('ApplySuccessAfter', ['merchant' => $tarMerchantInfo, 'thread' => ['uid' => $userInfo->id, 'customerId' => $customerId, 'thread_price' => $threadPriceInfo['thread_price']]]);
                            //同步线索
                            if($tarMerchantInfo->is_filiale == 1){
                                event('ApplyThreadSuccessAfter', ['threadId' => $retId]);
                            }
                            AssignThreadQueueLog::create([
                                'thread_id' => $retId,
                                'org_thread_id' => $thread['id'],
                                'org_merchant_id' => $orgMerchantInfo->id,
                                'tar_merchant_id' => $tarMerchantInfo->id,
                                'org_customer_id' => $thread['customer_id'],
                                'tar_customer_id' => $customerId,
                                'thread_price' => $threadData['thread_price'],
                                'admin_id' => $loginUserInfo['id'],
                                'assign_mode' => $assignMode,
                                'channel_id' => $thread['channel_id'],
                                'app_id' => $thread['app_id'],
                                'app_class_id' => $thread['app_class_id'],
                                'build_mode' => $post['build_mode'],
                                'org_thread_price' => $thread['thread_price'],
                                'org_create_time' => $orgCreateTime
                            ]);
                            //return $this->success('分配成功');
                        }
                        $redis->incrBy($redisMerchantKey, $threadPrice);
                        $redis->sRem($threadMemberListRedisKey, $thread['id']);
                        Thread::where('id', $thread['id'])->update(['is_assign' => 1]);
                    }
                } catch (\Exception $e) {
                    $redis->incrBy($redisMerchantKey, $threadPrice);
                    file_put_contents('assign_error.txt','分配失败：' . $e->getMessage().'-'.$e->getLine()."\n",FILE_APPEND);
                    continue;
                }
            }
            return $this->success('分配成功');
        }
        return $this->success('分配失败');
    }
}