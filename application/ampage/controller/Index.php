<?php
namespace app\ampage\controller;

use think\Config;
use cms\Controller;
use cms\Response;
use app\manage\service\ViewService;
use app\common\App;
use think\Request;

class Index extends Controller
{
    /**
     * 网站标题
     *
     * @var unknown
     */
    protected $siteTitle;
    
   
    public function two() {
        $this->siteTitle='专家意见';
        
        return $this->fetch();
    }
    public function three() {
        $this->siteTitle='FAQ';
        
        return $this->fetch();
    }
    public function four() {
        $this->siteTitle='国际转诊';
        
        return $this->fetch();
    }
    public function five() {
        $this->siteTitle='汇医专家';
        
        return $this->fetch();
    }
    
    public function index() {
        $this->siteTitle='身心健康';
        
        return $this->fetch('header');
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
 