<?php
namespace app\ltpage\controller;

use think\Config;
use cms\Controller;
use cms\Response;
use think\Session;
use think\Request;
use app\manage\service\ViewService;


use core\cases\logic\ChatUserLogic;

class Interfaces extends Controller
{
    /**
     * 网站标题
     *
     * @var unknown
     */
    protected $siteTitle;
    
    protected $request;


    public function _initialize() {
        $this->request=Request::instance();
        $this->request->filter(['strip_tags','htmlspecialchars','trim']);
    }
   
  

    
    public function messagevalid() {
        
        //获取参数
        
        $tel=$this->request->request('tel');
        
        if($tel){
            $where=[
                'tel'=>$tel
            ];
            $logic =ChatUserLogic::getInstance();
            $result=$logic->IsOnly($where);
           if(!$result){
               $this->error('手机号已存在，如有疑问请联系客服');
           }
          
            $code= session($tel.'_phone');
            $codearr= @json_decode($code,true);
            $num= rand(1000, 9999);
            $newcount=1;
            $now=time();
            $beforetime=cookie('beforetime');
            if($beforetime){
                    $this->error('获取短信验证太过频繁，请稍后再试');
            }
            if(!empty($codearr)){
                
                   //在刷新时间之前的次数不可大于5次
                   $refersh=isset($codearr['refersh'])?$codearr['refersh']:0;
                   $numcount=isset($codearr['num'])?$codearr['num']:0;
                  
                if($refersh){
                     
                     if($now<$refersh&&$numcount>=5){  
                        
                         $this->error('超过每日获取验证码的最大次数');
                     }
                }
                
                $newcount=$numcount+1;
            }
            $jsondata=[
                'tel'=>$tel,
                'endtime'=>$now+60*15,
                'code'=>$num,
                'num'=>$newcount,
                'refersh'=>strtotime(date('Y-m-d',strtotime('+1 day')))
            ];
            //发送短信验证码并且存储session
           
            session($tel.'_phone',@json_encode($jsondata));  
            cookie('beforetime',$now+60,60);
         
            //发送短信验证码
            
//            
            $msg=new \message\mess();
            $content='【汇医服务】汇医服务提醒您，您正在申请汇医服务账户注册，验证码：'.$num.' ,15分钟内有效。为了您的信息安全，请勿告知他人。';
            $msg->send($tel, $content);  
            
            
            $this->success('发送成功');
        }
     
        
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
 