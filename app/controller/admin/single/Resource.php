<?php

namespace app\controller\admin\single;

use app\validate\admin\single\Resource as ResourceValidate;
use laytp\controller\Backend;
use think\facade\Db;
use app\model\admin\Merchant;

/**
 * 资源控制器
 * Class Resource
 * @package app\controller\admin\single
 */
class Resource extends Backend {
    protected $model;//当前模型对象

    protected $noNeedAuth = ['resourceList'];

    protected function _initialize() {
        $this->model = new \app\model\admin\single\Resource();
    }

    /**
     * 列表
     * @return false|mixed|string|\think\response\Json
     */
    public function index() {
        $order = $this->buildOrder();
        $whereCon = [];
        $rsId= $this->request->param('rsid', 0);
        if ($rsId) {
            $whereCon[] = ['id', '<>', $rsId];
        }
        $data = $this->buildSearch()->where($whereCon)->order($order);
        $allData = $this->request->param('all_data');
        if ($allData) {
            $data = $data->select();
        } else {
            $limit = $this->request->param('limit', 10);
            $data = $data->paginate($limit)->toArray();
        }
        return $this->success('数据获取成功', $data);
    }

    //资源列表
    public function resourceList()
    {
        $where = $this->buildSearchParams();
        $order = $this->buildOrder();
        $data = $this->model
            ->where($where)
            ->field('id,title,merchant_ids,resource_type')
            ->order($order);

        $allData = $this->request->param('all_data');
        if ($allData) {
            $data = $data->select();
        } else {
            $limit = $this->request->param('limit', 10);
            $data = $data->paginate($limit)->toArray();
        }
        if(!empty($data)){
            foreach($data as &$val){
                $merchantIds = explode(',',$val['merchant_ids']);
                $merchantList = Merchant::whereIn('id',$merchantIds)->column('merchant_name');
                $merchantName = implode('-',$merchantList);
                $titleLen = mb_strlen($val['title'], "UTF-8");
                if($titleLen > 10){
                    $val['title'] = mb_substr($val['title'],0,10,'utf-8');
                }
                if($val['resource_type'] == 1){
                    $val['title'] = $val['title'].'--'.'精选'.'--['.$merchantName.']';
                }
                if($val['resource_type'] == 2){
                    $val['title'] = $val['title'].'--'.'推荐'.'--['.$merchantName.']';
                }
            }
        }
        return $this->success('数据获取成功', $data);
    }

    /**
     * 添加
     * @return false|string|\think\response\Json
     */
    public function add() {
        $post = $this->request->post();
        $confirmCopyDesc = $post['confirm_copy_desc']['desc'];
        $flowDesc = $post['flow_desc']['desc'];
        $confirmCopyDescArr = [];
        $flowDescArr = [];
        foreach ($confirmCopyDesc as $val) {
            $confirmCopyDescArr[] = ['desc' => $val];
        }
        foreach ($flowDesc as $val) {
            $flowDescArr[] = ['desc' => $val];
        }
        $post['confirm_copy_desc'] = json_encode($confirmCopyDescArr, JSON_UNESCAPED_UNICODE);
        $post['flow_desc'] = json_encode($flowDescArr, JSON_UNESCAPED_UNICODE);
        $validate = new ResourceValidate();
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
        $info['flow_desc'] = json_decode($info['flow_desc'], true);
        $info['confirm_copy_desc'] = json_decode($info['confirm_copy_desc'], true);
        return $this->success('获取成功', $info);
    }

    /**
     * 编辑
     * @return false|string|\think\response\Json
     */
    public function edit() {
        $post = $this->request->post();
        $confirmCopyDesc = $post['confirm_copy_desc']['desc'];
        $flowDesc = $post['flow_desc']['desc'];
        $confirmCopyDescArr = [];
        $flowDescArr = [];
        foreach ($confirmCopyDesc as $val) {
            $confirmCopyDescArr[] = ['desc' => $val];
        }
        foreach ($flowDesc as $val) {
            $flowDescArr[] = ['desc' => $val];
        }
        $post['confirm_copy_desc'] = json_encode($confirmCopyDescArr, JSON_UNESCAPED_UNICODE);
        $post['flow_desc'] = json_encode($flowDescArr, JSON_UNESCAPED_UNICODE);
        $validate = new ResourceValidate();
        if (!$validate->scene('edit')->check($post)) return $this->error($validate->getError());
        Db::startTrans();
        try {
            $resource = $this->model->findOrEmpty($post['id']);
            if (!$resource) throw new \Exception('id参数错误');
            $updateRes = $resource->update($post);
            if (!$updateRes) throw new \Exception('数据库异常，操作失败');
            Db::commit();
            return $this->success('操作成功');
        } catch (\Exception $e) {
            Db::rollback();
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
     * 设置排序
     * @return false|string|\think\response\Json
     */
    public function setSort() {
        $id = $this->request->post('id');
        $fieldVal = $this->request->post('field_val');
        $isRecycle = $this->request->post('is_recycle');
        $update['sort'] = $fieldVal;
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
            $resourceModel = $this->model->onlyTrashed();
        } else {
            $resourceModel = $this->model;
        }
        if (isset($title) && !empty($title)) {
            $resourceModel = $resourceModel->where('title', 'like', "%{$title}%");
        }
        if (isset($merchant_ids) && !empty($merchant_ids)) {
            $resourceModel = $resourceModel->whereFindInSet('merchant_ids', $merchant_ids);
        }
        if (isset($app_ids) && !empty($app_ids)) {
            $resourceModel = $resourceModel->whereFindInSet('app_ids', $app_ids);
        }
        if (isset($resource_type) && is_numeric($resource_type)) {
            $resourceModel = $resourceModel->where('resource_type', '=', $resource_type);
        }
        if (isset($status) && is_numeric($status)) {
            $resourceModel = $resourceModel->where('status', '=', $status);
        }
        if (isset($create_time) && !empty($create_time)) {
            list($startTime, $endTime) = explode(' - ', $create_time);
            $resourceModel = $resourceModel->where('create_time', 'between', strtotime($startTime) . ',' . strtotime($endTime));
        }
        return $resourceModel;
    }
}