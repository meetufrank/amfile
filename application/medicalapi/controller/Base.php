<?php
namespace app\medicalapi\controller;

use think\Config;
use think\Request;
use cms\Response;
use cms\Controller;
use core\cases\model\CaseModel;
use core\cases\model\CompanyModel;
use core\cases\model\ChatUserModel;
use core\cases\logic\ChatUserLogic;
use core\cases\logic\CompanyLogic;
Header("Access-Control-Allow-Origin: * ");
Header("Access-Control-Allow-Methods: POST");
Header('Access-Control-Allow-Headers:x-requested-with,content-type'); 
class Base extends Controller
{
    protected $body=[];
    function _initialize() {
        header("Content-type: text/html; charset=utf-8");
        $request= Request::instance();
        if($request->isPost()){
        
        
      

       
        $apiid=$request->param('apiid');
        if(empty($apiid)){
           echo json_encode(['code' => 40001,'msg' => 'invalid code']);
           exit;
        }
        $apipwd=$request->param('apipwd');
        $time=$request->param('timestamp');
        $sign=$request->param('sign');
        $body=$request->param('body');
        //验证签名密钥
        $v_sign=md5(base64_encode($body.$time.$apiid.$apipwd));
        
        if($v_sign!==$sign){
             echo json_encode(['code' => 40004,'msg' => 'invalid code']);
             exit;
        }
        //解密body
        $this->jmbody($body);
        $body=$this->body; //赋值给类的属性
        $username=$body['username'];
        $pwd=$body['pwd'];
        //验证用户名和密码
        if(empty($username)){
           echo json_encode(['code' => 50001,'msg' => 'invalid code']);
           exit;
        }
        $usermap=[
            'user_name'=>$username,
            'pwd'=> md5($pwd)
        ];
        $user_content=$this->ishave($usermap);
        if(empty($user_content)){
            unset($usermap['pwd']);
            $user_content=$this->ishave($usermap);
            if(empty($user_content)){
               echo json_encode(['code' => 50001,'msg' => 'invalid code']);
               exit;
            }else{
               echo json_encode(['code' => 50002,'msg' => 'invalid code']);
               exit; 
            }
            
        }
        
        $companyid=$user_content['company'];
        $companymap=[
            'id'=>$companyid,
            'apiid'=>$apiid,
            'apipwd'=>$apipwd
        ];
        $company_content=CompanyLogic::getInstance()->getCompanyList($companymap,1);
        if(empty($company_content)){
            unset($companymap['apipwd']);
            $company_content=CompanyLogic::getInstance()->getCompanyList($companymap,1);
            if(empty($company_content)){
                echo json_encode(['code' => 40001,'msg' => 'invalid code']);
                exit;
            }else{
               echo json_encode(['code' => 40002,'msg' => 'invalid code']);
               exit;
            }
        }
        }else{
           echo json_encode(['code' => -1,'msg' => 'invalid code']);
           exit;
        }
     }
     protected function ishave($where=null) {
         $where['u_status']=1;
         return ChatUserLogic::getInstance()->getUserlist($where,1);
     }
     
   
     //解密body
    protected function jmbody($body=null) {
         //urldecode解密
         $body=urldecode($body);
         //base64解密
         $body= base64_decode($body);
         if(!is_null(json_decode($body,true))){
             $this->body=json_decode($body,true); 
         }
        
        
     }
     
 
     
 
}