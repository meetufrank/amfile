<?php
namespace app\medicalapi\controller;


use think\Request;
use core\cases\model\ChatUserModel;
use core\cases\model\CompanyModel;
use core\cases\logic\ChatUserLogic;
use think\Validate;
use core\cases\model\CaseModel;
use core\manage\model\FileModel;
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
    
    
    //提交case
    public function submitCase() {
       $body=$this->body;
       //查看是否有上传文件
       $optionid=$this->getFileId();
       
       $user_alias= ChatUserModel::getInstance()->alias_name;//chatuser表别名
       $map=[
           $user_alias.'.u_status'=>1,
           $user_alias.'.user_name'=>$body['username'],
           $user_alias.'.pwd'=>md5($body['pwd'])
       ];
       $id= ChatUserLogic::getInstance()->getUserId($map);  //该用户id
      
       $data = [
                'username' => isset($body['c_username'])?preg_replace('/[(\xc2\xa0)|\s]+/','',$body['c_username']):'',
                'birthday' => isset($body['birthday'])?$body['birthday']:'',
                'sex' => isset($body['sex'])?$body['sex']:1,
                'isme' => isset($body['isme'])?$body['isme']:1,
                'relationship' =>isset($body['relationship'])?preg_replace('/[(\xc2\xa0)|\s]+/','',$body['relationship']):'',
                'applicant_name' =>isset($body['applicant_name'])?preg_replace('/[(\xc2\xa0)|\s]+/','',$body['applicant_name']):'',
                'country'=>isset($body['country'])?$body['country']:null,
                'province' => isset($body['province'])?$body['province']:null,
                'city' => isset($body['city'])?$body['city']:null,
                'district' => isset($body['district'])?$body['district']:null,
                'address' => isset($body['address'])?$body['address']:'',
                'zip_code' => isset($body['zip_code'])?$body['zip_code']:'',
                'email'=>isset($body['email'])?preg_replace('/[(\xc2\xa0)|\s]+/','',$body['email']):'',
                'preferred_phone' => isset($body['preferred_phone'])?preg_replace('/[(\xc2\xa0)|\s]+/','',$body['preferred_phone']):'',
                'standby_phone' => isset($body['standby_phone'])?preg_replace('/[(\xc2\xa0)|\s]+/','',$body['standby_phone']):'',
                'preferred_time' => isset($body['preferred_time'])?preg_replace('/[(\xc2\xa0)|\s]+/','',$body['preferred_time']):'',
                'illness' => isset($body['illness'])?$body['illness']:'',
                'treatment_doctor' => isset($body['treatment_doctor'])?$body['treatment_doctor']:'',
                'treatment_hospital' => isset($body['treatment_hospital'])?$body['treatment_hospital']:'',
                'specialty' => isset($body['specialty'])?$body['specialty']:'',
                'case_type' => isset($body['isme'])?$body['case_type']:1,
                'userid' => $id,
                'options'=>$optionid
            ];
       $rule=[
        'username' =>['require','regex'=>'^[\x80-\xffa-zA-Z]+$','max'=>20],
        'birthday' => 'require|date',
        'relationship'=>'requireIf:isme,0',
        'applicant_name' => ['require','regex'=>'^[\x80-\xffa-zA-Z]+$','max'=>20],
        'country'=>'require',
        'province'=>'requireIf:country,1',
        'city'=>'requireIf:country,1',
        'district'=>'requireIf:country,1',
        'address' => 'require|max:200',
        'preferred_phone'=>['require','regex'=>'^((0\d{2,3}-\d{7,8})|(1[3584]\d{9}))$'],
        'email'=>['require','regex'=>'^[a-z0-9]+([._\\-]*[a-z0-9])*@([a-z0-9]+[-a-z0-9]*[a-z0-9]+.){1,63}[a-z0-9]+$'],
        'illness'=>'require',
                ];
       $msg = [
        'username.require' => json_encode(['code' => 10001,'msg' => 'invalid code']),
        'username.regex'=>json_encode(['code' => 10001,'msg' => 'invalid code']),
        'username.max'=>json_encode(['code' => 10001,'msg' => 'invalid code']),
        'birthday.require' => json_encode(['code' => 10002,'msg' => 'invalid code']),
        'birthday.date' => json_encode(['code' => 10002,'msg' => 'invalid code']),
        'relationship.requireIf'=>json_encode(['code' => 10003,'msg' => 'invalid code']),
        'applicant_name.require' => json_encode(['code' => 10004,'msg' => 'invalid code']),
        'applicant_name.regex' => json_encode(['code' => 10004,'msg' => 'invalid code']),
        'applicant_name.max' => json_encode(['code' => 10004,'msg' => 'invalid code']),
        'preferred_phone.require'=>json_encode(['code' => 10007,'msg' => 'invalid code']),
        'preferred_phone.regex'=>json_encode(['code' => 10007,'msg' => 'invalid code']),
        'address.require' => json_encode(['code' => 10006,'msg' => 'invalid code']),
        'address.max' => json_encode(['code' => 10006,'msg' => 'invalid code']),
        'country.require'=>json_encode(['code' => 10005,'msg' => 'invalid code']),
        'province.requireIf' => json_encode(['code' => 10005,'msg' => 'invalid code']),
        'city.requireIf' => json_encode(['code' => 10005,'msg' => 'invalid code']),
        'district.requireIf' => json_encode(['code' => 10005,'msg' => 'invalid code']),
        'email.require'=>json_encode(['code' => 10008,'msg' => 'invalid code']),
        'email.regex'=>json_encode(['code' => 10008,'msg' => 'invalid code']),
        'illness.require' =>json_encode(['code' => 10009,'msg' => 'invalid code'])
           
                ];
       //验证
       $validate = new Validate($rule,$msg);
      if (!$validate->check($data)) {
            echo $validate->getError();
            exit;
            }else{
               CaseModel::getInstance()->save($data);
               echo json_encode(['code' => 0,'msg' => 'success']);
               exit;
            }
    }
    //测试是否有上传文件并获取文件id
    private function getFileId(){
       // 文件是否存在
        $file = isset($_FILES['upload_file']) ? $_FILES['upload_file'] : null;
        if (!empty($file)) {
          
        
        $result = $this->uploadCaseFile($file);
        if(!empty($result)){
        $id=FileModel::getInstance()->where(['file_hash'=>$result['hash'],'file_ext'=>$result['ext']])->value('id');
        return $id;
        }
        }else{
            return 0;
        }
         
    }

    public function casedemo(){
      return  $this->fetch();
    }
}