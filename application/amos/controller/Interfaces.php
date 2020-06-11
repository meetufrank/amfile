<?php
namespace app\amos\controller;

use think\Config;
use think\Controller;
use think\Response;

use think\Session;
use think\Request;

class Interfaces extends Controller
{

    public function valid() {
        $file= $this->request->file('file');
        $error=$this->valid_action($file);
          $data=[];
         if(empty($error)){
             $this->success('文件符合规则');
         }else{
             $this->error($error);
         }
        
        
         
    }
    
    
    private function  valid_action($file){
         
         $file->check(['size'=>5000000,'ext'=>'png,doc,pdf']);
         $error=$file->getError();
         
         return $error;
    }
    
    public function getbasefile() {
        $file= $this->request->file('file');
        if($file){
            
      
        $error=$this->valid_action($file);
        if(!empty($error)){
            $this->error($error);
        }
        $info=$file->getInfo();
    
        $sound= base64_encode( file_get_contents( $info["tmp_name"] ) );
        
        return $sound;
        }else{
            return '';
        }
    }
}
 