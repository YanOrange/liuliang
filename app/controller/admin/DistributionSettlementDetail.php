<?php

namespace app\controller\admin;

use laytp\controller\Backend;
use laytp\library\CommonFun;
use think\facade\Db;

/**
 * 后台应用控制器
 */
class DistributionSettlementDetail extends Backend
{
    protected $model;//当前模型对象

    protected $noNeedAuth = ['*'];

    protected function _initialize()
    {
        $this->model = new \app\model\admin\DistributionSettlementDetail();
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

        return $this->success('数据获取成功', $data);
    }

}