<?php

namespace app\controller\admin\website;
use app\validate\admin\website\WebsiteStyleShow as WebsiteStyleShowValidate;
use think\facade\Db;
use laytp\controller\Backend;
use app\model\admin\website\WebsiteStyleImage;
/**
 * 后台风采展示控制器
 */
class WebsiteStyleShow extends Backend
{
    protected $model;//当前模型对象
    protected function _initialize()
    {
        $this->model = new \app\model\admin\website\WebsiteStyleShow();
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
        $validate = new WebsiteStyleShowValidate();
        if (!$validate->scene('add')->check($post)) return $this->error($validate->getError());
        Db::startTrans();
        try {
            $saveRes = $this->model->save($post);
            if (isset($post['image_url']) && !empty($post['image_url'])) {
                $imageUrlData = explode(',', $post['image_url']);
                $imageDescData = $post['image_desc'];
                $styleData = [];
                foreach ($imageUrlData as $key => $value) {
                    $styleData[] = ['style_id' => $this->model->id, 'image_url' => $value, 'image_desc' => $imageDescData[$key] ?? ''];
                }
                (new WebsiteStyleImage())->saveAll($styleData);
            }
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
        $info = $this->model->with(['imageDesc'])->findOrEmpty($id)->toArray();
        $imageUrlData = WebsiteStyleImage::where('style_id', $id)->column('image_url');
        $info['image_urls'] = $imageUrlData ? implode(', ', $imageUrlData) : '';
        return $this->success('获取成功', $info);
    }

    //编辑
    public function edit()
    {
        $post     = $this->request->post();
        $validate = new WebsiteStyleShowValidate();
        if (!$validate->scene('edit')->check($post)) return $this->error($validate->getError());
        Db::startTrans();
        try {
            $info = $this->model->findOrEmpty($post['id']);
            if (!$info) throw new \Exception('id参数错误');
            $updateRes  = $info->update($post);
            if (!$updateRes) throw new \Exception('数据库异常，操作失败');
            if (isset($post['image_url']) && !empty($post['image_url'])) {
                WebsiteStyleImage::where('style_id', $post['id'])->delete();
                $imageUrlData = explode(',', $post['image_url']);
                $imageDescData = $post['image_desc'];
                $styleData = [];
                foreach ($imageUrlData as $key => $value) {
                    $styleData[] = ['style_id' => $post['id'], 'image_url' => $value, 'image_desc' => $imageDescData[$key] ?? ''];
                }
                (new WebsiteStyleImage())->saveAll($styleData);
            }
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
    //设置显示状态
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