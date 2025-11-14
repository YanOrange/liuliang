<?php

namespace app\controller\api;
use app\model\api\Article as ArticleModel;
/**
 * 推荐阅读接口
 */
class Article extends BaseApi
{
    public $noNeedLogin = [''];
    public $noNeedCheckSign = [''];

    //推荐阅读详情
    public function getArticleDetail()
    {
        $params = $this->request->post();
        $this->commonApiValidate($params, 'app\validate\api\article\Article', 'getArticleDetail');
        return $this->success('推荐阅读详情', ArticleModel::getArticleDetail($params));
    }
    //推荐阅读详情V2
    public function getArticleDetailV2()
    {
        $params = $this->request->post();
        $this->commonApiValidate($params, 'app\validate\api\article\Article', 'getArticleDetail');
        return $this->success('推荐阅读详情', ArticleModel::getArticleDetailV2($params));
    }

}