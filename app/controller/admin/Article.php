<?php

namespace app\controller\admin;

use app\validate\admin\article\Article as ArticleValidate;
use laytp\controller\Backend;
use laytp\library\CommonFun;
use think\facade\Db;
use think\facade\Request;

/**
 * 后台推荐阅读控制器
 */
class Article extends Backend
{
    protected $model;//当前模型对象
    protected $noNeedAuth = ['info','articleList'];
    protected $noNeedLogin = ['info'];

    protected function _initialize()
    {
        $this->model = new \app\model\admin\Article();
    }

    //查看
    public function index()
    {
        $where = $this->buildSearchParams();
        $order = $this->buildOrder();
        $whereCon = " 1=1";
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

    public function articleList()
    {
        $where = $this->buildSearchParams();
        $order = $this->buildOrder();
        $whereCon = " 1=1";
        $merchantId = $this->request->param('merchant_id', 0);
        if ($merchantId) {
            $whereCon.= " AND FIND_IN_SET('{$merchantId}',merchant_id)";
        }
        $data = $this->model->where($where)->where($whereCon)->order($order)->field('id,title');
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
        $allowField = ['id', 'title', 'merchant_id', 'image', 'content', 'virtual_read_nums', 'virtual_like_nums', 'status',
            'sort', 'course_id', 'channel_ids', 'article_video_url'];
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

        $validate = new ArticleValidate();
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
        // 渠道ID @chenlele 1028 start
        $allowField = ['id', 'title', 'merchant_id', 'image', 'content', 'virtual_read_nums', 'virtual_like_nums', 'status',
            'sort', 'course_id', 'channel_ids', 'article_video_url'];
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

        $validate = new ArticleValidate();
        if (!$validate->scene('edit')->check($post)) return $this->error($validate->getError());
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
    //设置课程状态
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
            ->order($order)->where($where)->paginate($limit)->toArray();
        return $this->success('回收站数据获取成功', $data);
    }
}