<?php
namespace app\medicalapi\controller;


use think\Request;
use core\cases\model\ChatUserModel;
use core\cases\model\CompanyModel;
use core\cases\logic\ChatUserLogic;
class User extends Base
{
    //获取用户信息
    public function login() {
       $body=$this->body;
       $user_alias= ChatUserModel::getInstance()->alias_name;//chatuser表别名
       $map=[
           $user_alias.'.u_status'=>1,
           $user_alias.'.user_name'=>$body['username'],
           $user_alias.'.pwd'=>md5($body['pwd'])
       ];
       $data=ChatUserLogic::getInstance()->getUsers($map,1);//获取用户信息
       $putdata=[
           'nickname'=>$data['nickname'],
           'sex'=>$data['sex'],
           'user_name'=>$data['user_name'],
           'avatar'=>$data['avatar'],
           'area'=>$data['area'],
           'company'=>$data['companyname'],
           'tel'=>$data['tel'],
           'email'=>$data['email']
       ];
       if(stripos($putdata['avatar'], 'http://')!=0&&stripos($putdata['avatar'], 'https://')!=0){
           $putdata['avatar']='http://'.$_SERVER['SERVER_NAME'].$putdata['avatar'];
       }
       
      //整合数组
       $jsondata=[
           'code'=>0,
           'data'=>$putdata
       ];
       echo json_encode($jsondata,JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES);
       exit;
    }
}