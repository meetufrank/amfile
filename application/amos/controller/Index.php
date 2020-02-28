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
       $this->siteTitle='咨询列表';
       if($this->request->isPost()){
           try {
               
               $data=[
               'files'=>false
              ];
            $result= AmosLogic::getInstance()->zxlist($data);
            if($result['status']!='ok'){
                $this->error($result['data']['message']);
            }
            $rdata=$result['data'];
            $list=$rdata['videocalls'];
            $this->assign('list', $list);
           $data['content']=$this->fetch('index/library/zxlist',[],['__AMOS__'=>'/static/amos']);
           
           
           } catch (Exception $ex) {
               $this->error($ex->getMessage());
           }
           
           
           $this->success('获取成功','',$data);
       }
        
        return $this->fetch('',[],['__AMOS__'=>'/static/amos']);
        
        
    }
    
    
    public function familylist() {
        
         $this->siteTitle='家属列表';
         $selectid=  $this->request->param('id');
        $this->assign('selectid', $selectid);
       if($this->request->isPost()){
           try {
            
            $result= AmosLogic::getInstance()->famlist([]);
            
            if($result['status']!='ok'){
                $this->error($result['data']['message']);
            }
       
            $rdata=$result['data'];
            $list=$rdata['dependents'];
            $this->assign('list', $list);
           $data['content']=$this->fetch('index/library/family_library',[],['__AMOS__'=>'/static/amos']);
           
           
           } catch (Exception $ex) {
               $this->error($ex->getMessage());
           }
           
           
           $this->success('获取成功','',$data);
       }
        return $this->fetch('',[],['__AMOS__'=>'/static/amos']);
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
 