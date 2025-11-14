<?php

namespace app\controller\admin\thread;

use app\model\admin\api\ThreadUserStatus as ThreadUserStatusModel;
use app\validate\admin\api\ThreadUserStatusValidate;
use laytp\controller\Backend;

/**
 * 用户状态关联控制器
 */
class ThreadUserStatus extends Backend
{
    protected $noNeedAuth = ['*'];
    protected $noNeedLogin = [''];

    protected $model;//当前模型对象

    protected function _initialize() {
        $this->model = new \app\model\admin\thread\ThreadUserStatus();
    }



    /**
     * 获取用户状态列表
     * @return false|string|\think\response\Json
     */
    public function getThreadUserStatus()
    {
        $params = $this->request->post();
        return $this->success('状态列表', $this->model->getThreadUserStatus($params));
    }

}