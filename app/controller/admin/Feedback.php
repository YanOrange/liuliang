<?php

namespace app\controller\admin;

use app\model\admin\login\Log;
use app\service\admin\AuthServiceFacade;
use app\service\admin\UserServiceFacade;
use laytp\controller\Backend;
use laytp\library\CommonFun;
use laytp\library\Str;
use think\facade\Config;
//use think\facade\Db;
use think\Db;
/**
 * 后台意见反馈控制器
 */
class Feedback extends Backend
{
    protected $model;//当前模型对象
    protected function _initialize()
    {
        $this->model = new \app\model\admin\Feedback();
    }
    //查看
    public function index()
    {

        $order = $this->buildOrder();
        $data = $this->buildSearch()->with(['user' => function($query) {
            $query->with(['channelpro', 'app', 'class']);
        }])->order($order);
        $allData = $this->request->param('all_data');
        if ($allData) {
            $data = $data->select();
        } else {
            $limit = $this->request->param('limit', 10);
            $data = $data->paginate($limit);
        }
        return $this->success('数据获取成功', $data);
    }
    //删除
    public function del()
    {
        $ids = array_filter($this->request->param('ids'));
        if (!$ids) {
            return $this->error('参数ids不能为空');
        }
        try{
            if ($this->model->destroy($ids)) {
                return $this->success('数据删除成功');
            } else {
                return $this->error('数据删除失败');
            }
        }catch (\Exception $e){
            return $this->exceptionError($e);
        }
    }

    // 构建查询条件
    private function buildSearch($isDelete = false)
    {
        $filter = $this->request->param('search_param') ?? [];
        extract($filter);
        $name = $this->model->getName();
        $tableName = env('database.prefix') . $name;
        if ($isDelete) {
            $feedbackModel = $this->model->onlyTrashed();
        } else {
            $feedbackModel = $this->model;
        }
        if (isset($id) && !empty($id)) {
            $feedbackModel = $feedbackModel->where('id', '=', $id);
        }
        if (isset($contact) && !empty($contact)) {
            $feedbackModel = $feedbackModel->where('contact', '=', $contact);
        }
        if (isset($create_time) && !empty($create_time)) {
            list($startTime, $endTime) = explode(' - ', $create_time);
            $feedbackModel = $feedbackModel->where('create_time', 'between', strtotime($startTime). ','. strtotime($endTime));
        }
        if (isset($nickname) && !empty($nickname)) {
            $feedbackModel = $feedbackModel->whereExists(function ($query) use ($tableName, $nickname) {
                $userListTableName = (new \app\model\admin\UserList())->getName();
                $query = $query->table(env('database.prefix') .$userListTableName)->where(env('database.prefix') . $userListTableName . '.id=' .   $tableName . '.uid');
                $query = $query->where('nickname', '=', $nickname);
                return $query;
            });
        }
        if (isset($phone) && !empty($phone)) {
            $feedbackModel = $feedbackModel->whereExists(function ($query) use ($tableName, $phone) {
                $userListTableName = (new \app\model\admin\UserList())->getName();
                $query = $query->table(env('database.prefix') . $userListTableName)->where(env('database.prefix') . $userListTableName . '.id=' .   $tableName . '.uid');
                $query = $query->where('phone', '=', $phone);
                return $query;
            });
        }
        return $feedbackModel;
    }
    //回收站
    public function recycle()
    {
        $order = $this->buildOrder();
        $limit = $this->request->param('limit', 10);
        $data  = $this->buildSearch(true)
            ->with(['user'])
            ->order($order)->paginate($limit);
        return $this->success('回收站数据获取成功', $data);
    }

}