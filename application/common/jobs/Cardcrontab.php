<?php
namespace app\common\jobs;
use email\Cs;
use think\queue\Job;
use think\Db;
/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
class Cardcrontab
{
    /**
     * 循环自动执行服务卡自动激活任务
     * @param array $data  内容
     * @return 
     */
    public function jhCard(Job $job, $data=[]) 
    {
        
        
        
        try {
             $time=time();
            
            $wheremap=[
                'jh_stop_time'=>[
                   [ 'neq',0],
                   ['elt',$time]
                    ],
                'jh_time'=>0
                
            ];
            $list=db('cases_order_card')->where($wheremap)->select();
            
            if(!empty($list)){
                foreach (@$list as $key => $value) {
                    $stoptime= strtotime(' +'.$value['duration'].' '.$value['unit'],time());
                    
                    $update=[
                        'jh_time'=>$time,
                        'stop_time'=>$stoptime,
                        'update_time'=>$time,
                        'jh_stop_time'=>0
                    ];
                    if($value['send_id']!=0&&$value['is_used']==0){
                        $update['is_used']=1;
                        
                    }
                    db('cases_order_card')->where($wheremap)->update($update);
                }
            }
            
         $job->release(14400);  //休眠4个小时检查一次 
        
         } catch (\Exception $e) {
             $sendemail = new Cs();
             $result    = $sendemail->activeEmail('ql.zhang@meetuuu.com','服务卡进程意外停止提醒','你的AM的项目服务卡自动激活进程意外停止，请立即查看'); 
             $job->delete();
         }
    }
  
}
