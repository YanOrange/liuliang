<?php
/**
 * 后台应用分类表模型
 */

namespace app\model\admin;

use laytp\BaseModel;
use think\model\concern\SoftDelete;

class FlowInfoRule extends BaseModel
{
    use SoftDelete;

    //模型名
    protected $name = 'flow_info_rule';

    protected $append = [
        'channel_name'
    ];

    public function getChannelNameAttr($value,$data)
    {
        $channelName = '';
        if(!empty($data['channel_ids'])){
            $channelIds = explode(',',$data['channel_ids']);
            $channelName = implode(',',Channel::whereIn('id',$channelIds)->column('channel_name'));
        }
        return $channelName;
    }

}
