<?php
namespace app\ampage\controller;

use think\Config;
use cms\Controller;
use cms\Response;
use app\manage\service\ViewService;
use app\common\App;
use think\Request;
use think\Queue;
use app\ampage\validate\YjpageValidate;
class Index extends Controller
{
    /**
     * 网站标题
     *
     * @var unknown
     */
    protected $siteTitle;
    
   
    /*
     * 发送邮件的一个页面
     * 
     */
    public function yjpage() {
        $request= request();
        
        if($request->isPost()){
            
           
                $data=[
                     'name' => $request->param('name'),
                     'number' => $request->param('number'),
                     'email' => $request->param('email'),
                     'question' => $request->param('question')
                ];
               
                $YjpageValidate=YjpageValidate::getInstance();
                $result =$YjpageValidate->scene('send')->check($data);
                if(!$result){
                    
                     $msg=$YjpageValidate->getError();
                     $this->error($msg);
                }
               $mailTo = "lilyhuang@advance-medical.com.cn";
           //     $mailTo = "972270516@qq.com";
                $title='汇医国际健康咨询邮件';
                $from = '汇医web';
                $content = "<strong>Dear LilyHuang,</strong><br/>"
                        ."<br/>"
                        ." 您收到一份来自访问汇医国际健康顾问页面的一条咨询信息<br/>"
                        ."<br/>"
                          ."姓名：".$data['name']."<br/>"
                        ."<br/>"
                          ."手机号码：".$data['number']."<br/>"
                        ."<br/>"
                          ."邮箱：".$data['email']."<br/>"
                        ."<br/>"
                          ."问题描述：".$data['question']."<br/>"
                        ."<br/>"
                        ."<br/>"

                        ."<br/>"
                          ."谢谢!<br/>"
                          ."汇医web";
                      
                     
                
                $email_data['to']=$mailTo;
                $email_data['title']=$title;
                $email_data['sendperson']=$from;
                $email_data['content']=$content;
                
                //加入任务队列中
                Queue::push('app\common\jobs\QueueClient@sendMAIL', $email_data, $queue ='jobs');
                
                
                $this->success('提交成功，我们会尽快回复您！', url('/health'));
            
        }
        $this->siteTitle='AM医疗咨询';
        
        return $this->fetch();
    }
    public function two() {
        $this->siteTitle='专家意见';
        
        return $this->fetch();
    }
    public function three() {
        $this->siteTitle='FAQ';
        
        return $this->fetch();
    }
    public function four() {
        $this->siteTitle='国际转诊';
        
        return $this->fetch();
    }
    public function five() {
        $this->siteTitle='汇医专家';
        
        return $this->fetch();
    }
    
    public function index() {
        $this->siteTitle='身心健康';
        
        return $this->fetch('header');
    }
  
       /**
     *
     * {@inheritdoc}
     *
     * @see Controller::beforeViewRender()
     */
    protected function beforeViewRender()
    {
        // 网站标题
        $this->assign('site_title', $this->siteTitle);
    }

    /**
     *
     * {@inheritdoc}
     *
     * @see Controller::getView()
     */
    protected function getView()
    {
        return ViewService::getSingleton()->getView();
    }
}
 