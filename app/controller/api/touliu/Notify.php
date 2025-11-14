<?php
namespace app\controller\api\touliu;

use app\controller\api\BaseApi;
use app\lib\service\external\OppoService;

class Notify extends BaseApi {

    public $noNeedLogin = ['*'];
    public $noNeedCheckSign = ['*'];

    public function oppo()
    {
        $params = $this->request->param();

        OppoService::notify($params);

        return $this->success('请求成功');
    }
}