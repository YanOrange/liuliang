<?php

namespace app\controller\api;
use app\model\api\Thread as ThreadModel;
/**
 * 课程接口
 */
class Thread extends BaseApi
{
    public $noNeedLogin = [''];
    public $noNeedCheckSign = ['getApplyCustomerPhone'];
    //免费报名
    public function freeApplyCourse()
    {
        $params = $this->request->post();
        $this->commonApiValidate($params, 'app\validate\api\thread\Thread', 'freeApplyCourse');
        return $this->success('报名成功', ThreadModel::freeApplyCourse($params));
    }
    //获取已报名课程客服二维码
    public function getApplyQrCode()
    {
        $params = $this->request->post();
        //$this->commonApiValidate($params, 'app\validate\api\thread\Thread', 'freeApplyCourse');
        return $this->success('客服二维码', ThreadModel::getApplyQrCode($params));
    }
    //获取已报名课程客服二维码多机构
    public function getApplyQrCodeMore()
    {
        $params = $this->request->post();
        return $this->success('客服二维码', ThreadModel::getApplyQrCodeMore($params));
    }
    //长按识别二维码
    public function discernQrCode()
    {
        $params = $this->request->post();
        //$this->commonApiValidate($params, 'app\validate\api\thread\Thread', 'freeApplyCourse');
        return $this->success('长按识别二维码', ThreadModel::discernQrCode($params));
    }

    //获取客服手机号
    public function getApplyCustomerPhone()
    {
        $params = $this->request->post();
        //$this->commonApiValidate($params, 'app\validate\api\thread\Thread', 'freeApplyCourse');
        return $this->success('客服手机号', ThreadModel::getApplyCustomerPhone($params));
    }

    //设置在线客服跳微
    public function setServiceWechat()
    {
        $params = $this->request->post();
        //$this->commonApiValidate($params, 'app\validate\api\thread\Thread', 'freeApplyCourse');
        return $this->success('客服跳微', ThreadModel::setServiceWechat($params));
    }
    //获取客服获客链接
    public function getCustomerLink()
    {
        $params = $this->request->post();
        //$this->commonApiValidate($params, 'app\validate\api\thread\Thread', 'freeApplyCourse');
        return $this->success('获取客服获客链接', ThreadModel::getCustomerLink($params));
    }
}