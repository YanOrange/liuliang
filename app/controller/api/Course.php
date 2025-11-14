<?php

namespace app\controller\api;
use app\model\api\Course as CourseModel;
/**
 * 课程接口
 */
class Course extends BaseApi
{
    public $noNeedLogin = [''];
    public $noNeedCheckSign = ['getCourseDetail'];

    //热门收藏列表
    public function getCourseIsHotCollectList()
    {
        $params = $this->request->post();
        $this->commonApiValidate($params, 'app\validate\api\course\Course', 'getCourseIsHotCollectList');
        return $this->success('热门收藏列表', CourseModel::getCourseIsHotCollectList($params));
    }
    //精品推荐
    public function getCourseList()
    {
        $params = $this->request->post();
        $this->commonApiValidate($params, 'app\validate\api\course\Course', 'getCourseList');
        return $this->success('精品推荐列表', CourseModel::getCourseList($params));
    }
    //课程详情
    public function getCourseDetail()
    {
        $params = $this->request->post();
        $this->commonApiValidate($params, 'app\validate\api\course\Course', 'getCourseDetail');
        return $this->success('课程详情', CourseModel::getCourseDetail($params));
    }
}