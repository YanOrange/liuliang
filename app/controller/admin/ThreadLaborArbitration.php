<?php

namespace app\controller\admin;

use app\model\admin\GatherUserInfo;
use app\model\admin\thread\ThreadUserStatus;
use app\model\admin\UserListExternal;
use app\service\admin\AuthServiceFacade;
use app\service\admin\UserServiceFacade;
use laytp\controller\Backend;
use think\facade\Cache;
use think\facade\Db;
use think\facade\Queue;
use think\facade\Session;
use app\model\admin\Course;
use app\model\admin\Merchant;
use app\model\admin\AssignThreadRecord;
use app\model\admin\ThreadQueue;
use app\model\admin\UserList;
use app\model\admin\AdminActionLog;
use app\model\admin\Channel;

/**
 * 后台线索控制器
 */
class ThreadLaborArbitration extends Backend
{
    protected $model;//当前模型对象

    protected $noNeedAuth = ['*'];
    protected $noNeedLogin = [''];

    protected $identityList = ["-", "学生", "职场", "自由职业", "全职宝妈", "公职职业编"];
    protected $educationList = ["-", "高中以下", "高中及职高", "大专", "本科及以上"];

    protected function _initialize()
    {
        $this->model = new \app\model\admin\Thread();
    }

    /**
     * 获取用户信息
     * @return false|string|\think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getThreadUserInfo()
    {
        $loginUserInfo = UserServiceFacade::getUserInfo();
        $loginId = $loginUserInfo['id'];
        $roleIds = AuthServiceFacade::getAuthUserRole($loginId);
        $userInfo = [];
        $newInfo = [];
        $id = $this->request->param('id');
        $thread = $this->model->field('uid,external_uid,merchant_id,is_discern_qrcode,province,city,create_time,source_id,status')->where('id', $id)->find();
        if (!empty($thread)) {
            $userInfo = UserList::where('id', $thread['uid'])->find();
            $userInfo['is_discern_qrcode'] = $thread['is_discern_qrcode'] ? '是' : '否';//是否长按二维码,
            $userInfo['source_title'] = '宇络';
            $userInfo['thread_create_time'] = $thread['create_time'];
            $userInfo['thread_status'] = $thread['status'];
            $userInfo['thread_source_id'] = $thread['source_id'];
            if ($loginUserInfo['is_show_phone'] == 0) {
                $userInfo['phone']= $userInfo['phone'] ? substr_replace($userInfo['phone'], '****', 3, 4) : '';
            }
            if ($thread['external_uid']) {
                $userInfoCopy = UserListExternal::where('id', $thread['external_uid'])->find();
                if (!empty($userInfoCopy)) {
                    //$phone = $userInfoCopy['phone'];
                    $phone= $userInfoCopy['phone'] ? substr_replace($userInfoCopy['phone'], '****', 3, 4) : '';

                    $newInfo = [
                        'phone' => $phone,
                        'nickname' => $userInfoCopy['nickname'],
                        'age' => $userInfoCopy['age'],
                        'education' => $userInfoCopy['education'],
                        'identity' => $userInfoCopy['identity'],
                        'province' => $userInfoCopy['province'],
                        'city' => $userInfoCopy['city'],
                    ];
                }
            }
        }
        return $this->success('数据获取成功', ['base_info' => $userInfo, 'new_info' => $newInfo]);
    }


    //查看
    public function index()
    {
        $loginUserInfo = UserServiceFacade::getUserInfo();
        $loginId = $loginUserInfo['id'];
        $roleIds = AuthServiceFacade::getAuthUserRole($loginId);

        // 客户权限 2022-09-03
        $isCustomer = false;
        if ($loginId != 1) {
            // 10 - 客服主管 || 11 - 客服干事
            if (in_array(env('ROLE.CUSTOMERLEADER'), $roleIds) || in_array(env('ROLE.CUSTOMERGANSHI'), $roleIds)) {
                $isCustomer = true;
            }
        }
        $order = $this->buildOrder();
        $data = $this->buildSearch(false)->with(['merchant', 'course', 'customer', 'user', 'channelpro', 'app', 'threadSource', 'class', 'flow', 'singleCourse' => function ($query) {
            $query->field('id,title');
        }, 'resource' => function ($query) {
            $query->field('id,title');
        }, 'appMessage' => function ($query) {
            $query->field('id,nickname');
        },'userStatus'=>function($query){
            $query->with(['followAction','parentFollowAction']);
        }])->order('thread.create_time desc');
        $allData = $this->request->param('all_data');
        /*print_r($data->buildSql());
        exit;*/
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
                $item['user']['jiankang_wenti'] = '';
                $item['user']['tk_touru_monney'] = '';
                $item['user']['overdue_time'] = '';
                foreach($customField as $val){
                    $gatherInfoData = $this->inArrayKey($gatherUserList,$val,'pid_cid_key');
                    if(isset($gatherInfoData[0])) {
                        $item['user'][$gatherInfoData[0]['field']] = isset($gatherInfoData[0]['name']) ? $gatherInfoData[0]['name'] : '';
                    }
                }
                return $item;
            })->toArray();
        }
        $filter = $this->request->param('search_param');
        $filter = !empty($filter) ? $filter : [];
        Session::set('thread_con', json_encode($filter));
        foreach ($data['data'] as $key => &$val) {
            $val['isCustomer'] = $isCustomer;   // chenlele 0903 客户权限
            if ($loginUserInfo['is_show_phone'] == 0) $val['user']['phone'] = isset($val['user']['phone']) && $val['user']['phone'] ? substr_replace($val['user']['phone'], '****', 3, 4) : '';
            $val['user']['wx_number'] = $val['user']['wx_number'] ?? '';
            $wxNumberCount = mb_strlen($val['user']['wx_number']);
            if($wxNumberCount){
                $val['user']['wx_number'] = $wxNumberCount > 4 ? substr_replace($val['user']['wx_number'], '****', 3, 4) : substr_replace($val['user']['wx_number'], '****', 1, 4);
            }
            if (isset($val['singleCourse']) && !empty($val['singleCourse'])) {
                $val['course']['title'] = isset($val['singleCourse']['title']) ? $val['singleCourse']['title'] : '';
            }
            if (isset($val['resource']) && !empty($val['resource'])) {
                $val['course']['title'] = isset($val['resource']['title']) ? $val['resource']['title'] : '';
            }
            if (isset($val['appMessage']) && !empty($val['appMessage'])) {
                $val['course']['title'] = isset($val['appMessage']['nickname']) ? $val['appMessage']['nickname'] : '';
            }
            unset($data['data'][$key]['resource']);
            unset($data['data'][$key]['singleCourse']);
            unset($data['data'][$key]['appMessage']);
            //查询用户状态列表信息
            $val['follow_action'] = ThreadUserStatus::getThreadUserStatusText($val['userStatus']);
            $val['baidu_status'] = '';
            if(in_array($val['merchant_id'],[142,195,229,242,245,246])){
                $val['baidu_status'] = Db::name('thread_external')->alias('thr')
                    ->join('thread_user_status tus','tus.thread_id = thr.id and current_parent_action_id in (558,569,580)','left')
                    ->join('thread_follow_action tha','tha.id = tus.current_action_id','left')
                    ->where('thr.inside_thread_id',$val['id'])
                    ->value('tha.title');
            }
        }
        return $this->success('数据获取成功', $data);
    }

    // 构建查询条件
    private function buildSearch($isDelete = false, $whereCon = [])
    {
        $filter = $this->request->param('search_param');
        $filter = !empty($filter) ? $filter : [];
        $whereCon = !empty($whereCon) ? $whereCon : [];
        extract($filter);
        extract($whereCon);
        $loginUserInfo = UserServiceFacade::getUserInfo();
        $loginId = $loginUserInfo['id'];
        $roleIds = AuthServiceFacade::getAuthUserRole($loginId);
        $name = $this->model->getName();
        $tableName = env('database.prefix') . $name;
        if ($isDelete) {
            $threadModel = $this->model->onlyTrashed();
        } else {
            $threadModel = $this->model;
        }
        // 线索类型 1劳动仲裁
        $threadModel = $threadModel->where('thread_type', 1);

        if (isset($follow_status) && !empty($follow_status)) {
            //关联跟进状态关联表
            if($follow_status == 1){
                $threadModel = $threadModel->join('thread_user_status tus', 'tus.thread_id = '.$tableName.'.id', 'inner');
                $threadModel = $threadModel->group('tus.thread_id');
            }else{
                $threadModel = $threadModel->join('thread_user_status tus', 'tus.thread_id = '.$tableName.'.id', 'left');
                $threadModel = $threadModel->whereNull('tus.id');
            }
        }
        // 干事角色ID 22.09.02 chenlele
        // 非超级管理员
        if ($loginId != 1) {

            // 8 - 干事：管理自己负责的客户
            if (in_array(env('ROLE.GANSHI'), $roleIds)) {
                $threadModel = $threadModel->where($tableName . '.merchant_admin_id', '=', $loginId);
            }
        }

        if (!empty($status)) {
            $threadModel = $threadModel->where($tableName . '.status', '=', $status);
        }
        if (!empty($situation)) {
            $threadModel = $threadModel->where($tableName . '.situation', '=', $situation);
        }
        if (!empty($source_id)) {
            $threadModel = $threadModel->where($tableName . '.source_id', '=', $source_id);
        }
        if ($loginId != 1 && in_array(6,$roleIds)) {
            $merchantIds = Merchant::whereFindInSet('admin_ids',$loginId)->column('id');
            $threadModel = $threadModel->where($tableName.'.merchant_id', 'in', $merchantIds);
        }
        if (isset($thread_tag_ids) && !empty($thread_tag_ids)) {
            $threadModel = $threadModel->whereFindInSet($tableName . '.thread_tag_ids', $thread_tag_ids);
        }
        if (isset($merchant_id) && !empty($merchant_id)) {
            $merchant_id = explode(',', $merchant_id);
            $threadModel = $threadModel->where($tableName . '.merchant_id', 'in', $merchant_id);
        }
        if (isset($course_id) && !empty($course_id)) {
            $threadModel = $threadModel->where($tableName . '.course_id', '=', $course_id);
        }
        if (isset($customer_id) && !empty($customer_id)) {
            $threadModel = $threadModel->where($tableName . '.customer_id', '=', $customer_id);
        }
        if (isset($platform_id) && !empty($platform_id)) {
            $channelIds = Channel::where('platform_id',$platform_id)->value('id');
            $threadModel = $threadModel->where($tableName . '.channel_id', 'in', $channelIds);
        }
        if (isset($channel_id) && !empty($channel_id)) {
            $threadModel = $threadModel->where($tableName . '.channel_id', 'in', $channel_id);
        }
        if (isset($app_id) && !empty($app_id)) {
            $threadModel = $threadModel->where($tableName . '.app_id', '=', $app_id);
        }
        if (isset($app_class_id) && !empty($app_class_id)) {
            $threadModel = $threadModel->where($tableName . '.app_class_id', '=', $app_class_id);
        }
        if (isset($is_register) && is_numeric($is_register)) {
            $threadModel = $threadModel->where($tableName . '.is_register', '=', $is_register);
        }
        if (isset($is_wecom_qrcode) && is_numeric($is_wecom_qrcode)) {
            $threadModel = $threadModel->where($tableName . '.is_wecom_qrcode', '=', $is_wecom_qrcode);
        }
        if (isset($is_discern_qrcode) && is_numeric($is_discern_qrcode)) {
            if($is_discern_qrcode == 1){
                $threadModel = $threadModel->where($tableName . '.is_discern_qrcode', '=', 1);
                $threadModel = $threadModel->where($tableName . '.is_real_qrcode', '=', 1);
            }else if($is_discern_qrcode == 2){
                $threadModel = $threadModel->where($tableName . '.is_discern_qrcode', '=', 1);
                $threadModel = $threadModel->where($tableName . '.is_real_qrcode', '=', 0);
            }else{
                $threadModel = $threadModel->where($tableName . '.is_discern_qrcode', '=', $is_discern_qrcode);
            }
        }
        if (isset($thread_type) && is_numeric($thread_type)) {
            $threadModel = $threadModel->where($tableName . '.thread_type', '=', $thread_type);
        }
        if (isset($source) && is_numeric($source)) {
            if($source == 1){
                $threadModel = $threadModel->where($tableName . '.source', '=', $source)->whereNotIn($tableName . '.apply_register_type',[10,11]);
            }else if($source == 10 || $source == 11){
                $threadModel = $threadModel->where($tableName . '.apply_register_type', '=', $source);
            }else{
                $threadModel = $threadModel->where($tableName . '.source', '=', $source);
            }
        }
        if (isset($assign_mode) && is_numeric($assign_mode)) {
            $threadModel = $threadModel->where($tableName . '.assign_mode', '=', $assign_mode);
        }
        if (isset($is_free_try) && is_numeric($is_free_try)) {
            if ($is_free_try == 2) $is_free_try = 0;
            $threadModel = $threadModel->where($tableName . '.is_free_try', '=', $is_free_try);
        }
        if (isset($is_test) && is_numeric($is_test)) {
            if($is_test == 2) $is_test = 0;
            $threadModel = $threadModel->where($tableName . '.is_test', '=', $is_test);
        }
        if (isset($is_assign) && is_numeric($is_assign)) {
            if($is_assign == 2) $is_assign = 0;
            $threadModel = $threadModel->where($tableName . '.is_assign', '=', $is_assign);
        }
        if ((isset($phone) && !empty($phone)) ||
            (isset($phone_end_number) && !empty($phone_end_number)) ||
            (isset($user_nickname) && !empty($user_nickname)) ||
            (isset($user_wx_nickname) && !empty($user_wx_nickname)) ||
            (isset($age_range_id) && is_numeric($age_range_id)) ||
            (isset($identity_id) && is_numeric($identity_id)) ||
            (isset($education_id) && is_numeric($education_id)) ||
            (isset($is_has_computer_id) && is_numeric($is_has_computer_id))
        ) {
            $threadModel = $threadModel->withJoin(['user'], 'inner');
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
            if (isset($age_range_id) && !empty($age_range_id)) {
                $threadModel = $threadModel->where('user.age_range_id', '=', $age_range_id);
            }
            if (isset($identity_id) && !empty($identity_id)) {
                $threadModel = $threadModel->where('user.identity_id', '=', $identity_id);
            }
            if (isset($education_id) && !empty($education_id)) {
                $threadModel = $threadModel->where('user.education_id', '=', $education_id);
            }
            if (isset($is_has_computer_id) && !empty($is_has_computer_id)) {
                $threadModel = $threadModel->where('user.is_has_computer_id', '=', $is_has_computer_id);
            }
        }else{
            $threadModel = $threadModel->field($tableName.'.*');
        }


        /* if ((isset($cus_nickname) && !empty($cus_nickname)) || (isset($cus_wechat_number) && !empty($cus_wechat_number))) {
                 $threadModel = $threadModel->withJoin(['customer'], 'inner');
                 $threadModel = $threadModel->withJoin(['user'], 'inner');
                 if (isset($cus_wechat_number) && !empty($cus_wechat_number)) {
                     $threadModel = $threadModel->where('customer.wechat_number', '=', $cus_wechat_number);
                 }

         }*/
        if (isset($create_time) && !empty($create_time)) {
            list($startTime, $endTime) = explode(' - ', $create_time);
            $threadModel = $threadModel->where($tableName . '.create_time', 'between', strtotime($startTime) . ',' . strtotime($endTime));
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

    public function getThreadList()
    {
        $app_class_id = $this->request->param('app_class_id');
        $createTime = $this->request->param('create_time');
        $threadType = $this->request->param('thread_type');
        if (empty($createTime)) {
            $createTime = date('Y-m-d 12:00:00', strtotime('-1 day')) . " - " . date('Y-m-d 12:00:00');
        }
        $threadModel = $this->model;
        $name = $this->model->getName();
        $tableName = env('database.prefix') . $name;
        $threadModel = $threadModel->where('is_assign', 0);
        if (isset($app_class_id) && !empty($app_class_id)) {
            $threadModel = $threadModel->where('app_class_id', $app_class_id);
        }
        if (isset($threadType) && !empty($threadType)) {
            if ($threadType == 1) {
                $threadModel = $threadModel->where('entry_fee', 0);
            }
            if ($threadType == 2) {
                $threadModel = $threadModel->where('entry_fee', '>', 0);
            }
        }
        $threadModel = $threadModel->whereExists(function ($query) use ($tableName) {
            $userListTableName = (new \app\model\admin\UserList())->getName();
            $query = $query->table(env('database.prefix') . $userListTableName)->where(env('database.prefix') . $userListTableName . '.id=' . $tableName . '.uid');
            $query = $query->where('age_range_id', '>', 1);
            return $query;
        });

        $threadModel = $threadModel->whereExists(function ($query) use ($tableName) {
            $merchantTableName = (new \app\model\admin\Merchant())->getName();
            $query = $query->table(env('database.prefix') . $merchantTableName)->where(env('database.prefix') . $merchantTableName . '.id=' . $tableName . '.merchant_id');
            $query = $query->where('is_source', '=', 1);
            return $query;
        });

        list($startTime, $endTime) = explode(' - ', $createTime);
        $threadModel = $threadModel->where('create_time', 'between', strtotime($startTime) . ',' . strtotime($endTime));

        $data['data'][0]['totalThread'] = $threadModel
            ->order('create_time desc')
            ->count();

        return $this->success('数据获取成功', $data);
    }

    //编辑微信号
    public function setWxNumber()
    {
        $threadId = $this->request->post('id');
        $wxNumber = $this->request->post('wx_number');
        if(!empty($threadId) && !empty($wxNumber)){
            $threadInfo = $this->model->where('id',$threadId)->field('id,uid')->find();
            $userInfo = UserList::where('id',$threadInfo->uid)->field('id,wx_number')->find();
            $userInfo->wx_number = $wxNumber;
            $userInfo->save();
        }
        return $this->success('操作成功');
    }

    /**
     * 设置劳动仲裁状态
     * @return void
     */
    public function setThreadLaborArbitationStatus()
    {
        $threadId = $this->request->post('id');
        $status = $this->request->post('status');
        $threadType = $this->request->post('thread_type');
        if(!empty($threadId) && !empty($status)){
            $threadInfo = $this->model->where('id',$threadId)->where('thread_type',$threadType)->field('id,status')->find();
            $threadInfo->status = $status;
            $threadInfo->save();
        }
        return $this->success('操作成功');
    }

    /**
     * 设置劳动仲裁跟进情况
     * @return void
     */
    public function setThreadLawyerSituation()
    {
        $threadId = $this->request->post('id');
        $situation = $this->request->post('situation');
        $threadType = $this->request->post('thread_type');
        if(!empty($threadId) && !empty($situation)){
            $threadInfo = $this->model->where('id',$threadId)->where('thread_type',$threadType)->field('id,situation')->find();
            $threadInfo->situation = $situation;
            $threadInfo->save();
        }
        return $this->success('操作成功');
    }

    /**
     * 编辑字段
     * @return false|string|\think\response\Json
     */
    public function setFind()
    {
        $threadId = $this->request->post('id');
        $field = $this->request->post('field');
        $fieldVal = $this->request->post('field_val');

        if ($field == 'situation_describe') {
            $threadInfo = $this->model->where('id',$threadId)->field('id,situation_describe')->find();
            $threadInfo->situation_describe = $fieldVal;
            $threadInfo->save();
            return $this->success('操作成功');
        }

        if ($field == 'signed_amount') {
            $threadInfo = $this->model->where('id',$threadId)->field('id,signed_amount')->find();
            $threadInfo->signed_amount = $fieldVal;
            $threadInfo->save();
            return $this->success('操作成功');
        }

        if ($field == 'actual_received_amount') {
            $threadInfo = $this->model->where('id',$threadId)->field('id,actual_received_amount')->find();
            $threadInfo->actual_received_amount = $fieldVal;
            $threadInfo->save();
            return $this->success('操作成功');
        }

        return $this->error('未作修改');
    }

    //年龄
    public function ageRangeList()
    {
        $gatherInfoJson = GatherUserInfo::where('id', 1)->value('gather_info_json');
        $ageRangeList = json_decode($gatherInfoJson, true);
        return $this->success('获取数据成功', $ageRangeList);
    }

    //身份
    public function identifyList()
    {
        $gatherInfoJson = GatherUserInfo::where('id', 2)->value('gather_info_json');
        $identityList = json_decode($gatherInfoJson, true);
        return $this->success('获取数据成功', $identityList);
    }

    //学历
    public function educationList()
    {
        $gatherInfoJson = GatherUserInfo::where('id', 3)->value('gather_info_json');
        $educationList = json_decode($gatherInfoJson, true);
        return $this->success('获取数据成功', $educationList);
    }

    //学习需求
    public function studyGoalList()
    {
        $gatherInfoJson = GatherUserInfo::where('id', 4)->value('gather_info_json');
        $studyGoalList = json_decode($gatherInfoJson, true);
        return $this->success('获取数据成功', $studyGoalList);
    }

    //线索导出
    public function exportThread()
    {

        set_time_limit(0);
        //设置程序运行内存
        ini_set('memory_limit', '512M');
        $fileName = date("Y-m-d H:i:s") . '-线索数据';
        header('Content-Encoding: UTF-8');
        header("Content-type:application/vnd.ms-excel;charset=UTF-8");
        header('Content-Disposition: attachment;filename="' . $fileName . '.csv"');
        //打开php标准输出流
        $fp = fopen('php://output', 'a');
        //添加BOM头，以UTF8编码导出CSV文件，如果文件头未添加BOM头，打开会出现乱码。
        fwrite($fp, chr(0xEF).chr(0xBB).chr(0xBF));
        $whereCon = json_decode(Session::get('thread_con'), true);
        $loginUserInfo = UserServiceFacade::getUserInfo();
        $roleIds =AuthServiceFacade::getAuthUserRole($loginUserInfo['id']);
        $data = $this->buildSearch(false, $whereCon)->with(['merchant', 'course', 'customer', 'user', 'channelpro', 'app', 'threadSource', 'class', 'flow', 'singleCourse'])->order('id desc')->select();
        fputcsv($fp, ['ID', '商户','用户昵称','咨询类型', '用户手机号','来源','客服昵称', '客服微信号', '省份', '城市', '跟进状态', '跟进情况', '跟进情况描述', '签单金额', '实收金额', '添加时间']);
        foreach ($data as $val) {
            $status_text = '-';
            $situation_text = '-';
            if($val['status'] == 99){
                $status_text =  '已放弃';
            }else{
                if($val['customer_id']){
                    if($val['status'] == 1){
                        $status_text =  '跟进中';
                    }else if($val['status'] == 2){
                        $status_text =  '转化中';
                    }else if($val['status'] == 3){
                        $status_text =  '已成功';
                    }
                }else{
                    $status_text =  '待分配';
                }
            }
            if ($val['situation'] == 0) {
                $situation_text = '正常';
            } else if($val['situation'] == 1) {
                $situation_text =  '拒绝';
            } else if($val['situation'] == 2) {
                $situation_text =  '无法接通';
            } else if($val['situation'] == 3) {
                $situation_text =  '无效对话';
            } else if($val['situation'] == 4) {
                $situation_text =  '无意向客户';
            } else if($val['situation'] == 5) {
                $situation_text =  '潜在客户';
            } else if($val['situation'] == 6) {
                $situation_text =  '强意向客户';
            }
            $item = [
                $val->id,
                $val->merchant->merchant_name ?? '',
                $val->userExternal->nickname ?? '',
                $val->consulting_type ?? '',
                isset($val['userExternal']['phone']) && !empty($val['userExternal']['phone']) ? substr_replace($val['userExternal']['phone'], '****', 3, 4) : $val['userExternal']['phone'],
                $val->threadSource->title??'',
                $val->customer->nickname ?? '',
                $val->customer->wechat_number ?? '',
                $val->province ?? '',
                $val->city ?? '',
                $status_text,
                $situation_text,
                $val->situation_describe ?? '',
                $val->signed_amount ?? '',
                $val->actual_received_amount ?? '',
                $val->create_time,
            ];
            fputcsv($fp, $item);
        }
        ob_flush();
        flush();
    }
    //线索分配
    public function assignThread()
    {
        $loginUserInfo = UserServiceFacade::getUserInfo();
        $params = $this->request->post();
        extract($params);
        try {
            Validate('app\validate\admin\thread\Thread')
                ->scene('assignThread')
                ->check($params);
        } catch (\Exception $e) {
            return $this->error($e->getError());
        }
        $target_merchant_id = $params['target_merchant_id'];
        $origin_merchant_id = $params['origin_merchant_id'];
        if ($target_merchant_id == $origin_merchant_id) {
            return $this->error('原商户和目标商户不能重复');
        }
        if (Cache::get('assign_thread_merchant' . $target_merchant_id)) {
            return $this->error('请勿重复分配，同一商户每天分配1次数据');
        }
        $origin_create_time = explode(' - ', $params['origin_create_time']);
        $target_create_time = explode(' - ', $params['target_create_time']);
        Db::startTrans();
        try {
            if (isset($num) && !empty($num)) {
                $thread = $this->model->where('merchant_id', $origin_merchant_id)
                    ->whereNotLike('age', '未满18岁')
                    ->whereBetween('create_time', [strtotime($origin_create_time[0]), strtotime($origin_create_time[1])])
                    ->limit($num)
                    ->select();
            } else {
                $thread = $this->model->where('merchant_id', $origin_merchant_id)
                    ->whereNotLike('age', '未满18岁')
                    ->whereBetween('create_time', [strtotime($origin_create_time[0]), strtotime($origin_create_time[1])])
                    ->select();
            }
            $thread = $thread->toArray();
            if (empty($thread)) {
                Db::rollback();
                return $this->error('原商户线索数据为空');
            }
            $target_course_id = Course::where('merchant_id', $target_merchant_id)->value('id');
            if (empty($target_course_id)) {
                Db::rollback();
                return $this->error('目标商户暂没有课程');
            }

            foreach ($thread as $key => &$item) {
                $threadNum = $this->model->where('uid', $item['uid'])->where('merchant_id', $target_merchant_id)->count();
                if ($threadNum > 0) {
                    unset($thread[$key]);
                } else {
                    unset($item['id']);
                    $item['course_id'] = $target_course_id;
                    $item['merchant_id'] = $target_merchant_id;
                    $item['customer_id'] = $params['customer_id'];
                    $item['thread_price'] = $params['thread_price'];
                    $item['create_time'] = mt_rand(strtotime($target_create_time[0]), strtotime($target_create_time[1]));
                    $item['update_time'] = $item['create_time'];
                }
                if (isset($item['tag_names'])) {
                    unset($thread[$key]['tag_names']);
                }
            }
            if (empty($thread)) {
                Db::rollback();
                return $this->error('目标商户没有可导入数据');
            }
            $resThreadNum = $this->model->insertAll($thread);
            if ($resThreadNum <= 0) {
                Db::rollback();
                return $this->error('没有分配线索数据');
            }
            $merchant = Merchant::where('id', $target_merchant_id)->find();
            $merchant->residue_amount = $merchant->residue_amount - $resThreadNum * $params['thread_price'];
            if ($merchant->residue_amount < 0) {
                Db::rollback();
                return $this->error('商户余额不足，不可分配');
            }
            $merchant->consume_thread_nums = $merchant->consume_thread_nums + $resThreadNum;
            $merchant->save();
            $data['origin_merchant_id'] = $params['origin_merchant_id'];
            $data['admin_id'] = $loginUserInfo['id'];
            $data['target_merchant_id'] = $params['target_merchant_id'];
            $data['customer_id'] = $params['customer_id'];
            $data['num'] = $resThreadNum;
            $data['thread_price'] = $params['thread_price'];
            $data['origin_create_time'] = $params['origin_create_time'];
            $data['target_create_time'] = $params['target_create_time'];
            AssignThreadRecord::create($data);
            Cache::set('assign_thread_merchant' . $target_merchant_id, $resThreadNum, 72000);
            Db::commit();
            return $this->success('成功分配给' . $merchant['merchant_name'] . '线索' . $resThreadNum . '条');
        } catch (\Exception $e) {
            Db::rollback();
            return $this->error($e->getMessage());
        }

    }

    //线索ID分配
    public function assignThreadId1()
    {
        $loginUserInfo = UserServiceFacade::getUserInfo();
        $params = $this->request->post();
        extract($params);
        try {
            Validate('app\validate\admin\thread\Thread')
                ->scene('assignThreadId')
                ->check($params);
        } catch (\Exception $e) {
            return $this->error($e->getError());
        }
        $threadIds = explode(',', $params['thread_ids']);
        $target_merchant_id = $params['target_merchant_id'];
//        if(Cache::get('assign_thread_id_merchant'.$target_merchant_id)){
//            return $this->error('请勿重复分配，同一商户每天分配1次数据');
//        }
        Db::startTrans();
        try {
            $name = strtolower($this->model->getName());
            $tableName = env('database.prefix') . $name;
            $thread = $this->model->whereExists(function ($query) use ($tableName) {
                $merchantTableName = (new \app\model\admin\Merchant())->getName();
                $query = $query->table(env('database.prefix') . $merchantTableName)->where(env('database.prefix') . $merchantTableName . '.id=' . $tableName . '.merchant_id');
                $query->where('is_source', 1);
                return $query;
            })
                ->whereIn('id', $threadIds)
                ->whereNotLike('age', '未满18岁')
                ->select()
                ->toArray();
            if (empty($thread)) {
                Db::rollback();
                return $this->error('原商户线索数据为空');
            }
            $target_course_id = Course::where('merchant_id', $target_merchant_id)->value('id');
            if (empty($target_course_id)) {
                Db::rollback();
                return $this->error('目标商户暂没有课程');
            }

            $merchant = Merchant::where('id', $target_merchant_id)->find();
            $threadPriceInfo = \app\model\api\Merchant::getMerchantThreadPrice($merchant);
            foreach ($thread as $key => &$item) {
                $threadNum = $this->model->where('uid', $item['uid'])->where('merchant_id', $target_merchant_id)->count();
                if ($threadNum > 0) {
                    unset($thread[$key]);
                } else {
                    unset($item['id']);
                    $item['course_id'] = $target_course_id;
                    $item['merchant_id'] = $target_merchant_id;
                    $item['customer_id'] = $params['customer_id'];
                    $item['thread_price'] = $threadPriceInfo['thread_price'];
                    $item['thread_price_type'] = $threadPriceInfo['thread_price_type'];
                    $item['create_time'] = time();
                    $item['update_time'] = time();
                }
                if (isset($item['tag_names'])) {
                    unset($thread[$key]['tag_names']);
                }
            }
            if (empty($thread)) {
                Db::rollback();
                return $this->error('目标商户没有可导入数据');
            }
            $resThreadNum = $this->model->insertAll($thread);
            if ($resThreadNum <= 0) {
                Db::rollback();
                return $this->error('没有分配线索数据');
            }

            $merchant->residue_amount = $merchant->residue_amount - $resThreadNum * $threadPriceInfo['thread_price'];
            if ($merchant->residue_amount < 0) {
                Db::rollback();
                return $this->error('商户余额不足，不可分配');
            }
            $merchant->consume_thread_nums = $merchant->consume_thread_nums + $resThreadNum;
            $merchant->save();
            $data['thread_ids'] = $params['thread_ids'];
            $data['admin_id'] = $loginUserInfo['id'];
            $data['target_merchant_id'] = $params['target_merchant_id'];
            $data['customer_id'] = $params['customer_id'];
            $data['num'] = $resThreadNum;
            AssignThreadRecord::create($data);
            Cache::set('assign_thread_id_merchant' . $target_merchant_id, $resThreadNum, 72000);
            Db::commit();
            return $this->success('成功分配给' . $merchant['merchant_name'] . '线索' . $resThreadNum . '条');
        } catch (\Exception $e) {
            Db::rollback();
            return $this->error($e->getMessage());
        }

    }

    //线索自动分配
    public function assignThreadId()
    {
        $target_merchant_id = $this->request->param('target_merchant_id');
        $target_create_time = $this->request->param('target_create_time');
        $threadType = $this->request->param('thread_type');
        $create_time = $this->request->param('create_time');
        $app_class_id = $this->request->param('app_class_id');
        $num = $this->request->param('num');
        $params = $this->request->param();
        $num = isset($num) && !empty($num) ? $num : 20;
        try {
            Validate('app\validate\admin\thread\Thread')
                ->scene('assignThreadId')
                ->check($params);
        } catch (\Exception $e) {
            return $this->error($e->getError());
        }
        list($startTime, $endTime) = explode(' - ', $target_create_time);
        if (strtotime($startTime) < time()) {
            return $this->error('开始时间不能小于当前时间');
        }
        if (strtotime($startTime) == strtotime($endTime)) {
            return $this->error('开始结束时间不能相等');
        }
//        if(Cache::get('assign_thread_id_merchant'.$target_merchant_id)){
//            return $this->error('请勿重复分配，同一商户每天分配1次数据');
//        }

        try {
            $threadModel = $this->model;
            $name = $this->model->getName();
            $tableName = env('database.prefix') . $name;
            $threadModel = $threadModel->where('is_assign', 0);
            if (isset($app_class_id) && !empty($app_class_id)) {
                $threadModel = $threadModel->where('app_class_id', $app_class_id);
            }
            if (isset($threadType) && !empty($threadType)) {
                if ($threadType == 1) {
                    $threadModel = $threadModel->where('entry_fee', 0);
                }
                if ($threadType == 2) {
                    $threadModel = $threadModel->where('entry_fee', '>', 0);
                }
            }
            $threadModel = $threadModel->whereExists(function ($query) use ($tableName) {
                $userListTableName = (new \app\model\admin\UserList())->getName();
                $query = $query->table(env('database.prefix') . $userListTableName)->where(env('database.prefix') . $userListTableName . '.id=' . $tableName . '.uid');
                $query = $query->where('age_range_id', '>', 1);
                return $query;
            });

            $threadModel = $threadModel->whereExists(function ($query) use ($tableName) {
                $merchantTableName = (new \app\model\admin\Merchant())->getName();
                $query = $query->table(env('database.prefix') . $merchantTableName)->where(env('database.prefix') . $merchantTableName . '.id=' . $tableName . '.merchant_id');
                $query = $query->where('is_source', '=', 1);
                return $query;
            });

            if (isset($create_time) && !empty($create_time)) {
                $createTime = explode(' - ', $create_time);
                $threadModel = $threadModel->where('create_time', 'between', strtotime($createTime[0]) . ',' . strtotime($createTime[1]));
            } else {
                $threadModel = $threadModel->where('create_time', 'between', strtotime(date('Y-m-d 12:00:00', strtotime('-1 day'))) . ',' . strtotime(date('Y-m-d 12:00:00')));
            }
            Db::startTrans();
            $thread = $threadModel->limit($num)->order('id desc')->select()->toArray();
            if (!empty($thread)) {
                $threadIds = array_column($thread, 'id');
                $this->model->whereIn('id', $threadIds)->save(['is_assign' => 1]);
                $threadAveTime = floor((strtotime($endTime) - strtotime($startTime)) / count($thread));
                if ($threadAveTime <= 1) {
                    return $this->error('请重新选择时间');
                }
                $threadQueueId = ThreadQueue::insertGetId([
                    'name' => '分配线索队列',
                    'content' => json_encode(['app_class_id' => $app_class_id, 'thread_type' => $threadType, 'target_merchant_id' => $target_merchant_id, 'target_create_time' => $target_create_time, 'num' => $num]),
                    'create_time' => time(),
                    'update_time' => time(),
                ]);
                $jobData = ['thread' => $thread, 'target_merchant_id' => $target_merchant_id, 'target_create_time' => $target_create_time, 'queue_id' => $threadQueueId];
                Queue::push('\app\job\AssignMerchantThread@threadJob', $jobData, 'assignMerchantThreadAsynJob');
                Db::commit();
                return $this->success('提交成功');
            } else {
                Db::rollback();
                return $this->error('暂无分配数据');
            }
        } catch (\Exception $e) {
            Db::rollback();
            return $this->error($e->getMessage());
        }

    }

    //临时更新线索
    public function updateThread()
    {
        $threadList = \app\model\admin\Thread::with(['user' => function($query){
            $query->field('id,age_range_id,identity_id,education_id,is_has_computer_id,need_id,cuishou_zhuangtai,app_class_id,custom_fields');
        }])
            ->where('app_class_id',9)
            ->field('id,uid,app_class_id')
            ->select()
            ->toArray();
        $debtRangeList = \app\model\admin\GatherUserInfo::where('id',18)->find();
        $moneyRangeList = \app\model\admin\GatherUserInfo::where('id',19)->find();
        $debtRangeArr = $this->gatherUserListV1($debtRangeList);
        $moneyRangeArr = $this->gatherUserListV1($moneyRangeList);
        foreach($threadList as $item){
            $debtRangeName = '';
            $moneyRangeName = '';
            $customFields = explode(',',$item['user']['custom_fields']);
            $debtRange = array_values(array_intersect($debtRangeArr['gatherUserArr'],$customFields));
            $moneyRange = array_values(array_intersect($moneyRangeArr['gatherUserArr'],$customFields));
            if(!empty($debtRange) && isset($debtRange[0])){
                $debtRangeIds = explode('=',$debtRange[0]);
                $debtRangeName = isset($debtRangeArr['gatherNameArr'][$debtRangeIds[1]]) ? $debtRangeArr['gatherNameArr'][$debtRangeIds[1]] : '';
            }
            if(!empty($moneyRange) && isset($moneyRange[0])) {
                $moneyRangeIds = explode('=', $moneyRange[0]);
                $moneyRangeName = isset($moneyRangeArr['gatherNameArr'][$moneyRangeIds[1]]) ? $moneyRangeArr['gatherNameArr'][$moneyRangeIds[1]] : '';
            }
            \app\model\admin\Thread::where('id',$item['id'])->save(['debt_range' => $debtRangeName,'money_range' => $moneyRangeName]);
        }
    }

    public function gatherUserListV1($gatherUserList)
    {
        $gatherUserArr = [];
        $gatherNameArr = [];
        if(!empty($gatherUserList)) {
            $gatherInfoJson = json_decode($gatherUserList['gather_info_json'],true);
            foreach($gatherInfoJson as $val){
                $gatherUserArr[] = $gatherUserList['id'].'='.$val['id'];
                $gatherNameArr[$val['id']] = $val['name'];
            }
        }
        $data['gatherUserArr'] = $gatherUserArr;
        $data['gatherNameArr'] = $gatherNameArr;
        return $data;
    }

}