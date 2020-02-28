<?php
namespace core\cases\logic;

use core\Logic;
use think\Db;



class CaseCardLogic extends Logic
{
    
    protected $db='cases_order_card';
    protected $orderdb='cases_order_info';
    protected $typedb='cases_case_type';


    //生成唯一服务卡号
    public function getCardId() {
        
        $yCode = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J');
        $cardsn = $yCode[intval(date('Y')) - 2011] . strtoupper(dechex(date('m'))) . date('d') . substr(time(), -5) . substr(microtime(), 2, 5) . sprintf('%02d', rand(0, 99));
        
        
        
        return $cardsn;
        
    }

    
    //获取用户端的服务卡列表
    public function getList($userid,$type=0) {
        
        $db=$this->db;
        $map=function($query) use ($userid,$type){
            $where=[
                    'c.uid'=>$userid,
                    'c.delete_time'=>0,
                    'c.status'=>1,
                    'of.state'=>1
                ];
            if(!empty($type)){
                    $map['c.s_type_id']=$type;
                }
            $query->where($where);
            };
         $map2=function($query) use ($userid,$type){
            $where=[
                    'c.send_id'=>$userid,
                    'c.delete_time'=>0,
                    'c.status'=>1,
                    'of.state'=>1
                ];
            if(!empty($type)){
                    $map['c.s_type_id']=$type;
                }
            $query->where($where);
            
            };
    
       $list=db($db)->alias('c')
                ->join($this->orderdb.' of','of.id=c.orderid','left')
                ->join($this->typedb.' tb','tb.id=c.s_type_id','left')
                ->where($map)
                ->whereOr($map2)
                ->field('c.*,tb.typename')
                ->order('c.update_time desc')
                ->select();
       
       $service_list=$list;
        
        foreach (@$service_list as $key => $value) {
            if($value['uid']==$userid){  //自买
                $service_list[$key]['c_isme']=1;
            }else{
                $service_list[$key]['c_isme']=0;
            }
          //自买/礼品   未激活/已激活   未赠送/已赠送  未领取/已领取   过期/次数用完/可以用
            $service_list[$key]['is_jh']=0;
            $service_list[$key]['can_use']='';
            
            if(!empty($value['jh_time'])){  //已激活
                 $service_list[$key]['is_jh']=1;
                 //是否过期或者次数用完
                 if(($value['uid']==$userid && $value['send_id']==0) || $value['send_id']==$userid){
                     
                     if($value['times']<=0){  //次数用完
                         $service_list[$key]['can_use']='no_cishu';
                    }elseif($value['stop_time']<time()){
                        $service_list[$key]['can_use']='no_date';
                    }else{
                        $service_list[$key]['can_use']='yes';
                    }
                        
                 }else{
                     $service_list[$key]['can_use']='yes_zz'; 
                 }
                 

               
            }
        }
     
        return $service_list;
    }
    
}