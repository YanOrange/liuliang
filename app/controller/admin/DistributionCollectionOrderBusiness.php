<?php

namespace app\controller\admin;

use app\model\admin\DistributionPartnerUser;
use app\validate\admin\distribution\DistributionCollectionOrder as DistributionCollectionOrderValidate;
use laytp\controller\Backend;
use laytp\library\CommonFun;
use think\facade\Db;

/**
 * 后台应用控制器
 */
class DistributionCollectionOrderBusiness extends Backend
{
    protected $model;//当前模型对象

    protected $noNeedAuth = ['*'];

    protected function _initialize()
    {
        $this->model = new \app\model\admin\DistributionCollectionOrderBusiness();
    }
    //查看
    public function index()
    {
        $order = $this->buildOrder();
        $data = $this->buildSearch()->with(['user' => function($query){
                $query->field('id,phone,create_time');
            },'merchant' => function($query){
                $query->field('id,merchant_name');
            },'customer' => function($query){
                $query->field('id,nickname');
            },'channel' => function($query){
            $query->field('id,channel_name');
        }])
            ->where('pay_amount','>',0)
            //->field('id,uid,merchant_id,customer_id,pay_amount,pay_time,pay_amount')
            ->order($order);
        $allData = $this->request->param('all_data');
        if ($allData) {
            $data = $data->select();
        } else {
            $limit = $this->request->param('limit', 10);
            $data = $data->paginate($limit)->toArray();
        }
        if(!empty($data['data'])){
            foreach($data['data'] as &$item){
                $payAmountOrigin = $item['pay_amount_origin'];
                $payAmount = $item['pay_amount'];
                $item['pay_amount_origin'] = $payAmountOrigin > 0 ? $payAmountOrigin : $payAmount;
                $item['pay_amount'] = $payAmountOrigin > 0 ? $payAmount : '';
            }
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

        $this->model = $this->model->withJoin(['user'], 'inner');
        if (isset($phone) && !empty($phone)) {
            $this->model = $this->model->where('user.phone', '=', $phone);
        }
        if (isset($channel_id) && !empty($channel_id)) {
            $this->model = $this->model->where($tableName.'.channel_id', '=', $channel_id);
        }else{
            $channelIds = $this->getPartnerUserChannel();
            $this->model = $this->model->where($tableName.'.channel_id', 'in', $channelIds);
        }
        if (isset($create_time) && !empty($create_time)) {
            list($startTime, $endTime) = explode(' - ', $create_time);
            $this->model = $this->model->where('user.create_time', 'between', strtotime($startTime) . ',' . strtotime($endTime));
        }
        if (isset($pay_time) && !empty($pay_time)) {
            list($startTime, $endTime) = explode(' - ', $pay_time);
            $this->model = $this->model->where($tableName.'.pay_time', 'between', strtotime($startTime) . ',' . strtotime($endTime));
        }

        return $this->model;
    }

    //获取分销人渠道
    public function getPartnerUserChannel()
    {
        $channelIds = DistributionPartnerUser::where('status',1)->group('channel_id')->column('channel_id');
        return $channelIds;
    }

    //查看详情
    public function info()
    {
        $id   = $this->request->param('id');
        $info = $this->model->findOrEmpty($id)->toArray();
        return $this->success('获取成功', $info);
    }

    //编辑
    public function edit()
    {
        $post     = CommonFun::filterPostData($this->request->post());

        $validate = new DistributionCollectionOrderValidate();
        if (!$validate->scene('edit')->check($post)) return $this->error($validate->getError());
        $post['pay_time'] = strtotime($post['pay_time']);
        Db::startTrans();
        try {
            $collectionOrder = $this->model->findOrEmpty($post['id']);
            if($post['pay_amount'] != $collectionOrder['pay_amount']){
                $post['pay_amount_origin'] = $collectionOrder['pay_amount'];
            }
            if($post['pay_time'] != $collectionOrder['pay_time']){
                $post['pay_time_origin'] = strtotime($collectionOrder['pay_time']);
            }
            $updateRes  = $collectionOrder->update($post);
            if (!$updateRes) throw new \Exception('数据库异常，操作失败');
            Db::commit();
            return $this->success('操作成功');
        } catch (\Exception $e) {
            Db::rollback();
            return $this->exceptionError($e);
        }
    }
}