<?php
namespace app\amservice\controller;

use think\Config;
use cms\Controller;
use cms\Response;
use app\manage\service\ViewService;
use app\common\zhifu\Wechat;
use app\common\zhifu\Service;
use think\Session;
use think\Request;
use think\Db;


use core\cases\logic\CaseLogic;
use core\cases\logic\ChatUserLogic;
use core\cases\logic\CompanyLogic;
use app\amservice\validate\ChatUserValidate;
use core\cases\model\ChatUserModel;
use app\amservice\validate\TokenValidate;
use core\cases\model\CaseModel;
use core\cases\model\AreaModel;
use core\manage\model\FileModel;
use app\common\sendemail\SendUser;
use app\amservice\validate\CaseValidate;
use core\cases\logic\CaseTypeLogic;

use core\cases\logic\CaseServiceLogic;
use core\cases\logic\CaseOrderLogic;
use core\cases\logic\CaseCardLogic;

class Index extends Base
{
    /**
     * 网站标题
     *
     * @var unknown
     */
    protected $siteTitle;
    
    
    public function _initialize() {
        parent::_initialize();
        
        $this->request=Request::instance();
        
      
        
       
        $this->request->filter(['strip_tags','htmlspecialchars','trim']);
        
    }
  
    
    
  
    
    public function index() {
        $this->siteTitle='汇医服务-首页';
        
         //查询case类型
         $type_list=db('cases_case_type')->select();
         $newlist=[];
         foreach (@$type_list as $key => $value) {
             $newlist[$value['id']]=$value;
         }
        
         
         
         $this->assign('newlist', $newlist);
       
         
         
         return $this->fetch();
      
        
        
    }
    
    public function service_detail($typeid=1) {
        $typeid= intval($typeid);
        $this->assign('typeid', $typeid);
        $map=['id'=>$typeid];
        $typeinfo=db('cases_case_type')->where($map)->find();
        if(!empty($typeinfo)){
            $hotnum=$typeinfo['hot_num'];
            $up_data=[
                'hot_num'=>$hotnum+1
            ];
            db('cases_case_type')->where($map)->update($up_data);
            
            
            
            $this->siteTitle='汇医服务-'.$typeinfo['typename'];
            
            
            return $this->fetch();
            
        }
        
        
    }
    
    
  
   
     public function register() {
        $this->siteTitle='汇医服务-客户注册';
        $request=$this->request;
       
        if ($request->isPost()) {
            $inputcode=$request->request('codenum');
            $tel=$request->request('tel');
            $email=$request->request('email');
            $nickname=$request->request('nickname');
            $code= session($tel.'_phone');
           
            $codearr= @json_decode($code,true);
            if(empty($codearr)){
                $this->error('非法操作');
            }
            $codenum=isset($codearr['code'])?$codearr['code']:0;
            $endtime=isset($codearr['endtime'])?$codearr['endtime']:0;
            if($endtime<time()){
                $this->error('验证码已过期,请重新发送');
            }
            if($inputcode!=$codenum){
                $this->error('验证码不正确');
            }
            
            $userinfo=json_decode(session('userinfo'),true);
            $openid= isset($userinfo['openid'])?$userinfo['openid']:'';
            if(empty($userinfo) || !$openid){
                $this->error('用户信息未能正常获取，请稍后再试');
            }
            $sex= isset($userinfo['sex'])?$userinfo['sex']:0;
            if($sex==2){  //调整性别
               $sex=0; 
            }elseif($sex!=1){
                $sex=2; 
            }
            $country= isset($userinfo['country'])?$userinfo['country']:'';
            $province= isset($userinfo['province'])?$userinfo['province']:'';
            $city= isset($userinfo['city'])?$userinfo['city']:'';
            $headimgurl= isset($userinfo['headimgurl'])?$userinfo['headimgurl']:'';
            $username='wx_'.date('YmdHis',time());
            //注册成功检测有无推荐人cookie
            $tj_person=cookie('tj_person');
            if(!empty($tj_person)){
                $tj_id=$tj_person;
            }else{
                $tj_id=0;
            }
            $data = [
                'user_name' => $username,
                'pwd' => '123456',
                'pwd_again'=>'123456',
                'nickname' => $nickname,
                'sex' => $sex,
                'avatar' => $headimgurl,
                'company' => 15,
                'tel' => $tel,
                'email' => $email,
                'sort' => 0,
                'area'=>$country.' '.$province.' '.$city,
                'wxopenid'=>$openid,
                '__token__'=>$request->request('__token__'),
                'tj_id'=>$tj_id

            ];
           
            if($data['avatar']==''){
                if($data['sex']){
                    $data['avatar']='/static/laychat/phone/img/moren.png';
                }else{
                    $data['avatar']='/static/laychat/phone/img/moren1.png';
                }
            }

            //检测用户名重复
           $where=[

               [
                   'where'=>['user_name'=>$data['user_name']],
                   'msg'=>'服务繁忙，请重新提交'
               ],
               [
                   'where'=>['tel'=>$data['tel']],
                   'msg'=>'手机号已存在'
               ]

           ];
         
            $logic =ChatUserLogic::getInstance();
               if(is_array($where)){
               foreach ($where as $key => $value) {
                   $result=$logic->IsOnly($value['where']);
                   if(!$result){
                       $this->error($value['msg']);
                   }
               }
              }
            
           // 验证
            $validate=new ChatUserValidate;
            if (! $validate->scene('add')->check($data)) {
               $this->error($validate->getError());
             }
           
           
            
              // 添加
            $model = ChatUserModel::getInstance();
            //加密密码
            $data['pwd']= md5($data['pwd']);
            unset($data['pwd_again']);
            unset($data['__token__']);
            $status = $model->save($data);
            $uid=$model->id;  //获取自增id
            //添加成功以后重新计算注册数
            $cjinfo=db('cases_cjtj')->where(['userid'=>$tj_id])->find();
            if(!empty($cjinfo)){
                $updata=[
                 'zc_num'=>$cjinfo['zc_num']+1,
                 'update_time'=>time()
                 ];

                db('cases_cjtj')->where(['id'=>$cjinfo['id']])->update($updata);
            }
            //添加成功后将赠送该手机的卡片改成该用户的
            $upwhere=[
                'send_id'=>-1,
                'no_user_tel'=>$tel
            ];
            $upda=[
                'send_id'=>$uid,
                'no_user_tel'=>''
            ];
            db('cases_order_card')->where($upwhere)->update($upda);
            cookie('tj_person',null);
            $href= cookie('re_url');
            cookie('re_url',null);
            if(!$href){
                $href= url('/serviceIndex');
            }
            $this->success('注册成功',$href);
            session($tel.'_phone',null);    
            cookie('beforetime',null);
        }else{
            
        $token = $request->token('__token__', 'sha1');
        $this->assign('token', $token);
        
        $beforetime= cookie('beforetime');
        if($beforetime){   //计算时间差
            $btime=$beforetime-time();
            $this->assign('btime', $btime>0?$btime:0);
        }else{
            $this->assign('btime', 0);
            
        }
        

      
        return $this->fetch();
        
        } 
    }
    
 
//    public function addcrontab() {  //只需要执行一次就需要注释
//       
//        \think\Queue::push('app\common\jobs\Cardcrontab@jhCard', [], $queue ='jobs');
//    }
    
    
    //选择服务
    public function selectservice($id=null) {
        //修改专用 id242为技术专用
        $userid= session('userid');
        $this->assign('userid', $userid);
        $this->siteTitle='汇医服务-选择服务';
        $typeid= intval($id);
        if(!$typeid){
            $this->redirect('/serviceIndex');
        }
        $typename=db('cases_case_type')->where('id',$typeid)->value('typename');
        $this->assign('typename', $typename);
        $where=[
            'status'=>1
        ];
        $serive_list=CaseServiceLogic::getInstance()->getListByType($typeid, $where);
        if(empty($serive_list)){
            $this->error('该服务暂未开放');
        }
        $this->assign('serive_list', $serive_list);
       
        return $this->fetch();
        
    }
    public function payprotocol() {  //支付条款
        
        
        return $this->fetch();
    }
    //支付页面
    public function payorder($sid=null) {  //服务id
        
        $this->siteTitle='汇医服务-等待付款';
        
        $sid= intval($sid);
        if(!$sid){
            $this->redirect('/serviceIndex');
        }
        $request=$this->request;
        //服务卡数量
        $pay_num= $request->request('paynum');
        $pay_num= intval($pay_num)?$pay_num:1;
        if($pay_num>20){
           $this->error('每单最多只能买20张服务卡');
        }
        $info=CaseServiceLogic::getInstance()->getInfoById($sid);
        if(empty($info)){
           $this->error('未能正确选择服务');
        }
      
        $this->assign('info', $info);
       
                    
        $order_price=$info['price']*$pay_num;
        
        if($request->isPost()){
            
            $data=[
                'token'=>$sid,
                '__token__'=>$request->request('__token__')
            ];
            // 验证
            $validate=new TokenValidate;
            if (! $validate->scene('token')->check($data)) {
               $this->error($validate->getError());
             }
            $userid= session('userid');
            $openid= session('openid');
            $order_no= CaseOrderLogic::getInstance()->build_order_no();
            $card_price=$info['price'];
            $time=time();
            
            Db::startTrans();
            try{
                
                $orderdata=[
                'uid'=>$userid,
                'order_no'=>$order_no,
                'order_money'=>$order_price,
                'state'=>0,
                'addtime'=>$time,
                'pay_way'=>'wechat'
                ];
               $orderid=db('cases_order_info')->insertGetId($orderdata);
               if(!$orderid){
                   $this->error('生成订单异常');
               }
               $cardinsert=[];
               for ($i=1;$i<=$pay_num;$i++){
                   $card_num= CaseCardLogic::getInstance()->getCardId();
                   $cardorder=[
                       'uid'=>$userid,
                       'orderid'=>$orderid,
                       'card_num'=>$card_num,
                       'add_time'=>$time,
                       's_type_id'=>$info['s_type'],
                       'cardname'=>$info['name'],
                       'price'=>$card_price,
                       'times'=>$info['times'],
                       'duration'=>$info['duration'],
                       'unit'=>$info['unit'],
                       'unitname'=>$info['unitname'],
                       'status'=>0
                   ];
                   $cardinsert[]=$cardorder;
               }
               
               db('cases_order_card')->insertAll($cardinsert);
               //db('cases_order_card')->insert($cardorder);
               Db::commit();
            } catch (\Exception $e){
                Db::rollback();
                $this->error('系统错误');
            }
            $tel_arr=[   //超级手机号
                '13524595425',
                '13801994862',
                '18721667531',
                '18616696490'
            ];
            //查询该用户的手机号是否在超级账号中
            $tel=db('cases_chatuser')->where(['id'=>$userid])->value('tel');
            if(in_array($tel, $tel_arr)){  //超级账号不用支付直接修改卡状态
                
                    $update=[
                            'state'=>1,
                            'order_money'=>0,
                            'update_time'=>$time
                        ];
                        
                        db('cases_order_info')->where(['id'=>$orderid])->update($update);
                        
                        $cardupdate=[
                            'add_time'=>$time,
                            'update_time'=>$time,
                            'status'=>1,
                            'jh_stop_time'=>$time+ config('jh_times')
                        ];
                        db('cases_order_card')->where(['orderid'=>$orderid])->update($cardupdate);
                        
                        $this->redirect('/userservice');
            }else{
                
            
            //调起支付
            
             $type='wechat';
             $notifyurl=request()->root(true) . url('amservice/api/notifyx',['type'=>$type]);
             $returnurl=request()->root(true) . url('amservice/api/returnx',['type'=>$type]);
             $params = [
                    'amount'    => $order_price,
                    'orderid'   => $order_no,
                    'type'      => $type,
                    'title'     => '支付',
                    'method'    => 'mp',
                    'openid'    => $openid,
                    'notifyurl'    => $notifyurl,
                    'returnurl'    => $returnurl,
                ];
                session('wechatorderdata',$params);

                session('nopayback', url('/payorder',['sid'=>$sid]));
                $this->redirect('api/wechat');
            }
        }else{
            $this->assign('pay_num', $pay_num);
            $this->assign('order_price', $order_price);
            $token = $request->token('__token__', 'sha1');
            $this->assign('token', $token);
             return $this->fetch();
        }
       
       
    }
    
    
    
    public function userservice() {
        
        
        $this->siteTitle='汇医服务-我的服务卡';
        $userid= session('userid');
        //查询有无需要自动激活该用户的卡
        $time=time();
            
            $wheremap=[
                'jh_stop_time'=>[
                   [ 'neq',0],
                   ['elt',$time]
                    ],
                'jh_time'=>0,
                'uid'=>$userid
                
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
        $service_list=CaseCardLogic::getInstance()->getList($userid);
        
        //print_r($service_list);exit;
        
          $this->assign('userid', $userid);
         $this->assign('service_list', $service_list);
         return $this->fetch();
    }
    
    
    //用户服务卡操作
    public function oprateservice() {
        $request=$this->request;
        $request->filter('htmlspecialchars');
        if ($request->isPost()) {
            $id= intval($request->request('id'));
            $action=$request->request('action');
            $tel= trim(htmlspecialchars($request->request('tel')));
            if(empty($id) || empty($action)){
                $this->error('缺少必要参数');
            }
            $userid= session('userid');
            $cardinfo=db('cases_order_card')->where(['id'=>$id])->find();
            //先检测用户是否有权操作这张卡
            if(empty($cardinfo)){
                $this->error('未找到这张服务卡');
            }
            $carduid=$cardinfo['uid'];
            $sendid=$cardinfo['send_id'];
            $is_jh= empty($cardinfo['jh_time'])?0:1;
            $is_used=$cardinfo['is_used'];
            
            $is_me=0;
            $is_zs=0;
            if($carduid==$userid){
                $is_me=1;
            }
            if($sendid==$userid){
                $is_zs=1;
            }
            if(($is_me==0 && $is_zs==0 )|| empty($userid)){
                $this->error("您无权操作这张服务卡");
            }
            $updatedata=[];
            $time=time();
            $u_tel=db('cases_chatuser')->where(['id'=>$userid])->value('tel');
            if($action=='lingqu'){  //用户进行领取操作
               //操作条件：这张卡 此时的状态必须是 未激活 未领取 且这张卡的send_id 为自己,uid不属于自己
                if($is_jh!=0 || $is_used!=0 || $is_zs==0 || $is_me){
                    $this->error("该卡片无法进行领取操作");
                }
                $updatedata=[
                    'is_used'=>1,
                    'update_time'=>$time
                ];
                
            }elseif($action=='jihuo'){
                //操作条件： 这张卡  此时的状态必须是 
                //如果是自己的卡 则是未激活，未赠送，未领取 
                 //如果是别人赠送自己的卡 则是未激活，已领取,收礼人是自己,uid不属于自己
               
               
                $stoptime= strtotime(' +'.$cardinfo['duration'].' '.$cardinfo['unit'],time());
                if($is_me){
                    if($is_jh!=0 || $is_used!=0 || $sendid!=0){
                     $this->error("该卡片无法进行激活操作");
                    }
                    
                    
                }elseif($is_zs){
                    if($is_jh!=0 || $is_used==0 || $is_zs==0 || $is_me){
                     $this->error("该卡片无法进行激活操作");
                    }
                }else{
                    $this->error("您无权操作这张服务卡");
                }
                
                $updatedata=[
                        'jh_time'=>$time,
                        'stop_time'=>$stoptime,
                        'update_time'=>$time,
                        'jh_stop_time'=>0
                    ];
            }elseif($action=='zhuanzeng'){
                //操作条件  这张卡此时状态必须是  未激活，未领取，未有赠送人 uid是自己
                if($is_jh!=0 || $is_used!=0 || $sendid!=0 || $is_me==0){
                     $this->error("该卡片无法进行转赠操作");
                    }
                if(empty($tel)){
                    $this->error("无效的手机号");
                }
                $map=[
                    'tel'=>$tel,
                    'wxopenid'=>['neq',''],
                    'delete_time'=>0
                ];
                $userinfo=db('cases_chatuser')->where($map)->find();
                
                if($userinfo['id']==$userid){
                    $this->error("不能转赠给自己");
                }
                if(empty($userinfo)){
//                     $this->error("未找到用户");
                    
                    //如果未找到用户则存储send_id为0，存储填写手机号
                    $updatedata=[
                        'send_id'=>-1,
                        'no_user_tel'=>$tel
                    ];
                }else{
                    $updatedata=[
                        'send_id'=>$userinfo['id'],
                        'update_time'=>$time
                    ];
                }
                $msg=new \message\mess();
                $tel_num=substr($u_tel,-4);
                $content='【汇医服务】尊敬的客户，您已收到尾号'.$tel_num.'赠送的汇医家庭医生随身行服务卡，请关注“汇医国际健康顾问”,点击“获取服务”，注册领取您的礼包。';
                $msg->send($tel, $content);   
                
            }elseif($action=='qxzhuanzeng'){
                //操作条件  此时这张卡的状态必须是  未激活，未领取，有赠送人，uid是自己
                
                if($is_jh!=0 || $is_used!=0 || $sendid==0 || $is_me==0){
                  $this->error("该卡片无法进行取消转赠操作");
                }
               $updatedata=[
                        'send_id'=>0,
                        'update_time'=>$time,
                        'no_user_tel'=>''
                    ];
            }
            if(empty($updatedata)){
                $this->error("非法提交");
            }
            db('cases_order_card')->where('id',$id)->update($updatedata);
            $this->success('操作成功');
        }
    }
    
    
       
        public function addcase() {
            $request= Request::instance();
            $userid= session('userid');
            $cardid=intval($request->param('cardid'));
            if(empty($cardid)){
                $this->error('请选择服务卡');
            }
            
            $cardinfo=db('cases_order_card')->where(['id'=>$cardid])->find();
            //先检测用户是否有权操作这张卡
            if(empty($cardinfo)){
                $this->error('未找到这张服务卡');
            }
            if(empty($cardinfo['status'])){
                $this->error('该卡已被禁用');
            }
            
            $carduid=$cardinfo['uid'];
            $sendid=$cardinfo['send_id'];
            $is_jh= empty($cardinfo['jh_time'])?0:1;
            $is_used=$cardinfo['is_used'];
            $typeid=$cardinfo['s_type_id'];
            $is_me=0;
            $is_zs=0;
            if($carduid==$userid){
                $is_me=1;
            }
            if($sendid==$userid){
                $is_zs=1;
            }
            if(($is_me==0 && $is_zs==0 )|| empty($userid)){
//                $this->error("这张服务卡不属于你");
                $this->redirect('/serviceIndex');
            }
            //提交case条件
            //如果是自己激活的卡片  则是 已激活  未赠送  未领取的状态 次数大于0 未过期
            //如果是别人送的  则是 已激活 赠送人是自己  已领取  次数大于0  未过期
            //先判断次数 和是否过期
            if($cardinfo['times']<=0 || (!empty($cardinfo['stop_time']) && $cardinfo['stop_time']<time()) ){
                $this->error('卡片已过期或无次数');
            }
            if($is_me){
                if(!$is_jh || $sendid!=0 || $is_used ){
                    $this->error('请检查卡片状态');
                }
            }elseif($is_zs){
                if(!$is_jh || !$is_used){
                    $this->error('请检查卡片状态');
                }
            }
            
          
              //查询用户的手机号和邮箱和昵称
            $userinfo=ChatUserModel::get($userid);
            $input_tel= isset($userinfo['tel'])?$userinfo['tel']:'';
            $input_email= isset($userinfo['email'])?$userinfo['email']:'';
            $input_nickname= isset($userinfo['nickname'])?$userinfo['nickname']:'';
            $this->assign('input_tel', $input_tel);
            $this->assign('input_email', $input_email);
            $this->assign('input_nickname', $input_nickname);
            
            if ($request->isPost()) {
                $data = [
                    'username' => str_replace(' ', '',$request->param('username')),
                    'birthday' => $request->param('birthday'),
                    'sex' => $request->param('sex'),
                    'isme' => $request->param('isme'),
                    'relationship' => $request->param('relationship'),
                    'applicant_name' => str_replace(' ', '',$request->param('applicant_name')),
                    'address' => $request->param('address'),
                    'province' => $request->param('province',110000),
                    'city' => $request->param('city',110100),
                    'district' => $request->param('district',110101),
                    'zip_code' => $request->param('zip_code'),
                    'preferred_phone' => $request->param('preferred_phone'),
                    'standby_phone' => $request->param('standby_phone'),
                    'preferred_time' => $request->param('preferred_time'),
                    'illness' => $request->param('illness'),
                    'treatment_doctor' => $request->param('treatment_doctor'),
                    'treatment_hospital' => $request->param('treatment_hospital'),
                     'specialty' => $request->param('specialty'),
                     'case_type' => $request->param('case_type'),
                    'sort' => $request->param('sort',0), 
                    'country'=>$request->param('country',1),
                    'email'=>str_replace(' ', '',$request->param('email')),
                    'e_province' => $request->param('e_province'),
                    'xinli_help'=>$request->param('xinli_help'), //问卷开始
                    'qingxu_help'=>implode(',', array_filter($request->param('qingxu_help/a',[]))),
                    'qingxu_other'=>$request->param('qingxu_other'),
                    'shanghai_help'=>$request->param('shanghai_help'),
                    'shenti_content'=>$request->param('shenti_content'),
                    'yaowu_help'=>$request->param('yaowu_help'),
                    'before_xinli'=>$request->param('before_xinli'),//问卷结束
                    'record_number' => $request->param('record_number'),  //病案号

                ];
  
                
                if($data['username']!=$input_nickname || $data['applicant_name']!=$input_nickname ){
                   $this->error('姓名无法修改');
               }

                 if(empty($data['province'])){
                   $data['province']=110000;
               }
               if(empty($data['city'])){
                   $data['city']=110100;
               }
               if(empty($data['district'])){
                   $data['district']=110101;
               }
                if($request->param('options')){
                    $file=FileModel::getInstance()->where(['file_url'=>$request->param('options')])->find();
                    if(!empty($file)){
                        $data['options']=$file['id'];
                    }
                }
                if($userid){
                    $data['userid']= $userid;
                   // 动态绑定属性
                    Request::instance()->bind('userid',$data['userid']);
                }else{
                    $msg['error']=2;
                    $msg['msg']='请登录';
                   $this->error($msg); 
                  
                }

            $case_validate=CaseValidate::getInstance();
            $result =$case_validate->scene('add')->check($data);
            $msg=[];
            if ($result!==true) {
                $msg['error']=1;
                $msg['msg']=$case_validate->getError(); 
                $this->error($msg);  
            }else{
                            // 添加
                $model = CaseModel::getInstance();
                $status = $model->save($data);

                //扣除一个次数
                //再次检查当前卡片次数，以延时导致服务卡次数为负
                $new_times=db('cases_order_card')->where(['id'=>$cardid])->value('times');
                if($new_times<=0){
                    $this->error('请检查卡片可用次数');
                }
                $up_times=$new_times;
                if($new_times!=9999){
                    $up_times=$new_times-1;
                }
                
                $up_times=$up_times<0?0:$up_times;
                $update=[
                    'times'=>$up_times
                ];
                db('cases_order_card')->where(['id'=>$cardid])->update($update);
                $msg['error']=0;
                $msg['msg']='新增成功';
                $this->success($msg);  
            }
          
          

            
            
          
         }
            
              //获取省列表
            $this->assignProvinceList();
            //获取国家列表
            $this->getCountryList();
            
             //获取语言服务列表
            $this->getCaseServiceList();
            
           
             //问卷表单
           //获取心理支持单选数组(问卷)
           $this->getXinliList();
           //获取情绪问题多选选项数组(问卷)
           $this->getQingxuList();
           //是否有伤害自己的想法单选选项数组(问卷)
           $this->getShanghaiList();
            
            if($typeid==1){
                $title='国际医学专家意见服务申请表';
            }elseif($typeid==5){
                $title='家庭医生随身行服务申请表';
            }elseif($typeid==2){
                $title='心理健康支持服务申请表';
            }else{
                $title='海外医疗安排服务申请表';
            }
            
            $this->assign('cardid', $cardid);
            $this->assign('title', $title);
            $this->assign('typeid', intval($typeid));
            
            
           
            return $this->fetch();
        }


        //获取语言服务列表
    protected function getCaseServiceList(){
         
         $logic =CaseTypeLogic::getInstance();
         $case_service_list=$logic->getSelectServiceLang(['sl_status'=>1]);
         $allarr=[1,2,3];
         foreach ($case_service_list as $key => $value) {
             if(!in_array($value['value'],$allarr)){
                 unset($case_service_list[$key]);
             }
         }
         $this->assign('case_service_list',$case_service_list);
     }
        //获取国家数组
    protected function getCountryList(){
         
         $logic =CaseTypeLogic::getInstance();
         $country_list=$logic->getSelectCountry();
         $this->assign('country_list',$country_list);
     }
         //省市区联动
     protected function assignProvinceList(){
        
    	//地区
    	$area= AreaModel::all(['parent_id'=>0]);
    	$this->assign('area',$area);

     }
     
      //获取心理支持单选数组(问卷)
    protected function getXinliList(){
         
         $logic =CaseLogic::getInstance();
         $case_manager=$logic->getXinlihelp();
         unset($case_manager[0]);
         $this->assign('xinlilist',$case_manager);
     }
      //获取情绪问题多选选项数组(问卷)
    protected function getQingxuList(){
         
         
         $logic =CaseLogic::getInstance();
         $case_manager=$logic->getQingxuhelp();
         $this->assign('qingxulist',$case_manager);
     }
      //是否有伤害自己的想法单选选项数组(问卷)
    protected function getShanghaiList(){
         
        
        
         $logic =CaseLogic::getInstance();
         $case_manager=$logic->getShanghaihelp();
         unset($case_manager[0]);
         $this->assign('shanghailist',$case_manager);
     }
     
      public function ruleservice($id) {
            
            if($id==1){
                $fetchname='one_service';
            }elseif($id==5){
                $fetchname='five_service';
            }elseif($id==2){
                $fetchname='two_service';
            }else{
                $fetchname='four_service';
            }
            
            return $this->fetch($fetchname);
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
 