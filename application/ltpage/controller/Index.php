<?php
// +----------------------------------------------------------------------
// | ichat-v3.0
// +----------------------------------------------------------------------
// | Author: NickBai <1902822973@qq.com>
// +----------------------------------------------------------------------
namespace app\ltpage\controller;

use think\Controller;
use app\laychatphone\model\ChatUser;
use app\laychatphone\model\Friends;
use app\laychatphone\model\GroupDetail;
use app\laychatphone\model\ChatGroup;
use app\laychatphone\model\Cases;
use think\Request;
use think\Db;
use app\laychatphone\model\Message;
use core\cases\logic\CaseLogic;
use core\cases\model\ChatGroupModel;
use core\cases\model\GroupDetailModel;
use core\cases\model\CaseModel;
use core\cases\model\ChatUserModel;
use core\cases\logic\ChatUserLogic;
use core\cases\model\JtModel;
use core\cases\model\CompanyModel;
use core\manage\model\UserModel;
use app\common\sendemail\SendUser;

use core\cases\validate\CaseValidate;
use core\cases\logic\CaseTypeLogic;
use core\cases\model\AreaModel;
use core\manage\model\FileModel;

class Index extends Base
{
    public function index()
    {
        $this->assign('server_name',$_SERVER['SERVER_NAME']);
        $request=Request::instance();
        
  
      $chatuser=new ChatUser;
      $friendsModle=new Friends;
      $groupdetailModle=new GroupDetail;
      

        // 查询自己的信息
        $uid = cookie('phone_user_id');
        
        $mine = $chatuser->where('id', $uid)->find();
        if(empty($mine)){
               $this->redirect(url('/ltindex'));
        }
        $this->assign('isjump', $mine['managerid']?0:1);
        
        $this->assign('ismanager', $mine['managerid']);
        if(!$mine['managerid']){ //如果是普通用户则更新群组数据
            
            //查询该用户是否拥有有效会话
            $gmap=[
                'status'=>1,
                'owner_id'=>$uid
            ];
            $gdata=db('cases_chatgroup')->where($gmap)->field('id')->select();
            $groupid=0;
            foreach (@$gdata as $key => $value) {
                $cmap=[
                    'groupid'=>$value['id']
                ];
                $count=db('cases_case')->where($cmap)->count();
                if(!$count){
                    $groupid=$value['id'];
                    
                    break;
                }
            }
            
            if(!$groupid){   //未查询到有效会话群组则新建
                $g_data=[
                    'group_name'=>$mine['nickname'].'的会话聊天',
                    'avatar'=>$mine['avatar'],
                    'owner_name'=>$mine['nickname'],
                    'owner_id'=>$uid,
                    'addtime'=>time(),
                    'status'=>1,
                    'type'=>'ltpage'
                ];
                
                $g_id=db('cases_chatgroup')->insertGetId($g_data);
                $mgdata=db('cases_chatuser')->where(['managerid'=>['neq',0],'delete_time'=>0])->select();
                $indata=[
                    [
                        'user_id'=>$mine['id'],
                        'user_name'=>$mine['nickname'],
                        'user_avatar'=>$mine['avatar'],
                        'group_id'=>$g_id,
                        'status'=>1
                    ]
                ];
                
                foreach (@$mgdata as $key => $value) {
                    $i_da=[
                        'user_id'=>$value['id'],
                        'user_name'=>$value['nickname'],
                        'user_avatar'=>$value['avatar'],
                        'group_id'=>$g_id,
                        'status'=>1
                    ];
                    $indata[]=$i_da;
                }
                if(!empty($indata)){
                    db('cases_groupdetail')->insertAll($indata);
                }
                
                $groupid=$g_id;
            }else{
                //如果有则更新群组人员，将会话后新添加的监听和casemanage加入
                $d_data=db('cases_groupdetail')->where(['group_id'=>$groupid])->field('user_id')->select();
                $im_arr=[];
                foreach (@$d_data as $key => $value) {
                    $m_id=db('cases_chatuser')->where(['id'=>$value['user_id']])->value('managerid');
                    if($m_id){
                        $im_arr[]=$m_id;
                    }
                    
                }
                if(!empty($im_arr)){
                   $d_str=@implode(',', $im_arr); 
                }else{
                   $d_str='';
                }
                
                
                $s_map2=[
                    'managerid'=>[
                       [ 'neq',0],
                       ['NOT IN',$d_str]
                        ],
                    'delete_time'=>0
                ];
                
               $c_data= db('cases_chatuser')->where($s_map2)->select();
           
               $indata=[];
               foreach (@$c_data as $key => $value) {
                   $i_da=[
                        'user_id'=>$value['id'],
                        'user_name'=>$value['nickname'],
                        'user_avatar'=>$value['avatar'],
                        'group_id'=>$groupid,
                        'status'=>1
                    ];
                    $indata[]=$i_da;
               }
               if(!empty($indata)){
                    db('cases_groupdetail')->insertAll($indata);
                }
            }
            $g_map=[
                'id'=>$groupid
            ];
            $chatdata=db('cases_chatgroup')->where($g_map)->find();
             $this->updateLog($chatdata['id']); //更新聊天记录
            $this->assign('chat_alert',$chatdata);
        }
          

          //查询当前用户的所处的群组
        $groupArr = $groupdetailModle->alias('j')->field('c.group_name groupname,c.id,c.avatar')
            ->join(['nd_cases_chatgroup'=>'c'], 'j.group_id = c.id')->where(['j.user_id'=>$uid,'j.status'=>1,'type'=>'ltpage'] )
            ->group('j.group_id')->select();

         
        
           $this->assign('mineid',$uid);
        $online = 0;
        $group = [];  //记录分组信息
    //    $userGroup = config('user_group');
        $list = [];  //群组成员信息
        $i = 0;
        $j = 0;
        //查询该用户的好友
//        $friends = $friendsModle->alias('f')->field('c.user_name,c.id,c.avatar,c.sign,c.status,f.group_id')
//            ->join(['nd_cases_chatuser'=>'c'], 'c.id = f.friend_id')
//            ->where('f.user_id', $uid)->select();
//
//        foreach( @$userGroup as $key=>$vo ){
//            $group[$i] = [
//                'groupname' => $vo,
//                'id' => $key,
//                'online' => 0,
//                'list' => []
//            ];
//            $i++;
//        }
//        unset( $userGroup );

        foreach( $group as $key=>$vo ){

            foreach( $friends as $k=>$v ) {

                if ($vo['id'] == $v['group_id']) {

                    $list[$j]['username'] = $v['user_name'];
                    $list[$j]['id'] = $v['id'];
                    $list[$j]['avatar'] = $v['avatar'];
                    $list[$j]['sign'] = $v['sign'];
                    $list[$j]['status'] = empty($v['status']) ? 'offline' : 'online';

                    if (1 == $v['status']) {
                        $online++;
                    }

                    $group[$key]['online'] = $online;
                    $group[$key]['list'] = $list;

                    $j++;
                }
            }
            $j = 0;
            $online = 0;
            unset($list);
        }

        //自定义
        // $new_group[0] = [
        //     'groupname' => '聊天室',
        //     'id' => 1,
        //     'online' => 0,
        //     'list' => []
        // ];
        // $new_group[0]['list']=$groupArr;


        //自定义结束
        //print_r($group);die;
        unset( $friends );

        $return = [
            'mine' => [
                    'username' => $mine['nickname'],
                    'id' => $mine['id'],
                    'status' => 'online',
                    'sign' => $mine['sign'],
                    'avatar' => $mine['avatar']
            ],
            'group' => $groupArr,
        ];

        //echo json_encode($return);die;

        //聊天用户
        $userInfo = [
            'id' => $mine['id'],
            'username' => $mine['nickname'],
            'avatar' => $mine['avatar'],
            'sign' => $mine['sign'],
        ];
        $this->assign([
            'userlist' => json_encode($return),
            'uinfo' => $userInfo
        ]);
        
       
        return $this->fetch();
    }


                 
        //获取当前用户有多少个未读通知
    public function getNoRead()
    {
        $message=new Message;
        if(request()->isAjax()){

            $tips = $message->where('`uid`=' . session('f_user_id') . ' and `read`=1')->count();
            return json(['code' => 1, 'data' => $tips, 'msg' => 'success']);
        }
        $this->error('非法访问');
    }
    
    
//    //邮件发送
//   public function email(){
//		
//		//调用email接口方法
//	    $emails = new \email\Cs();
//		
//		//为1请求发送邮件
//		$to = $_POST['to'];
//		
//		//邮件主题
//        $youxiangtitle="WebEx 会议邀请：text1";
//	
//		$YouxiangContent = "您好，<br/>webex meetuuu 邀请您加入以下 WebEx 会议。<br/><br/><strong>text1</strong><br/>2017年5月16日<br/>15:00  |  中国时间（北京，GMT+08:00）  |  2 小时<br/>会议号（访问码）： 182 325 056<br/>会议密码： 1234<br/>到时间后，".'<a href="https://meetuuu.webex.com.cn/meetuuu/j.php?MTID=mdafae37f1bc69b8290f5f5e3588278dd">请加入会议。</a>';
//		$emailtrue = $emails->sentemail($to,$youxiangtitle,$YouxiangContent);
//		
//	}
        
        //退出
        public function logout(){
            
            
//            cookie('phone_user_name', null);
            cookie('phone_user_id', null);
            cookie('phone_user_sign', null);
            cookie('phone_user_avatar', null);
            
            
            return json(['url'=>url('/service')]);
        }
        
        
        //发送短信
        public function sendMsg(){
//            $data=input('post.data');
//            $data=json_decode($data,true);
//            
//            $msg=new \message\mess();
//            $content=ChatUserLogic::getInstance()->getLanguage($data['mess_content'], 4);
//            $insert=[
//                'content'=>$content['content'],
//                'tel'=>$data['tel'],
//                'user_id'=>$data['mess_content']['id'],
//                'groupid'=>$data['groupid'],
//                'create_time'=>time()
//            ];
//            Db::name('cases_messlog')->insert($insert);
//            if($data){
//                $msg->send($data['tel'], $content['content']);
//            }
      
        }
        

        
        
        public function updateLog($id){
            $perPage=21; //由于layim框架的显示问题，这里需要多一条数据，用户看到的是20条数据
            $cook_id= cookie('phone_user_id');
            //查询该用户是否可以查询该群组聊天记录
            $count=db('cases_groupdetail')->where(['user_id'=>$cook_id,'group_id'=>$id])->count();
           $chatlogs=[];
            if($count){
                
            
            $field = 'from_name username,to_id id,from_avatar avatar,timeline timestamp,content,type,from_id ';
            $result = db('cases_chatlog')->field($field)->where("to_id={$id} and type='group'")
                       ->order('timestamp desc')->limit($perPage)->select();
           $result = array_reverse($result); //反转
        
            foreach ($result as $key => $value) {
                if($value['from_id']==$cook_id){
                    $result[$key]['mine']=true;
                }else{
                    $result[$key]['mine']=false;
                }
                $result[$key]['timestamp']=$result[$key]['timestamp']*1000;
            }
                $this->assign('chatlogs', json_encode($result));
              }else{
                  $this->assign('chatlogs', json_encode($chatlogs));
              }
    
        }

        
     
        

}
