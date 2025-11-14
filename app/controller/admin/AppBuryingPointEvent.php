<?php

namespace app\controller\admin;

use app\service\admin\UserServiceFacade;
use app\validate\admin\appburyingpointevent\AppBuryingPointEvent as AppBuryingPointEventValidate;
use laytp\controller\Backend;
use laytp\library\CommonFun;
use think\facade\Db;

class AppBuryingPointEvent extends Backend
{
    protected $model;//当前模型对象

    protected function _initialize()
    {
        $this->model = new \app\model\admin\AppPointEvent();
    }

    //查看
    public function index()
    {
        $where = $this->buildSearchParams();
        $order = $this->buildOrder();
        $whereCon = [];
        $pageId = $this->request->param('page_id', 0);
        if ($pageId) {
            $whereCon[] = ['page_id', '=', $pageId];
        }
        $data = $this->model->where($where)->where($whereCon)->with(['page','admUser'])->order('id asc');
        $allData = $this->request->param('all_data');
        if ($allData) {
            $data = $data->select();
        } else {
            $limit = $this->request->param('limit', 10);
            $data = $data->paginate($limit)->toArray();
        }
        if(!empty($data['data'])){
            foreach($data['data'] as &$item){
                $item['event_pv'] = (new \app\model\admin\PointData())->where('event_id',$item['id'])->count() ?? 0;
                if ($item['event_id'] == 'event_login') {
                    $item['event_uv'] = (new \app\model\admin\PointData())->where('event_id',$item['id'])->group(['oa_id'])->count() ?? 0;
                } else {
                    $item['event_uv'] = (new \app\model\admin\PointData())->where('event_id',$item['id'])->field(['FROM_UNIXTIME(create_time, "%Y-%m-%d") as date_day'])->group(['ip','channel_id','app_id','uid','date_day'])->count() ?? 0;
                }

                if ($item['event_id'] == 'event_landing_lz') {
                    $item['event_uv'] = (new \app\model\admin\PointData())->where('event_id',$item['id'])->group(['ip','channel_id','app_id','uid','landing_page_id'])->count() ?? 0;
                }
            }
        }
        return $this->success('数据获取成功', $data);
    }

    //添加
    public function add()
    {
        $loginUserInfo = UserServiceFacade::getUserInfo();
        $post     = CommonFun::filterPostData($this->request->post());
        $validate = new AppBuryingPointEventValidate();
        if (!$validate->scene('add')->check($post)) return $this->error($validate->getError());
        Db::startTrans();
        try {
            $post['admin_id'] = $loginUserInfo['id'];
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
        $loginUserInfo = UserServiceFacade::getUserInfo();
        $post     = CommonFun::filterPostData($this->request->post());
        $validate = new AppBuryingPointEventValidate();
        if (!$validate->scene('edit')->check($post)) return $this->error($validate->getError());
        Db::startTrans();
        try {
            $app = $this->model->findOrEmpty($post['id']);
            if (!$app) throw new \Exception('id参数错误');
            $post['admin_id'] = $loginUserInfo['id'];
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

    //回收站
    public function recycle()
    {
        $where = $this->buildSearchParams();
        $order = $this->buildOrder();
        $limit = $this->request->param('limit', 10);
        $data  = $this->model->onlyTrashed()
            ->order($order)->where($where)->paginate($limit)->toArray();
        return $this->success('回收站数据获取成功', $data);
    }
}