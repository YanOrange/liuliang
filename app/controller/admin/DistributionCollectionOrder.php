<?php

namespace app\controller\admin;

use app\model\admin\DistributionPartnerUser;
use laytp\controller\Backend;
use laytp\library\CommonFun;
use think\facade\Db;

/**
 * 后台应用控制器
 */
class DistributionCollectionOrder extends Backend
{
    protected $model;//当前模型对象

    protected $noNeedAuth = ['*'];

    protected function _initialize()
    {
        $this->model = new \app\model\admin\DistributionCollectionOrder();
    }
    //查看
    public function index()
    {
        $channelIds = $this->getPartnerUserChannel();
        $where[] = ['channel_id','in',$channelIds];
        $order = $this->buildOrder();
        $data = $this->model->with(['user' => function($query){
                $query->field('id,phone,create_time');
            },'merchant' => function($query){
                $query->field('id,merchant_name');
            },'customer' => function($query){
                $query->field('id,nickname');
            }])
            ->where($where)
            ->where('pay_amount','>',0)
            ->field('id,uid,merchant_id,customer_id,pay_amount,pay_time,count(id) as pay_num,sum(pay_amount) as pay_total_amount')
            ->order($order)->group('uid');
        $allData = $this->request->param('all_data');
        if ($allData) {
            $data = $data->select();
        } else {
            $limit = $this->request->param('limit', 10);
            $data = $data->paginate($limit)->toArray();
        }

        return $this->success('数据获取成功', $data);
    }

    //获取分销人渠道
    public function getPartnerUserChannel()
    {
        $channelIds = DistributionPartnerUser::where('status',1)->group('channel_id')->column('channel_id');
        return $channelIds;
    }

    //查看
    public function detail()
    {
        $uid = $this->request->param('uid');
        $where = [];
        if(!empty($uid)){
            $where[] = ['uid','=',$uid];
        }
        //$where = $this->buildSearchParams();
        $order = $this->buildOrder();
        $data = $this->model->with(['user' => function($query){
            $query->field('id,phone,create_time');
            }])
            ->where($where)
            ->where('pay_amount','>',0)
            ->field('id,uid,pay_amount,pay_time,pay_amount')
            ->order($order);
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