<?php

namespace app\model\admin\website;
use laytp\BaseModel;
use think\model\concern\SoftDelete;
class WebsiteStyleShow extends BaseModel
{
    use SoftDelete;

    //模型名
    protected $name = 'website_style_show';

    public function imageDesc()
    {
        return $this->hasMany(\app\model\admin\website\WebsiteStyleImage::class, 'style_id');
    }
}