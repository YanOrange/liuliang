<?php

namespace app\controller\api;
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
        event('PointDataV1', $params);
        return $this->success('埋点数据上报成功');
    }

    //埋点上报
    public function h5PonitReporteData()
    {
        $params = $this->request->post();
        $this->commonApiValidate($params, 'app\validate\api\point_data\PointData', 'h5PonitReporteData');
        event('H5PointData', $params);
        return $this->success('埋点数据上报成功');
    }

    //埋点上报
    public function ponitReporteDataV4()
    {
        $params = $this->request->post();
        return $this->success('埋点数据上报成功');
    }

    //埋点上报V5
    public function ponitReporteDataV5()
    {
        $params = $this->request->post();
        $this->commonApiValidate($params, 'app\validate\api\point_data\PointData', 'ponitReporteData');
        event('PointDataV4', $params);
        return $this->success('埋点数据上报成功');
    }

}