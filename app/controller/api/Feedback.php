<?php

namespace app\controller\api;
use app\model\api\Feedback as FeedbackModel;
/**
 * 意见反馈接口
 */
class Feedback extends BaseApi
{
    public $noNeedLogin = [''];
    public $noNeedCheckSign = ['saveFeedback'];

    //意见反馈
    public function saveFeedback()
    {
        $params = $this->request->post();
        $this->commonApiValidate($params, 'app\validate\api\feedback\Feedback', 'saveFeedback');
        return $this->success('提交成功', FeedbackModel::saveFeedback($params));
    }

}