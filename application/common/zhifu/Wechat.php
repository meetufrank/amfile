<?php

namespace app\common\zhifu;

use http\Http;
use think\Cache;
use think\Session;
use think\Request;
/**
 * 微信授权
 *
 */
class Wechat
{
    private $app_id = '';
    private $app_secret = '';
    private $scope = 'snsapi_userinfo';

    public function __construct($app_id, $app_secret)
    {
        $this->app_id = $app_id;
        $this->app_secret = $app_secret;
    }

    /**
     * 获取微信授权链接
     *
     * @return string
     */
    public function getAuthorizeUrl()
    {
        $redirect_uri = Request::instance()->url(true);
        $redirect_uri = urlencode($redirect_uri);
        $state = time();
        Session::set('state', $state);
        return "https://open.weixin.qq.com/connect/oauth2/authorize?appid={$this->app_id}&redirect_uri={$redirect_uri}&response_type=code&scope={$this->scope}&state={$state}#wechat_redirect";
    }

    /**
     * 获取微信openid
     *
     * @return mixed|string
     */
    public function getOpenid()
    {
        $openid = Session::get('openid');
        if (!$openid) {
            if (!isset($_GET['code'])) {
                $url = $this->getAuthorizeUrl();

                Header("Location: $url");
                exit();
            } else {
                $state = Session::get('state');
                if ($state == $_GET['state']) {
                    $code = $_GET['code'];
                    $token = $this->getAccessToken($code);
                    $openid = isset($token['openid']) ? $token['openid'] : '';
                    if ($openid) {
                        Session::set("openid", $openid);
                    }
                }
            }
        }
        return $openid;
    }

     /**
     * 获取微信openid并且获取微信用户信息
     *
     * @return mixed|string
     */
    public function getUserInfo()
    {
        $openid = Session::get('openid');
        if (!$openid) {
            if (!isset($_GET['code'])) {
                $url = $this->getAuthorizeUrl();

                Header("Location: $url");
                exit();
            } else {
                $state = Session::get('state');
                if ($state == $_GET['state']) {
                    $code = $_GET['code'];
                    $token = $this->getAccessToken($code);
                    $openid = isset($token['openid']) ? $token['openid'] : '';
                    $access_token = isset($token['access_token']) ? $token['access_token'] : '';
                   
                    if ($openid) {
                        Session::set("openid", $openid);
                        
                       $userinfo=$this->getWxUser($access_token,$openid); 
                       
                         return $userinfo;
                    }
                    
                    
                }
            }
        }
        return [
            'openid'=>$openid
        ];
    }
    
    /*
     * 获取userinfo
     */
     public function getWxUser($access_token,$openid)
    {
        $params = [
            'access_token'      => $access_token,
            'openid'     => $openid,
            'lang'       => 'zh_CN'
        ];
        $ret = Http::sendRequest('https://api.weixin.qq.com/sns/userinfo', $params, 'GET');
       
        if ($ret['ret']) {
            $ar = json_decode($ret['msg'], true);
            return $ar;
        }
        return [
            'openid'=>$openid
        ];
    }
    
    /**
     * 获取授权token网页授权
     *
     * @param string $code
     * @return mixed|string
     */
    public function getAccessToken($code = '')
    {
        $params = [
            'appid'      => $this->app_id,
            'secret'     => $this->app_secret,
            'code'       => $code,
            'grant_type' => 'authorization_code'
        ];
        $ret = Http::sendRequest('https://api.weixin.qq.com/sns/oauth2/access_token', $params, 'GET');
        if ($ret['ret']) {
            $ar = json_decode($ret['msg'], true);
            return $ar;
        }
        return [];
    }

    public function getJsticket()
    {
        $jsticket = Session::get('jsticket');
        if (!$jsticket) {
            $token = $this->getAccessToken($code);
            $params = [
                'access_token' => 'token',
                'type'         => 'jsapi',
            ];
            $ret = Http::sendRequest('https://api.weixin.qq.com/cgi-bin/ticket/getticket', $params, 'GET');
            if ($ret['ret']) {
                $ar = json_decode($ret['msg'], true);
                return $ar;
            }
        }
        return $jsticket;
    }
}
