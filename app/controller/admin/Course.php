<?php

namespace app\controller\admin;

use app\validate\admin\course\Course as CourseValidate;
use laytp\controller\Backend;
use laytp\library\CommonFun;
use think\facade\Db;
use app\model\admin\Merchant;
use app\service\admin\AuthServiceFacade;
use app\service\admin\UserServiceFacade;
use think\facade\Request;

/**
 * 后台课程控制器
 */
class Course extends Backend
{
    protected $model;//当前模型对象

    protected $noNeedAuth = ['courseList'];

    protected function _initialize()
    {
        $this->model = new \app\model\admin\Course();
    }
    //查看
    public function index()
    {
        //$where = $this->buildSearchParams();
        $order = $this->buildOrder();
        $courseType = $this->request->param('course_type');
        $where = [];
        $whereCon = [];
        if (is_numeric($courseType)) {
            $whereCon['course_type'] = ['=', $courseType];
        }

        // 干事角色ID 22.09.02 chenlele
        //$loginUserInfo = UserServiceFacade::getUserInfo();
       // $loginId = $loginUserInfo['id'];
        //$roleIds = AuthServiceFacade::getAuthUserRole($loginId);
        /*if ($loginId != 1 && in_array(env('ROLE.GANSHI'), $roleIds)) {

            // 8 - 干事：管理自己负责的客户
            $merchantIds = Merchant::whereFindInSet('admin_ids', $loginId)->column('id');
            $where[] = ['merchant_id', 'in', $merchantIds];

        }*/
        $data = $this->buildSearch()->where($where)->where($whereCon)->with(['merchant'])->order($order);

        $allData = $this->request->param('all_data');
        if ($allData) {
            $data = $data->select();
        } else {
            $limit = $this->request->param('limit', 10);
            $data = $data->paginate($limit)->toArray();
        }
        return $this->success('数据获取成功', $data);
    }

    // 构建查询条件
    private function buildSearch($isDelete = false, $whereCon = [])
    {
        $filter = $this->request->param('search_param');
        $filter = !empty($filter) ? $filter : [];
        $whereCon = !empty($whereCon) ? $whereCon : [];
        extract($filter);
        extract($whereCon);
        $name = $this->model->getName();
        $tableName = env('database.prefix') . $name;
        if ($isDelete) {
            $courseModel = $this->model->onlyTrashed();
        } else {
            $courseModel = $this->model;
        }

        if (isset($app_class_id) && !empty($app_class_id)) {
            $courseModel = $courseModel->withJoin(['merchant'], 'inner');
            $courseModel = $courseModel->where('merchant.app_class_id', '=', $app_class_id);
        }
        if (isset($title) && !empty($title)) {
            $courseModel = $courseModel->where($tableName.'.title', 'LIKE', '%'.$title.'%');
        }
        if (isset($merchant_id) && !empty($merchant_id)) {
            $courseModel = $courseModel->where($tableName.'.merchant_id', '=', $merchant_id);
        }
        if (isset($status) && !empty($status)) {
            $courseModel = $courseModel->where($tableName.'.status', '=', $status);
        }
        if (isset($app_ids) && !empty($app_ids)) {
            $courseModel = $courseModel->whereFindInSet($tableName.'.app_ids', $app_ids);
        }

        if (isset($create_time) && !empty($create_time)) {
            list($startTime, $endTime) = explode(' - ', $create_time);
            $courseModel = $courseModel->where($tableName . '.create_time', 'between', strtotime($startTime) . ',' . strtotime($endTime));
        }

        return $courseModel;
    }

    //课程列表
    public function courseList()
    {
        $where = $this->buildSearchParams();
        $order = $this->buildOrder();
        $whereCon = [];
        $data = $this->model
            ->where($where)
            ->where($whereCon)
            ->field('id,title,merchant_id')
            ->order($order);

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
        $allowField = ['merchant_id', 'app_ids', 'channel_ids', 'status', 'title', 'video_cover_image', 'laytpUploadFile', 'video_url',
            'landing_page_btn_image', 'tag_names', 'course_thumbnail_image', 'video_burning_time', 'entry_fee', 'virtual_apply_nums', 'btn_desc',
            'confirm_btn_desc', 'apply_succeed_wx_btn', 'confirm_btn_desc_background_color','confirm_btn_desc_font_color','sort','content'
        ];
        $post = CommonFun::filterPostData(Request::only($allowField));
        $appIds = $this->request->post('app_ids');
        if (strlen($appIds) < 1) {
            return $this->error('请选择应用');
        }
        $post['app_ids'] = $appIds;

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
        if (!$post['merchant_id']) {
            return $this->error('请选择商户');
        }
        // 渠道ID @chenlele 1028 end

        $validate = new CourseValidate();
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
        $allowField = ['id', 'merchant_id', 'app_ids', 'channel_ids', 'status', 'title', 'video_cover_image', 'laytpUploadFile', 'video_url',
            'landing_page_btn_image', 'tag_names', 'course_thumbnail_image', 'video_burning_time', 'entry_fee', 'virtual_apply_nums', 'btn_desc',
            'confirm_btn_desc', 'apply_succeed_wx_btn', 'confirm_btn_desc_background_color','confirm_btn_desc_font_color','sort','content'
        ];
        $post = CommonFun::filterPostData(Request::only($allowField));

        $appIds = $this->request->post('app_ids');
        if (strlen($appIds) < 1) {
            return $this->error('请选择应用');
        }
        $post['app_ids'] = $appIds;

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
        if (!$post['merchant_id']) {
            return $this->error('请选择商户');
        }
        // 渠道ID @chenlele 1028 end

        $validate = new CourseValidate();
        if (!$validate->scene('edit')->check($post)) return $this->error($validate->getError());
        Db::startTrans();
        try {
            $course = $this->model->findOrEmpty($post['id']);
            if (!$course) throw new \Exception('id参数错误');
            $updateRes  = $course->update($post);
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
            ->with(['merchant'])
            ->order($order)->where($where)->paginate($limit)->toArray();
        return $this->success('回收站数据获取成功', $data);
    }
}