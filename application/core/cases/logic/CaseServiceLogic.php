<?php
namespace core\cases\logic;

use core\Logic;
use think\Db;



class CaseServiceLogic extends Logic
{
    
    protected $db='cases_service_card';
    protected $typedb='cases_case_type';


    //根据服务类型获取服务价格列表
    
    public function getListByType($typeid,$where=[],$sort=' sort desc') {
        $list=[];
        if($typeid){
            
            $where['s_type']=$typeid;
            $list=db($this->db)->where($where)->order($sort)->select();
            
            
        }
       
        
        return $list;
    }
    
    
    //根据id获取服务详情
    public function getInfoById($id) {
        $id=intval($id);
        $info=[];
        if($id){
            $info=db($this->db)->alias('sc')
                    ->join($this->typedb.' ts','ts.id=sc.s_type','left')
                    ->where(['sc.id'=>$id])
                    ->field('sc.*,ts.typename')
                    ->find();
        }
        
        
        return $info;
    }
    
}