<?php
/**
 * 后台应用分类表模型
 */

namespace app\model\admin;

use laytp\BaseModel;
use think\model\concern\SoftDelete;

class BiCustomerPaiban extends BaseModel
{
    use SoftDelete;

    //模型名
    protected $name = 'bi_customer_paiban';

    protected $append = [
        'responsible'
    ];

    public function getResponsibleAttr($value,$data)
    {
        $responsible = '';
        if($data['new_people_or_responsible'] == 0) $responsible = '';
        if($data['new_people_or_responsible'] == 1) $responsible = '新人';
        if($data['new_people_or_responsible'] == 2) $responsible = '主管';
        return $responsible;
    }

     public function customer()
    {
        return $this->belongsTo('app\model\admin\Customer','customer_id','id')->removeOption('soft_delete');
    }

}
