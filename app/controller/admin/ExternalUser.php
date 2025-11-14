<?php

namespace app\controller\admin;

use app\model\admin\login\Log;
use app\service\admin\AuthServiceFacade;
use app\service\admin\UserServiceFacade;
use app\validate\admin\externaluser\Add;
use app\validate\admin\externaluser\Edit;
use laytp\controller\Backend;
use laytp\library\CommonFun;
use laytp\library\Random;
use laytp\library\Str;
use laytp\library\Token;
use think\facade\Config;
use think\facade\Db;
/**
 * 后台管理员控制器
 */
class ExternalUser extends Backend
{
    protected $model;//当前模型对象

    protected function _initialize()
    {
        $this->model = new \app\model\admin\ExternalUser();
    }
    //查看
    public function index()
    {
        $where = $this->buildSearchParams();
        $order = $this->buildOrder();
        $data = $this->model->where($where)->with(['avatar_file'])->order($order);
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
        Db::startTrans();
        try {
            $post     = CommonFun::filterPostData($this->request->post());
            $validate = new Add();
            if (!$validate->check($post)) return $this->error($validate->getError());

            $post['password'] = Str::createPassword($post['password']);
            $saveRes = $this->model->save($post);
            if (!$saveRes) throw new \Exception('保存基础信息失败');
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
        $data = $this->model->with(['avatar_file'])->findOrEmpty($id)->toArray();
        return $this->success('获取成功', $data);
    }

    //编辑
    public function edit()
    {
        Db::startTrans();
        try {
            $post = CommonFun::filterPostData($this->request->post());
            $user = $this->model->findOrEmpty($post['id']);
            if (!$user) throw new \Exception('id参数错误');

            $validate = new Edit();
            if (!$validate->check($post)) return $this->error($validate->getError());
            if ($post['password']) {
                $post['password'] = Str::createPassword($post['password']);
            } else {
                unset($post['password']);
                unset($post['re_password']);
            }
            $updateRes  = $user->update($post);
            if (!$updateRes) throw new \Exception('保存基本信息失败');
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
        if (in_array(1, $ids)) {
            return $this->error('不允许删除初始化用户');
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

    //设置状态
    public function setStatus()
    {
        $id       = $this->request->post('id');
        $fieldVal = $this->request->post('field_val');
        $isRecycle = $this->request->post('is_recycle');
        $update['status'] = $fieldVal;
        try {
            if($isRecycle) {
                $updateRes = $this->model->onlyTrashed()->where('id', '=', $id)->update($update);
            } else {
                $updateRes = $this->model->where('id', '=', $id)->update($update);
            }
            if ($updateRes) {
                return $this->success('操作成功');
            } else if ($updateRes === 0) {
                return $this->success('未作修改');
            } else {
                return $this->error('操作失败');
            }
        } catch (\Exception $e) {
            return $this->error('数据库异常，操作失败');
        }
    }
}