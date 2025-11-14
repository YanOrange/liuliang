<?php

namespace app\model\admin;

use laytp\BaseModel;
use think\model\concern\SoftDelete;

class ChannelDeliveryMode extends BaseModel
{
    use SoftDelete;

    //模型名
    protected $name = 'channel_delivery_mode';

    public static function createData($channelId = 0, $deliveryModeId = 0)
    {
        $data = DeliveryMode::getAllParentData($deliveryModeId);
        $endIdArr[] = $data[count($data) - 1];
        unset($data[count($data) - 1]);
        $data = array_values($data);
        krsort($data);
        $data = array_values($data);
        $data = array_merge($data, $endIdArr);
        if (!empty($data)) {
            $info = self::where('channel_id', $channelId)->find();
            $arr = ['channel_id' => $channelId];
            foreach ($data as $item => $val) {
                $arr['delivery_mode_id' . ($item + 1)] = $val;
            }
            if (!empty($info)) {
                $count = count($data) + 1;
                $tempData = [];
                for ($i = $count; $i<=10; $i++) {
                    $tempData['delivery_mode_id' . $i] = 0;
                }
                $info->save(array_merge($arr, $tempData));
            } else {
                self::create($arr);
            }
        }
    }
}
