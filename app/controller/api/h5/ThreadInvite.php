<?php

namespace app\controller\api\h5;

use app\controller\api\BaseApi;
use app\model\api\Captcha;
use app\model\api\h5\FlowPvUv;
use app\model\api\h5\ThreadInvite as ThreadInviteModel;
use app\lib\api\service\H5CustomerLinkService;

class ThreadInvite extends BaseApi
{
    protected $noNeedLogin = ['*'];
    protected $noNeedCheckSign = ['*'];

    //留资项
    public function getGatherInfoData()
    {
        $token = $this->request->header('token');
        return $this->success('获取成功', ThreadInviteModel::getGatherInfoData($token));
    }

    //h5免费报名领取
    public function freeApplyInvite()
    {
        $params = $this->request->post();
        $token = $this->request->header('token');
        $this->commonApiValidate($params, 'app\validate\api\h5\ThreadInvite', 'freeApplyInvite');
        $data = ThreadInviteModel::freeApplyInvite($params,$token);
        try {
            FlowPvUv::setH5PvUv(['h5_uid' => $params['h5_uid'] ?? '', 'for_flow_id' => $params['for_flow_id'] ?? 0, 'position' => 'button']);
        }catch (\Exception $e) {}
        return $this->success('领取成功', $data);
//        return $this->success('领取成功', ThreadInviteModel::freeApplyInvite($params,$token));
    }

    //获取已领取主题客服二维码
    public function getApplyQrCode()
    {
        $params = $this->request->post();
        return $this->success('客服二维码', ThreadInviteModel::getApplyQrCode($params));
    }

    //长按识别二维码
    public function discernQrCode()
    {
        $params = $this->request->post();
        return $this->success('长按识别二维码', ThreadInviteModel::discernQrCode($params));
    }
}