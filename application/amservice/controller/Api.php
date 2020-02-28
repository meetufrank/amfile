<?php

namespace app\amservice\controller;



use app\common\zhifu\Service;
use app\common\zhifu\Wechat;
use Endroid\QrCode\QrCode;
use think\Controller;
use think\Response;
use think\Session;
use Yansongda\Pay\Pay;

use think\Db;
use think\config;
/**
 * API接口控制器
 *
 * @package addons\epay\controller
 */
class Api extends Controller
{

    protected $layout = 'default';
    protected $config = [];

    /**
     * 默认方法
     */
    public function index()
    {
        $this->error();
    }

    /**
     * 外部提交
     */
    public function submit()
    {
        $out_trade_no = $this->request->request("out_trade_no");
        $title = $this->request->request("title");
        $amount = $this->request->request('amount');
        $type = $this->request->request('type');
        $method = $this->request->request('method', 'web');
        $openid = $this->request->request('openid', '');
        $auth_code = $this->request->request('auth_code', '');
        $notifyurl = $this->request->request('notifyurl', '');
        $returnurl = $this->request->request('returnurl', '');

        if (!$amount || $amount < 0) {
            $this->error("支付金额必须大于0");
        }

        if (!$type || !in_array($type, ['alipay', 'wechat'])) {
            $this->error("支付类型错误");
        }

        $params = [
            'type'         => $type,
            'out_trade_no' => $out_trade_no,
            'title'        => $title,
            'amount'       => $amount,
            'method'       => $method,
            'openid'       => $openid,
            'auth_code'    => $auth_code,
            'notifyurl'    => $notifyurl,
            'returnurl'    => $returnurl,
        ];
        return Service::submitOrder($params);
    }

    /**
     * 微信支付
     * @return string
     */
    public function wechat()
    {
        $config = Service::getConfig('wechat');

        $isWechat = stripos($this->request->server('HTTP_USER_AGENT'), 'MicroMessenger') !== false;
        $isMobile = $this->request->isMobile();
        $this->view->assign("isWechat", $isWechat);
        $this->view->assign("isMobile", $isMobile);

        $backurl=session('nopayback');
        session('nopayback',null);
        
        $this->view->assign('backurl', $backurl);
        if ($isWechat) {
            //发起公众号(jsapi支付)
            $orderData = Session::get("wechatorderdata");
            $openid = Session::get('openid');
            //如果没有openid
            if (!$openid) {
                $wechat = new Wechat($config['wechat']['app_id'], $config['wechat']['app_secret']);
                $openid = $wechat->getOpenid();
            }

            $orderData['method'] = 'mp';
            $orderData['openid'] = $openid;
            $payData = Service::submitOrder($orderData);
            $payData = json_decode($payData, true);
            if (!isset($payData['appId'])) {
                $this->error("创建订单失败，请返回重试");
            }
            $type = 'jsapi';
            $this->view->assign("orderData", $orderData);
            $this->view->assign("payData", $payData);
            ;
        } else {
            //发起PC支付(Native支付)
            $body = $this->request->request("body");
            $code_url = $this->request->request("code_url");
            $out_trade_no = $this->request->request("out_trade_no");
            $return_url = $this->request->request("return_url");
            $total_fee = $this->request->request("total_fee");

            $sign = $this->request->request("sign");

            $data = [
                'body'         => $body,
                'code_url'     => $code_url,
                'out_trade_no' => $out_trade_no,
                'return_url'   => $return_url,
                'total_fee'    => $total_fee,
            ];
            if ($sign != md5(implode('', $data) . $config['wechat']['appid'])) {
                $this->error("签名不正确");
            }

            if ($this->request->isAjax()) {
                $pay = new Pay($config);
                $result = $pay->driver('wechat')->gateway('scan')->find($out_trade_no);
                if ($result['return_code'] == 'SUCCESS' && $result['result_code'] == 'SUCCESS') {
                    $this->success("", "", ['trade_state' => $result['trade_state']]);
                } else {
                    $this->error("查询失败");
                }
            }
            $data['sign'] = $sign;
            $type = 'pc';
            $this->view->assign("data", $data);
        }

        $this->view->assign("type", $type);
        $this->view->assign("title", "微信支付");
        return $this->view->fetch();
    }

    /**
     * 支付成功回调
     */
    public function notifyx()
    {
        
        $type = $this->request->param('type');
        $data = $GLOBALS['HTTP_RAW_POST_DATA'];
        
        $config = Service::getConfig($type);
        $pay = new Pay($config);
        $verify=$pay->driver($type)->gateway()->verify($data);
        if (!$verify) {
            echo '签名错误';
            return;
        }else{
           
            if ($verify["return_code"] == "FAIL") {
                //此处应该更新一下订单状态，商户自行增删操作
                echo 'return_code.error';
                return;
            } elseif ($verify["result_code"] == "FAIL") {
                echo 'result_code.error';
                return;
            } else {
                /*
                 * 这里写业务处理【数据库】
                 */
              //首先判断是否appid是否匹配
                if($config[$type]['app_id']== $verify['appid'] && $config[$type]['mch_id']==$verify['mch_id']){
                    //查询订单是否存在
                    $order_no=$verify['out_trade_no'];
                    $orderdb=db('cases_order_info');
                    $orderinfo=$orderdb->where(['order_no'=>$order_no])->find();
                    $time= time();
                   
                    if(!empty($orderinfo) && ($orderinfo['order_money']*100)==$verify['total_fee']){
                        $orderid=$orderinfo['id'];
                        $update=[
                            'trade_no'=>$verify['transaction_id'],
                            'state'=>1,
                            'update_time'=>$time
                        ];
                        
                        $orderdb->where(['id'=>$orderid])->update($update);
                        
                        $cardupdate=[
                            'add_time'=>$time,
                            'update_time'=>$time,
                            'status'=>1,
                            'jh_stop_time'=>$time+ config('jh_times')
                        ];
                        db('cases_order_card')->where(['orderid'=>$orderid])->update($cardupdate);
                        
                        //给用户统计成交量
                        $userid=$orderinfo['uid'];
                        $tj_id=db('cases_chatuser')->where(['id'=>$userid])->value('tj_id');
                        if(!empty($tj_id)){
                            $tj_info=db("cases_cjtj")->where(['userid'=>$tj_id])->find();
                            if(!empty($tj_info)){
                                $update=[
                                    'cj_num'=>$tj_info['cj_num']+1,
                                    'update_time'=>time(),
                                    'cj_price'=>$tj_info['cj_price']+$orderinfo['order_money']
                                ];
                                db("cases_cjtj")->where(['id'=>$tj_info['id']])->update($update);
                                $insert=[
                                    'cjid'=>$tj_info['id'],
                                    'userid'=>$tj_id,
                                    'orderid'=>$orderid,
                                    'add_time'=>time()
                                ];
                                db("cases_cjtj_info")->insert($insert);
                                
                                
                            }
                        }
                        //你可以在这里你的业务处理逻辑,比如处理你的订单状态、给会员加余额等等功能
                        //下面这句必须要执行,且在此之前不能有任何输出
                        echo 'success';
                        return;
                    }
                }
                
               echo 'error';
               return; 
//            file_put_contents('notify.txt', "收到来自微信的异步通知\r\n", FILE_APPEND);
//            file_put_contents('notify.txt', '订单号：' . $verify['out_trade_no'] . "\r\n", FILE_APPEND);
//            file_put_contents('notify.txt', '订单金额：' . $verify['total_fee'] . "\r\n\r\n", FILE_APPEND);
//            file_put_contents('notify.txt', '订单金额：' . json_encode($verify) . "\r\n\r\n", FILE_APPEND);
            
            }

        }

        
    }

    /**
     * 支付成功返回
     */
    public function returnx()
    {
        
       
        $type = $this->request->param('type');
        $data = $this->request->request('', null, 'trim');
        $config = Service::getConfig($type);
        $pay = new Pay($config);
        if ($type == 'alipay' && !$pay->driver($type)->gateway()->verify($data)) {
            echo '签名错误';
            return;
        }

        //你可以在这里定义你的提示信息,但切记不可在此编写逻辑
      //  $this->success("恭喜你！支付成功!", url("amservice/index/index"));
        $this->redirect("/userService");
        return;
    }

    /**
     * 生成二维码
     * @return Response
     */
    public function qrcode()
    {
        $text = $this->request->get('text', 'hello world');
        $size = $this->request->get('size', 250);
        $padding = $this->request->get('padding', 15);
        $errorcorrection = $this->request->get('errorcorrection', 'medium');
        $foreground = $this->request->get('foreground', "#ffffff");
        $background = $this->request->get('background', "#000000");
        $logo = $this->request->get('logo');
        $logosize = $this->request->get('logosize');
        $label = $this->request->get('label');
        $labelfontsize = $this->request->get('labelfontsize');
        $labelhalign = $this->request->get('labelhalign');
        $labelvalign = $this->request->get('labelvalign');

        // 前景色
        list($r, $g, $b) = sscanf($foreground, "#%02x%02x%02x");
        $foregroundcolor = ['r' => $r, 'g' => $g, 'b' => $b];

        // 背景色
        list($r, $g, $b) = sscanf($background, "#%02x%02x%02x");
        $backgroundcolor = ['r' => $r, 'g' => $g, 'b' => $b];

        $qrCode = new QrCode();
        $qrCode
            ->setText($text)
            ->setSize($size)
            ->setPadding($padding)
            ->setErrorCorrection($errorcorrection)
            ->setForegroundColor($foregroundcolor)
            ->setBackgroundColor($backgroundcolor)
            ->setLogoSize($logosize)
//            ->setLabelFontPath(ROOT_PATH . 'public/assets/fonts/Times New Roman.ttf')
            ->setLabel($label)
            ->setLabelFontSize($labelfontsize)
            ->setLabelHalign($labelhalign)
            ->setLabelValign($labelvalign)
            ->setImageType(QrCode::IMAGE_TYPE_PNG);
        //也可以直接使用render方法输出结果
        //$qrCode->render();
        return new Response($qrCode->get(), 200, ['Content-Type' => $qrCode->getContentType()]);
    }

}
