<?php
/**
 * 后台行为转化记录表模型
 */

namespace app\model\admin;

use app\model\api\TodayReceiveMonitorData;
use laytp\BaseModel;
use think\model\concern\SoftDelete;

class AdvertiserCallbackRecord extends BaseModel
{
    use SoftDelete;

    //模型名
    protected $name = 'advertiser_callback_record';

    // 追加属性
    protected $append = [
    ];

    public function getSourceAttr($value, $data)
    {
        $source = $value;
        if($data['app_bundle_id'] == 'com.yuluojishu.kuaixue' && $data['channel_name'] == 'kuaixue_oppo')
        {
            $receiveSource = TodayReceiveMonitorData::where('oaid', $data['oaid'])
                ->where('channel', $data['channel_name'])
                ->where('app_bundle_id', $data['app_bundle_id'])
                ->order('id desc')
                ->value('source');
            if(isset($receiveSource) && !empty($receiveSource)){
                if($receiveSource == 1){
                    $source = 3;
                }
                if($receiveSource == 2){
                    $source = 4;
                }
            }else{
                $source = 3;
            }
        }
        return $source;
    }

}
