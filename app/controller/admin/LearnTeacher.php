<?php

namespace app\controller\admin;

use app\service\admin\UserServiceFacade;
use app\validate\admin\learncourse\LearnTeacher as LearnTeacherValidate;
use laytp\controller\Backend;
use laytp\library\CommonFun;
use think\facade\Db;

/**
 * 后台应用控制器
 */
class LearnTeacher extends Backend
{
    protected $model;//当前模型对象

    protected function _initialize()
    {
        $this->model = new \app\model\admin\LearnTeacher();
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

    public function add()
    {
        $loginUserInfo = UserServiceFacade::getUserInfo();
        $loginId = $loginUserInfo['id'];
        $post     = CommonFun::filterPostData($this->request->post());
        $teacherTags = $this->request->post('teacher_tags');
        $validate = new LearnTeacherValidate();
        if (!$validate->scene('add')->check($post)) return $this->error($validate->getError());
        $post['admin_id'] = $loginId;
        $post['teacher_tags'] = json_encode($teacherTags);
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
        $info['teacher_tags'] = json_decode($info['teacher_tags'],true);
        return $this->success('获取成功', $info);
    }

    //编辑
    public function edit()
    {
        $post     = CommonFun::filterPostData($this->request->post());
        $teacherTags = $this->request->post('teacher_tags');
        $validate = new LearnTeacherValidate();
        if (!$validate->scene('add')->check($post)) return $this->error($validate->getError());
        $post['teacher_tags'] = json_encode($teacherTags);
        Db::startTrans();
        try {
            $app = $this->model->findOrEmpty($post['id']);
            if (!$app) throw new \Exception('id参数错误');
            $updateRes  = $app->update($post);
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

    //设置微信授权状态
    public function setIsStatus()
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

    //回收站
    public function recycle()
    {
        $where = $this->buildSearchParams();
        $order = $this->buildOrder();
        $whereCon = " 1 = 1";
        $isManyOrganization = $this->request->param('is_many_organization', 1);
        if ($isManyOrganization) {
            $whereCon.= " AND is_many_organization = {$isManyOrganization}";
        }
        $limit = $this->request->param('limit', 10);
        $data  = $this->model->onlyTrashed()
            ->with(['class'])
            ->order($order)->where($where)->paginate($limit)->toArray();
        return $this->success('回收站数据获取成功', $data);
    }
}