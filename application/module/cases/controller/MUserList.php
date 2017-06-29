<?php
namespace module\cases\controller;

use think\Request;
use think\Session;

use core\cases\model\ChatUserModel;
use core\manage\model\UserModel;
use core\manage\logic\UserLogic;
use core\cases\logic\ChatUserLogic;
use core\cases\logic\CaseTypeLogic;

class MUserList extends Base
{


       //获取性别数组
    protected function getSexList(){
         
         $logic =CaseTypeLogic::getInstance();
         $case_manager=$logic->getSelectSex();
         $this->assign('sexlist',$case_manager);
     }

     
     //验证唯一
    protected function UserOnly($where=null){
       $logic =ChatUserLogic::getInstance();
       if(is_array($where)){
       foreach ($where as $key => $value) {
          
           $result=$logic->IsOnly($value['where']);
           if(!$result){
               $this->error($value['msg']);
           }
       }
      }
 
       
     }
            //获取用户状态数组
    protected function getUserStatus(){
         
         $logic =ChatUserLogic::getInstance();
         $case_manager=$logic->getSelectStatus();
         $this->assign('userstatus',$case_manager);
     }

     

 
 
        /**
     * 添加case
     *
     * @param Request $request            
     * @return string
     */
    public function add(Request $request)
    {
        if ($request->isPost()) {
            $data = [
                'user_name' => $request->param('user_name'),
                'pwd' => $request->param('pwd'),
                'sex' => $request->param('sex'),
                'avatar' => $request->param('avatar'),
                'company' => $request->param('company'),
                 'managerid' => $request->param('managerid',0),
                'tel' => $request->param('tel'),
                'email' => $request->param('email'),
                'sort' => $request->param('sort'),
                'u_status' => $request->param('u_status'),
                'is_manager'=>1
            ];
            if(empty($request->param('managerid'))){
                $this->error('非法操作', self::JUMP_REFERER);
            }
            
          //检测用户名重复
           $where=[
                 
               [
                   'where'=>['user_name'=>$data['user_name']],
                   'msg'=>'用户名已存在'
               ],
               [
                   'where'=>['tel'=>$data['tel']],
                   'msg'=>'手机号已存在'
               ],
               [
                   'where'=>['email'=>$data['email']],
                   'msg'=>'邮箱已存在'
               ],
               [
                   'where'=>['managerid'=>$data['managerid']],
                   'msg'=>'该管理员已开通layim帐号'
               ]
         
           ];
           $this->UserOnly($where);
            // 添加
            $model = ChatUserModel::getInstance();
            //加密密码
            $data['pwd']= md5($data['pwd']);
            $status = $model->save($data);
            $this->success('新增成功', self::JUMP_REFERER);
        } else {
            $this->siteTitle = '新增用户';
           
            
           //性别
            $this->getSexList();
            
          //用户状态
          $this->getUserStatus();
            
         if($request->param('forid')){
             $this->assign('managerid',$request->param('forid'));
         }else{
             $this->error('非法操作', self::JUMP_REFERER); 
         }
          
    

            return $this->fetch();
        }
    }
    
    
       /**
     * 编辑case
     *
     * @param Request $request            
     * @return mixed
     */
    public function edit(Request $request)
    {
        $userid=$this->_id();
        $userid || $this->error('非法操作');
        if ($request->isPost()) {
            $data = [
                'user_name' => $request->param('user_name'),
                'sex' => $request->param('sex'),
                'avatar' => $request->param('avatar'),
                'company' => $request->param('company'),
                'tel' => $request->param('tel'),
                'email' => $request->param('email'),
                'sort' => $request->param('sort'),
                'u_status' => $request->param('u_status')

            ];
          if($request->param('pwd')){
              $data['pwd']=$request->param('pwd');
          }
          //检测用户名重复
           $where=[
                 
               [
                   'where'=>[
                       'user_name'=>$data['user_name'],
                       'managerid'=>['neq',$userid]
                   ],
                   'msg'=>'用户名已存在'
               ],
               [
                   'where'=>[
                       'tel'=>$data['tel'],
                       'managerid'=>['neq',$userid]
                       ],
                   'msg'=>'手机号已存在'
               ],
               [
                   'where'=>[
                       'email'=>$data['email'],
                       'managerid'=>['neq',$userid]
                   ],
                   'msg'=>'邮箱已存在'
               ]
         
           ];
           $this->UserOnly($where);
            // 修改
            $model = ChatUserModel::getInstance();
            $map = [
            'managerid' => $userid
            ];
            $status = $model->save($data,$map);
            $this->success('修改成功', self::JUMP_REFERER);
        } else {
            $this->siteTitle = '编辑用户';
            
         $model = ChatUserModel::getInstance();   
        $user_alias=$model->alias_name;
            $map=[
               $user_alias.'.managerid'=>$userid
            ];
        
        $user_list=$model->getUserList($map)->select();
       
        if($user_list){
            $this->assign('user_list', $user_list[0]);
        }else{
            $this->error(非法操作);
        }
   

                   //性别
            $this->getSexList();
            
          //用户状态
          $this->getUserStatus();
          
            return $this->fetch();
        
        }
    }
    
    
}