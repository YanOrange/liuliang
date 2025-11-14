<?php

namespace app\validate\admin\channel;

use think\Validate;
use app\model\admin\Channel as ChannelModel;
use app\validate\BaseValidate;

class AdvertiserCallbackChannel extends BaseValidate
{
    //数组顺序就是检测的顺序
    protected $rule = [
        'channel_id'         => 'require',
        'behavior_type'          => 'require',
        'attributional_type'          => 'require',
    ];

        //定义内置方法检验失败后返回的字符
    protected $message = [
        'channel_id.require'             => '请选择渠道',
        'behavior_type.require'             => '请选择用户行为',
        'attributional_type.require'             => '请选择归因方式',
    ];

    //验证渠道名的唯一性
    protected function checkChannelName($channelName, $rule, $data) {
        $channelId = ChannelModel::getFieldByChannelName($channelName, 'id');
        if (!isset($data['id'])) {
            if ($channelId) {
                return '渠道名称已存在';
            }
        }
        if ($channelId && $channelId != $data['id']) {
            return '渠道名称已存在';
        }
        return true;
    }
    protected $scene = [
        'add' => ['channel_id','behavior_type','attributional_type'],
        'edit' => ['channel_id','behavior_type','attributional_type'],
    ];
}