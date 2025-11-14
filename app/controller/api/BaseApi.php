<?php

namespace app\controller\api;
use laytp\controller\Api;
use laytp\traits\JsonReturn;
use app\lib\api\exception\Exception;
/**
 * API基类
 */
class BaseApi extends Api
{
    use JsonReturn;
    //公共API的验证方法
    protected function commonApiValidate($params, $class, $scene, $data = [])
    {
        $validate = new $class;
        $ret = $validate->scene($scene)->check($params);
        if (!$ret) {
            new Exception($validate->getError(), 1,200, $data);
        }
    }
}