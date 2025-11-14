<?php

namespace app\model\admin\website;
use laytp\BaseModel;
use think\model\concern\SoftDelete;
class WebsitePostList extends BaseModel
{
    use SoftDelete;

    //模型名
    protected $name = 'website_post_list';


}