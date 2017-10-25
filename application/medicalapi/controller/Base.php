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
use cms\upload\processes\CropProcess;
use cms\upload\processes\OrientationProcess;
use app\common\App;
use app\common\factories\FileFactory;
use cms\upload\validates\CaseApiVaildate;
Header("Access-Control-Allow-Origin: * ");
Header("Access-Control-Allow-Methods: POST");
Header('Access-Control-Allow-Headers:x-requested-with,content-type'); 
class Base extends Controller
{
    protected $body=[];
    function _initialize() {
    //function a(){a
        header("Content-type: text/html; charset=utf-8");
        $request= Request::instance();
       
//      $time=time();
//      echo $time."<br/>";
//      $apiid=md5('SGJK'.$time);
//      $apipwd=md5($time.'SGJK');
//      echo $apiid.'<br/>';
//      echo $apipwd.'<br/>';exit;
      //print_r(json_encode($request->param())); 
      
        
//    echo urlencode(base64_encode('{"username":"wangqiang@meetuuu.com","pwd":"123","case_type":"1","c_username":"\u738b\u5f3a","birthday":"1993-02-01","sex":"1","isme":"1","relationship":"","applicant_name":"\u738b\u5f3a","country":"1","province":"110000","city":"110100","district":"110101","address":"\u6d4b\u8bd5\u5730\u5740","zip_code":"","email":"972270516@qq.com","preferred_phone":"18721667531","standby_phone":"","preferred_time":"9:00~12:00","illness":"\u75c5\u4e86","treatment_doctor":"","treatment_hospital":"","specialty":""}'));
//      exit;   
     
     
//     $body='eyJ1c2VybmFtZSI6IndhbmdxaWFuZ0BtZWV0dXV1LmNvbSIsInB3ZCI6IjEyMyIsImNhc2VfdHlwZSI6IjEiLCJjX3VzZXJuYW1lIjoiXHU3MzhiXHU1ZjNhIiwiYmlydGhkYXkiOiIxOTkzLTAyLTAxIiwic2V4IjoiMSIsImlzbWUiOiIxIiwicmVsYXRpb25zaGlwIjoiIiwiYXBwbGljYW50X25hbWUiOiJcdTczOGJcdTVmM2EiLCJjb3VudHJ5IjoiMSIsInByb3ZpbmNlIjoiMTEwMDAwIiwiY2l0eSI6IjExMDEwMCIsImRpc3RyaWN0IjoiMTEwMTAxIiwiYWRkcmVzcyI6Ilx1NmQ0Ylx1OGJkNVx1NTczMFx1NTc0MCIsInppcF9jb2RlIjoiIiwiZW1haWwiOiI5NzIyNzA1MTZAcXEuY29tIiwicHJlZmVycmVkX3Bob25lIjoiMTg3MjE2Njc1MzEiLCJzdGFuZGJ5X3Bob25lIjoiIiwicHJlZmVycmVkX3RpbWUiOiI5OjAwfjEyOjAwIiwiaWxsbmVzcyI6Ilx1NzVjNVx1NGU4NiIsInRyZWF0bWVudF9kb2N0b3IiOiIiLCJ0cmVhdG1lbnRfaG9zcGl0YWwiOiIiLCJzcGVjaWFsdHkiOiIifQ%3D%3D';
//        $time=1504165281;
//     $apiid='fae1642cca025e189c745da5d8b06a57';
//     $apipwd='39dfb57e2b525e81fe68445bfd25cf1a';
//  echo md5(base64_encode($body.$time.$apiid.$apipwd));
// exit;   
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
     
   /**
     * 上传case附件
     *
     * @param array $file            
     * @param array $option            
     *
     * @return array
     */
    protected function uploadCaseFile($file)
    {
        // 上传文件
        $type = is_array($file) ? FileFactory::TYPE_UPLOAD : FileFactory::TYPE_STREAM;
        $upfile = FileFactory::make($type);
        $upfile->load($file);
        
        // 上传对象
        $upload = App::getSingleton()->upload;
        
        // 文件后缀
        $extensions = ['zip','pdf','doc','jpeg','jpg','png'];
        $maxsize='10M';
        if (! empty($extensions)) {
            $option = [
                'extensions' => $extensions,
                'max_size'=>$maxsize
            ];
            $upload->addValidate(new CaseApiVaildate($option));
        }
        
        // 图片重力
        $upload->addProcesser(new OrientationProcess());
        
        // 图片大小
        if (isset($option['width']) || isset($option['height'])) {
            $upload->addProcesser(new CropProcess($option));
        }
        
        // 上传文件
        return $upload->upload($upfile);
    }
     
 
}