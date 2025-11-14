<?php

namespace app\validate\admin\channel;

use think\Validate;
use app\model\admin\Channel as ChannelModel;
use app\validate\BaseValidate;

class Channel extends BaseValidate
{
    //数组顺序就是检测的顺序
    protected $rule = [
        'source'         => 'require',
        'channel_name'          => 'require|length:2,30|checkChannelName:',
        'is_login_show'         => 'require',
        'app_id'                => 'require',
        'retention_page_desc'   => 'require',
        'cost_price'            => 'require|egt:0',
        'startup_page_image'    => 'require',
        'app_home_desc'          => 'require',
        'wxmini_path_ids' => 'require|length:1,200',
    ];

        //定义内置方法检验失败后返回的字符
    protected $message = [
        'source.require'             => '请选择来源',
        'channel_name.require'             => '渠道名不能为空',
        'channel_name.length'              => '渠道名长度2-30',
        'is_login_show.require'        => '请设置启动app登录状态',
        'app_id.require'        => '请选择关联应用',
        'retention_page_desc.require'    => '请设置留资页文案',
        'cost_price.require'    => '请输入线索成本',
        'cost_price.egt'        => '线索成本必须大于等于0',
        'startup_page_image.require'        => '请上传启动图',
        'app_home_desc.require'        => '请填写首页文案',
        'wxmini_path_ids.require' => '请选择微信小程序路径',
        'wxmini_path_ids.length' => '微信小程序路径选择过多',
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
        'add' => ['source','channel_name','is_login_show','app_id','retention_page_desc','cost_price', 'wxmini_path_ids'],
        'edit' => ['source','channel_name','is_login_show','app_id','retention_page_desc','cost_price', 'wxmini_path_ids'],
    ];
}