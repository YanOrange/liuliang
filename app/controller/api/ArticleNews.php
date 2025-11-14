<?php

namespace app\controller\api;
use app\model\api\ArticleNews as ArticleNewsModel;
/**
 * 文章接口
 */
class ArticleNews extends BaseApi
{
    protected $noNeedLogin = ['getArticleNews','getArticleNewsDetailV2','getArticleNewsListV5'];
    protected $noNeedCheckSign = ['getArticleNews'];

    //文章列表
    public function getArticleNews()
    {
        $params = $this->request->post();
        return $this->success('文章列表', ArticleNewsModel::getArticleNews($params));
    }
    //文章列表V3
    public function getArticleNewsV3()
    {
        $params = $this->request->post();
        return $this->success('文章列表', ArticleNewsModel::getArticleNewsV3($params));
    }

    //文章列表V2
    public function getArticleNewsListV2()
    {
        $params = $this->request->post();
        $this->commonApiValidate($params, 'app\validate\api\articlenews\ArticleNews', 'getArticleNewsListV2');
        return $this->success('文章列表', ArticleNewsModel::getArticleNewsListV2($params));
    }
    //逾期文章列表
    public function getArticleNewsListV3()
    {
        $params = $this->request->post();
        $this->commonApiValidate($params, 'app\validate\api\articlenews\ArticleNews', 'getArticleNewsListV2');
        return $this->success('文章列表', ArticleNewsModel::getArticleNewsListV3($params));
    }

    //文章详情
    public function getArticleNewsDetailV2()
    {
        $params = $this->request->post();
        $this->commonApiValidate($params, 'app\validate\api\articlenews\ArticleNews', 'getArticleNewsDetailV2');
        return $this->success('文章详情', ArticleNewsModel::getArticleNewsDetailV2($params));
    }

}