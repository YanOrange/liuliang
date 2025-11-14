<?php

namespace app\controller\admin;

use app\model\admin\AdvertisementPlatformCostData;
use app\service\admin\UserServiceFacade;
use laytp\controller\Backend;
/**
 * 后台用户控制器
 */
class OverviewOfLaunch extends Backend
{
    protected $model;//当前模型对象
    protected function _initialize()
    {
        $this->model = new AdvertisementPlatformCostData();
    }
    //查看
    public function index()
    {
        $loginUserInfo = UserServiceFacade::getUserInfo();
        $order = $this->buildOrder();
        $data = $this->buildSearch()->with(['app','class','channel','adminUser','adminUserUpdate'])->order($order);

        $allData = $this->request->param('all_data');
        if ($allData) {
            $data = $data->select();
        } else {
            $limit = $this->request->param('limit', 10);
            $data = $data->paginate($limit)->toArray();
        }
        return $this->success('数据获取成功', $data);
    }
    public function info()
    {
        $id = $this->request->param('id');
        return $this->success('数据获取成功', $this->model->with(['app','class','channel','adminUser','adminUserUpdate'])->findOrEmpty($id)->toArray());
    }
    // 构建查询条件
    private function buildSearch($whereCon = [])
    {
        $filter = $this->request->param('search_param');
        $filter = !empty($filter) ? $filter : [];
        $whereCon = !empty($whereCon) ? $whereCon : [];
        extract($filter);
        extract($whereCon);
        $name = $this->model->getName();
        $tableName = env('database.prefix') . $name;
        $threadModel = $this->model;
        return $threadModel;
    }

    //查看详情
    public function detail()
    {
        $id   = $this->request->param('id');
        $info = $this->model->findOrEmpty($id)->toArray();
        return $this->success('获取成功', $info);
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

    //设置账号状态
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

    //设置账号状态
    public function setBlock()
    {
        $id = $this->request->post('id');
        $update['status'] = 0;
        try {
            $updateRes = $this->model->where('id', '=', $id)->update($update);
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
        $limit = $this->request->param('limit', 10);
        $data  = $this->model->onlyTrashed()
            ->order($order)->where($where)->with(['channelpro','app','class','flow'])->paginate($limit)->toArray();
        return $this->success('回收站数据获取成功', $data);
    }

    // 新增
    public function add()
    {
        $data = $this->request->param();
        AdvertisementPlatformCostData::create($data);
        return $this->success('创建成功');
    }

    public function edit()
    {
        $loginUserInfo = UserServiceFacade::getUserInfo();
        $data = $this->request->param();
        $data['admin_modify_uid'] = $loginUserInfo['id'];
        return $this->success('编辑成功', $this->model->update($data));
    }
}