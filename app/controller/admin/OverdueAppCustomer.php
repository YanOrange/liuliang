<?php

namespace app\controller\admin;

use app\validate\admin\customer\OverdueAppCustomer as OverdueAppCustomerValidate;
use app\model\admin\Customer;
use app\model\admin\Channel;
use laytp\controller\Backend;
use laytp\library\CommonFun;
use think\facade\Db;
use think\facade\Event;

/**
 * 后台应用分类控制器
 */
class OverdueAppCustomer extends Backend
{
    protected $model;//当前模型对象
    protected $noNeedAuth = ['getChannelList','getChannelList'];
    protected function _initialize()
    {
        $this->model = new \app\model\admin\OverdueAppCustomer();
    }
    //查看
    public function index()
    {
        $where = $this->buildSearchParams();
        $order = $this->buildOrder();
        $chanenlId = $this->request->get('channel_id');
        $data = $this->model->where($where)->where('channel_id',$chanenlId)->order($order);
        $allData = $this->request->param('all_data');
        if ($allData) {
            $data = $data->select();
        } else {
            $limit = $this->request->param('limit', 10);
            $data = $data->paginate($limit)->toArray();
        }
        return $this->success('数据获取成功', $data);
    }

    //获取销售列表
    public function getCustomerList()
    {
        $where = $this->buildSearchParams();
        $order = $this->buildOrder();
        $model = new \app\model\admin\Customer();
        $data = $model->field('id,merchant_id,nickname as name')->where($where)->whereIn('merchant_id',[142,195,229,242])->order($order);
        $allData = $this->request->param('all_data');
        if ($allData) {
            $data = $data->select();
        } else {
            $limit = $this->request->param('limit', 10);
            $data = $data->paginate($limit)->toArray();
        }
        foreach($data as &$item){
            if($item['merchant_id'] == 142) $item['name'] = $item['name'].' - '.'山之名';
            if($item['merchant_id'] == 195) $item['name'] = $item['name'].' - '.'再无债';
            if($item['merchant_id'] == 229) $item['name'] = $item['name'].' - '.'国之良';
            if($item['merchant_id'] == 242) $item['name'] = $item['name'].' - '.'新国之良';
        }
        return $this->success('数据获取成功', $data);
    }

    //添加
    public function add()
    {
        $post     = CommonFun::filterPostData($this->request->post());
        $validate = new OverdueAppCustomerValidate();
        if (!$validate->scene('add')->check($post)) return $this->error($validate->getError());
        Db::startTrans();
        try {
            $channelInfo = Channel::where('id',$post['channel_id'])->with(['app' => function($query){
                $query->field('id,app_class_id');
            }])->field('id,channel_name,app_id')->find();
            $post['app_id'] = $channelInfo['app_id'];
            $post['app_class_id'] = $channelInfo['app']['app_class_id'];
            $post['channel'] = $channelInfo['channel_name'];
            $saveRes = $this->model->save($post);
            if (!$saveRes) throw new \Exception('数据库异常，操作失败');
            Event::trigger('ChannelCustomerAdd', [
                'ChannelCustomer' => $this->model->find($this->model->id),
            ]);
            Db::commit();
            return $this->success('操作成功');
        } catch (\Exception $e) {
            Db::rollback();
            return $this->error('数据库异常，操作失败');
        }
    }

    //查看详情
    public function info()
    {
        $id   = $this->request->param('id');
        $info = $this->model->findOrEmpty($id)->toArray();
        $customerIds = explode(',',$info['customer_ids']);
        $customer = Customer::whereIn('id',$customerIds)->column('nickname');
        $info['nickname'] = implode(',',$customer);
        return $this->success('获取成功', $info);
    }

    //编辑
    public function edit()
    {
        $post     = CommonFun::filterPostData($this->request->post());
        $validate = new OverdueAppCustomerValidate();
        if (!$validate->scene('edit')->check($post)) return $this->error($validate->getError());
        Db::startTrans();
        try {
            $channelCus = $this->model->findOrEmpty($post['id']);
            if (!$channelCus) throw new \Exception('id参数错误');
            $channelInfo = Channel::where('id',$channelCus['channel_id'])->with(['app' => function($query){
                $query->field('id,app_class_id');
            }])->field('id,channel_name,app_id')->find();
            $post['app_id'] = $channelInfo['app_id'];
            $post['app_class_id'] = $channelInfo['app']['app_class_id'];
            $post['channel'] = $channelInfo['channel_name'];
            $updateRes  = $channelCus->update($post);
            if (!$updateRes) throw new \Exception('数据库异常，操作失败');
            Event::trigger('ChannelCustomerEdit', [
                'ChannelCustomer' => $this->model->find($updateRes->id),
            ]);
            Db::commit();
            return $this->success('操作成功');
        } catch (\Exception $e) {
            Db::rollback();
            return $this->exceptionError($e);
        }
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
                Event::trigger('ChannelCustomerDel', [
                    'ChannelCustomerIds' => $ids,
                ]);
                return $this->success('数据删除成功');
            } else {
                return $this->error('数据删除失败');
            }
        }catch (\Exception $e){
            return $this->exceptionError($e);
        }
    }
}