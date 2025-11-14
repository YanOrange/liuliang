<?php

namespace app\controller\admin;

use laytp\controller\Backend;
use think\facade\Db;
class SourceChannelRate extends Backend
{
    protected $model;//当前模型对象

    protected function _initialize()
    {
        $this->model = new \app\model\admin\SourceChannelRate();
    }

    //查看
    public function index()
    {
        $where = $this->buildSearchParams();
        $order = $this->buildOrder();
        $data = $this->model->where($where)->with(['channel','class'])->order($order);
        $allData = $this->request->param('all_data');
        if ($allData) {
            $data = $data->select();
        } else {
            $limit = $this->request->param('limit', 10);
            $data = $data->paginate($limit)->toArray();
        }
        return $this->success('数据获取成功', $data);
    }

    //查看详情
    public function info()
    {
        $id   = $this->request->param('id');
        $info = $this->model->with(['channel','class'])->findOrEmpty($id)->toArray();
        $info['assign_rate'] = json_decode($info['assign_rate'], true);
        if (empty($info['assign_rate'])) {
            $info['assign_rate'] = [1, 0];
        }
        if($info['type'] == 1) $info['type'] = '0元';
        if($info['type'] == 2) $info['type'] = '1分';
        return $this->success('获取成功', $info);
    }

    //编辑
    public function edit()
    {
        $post     = $this->request->post();
        $data['assign_rate'] = json_encode($post['assign_rate'],JSON_UNESCAPED_UNICODE);
        if ($post['assign_rate'][0] <= 0) {
            return $this->error('手动分配比率不能为0');
        }
        if ($post['assign_rate'][1] < 0) {
            return $this->error('自动分配比率不能小于0');
        }
        if ($post['assign_rate'][1] > 3) {
            return $this->error('自动分配比率不能大于3');
        }
        Db::startTrans();
        try {
            $channelRate = $this->model->findOrEmpty($post['id']);
            if (!$channelRate) throw new \Exception('id参数错误');
            $updateRes  = $channelRate->save($data);
            if (!$updateRes) throw new \Exception('数据库异常，操作失败');
            Db::commit();
            return $this->success('操作成功');
        } catch (\Exception $e) {
            Db::rollback();
            return $this->exceptionError($e);
        }
    }
}