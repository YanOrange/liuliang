<?php

namespace app\controller\admin\thread;

use app\model\admin\api\ThreadSource as ThreadSourceModel;
use app\service\admin\UserServiceFacade;
use app\validate\admin\api\ThreadSourceValidate;
use laytp\controller\Backend;

/**
 * 线索来源控制器
 */
class ThreadSource extends Backend
{
    protected $model;//当前模型对象

    protected function _initialize() {
        $this->model = new \app\model\admin\thread\ThreadSource();
    }


    /**
     * 列表
     * @return false|string|\think\response\Json\
     */
    public function getSourceList()
    {
        return $this->success('线索来源列表', $this->model->getSourceList());
    }

}