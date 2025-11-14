<?php

namespace app\validate\api\article;
use app\validate\BaseValidate;
class Article extends BaseValidate
{
    protected $rule = [
        'channel'      => 'require',
        'app_bundle_id'      => 'require',
        'article_id'      => 'require',
    ];

    protected $message = [
        'channel.require' => '渠道参数错误',
        'app_bundle_id.require' => '包名参数错误',
        'article_id.require' => '请选择阅读的文章',
    ];

    /**
     * 验证场景
     */
    protected $scene = [
        'getArticleDetail' => ['article_id'],
        'getMerchantArticleList' => ['channel','app_bundle_id'],
    ];
}