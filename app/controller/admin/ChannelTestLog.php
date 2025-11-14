<?php

namespace app\controller\admin;

use app\service\admin\UserServiceFacade;
use app\validate\admin\channel\ChannelTestLog as ChannelTestLogValidate;
use laytp\controller\Backend;
use laytp\library\CommonFun;
use think\facade\Db;
use think\facade\Request;
use think\Validate as ChannelValidate;

/**
 * 后台渠道控制器
 */
class ChannelTestLog extends Backend
{
    protected $model;//当前模型对象

    protected $noNeedAuth = [''];

    protected function _initialize()
    {
        $this->model = new \app\model\admin\ChannelTestLog();
    }

    //查看
    public function index()
    {
        $where = $this->buildSearchParams();
        //$order = $this->buildOrder();
        $data = $this->model->where($where)->with(['app', 'createUser' => function ($query) {
            $query->field('id,nickname');
        }, 'updateUser' => function ($query) {
            $query->field('id,nickname');
        }, 'deleteUser' => function ($query) {
            $query->field('id,nickname');
        }])->order('create_time desc');
        $allData = $this->request->param('all_data');
        if ($allData) {
            $data = $data->select()->each(function($item){
                $item['material_onshelf_time'] = ($item['material_onshelf_time'] != 0)?date('Y-m-d H:i:s',$item['material_onshelf_time']):' - ';
                $item['material_offshelf_time'] = ($item['material_offshelf_time'] != 0)?date('Y-m-d H:i:s',$item['material_offshelf_time']):' - ';
                $item['test_time'] = date('Y-m-d',$item['start_time']).' - '.date('Y-m-d',$item['end_time']);
                return $item;
            });
        } else {
            $limit = $this->request->param('limit', 10);
            $data = $data->paginate($limit)->each(function($item){
                $item['material_onshelf_time'] = ($item['material_onshelf_time'] != 0)?date('Y-m-d H:i:s',$item['material_onshelf_time']):' - ';
                $item['material_offshelf_time'] = ($item['material_offshelf_time'] != 0)?date('Y-m-d H:i:s',$item['material_offshelf_time']):' - ';
                $item['test_time'] = date('Y-m-d',$item['start_time']).' - '.date('Y-m-d',$item['end_time']);
                return $item;
            })->toArray();
        }
        return $this->success('数据获取成功', $data);
    }


    //添加
    public function add()
    {
        $post = CommonFun::filterPostData($this->request->post());
        $validate = new ChannelTestLogValidate();
        if (!$validate->scene('add')->check($post)) return $this->error($validate->getError());
        if(isset($post['material_onshelf_time'])){
            $post['material_onshelf_time'] = ($post['material_onshelf_time'] != 0)?strtotime($post['material_onshelf_time']):0;
        }
        if(isset($post['material_offshelf_time'])){
            $post['material_offshelf_time'] = ($post['material_offshelf_time'] != 0)?strtotime($post['material_offshelf_time']):0;
        }
        if(isset($post['app_img'])){
            if(strpos($post['app_img'],',') !== false){
                $post['app_img'] = str_replace(',',', ',$post['app_img']);
            }
        }
        $user = UserServiceFacade::getUserInfo();
        $userId = $user['id'];
        $post['create_user_id'] = $userId;
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
        if(!empty($info)){
            $testTime = date('Y-m-d H:i:s',$info['start_time']).' - '.date('Y-m-d H:i:s',$info['end_time']);
            $info['test_time'] = $testTime;
            if($info['material_onshelf_time'] != 0 ){
                $info['material_onshelf_time'] = date('Y-m-d H:i:s',$info['material_onshelf_time']);
            }else{
                $info['material_onshelf_time'] = '';
            }
            if($info['material_offshelf_time'] != 0 ){
                $info['material_offshelf_time'] = date('Y-m-d H:i:s',$info['material_offshelf_time']);
            }else{
                $info['material_offshelf_time'] = '';
            }
        }
        return $this->success('获取成功', $info);
    }

    //编辑
    public function edit()
    {
        $post = CommonFun::filterPostData($this->request->post());
        $validate = new ChannelTestLogValidate();
        if (!$validate->scene('add')->check($post)) return $this->error($validate->getError());
        if(isset($post['material_onshelf_time'])){
            $post['material_onshelf_time'] = ($post['material_onshelf_time'] != 0)?strtotime($post['material_onshelf_time']):0;
        }
        if(isset($post['material_offshelf_time'])){
            $post['material_offshelf_time'] = ($post['material_offshelf_time'] != 0)?strtotime($post['material_offshelf_time']):0;
        }
        if(isset($post['app_img'])){
            if(strpos($post['app_img'],',') !== false){
                $post['app_img'] = str_replace(',',', ',$post['app_img']);
            }
        }
        $user = UserServiceFacade::getUserInfo();
        $userId = $user['id'];
        $post['update_user_id'] = $userId;
        Db::startTrans();
        try {
            $info = $this->model->findOrEmpty($post['id']);
            if (!$info) throw new \Exception('id参数错误');
            $updateRes  = $info->update($post);
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

    //回收站
    public function recycle()
    {
        $where = $this->buildSearchParams();
        $order = $this->buildOrder();
        $limit = $this->request->param('limit', 10);
        $data = $this->model->onlyTrashed()
            ->with(['app' => function ($query) {
                $query->field('id,app_name');
            }])
            ->order($order)->where($where)->paginate($limit)->toArray();
        return $this->success('回收站数据获取成功', $data);
    }
}