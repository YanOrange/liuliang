<?php

namespace app\controller\admin\part;

use app\validate\admin\part\LiveCourse as LiveCourseValidate;
use laytp\controller\Backend;
use laytp\library\CommonFun;
use think\facade\Db;
use think\facade\Request;

/**
 * 后台直播课程控制器
 */
class LiveCourse extends Backend
{
    protected $model;//当前模型对象
    protected function _initialize()
    {
        $this->model = new \app\model\admin\part\Course();
    }
    //查看
    public function index()
    {
        $order = $this->buildOrder();
        $data = $this->buildSearch()->where('course_type', 3)->with(['merchant'])->order($order);
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
    private function buildSearch($isDelete = false)
    {
        $filter = $this->request->param('search_param') ?? [];
        extract($filter);
        $name = $this->model->getName();
        $tableName = env('database.prefix') . $name;
        if ($isDelete) {
            $liveCourseModel = $this->model->onlyTrashed();
        } else {
            $liveCourseModel = $this->model;
        }
        if (isset($title) && !empty($title)) {
            $liveCourseModel = $liveCourseModel->where('title', 'like', "%{$title}%");
        }
        if (isset($part_class_ids) && !empty($part_class_ids)) {
            $liveCourseModel= $liveCourseModel->whereFindInSet('part_class_ids', $part_class_ids);
        }
        if (isset($merchant_id) && !empty($merchant_id)) {
            $liveCourseModel = $liveCourseModel->where('merchant_id', '=', $merchant_id);
        }
        if (isset($app_ids) && !empty($app_ids)) {
            $liveCourseModel= $liveCourseModel->whereFindInSet('app_ids', $app_ids);
        }
        if (isset($status) && is_numeric($status)) {
            $liveCourseModel= $liveCourseModel->where('status', '=', $status);
        }
        if (isset($create_time) && !empty($create_time)) {
            list($startTime, $endTime) = explode(' - ', $create_time);
            $liveCourseModel = $liveCourseModel->where('create_time', 'between', strtotime($startTime). ','. strtotime($endTime));
        }
        if (isset($live_status) && !empty($live_status)) {
            $nowTime = time();
            if ($live_status == 'wait_live') {
                $liveCourseModel = $liveCourseModel->where('live_start_time', '>', $nowTime);
            }
            if ($live_status == 'in_live') {
                $liveCourseModel = $liveCourseModel->where('live_start_time', '<=', $nowTime)->where('live_end_time', '>=', $nowTime);
            }
            if ($live_status == 'finish_live') {
                $liveCourseModel = $liveCourseModel->where('live_end_time', '<', $nowTime);
            }
        }
        return $liveCourseModel;
    }
    //添加
    public function add()
    {
        $allowField = ['merchant_id','channel_ids','app_class_id','status','title','part_class_ids','date','entry_fee',
            'original_price','video_cover_image','virtual_apply_nums','sort','share_desc','live_btn_desc', 'course_ids',
            'content','video_url','part_course_bottom_desc'
        ];
        $post     = $this->request->post(Request::only($allowField));
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

        $validate = new LiveCourseValidate();
        if (!$validate->scene('add')->check($post)) return $this->error($validate->getError());
        $btnDescArr = $post['live_btn_desc']['btn_desc'];
        $btnStatusArr = $post['live_btn_desc']['btn_status'];
        $btnDescStatusArr = [];
        for($i = 0; $i < count($btnDescArr); $i++) {
            $btnDescStatusArr[] = ['btn_desc' => $btnDescArr[$i], 'btn_status' => $btnStatusArr[$i]];
        }
        $post['share_image'] = $post['video_cover_image'];
        $post['course_type'] = 3;
        $post['live_btn_desc'] = json_encode($btnDescStatusArr, JSON_UNESCAPED_UNICODE);
        list($startTime, $endTime) = explode(' - ', $post['date']);
        $post['live_start_time'] = strtotime($startTime);
        $post['live_end_time'] = strtotime($endTime);
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
        $liveBtnDescArr = json_decode($info['live_btn_desc'],true);
        foreach ($liveBtnDescArr as $key => $value) {
            if ($value['btn_status'] == 'wait_live') {
                $liveBtnDescArr[$key]['btn_status_desc'] = '待直播按钮文案';
            }
            if ($value['btn_status'] == 'in_live') {
                $liveBtnDescArr[$key]['btn_status_desc'] = '直播中按钮文案';
            }
            if ($value['btn_status'] == 'finish_live') {
                $liveBtnDescArr[$key]['btn_status_desc'] = '结束中按钮文案';
            }
        }
        $info['live_btn_desc'] = $liveBtnDescArr;
        $info['live_time'] = date("Y-m-d H:i:s", $info['live_start_time']) . ' - ' . date("Y-m-d H:i:s", $info['live_end_time']);
        return $this->success('获取成功', $info);
    }

    //编辑
    public function edit()
    {
        $allowField = ['id','merchant_id','channel_ids','app_class_id','status','title','part_class_ids','date','entry_fee',
            'original_price','video_cover_image','virtual_apply_nums','sort','share_desc','live_btn_desc', 'course_ids',
            'content','video_url','part_course_bottom_desc'
        ];
        $post     = $this->request->post(Request::only($allowField));
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
        $validate = new LiveCourseValidate();
        $btnDescArr = $post['live_btn_desc']['btn_desc'];
        $btnStatusArr = $post['live_btn_desc']['btn_status'];
        $btnDescStatusArr = [];
        for($i = 0; $i < count($btnDescArr); $i++) {
            $btnDescStatusArr[] = ['btn_desc' => $btnDescArr[$i], 'btn_status' => $btnStatusArr[$i]];
        }
        $post['share_image'] = $post['video_cover_image'];
        $post['live_btn_desc'] = json_encode($btnDescStatusArr, JSON_UNESCAPED_UNICODE);
        list($startTime, $endTime) = explode(' - ', $post['date']);
        $post['live_start_time'] = strtotime($startTime);
        $post['live_end_time'] = strtotime($endTime);
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
    //设置报名人数
    public function setVirtualApplyNums()
    {
        $id       = $this->request->post('id');
        $fieldVal = $this->request->post('field_val');
        $isRecycle = $this->request->post('is_recycle');
        $update['virtual_apply_nums'] = $fieldVal;
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
        $order = $this->buildOrder();
        $limit = $this->request->param('limit', 10);
        $data  = $this->buildSearch(true)
            ->where('course_type', 3)
            ->with(['merchant'])
            ->order($order)->paginate($limit)->toArray();
        return $this->success('回收站数据获取成功', $data);
    }
}