<?php
namespace module\cases\controller;

use think\Request;
use think\Session;
use core\cases\model\CaseModel;
use core\cases\model\CaseTypeModel;
use core\cases\logic\CaseTypeLogic;
use core\cases\model\CountryModel;
use core\cases\model\AreaModel;
use core\manage\logic\UserLogic;
use core\manage\model\UserModel;
use core\cases\logic\ChatUserLogic;
use core\cases\validate\CaseValidate;
class CaseList extends Base
{

    /**
     * case列表
     *
     * @param Request $request            
     * @return string
     */
    public function index(Request $request)
    {
        
        $this->siteTitle = 'case列表';
        //获取case表的别名
        $case_alias=CaseModel::getInstance()->alias_name;
        // case列表
        $map = [
            $case_alias.'.delete_time' => 0
        ];
      
        $this->assignCaseList($map);
        return $this->fetch();
    }

/**
     * 赋值case列表
     *
     * @param array $map            
     *
     * @return void
     */
    protected function assignCaseList($map)
    {
        $request = Request::instance();
        //获取case表的别名
        $case_alias=CaseModel::getInstance()->alias_name;
        // 查询国家
        $cate = $request->param('country');
        if (! empty($cate)) {
            $cate = intval($cate);
            $map[$case_alias.'.country'] = $cate;
        }
        $this->assign('country', $cate);
        
        // 查询条件-状态
        $status = $request->param('status', '');
        if ($status != '') {
            $status = intval($status);
            $map[$case_alias.'.case_status'] = $status;
        }
        $this->assign('status', $status);
        
        // 查询条件-类型
        $casetype = $request->param('casetype','');
        if (!empty($casetype)) {
            $map[$case_alias.'.case_type'] = intval($casetype);
        }
        $this->assign('casetype',intval($casetype));
        
        // 查询条件-关键词
        $keyword = $request->param('keyword');
        if ($keyword != '') {
            $map[$case_alias.'.username'] = [
                'like',
                '%' . $keyword . '%'
            ];
        }
        $this->assign('keyword', $keyword);
        
        // 分页列表
        $model = CaseModel::getInstance();
        $case_list=$model->getCaseList($map);

        $this->_page($case_list);
        
        
        $this->getTypeList();
        
        $this->getStatusList();
        
        $this->getCountryList();
        
        $this->getManagerList();
        
    }


    //获取类型数组
     protected function getTypeList(){
         
         $logic =CaseTypeLogic::getInstance();
         $typelist=$logic->getSelectType();

         $this->assign('typelist',$typelist);
     }
    
     

  //获取状态数组
    protected function getStatusList(){
         
         $logic =CaseTypeLogic::getInstance();
         $status_list=$logic->getSelectStatus();
         $this->assign('status_list',$status_list);
     }
     
       //获取国家数组
    protected function getCountryList(){
         
         $logic =CaseTypeLogic::getInstance();
         $country_list=$logic->getSelectCountry();
         $this->assign('country_list',$country_list);
     }
       //获取casemanage数组
    protected function getManagerList(){
         
         $logic =UserLogic::getInstance();
         $where=[
             'user_gid'=>2
         ];
         $case_manager=$logic->getSelectList($where);
         $case_manager[0]=[
             'name'=>'无',
             'value'=>0
             ];
        ksort($case_manager);  //排序
         $this->assign('case_manager',$case_manager);
     }
            //获取casemanage数组
    protected function getUserList(){
         
         $logic = ChatUserLogic::getInstance();
         $where=[
             'is_manager'=>0,
             'managerid'=>0
         ];
         $chatuser=$logic->getSelectUser($where);

         $this->assign('chatuser',$chatuser);
     }
       //获取性别数组
    protected function getSexList(){
         
         $logic =CaseTypeLogic::getInstance();
         $case_manager=$logic->getSelectSex();
         $this->assign('sexlist',$case_manager);
     }
       //获取是否数组
    protected function getIsList(){
         
         $logic =CaseTypeLogic::getInstance();
         $case_manager=$logic->getSelectIs();
         $this->assign('islist',$case_manager);
     }
     
     //省市区联动
     protected function assignProvinceList(){
        
    	//地区
    	$area= AreaModel::all(['parent_id'=>0]);
    	$this->assign('area',$area);

     }
     

     /**
     * 更改case
     *
     * @param Request $request            
     * @return mixed
     */
    public function modify(Request $request)
    {
        $fields = [
            'sort',
            'case_status',
            'case_manager'
        ];
        $this->_modify(CaseModel::class, $fields);
    }
 
        /**
     * 添加case
     *
     * @param Request $request            
     * @return string
     */
    public function add(Request $request)
    {
        if ($request->isPost()) {
            $data = [
                'username' => $request->param('username'),
                'birthday' => $request->param('birthday'),
                'sex' => $request->param('sex'),
                'isme' => $request->param('isme'),
                'relationship' => $request->param('relationship'),
                'applicant_name' => $request->param('applicant_name'),
                'address' => $request->param('address'),
                'province' => $request->param('province', 110000),
                'city' => $request->param('city', 110100),
                'district' => $request->param('district', 110101),
                'zip_code' => $request->param('zip_code'),
                'preferred_phone' => $request->param('preferred_phone'),
                'standby_phone' => $request->param('standby_phone'),
                'preferred_time' => $request->param('preferred_time'),
                'illness' => $request->param('illness'),
                'treatment_doctor' => $request->param('treatment_doctor'),
                'treatment_hospital' => $request->param('treatment_hospital'),
                 'specialty' => $request->param('specialty'),
                 'case_type' => $request->param('case_type'),
                 'case_manager' => $request->param('case_manager'),
                'case_note' => $request->param('case_note'),
                'sort' => $request->param('sort',0),
                'userid' => $request->param('userid'),
                'country'=>$request->param('country'),
                'email'=>$request->param('email')
            ];
          
                        // 验证
            $this->_validate(CaseValidate::class, $data, 'add');
            
            // 添加
            $model = CaseModel::getInstance();
            $status = $model->save($data);
            $this->success('新增成功', self::JUMP_REFERER);
        } else {
            $this->siteTitle = '新增case';
           
            
           //性别
            $this->getSexList();
            
          //是否
            $this->getIsList();
            
            //获取省列表
            $this->assignProvinceList();
            //获取国家列表
            $this->getCountryList();
          
     
        
            //获取服务类型列表
        $this->getTypeList();
            //获取状态类型列表
        //$this->getStatusList();
            //获取case_manager列表
        $this->getManagerList();
                    //获取用户列表
        $this->getUserList();
            return $this->fetch();
        }
    }
    
    
       /**
     * 编辑case
     *
     * @param Request $request            
     * @return mixed
     */
    public function edit(Request $request)
    {
        $caseid=$this->_id();
        if ($request->isPost()) {
            $data = [
                'username' => $request->param('username'),
                'birthday' => $request->param('birthday'),
                'sex' => $request->param('sex'),
                'isme' => $request->param('isme'),
                'relationship' => $request->param('relationship'),
                'applicant_name' => $request->param('applicant_name'),
                'address' => $request->param('address'),
                'province' => $request->param('province', 110000),
                'city' => $request->param('city', 110100),
                'district' => $request->param('district', 110101),
                'zip_code' => $request->param('zip_code'),
                'preferred_phone' => $request->param('preferred_phone'),
                'standby_phone' => $request->param('standby_phone'),
                'preferred_time' => $request->param('preferred_time'),
                'illness' => $request->param('illness'),
                'treatment_doctor' => $request->param('treatment_doctor'),
                'treatment_hospital' => $request->param('treatment_hospital'),
                 'specialty' => $request->param('specialty'),
                 'case_type' => $request->param('case_type'),
                 'case_manager' => $request->param('case_manager'),
                'case_note' => $request->param('case_note'),
                'sort' => $request->param('sort',0),
                 'country'=>$request->param('country'),
                'email'=>$request->param('email')
            ];
            
;
            
            // 修改
            $model = CaseModel::getInstance();
            $map = [
            'id' => $caseid
            ];
            $status = $model->save($data,$map);
            $this->success('修改成功', self::JUMP_REFERER);
        } else {
            $this->siteTitle = '编辑case';
            
            
        $model = CaseModel::getInstance();
        $case_list=[];
        if($caseid){
            $alias= CaseModel::getInstance()->alias_name; //case表别名
            $map=[
                $alias.'.id'=>$caseid
                ];
          $case_list=$model->getCaseList($map)->select();
          
        }else{
            $this->error('非法操作', self::JUMP_REFERER);
        }
        
  
        $this->assign('case_list', $case_list[0]);
            
           //性别
            $this->getSexList();
            
          //是否
            $this->getIsList();
            
            //获取省列表
            $this->assignProvinceList();
            //获取国家列表
            $this->getCountryList();
          
     
        
            //获取服务类型列表
        $this->getTypeList();
            //获取状态类型列表
        $this->getStatusList();
            //获取case_manager列表
        $this->getManagerList();
        
            return $this->fetch();
        
        }
    }
    
        /**
     * 删除case
     *
     * @param Request $request            
     * @return mixed
     */
    public function delete(Request $request)
    {
        $this->_delete(CaseModel::class, true);
    }

}