<?php

namespace app\controller\api\touliu;

use app\controller\api\BaseApi;
/**
 * 埋点
 */
class PointData extends BaseApi
{
    public $noNeedLogin = ['*'];
    public $noNeedCheckSign = ['*'];
    //埋点上报
    public function ponitReporteData()
    {
        $params = $this->request->post();
        $this->commonApiValidate($params, 'app\validate\api\point_data\PointData', 'ponitReporteData');
        event('PointData', $params);
        return $this->success('埋点数据上报成功');
    }
}