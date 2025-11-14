<?php

namespace app\controller\admin;

use laytp\controller\Backend;

class UserPageEvent extends Backend
{
    protected $model;//当前模型对象
    protected function _initialize()
    {
        $this->model = new \app\model\admin\PointData();
    }

    //查看
    public function index()
    {
        $where = $this->buildSearchParams();
        $order = $this->buildOrder();
        $whereCon = [];
        $uid = $this->request->param('id',0);
        if($uid){
            $whereCon[] = ['uid','=',$uid];
        }
        $data = $this->model->with(['userPage','userEvent'])->where($where)->where($whereCon)->field('id,uid,event_id,page_id,create_time')->order($order);
        $allData = $this->request->param('all_data');
        if ($allData) {
            $data = $data->select();
        } else {
            $limit = $this->request->param('limit', 10);
            $data = $data->paginate($limit)->toArray();
        }
        return $this->success('数据获取成功', $data);
    }
}