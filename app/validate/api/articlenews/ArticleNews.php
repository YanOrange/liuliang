<?php

namespace app\validate\api\articlenews;
use app\validate\BaseValidate;
class ArticleNews extends BaseValidate
{
    protected $rule = [
        'channel'      => 'require',
        'article_id'   => 'require',
    ];

    protected $message = [
        'channel.require' => '渠道不能为空',
        'article_id.require' => '文章id不能为空',
    ];

    /**
     * 验证场景
     */
    protected $scene = [
        'getArticleNews' => ['channel'],
        'getArticleNewsListV2' => ['channel'],
        'getArticleNewsListV5' => ['channel'],
        'getArticleNewsDetailV2' => ['article_id'],
    ];
}