<?php
return [
    // 根命名空间
    'root_namespace' => [
        'core' => APP_PATH . 'core',
        'module' => APP_PATH . 'module'
    ],
    
    // 禁止访问模块
    'deny_module_list' => [
        'common',
        'core',
        'module',
        'extra'
    ],
    
    'default_module' => 'advance',//默认模块	
    'default_controller' => 'Index', //默认控制器
    'default_action'         => 'index',            // 默认操作名
    'session' => [                //session设置  不同的模块设置不同的session
                    'prefix' => 'module',
                    'type' => '',
                    'auto_start' => true,
                    ],
    'app_debug'=>false,
    'log' => [
        // 日志记录方式，内置 file socket 支持扩展
        'type'  => 'test',
         // 日志保存目录
        'path'  => LOG_PATH,
        // 日志记录级别
        'level' => [],
    ],
    'cache'                  => [
        // 驱动方式
        'type'   => 'File',
        // 缓存保存目录
        'path'   => CACHE_PATH,
     
        // 缓存有效期 0表示永久缓存
        'expire' => 0,
    ],
    'wechat_appid'=>'wxa663c0d75761bec1',
    'wechat_appsecret'=>'b56ed90b63222ad6ac0e25bde0c80777',
    'jh_times'=>2592000,  //30天
  
];