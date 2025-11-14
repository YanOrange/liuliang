<?php
/**
 * 客服表模型
 */

namespace app\model\api;

use app\lib\api\exception\Exception;
use laytp\BaseModel;
use think\model\concern\SoftDelete;

class Customer extends BaseModel
{
    use SoftDelete;

    //模型名
    protected $name = 'customer';

    public static function getCustomerList($params = [])
    {
//        $channelId = Channel::where('channel_name', $params['channel'])->field('app_id')->find(); # 查询渠道信息
//        if (!$channelId) {
//            throw new Exception('渠道不存在');
//        }
//
//        $appClassId = App::where('id', $channelId->app_id)->field('app_class_id')->find();
//        if (!$appClassId) {
//            throw new Exception('应用不存在');
//        }
//
//        $merchantId = Merchant::where('app_class_id', $appClassId->app_class_id)->field('id')->find();
//        if (!$merchantId) {
//            throw new Exception('商户不存在');
//        }
//
//        $customerList = self::where('status',1)->where('merchant_id', $merchantId->id)
//            ->orderRaw('RAND()')
//            ->limit(3)
//            ->select();
//        return $customerList;

        $customerList = [
            ['photo' => 'http://cdnwm.yuluojishu.com/uploads/20240525/774563ae03d5f8d2c3e99d3e3a186a2a.png', 'name' => '李磊', 'practice' => '主任律师', 'proficient_in' => '合同纠纷/侵权纠纷/劳动争议', 'service_number' => '2010', 'age' => 7],
            ['photo' => 'http://cdnwm.yuluojishu.com/uploads/20240525/77d1f7828c7101c0989fa98b137acd21.png', 'name' => '李婉秋 ', 'practice' => '主任律师', 'proficient_in' => '婚姻家事/债权纠纷/劳动纠纷', 'service_number' => '2213', 'age' => 5],
            ['photo' => 'http://cdnwm.yuluojishu.com/uploads/20240525/847538c870d05c3cf7a3441cf9a9d2c9.png', 'name' => '张立娟', 'practice' => '律师', 'proficient_in' => '劳动纠纷/婚姻家事/合同纠纷', 'service_number' => '2276', 'age' => 6],
            ['photo' => 'http://cdnwm.yuluojishu.com/uploads/20240525/b1c5c6e0c303f2326ad0d8d39c2f0351.png', 'name' => '夏彬彬', 'practice' => '律师', 'proficient_in' => '劳动纠纷/民事纠纷/公司纠纷', 'service_number' => '2057', 'age' => 6],
            ['photo' => 'http://cdnwm.yuluojishu.com/uploads/20240525/445a125a1cfa782e84a33cbe8e6776bf.png', 'name' => '王晨', 'practice' => '律师', 'proficient_in' => '合同纠纷/婚姻家事/劳动纠纷', 'service_number' => '1998', 'age' => 5],
            ['photo' => 'http://cdnwm.yuluojishu.com/uploads/20240525/1f1dc5f6cb22a66f08d8c39a9a5e2ba4.png', 'name' => '朱俊鹏', 'practice' => '律师', 'proficient_in' => '借贷纠纷/合同纠纷/劳动纠纷', 'service_number' => '2007', 'age' => 5],
            ['photo' => 'http://cdnwm.yuluojishu.com/uploads/20240525/af5c061ddd421f3a3a0721968a723e02.png', 'name' => '刘迎斌', 'practice' => '律师', 'proficient_in' => '合同纠纷/交通事故/劳动纠纷', 'service_number' => '1977', 'age' => 6]
        ];

        $data = [];
        foreach (array_rand($customerList, 3) as $index) {
            $data[] = $customerList[$index];
        }
        return $data;
    }
}
