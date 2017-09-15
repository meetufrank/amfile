<?php
namespace module\cases\controller;

use think\Request;
use think\Session;

use core\cases\model\ChatUserModel;
use core\cases\model\UserModel;
use core\manage\logic\UserLogic;
use core\cases\logic\ChatUserLogic;
use core\cases\logic\CaseTypeLogic;
use core\cases\validate\ChatUserValidate;
use app\common\sendemail\SendUser;
use core\cases\model\CmWorkModel;
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

                 //获取可用公司列表数组
    protected function assignCompanyList(){
         
         $logic =ChatUserLogic::getInstance();
         $where=[
             'status'=>1,
             'id'=>1
         ];
         $company_list=$logic->getSelectCompany($where);
         $this->assign('company_list',$company_list);
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
                 'pwd_again'=>$request->param('pwd_again'),
                 'nickname' => $request->param('nickname'),
                'sex' => $request->param('sex'),
                'avatar' => $request->param('avatar'),
                'company' => $request->param('company'),
                 'managerid' => $request->param('managerid',0),
                'tel' => $request->param('tel'),
                'email' => $request->param('email'),
                'sort' => $request->param('sort'),
                'u_status' => $request->param('u_status'),
                'is_manager'=>1,
                'language'=>$request->param('language'),
                'area'=>$request->param('area')
            ];
            if(empty($request->param('managerid'))){
                $this->error('非法操作', self::JUMP_REFERER);
            }
            $managerid=$request->param('managerid');
              // 验证
            $this->_validate(ChatUserValidate::class, $data, 'add');
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
           //发送邮箱
            $email=new SendUser();
            $email->addSend($data,2);
            // 添加
            $model = ChatUserModel::getInstance();
            //加密密码
            $data['pwd']= md5($data['pwd']);
            unset($data['pwd_again']);
            
            //验证是否是casemanager
            $cmlist=UserLogic::getInstance()->isCm($managerid);
            if(!empty($cmlist)){
                $cmwmodel=CmWorkModel::getInstance();
                $emptyarr=[
                       'complete_count'=>0
                       ];
                $cmwmodel->save($emptyarr);
                $data['workid']=$cmwmodel->id;
            }
             
            $status = $model->save($data);
            $ks_arr=$request->param('kslist/a',[]);
            ChatUserLogic::getInstance()->joinks($model->id, $ks_arr);
            $this->success('新增成功', self::JUMP_REFERER);
        } else {
            $this->siteTitle = '新增用户';
           
            
           //性别
            $this->getSexList();
            
          //用户状态
          $this->getUserStatus();
           //公司列表
          $this->assignCompanyList();
            
         $managerid=$request->param('forid');
         if($managerid){
             $this->assign('managerid',$managerid);
         }else{
             $this->error('非法操作', self::JUMP_REFERER); 
         }
    //获取语言列表
         $this->getLangList();
  //该用户是否是casemanager
         $cmlist=UserLogic::getInstance()->isCm($managerid);
         //print_r($cmlist);exit;
         if(!empty($cmlist)){
             //获取科室列表
             $this->getKsList();
             $this->assign('isCm', 1);
         }else{
             $this->assign('isCm', 0);
         }
            return $this->fetch();
        }
    }
        //获取科室数组
     protected function getKsList(){
         
         $logic = ChatUserLogic::getInstance();
         $kslist=$logic->getSelectKs();

         $this->assign('kslist',$kslist);
     } 
      //导出excel用户表格
   public function exportUser() {
       //获取用户表别名
        $chatuser_alias=ChatUserModel::getInstance()->alias_name;
        $manager_alias=UserModel::getInstance()->alias_name;
        $map=[
            $chatuser_alias.'.delete_time' => 0,
            $manager_alias.'.user_gid'=> config('am_casemanage')
        ];
        
        ChatUserLogic::getInstance()->exportCmanager($map);
    }
     /*
   * 获取语言列表
   */
  public function getLangList() {
      $list=ChatUserLogic::getInstance()->getLanguageList();
      $this->assign('languagelist', $list);
      
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
                 'nickname' => $request->param('nickname'),
                'avatar' => $request->param('avatar'),
                'company' => $request->param('company'),
                'tel' => $request->param('tel'),
                'email' => $request->param('email'),
                'sort' => $request->param('sort'),
                'u_status' => $request->param('u_status'),
                'language'=>$request->param('language'),
                'area'=>$request->param('area')
            ];
             if(!$data['language']){
               $data['language']=ChatUserModel::getInstance()->where(['id'=>$userid])->value('language');
           }
          
                     // 修改
           if($request->param('pwd')){
              $data['pwd']=$request->param('pwd');
              $data['pwd_again']=$request->param('pwd_again');
                 // 验证
            $this->_validate(ChatUserValidate::class, $data, 'edit_password');
            //发送邮箱
            $email=new SendUser();
            $email->editSend($data);
            //加密密码
            $data['pwd']= md5($data['pwd']);
            unset($data['pwd_again']);
          }else{
              // 验证
            $this->_validate(ChatUserValidate::class, $data, 'edit_info');
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
            $cmwmodel=CmWorkModel::getInstance();
            $usealias=$model->alias_name;//用户表别名
            $map = [
                'managerid' => $userid
            ];
            //查看该casemanager的工作信息是否还存在
            $usercontent=ChatUserLogic::getInstance()->getUsers($map,1);
            $id=$usercontent['id'];
            //验证是否是casemanager
            $cmlist=UserLogic::getInstance()->isCm($userid);
            if(!empty($cmlist)){
                if(!$usercontent['workid']){
                   $emptyarr=[
                       'complete_count'=>0
                       ];
                   $cmwmodel->save($emptyarr);
                   $data['workid']=$cmwmodel->id; 
                }
                
            }
            $status = $model->save($data,$map);
            $ks_arr=$request->param('kslist/a',[]);
            ChatUserLogic::getInstance()->joinks($id, $ks_arr);
            $this->success('修改成功', self::JUMP_REFERER);
        } else {
            $this->siteTitle = '编辑用户';
            
        $model = ChatUserModel::getInstance();   
        $user_alias=$model->alias_name;
            $map=[
               $user_alias.'.delete_time'=>0,
               $user_alias.'.managerid'=>$userid
            ];
        
        $user_list=$model->getUserList($map)->select();
        if($user_list){
            $ksarr= [];
            foreach ($user_list[0]->ksarr as $vo) {
                $ksarr[] = $vo['ks_id'];
            }
            $user_list[0]['ks_list']=$ksarr;
            $this->assign('user_list', $user_list[0]);
        }else{
            $this->error(非法操作);
        }
   

                   //性别
            $this->getSexList();
            
          //用户状态
          $this->getUserStatus();
          
          //公司列表
          $this->assignCompanyList();
          //获取语言列表
         $this->getLangList();
            return $this->fetch();
        
        }
    }
    
    
}