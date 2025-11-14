<?php

namespace app\validate\admin\single;

use app\validate\BaseValidate;

class Evaluate extends BaseValidate {
    //数组顺序就是检测的顺序
    protected $rule = [
        'course_id' => 'require',
        'resource_id' => 'require',
        'nickname' => 'require|length:2,10',
        'phone' => 'require|checkIsPhone',
        'avatar' => 'require',
        'score' => 'require',
        'content' => 'require',
        'status' => 'require',
        'evaluate_time' => 'require',
    ];
    //定义内置方法检验失败后返回的字符
    protected $message = [
        'be_evaluated_id.require' => '请选择被评价ID',
        'nickname.require' => '请输入昵称',
        'nickname.length' => '昵称长度2-10字',
        'phone.require' => '请输入手机号',
        'avatar.require' => '请输入上传头像',
        'score.require' => '请选择评分',
        'content.require' => '请输入评价内容',
        'status.require' => '请选择状态',
        'evaluate_time.require' => '请选择评论时间',
    ];

    protected $scene = [
        'add' => [ 'be_evaluated_id', 'nickname', 'phone', 'avatar', 'score', 'content', 'status', 'evaluate_time'],
        'edit' => [ 'be_evaluated_id', 'nickname', 'phone', 'avatar', 'score', 'content', 'status', 'evaluate_time'],
    ];
}