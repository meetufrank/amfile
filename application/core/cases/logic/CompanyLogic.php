<?php
namespace core\cases\logic;

use core\Logic;
use core\cases\model\CompanyModel;

class CompanyLogic extends Logic
{

    /*
     * 获取对应的公司类型额外字段数组
     */
    public function getMoreContent(
            $data=['birthday'=>'','policy'=>'','idtype'=>'','idnumber'=>'']
            ){
        
        //获取证件类型数组
        $idtype=$this->getIdType();
        return $arr=[
            [],
            [
              'birthday'=> [
                   'type'=>'date',
                   'title'=>'出生日期',
                   'name'=>'birthday',
                   'value'=>$data['birthday'] || ''
               ],
             'policy'=>[
                   'type'=>'text',
                   'title'=>'保单号',
                   'name'=>'policy',
                   'value'=>$data['policy'] || ''
               ],
              'idtype'=> [
                   'type'=>'select',
                   'title'=>'证件类型',
                   'name'=>'idtype',
                   'value'=>$data['idtype'] || '',
                   'list'=>$idtype
               ],
               'idnumber'=> [
                   'type'=>'text',
                   'title'=>'证件号',
                   'name'=>'idnumber',
                   'value'=>$data['idnumber'] || ''
                   
                ]
            ]
        ];
    }
    
    
    /*
     * 获取证件类型数组
     */
    public function getIdType() {
        return [
           [
               'name'=>'身份证',
               'value'=>1
           ]
        ];
    }
      /*
     * 根据id查询公司类型
     */
    public function getTypeById($id) {
        return CompanyModel::getInstance()->where(['id'=>$id])->value('type');
               
    }
  
}