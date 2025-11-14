<?php

namespace app\controller\admin;

use app\service\admin\UserServiceFacade;
use app\validate\admin\promotionmethod\PromotionMethod as PromotionMethodValidate;
use laytp\controller\Backend;
use laytp\library\CommonFun;
use laytp\library\Str;
use think\facade\Config;
use think\facade\Db;
use think\facade\Request;

/**
 * 后台投放模式控制器
 */
class PromotionPlatform extends Backend
{
    protected $model;//当前模型对象
    protected function _initialize()
    {
        $this->model = new \app\model\admin\PromotionPlatform();
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
        $post = CommonFun::filterPostData($this->request->post());
        $validate = new PromotionMethodValidate();
        if (!$validate->scene('add')->check($post)) return $this->error($validate->getError());
        $loginUserInfo = UserServiceFacade::getUserInfo();
        $post['admin_id'] = $loginUserInfo['id'];
        Db::startTrans();
        try {
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
        $post = CommonFun::filterPostData($this->request->post());

        $validate = new PromotionMethodValidate();
        if (!$validate->scene('edit')->check($post)) return $this->error($validate->getError());
        $loginUserInfo = UserServiceFacade::getUserInfo();
        $post['admin_id'] = $loginUserInfo['id'];
        Db::startTrans();
        try {
            $banner = $this->model->findOrEmpty($post['id']);
            if (!$banner) throw new \Exception('id参数错误');
            $updateRes  = $banner->update($post);
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

    //回收站
    public function recycle()
    {
        $where = $this->buildSearchParams();
        $order = $this->buildOrder();
        $limit = $this->request->param('limit', 10);
        $data  = $this->model->onlyTrashed()
            ->order($order)->where($where)->where('type', 1)->paginate($limit)->toArray();
        return $this->success('回收站数据获取成功', $data);
    }
}