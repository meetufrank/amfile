<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------
return [
    '__pattern__' => [
        'name' => '\w+'
    ],
    
    // 主页
    
    'mobilepage/:id' => 'index/mobile/index',
    'mobilepage' => 'index/mobile/index',
    
    
    // 博客
    'home' => 'blog/index/index',
    'cate/:name' => 'blog/index/cate',
    'article/:key' => 'blog/index/show',
    
   
    //AM聊天
    'service'=>'laychatphone/Login/index',
    
    //advance
    'am'=>'advance/Index/index',
    'services/:id'=>'advance/Index/service_details',
    'mobile_form/:id'=>'advance/Index/mobile_form',
    'loginApi'=>'medicalapi/User/Login',//获取个人信息接口
    'tokenApi'=>'medicalapi/GetToken/token',//获取登录token
    'caseApi'=>'medicalapi/User/submitCase', //提交case接口
    'caseList'=>'medicalapi/User/getCaseList', //提交case接口
    'downloadArea'=>'advance/Download/downloadAreaList', //下载地址表
    'yuyueApi'=>'medicalapi/Appointment/submitYuyue', //提交预约信息接口
    
    
    //国内保险公司
    'allianzchina'=>'allianzchina/Index/index',
    'edm/:date/:lang/:page'=>'edm/Index/index',
    
    
    
    //一个页面邮件 路由
    
    
    'health'=>'ampage/index/yjpage',
    
  
    //am购买服务卡
    'serviceIndex'=>'amservice/index/index',
    'selectservice'=>'amservice/index/selectservice',
    'payorder'=>'amservice/index/payorder',
    'userService'=>'amservice/index/userservice',
    'oprateService'=>'amservice/index/oprateservice',
    'servicecase'=>'amservice/index/addcase',
    
    
    //amos服务
    'amosindex'=>'amos/index/index',
    'amosdetail'=>'amos/index/detail',
    'amosfamily'=>'amos/index/familylist',
    'amosfamilyadd'=>'amos/index/familyadd',
    'amosfamilyedit'=>'amos/index/familyedit',
    'amosfamilydel'=>'amos/index/familydel',
    'amos_phonecall'=>'amos/index/phonecall',
    'amos_videocall'=>'amos/index/videocall',
    'amos_meeting'=>'amos/index/validmeeting',
    
    //am聊天demo服务
    'ltindex'=>'ltpage/login/index',
    'lthtml'=>'ltpage/index/index',
    
    //http://www.advance-medical.com.cn/generali_china
    
    //generali_china 服务
    'generali_china'=>'generalichina/index/index',
    
    
     // 后台
    'module/:_module_/:_controller_/:_action_' => 'manage/loader/run',
    'casecommander'=>'manage/start/login',
];
