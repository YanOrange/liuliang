<?php

namespace app\model\admin;

use laytp\BaseModel;
use think\model\concern\SoftDelete;

class ArticleNews extends BaseModel
{
    use SoftDelete;

    //模型名
    protected $name = 'article_news';

    public function getContentAttr($value, $data)
    {
        return richText($value);
    }
}