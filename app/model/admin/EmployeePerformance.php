<?php
/**
 * 组员绩效管理
 *
 * @date 2022-11-22
 */

namespace app\model\admin;

use laytp\BaseModel;
use think\model\concern\SoftDelete;

class EmployeePerformance extends BaseModel
{
    use SoftDelete;

    //模型名
    protected $name = 'employee_performance_info';


    public function employee()
    {
        return $this->hasMany(\app\model\admin\role\User::class, 'admin_user_id');
    }

}