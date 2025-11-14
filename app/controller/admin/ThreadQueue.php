<?php

namespace app\controller\admin;

use laytp\controller\Backend;
use app\model\admin\AppClass;
use app\model\admin\Merchant;

class ThreadQueue extends Backend
{
    protected $model;//当前模型对象
    protected function _initialize()
    {
        $this->model = new \app\model\admin\ThreadQueue();
    }

    //查看
    public function index()
    {
        $where = $this->buildSearchParams();
        $order = $this->buildOrder();
        $data = $this->model->where($where)->order($order);
        $allData = $this->request->param('all_data');
        if ($allData) {
            $data = $data->select();
        } else {
            $limit = $this->request->param('limit', 10);
            $data = $data->paginate($limit)->toArray();
        }
        if(!empty($data['data'])){
            foreach($data['data'] as &$val){
                $val['content'] = !empty($val['content']) ? json_decode($val['content'],true) : '';
                if(!empty($val['content'])){
                    $val['app_class_name'] = AppClass::where('id',$val['content']['app_class_id'])->value('app_class_name');
                    $val['merchant_name'] = Merchant::where('id',$val['content']['target_merchant_id'])->value('merchant_name');
                    $val['thread_type'] = !empty($val['content']['thread_type']) ? $val['content']['thread_type'] : 0;
                    $val['num'] = $val['content']['num'];
                    $val['target_create_time'] = $val['content']['target_create_time'];
                }
            }
        }
        return $this->success('数据获取成功', $data);
    }

    //设置状态
    public function setStatus()
    {
        $id       = $this->request->post('id');
        $fieldVal = $this->request->post('field_val');
        $isRecycle = $this->request->post('is_recycle');
        $update['status'] = $fieldVal;
        try {
            if($isRecycle) {
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