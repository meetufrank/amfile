<?php
namespace app\amos\controller;

use think\Config;
use cms\Controller;
use cms\Response;
use app\manage\service\ViewService;
use think\Session;
use think\Request;
use think\Db;


use app\amos\logic\AmosLogic;
use think\Exception;
use app\amos\controller\Interfaces;
use app\common\sendemail\SendUser;
class Index extends Base
{
    /**
     * 网站标题
     *
     * @var unknown
     */
    protected $siteTitle;
    protected $zoomlist=[];
    protected $zoomurl='';

    public function _initialize() {
        parent::_initialize();
        
        $this->request=Request::instance();
        
        
        $kslist=[
            'general'=>'全科',
            'nutrition'=>'营养',
            'psychological'=>'心理',
            'sports'=>'运动',
        ];
        $this->assign('ks_text_list', $kslist);
        
        $zoomlist=[
            'general'=>'https://zoom.com.cn/j/2595518282',
            'nutrition'=>'https://zoom.com.cn/j/2595518282',
            'psychological'=>'https://zoom.com.cn/j/2595518282',
            'sports'=>'https://zoom.com.cn/j/2595518282',
        ];
        $this->zoomlist=$zoomlist;
        $this->assign('zoomlist', $zoomlist);
        
        $this->request->filter(['strip_tags','htmlspecialchars','trim']);
        
    }
  
    
    
  
    
    public function index() {
       $this->siteTitle='咨询列表';
       $listtype=  $this->request->param('listtype');
       if(!$listtype){
           $listtype='phone';
       }
       $this->assign('listtype', $listtype);
       if($this->request->isPost()){
           try {
               $type=  $this->request->param('type');
               $filter='upcoming';
               if($type==1){
                   $filter='upcoming';
               }else{
                   $filter='history'; 
               }
               $data=[
                   'filter'=>$filter,
//                   'limit'=>10,
                   'offset'=>0,
                   'files'=>false
               ];
              
            //获取videocall
            if($listtype=='phone'){
                $result= AmosLogic::getInstance()->omclist($data);
        
          
                if($result['status']!='ok'){
                    $this->error($result['data']['message']);
                }
                $rdata=$result['data'];
                $omclist=$rdata['consultations'];

                foreach ($omclist as $key => $value) {
                    $omclist[$key]['date']=date('Y-m-d H:i', strtotime ("+8 hour", strtotime($value['date'])));//Europe/Amsterdam 和中国时区相差8小时
                }

                 $omclist=array_reverse($omclist);
                $this->assign('list', $omclist);
           }else{
                $result= AmosLogic::getInstance()->zxlist($data);
        
                if($result['status']!='ok'){
                    $this->error($result['data']['message']);
                }
                $rdata=$result['data'];
                $videolist=$rdata['videocalls'];
                $videolist=array_reverse($videolist);
                $this->assign('list', $videolist);
           }
            

            
            
            
           $data['content']=$this->fetch('index/library/zxlist',[],['__AMOS__'=>'/static/amos']);
           
           
           } catch (Exception $ex) {
               $this->error($ex->getMessage());
           }
           
           
           $this->success('获取成功','',$data);
       }
        
        return $this->fetch('',[],['__AMOS__'=>'/static/amos']);
        
        
    }
    //统一验证开启会议时间差
    private function videotime($rdata) {
        $startdate=$rdata['date'].' '.$rdata['time'];
        $start= strtotime($startdate);
        $stop=strtotime($startdate)+60*30;
        $now= time();
       if($rdata['status']=='in_progress' && $now>=$start && $now<=$stop){
           return true;
       }else{
           return false;
       }
    }
    public function validmeeting() {
        $id= $this->request->param('id');
        $data=[
             'files'=>false
             ];
         $result= AmosLogic::getInstance()->videoinfo($data,$id);


            if($result['status']!='ok'){
                $this->error($result['data']['message']);
            }
            $rdata=$result['data'];
            $url=url('/amos_meeting',['id'=>$id,'uname'=> cookie('amos_uname'),'ishref'=>1]);
            $ismeeting=$this->videotime($rdata);
            $ismeeting=true;
        if($this->request->isPost()){
            
           
            
               if($ismeeting){
                   $this->success('开启会议', $url,$rdata); 
               }else{
                   $this->error('当前时间未在预约的时间之内');
               }
                
                
            
              
        }else{
            $url=$this->zoomlist[$rdata['specialty']];
            if($ismeeting){
                   $this->redirect($url);
               }else{
                   $this->error('会议未开始');
               }
        }
    }
    public function detail() {
        
         $this->siteTitle='咨询详情';
         
         $id=  $this->request->param('id');
         $type=  $this->request->param('type');
         
         if(!$type){
             $type='phone';
         }
         
         if(!$id){
             $this->error('缺少重要参数');
         }
         $data=[
             'files'=>false
         ];
         
        if($type=='phone'){
             
      
            $result= AmosLogic::getInstance()->omcinfo($data,$id);
       
          
            if($result['status']!='ok'){
                $this->error($result['data']['message']);
            }
            $rdata=$result['data'];
            $rdata['date']=date('Y-m-d H:i', strtotime ("+8 hour", strtotime($rdata['date']))); //Europe/Amsterdam 和中国时区相差8小时
        
            
            
            }else{
                $result= AmosLogic::getInstance()->videoinfo($data,$id);
       
          
                if($result['status']!='ok'){
                    $this->error($result['data']['message']);
                }
                $rdata=$result['data'];
                $ismeeting=$this->videotime($rdata);
                $this->assign('ismeeting', $ismeeting);
                $url=url('/amos_meeting',['id'=>$id,'uname'=> cookie('amos_uname'),'ishref'=>1]);
                $domain=$this->request->domain();
                $url=$domain.$url;
                $this->assign('meetingurl', $url);
                
            }
            $docinfo=[];
           
            if($rdata['doctor_id']){
                $did=$rdata['doctor_id'];
                $data=[];
                   
                 $result= AmosLogic::getInstance()->docinfo($data,$did);


                    if($result['status']!='ok'){
                        $this->error($result['data']['message']);
                    }
                    
                    
                    
                    $docinfo=$result['data'];
                    
            }
            
             $this->assign('docinfo', $docinfo);
            $this->assign('info', $rdata);
            $this->assign('type', $type);
         
         return $this->fetch('',[],['__AMOS__'=>'/static/amos']);
    }
    public function fwcancel() {
        
         if($this->request->isPost()){
             $id=  $this->request->param('id');
             $type=  $this->request->param('type');

             if(!$type){
                 $type='phone';
             }
             $data=[];
             if($type=='phone'){
                 
            
             $result= AmosLogic::getInstance()->omccancel($data,$id);


             }else{
               $result= AmosLogic::getInstance()->videocancel($data,$id);  
                 
             }
                if($result['status']!='ok'){
                    $this->error($result['data']['message']);
                }
             
             
                $this->success('取消成功');
         }
        
    }
    
    public function phonecall() {
        set_time_limit(0);
        $this->siteTitle='电话回呼添加';
        $fid=  $this->request->param('fid');
        $this->assign('fid', $fid);
        $info=[];
        if($fid){
            
       
         $info=db('amos_family')->where(['id'=>$fid])->find();
         $uid=cookie('amos_client_id');
         if(empty($info)){
             $this->error('家属不存在');
         }
         if($info['client_id']!=$uid){
             $this->error('这位家属属于你');
         }
        }
        if($this->request->isPost()){
            if(!$fid){
                $this->error('请选择家庭成员');
            }
            $question=  $this->request->param('question');
            $filename=  $this->request->param('filename');
            $specialty=  $this->request->param('specialty');
            $loadinterface=new Interfaces();
            $fileinfo=$loadinterface->getbasefile();
          
            $data=[
               "source"=> "app",
               'channel'=>'phone',
                'language'=>$info['language'],
                'time_zone'=>$info['time_zone'],
                'country'=>$info['country'],
                'doctor_location'=>'local',
                'patient'=>[
                    'name'=>$info['name'],
                    'surname'=>$info['name'],
                    'birth_date'=>$info['birth_date'],
                    'phone_prefix'=>$info['phone_prefix'],
                    'phone'=>$info['phone']
                ],
                'specialty'=> $specialty,
                'consultation'=>$question
            ];
            if(!empty($fileinfo)){
               
                $data['patient_files']=[
                    [
                        'name'=> empty($filename)?$info['name'].'的附件':$filename,
                        'file'=>$fileinfo
                    ]
                ];
            }
            $result= AmosLogic::getInstance()->consuladd($data);
         
             if($result['status']!='ok'){
                $this->error($result['data']['message']);
              }
            
              $send=new SendUser();
              $to=cookie('amos_uname'); 
              $content="<strong>亲爱的".$info['name']."先生/女士，您好！</strong><br/>".
                      "<br/>".
                      "<br/>".
                      "您已经提交了电话回呼。我们的专案医生将在24（工作）小时内与您联络。<br/>".
                      "<br/>".
                      "<br/>".
                      "<strong>祝，安康！</strong><br/>".
                      "<strong>汇医亚太区</strong><br/>";
              $send->amoseamil($to, '', $content);
              $this->success('提交成功',url('/amosindex'));
        }
        
//        $result= AmosLogic::getInstance()->faminfo([],$fid);
        
//       if($result['status']!='ok'){
//                $this->error($result['data']['message']);
//       }
       
       $this->assign('info', $info);        
                
        return $this->fetch('',[],['__AMOS__'=>'/static/amos']);
    }
       public function videocall() {
        set_time_limit(0);
        $this->siteTitle='视频预约添加';
        $fid=  $this->request->param('fid');
        $this->assign('fid', $fid);
        $info=[];
        if($fid){
            
       
         $info=db('amos_family')->where(['id'=>$fid])->find();
         $uid=cookie('amos_client_id');
         if(empty($info)){
             $this->error('家属不存在');
         }
         if($info['client_id']!=$uid){
             $this->error('这位家属属于你');
         }
        }
        if($this->request->isPost()){
            if(!$fid){
                $this->error('请选择家庭成员');
            }
            $question=  $this->request->param('question');
            $filename=  $this->request->param('filename');
            $lang= $this->request->param('amos_language');
            $specialty=  $this->request->param('specialty');
            $date=  $this->request->param('birthday');
            $dtime=  $this->request->param('time_after');
            $time_zone=  $this->request->param('time_zone');
            $loadinterface=new Interfaces();
            $fileinfo=$loadinterface->getbasefile();
            $age=getage($info['birth_date']);
            
            $data=[
               "source"=> "app",
               'language'=>$lang,
                'specialty'=>$specialty,
                'time_zone'=>$time_zone,
                'country'=>$info['country'],
                'doctor_location'=>'local',
                'date'=>$date,
                'time'=>$dtime,
                'patient'=>[
                    'name'=>$info['name'],
                    'surname'=>$info['name'],
                    'birth_date'=>$info['birth_date'],
                    'age'=>$age,
                    'phone_prefix'=>$info['phone_prefix'],
                    'phone'=>$info['phone']
                ],
                'call_reason'=>$question,
                'scheduled'=>true
            ];
            
            if(!empty($fileinfo)){
               
                $data['patient_files']=[
                    [
                        'name'=> empty($filename)?$info['name'].'的附件':$filename,
                        'file'=>$fileinfo
                    ]
                ];
            }
            
            $result= AmosLogic::getInstance()->videoadd($data);
         
             if($result['status']!='ok'){
                $this->error($result['data']['message']);
              }
            
              $id=$result['data']['id'];
              $send=new SendUser();
              $to=cookie('amos_uname'); 
              $date= date('Y-m-d H:i:s');
              $url=url('/amos_meeting',['id'=>$id,'uname'=> $to,'ishref'=>1]);
                $domain=$this->request->domain();
                $url=$domain.$url;
//              $url=isset($this->zoomlist[$specialty])?$this->zoomlist[$specialty]:'';
              $content="<strong>亲爱的".$info['name']."先生/女士，您好！</strong><br/>".
                       "<br/>".
                      "<br/>".
                      "您已经提交了".$date."的视频预约。请准时参加视频会议，以下是参会者链接 ".$url."<br/>".
                      "<br/>".
                      "<br/>".
                      "<strong>祝，安康！</strong><br/>".
                      "<strong>汇医亚太区</strong><br/>";
              $send->amoseamil($to, '', $content);
              
              $this->success('提交成功',url('/amosindex'));
        }
        
//        $result= AmosLogic::getInstance()->faminfo([],$fid);
        
//       if($result['status']!='ok'){
//                $this->error($result['data']['message']);
//       }
       
       $this->assign('info', $info);        
                
        return $this->fetch('',[],['__AMOS__'=>'/static/amos']);
    }
    

    
    public function gettime() {
        if($this->request->isPost()){
            $date= $this->request->param('date');
            $lang= $this->request->param('lang'); 
            $timezone= $this->request->param('timezone'); 
            $data=[
                'date_from'=>$date,
                'date_to'=>$date,
                'language'=>$lang,
                'time_zone'=>$timezone,
            ];
            $result= AmosLogic::getInstance()->getvideotime($data);
         
             if($result['status']!='ok'){
                $this->error($result['data']['message']);
              }
            
              $this->success('获取成功','',$result['data']); 
        }
    }
    
    public function familylist() {
        
         $this->siteTitle='家属列表';
         $selectid=  $this->request->param('id');
        $this->assign('selectid', $selectid);
        $act=  $this->request->param('act');

        $this->assign('act', $act);
  
       if($this->request->isPost()){
           try {
            
//            $result= AmosLogic::getInstance()->famlist([]);
//            
//            if($result['status']!='ok'){
//                $this->error($result['data']['message']);
//            }
//       
//            $rdata=$result['data'];
//            $list=$rdata['dependents'];
               $uid=cookie('amos_client_id');
               $map=[
                   'client_id'=>$uid
               ];
              $list= db('amos_family')->where($map)->select();
              
            $this->assign('list', $list);
           $data['content']=$this->fetch('index/library/family_library',[],['__AMOS__'=>'/static/amos']);
           
           
           } catch (Exception $ex) {
               $this->error($ex->getMessage());
           }
           
           
           $this->success('获取成功','',$data);
       }
        return $this->fetch('',[],['__AMOS__'=>'/static/amos']);
    }
    
    
    public function familyadd() {
        $this->siteTitle='家属添加';
        $act=  $this->request->param('act');

        $this->assign('act', $act);
        if($this->request->isPost()){
          $data= $this->request->param();
            
          try {
              
              $pdata=[
               'relation_type'=> isset($data['relation_type'])?$data['relation_type']:'other',
               'gender'=> isset($data['gender'])?$data['gender']:'male',
               'name'=> isset($data['username'])?$data['username']:'',
               'country'=> isset($data['amos_country'])?$data['amos_country']:'',
               'language'=> isset($data['amos_language'])?$data['amos_language']:'',
               'phone_prefix'=> isset($data['phone_prefix'])?$data['phone_prefix']:'',
               'phone'=> isset($data['phone'])?$data['phone']:'',
               'time_zone'=> isset($data['time_zone'])?$data['time_zone']:'',
               'birth_date'=> isset($data['birthday'])?$data['birthday']:'',
               'email'=> isset($data['email'])?$data['email']:'',
               
               
           ];
            
//            $result= AmosLogic::getInstance()->famadd($pdata);
//           
//            if($result['status']!='ok'){
//                $this->error($result['data']['message']);
//            }
               $uid=cookie('amos_client_id');
               $pdata['client_id']=$uid;
                db('amos_family')->insert($pdata);
           
           
           } catch (Exception $ex) {
               $this->error($ex->getMessage());
           }
           
      
           
           
           
           $this->success('保存成功',url('/amosfamily',['act'=>$act]));
       }
        return $this->fetch('',[],['__AMOS__'=>'/static/amos']);
    }
    
    public function familyedit() {
        $this->siteTitle='家属修改';
        $act=  $this->request->param('act');

        $this->assign('act', $act);
       $id= $this->request->param('id');
       $info=db('amos_family')->where(['id'=>$id])->find();
       $uid=cookie('amos_client_id');
       if(empty($info)){
           $this->error('家属不存在');
       }
       if($info['client_id']!=$uid){
           $this->error('这位家属属于你');
       }
        if($this->request->isPost()){
          $data= $this->request->param();
            
          try {
              
              $pdata=[
               'relation_type'=> isset($data['relation_type'])?$data['relation_type']:'other',
               'gender'=> isset($data['gender'])?$data['gender']:'male',
               'name'=> isset($data['username'])?$data['username']:'',
               'country'=> isset($data['amos_country'])?$data['amos_country']:'',
               'language'=> isset($data['amos_language'])?$data['amos_language']:'',
               'phone_prefix'=> isset($data['phone_prefix'])?$data['phone_prefix']:'',
               'phone'=> isset($data['phone'])?$data['phone']:'',
               'time_zone'=> isset($data['time_zone'])?$data['time_zone']:'',
               'birth_date'=> isset($data['birthday'])?$data['birthday']:'',
               'email'=> isset($data['email'])?$data['email']:'',
               
               
           ];
           
           db('amos_family')->where(['id'=>$id])->update($pdata);
             
             
//            $result= AmosLogic::getInstance()->famedit($pdata,$id);
//           
//            if($result['status']!='ok'){
//                $this->error($result['data']['message']);
//            }
//       
          
           
           
           } catch (Exception $ex) {
               $this->error($ex->getMessage());
           }
           
      
           
           
           
           $this->success('保存成功',url('/amosfamily',['act'=>$act]));
       }
//       $result= AmosLogic::getInstance()->faminfo([],$id);
//       if($result['status']!='ok'){
//                $this->error($result['data']['message']);
//       }
//       $this->assign('info', $result['data']);
       $this->assign('info', $info);
        return $this->fetch('',[],['__AMOS__'=>'/static/amos']);
    }
  
    
    public function familydel() {
        if($this->request->isPost()){
            $id= $this->request->param('id');
           $info=db('amos_family')->where(['id'=>$id])->find();
           $uid=cookie('amos_client_id');
           if(empty($info)){
               $this->error('家属不存在');
           }
           if($info['client_id']!=$uid){
               $this->error('这位家属属于你');
           }
           
           
           db('amos_family')->where(['id'=>$id])->delete();
       
           $this->success('删除成功');
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
 