<?php
namespace core\cases\logic;

use core\Logic;
use core\cases\model\ChatUserModel;
use core\cases\model\CompanyModel;
class ChatUserLogic extends Logic
{

 /*
  * 获取用户下拉列表
  */
    
        public function getSelectUser($where=[],$sort_name='sort',$sort='asc')
    {

           $data= ChatUserModel::getInstance()->where($where)->order($sort_name, $sort)->select();
           $list=[

           ];
           foreach ($data as $key => $value) {
               $list[]=[
                 'name'=>$value['user_name'],
                 'value'=>$value['id']
               ];
           }
           
           
           return $list;
    }
    
        /**
     * 获取状态下拉
     *
     * @return array
     */
    public function getSelectStatus()
    {
        return [
            [
                'name' => '启用',
                'value' => 1
            ],
            [
                'name' => '禁用',
                'value' => 0
            ]
        ];
    }
     /*
  * 获取用户下拉列表
  */
    
        public function getSelectCompany($where=[],$sort_name='sort',$sort='desc')
    {

           $data= CompanyModel::getInstance()->where($where)->order($sort_name, $sort)->select();
           $list=[

           ];
           foreach ($data as $key => $value) {
               $list[]=[
                 'name'=>$value['name'],
                 'value'=>$value['id']
               ];
           }
           
           
           return $list;
    }
          /**
     * 查询某条件下用户数据是否重复
     *
     * @return array
     */
    
    public function IsOnly($where=null) {
       $where['delete_time']=0;   
      
       $count=ChatUserModel::getInstance()->where($where)->count(); 
       $result=true;
       $count && $result=false;
       
      return $result;
    }
}