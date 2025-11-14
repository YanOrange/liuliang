<?php

namespace app\controller\api;
use app\model\api\CourseCollect as CourseCollectModel;
/**
 * 课程收藏接口
 */
class CourseCollect extends BaseApi
{
    public $noNeedLogin = [''];
    public $noNeedCheckSign = [''];

    //获取收藏列表
    public function getCourseCollectList()
    {
        $params = $this->request->post();
        $this->commonApiValidate($params, 'app\validate\api\coursecollect\CourseCollect', 'getCourseCollectList');
        return $this->success('收藏列表', CourseCollectModel::getCourseCollectList($params));
    }
    //添加收藏
    public function addCourseCollect()
    {
        $params = $this->request->post();
        $this->commonApiValidate($params, 'app\validate\api\coursecollect\CourseCollect', 'addCourseCollect');
        return $this->success('收藏成功', CourseCollectModel::addCourseCollect($params));
    }
    //取消收藏
    public function cancelCourseCollect()
    {
        $params = $this->request->post();
        $this->commonApiValidate($params, 'app\validate\api\coursecollect\CourseCollect', 'cancelCourseCollect');
        return $this->success('取消收藏成功', CourseCollectModel::cancelCourseCollect($params));
    }

}