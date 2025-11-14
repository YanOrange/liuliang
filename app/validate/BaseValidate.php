<?php
//基类验证
namespace app\validate;
use think\Validate;

class BaseValidate extends Validate
{
    //判断手机号格式
    protected function checkIsPhone($phone)
    {
        if (!preg_match("/^1[13456789]{1}\d{9}$/", $phone)){
            return '手机号不正确';
        }
        return true;
    }
}