<?php

namespace app\controller\api;
use app\model\api\Banner as BannerModel;
/**
 * 轮播图接口
 */
class Banner extends BaseApi
{
    public $noNeedLogin = [''];
    public $noNeedCheckSign = [''];

    //轮播图列表
    public function getBannerList()
    {
        $params = $this->request->post();
        $this->commonApiValidate($params, 'app\validate\api\banner\Banner', 'getBannerList');
        return $this->success('轮播图列表', BannerModel::getBannerList($params));
    }
}