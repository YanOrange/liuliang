<?php

namespace app\controller\api\touliu;

use app\controller\api\BaseApi;
use app\model\api\Thread as ThreadModel;

/**
 * 留资页接口
 */
class Thread extends BaseApi
{
    public $noNeedLogin = [''];
    public $noNeedCheckSign = ['getApplyCustomerPhone'];

    /**
     * 留资提交
     *
     * @return void
     */
    public function saveFreeApply()
    {
        $params = $this->request->post();
        $this->commonApiValidate($params, 'app\validate\api\thread\Thread', 'saveFreeApply');
        return $this->success('报名成功', ThreadModel::saveFreeApply($params));
    }

    //获取客服获客链接
    public function getMerLink()
    {
        $params = $this->request->post();
        return $this->success('获取客服获客链接', ThreadModel::getMerLink($params));
    }

    /**
     * 劳动仲裁线索提交
     * @return void
     */
    public function saveEmploymentContract()
    {
        $params = $this->request->post();
        $this->commonApiValidate($params, 'app\validate\api\thread\Thread', 'saveFreeApply');
        return $this->success('提交成功', ThreadModel::saveEmploymentContract($params));
    }
}