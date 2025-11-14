<?php
/**
 * 后台应用分类表模型
 */

namespace app\model\admin;

use app\model\admin\Customer;
use laytp\BaseModel;
use think\model\concern\SoftDelete;

class OverdueAppCustomer extends BaseModel
{
    use SoftDelete;

    //模型名
    protected $name = 'overdue_app_customer';

    protected $append = ['nickname'];

    public function getNicknameAttr($value, $data)
    {
        $customerId = explode(',',$data['customer_ids']);
        $customer = Customer::whereIn('id',$customerId)->column('nickname');
        return implode(',',$customer);
    }

}
