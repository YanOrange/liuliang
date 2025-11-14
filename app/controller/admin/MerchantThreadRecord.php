<?php

namespace app\controller\admin;

use laytp\controller\Backend;
use think\facade\Db;

class MerchantThreadRecord extends Backend
{
    protected $model;//当前模型对象
    protected function _initialize()
    {
        $this->model = new \app\model\admin\Thread();
    }
    //查看
    public function index()
    {
        $where = $this->buildSearchParams();
        $merchantId = $this->request->param('merchant_id');
        $whereCon = " merchant_id = {$merchantId} and is_test = 0 and delete_time = 0";
        if(empty($where)){
            $whereCon .= ' and create_time >= 1656604800';
        }else{
            $createTime = explode(',',$where[0][2]);
            $startTime = strtotime($createTime[0]);
            $endTime = strtotime($createTime[1]);
            $whereCon .= " and create_time between {$startTime} and {$endTime}";
        }

        $limit = $this->request->param('limit', 10);
        $page = $this->request->param('page', 1);
        $start = ($page - 1) * $limit;
        $totalSql = "select FROM_UNIXTIME(create_time,'%Y-%m-%d') as create_time1 from lt_thread where {$whereCon} GROUP BY thread_price,create_time1";
        $totalData = Db::query($totalSql);
        $sql = "select count(*) as total_thread,thread_price,sum(thread_price) as total_thread_price,FROM_UNIXTIME(create_time,'%Y-%m-%d') as create_time1 from lt_thread where {$whereCon} GROUP BY thread_price,create_time1 order by create_time desc limit {$start},{$limit}";
        $data['data'] = Db::query($sql);
        $data['current_page'] = $page;
        $data['per_page'] = $limit;
        $data['total'] = !empty($totalData) ? count($totalData) : 0;
        $data['last_page'] = $data['total'] > 0 ? $data['total']/$limit : 1;

        if(!empty($data['data'])) {
            foreach ($data['data'] as $key => &$val) {
                $val['id'] = $key + 1;
            }
        }
        return $this->success('数据获取成功', $data);
    }
}