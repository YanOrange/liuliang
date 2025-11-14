<?php
/**
 * 后台隐私协议表模型
 */

namespace app\model\admin;

use laytp\BaseModel;
use think\model\concern\SoftDelete;

class PrivacyAgreement extends BaseModel
{
    use SoftDelete;

    //模型名
    protected $name = 'privacy_agreement';
    protected $append = [
        'channel_names'
    ];
    public function app()
    {
        return $this->belongsTo('app\model\admin\App','app_id','id')->field('id,app_name')->removeOption('soft_delete');
    }

    public function getChannelNamesAttr($value, $data)
    {
        if (!empty($data['channel_ids'])) {
            $channelArray = explode(',', $data['channel_ids']);
            $channelNames = Channel::field('channel_name')->whereIn('id', $channelArray)->select()->toArray();
            if (!empty($channelNames)) {
                $channelNamesList = array_column($channelNames, 'channel_name');
                return implode('、', $channelNamesList);
            }
        }
        return '-';
    }

}
