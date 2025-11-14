<?php

namespace app\controller\admin;

use laytp\controller\Backend;
use think\facade\Db;
use app\validate\admin\falsefrontpage\FalseFrontPage as FalseFrontPageValidate;
use laytp\library\CommonFun;

/**
 * 微信小程序路径
 *
 * @package app\admin\controller
 * @date 2022-10-18
 * @author chenlele
 */
class FalseFrontPage extends Backend
{
    protected $model;

    protected $noNeedAuth = ['getFrontPage'];

    public function _initialize()
    {
        $this->model = new \app\model\admin\FalseFrontPage();
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
        $postOld = CommonFun::filterPostData($this->request->post());

        $post = [];
        foreach ($postOld as $key => $val) {
            $post[$key] = trim($val);
        }

        $validate = new FalseFrontPageValidate();
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
        $id = $this->request->param('id');
        $info = $this->model->findOrEmpty($id)->toArray();
        return $this->success('获取成功', $info);
    }

    //编辑
    public function edit()
    {
        $postOld = CommonFun::filterPostData($this->request->post());

        $post = [];
        foreach ($postOld as $key => $val) {
            $post[$key] = trim($val);
        }

        $validate = new FalseFrontPageValidate();
        if (!$validate->scene('edit')->check($post)) return $this->error($validate->getError());
        Db::startTrans();
        try {
            $course = $this->model->findOrEmpty($post['id']);
            if (!$course) throw new \Exception('id参数错误');
            $updateRes = $course->update($post);
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

    // 多选微信路径 @date 2022.10.18 $isEdit => 是否编辑，$pathArr => 已选微信路径
    public function getFrontPage($isEdit = false, $pathArr = [])
    {
        $arr = self::_getPage($pathArr);

        return ($isEdit) ? $arr : $this->success('数据获取成功', $arr);
    }

    // 公用方法 获取路径
    public static function _getPage($pathArr)
    {
        $data = \app\model\admin\FalseFrontPage::field('id, page_title, page_image')->select()->toArray();

        $arr = [];
        foreach ($data as $item) {
            $title = $item['page_title'];
            preg_match("/^[a-zA-Z\s]+$/", $title, $isEn);

            if ($isEn && strlen($title) > 26) { // 中英文
                $title = substr($title, 0, 26) . '...';
            } else if (mb_strlen($title) > 9){
                $title = mb_substr($title, 0, 9) . '...';
            }

            $arr[] = [
                'id' => $item['id'],
                'src' => $item['page_image'],
                'title' => $title,
                'checked' => in_array($item['id'], $pathArr) ? 'checked' : ''
            ];
        }

        return $arr;
    }
}