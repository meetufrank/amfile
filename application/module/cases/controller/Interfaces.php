<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace module\cases\controller;

use think\Controller;
use think\Request;
use core\cases\model\AreaModel;
class Interfaces extends Controller
{
             
    public function getCity(Request $request){
        $model =AreaModel::getInstance();
    	$ParentId=$request->param('ParentId');
        $where='parent_id='.$ParentId;      
    	$current_city=$model->getlist($where);
    	$data['data']=$current_city;
        return json($data);
    	
    }
    
    public function getDistrict(Request $request){
    	$model =AreaModel::getInstance();
    	$ParentId=$request->param('ParentId');
        $where='parent_id='.$ParentId;
    	$current_county=$model->getlist($where);
    	$data['data']=$current_county;
        
    	 return json($data);
    }
}