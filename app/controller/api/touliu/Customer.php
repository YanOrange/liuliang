<?php

namespace app\controller\api\touliu;

use app\controller\api\BaseApi;
use app\model\api\Customer as ApiCustomer;

/**
 * 留资页接口
 */
class Customer extends BaseApi
{
    public $noNeedLogin = [''];
    public $noNeedCheckSign = ['getApplyCustomerPhone'];

    /**
     * 获取客服列表 每次返回3个随机的客服
     * @return false|string|\think\response\Json
     * @throws \app\lib\api\exception\Exception
     */
    public function getCustomerList()
    {
        $params = $this->request->post();
        return $this->success('获取客服成功', ApiCustomer::getCustomerList($params));
    }
}