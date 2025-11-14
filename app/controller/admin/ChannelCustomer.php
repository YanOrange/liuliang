<?php

namespace app\controller\admin;

use app\model\admin\Merchant;
use app\service\admin\AuthServiceFacade;
use app\service\admin\UserServiceFacade;
use app\validate\admin\gatheruserinfo\GatherUserInfo as GatherUserInfoValidate;
use app\model\admin\ChannelCustomer as ChannelCustomerModel;
use laytp\controller\Backend;
use laytp\library\Tree;
use think\facade\Db;

class ChannelCustomer extends Backend
{
    protected $model;//当前模型对象

    protected function _initialize()
    {
        $this->model = new ChannelCustomerModel;
    }

    //查看
    public function index()
    {
        $order = $this->buildOrder();
        $data = $this->model->with(['channel'])->order($order);
        $allData = $this->request->param('all_data');
        if ($allData) {
            $data = $data->select();
        } else {
            $limit = $this->request->param('limit', 10);
            $data = $data->paginate($limit)->toArray();
            foreach ($data['data'] as &$item) {
                $arr = \app\model\admin\Customer::whereIn('id', explode(',', $item['customer']))->field('nickname')->select()->toArray() ?? [];
                $item['customer'] = implode(',', array_column($arr, 'nickname'));
            }
        }

        return $this->success('数据获取成功', $data);
    }

    //获取客服列表
    public function getCustomerList(){
        $where = $this->buildSearchParams();
        $order = $this->buildOrder();
        $model = new \app\model\admin\Customer();

        // 干事：管理自己负责得商户 2022-09-02
        $loginUserInfo = UserServiceFacade::getUserInfo();
        $loginId = $loginUserInfo['id'];
        $roleIds = AuthServiceFacade::getAuthUserRole($loginId);

        if ($loginId != 1 && in_array(env('ROLE.GANSHI'), $roleIds)) {
            // 8 - 干事：管理自己负责的客户
            $merchantIds = Merchant::whereFindInSet('admin_ids', $loginId)->column('id');
            $where[] = ['merchant_id', 'in', $merchantIds];
        }
        $data = $model->with(['merchant'])->field('id,nickname,merchant_id')->where($where)->order($order);
        $limit = $this->request->param('limit', 10);
        $data = $data->select();

        foreach ($data as &$item) {
            $item['nickname'] = $item['merchant']['merchant_name'] . '-' . $item['nickname'];
        }
        return $this->success('数据获取成功', $data);
    }

    //添加
    public function add()
    {
        $post = $this->request->post();
        $this->model->save($post);
        return $this->success('添加成功');
    }

    //查看详情
    public function info()
    {
        $id   = $this->request->param('id');
        $info = $this->model->findOrEmpty($id)->toArray();
        $info['customer'] = explode(',', $info['customer']);
        return $this->success('获取成功', $info);
    }

    //编辑
    public function edit()
    {
        $post = $this->request->post();
        $this->model->update($post);
        return $this->success('编辑成功');
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
}