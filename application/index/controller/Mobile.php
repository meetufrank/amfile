<?php
namespace app\index\controller;

use think\Config;
use cms\Controller;
use cms\Response;
use app\manage\service\ViewService;
use app\common\App;
use org\wechat\Jssdk;
class Mobile extends Controller
{
    
    /**
     * 网站标题
     *
     * @var unknown
     */
    protected $siteTitle;

    /**
     * 首页
     *
     * @return string
     */
    public function index($id='one')
    {
        $data=[
            'one'=>'独立医学专家意见',
            'two'=>'家庭医生随身行',
            'three'=>'医疗资源安排',
            'four'=>'慢性病管理',
            'five'=>'身心健康支持',
            'six'=>'医疗专家简介',
            'seven'=>'公司简介',
            
        ];
        $say=[
            'one'=>'医学专家意见（EMO）由我们安排给您的职业家庭医生悉心匹配，让您足不出户享受到全国50，000多名顶尖专家的权威意见，使医疗决策变得轻松以及安心。',
            'two'=>'感觉不舒服却没有时间看医生? 孩子发了疹子, 不知如何是好？动动手指, 全天候家庭医生咨询服触手可及(适用非紧急健康问题)。',
            'three'=>'通过医疗资源安排服务，汇医将根据您的状况专门为您匹配权威治疗专家和医疗机构。',
            'four'=>'慢性疾病是很有挑战性的，但是有了合适的帮助，您可以更好地控制和管理这些挑战。',
            'five'=>'身心健康是整体健康的重要组成部分。如果您在应对焦虑、压力或抑郁方面遇到困难，汇医医疗可为您提供私密贴心的心理支持。',
            'six'=>'我们的专家网络涵盖全球450多个子专科的50000多名权威专科专家，可应对各种常见疾病或疑难杂症。',
            'seven'=>'Advance Medical目前隶属于全球线上医疗服务领导者Teladoc Health，是唯一一家有能力在全球范围提供全面远程医疗解决方案的服务商，其业务涵盖远程医疗、专家意见和授权平台服务。Teladoc Health为全球领先的保险公司、企业和医疗机构提供服务，并在全球各地帮助人们得到安心放心的医疗服务。',
            
        ];
        
        //banner
        $banner=[
            'one'=>'http://meetuuu.oss-cn-shanghai.aliyuncs.com/zhaoqilong/advancePhone/one.png',
            'two'=>'http://meetuuu.oss-cn-shanghai.aliyuncs.com/zhaoqilong/advancePhone/two.png',
            'three'=>'http://meetuuu.oss-cn-shanghai.aliyuncs.com/zhaoqilong/advancePhone/three.png',
            'four'=>'http://meetuuu.oss-cn-shanghai.aliyuncs.com/zhaoqilong/advancePhone/four.png',
            'five'=>'http://meetuuu.oss-cn-shanghai.aliyuncs.com/zhaoqilong/advancePhone/five.png',
            'six'=>'http://meetuuu.oss-cn-shanghai.aliyuncs.com/zhaoqilong/advancePhone/six.png',
            'seven'=>'http://meetuuu.oss-cn-shanghai.aliyuncs.com/zhaoqilong/advancePhone/seven.png',
        ];
        if(isset($data[$id])&&isset($say[$id])&&isset($banner[$id])){
           $this->siteTitle=$data[$id];
           $desc=$say[$id];
           $imgurl=$banner[$id];
            //分享二维码
         
        $jssdk = new Jssdk(config('wechat_appid'), config('wechat_appsecret'));
        $signPackage = $jssdk->GetSignPackage();
        //print_r($signPackage);exit;
        $this->assign('signPackage', $signPackage);
        $this->assign('desc', $desc);
        $this->assign('drumpurl', request()->url(true)); //当前链接
        $this->assign('imgurl', $imgurl);
        
        
        $this->assign('idcss', $id);
           return $this->fetch('child_'.$id); 
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
 