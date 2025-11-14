<?php

namespace app\controller\api\h5;

use app\controller\api\BaseApi;
use app\lib\api\service\H5CustomerLinkServiceV1;
use app\model\api\Captcha;
use app\model\api\h5\FlowPvUv;
use app\model\api\h5\Thread as ThreadModel;
use app\lib\api\service\H5CustomerLinkService;

class Thread extends BaseApi
{
    protected $noNeedLogin = ['*'];
    protected $noNeedCheckSign = ['*'];

    public function getPayApplyForFlowStatus(){
        $params = $this->request->post();
        $this->commonApiValidate($params, 'app\validate\api\h5\Thread', 'getPayApplyForFlowStatus');
        return $this->success('订单状态', ThreadModel::getPayApplyForFlowStatus($params));
    }

    //h5付费报名领取
    public function payApplyForFlow()
    {
        $params = $this->request->post();
        extract($params);
        Captcha::checkCaptcha(['phone' => $phone, 'type' => 5], $captcha);
        $this->commonApiValidate($params, 'app\validate\api\h5\Thread', 'payApplyForFlow');
        return $this->success('领取成功', ThreadModel::payApplyForFlow($params));
    }

    //h5免费报名领取
    public function freeApplyForFlow()
    {
        $params = $this->request->post();
        extract($params);
        //Captcha::checkCaptcha(['phone' => $phone, 'type' => 5], $captcha);
        $this->commonApiValidate($params, 'app\validate\api\h5\Thread', 'freeApplyForFlow');
        $data = ThreadModel::freeApplyForFlow($params);
        try {
            FlowPvUv::setH5PvUv(['h5_uid' => $params['h5_uid'] ?? '', 'for_flow_id' => $params['for_flow_id'] ?? 0, 'position' => 'button']);
        }catch (\Exception $e) {}
        return $this->success('领取成功', $data);
//        return $this->success('领取成功', ThreadModel::freeApplyForFlow($params));
    }

    //获取已领取主题客服二维码
    public function getApplyQrCode()
    {
        $params = $this->request->post();
        $this->commonApiValidate($params, 'app\validate\api\h5\Thread', 'getApplyQrCode');
        return $this->success('客服二维码', \app\model\api\h5\Thread::getApplyQrCode($params));
    }

    //长按识别二维码
    public function discernQrCode()
    {
        $params = $this->request->post();
        $this->commonApiValidate($params, 'app\validate\api\h5\Thread', 'discernQrCode');
        return $this->success('长按识别二维码', \app\model\api\h5\Thread::discernQrCode($params));
    }

    //获取微信小程序
    public function customerWxLink()
    {
        $params = $this->request->post();
        return $this->success('微信小程序', \app\model\api\h5\Thread::customerWxLink($params));
    }

    //获取销售客服链接
    public function customerLink()
    {
        $params = $this->request->param();
        $customerLink = H5CustomerLinkService::getCustomerServiceId($params,'yqh5_gdt1');
        return redirect($customerLink);
    }

    //获取销售客服链接
    public function szmCustomerLink()
    {
        $params = $this->request->param();
        $customerLink = H5CustomerLinkService::szmCustomerServiceId($params);
        return redirect($customerLink);
    }

    //获取销售企微昵称
    public function szmCustomerNickname()
    {
        $params = $this->request->param();
        $customerNickname = H5CustomerLinkService::szmCustomerNickname($params);
        return $this->success('销售昵称',$customerNickname);
    }

    # 获取客服获客链接
    public function getCustomerService()
    {
        $params = $this->request->param();
        $this->commonApiValidate($params, 'app\validate\api\h5\Thread', 'getCustomerService');
        $customer = H5CustomerLinkServiceV1::getCustomerServiceId($params);

        try {
            FlowPvUv::setH5PvUv(['h5_uid' => $params['h5_uid'] ?? '', 'for_flow_id' => $params['for_flow_id'] ?? 0, 'position' => 'button']);
        }catch (\Exception $e) {}

        return $this->success('客服信息', $customer);
    }
}