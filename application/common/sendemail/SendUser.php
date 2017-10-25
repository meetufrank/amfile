<?php
namespace app\common\sendemail;
use email\Cs;
use core\cases\logic\ChatUserLogic;
use think\Queue;
class SendUser
{
    
    /*
     * AM应用的layim用户表添加
     */
    public function addSend($user=[],$type=1){
        //调用email接口方法
	    $emails = new Cs();
		
		//为1请求发送邮件
		$to = $user['email'];
		
		//邮件主题
       
        $url='http://'.$_SERVER['HTTP_HOST'];
        $user['url']=$url;
              if($type==1){
                  $is='user';
              }else{
                  $is='manager';
              }
		$YouxiangContent=ChatUserLogic::getInstance()->getLanguage($user,2); //获取邮件内容
                $email_data['to']=$to;
                $email_data['title']=$YouxiangContent['title'];
                $email_data['content']=$YouxiangContent['content'][$is];
                //加入任务队列中
                Queue::push('app\common\jobs\QueueClient@sendMAIL', $email_data, $queue ='jobs');
		//$emailtrue = $emails->activeEmail($to,$YouxiangContent['title'],$YouxiangContent['content'][$is]);
    }
        /*
     * AM应用的layim用户表修改密码
     */
    public function editSend($user=[]){
        //调用email接口方法
	    $emails = new Cs();
		
		//为1请求发送邮件
		$to = $user['email'];
	$url='http://'.$_SERVER['HTTP_HOST'];
        $user['url']=$url;
		//邮件主题
        
	$YouxiangContent=ChatUserLogic::getInstance()->getLanguage($user,3); //获取邮件内容
		$email_data['to']=$to;
                $email_data['title']=$YouxiangContent['title'];
                $email_data['content']=$YouxiangContent['content'];
                //加入任务队列中
                //Queue::push('app\common\jobs\QueueClient@sendMAIL', $email_data, $queue ='jobs');
                
		$emailtrue = $emails->activeEmail($to,$YouxiangContent['title'],$YouxiangContent['content']);
    }
    
    /*
     * am应用layim的casemanger接受case
     */
       public function acceptCase($user=[]){
        //调用email接口方法
//	    $emails = new Cs();
		
		//为1请求发送邮件
		$to = $user['email'];
	$url='http://'.$_SERVER['HTTP_HOST'];
        $user['url']=$url;
		//邮件主题
        
	$YouxiangContent=ChatUserLogic::getInstance()->getLanguage($user,6); //获取邮件内容
		$email_data['to']=$to;
                $email_data['title']=$YouxiangContent['title'];
                $email_data['content']=$YouxiangContent['content'];
                //加入任务队列中
                Queue::push('app\common\jobs\QueueClient@sendMAIL', $email_data, $queue ='jobs');
		//$emailtrue = $emails->activeEmail($to,$YouxiangContent['title'],$YouxiangContent['content']);
    }
    
    
    
    
    //预约邮件
    public function yuyueemail($to,$title,$content){
      
               $email_data['to']=$to;
                $email_data['title']=$title;
                $email_data['content']=$content;
                //加入任务队列中
                Queue::push('app\common\jobs\QueueClient@sendMAIL', $email_data, $queue ='jobs');
        //$emails->activeEmail($to,$subject,$body,$receivingparty);
    }
}



