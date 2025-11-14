<?php

namespace app\controller\api;

/**
 * 文章接口
 */
class ServiceMessage extends BaseApi
{
    //发送消息
    public function addMessage()
    {
        $params = $this->request->post();
        return $this->success('发送消息', []);
    }

}