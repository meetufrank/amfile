<?php
//命名空间
namespace app\medicalapi\controller;

//导入
use think\Controller;
use think\Request;
use think\Db;
use app\common\sendemail\SendUser;


class Appointment extends Controller{
    
    protected  $db;
    
    public function yuyuedemo(){
        //渲染模板输出
        return $this -> fetch();
    }
    
    //预约信息
    public function submitYuyue(){
       
        $request= Request::instance();
        
        //获取预约信息
        if($request->isPost()){
            //手机
            $phone = $request->param('phone');
            if(!empty($phone)){
                if(preg_match("/^1[34578]\d{9}$/", $phone)){
                    $data['phone'] = $phone;
                }else{
                    echo json_encode(['code' => 20001,'msg' => 'invalid code']);
                    exit;
                }
            }else{
                echo json_encode(['code' => 20001,'msg' => 'invalid code']);
                exit;
            }
            
            //邮箱
            $email = $request->param('email');
            if(!empty($email)){
                if(preg_match("/^([a-zA-Z0-9_-])+@([a-zA-Z0-9_-])+((\.[a-zA-Z0-9_-]{2,3}){1,2})$/",$email)){
                    $data['email'] = $email;
                }else{
                    echo json_encode(['code' => 20002,'msg' => 'invalid code']);
                    exit;
                }
            }else{
                echo json_encode(['code' => 20002,'msg' => 'invalid code']);
                exit;
            }
            
            
            //咨询内容简介
            $advisory_details = $request->param('advisory_details');
            if(!empty($advisory_details)){
                $data['advisory_details'] = $advisory_details;
            }else{
                echo json_encode(['code' => 20003,'msg' => 'invalid code']);
                exit;
            }
            
            
            //预约日期
            $submitdate = $request->param('submitdate');
            if(!empty($submitdate)){
                if(strpos($submitdate,'/')){
                    $data['submitdate'] = $submitdate;
                }else{
                    echo json_encode(['code' => 20006,'msg' => 'invalid code']);
                    exit;
                }
            }else{
                echo json_encode(['code' => 20006,'msg' => 'invalid code']);
                exit;
            }
            
            //预约时间段
            $time_quantum = $request->param('time_quantum');
            if(!empty($time_quantum)){
                    if(strpos($time_quantum,':')){
                             //根据时间段查找,预约时间段表id
                            $atq = Db::table('nd_appointment_time_quantum')->where("time_quantum = '".$time_quantum."'")->select();
                            if(!empty($atq)){
                                //时间段表id
                                $atqid = $atq[0]['id'];
                                $data['time_qid'] = $atqid;
                                
                            }else{
                                echo json_encode(['code' => 20007,'msg' => 'invalid code']);
                                exit;
                            }
                     }else{
                            echo json_encode(['code' => 20007,'msg' => 'invalid code']);
                            exit;
                     }
            }else{
                echo json_encode(['code' => 20007,'msg' => 'invalid code']);
                exit;
            }
            
            
            
            
        }
        
        
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
        $CreateMeeting = <<<Eof
<?xml version="1.0" encoding="UTF-8"?><serv:message xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"><header><securityContext><siteName>advance-medical</siteName><webExID>zhaojing@advance-medical.com.cn</webExID><password>1314Maggie</password></securityContext></header><body><bodyContent xsi:type="java:com.webex.service.binding.meeting.CreateMeeting"><accessControl><meetingPassword>111111</meetingPassword></accessControl><metaData><confName>Sample Meeting</confName><agenda>Test</agenda></metaData><schedule><startDate>09/6/2017 13:20:00</startDate><duration>20</duration></schedule></bodyContent></body></serv:message>
Eof;
        
        $CreateMeeting_array = curlxml($CreateMeeting);
        
        echo $meetmeetingkey = $CreateMeeting_array['servbody']['servbodyContent']['meetmeetingkey'];
        
        
        
        
        //获得主持人开会地址
        $GethosturlMeeting = <<<Eof
<?xml version="1.0" encoding="UTF-8"?><serv:message xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"><header><securityContext><siteName>advance-medical</siteName><webExID>zhaojing@advance-medical.com.cn</webExID><password>1314Maggie</password></securityContext></header><body><bodyContent xsi:type="java:com.webex.service.binding.meeting.GethosturlMeeting"><meetingKey>$meetmeetingkey</meetingKey></bodyContent></body></serv:message>
Eof;
        
        $GethosturlMeeting_array = curlxml($GethosturlMeeting);
        
        $hostMeetingURL = $GethosturlMeeting_array['servbody']['servbodyContent']['meethostMeetingURL'];
        
        $hostMeetingURLs = str_replace("https//advance-medical.webex.com.cn/","https://advance-medical.webex.com.cn/",$hostMeetingURL);
        echo $hostMeetingURLs;
        
        
        
        
        //获得加会地址
        $GetjoinurlMeeting = <<<Eof
<?xml version="1.0" encoding="UTF-8"?><serv:message xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"><header><securityContext><siteName>advance-medical</siteName><webExID>zhaojing@advance-medical.com.cn</webExID><password>1314Maggie</password></securityContext></header><body><bodyContent xsi:type="java:com.webex.service.binding.meeting.GetjoinurlMeeting"><meetingKey>$meetmeetingkey</meetingKey></bodyContent></body></serv:message>
Eof;
        
        $GetjoinurlMeeting_array = curlxml($GetjoinurlMeeting);
        
        $joinMeetingURL = $GetjoinurlMeeting_array['servbody']['servbodyContent']['meetjoinMeetingURL'];
        
        $joinMeetingURLs = str_replace("https//advance-medical.webex.com.cn/","https://advance-medical.webex.com.cn/",$joinMeetingURL);
        echo $joinMeetingURLs;
        
        
        //webex预约视频表
        
        
    
   
        //邮件
        $email = new SendUser();
        $email -> yuyueemail(1,"是主题吗","是邮件主内容吗");
  
        
        

        //添加预约信息到数据库
        Db::table('nd_appointment_info')->insert($data);
        
        
    }
    
}
?>

