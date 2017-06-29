<?php
// +----------------------------------------------------------------------
// | ichat-v3.0
// +----------------------------------------------------------------------
// | Author: NickBai <1902822973@qq.com>
// +----------------------------------------------------------------------
namespace app\laychatphone\controller;

use think\Controller;
use app\laychatphone\model\ChatUser;
use app\laychatphone\model\Friends;
use app\laychatphone\model\GroupDetail;
use app\laychatphone\model\ChatGroup;
use app\laychatphone\model\Cases;
use think\Request;
use app\laychatphone\model\Message;

class Phone extends Base
{
    public function index()
    {
        
        //聊天用户
        $userInfo = [
            'id' => cookie('phone_user_id'),
            'username' => cookie('phone_user_name'),
            'avatar' => cookie('phone_user_avatar'),
            'sign' => cookie('phone_user_sign'),
        ];
      $chatuser=new ChatUser;
      $friendsModle=new Friends;
      $groupdetailModle=new GroupDetail;
      

        // 查询自己的信息
        $uid = cookie('phone_user_id');
        $mine = $chatuser->where('id', $uid)->find();
       
            
           $chatdata= db('cases_case')->alias('c')->field('vg.group_name,vg.id,vg.avatar')->join(['nd_cases_chatgroup'=>'vg'],'c.groupid=vg.id')->where('c.id',1)->find();
 
          //查询当前用户的所处的群组
        $groupArr = $groupdetailModle->alias('j')->field('c.group_name groupname,c.id,c.avatar')
            ->join(['nd_cases_chatgroup'=>'c'], 'j.group_id = c.id')->where('j.user_id', $uid)
            ->group('j.group_id')->select();

            $this->updateLog($chatdata['id']); //更新聊天记录
           $this->assign('chat_alert',$chatdata);
           $this->assign('mineid',$uid);
        $online = 0;
        $group = [];  //记录分组信息
        $userGroup = config('user_group');
        $list = [];  //群组成员信息
        $i = 0;
        $j = 0;
        //查询该用户的好友
        $friends = $friendsModle->alias('f')->field('c.user_name,c.id,c.avatar,c.sign,c.status,f.group_id')
            ->join(['nd_cases_chatuser'=>'c'], 'c.id = f.friend_id')
            ->where('f.user_id', $uid)->select();

        foreach( $userGroup as $key=>$vo ){
            $group[$i] = [
                'groupname' => $vo,
                'id' => $key,
                'online' => 0,
                'list' => []
            ];
            $i++;
        }
        unset( $userGroup );

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
                    'username' => $mine['user_name'],
                    'id' => $mine['id'],
                    'status' => 'online',
                    'sign' => $mine['sign'],
                    'avatar' => $mine['avatar']
            ],
            'group' => $groupArr,
        ];

        //echo json_encode($return);die;

        $this->assign([
            'userlist' => json_encode($return),
            'uinfo' => $userInfo
        ]);
        
       
        return $this->fetch();
    }
    //手机端case详情
    public function case_content($id=1){
        $action_data=$this->getIdentity();
         $this->assign('is_jt',$action_data['is_jt']);
         $this->assign('action_data',$action_data);
        if($id){;
            $casemodel=new Cases;
             $chatuser=new ChatUser;
            $arr=['nd_cases_case.id'=>$id];
            $case_content=$casemodel->getList($arr);  //获取单条case
            $case_content=$case_content[0];
            $manager_content = $chatuser->where('managerid', $case_content['case_manager'])->find();
            $this->assign('manager_content',$manager_content);
            $this->assign('case_content',$case_content);
        }
        

         return $this->fetch();
    }
    private function getperson(){
         $chatuser=new ChatUser;
                // 查询自己的信息
        $uid = cookie('phone_user_id');
        $mine = $chatuser->where('id', $uid)->find();
        $this->assign('is_manager',$mine['is_manager']);
        return $mine;
    }
    //检测登录者身份
    private function getIdentity(){

        $mine= $this->getperson();
        $is_jt=0;
        if($mine['is_manager']){
            
            
            $jt_where=['id'=>$mine['managerid'],'user_gid'=>3];
            $is_jt=db("manage_user")->where($jt_where)->count();
            
            if(!$is_jt){
                $arr=['case_manager'=>$mine['managerid']];
                $action='立即帮助';
            }else{
                $arr=[];
                $action='立即干预';
            }
            
        }else{
            $arr=['userid'=>$mine['id']];
            $action='立即咨询';
        }
       return $getarr=[
            'is_jt'=>$is_jt,
            'action'=>$action,
            'where'=>$arr,
           'mine'=>$mine
        ];
    }
    //验证该case是否属于该case_manager
    private function validate_manager($id){
            $mine= $this->getperson();
              $casemodel=new Cases;
              $caseid=$casemodel->where('id',$id)->value('case_manager');
              if($mine['managerid']==$caseid){
                  return true;
              }else{
                  return false;
              }
              
    }
    //修改case状态
  public function update_status(){
       $casemodel=new Cases;
      $id=input('post.id');
      $status=input('post.status');

      if($this->validate_manager($id)){
          
     
      if(!$status){
          $casemodel->where('id',$id)->setField(['case_manager'=>0,'case_status'=>1,'pended'=>1]);
           $data['msg']='确认成功';
          $data['url']=url("Phone/case_list");
      }else{
          $casemodel->where('id',$id)->setField(['case_status'=>5]);
          $data['msg']='确认成功';
          $data['url']=url("Phone/case_content");
      }
      
       }else{
           $data['msg']='这不是你的case';
          
       }
       
       return json($data);
  }
    //手机端case列表
    public function case_list(){
        
        
       $data= $this->getIdentity();
 
 
        $casemodel=new Cases;
  
        if($data['mine']['is_manager']){
 
            if(!$data['is_jt']){
                $arr=['case_manager'=>$data['mine']['managerid']];
            }else{
                $arr=[];
            }
            $case_list=$casemodel->getList($arr);  //获取case列表
        }else{
            $arr=['userid'=>$data['mine']['id']];
            $case_list=$casemodel->getList($arr);  //获取case列表
        }

        $this->assign('action',$data['action']);
        $this->assign('is_jt',$data['is_jt']);
        $this->assign('is_manager',$data['mine']['is_manager']);
        $this->assign('case_list',$case_list);
        $this->assign('userdata',$data['mine']);
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
    
    
    //邮件发送
   public function email(){
		
		//调用email接口方法
	    $emails = new \email\Cs();
		
		//为1请求发送邮件
		$to = $_POST['to'];
		
		//邮件主题
        $youxiangtitle="WebEx 会议邀请：text1";
	
		$YouxiangContent = "您好，<br/>webex meetuuu 邀请您加入以下 WebEx 会议。<br/><br/><strong>text1</strong><br/>2017年5月16日<br/>15:00  |  中国时间（北京，GMT+08:00）  |  2 小时<br/>会议号（访问码）： 182 325 056<br/>会议密码： 1234<br/>到时间后，".'<a href="https://meetuuu.webex.com.cn/meetuuu/j.php?MTID=mdafae37f1bc69b8290f5f5e3588278dd">请加入会议。</a>';
		$emailtrue = $emails->sentemail($to,$youxiangtitle,$YouxiangContent);
		
	}
        
        //退出
        public function logout(){
            
            
            cookie('phone_user_name', null);
            cookie('phone_user_id', null);
            cookie('phone_user_sign', null);
            cookie('phone_user_avatar', null);
            
            
            return json(['url'=>url('Login/index')]);
        }
        
        
        //发送短信
        public function sendMsg(){
            $data=input('post.data');
            $data=json_decode($data,true);
            $msg=new \message\mess();
            if($data){
                $msg->send($data['tel'], $data['mess_content']);
            }
      
        }
        

        
        public function updateLog($id){
            $perPage=21; //由于layim框架的显示问题，这里需要多一条数据，用户看到的是20条数据
            $cook_id= cookie('phone_user_id');
            //查询该用户是否可以查询该群组聊天记录
            $count=db('cases_groupdetail')->where(['user_id'=>$cook_id,'group_id'=>$id])->count();
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
              }
    
        }

        
        


}
