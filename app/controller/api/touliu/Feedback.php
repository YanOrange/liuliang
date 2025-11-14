<?php

namespace app\controller\api\touliu;
use app\controller\api\BaseApi;
use app\model\admin\Feedback as AdminFeedback;

/**
 * 反馈
 */
class Feedback extends BaseApi
{
    /**
     * 反馈提交
     *
     * @return void
     */
    public function FeedbackAdd()
    {
        $params = $this->request->post();
        $this->commonApiValidate($params, 'app\validate\api\feedback\Feedback', 'saveFeedback');
        return $this->success('提交成功', AdminFeedback::FeedbackAdd($params));   
    }
}