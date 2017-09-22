<?php
namespace module\cases\controller;

use think\Controller;
use think\Db;
class Yuyue extends Base{
    
    public function index(){
        
        //查询出用户提交预约信息
        $info = Db::table('nd_appointment_info')
        ->alias('a')
        ->join('nd_appointment_time_quantum t','a.time_qid = t.id')
        ->field("a.id,a.phone,a.email,a.advisory_details,a.submitdate,a.user_name,a.appointment_state,t.time_quantum")->select();
       
        $this -> assign('info',$info); 
        
        return $this->fetch();
    }
    
    
    //修改
    public function edit(){
        //获取修改id 
        $id = $this->_id();
      
        //根据id查询出用户预约信息
        $yuyue_list = Db::table('nd_appointment_info')
        ->where('id','=',$id)
        ->select(); 
        
        //查找出
        $time_quantum = Db::table('nd_appointment_time_quantum')
        ->where('id','=',$yuyue_list[0]['time_qid'])
        ->select(); 
        //print_r($time_quantum);exit;
        
        //预约时间段
        $this -> assign('time_quantum',$time_quantum);
        
        //预约详情信息
        $this->assign('yuyue_list', $yuyue_list);
 
        return $this->fetch();
    }
    
}
?>