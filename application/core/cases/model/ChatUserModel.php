<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace core\cases\model;

use core\Model;

class ChatUserModel extends Model
{
    /**
     * 去前缀表名
     *
     * @var unknown
     */
    protected $name = 'cases_chatuser';

    /**
     * 自动写入时间戳
     *
     * @var unknown
     */
    protected $autoWriteTimestamp = true;

/*
 * 定义别名变量
 */
   public $alias_name='a_chatuser';
    

   public function getUserlist($map=null) {
       $user_alias= $this->alias_name;//chatuser表别名
       $company_alias= CompanyModel::getInstance()->alias_name;  //公司别名
       $user_list= $this->withCates()->field($user_alias.'.*,'.$company_alias.'.name as companyname')->where($map)->order($user_alias.'.sort desc');
       return $user_list;
   }
   //获取casemanager表列表
    public function getCmlist($map=null) {
       $user_alias= $this->alias_name;//chatuser表别名
       $company_alias= CompanyModel::getInstance()->alias_name;  //公司别名
       $manager_list= UserModel::getInstance()->alias_name;
       $user_list= $this->withCates(1)->field($user_alias.'.*,'.$company_alias.'.name as companyname')->where($map)->order($user_alias.'.sort desc');
       return $user_list;
   }
   /*
    * 加入各级关联
    */
   public function withCates($type=0) {
       $query= $this->alias($this->alias_name); //本表别名
       $query= $this->joinCompany($query);
       if($type>0){
        $query= $this->joinUser($query);
       }
       
       return $query;
       
   }
   
   
   public function joinCompany($query) {
       $company= CompanyModel::getInstance();
       return $query->join($company->getTableShortName().' '.$company->alias_name,$this->alias_name.'.company = '.$company->alias_name.'.id');
   }
   //加入管理员
    public function joinUser($query) {
       $user= UserModel::getInstance();
       return $query->join($user->getTableShortName().' '.$user->alias_name,$this->alias_name.'.managerid = '.$user->alias_name.'.id');
   }
}