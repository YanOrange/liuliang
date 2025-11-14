<?php

namespace app\controller\admin;

use laytp\controller\Backend;

class AppModule extends Backend
{
    protected $model;//当前模型对象
    protected function _initialize()
    {
        $this->model = new \app\model\admin\AppModule();
    }
    //查看
    public function index()
    {
        $where = $this->buildSearchParams();
        $order = $this->buildOrder();
        $whereCon = [];
        $isManyOrganization = $this->request->param('is_many_organization');
        if ($isManyOrganization && is_numeric($isManyOrganization)) {
            if($isManyOrganization == 3){
                $whereCon[] = ['id','in',[3,4,5]];
            }else{
                $whereCon[] = ['id','in',[1,2]];
            }
        }
        $data = $this->model->where($where)->where($whereCon)->order($order);
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