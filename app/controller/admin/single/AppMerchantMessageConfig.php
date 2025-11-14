<?php

namespace app\controller\admin\single;

use app\validate\admin\single\AppMerchantMessageConfig as AppMerchantMessageConfigValidate;
use laytp\controller\Backend;
use think\facade\Db;

/**
 * 商户应用控制器
 * Class AppMerchantMessageConfig
 * @package app\controller\admin\single
 */
class AppMerchantMessageConfig extends Backend {

    protected $model;//当前模型对象

    protected function _initialize() {
        $this->model = new \app\model\admin\single\AppMerchantMessageConfig();
    }

    /**
     * 列表
     * @return false|mixed|string|\think\response\Json
     */
    public function index() {
        $order = $this->buildOrder();
        $data = $this->buildSearch()->order($order);
        $allData = $this->request->param('all_data');
        if ($allData) {
            $data = $data->select();
        } else {
            $limit = $this->request->param('limit', 10);
            $data = $data->paginate($limit)->toArray();
        }
        return $this->success('数据获取成功', $data);
    }

    /**
     * 构建查询条件
     * @param false $isDelete
     * @return mixed
     */
    private function buildSearch($isDelete = false) {
        $filter = $this->request->param('search_param') ?? [];
        extract($filter);
        $name = $this->model->getName();
        $tableName = env('database.prefix') . $name;
        if ($isDelete) {
            $appmmModel = $this->model->onlyTrashed();
        } else {
            $appmmModel = $this->model;
        }
        if (isset($merchant_ids) && !empty($merchant_ids)) {
            $appmmModel = $appmmModel->whereFindInSet('merchant_ids', $merchant_ids);
        }
        if (isset($app_ids) && !empty($app_ids)) {
            $appmmModel = $appmmModel->whereFindInSet('app_ids', $app_ids);
        }
        if (isset($status) && is_numeric($status)) {
            $appmmModel = $appmmModel->where('status', '=', $status);
        }
        if (isset($create_time) && !empty($create_time)) {
            list($startTime, $endTime) = explode(' - ', $create_time);
            $appmmModel = $appmmModel->where('create_time', 'between', strtotime($startTime) . ',' . strtotime($endTime));
        }
        return $appmmModel;
    }

    /**
     * 添加
     * @return false|string|\think\response\Json
     */
    public function add() {
        $post = $this->request->post();
        $validate = new AppMerchantMessageConfigValidate();
        if (!$validate->scene('add')->check($post)) return $this->error($validate->getError());
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

    /**
     * 查看详情
     * @return false|string|\think\response\Json
     */
    public function info() {
        $id = $this->request->param('id');
        $info = $this->model->findOrEmpty($id)->toArray();
        return $this->success('获取成功', $info);
    }

    /**
     * 编辑
     * @return false|string|\think\response\Json
     */
    public function edit() {
        $post = $this->request->post();
        $validate = new AppMerchantMessageConfigValidate();
        if (!$validate->scene('edit')->check($post)) return $this->error($validate->getError());
        Db::startTrans();
        try {
            $scan = $this->model->findOrEmpty($post['id']);
            if (!$scan) throw new \Exception('id参数错误');
            $updateRes = $scan->update($post);
            if (!$updateRes) throw new \Exception('数据库异常，操作失败');
            Db::commit();
            return $this->success('操作成功');
        } catch (\Exception $e) {
            Db::rollback();
            return $this->exceptionError($e);
        }
    }

    /**
     * 删除
     * @return false|string|\think\response\Json
     */
    public function del() {
        $ids = array_filter($this->request->param('ids'));
        if (!$ids) {
            return $this->error('参数ids不能为空');
        }
        try {
            if ($this->model->destroy($ids)) {
                return $this->success('数据删除成功');
            } else {
                return $this->error('数据删除失败');
            }
        } catch (\Exception $e) {
            return $this->exceptionError($e);
        }
    }

    /**
     * 设置状态
     * @return false|string|\think\response\Json
     */
    public function setStatus() {
        $id = $this->request->post('id');
        $fieldVal = $this->request->post('field_val');
        $isRecycle = $this->request->post('is_recycle');
        $update['status'] = $fieldVal;
        try {
            if ($isRecycle) {
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

    /**
     * 回收站
     * @return false|string|\think\response\Json
     */
    public function recycle() {
        $order = $this->buildOrder();
        $limit = $this->request->param('limit', 10);
        $data = $this->buildSearch(true)
            ->order($order)->paginate($limit)->toArray();
        return $this->success('回收站数据获取成功', $data);
    }
}