<?php

namespace app\controller\api\customer;

use app\controller\api\BaseApi;
use app\model\api\customer\Thread as ThreadModel;

class Thread extends BaseApi
{
    public $noNeedLogin = ['*'];
    public $noNeedCheckSign = ['*'];

    public function getThreadList()
    {
        $params = $this->request->post();
        //$this->commonApiValidate($params, 'app\validate\api\customer\Thread', 'getThreadList');
        $data = ThreadModel::getThreadList($params);
        return $this->successOrder('获取成功', $data['total'],$data['data']);
    }

    public function detail()
    {
        $params = $this->request->post();
        $this->commonApiValidate($params, 'app\validate\api\customer\Thread', 'detail');
        return $this->success('获取成功', ThreadModel::detail($params));
    }

    public function assignDetail()
    {
        $params = $this->request->post();
        $this->commonApiValidate($params, 'app\validate\api\customer\Thread', 'assignDetail');
        return $this->success('获取成功', ThreadModel::assignDetail($params));
    }

    public function merchantList()
    {
        return $this->success('获取成功', ThreadModel::merchantList());
    }

    public function customerList()
    {
        return $this->success('获取成功', ThreadModel::customerList());
    }

    public function courseList()
    {
        return $this->success('获取成功', ThreadModel::courseList());
    }

    public function appList()
    {
        return $this->success('获取成功', ThreadModel::appList());
    }

    public function channelList()
    {
        return $this->success('获取成功', ThreadModel::channelList());
    }

    public function appClassList()
    {
        return $this->success('获取成功', ThreadModel::appClassList());
    }

    public function tagList()
    {
        return $this->success('获取成功', ThreadModel::tagList());
    }

    public function ageRangeList()
    {
        return $this->success('获取成功', ThreadModel::ageRangeList());
    }

    public function identifyList()
    {
        return $this->success('获取成功', ThreadModel::identifyList());
    }

    public function educationList()
    {
        return $this->success('获取成功', ThreadModel::educationList());
    }

    public function threadTypeList()
    {
        return $this->success('获取成功', ThreadModel::threadTypeList());
    }


}