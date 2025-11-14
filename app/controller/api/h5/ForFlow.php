<?php

namespace app\controller\api\h5;

use app\controller\api\BaseApi;
use app\model\api\h5\ForFlow as ForFlowModel;

class ForFlow extends BaseApi
{
    protected $model = null;
    protected $noNeedLogin = ['*'];
    protected $noNeedCheckSign = ['*'];

    public function homeInfo()
    {
        $params = $this->request->post();
        $this->commonApiValidate($params, 'app\validate\api\h5\ForFlow', 'getForFlowDetail');
        return $this->success('主题数据', ForFlowModel::getForFlowDetail($params));
    }

    public function getFlowPage()
    {
        $params = $this->request->post();
        $this->commonApiValidate($params, 'app\validate\api\h5\ForFlow', 'getForFlowDetail');
        return $this->success('h5信息流', ForFlowModel::getFlowPage($params));
    }

}