<?php
namespace app\common\jobs;
use email\Cs;
use think\queue\Job;
/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
class QueueClient
{
    /**
     * 邮件提醒
     * @param array $data  内容
     * @return 
     */
    public function sendMAIL(Job $job, $data) 
    {
        
        $isJobDone = $this->send($data);       
        if ($isJobDone) {
            //成功删除任务
            $job->delete();
        } else {
            //任务轮询4次后删除
            if ($job->attempts() > 3) {              
                // 第1种处理方式：重新发布任务,该任务延迟10秒后再执行
                //$job->release(10); 
                // 第2种处理方式：原任务的基础上1分钟执行一次并增加尝试次数
                //$job->failed();   
                // 第3种处理方式：删除任务
                $job->delete();  
            }
        }
    }
    /**
     * 根据消息中的数据进行实际的业务处理
     * @param array|mixed    $data     发布任务时自定义的数据
     * @return boolean                 任务执行的结果
     */
    private function send($data) 
    {
         $fileurl='';//附件路径
         try {
             if(isset($data['more'])){
                 
                         //判断是否是带有附件的邮件发送
                         $field=$data['more']['field'];
                        if($field['is_email_file']>0){
                            if(!empty($field['options'])){
                                $url=$field['options'];
                                $filename=$data['more']['case_code'].strrchr($url,".");  //case编号作为文件名称
                                //echo $filename;
                                $get_file=@file_get_contents($url);
                                //创建保存目录
                                $save_dir="public/uploads/email/";
                                if(!file_exists($save_dir) && !mkdir($save_dir,0777,true)){
                                    

                                }else{
                                    $fileurl=$save_dir.$filename;
                                   if($get_file){
                                    $fp=@fopen($fileurl,'w');
                                    @fwrite($fp,$get_file);
                                    @fclose($fp);
                                   } 
                                  
                                }


                            }
                  }
            }
         } catch (\Exception $e) {
             
         }
                               
        $sendemail = new Cs();
        $result    = $sendemail->activeEmail($data['to'],$data['title'],$data['content'],$data['sendperson'],$fileurl); 
        if ($result) {
            
             @unlink($fileurl);
            return true;
        } else {
            return false;
        }            
    }
}
