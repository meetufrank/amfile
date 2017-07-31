<?php
namespace app\common\sendemail;
use email\Cs;

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
        $youxiangtitle="用户添加成功提醒";
              if($type==1){
                  $YouxiangContent = "<strong>亲爱的用户:".$user['nickname'].",您好!</strong><br/>"
                        ."<br/>"
                        ."您已经成功的取得了ADVANCE-MEDICAL PATIENT PORTAL账号密码如下<br/>"
                        ."<br/>"
                          ."登录帐号：".$user['user_name']."<br/>"
                        ."<br/>"
                          ."登录密码：".$user['pwd']."<br/>"
                        ."<br/>"
                          ."您可以登录 http://demo.advance-medical.com.cn 进行case提交，我们会在最短时间内安排casemanager跟进您的case。<br/>"
                        ."<br/>"
                          ."请关注公众号，进入点击右下角我的-在线IM系统进行咨询。";
              }else{
                  $YouxiangContent = "<strong>亲爱的用户:".$user['nickname'].",您好!</strong><br/>"
                        ."<br/>"
                        ."您已经成功的取得了ADVANCE-MEDICAL PATIENT PORTAL账号密码如下<br/>"
                        ."<br/>"
                          ."登录帐号：".$user['user_name']."<br/>"
                        ."<br/>"
                          ."登录密码：".$user['pwd']."<br/>"
                        ."<br/>"
                          ."您可以登录 http://demo.advance-medical.com.cn/service 进行case处理。<br/>"
                        ."<br/>"
                          ."请关注公众号，进入点击右下角我的-在线IM系统进行咨询。";
              }
		
		$emailtrue = $emails->activeEmail($to,$youxiangtitle,$YouxiangContent);
    }
        /*
     * AM应用的layim用户表修改密码
     */
    public function editSend($user=[]){
        //调用email接口方法
	    $emails = new Cs();
		
		//为1请求发送邮件
		$to = $user['email'];
		
		//邮件主题
        $youxiangtitle="用户密码修改成功提醒";
	
		$YouxiangContent = "您好,".$user['nickname'].":<br/>"
                          ."帐号：".$user['user_name']."修改"
                          ."最新密码为：".$user['pwd']."<br/>"
                          ."提示：若遗忘密码可联系管理员重置。";
		$emailtrue = $emails->activeEmail($to,$youxiangtitle,$YouxiangContent);
    }
    
}



