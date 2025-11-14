<?php
/**
 * 推荐阅读表模型
 */

namespace app\model\admin;

use laytp\BaseModel;
use think\model\concern\SoftDelete;

class Article extends BaseModel
{
    use SoftDelete;

    //模型名
    protected $name = 'article';

    // 追加属性
    protected $append = [
        'merchant_names',
    ];
    public function getMerchantNamesAttr($value, $data)
    {
        if (!empty($data['merchant_id'])) {
            $merchantArray = explode(',', $data['merchant_id']);
            $merchantNames = Merchant::field('merchant_name')->whereIn('id', $merchantArray)->select()->toArray();
            if (!empty($merchantNames)) {
                $merchantNamesList = array_column($merchantNames, 'merchant_name');
                return implode('、', $merchantNamesList);
            }
        }
        return '-';
    }

   /* public function merchant()
    {
        return $this->belongsTo('app\model\admin\Merchant','merchant_id','id')->removeOption('soft_delete');
    }*/

}
