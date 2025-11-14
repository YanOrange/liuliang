<?php

namespace app\controller\admin;

use laytp\controller\Backend;
use think\facade\Db;
use app\validate\admin\sourcechannel\SourceChannel as SourceChannelValidate;
use app\model\admin\AppClass;
class SourceChannel extends Backend
{
    protected $model;//当前模型对象
    protected $channelModel;//当前模型对象

    protected function _initialize()
    {
        $this->model = new \app\model\admin\SourceChannel();
        $this->channelModel = new \app\model\admin\SourceChannelRate();
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
        $post     = $this->request->post();
        $validate = new SourceChannelValidate();
        if (!$validate->scene('add')->check($post)) return $this->error($validate->getError());
        $dataFree = [];
        $dataPay = [];
        Db::startTrans();
        try {
            $saveRes = $this->model->save($post);
            $appClassIds = AppClass::column('id');
            foreach($appClassIds as $appClassId){
                $dataFree[] = [
                        'source_id' => $this->model->id,
                        'app_class_id' => $appClassId,
                        'assign_rate' => json_encode([1,0]),
                        'type' => 1
                    ];
                $dataPay[] = [
                        'source_id' => $this->model->id,
                        'app_class_id' => $appClassId,
                        'assign_rate' => json_encode([1,0]),
                        'type' => 2
                    ];
            }
            $saveResFree =  $this->channelModel->saveAll($dataFree);
            $saveResPay =  $this->channelModel->saveAll($dataPay);
            if (!$saveRes && $saveResFree && $saveResPay) throw new \Exception('数据库异常，操作失败');
            Db::commit();
            return $this->success('操作成功');
        } catch (\Exception $e) {
            Db::rollback();
            dump($e->getMessage());
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
        $post     = $this->request->post();
        $validate = new SourceChannelValidate();
        if (!$validate->scene('edit')->check($post)) return $this->error($validate->getError());
        Db::startTrans();
        try {
            $forFlow = $this->model->findOrEmpty($post['id']);
            if (!$forFlow) throw new \Exception('id参数错误');
            $updateRes  = $forFlow->update($post);
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