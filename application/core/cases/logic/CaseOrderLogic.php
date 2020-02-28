<?php
namespace core\cases\logic;

use core\Logic;
use think\Db;



class CaseOrderLogic extends Logic
{
    
    protected $db='cases_order_info';


    //生成唯一订单号
    public function build_order_no() {
        
        return date('Ymd').substr(implode(NULL, array_map('ord', str_split(substr(uniqid(), 7, 13), 1))), 0, 8);
    }
    
    
  
  
    
    
}