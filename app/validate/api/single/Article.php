<?php

namespace app\validate\api\single;

use app\validate\BaseValidate;

class Article extends BaseValidate
{
    protected $rule = [
        'channel'      => 'require',
        'article_id'   => 'require',
    ];

    protected $message = [
        'channel.require' => '渠道参数错误',
        'article_id.require' => '文章参数错误',
    ];

    /**
     * 验证场景
     */
    protected $scene = [
        'getArticleList' => ['channel'],
        'getArticleDetail' => ['article_id'],
    ];
}