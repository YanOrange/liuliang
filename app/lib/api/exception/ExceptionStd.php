<?php
namespace app\lib\api\exception;

use think\Response;

class ExceptionStd
{
    protected $msg = '错误消息';
    protected $code = 0;  //TOAST自动弹出消息
    protected $data = null;
    public function __construct($msg, $code = 1, $status_code = 200)
    {
        $this->msg = $msg;
        $this->code = $code;
        $this->data = new \stdClass();
        $this->send($status_code);
    }
    protected function send($code = 200)
    {
        $data = [
            'code' => $this->code,
            'msg' => $this->msg,
            'time' => time(),
            'data' =>  $this->data,
        ];
        Response::create($data, 'json', $code)->send();
        die;
    }
}
