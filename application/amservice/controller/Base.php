<?php
namespace app\amservice\controller;

use think\Config;
use cms\Controller;
use cms\Response;
use app\common\zhifu\Wechat;
use app\common\zhifu\Service;
use think\Session;
use think\Request;

use core\cases\logic\ChatUserLogic;
use core\cases\model\ChatUserModel;
use org\wechat\Jssdk;
class Base extends Controller
{
    /**
     * 网站标题
     *
     * @var unknown
     */
    protected $siteTitle;
    protected $wxconfig;

    protected $request;



    public function _initialize() {
      
        
        $this->wxconfig = $wxconfig = Service::getConfig('wechat');
        $this->request=Request::instance();
        $person= intval($this->request->param('person'));  //推荐人
        //查询用户信息
        $chatuser=new ChatUserLogic();
        $user_alias= ChatUserModel::getInstance()->alias_name;//chatuser表别名
        
        if(!empty($person)){
            $mapwhere=[
            $user_alias.'.id'=>$person
            ];
            $perid=$chatuser->getUserId($mapwhere);
            if(!empty($perid)){
                
                cookie('tj_person',$perid);
            }else{
                $person=0;
            }
        }
        $openid = Session::get('openid');
        //如果没有openid
        if (!$openid) {
            
            $wechat = new Wechat($wxconfig['wechat']['app_id'], $wxconfig['wechat']['app_secret']);
            $userinfo= $wechat->getUserInfo();
            
            $openid= isset($userinfo['openid'])?$userinfo['openid']:'';
            
            
            
            Session::set('userinfo',@json_encode($userinfo)); 
        }
            
       
        if(!$openid){
            $this->error('未能正常获取用户信息');
        }
        
        $where=[
            $user_alias.'.wxopenid'=>$openid
        ];
        $userid=$chatuser->getUserId($where);

        $action=request()->action();
        
        if($userid){
   
            cookie('tj_person',null);
            
            Session::set('userid',$userid); 
            if($action=='register'){
                $this->redirect('/serviceIndex');
            }
        }else{
           
           
            $tjid= !empty($person)?$person:(!empty(cookie('tj_person'))?cookie('tj_person'):0);
            if(!empty($tjid)){
                //cookie的存在会导致无法实时读取用户是否存在，所以要在做检测
                 $mapwhere=[
                    $user_alias.'.id'=>$tjid
                    ];
                    $perid=$chatuser->getUserId($mapwhere);
                    if(!empty($perid)){
                        
                  
                        //查询是否有重复推荐的
                        $ma=[
                            'userid'=>$tjid,
                            'openid'=>$openid
                        ];
                         $count=db('cases_hit_info')->where($ma)->count();
                         if(!$count){
                             $hitdata=[
                                'openid'=>$openid,
                                'userid'=>$tjid,
                                 'add_time'=>time()
                                ];

                             db('cases_hit_info')->insert($hitdata); 
                         
                         $cjinfo=db('cases_cjtj')->where(['userid'=>$tjid])->find();
                         if(empty($cjinfo)){
                             $insertdata=[
                                 'hit_num'=>1,
                                 'userid'=>$tjid,
                                 'update_time'=>time()
                             ];
                             db('cases_cjtj')->insert($insertdata);
                         }else{
                             $updata=[
                                 'hit_num'=>$cjinfo['hit_num']+1,
                                 'update_time'=>time()
                             ];
                             db('cases_cjtj')->where(['id'=>$cjinfo['id']])->update($updata);
                         }
                
                         }
                    }
               
            }
            
            if($action!='register'){
                cookie('re_url', $this->request->url());
                if($action=='index' ||  $action=='service_detail'){
                    
                }else{
                    $this->redirect('register');
                }
                
            }
           
           
           
        }

         //分享二维码
        if (!$this->request->isPost()) {
            
       
            if($userid){
                $fx_id=$userid;
            }elseif(cookie('tj_person')){
                $fx_id=cookie('tj_person');
            }else{
                $fx_id=0;
            }
            $jssdk = new Jssdk(config('wechat_appid'), config('wechat_appsecret'));
            $signPackage = $jssdk->GetSignPackage();
            //print_r($signPackage);exit;
            $this->assign('signPackage', $signPackage);
            $this->assign('fx_title', '家庭医生随身行');
            $this->assign('desc', '独立医疗咨询，关爱只为您');
            if($fx_id){
                $url=url('/serviceIndex',['person'=>$fx_id]);
            }else{
                $url=url('/serviceIndex');
            }
            $domain=request()->domain();
           
            $this->assign('drumpurl',  $domain.$url); //当前链接
            $this->assign('imgurl', $domain.'/static/ampay/images/fenxiang.jpg');      
       
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
 