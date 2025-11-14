<?php

namespace app\controller\admin;

use app\validate\admin\channel\AdvertiserCallbackChannel as AdvertiserCallbackChannelValidate;
use laytp\controller\Backend;
use laytp\library\CommonFun;
use think\facade\Db;
use app\model\admin\Channel;

/**
 * 后台应用分类控制器
 */
class AdvertiserCallbackChannel extends Backend
{
    protected $model;//当前模型对象
    protected function _initialize()
    {
        $this->model = new \app\model\admin\AdvertiserCallbackChannel();
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
        $post     = CommonFun::filterPostData($this->request->post());
        $validate = new AdvertiserCallbackChannelValidate();
        if (!$validate->scene('add')->check($post)) return $this->error($validate->getError());
        Db::startTrans();
        try {
            $advertiserCallbackChannel = $this->model->where('channel_id',$post['channel_id'])->find();
            if(!empty($advertiserCallbackChannel)){
                return $this->error('渠道已存在');
            }
            if(strpos($post['behavior_type'],'pay') !== false && strpos($post['behavior_type'],'submit') !== false){
                return $this->error('付费和表单提交只能选择一项');
            }
            $post['channel'] = Channel::where('id',$post['channel_id'])->value('channel_name');
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
        $post     = CommonFun::filterPostData($this->request->post());
        $validate = new AdvertiserCallbackChannelValidate();
        if (!$validate->scene('edit')->check($post)) return $this->error($validate->getError());
        Db::startTrans();
        try {
            $appClass = $this->model->findOrEmpty($post['id']);
            if (!$appClass) throw new \Exception('id参数错误');
            $updateRes  = $appClass->update($post);
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