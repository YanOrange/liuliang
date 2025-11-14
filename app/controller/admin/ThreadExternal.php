<?php

namespace app\controller\admin;

use app\model\admin\api\Customer;
use app\model\admin\thread\ThreadSource;
use app\model\admin\thread\ThreadUserStatus;
use app\model\admin\UserListExternal;
use app\service\admin\AuthServiceFacade;
use app\service\admin\UserServiceFacade;
use laytp\controller\Backend;
use think\facade\Session;

/**
 * 后台线索控制器
 */
class ThreadExternal extends Backend
{
    protected $model;//当前模型对象

    protected $noNeedAuth = ['*'];
    protected $noNeedLogin = [''];

    protected $identityList = ["-", "学生", "职场", "自由职业", "全职宝妈", "公职职业编"];
    protected $educationList = ["-", "高中以下", "高中及职高", "大专", "本科及以上"];

    protected function _initialize()
    {
        $this->model = new \app\model\admin\ThreadExternal();
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
        $userInfo = [];
        $id = $this->request->param('id');
        $thread = $this->model->field('uid,external_uid,merchant_id,is_discern_qrcode,province,city,create_time,source_id,status')->where('id', $id)->find();
        if (!empty($thread)) {
            $userInfo = UserListExternal::where('id', $thread['external_uid'])->find();
            $userInfo['is_discern_qrcode'] = $thread['is_discern_qrcode'] ? '是' : '否';//是否长按二维码,
            $source_title = ' - ';
            $source = ThreadSource::field('title')->where('id', $thread['source_id'])->find();
            if (!empty($source)) {
                $source_title = $source['title'];
            }
            if ($loginUserInfo['is_show_phone'] == 0) {
                $userInfo['phone'] = substr_replace($userInfo['phone'], '****', 3, 4);
            }

            $userInfo['source_title'] = $source_title;
            $userInfo['thread_create_time'] = $thread['create_time'];
            $userInfo['thread_status'] = $thread['status'];
            $userInfo['thread_source_id'] = $thread['source_id'];
        }
        return $this->success('数据获取成功', ['base_info' => $userInfo]);
    }


    //查看
    public function index()
    {
        $loginUserInfo = UserServiceFacade::getUserInfo();
        $loginId = $loginUserInfo['id'];
        $roleIds = AuthServiceFacade::getAuthUserRole($loginId);
        // $where = $this->buildSearchParams();
        $order = $this->buildOrder();
        $data = $this->buildSearch(false)->with(['merchant', 'customer', 'userExternal', 'threadSource','userStatus'=>function($query){
            $query->with(['followAction','parentFollowAction']);
        }])->order('create_time desc');
        $allData = $this->request->param('all_data');
        if ($allData) {
            $data = $data->select();
        } else {
            $limit = $this->request->param('limit', 10);
            $data = $data->paginate($limit)->toArray();
        }
        foreach($data['data'] as $key => &$val){
            if ($loginUserInfo['is_show_phone'] == 0) {

            $val['userExternal']['phone'] = isset($val['userExternal']['phone']) && !empty($val['userExternal']['phone']) ? substr_replace($val['userExternal']['phone'], '****', 3, 4) : '';
            }
            //查询用户状态列表信息
            $val['follow_action'] = ThreadUserStatus::getThreadUserStatusText($val['userStatus']);
        }
        $filter = $this->request->param('search_param');
        $filter = !empty($filter) ? $filter : [];
        Session::set('thread_con', json_encode($filter));
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
      //  $threadModel->whereIn($tableName . '.is_hidden_test', [0,1]);
        if (isset($merchant_id) && !empty($merchant_id)) {
            $merchant_id = explode(',', $merchant_id);
            $threadModel = $threadModel->where($tableName . '.merchant_id', 'in', $merchant_id);
        }
        if (isset($source_id) && !empty($source_id)) {
            $threadModel = $threadModel->where($tableName . '.source_id', '=', $source_id);
        }
        if (isset($customer_id) && !empty($customer_id)) {
            $threadModel = $threadModel->where($tableName . '.customer_id', '=', $customer_id);
        }
        if ((isset($phone) && !empty($phone)) ||
            (isset($user_nickname) && !empty($user_nickname)) ||
            (isset($user_wx_nickname) && !empty($user_wx_nickname))
        ) {
            $threadModel = $threadModel->withJoin(['userExternal'], 'inner');
            if (isset($phone) && !empty($phone)) {
                $threadModel = $threadModel->where('userExternal.phone', '=', $phone);
            }
            if (isset($user_nickname) && !empty($user_nickname)) {
                $threadModel = $threadModel->where('userExternal.nickname', '=', $user_nickname);
            }
            if (isset($user_wx_nickname) && !empty($user_wx_nickname)) {
                $threadModel = $threadModel->where('userExternal.wx_nickname', '=', $user_wx_nickname);
            }
        }
        if (isset($create_time) && !empty($create_time)) {
            list($startTime, $endTime) = explode(' - ', $create_time);
            $threadModel = $threadModel->where($tableName . '.create_time', 'between', strtotime($startTime) . ',' . strtotime($endTime));
        }

        return $threadModel;
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
        //$data = $this->buildSearch(false, $whereCon)->with(['merchant','course','customer','user','channelpro','app','class'])->group('merchant_id,uid')->order('id desc')->select();
        $data = $this->buildSearch(false, $whereCon)->with(['merchant', 'customer', 'userExternal', 'threadSource'])->order('id desc')->select();
        fputcsv($fp, ['ID', '商户','用户昵称','用户微信昵称', '用户手机号','来源', '备注', '线索描述', '管理员备注', '阶段','用户年龄','学历','身份','客服昵称', '客服微信号', '省份', '城市', '添加时间']);
        foreach ($data as $val) {
            $status_text = '-';
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
            $item = [
                $val->id,
                $val->merchant->merchant_name ?? '',
                $val->userExternal->nickname ?? '',
                $val->userExternal->wx_nickname ?? '',
                isset($val['userExternal']['phone']) && !empty($val['userExternal']['phone']) ? substr_replace($val['userExternal']['phone'], '****', 3, 4) : $val['userExternal']['phone'],
                $val->threadSource->title??'',
                $val->remarks,
                $val->textarea,
                $val->admin_remarks,
                $status_text,
                $val->userExternal->age ?? 0,
                $val->user->education ?? '',
                $val->user->identity ?? '',
                $val->customer->nickname ?? '',
                $val->customer->wechat_number ?? '',
                $val->province ?? '',
                $val->city ?? '',
                $val->create_time,
            ];
            fputcsv($fp, $item);
        }
        ob_flush();
        flush();
    }

    /**
     * 设置管理员备注
     *
     * @return void
     */
    public function setAdminRemarks()
    {
        $id = $this->request->post('id');
        $fieldVal = $this->request->post('field_val');
        $isRecycle = $this->request->post('is_recycle');
        $update['admin_remarks'] = $fieldVal;
        try {
            if ($isRecycle) {
                $updateRes = $this->model->onlyTrashed()->where('id', '=', $id)->update($update);
            } else {
                $updateRes = $this->model->where('id', '=', $id)->update($update);
            }
            if ($updateRes) {
                return $this->success('操作成功');
            } else if ($updateRes === 0) {
                return $this->success('未作修改');
            } else {
                return $this->error('操作失败');
            }
        } catch (\Exception $e) {
            return $this->error('数据库异常，操作失败');
        }
    }
}