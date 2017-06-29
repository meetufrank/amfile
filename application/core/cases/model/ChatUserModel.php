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
       $user_list= $this->withCates()->field($user_alias.'.*')->where($map)->order($user_alias.'.sort desc');
       return $user_list;
   }
   /*
    * 加入各级关联
    */
   public function withCates() {
       $query= $this->alias($this->alias_name); //本表别名
       
       
       
       return $query;
       
   }
  
}