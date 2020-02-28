<?php
namespace app\amos\controller;

use think\Config;
use cms\Controller;
use cms\Response;

use think\Session;
use think\Request;
use app\amos\logic\AmosLogic;
use think\Exception;
class Base extends Controller
{
    /**
     * 网站标题
     *
     * @var unknown
     */
    protected $siteTitle;

    protected $request;

    protected $project_id=61;


    public function _initialize() {
      
       $this->request=Request::instance();
        
      
        
       
        $this->request->filter(['strip_tags','htmlspecialchars','trim']);
             
        $uname= $this->request->param('email');
         $pwd='123456'; //密码规则
          
        if($uname){
           
           
             $data=[
                'email'=>$uname,
                'password'=>$pwd,
                'project_id'=> $this->project_id
                 ];
              $this->dlogin($data);
              
               $domain= $this->request->root(true);
                $url=$this->request->baseUrl();
               $pa=$this->request->param();
               unset($pa['email']);
               if(!empty($pa)){
                   $pa=http_build_query($pa);
                   $url=$domain.$url.'?'.$pa;

               }else{
                   $url=$domain.$url;

               }
               $this->redirect($url);
              
        }else{
       
          if(!cookie('amos_token')){
              if(!cookie('amos_uname')){
                  $this->error('用户失效，请重新进入');
                
              }
               $data=[
                'email'=> cookie('amos_uname'),
                'password'=>$pwd,
                'project_id'=> $this->project_id
                 ];
                 $this->dlogin($data);
          }
        }
        
        
    }
  
    private function dlogin($data) {

                 
                
                 $result= AmosLogic::getInstance()->login($data);
              
               
                    
                     $clientid= isset($result['data']['client_id'])?$result['data']['client_id']:'';
                     $token= isset($result['data']['client_token'])?$result['data']['client_token']:'';
                     $re_token= isset($result['data']['client_refresh_token'])?$result['data']['client_refresh_token']:'';

                     cookie('amos_uname',$data['email']);
                     cookie('amos_token',$token,3600);
                    
                     cookie('amos_client_id',$clientid,3600);
                     cookie('amos_re_token',$re_token,3600);
                    
                     $client_code= session_id();
                    
                     cookie('amos_client_code',$client_code,3600);
                    

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
 