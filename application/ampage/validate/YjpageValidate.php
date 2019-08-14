<?php
namespace app\ampage\validate;

use core\Validate;

class  YjpageValidate extends Validate
{

    /**
     * 规则
     *
     * @var unknown
     */
    protected $rule = [

        'name' =>['require','max'=>50],


        'number'=>['requireIf:email,'.''],
        'email'=>['requireIf:preferred_phone,'.'','regex'=>'^[a-z0-9]+([._\\-]*[a-z0-9])*@([a-z0-9]+[-a-z0-9]*[a-z0-9]+.){1,63}[a-z0-9]+$'],
        'question'=>'require'

        
    ];

    /**
     * 提示
     *
     * @var unknown
     */
    protected $message = [
        'name.require' => '姓名必须填写',
        'name.regex'=>'姓名只能包含中文或英文',
        'name.max'=>'姓名长度最多不能超过50',

        'number.requireIf'=>'电话必须填写',
        'number.regex'=>'电话格式不正确',
        'email.requireIf'=>'邮箱需要填写',
        'email.regex'=>'邮箱格式不正确',
        'question.require' => '问题描述必须填写',
      
        
    ];

    /**
     * 场景
     *
     * @var unknown
     */
    protected $scene = [
        'send' => [
            'name',
            'number',
            'email',
            'question'
         
        ]
    ];
}