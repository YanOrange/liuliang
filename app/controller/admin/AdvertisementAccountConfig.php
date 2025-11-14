<?php

namespace app\controller\admin;

use laytp\controller\Backend;
use laytp\library\CommonFun;
use think\facade\Db;
/**
 * 后台阅读器
 */
class AdvertisementAccountConfig extends Backend
{
    protected $model;//当前模型对象
    protected $noNeedAuth = [''];
    protected $noNeedLogin = ['info'];

    protected function _initialize()
    {
        $this->model = new \app\model\admin\AdvertisementAccountConfig();
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
        $post = CommonFun::filterPostData($this->request->param());

        $validate = new \app\validate\admin\advertising\AdvertisementAccountConfig();
        if (!$validate->scene('add')->check($post)) return $this->error($validate->getError());

        $post['accessTokendate'] = isset($post['accessTokendate']) && $post['accessTokendate'] ? $post['accessTokendate'] : null;
        $post['refreshTkoendate'] = isset($post['refreshTkoendate']) && $post['refreshTkoendate'] ? $post['refreshTkoendate'] : null;

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
        $post = CommonFun::filterPostData($this->request->param());

        $validate = new \app\validate\admin\advertising\AdvertisementAccountConfig();
        if (!$validate->scene('edit')->check($post)) return $this->error($validate->getError());

        $post['accessTokendate'] = isset($post['accessTokendate']) && $post['accessTokendate'] ? $post['accessTokendate'] : null;
        $post['refreshTkoendate'] = isset($post['refreshTkoendate']) && $post['refreshTkoendate'] ? $post['refreshTkoendate'] : null;

        Db::startTrans();
        try {
            $article = $this->model->findOrEmpty($post['id']);
            if (!$article) throw new \Exception('id参数错误');
            $updateRes  = $article->update($post);
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

    // 应用列表
    public function appList()
    {
        $data = \app\model\admin\App::order('id desc')->field('id,app_class_id,app_name')->select()->toArray();

        return $this->success('获取应用列表', $data);
    }

    // 渠道列表
    public function channelList()
    {
        $appId = $this->request->param('id', 0);

        $data = \app\model\admin\Channel::when($appId, function ($query) use ($appId) {
            return $query->where('app_id', $appId);
        })->order('id desc')->field('id,channel_name')->select()->toArray();

        return $this->success('获取渠道列表', $data);
    }
}