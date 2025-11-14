<?php

namespace app\controller\admin;

use app\service\admin\UserServiceFacade;
use app\validate\admin\learncourse\LearnLandingPage as LearnLandingPageValidate;
use laytp\controller\Backend;
use laytp\library\CommonFun;
use think\facade\Db;
use think\facade\Request;

/**
 * 后台应用控制器
 */
class LearnLandingPage extends Backend
{
    protected $model;//当前模型对象
    protected $noNeedAuth = ['getOpenCourseList'];

    protected function _initialize()
    {
        $this->model = new \app\model\admin\LearnLandingPage();
    }
    //查看
    public function index()
    {
        $where = $this->buildSearchParams();
        $order = $this->buildOrder();
        $whereCon[] = ['landing_page_type','=',3];
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

    //获取课程列表
    public function getOpenCourseList()
    {
        $where = $this->buildSearchParams();
        $order = $this->buildOrder();
        $model = new \app\model\admin\LearnCourse();
        $whereCon[] = ['course_type','=',2];
        $data = $model->field('id,title')->where($where)->where($whereCon)->order($order);
        $allData = $this->request->param('all_data');
        if ($allData) {
            $data = $data->select();
        } else {
            $limit = $this->request->param('limit', 10);
            $data = $data->paginate($limit)->toArray();
        }
        return $this->success('数据获取成功', $data);
    }

    public function add()
    {
        $allowField = ['landing_page_type','channel_ids','guide_page', 'guide_page_video', 'guide_page_language', 'guide_page_btn_desc',];
        $post = CommonFun::filterPostData(Request::only($allowField));
        $courseIds = $this->request->post('course_id');
        $courseEntryFees = $this->request->post('course_entry_fee');
        $exposeNums = $this->request->post('expose_num');
        $validate = new LearnLandingPageValidate();
        if (!$validate->scene('add')->check($post)) return $this->error($validate->getError());
        $cIds = [];
        $channelIds = $this->request->post('channel_ids');
        $channelIds = array_filter(explode(',', $channelIds));
        foreach ($channelIds as $ids) {
            $idsArr = explode('_', $ids);
            $cIds[] = (isset($idsArr[1])) ? $idsArr[1] : '';
        }
        $post['channel_ids'] = implode(',', $cIds);
        $courseData = [];
        foreach($courseIds as $key => $item){
            $courseData[] = [
                'course_id' => $item,
                'course_entry_fee' => $courseEntryFees[$key],
                'expose_num' => $exposeNums[$key]
            ];
        }
        $post['open_course_json'] = json_encode($courseData);
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
        $info['open_course_json'] = json_decode($info['open_course_json'],true);
        return $this->success('获取成功', $info);
    }

    //编辑
    public function edit()
    {
        $allowField = ['id','landing_page_type','channel_ids','guide_page', 'guide_page_video', 'guide_page_language', 'guide_page_btn_desc',];
        $post = CommonFun::filterPostData(Request::only($allowField));
        $courseIds = $this->request->post('course_id');
        $courseEntryFees = $this->request->post('course_entry_fee');
        $exposeNums = $this->request->post('expose_num');
        $validate = new LearnLandingPageValidate();
        if (!$validate->scene('add')->check($post)) return $this->error($validate->getError());
        $cIds = [];
        $channelIds = $this->request->post('channel_ids');
        $channelIds = array_filter(explode(',', $channelIds));
        foreach ($channelIds as $ids) {
            $idsArr = explode('_', $ids);
            $cIds[] = (isset($idsArr[1])) ? $idsArr[1] : '';
        }
        $post['channel_ids'] = implode(',', $cIds);
        $courseData = [];
        foreach($courseIds as $key => $item){
            $courseData[] = [
                'course_id' => $item,
                'course_entry_fee' => $courseEntryFees[$key],
                'expose_num' => $exposeNums[$key]
            ];
        }
        $post['open_course_json'] = json_encode($courseData);
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

    //设置微信授权状态
    public function setIsStatus()
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