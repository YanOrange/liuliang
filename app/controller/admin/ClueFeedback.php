<?php

namespace app\controller\admin;

use app\model\admin\ClueMessage;
use app\service\admin\UserServiceFacade;
use app\validate\admin\app\App as AppValidate;
use laytp\controller\Backend;
use laytp\library\CommonFun;
use think\facade\Db;
use app\model\admin\UserListExternal;
use app\model\admin\ThreadExternal;
use app\model\admin\Thread;
use app\model\admin\Merchant;
use app\model\admin\ClueProblem;
use app\lib\api\wx\Robot;
/**
 * 后台应用控制器
 */
class ClueFeedback extends Backend
{
    protected $model;//当前模型对象

    protected $noNeedAuth = ['getClueFeedback'];

    protected function _initialize()
    {
        $this->model = new \app\model\admin\ClueFeedback();
    }
    //查看
    public function index()
    {
        //$where = $this->buildSearchParams();
        $order = $this->buildOrder();
        //$where[] = ['feedback_status','in',[10,11]];
        $data = $this->buildSearch()->with(['threadExternal','userExternal','merchant','subMerchant','subCustomer','problem','auditorOne','auditorTwo'])->order($order);
        $allData = $this->request->param('all_data');
        if ($allData) {
            $data = $data->select();
        } else {
            $limit = $this->request->param('limit', 10);
            $data = $data->paginate($limit)->each(function($item){
                if($item['submitter_terminal'] == 1){
                    $item['subMerchant'] = [
                        'merchant_name' => $item['subCustomer']['nickname']
                    ];
                }
            })->toArray();
        }
        return $this->success('数据获取成功', $data);
    }

    public function getClueFeedback()
    {
        $where[] = ['status','=',1];
        $data = ClueProblem::where($where)->field('id,title');
        $allData = $this->request->param('all_data');
        if ($allData) {
            $data = $data->select();
        } else {
            $limit = $this->request->param('limit', 10);
            $data = $data->paginate($limit)->toArray();
        }
        return $this->success('数据获取成功', $data);
    }

    // 构建查询条件
    private function buildSearch()
    {
        $filter = $this->request->param('search_param');
        $filter = !empty($filter) ? $filter : [];
        extract($filter);
        $name = $this->model->getName();
        $tableName = env('database.prefix') . $name;

        if (isset($feedback_status) && !empty($feedback_status)) {
            $this->model = $this->model->where($tableName . '.feedback_status', '=', $feedback_status);
        }else{
            $this->model = $this->model->where($tableName . '.feedback_status', '=', 10);
        }
        if (isset($nickname) && !empty($nickname)) {
            $uid = UserListExternal::where('nickname',$nickname)->value('id');
            $this->model = $this->model->where($tableName . '.thread_external_uid', '=', $uid);
        }
        if (isset($wx_nickname) && !empty($wx_nickname)) {
            $uid = UserListExternal::where('wx_nickname',$wx_nickname)->value('id');
            $this->model = $this->model->where($tableName . '.thread_external_uid', '=', $uid);
        }
        if (isset($phone) && !empty($phone)) {
            $uid = UserListExternal::where('phone',$phone)->value('id');
            $this->model = $this->model->where($tableName . '.thread_external_uid', '=', $uid);
        }
        if (isset($clue_problem_id) && !empty($clue_problem_id)) {
            $this->model = $this->model->where($tableName . '.clue_problem_id', '=', $clue_problem_id);
        }

        if (isset($thread_create_time) && !empty($thread_create_time)) {
            $this->model = $this->model->withJoin(['threadExternal'], 'inner');
            list($startTime, $endTime) = explode(' - ', $thread_create_time);
            $this->model = $this->model->where('threadExternal.create_time', 'between', strtotime($startTime) . ',' . strtotime($endTime));
        }

        if (isset($create_time) && !empty($create_time)) {
            list($startTime, $endTime) = explode(' - ', $create_time);
            $this->model = $this->model->where($tableName . '.create_time', 'between', $startTime . ',' . $endTime);
        }

        return $this->model;
    }

    //查看详情
    public function info()
    {
        $id   = $this->request->param('id');
        $info = $this->model->with(['threadExternal','userExternal','subMerchant','subCustomer','problem'])->findOrEmpty($id)->toArray();
        if($info['submitter_terminal'] == 1){
            $info['subMerchant']['merchant_name'] = $info['subCustomer']['nickname'];
        }
        return $this->success('获取成功', $info);
    }

    //编辑
    public function edit()
    {
        $post     = CommonFun::filterPostData($this->request->post());
        $loginUserInfo = UserServiceFacade::getUserInfo();
        $loginId = $loginUserInfo['id'];
        //$validate = new AppValidate();
        //if (!$validate->scene('edit')->check($post)) return $this->error($validate->getError());
        Db::startTrans();
        try {
            $feedback = $this->model->findOrEmpty($post['id']);
            if (!$feedback) throw new \Exception('id参数错误');
            $post['auditor_one'] = $loginId;
            $post['auditor_one_time'] = time();
            $updateRes  = $feedback->update($post);
            //var_dump($feedback);die;
            if (!$updateRes) throw new \Exception('数据库异常，操作失败');
           // var_dump($post['feedback_status']);die;
            $threadExternalInfo = ThreadExternal::find($feedback->thread_external_id);
            if (empty($threadExternalInfo)) {
                throw new \Exception('记录不存在');
            }
            $threadInfo = Thread::find($threadExternalInfo->inside_thread_id);
            if (empty($threadInfo)) {
                throw new \Exception('记录不存在');
            }
            $merchantName = Merchant::where('id', $threadExternalInfo->merchant_id)->value('merchant_name');
            $message = $post['feedback_status'] == 20 ? '已通过' : '未通过';
            if($post['feedback_status'] == 11){
                ClueMessage::create([
                    'thread_external_id' => $feedback->thread_external_id,
                    'merchant_id' => $feedback->merchant_id,
                    'admin_id' => $loginId,
                    'content' => '您提交的无效线索，线索手机号：'.$feedback->userExternal->phone.'管理员已审核'.$message,
                    'recipient_id' => $feedback->submitter,
                    'submitter_terminal' => $feedback->submitter_terminal
                ]);
                ThreadExternal::where('id',$feedback->thread_external_id)->update(['feedback_status' => 3]);
            }
            Db::commit();
            if ($post['feedback_status'] == 20) {
                Robot::robotMsg("你有一条{$merchantName}线索待审核", env('WXROOT.AUDIT_THREAD_URL'), true, '17358044876');
            }
            return $this->success('操作成功');
        } catch (\Exception $e) {
            Db::rollback();
            return $this->exceptionError($e);
        }
    }
}