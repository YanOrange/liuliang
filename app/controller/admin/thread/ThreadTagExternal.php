<?php

namespace app\controller\admin\thread;

use app\validate\admin\api\ThreadTagValidate;
use laytp\controller\Backend;

/**
 * 线索标签控制器
 */
class ThreadTagExternal extends Backend {

    protected $noNeedAuth = ['*'];
    protected $noNeedLogin = [''];

    protected $model;//当前模型对象

    protected function _initialize() {
        $this->model = new \app\model\admin\thread\ThreadTagExternal();
    }


    /**
     * 根据线索ID获取标签列表
     * @return false|string|\think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getThreadTagList() {
        $params = $this->request->post();
        return $this->success('线索标签列表', $this->model->getThreadTagList($params));
    }


}