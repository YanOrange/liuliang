<?php

namespace app\controller\admin;

use laytp\controller\Backend;
use laytp\library\CommonFun;
use think\facade\Db;
use think\facade\Request;

/**
 * 后台应用控制器
 */
class LearnFeedback extends Backend
{
    protected $model;//当前模型对象
    protected $noNeedAuth = [''];

    protected function _initialize()
    {
        $this->model = new \app\model\admin\LearnFeedback();
    }
    //查看
    public function index()
    {
        $where = $this->buildSearchParams();
        $order = $this->buildOrder();
        $data = $this->model->with(['user' => function($query){
            $query->field('id,nickname,phone');
        },'channel' => function($query){
            $query->field('id,channel_name');
        }])->where($where)->order($order);
        $allData = $this->request->param('all_data');
        if ($allData) {
            $data = $data->select();
        } else {
            $limit = $this->request->param('limit', 10);
            $data = $data->paginate($limit)->toArray();
        }

        return $this->success('数据获取成功', $data);
    }

    //查看详情
    public function info()
    {
        $id = $this->request->param('id');
        $info = $this->model->findOrEmpty($id)->toArray();
        return $this->success('获取成功', $info);
    }

    //编辑
    public function edit()
    {
        $post     = CommonFun::filterPostData($this->request->post());
        Db::startTrans();
        try {
            $app = $this->model->findOrEmpty($post['id']);
            if (!$app) throw new \Exception('id参数错误');
            $updateRes  = $app->update($post);
            if (!$updateRes) throw new \Exception('数据库异常，操作失败');
            Db::commit();
            return $this->success('操作成功');
        } catch (\Exception $e) {
            Db::rollback();
            return $this->exceptionError($e);
        }
    }

    //设置微信授权状态
    public function setIsStatus()
    {
        $id       = $this->request->post('id');
        $update['status'] = 1;
        try {
            $updateRes = $this->model->where('id', '=', $id)->update($update);
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