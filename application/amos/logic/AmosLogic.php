<?php
namespace app\amos\logic;

use core\Logic;
use think\Exception;
class AmosLogic extends Logic
{

    private $username='SinoUnited@api';
    private $key='8qHvvxE9tdp6FJdeP4c4eZPN5B8qSJSe';
    private $url='https://pre.api.amos-am.com/2.0/';

    private $token;
    private $clientid;
    private $project_id=61;
    public function __construct() {
        $this->token= cookie('amos_token');
        $this->clientid=cookie('amos_client_id');
        
    }
    
    //登录action
    public function login($data) {
        $action='clients/login';
        $result=$this->load($data, $action,[],'post');
       return $result;
        
    }
    
    //获取咨询列表
    public function zxlist($data) {
        $data['project_id']= $this->project_id;
        $action='telehealth/videocalls';
        $arr_header[]="Amos-Client-Token:".$this->token;
        $arr_header[]="Amos-Client-Id:".$this->clientid;
        $result=$this->load($data, $action,$arr_header,'get');
       return $result;
    }
    public function videoinfo($data,$id) {
        $data['project_id']= $this->project_id;
        $action='telehealth/videocalls/'.$id;
        $arr_header[]="Amos-Client-Token:".$this->token;
        $arr_header[]="Amos-Client-Id:".$this->clientid;
        $result=$this->load($data, $action,$arr_header,'get');
        return $result;
    }
    public function videocancel($data,$id) {
        $data['project_id']= $this->project_id;
        $action='telehealth/videocalls/'.$id.'}/cancel';
        $arr_header[]="Amos-Client-Token:".$this->token;
        $arr_header[]="Amos-Client-Id:".$this->clientid;
        $result=$this->load($data, $action,$arr_header,'post');
        return $result;
    }
    //获取咨询列表
    public function omclist($data) {
        $data['project_id']= $this->project_id;
        $action='omc/consultations';
        $arr_header[]="Amos-Client-Token:".$this->token;
        $arr_header[]="Amos-Client-Id:".$this->clientid;
        $result=$this->load($data, $action,$arr_header,'get');
        return $result;
    }
    public function omcinfo($data,$id) {
        $data['project_id']= $this->project_id;
        $action='omc/consultations/'.$id;
        $arr_header[]="Amos-Client-Token:".$this->token;
        $arr_header[]="Amos-Client-Id:".$this->clientid;
        $result=$this->load($data, $action,$arr_header,'get');
        return $result;
    }
    public function omccancel($data,$id) {
        $data['project_id']= $this->project_id;
        $action='omc/consultations/'.$id.'}/cancel';
        $arr_header[]="Amos-Client-Token:".$this->token;
        $arr_header[]="Amos-Client-Id:".$this->clientid;
        $result=$this->load($data, $action,$arr_header,'post');
        return $result;
    }
    public function docinfo($data,$id) {
        $data['project_id']= $this->project_id;
        $action='doctors/'.$id;
        $arr_header[]="Amos-Client-Token:".$this->token;
        $arr_header[]="Amos-Client-Id:".$this->clientid;
        $result=$this->load($data, $action,$arr_header,'get');
        return $result;
    }
    
    //家属列表
    public function famlist($data) {
        
        $data['project_id']= $this->project_id;
        $action='dependents';
        $arr_header[]="Amos-Client-Token:".$this->token;
        $arr_header[]="Amos-Client-Id:".$this->clientid;
        $result=$this->load($data, $action,$arr_header,'get');
        return $result;
    }
    
    //创建家属
    public function famadd($data) {
        
        $data['project_id']= $this->project_id;
        $action='dependents/create';
        $arr_header[]="Amos-Client-Token:".$this->token;
        $arr_header[]="Amos-Client-Id:".$this->clientid;
        $result=$this->load($data, $action,$arr_header,'post');
        return $result;
    }
    //家属详情
    public function faminfo($data,$id) {
        
        $data['project_id']= $this->project_id;
        $action='dependents/'.$id;
        $arr_header[]="Amos-Client-Token:".$this->token;
        $arr_header[]="Amos-Client-Id:".$this->clientid;
        $result=$this->load($data, $action,$arr_header,'get');
        return $result;
    }
    //修改家属
    public function famedit($data,$id) {
        
        $data['project_id']= $this->project_id;
        $action='dependents/'.$id;
        $arr_header[]="Amos-Client-Token:".$this->token;
        $arr_header[]="Amos-Client-Id:".$this->clientid;
        $result=$this->load($data, $action,$arr_header,'post');
        return $result;
    }
    //添加电话回呼
    public function consuladd($data) {
        
        $data['project_id']= $this->project_id;
        $action='omc/consultations/create';
        $arr_header[]="Amos-Client-Token:".$this->token;
        $arr_header[]="Amos-Client-Id:".$this->clientid;
        $result=$this->load($data, $action,$arr_header,'post');
        return $result;
    }
    //添加视频咨询
    public function videoadd($data) {
        
        $data['project_id']= $this->project_id;
        $action='telehealth/videocalls/create';
        $arr_header[]="Amos-Client-Token:".$this->token;
        $arr_header[]="Amos-Client-Id:".$this->clientid;
        $result=$this->load($data, $action,$arr_header,'post');
        return $result;
    }
    //获取视频预约时间表
    public function getvideotime($data) {
        
        $data['project_id']= $this->project_id;
        $action='telehealth/videocalls/availability';
        $arr_header[]="Amos-Client-Token:".$this->token;
        $arr_header[]="Amos-Client-Id:".$this->clientid;
        $result=$this->load($data, $action,$arr_header,'get');
        return $result;
    }

    //公共请求
    private function load($postdata,$action,$arr_header=array(),$method='post') {
              $authstr= base64_encode($this->username.":". $this->key);
              $arr_header[] = "Authorization: Basic ".$authstr; //添加头，在name和pass处填写对应账号密码
              $arr_header[] = "Content-Type: application/json ";
              
              $postdata= !empty($postdata)?$postdata:[];
              if($method=='get'){
                  $str=http_build_query($postdata);
                  $result = httpRequest($this->url.$action.'?'.$str, $method, null,$arr_header);
              }else{
                  
                  $postdata=json_encode($postdata);

                  $result = httpRequest($this->url.$action, $method, $postdata,$arr_header);
                  
              }
              
     
             
              if($result){
                  $result=json_decode($result,true);

                  return $result; 
                  
              }else{

                  throw new Exception('link error');
              }
      
    }
}