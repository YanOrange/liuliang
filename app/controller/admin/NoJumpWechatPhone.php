<?php

namespace app\controller\admin;

use app\service\admin\UserServiceFacade;
use app\validate\admin\nojumpwechatphone\NoJumpWechatPhone as NoJumpWechatPhoneValidate;
use laytp\controller\Backend;
use laytp\library\CommonFun;
use think\facade\Db;

class NoJumpWechatPhone extends Backend
{
    protected $model;//当前模型对象

    protected $noNeedAuth = [''];

    protected function _initialize()
    {
        $this->model = new \app\model\admin\NoJumpWechatPhone();
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

    //添加
    public function add()
    {
        $loginUserInfo = UserServiceFacade::getUserInfo();
        $post     = CommonFun::filterPostData($this->request->post());
        if(empty($post['phone']) && empty($post['wx_nickname'])){
            return $this->error('两者选填一项');
        }
        //$validate = new NoJumpWechatPhoneValidate();
        //if (!$validate->scene('add')->check($post)) return $this->error($validate->getError());
        Db::startTrans();
        try {
            $post['admin_id'] = $loginUserInfo['id'];
            $saveRes = $this->model->save($post);
            if (!$saveRes) throw new \Exception('数据库异常，操作失败');
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
        return $this->success('获取成功', $info);
    }

    //编辑
    public function edit()
    {
        $loginUserInfo = UserServiceFacade::getUserInfo();
        $post     = CommonFun::filterPostData($this->request->post());
        if(empty($post['phone']) && empty($post['wx_nickname'])){
            return $this->error('两者选填一项');
        }
        //$validate = new NoJumpWechatPhoneValidate();
        //if (!$validate->scene('edit')->check($post)) return $this->error($validate->getError());
        Db::startTrans();
        try {
            $post['admin_id'] = $loginUserInfo['id'];
            $article = $this->model->findOrEmpty($post['id']);
            if (!$article) throw new \Exception('id参数错误');
            $updateRes  = $article->update($post);
            if (!$updateRes) throw new \Exception('数据库异常，操作失败');
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
                return $this->success('数据删除成功');
            } else {
                return $this->error('数据删除失败');
            }
        }catch (\Exception $e){
            return $this->exceptionError($e);
        }
    }

}