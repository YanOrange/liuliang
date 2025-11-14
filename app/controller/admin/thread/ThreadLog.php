<?php

namespace app\controller\admin\thread;

use laytp\controller\Backend;

/**
 * 线索日志控制器
 */
class ThreadLog extends Backend
{
    protected $noNeedAuth = ['*'];
    protected $noNeedLogin = [''];

    protected $model;//当前模型对象

    protected function _initialize() {
        $this->model = new \app\model\admin\thread\ThreadLog();
    }

    public function getThreadLogList(){
        $params = $this->request->param();
        $threadLogList = $this->model->getThreadLogList($params);
        return $this->success('数据获取成功', $threadLogList);
    }

}