<?php
namespace module\cases\controller;

use think\Controller;
use think\Db;
use core\cases\model\ChatUserModel;
use core\manage\model\UserModel;
use core\cases\logic\ChatUserLogic;
use think\Config;
use think\Request;
use app\common\sendemail\SendUser;
class Yuyue extends Base{
    
    public function index(){
        
        //查询出用户提交预约信息
        $info = Db::table('nd_appointment_info')
        ->alias('a')
        ->join('nd_appointment_time_quantum t','a.time_qid = t.id')
        ->field("a.id,a.phone,a.email,a.advisory_details,a.submitdate,a.user_name,a.appointment_state,t.time_quantum")->select();
       
        $this -> assign('info',$info); 
        
        return $this->fetch();
    }
    
    
    //修改
    public function edit(){
        //获取修改id 
        $id = $this->_id();
      
        //根据id查询出用户预约信息
        $yuyue_list = Db::table('nd_appointment_info')
        ->where('id','=',$id)
        ->select(); 
        
        //查找出
        $time_quantum = Db::table('nd_appointment_time_quantum')
        ->where('id','=',$yuyue_list[0]['time_qid'])
        ->select(); 
        //print_r($time_quantum);exit;
        
        //预约时间段
        $this -> assign('time_quantum',$time_quantum);
        
        //预约详情信息
        $this->assign('yuyue_list', $yuyue_list);
 
        return $this->fetch();
    }
    
    
    //取消预约 cancel
    public function cancel(){
        
        //获取修改id
        $id = $this->_id();
        
        Db::table('nd_appointment_info')->where('id', '=',$id)->update(['appointment_state' => '4']);
        
        $this->success('已取消当前预约',self::JUMP_REFRESH);
    }
    
    
    
       
    
    //指定医生
    public function doctor(){
        $request = Request::instance();
        $yuyueinfoid = $request->param('id');

        //查询医生
        $list=ChatUserLogic::getInstance()->getCasemanager();
     
        //预约id
        $this -> assign('yuyueinfoid',$yuyueinfoid);
        $this->wjpage($list);
    
        return $this -> fetch();
    }
    
    
    //创建会议发送医生和用户邮件
    public function doctormeeting(){
        
        $request = Request::instance();
        $yuyueinfoid = $request->param('yuyueid');
        
        //获取用户提交预约信息
        //2017-09-25     09/22/2017
        $yuyueinfo =  Db::table('nd_appointment_info')->where('id = '.$yuyueinfoid)->select();

        
        //预约时间段
        $time_quantumselect =  Db::table('nd_appointment_time_quantum')->where('id = '.$yuyueinfo[0]['time_qid'])->select();
        $time_quantum = $time_quantumselect[0]['time_quantum'];
        

        $time =strtotime($yuyueinfo[0]['submitdate'].' '.$time_quantum);
        $times = date('m/d/Y H:i:s',$time);
        //print_r($times);exit;
        
        //预约日期
        $submitdate = $yuyueinfo[0]['submitdate'];
      

        //添加会议，获取会议信息
        //对url的xml参数进行culr获取
        function curlxml($strxml){
            
            //对url的xml参数进行加密
            $urlencode_strxml = urlencode("$strxml");
            
            $curlobj = curl_init();			// 初始化
            curl_setopt($curlobj, CURLOPT_URL, "https://meetuuu.webex.com.cn/WBXService/xml8.0.0/XMLService?XML=".$urlencode_strxml);		// 设置访问网页的URL
            curl_setopt($curlobj, CURLOPT_RETURNTRANSFER, true);			// 执行之后不直接打印出来
            
            // 设置HTTPS支持
            date_default_timezone_set('PRC'); // 使用Cookie时，必须先设置时区
            curl_setopt($curlobj, CURLOPT_SSL_VERIFYPEER, 0); // 对认证证书来源的检查从证书中检查SSL加密算法是否存在
            curl_setopt($curlobj, CURLOPT_SSL_VERIFYHOST, 2);
            
            $output=curl_exec($curlobj);	// 执行
            curl_close($curlobj);			// 关闭cURL
            //echo $output;
            $outputs = str_replace(":","",$output);
            
            $ob= simplexml_load_string($outputs);//将字符串转化为变量
            $json = json_encode($ob);//将对象转化为JSON格式的字符串
            $configData = json_decode($json, true);//将JSON格式的字符串转化为数组
            return $configData;
            //print_r($configData);
            
        }
        
        
        //创建会议
        //会议主题
        $Meeting_Topic = "advance-medical预约会议";
        //会议密码
        $meetingPassword = 123456;
           $CreateMeeting = <<<Eof
<?xml version="1.0" encoding="UTF-8"?><serv:message xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"><header><securityContext><siteName>advance-medical</siteName><webExID>zhaojing@advance-medical.com.cn</webExID><password>1314Maggie</password></securityContext></header><body><bodyContent xsi:type="java:com.webex.service.binding.meeting.CreateMeeting"><accessControl><meetingPassword>$meetingPassword</meetingPassword></accessControl><metaData><confName>Sample Meeting</confName><agenda>$Meeting_Topic</agenda></metaData><schedule><startDate>$times</startDate><duration>20</duration></schedule></bodyContent></body></serv:message>
Eof;

 

      
    
        $CreateMeeting_array = curlxml($CreateMeeting);
        
       $meetmeetingkey = $CreateMeeting_array['servbody']['servbodyContent']['meetmeetingkey'];
        
        $data['meetmeetingkey'] = $meetmeetingkey;
        
        
        //获得主持人开会地址
        $GethosturlMeeting = <<<Eof
<?xml version="1.0" encoding="UTF-8"?><serv:message xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"><header><securityContext><siteName>advance-medical</siteName><webExID>zhaojing@advance-medical.com.cn</webExID><password>1314Maggie</password></securityContext></header><body><bodyContent xsi:type="java:com.webex.service.binding.meeting.GethosturlMeeting"><meetingKey>$meetmeetingkey</meetingKey></bodyContent></body></serv:message>
Eof;
        
        $GethosturlMeeting_array = curlxml($GethosturlMeeting);
        
        $hostMeetingURL = $GethosturlMeeting_array['servbody']['servbodyContent']['meethostMeetingURL'];
        
        $hostMeetingURLs = str_replace("https//advance-medical.webex.com.cn/","https://advance-medical.webex.com.cn/",$hostMeetingURL);
        //echo $hostMeetingURLs;
        $data['hostmeetingurl'] = $hostMeetingURLs;
        
        
        
        //获得加会地址
        $GetjoinurlMeeting = <<<Eof
<?xml version="1.0" encoding="UTF-8"?><serv:message xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"><header><securityContext><siteName>advance-medical</siteName><webExID>zhaojing@advance-medical.com.cn</webExID><password>1314Maggie</password></securityContext></header><body><bodyContent xsi:type="java:com.webex.service.binding.meeting.GetjoinurlMeeting"><meetingKey>$meetmeetingkey</meetingKey></bodyContent></body></serv:message>
Eof;
        
        $GetjoinurlMeeting_array = curlxml($GetjoinurlMeeting);
        
        $joinMeetingURL = $GetjoinurlMeeting_array['servbody']['servbodyContent']['meetjoinMeetingURL'];
        
        $joinMeetingURLs = str_replace("https//advance-medical.webex.com.cn/","https://advance-medical.webex.com.cn/",$joinMeetingURL);
        //echo $joinMeetingURLs;
        $data['joinmeetingurl'] = $joinMeetingURLs;
        
        //添加视频会议信息
        Db::table('nd_appointment_meeting')->insert($data);
        
        
        //print_r($yuyueinfo[0]['user_name']);exit;
       
        //邮件
        $email = new SendUser();
        //开会人
        $GetjoinurlMeeting_Theme = "advance-medical预约会议邀请:".$yuyueinfo[0]['user_name'];
        $GetjoinurlMeeting_Body = "您好，<br/>advance-medical 邀请您加入以下预约会议。<br/><br/><strong>$Meeting_Topic</strong><br/>$submitdate<br/>$time_quantum | 中国时间（北京，GMT+08:00） | 2 小时<br/>会议密码： $meetingPassword<br/>".'<a href="'.$joinMeetingURLs.'">到时间后，请加入会议。</a>';
        $email -> yuyueemail(1,$GetjoinurlMeeting_Theme,$GetjoinurlMeeting_Body,'jiang.wang@meetuuu.com');
        
        
        
        //主持人
        $GethosturlMeeting_Theme = "advance-medical预约会议邀请:".$yuyueinfo[0]['user_name'];
        $GethosturlMeeting_Body = "您好，<br/>advance-medical 邀请您主持以下预约会议。<br/><br/><strong>$Meeting_Topic</strong><br/>$submitdate<br/>$time_quantum | 中国时间（北京，GMT+08:00） | 2 小时<br/>会议密码： $meetingPassword<br/>".'<a href="'.$hostMeetingURLs.'">到时间后，请主持会议。</a>';
        $email -> yuyueemail(1,$GethosturlMeeting_Theme,$GethosturlMeeting_Body,'j.wang@meetuuu.com');
        
        
        //修改预约状态
        Db::table('nd_appointment_info')->where('id', $yuyueinfoid)->update(['appointment_state' => 2]);
        
        
    }
  
    
    /**
     * 分页列表
     *
     * @param Model $model
     * @param integer $rowNum
     * @param Closure $perform
     * @return void
     */
    protected function wjpage($model, $rowNum = null, \Closure $perform = null)
    {
        $rowNum || $rowNum = Config::get('manage_row_num');
        $rowNum || $rowNum = 10;
        
        $model = $this->buildModel($model);
        
        $list = $model->paginate($rowNum);
        $perform && $perform($list);
        
        //print_r($list);exit;
        $this->assign('_list', $list);
        $this->assign('_page', $list->render());
        $this->assign('_total', $list->total());
    }

}
?>