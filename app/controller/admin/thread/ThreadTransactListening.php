<?php

namespace app\controller\admin\thread;

use laytp\controller\Backend;

/**
 * 办理试听课程关联控制器
 */
class ThreadTransactListening extends Backend
{
    protected $noNeedAuth = ['*'];
    protected $noNeedLogin = [''];


    protected $model;//当前模型对象

    protected function _initialize() {
        $this->model = new \app\model\admin\thread\ThreadTransactListening();
    }

    /**
     * 我的办理试听列表
     * @return false|string|\think\response\Json
     */
    public function getThreadTransactListening()
    {
        $params = $this->request->param();
        $threadTransactListeningList = $this->model->getThreadTransactListening($params);
        return $this->success('数据获取成功', $threadTransactListeningList);
    }


}