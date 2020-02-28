<?php
namespace app\amservice\validate;

use core\Validate;

class TokenValidate extends Validate
{

    /**
     * 规则
     *
     * @var unknown
     */
    protected $rule = [
        'token' => 'require|token',
    ];

    /**
     * 提示
     *
     * @var unknown
     */
    protected $message = [
        'token.require' => '',
        'token.token' => '请勿重复请求',
    ];

    /**
     * 场景
     *
     * @var unknown
     */
    protected $scene = [
        
        'token' => [
            'token'
        ]

    ];
}