<?php
namespace app\amservice\validate;

use core\Validate;

class ChatUserValidate extends Validate
{

    /**
     * 规则
     *
     * @var unknown
     */
    protected $rule = [
        'user_name' => 'require|token',
        'nickname' => 'require',
        'pwd' => 'require',
        'pwd_again' => 'require|confirm:pwd',
        'tel' => 'require',
    ];

    /**
     * 提示
     *
     * @var unknown
     */
    protected $message = [
        'user_name.require' => '用户名为空',
        'user_name.token' => '表单重复提交',
        'nickname.require' => '姓名为空',
        'pwd.require' => '密码为空',
        'pwd_again.require' => '重复密码为空',
        'pwd_again.confirm' => '两次密码不同',
        'tel.require' => '手机号需要填写',
    ];

    /**
     * 场景
     *
     * @var unknown
     */
    protected $scene = [
        
        'add' => [
            'user_name',
            'nickname',
            'pwd',
            'pwd_again',
            'tel',
            'email'
        ]

    ];
}