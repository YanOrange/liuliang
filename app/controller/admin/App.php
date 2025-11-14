<?php

namespace app\controller\admin;

use app\validate\admin\app\App as AppValidate;
use laytp\controller\Backend;
use laytp\library\CommonFun;
use think\facade\Db;

/**
 * 后台应用控制器
 */
class App extends Backend
{
    protected $model;//当前模型对象

    protected $noNeedAuth = ['getTree','getChannelTree'];

    protected function _initialize()
    {
        $this->model = new \app\model\admin\App();
    }
    //查看
    public function index()
    {
        $where = $this->buildSearchParams();
        $order = $this->buildOrder();
        $whereCon = " 1 = 1";
        $isManyOrganization = $this->request->param('is_many_organization');
        $isSingleMerchant = $this->request->param('is_single_merchant');
        if (is_numeric($isManyOrganization) && is_numeric($isSingleMerchant)) {
            $whereCon.= " AND is_many_organization = {$isManyOrganization}";
        }
        $data = $this->model->where($where)->where($whereCon)->with(['class'])->order($order);
        $allData = $this->request->param('all_data');
        if ($allData) {
            $data = $data->select();
        } else {
            $limit = $this->request->param('limit', 10);
            $data = $data->paginate($limit)->toArray();
        }

        return $this->success('数据获取成功', $data);
    }

    // 分类 - 应用 @chenlele 22-09-26
    public function getTree()
    {
        $id = $this->request->param('id');
        $tabName = $this->request->param('tabName');

        $isManyOrganization = $this->request->param('is_many_organization');
        $isSingleMerchant = $this->request->param('is_single_merchant');

        // 编辑展示选中
        $clsId = 0;
        $appId = 0;
        $appIds = [];
        if ($id) {

            // 好课推荐
            if ($tabName && $tabName == 'course') {
                $info = Db::name('course')->field('id, app_ids')->where('id', $id)->find();
                $appIds = (isset($info['app_ids']) && $info['app_ids']) ? explode(',', $info['app_ids']) : [];

            // 落地页
            } else {
                $landingPage = Db::name('landing_page')->field('id, app_class_id, app_id, channel_ids')->where('id', $id)->find();

                if ($landingPage) {
                    $clsId = $landingPage['app_class_id'];
                    $appId = $landingPage['app_id'];
                }
            }
        }

        $whereCon = " 1 = 1";
        if (is_numeric($isManyOrganization) && is_numeric($isSingleMerchant)) {
            $whereCon.= " AND is_many_organization = {$isManyOrganization}";
        }
        $class = Db::name('app_class')->field('id, app_class_name')->select()->toArray();
        $app = $this->model->where($whereCon)->field('id, app_class_id, app_name')->with(['class'])->order('id desc')->select()->toArray();

        // app
        $appArr = [];
        foreach ($app as $item) {

            $flag = false;
            if ($tabName && $appIds) {
                $flag = (in_array($item['id'], $appIds)) ? true : false;
            } else {
                $flag = ($appId == $item['id']) ? true : false;
            }
            $appArr[$item['app_class_id']][] = [
                'id' => '#' . $item['app_class_id'] . '_'.$item['id'],
                'id1' => $item['id'],
                'name' => $item['app_name'],
                'title' => $item['app_name'],
                'checked' => $flag,
                'pid' => $item['app_class_id'],
                'children' => []
            ];
        }

        // class
        $arr = [];
        foreach ($class as $item) {
            $arr[] = [
                'id' => $item['id'] . '#',
                'id1' => $item['id'],
                'name' => $item['app_class_name'],
                'title' => $item['app_class_name'],
                'checked' => ($clsId == $item['id']) ? true : false,
                'pid' => 0,
                'children' => isset($appArr[$item['id']]) ? $appArr[$item['id']] : []
            ];
        }

        return $this->success('获取成功', $arr);
    }

    // 分类 - 应用 - 渠道 @chenlele 22-09-26
    public function getChannelTree()
    {
        $id = $this->request->param('id');
        $isManyOrganization = $this->request->param('is_many_organization');
        $isSingleMerchant = $this->request->param('is_single_merchant');

        // 编辑展示选中
        $appId = 0;
        $channelIds = [];
        if ($id) {
            $landingPage = Db::name('landing_page')->field('id, app_class_id, app_id, channel_ids, landing_page_type')->where('id', $id)->find();
            if ($landingPage && $landingPage['landing_page_type'] == 1) {
                $appId = $landingPage['app_id'];
                $channelIds = explode(',', $landingPage['channel_ids']);
            }
        }

        $whereCon = " 1 = 1";
        if (is_numeric($isManyOrganization) && is_numeric($isSingleMerchant)) {
            $whereCon.= " AND is_many_organization = {$isManyOrganization}";
        }
        $class = Db::name('app_class')->field('id, app_class_name')->select()->toArray();
        $app = $this->model->where($whereCon)->field('id, app_class_id, app_name')->with(['class'])->order('id desc')->select()->toArray();
        $channel = Db::name('channel')->field('id, app_id, channel_name')->select()->toArray();

        // channel
        $channelArr = [];
        foreach ($channel as $item) {
            $channelArr[$item['app_id']][] = [
                'id' => '|' . $item['app_id'] . '_' . $item['id'],
                'name' => $item['channel_name'],
                'title' => $item['channel_name'],
                'checked' => (in_array($item['id'], $channelIds)) ? true : false,
                'pid' => $item['app_id'],
                'children' => []
            ];
        }

        // app
        $appArr = [];
        foreach ($app as $item) {
            $appArr[$item['app_class_id']][] = [
                'id' => '#' . $item['app_class_id'] . '_' . $item['id'],
                'name' => $item['app_name'],
                'title' => $item['app_name'],
                'checked' => ($appId == $item['id']) ? true : false,
                'pid' => $item['app_class_id'],
                'children' => isset($channelArr[$item['id']]) ? $channelArr[$item['id']] : []
            ];
        }

        // class
        $arr = [];
        foreach ($class as $item) {
            $arr[] = [
                'id' => $item['id'].'#',
                'name' => $item['app_class_name'],
                'title' => $item['app_class_name'],
                'checked' => false,
                'pid' => 0,
                'children' => isset($appArr[$item['id']]) ? $appArr[$item['id']] : []
            ];
        }

        return $this->success('获取成功', $arr);
    }

    //添加
    public function add()
    {
        $post     = CommonFun::filterPostData($this->request->post());
        $validate = new AppValidate();
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
        $post     = CommonFun::filterPostData($this->request->post());
        $validate = new AppValidate();
        if (!$validate->scene('edit')->check($post)) return $this->error($validate->getError());
        Db::startTrans();
        try {
            $app = $this->model->findOrEmpty($post['id']);
            if (!$app) throw new \Exception('id参数错误');
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
    //设置app启动登录状态
    public function setIsLoginShow()
    {
        $id       = $this->request->post('id');
        $fieldVal = $this->request->post('field_val');
        $isRecycle = $this->request->post('is_recycle');
        $update['is_login_show'] = $fieldVal;
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
    //设置落地页状态
    public function setIsLandingPage()
    {
        $id       = $this->request->post('id');
        $fieldVal = $this->request->post('field_val');
        $isRecycle = $this->request->post('is_recycle');
        $update['is_landing_page'] = $fieldVal;
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
    //设置微信授权状态
    public function setIsWxAuth()
    {
        $id       = $this->request->post('id');
        $fieldVal = $this->request->post('field_val');
        $isRecycle = $this->request->post('is_recycle');
        $update['is_wx_auth'] = $fieldVal;
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
        $whereCon = " 1 = 1";
        $isManyOrganization = $this->request->param('is_many_organization', 1);
        if ($isManyOrganization) {
            $whereCon.= " AND is_many_organization = {$isManyOrganization}";
        }
        $limit = $this->request->param('limit', 10);
        $data  = $this->model->onlyTrashed()
            ->with(['class'])
            ->order($order)->where($where)->paginate($limit)->toArray();
        return $this->success('回收站数据获取成功', $data);
    }
}