<?php

namespace app\validate\api\h5;

use app\validate\BaseValidate;
class OppoAdvertiser extends BaseValidate
{
    protected $rule = [
        'channel'        => 'require',
        'for_flow_id'    => 'require',
        'pageId'         => 'require',
        'tid'            => 'require',
        'lbid'           => 'require',
        'uctrackid'      => 'require',
        'qz_gdt'         => 'require',
        'adid'         => 'require',
        'requestid'         => 'require',
        'logExtra'         => 'require',
        'webConversionId'     => 'require',
        'clientTime'         => 'require',
        'conversionId'         => 'require',
        'qhclickid'         => 'require',
        'trans_id'         => 'require',
        'clickid'         => 'require',
    ];

    protected $message = [
        'channel.require' => '渠道参数错误',
        'for_flow_id.require' => '信息流参数错误',
        'pageId.require' => '信息流参数错误',
        'tid.require' => '信息流参数错误',
        'lbid.require' => '信息流参数错误',
        'uctrackid.require' => '信息流参数错误',
        'qz_gdt.require' => '信息流参数错误',
        'adid.require' => '信息流参数错误',
        'requestid.require' => '信息流参数错误',
        'logExtra.require' => '信息流参数错误',
        'webConversionId.require' => '信息流参数错误',
        'clientTime.require' => '信息流参数错误',
        'conversionId.require' => '信息流参数错误',
        'qhclickid.require' => '信息流参数错误',
        'trans_id.require' => '信息流参数错误',
        'clickid.require' => '信息流参数错误',
    ];

    /**
     * 验证场景
     */
    protected $scene = [
        'callback' => ['channel','pageId','tid','lbid'],
        'ucCallback' => ['channel','for_flow_id','uctrackid'],
        'gdtCallback' => ['channel','for_flow_id','qz_gdt'],
        'vivoCallback' => ['channel','adid','requestid'],
        'xiaomiCallback' => ['channel','logExtra','webConversionId','conversionId'],
        'c360Callback' => ['channel','qhclickid','trans_id'],
        'douyinCallback' => ['channel','for_flow_id'],
        'bzhanCallback'  => ['channel','for_flow_id', 'track_id'],
    ];
}