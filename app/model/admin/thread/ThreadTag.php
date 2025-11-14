<?php
/**
 * 后台线索标签模型
 */

namespace app\model\admin\thread;

use app\model\admin\Merchant;
use laytp\BaseModel;
use think\model\concern\SoftDelete;

class ThreadTag extends BaseModel {
    use SoftDelete;

    //模型名
    protected $name = 'thread_tag';

    // 追加属性
    protected $append = [
        'merchant_names',
        'cate_name',
    ];

    public function getMerchantNamesAttr($value, $data) {
        if (isset($data['merchant_ids']) && !empty($data['merchant_ids'])) {
            $appArray = explode(',', $data['merchant_ids']);
            $merchantNames = Merchant::field('merchant_name')->whereIn('id', $appArray)->select()->toArray();
            if (!empty($merchantNames)) {
                $merchantNamesList = array_column($merchantNames, 'merchant_name');
                return implode('、', $merchantNamesList);
            }
        }
        return '-';
    }

    public function getCateNameAttr($value, $data) {
        if (!empty($data['cate_id'])) {
            $tagCategory = ThreadTagCategory::field('title')->where('id', $data['cate_id'])->find();
            if (!empty($tagCategory)) {
                return $tagCategory->title;
            }
        }
        return '-';
    }

}
