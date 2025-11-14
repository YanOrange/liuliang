<?php

namespace app\controller\admin;

use app\model\admin\login\Log;
use app\service\admin\AuthServiceFacade;
use app\service\admin\UserServiceFacade;
use app\validate\admin\banner\Banner as BannerValidate;
use laytp\controller\Backend;
use laytp\library\CommonFun;
use laytp\library\Str;
use think\facade\Config;
use think\facade\Db;
use think\facade\Request;

/**
 * 后台轮播图控制器
 */
class Banner extends Backend
{
    protected $model;//当前模型对象
    protected function _initialize()
    {
        $this->model = new \app\model\admin\Banner();
    }
    //查看
    public function index()
    {
        $where = $this->buildSearchParams();
        $order = $this->buildOrder();
        $whereCon = " type = 0";
        $merchantId = $this->request->param('merchant_id', 0);
        if ($merchantId) {
            $whereCon.= " AND FIND_IN_SET('{$merchantId}',merchant_id)";
        }
        $data = $this->model->where($where)->where($whereCon)->order($order);
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
        // 渠道ID @chenlele 1028 start
        $allowField = ['id', 'title', 'image', 'app_id', 'status', 'sort', 'merchant_id', 'is_many_organization',
            'jump_mode', 'module_id','jump_mode_json', 'jump_url', 'type', 'channel_ids'];
        $post = CommonFun::filterPostData(Request::only($allowField));

        $cIds = [];
        $channelIds = $this->request->post('channel_ids');
        $channelIds = array_filter(explode(',', $channelIds));
        foreach ($channelIds as $ids) {
            $idsArr = explode('_', $ids);
            $cIds[] = (isset($idsArr[1])) ? $idsArr[1] : '';
        }
        $post['channel_ids'] = implode(',', $cIds);
        if (!$post['channel_ids']) {
            return $this->error('请选择关联渠道');
        }
        // 渠道ID @chenlele 1028 end

        $validate = new BannerValidate();
        if (!$validate->scene('add')->check($post)) return $this->error($validate->getError());
//        if($post['jump_mode'] == 1){
//            if(!$post['module_id'] || (!$post['course_id_1'] && $post['course_id_2'] && !$post['article_id'])){
//                return $this->error('跳转参数错误');
//            }
//            if(!empty($post['course_id_1'])){
//                $post['course_id'] = $post['course_id_1'];
//            }else if(!empty($post['course_id_2'])){
//                $post['course_id'] = $post['course_id_2'];
//            }
//            if(!empty($post['article_id'])) $post['course_id'] = $post['article_id'];
//            if(!empty($post['resource_id'])) $post['course_id'] = $post['resource_id'];
//            $post['jump_mode_json'] = json_encode(['module_id' => $post['module_id'],'course_id' => $post['course_id']]);
//        }
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
        $jump_mode_json = !empty($info['jump_mode_json']) ? json_decode($info['jump_mode_json'],true) : [];
        $info['module_id'] = isset($jump_mode_json['module_id']) ? $jump_mode_json['module_id'] : 0;
        $info['course_id'] = isset($jump_mode_json['course_id']) ? $jump_mode_json['course_id'] : 0;
        return $this->success('获取成功', $info);
    }

    //编辑
    public function edit()
    {
        // 渠道ID @chenlele 1028 start
        $allowField = ['id', 'title', 'image', 'app_id', 'status', 'sort', 'merchant_id', 'is_many_organization',
            'jump_mode','module_id', 'jump_mode_json', 'jump_url', 'type', 'channel_ids'];
        $post = CommonFun::filterPostData(Request::only($allowField));

        $cIds = [];
        $channelIds = $this->request->post('channel_ids');
        $channelIds = array_filter(explode(',', $channelIds));
        foreach ($channelIds as $ids) {
            $idsArr = explode('_', $ids);
            $cIds[] = (isset($idsArr[1])) ? $idsArr[1] : '';
        }
        $post['channel_ids'] = implode(',', $cIds);
        if (!$post['channel_ids']) {
            return $this->error('请选择关联渠道');
        }
        // 渠道ID @chenlele 1028 end

        $validate = new BannerValidate();
        if (!$validate->scene('edit')->check($post)) return $this->error($validate->getError());
//        if($post['jump_mode'] == 1){
//            if(!$post['module_id'] || (!$post['course_id_1'] && $post['course_id_2'] && !$post['article_id'])){
//                return $this->error('跳转参数错误');
//            }
//            if(!empty($post['course_id_1'])){
//                $post['course_id'] = $post['course_id_1'];
//            }else if(!empty($post['course_id_2'])){
//                $post['course_id'] = $post['course_id_2'];
//            }else{
//                $post['course_id'] = $post['article_id'];
//            }
//            $post['jump_mode_json'] = json_encode(['module_id' => $post['module_id'],'course_id' => $post['course_id']]);
//        }
        Db::startTrans();
        try {
            $banner = $this->model->findOrEmpty($post['id']);
            if (!$banner) throw new \Exception('id参数错误');
            $updateRes  = $banner->update($post);
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

    //设置状态
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
    //设置排序
    public function setSort()
    {
        $id       = $this->request->post('id');
        $fieldVal = $this->request->post('field_val');
        $isRecycle = $this->request->post('is_recycle');
        $update['sort'] = $fieldVal;
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
        $limit = $this->request->param('limit', 10);
        $data  = $this->model->onlyTrashed()
            ->order($order)->where($where)->where('type', 1)->paginate($limit)->toArray();
        return $this->success('回收站数据获取成功', $data);
    }
}