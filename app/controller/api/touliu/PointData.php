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
        // 补充必要字段（根据数据库表结构调整）
        $params['create_time'] = time(); // 创建时间
        $params['ip'] = $this->request->ip(); // 上报IP
        $params['device'] = $this->request->header('User-Agent'); // 设备信息（可选）
        event('PointData', $params);
        return $this->success('埋点数据上报成功');
    }
}