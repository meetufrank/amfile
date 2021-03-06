<?php
namespace module\ecases\controller;

use think\Request;
use think\Session;
use think\cache\driver\Redis;
use core\cases\model\CaseModel;
use core\cases\model\CaseTypeModel;
use core\cases\model\ChatUserModel;
use core\cases\logic\CaseTypeLogic;
use core\cases\model\CountryModel;
use core\cases\model\AreaModel;
use core\manage\logic\UserLogic;
use core\manage\model\UserModel;
use core\cases\logic\ChatUserLogic;
use core\cases\validate\CaseValidate;
use core\cases\validate\EcaseValidate;
use core\cases\logic\CaseLogic;
use core\cases\model\ChatGroupModel;
use core\cases\model\GroupDetailModel;
use app\manage\service\LoginService;
use core\manage\model\FileModel;
use core\cases\model\CompanyModel;
use core\cases\model\JtModel;
use app\common\sendemail\SendUser;
use app\common\addpdf\AddPdf;
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

        $this->siteTitle = 'Case List';
        //获取case表的别名
        $case_alias=CaseModel::getInstance()->alias_name;
        // case列表
        $map = [
            $case_alias.'.delete_time' => 0
        ];
      
        $this->assignCaseList($map);
        
        //是否监听
        $this->isJt();
        return $this->fetch();
    }

    /*
     * 当前登录帐号是否监听
     */
    public function isJt() {
        $login = LoginService::getSingleton();   
        $loginUser = $login->getLoginUser();
        $id=$loginUser['user_id'];
        $this->assign('userid', $id);//管理员当前登录id
        $logic =UserLogic::getInstance();
        $list=$logic->isJT($id);
        if(!empty($list)){
            
            $is_jt=1;
        }else{
            $is_jt=0;
        }
        $this->assign('is_jt', $is_jt);
    }
       /**
     * case详情
     *
     * @param Request $request            
     * @return string
     */
    public function case_content($id)
    {
        
        $this->siteTitle = 'Case content';
        
        // case详情
        $model = CaseModel::getInstance();
        
       //获取case详情
        $case_list=CaseLogic::getInstance()->casesById($id);
       //print_r($case_list);exit;
        if(empty($case_list)){
            $this->error('The case does not exist or is invalid', self::JUMP_BACK);
            exit;
        }
        if($case_list['case_manager']){
            $map=[
                'managerid'=>$case_list['case_manager']
            ];
            $users=ChatUserLogic::getInstance()->getUsersAll($map,1);
             
            $this->assign('users',$users);
        }else{
            $this->assign('users',[]);
        }
       
        $jtarr=[];
        foreach ($case_list->jtarr as $key => $value) {
            $jtarr[]=$value['user_name'];
        }
        $this->assign('jtstr', implode(',', $jtarr));
        $this->assign('case_list',$case_list);
        return $this->fetch();
    }
    
    /*
     * 生成PDF
     * 
     */
    public function create_pdf($id){
        $this->siteTitle = 'Generate pdf';
        
        // case详情
        $model = CaseModel::getInstance();
        
       //获取case详情
        $case_list=CaseLogic::getInstance()->casesById($id);
       //print_r($case_list);exit;
        if(empty($case_list)){
            $this->error('The case does not exist or is invalid', self::JUMP_BACK);
            exit;
        }
        if($case_list['case_manager']){
            $map=[
                'managerid'=>$case_list['case_manager']
            ];
            $users=ChatUserLogic::getInstance()->getUsersAll($map,1);
             
            
        }else{
            $users=[];
        }
        if(empty($users)){
            $manager_name='';
        }else{
            $manager_name=$users['nickname'].'('.$users['user_name'].')';
        }
        //排除性别字段,与患者的关系字段,国家等
        $keyarr=[
            'isme',
            'Hypertension',
            'highCholestero',
            'heartDisease',
            'kidneyDisease',
            'footLegProblems',
            'eyeDisease',
            'msIssues',
            'mfConcerns',
            'isAccept'
        ];
        $province_data='';
        
        $case_list=json_decode(json_encode($case_list),true);
        
        $logic =CaseLogic::getInstance();
         //问卷表单
       //获取心理支持单选数组(问卷)
        $xl_list=$logic->getXinlihelp(2);
       //获取情绪问题多选选项数组(问卷)
        $qx_list=$logic->getQingxuhelp(2);
    
       //是否有伤害自己的想法单选选项数组(问卷)
        $sh_list=$logic->getShanghaihelp(2);
        
        foreach ($case_list as $key => $value) {
            
            if(!in_array($key, $keyarr)){
                if($key=='sex'){
                   if($value==1){
                      $case_list[$key]='Male'; 
                   }else{
                       $case_list[$key]='Female'; 
                   }
                }
                if($key=='relationship'){
                   if(empty($value)){
                      $case_list[$key]='myself'; 
                   }
                }
                if($key=='country'){
                   if($value==1){
                      $province_data=$case_list['province_name'].'-'.$case_list['city_name'].'-'.$case_list['district_name']; 
                   }else{
                      $province_data=$case_list['e_province'];
                   }
                }
                
                 if($key=='xinli_help'){  //心里问卷单选
                
                    foreach ($xl_list as $k => $v) {
                        if($v['value']==$value && !empty($value)){
                            $case_list['xinli_help_name']=$v['name'];
                            $case_list['xinli_help_ename']=$v['ename'];
                        }
                    }
                    
                    isset($case_list['xinli_help_name']) || $case_list['xinli_help_name']='';
                  
                }
                
                
                if($key=='qingxu_help'){  //获取情绪问题多选选项数组(问卷)
                
                    $qingxuarr= @explode(',', $value);
                    $newarr=[];
                    foreach ($qx_list as $k => $v) {
                        if(in_array($v['value'], $qingxuarr) && !empty($value)){
                            
                            $newarr[]=$v['name'];
                        }
                    }
                    $case_list['qingxu_help_name']= @implode(',', $newarr);

                    if(in_array('999', $qingxuarr)){
                       $case_list['qingxu_help_name']=$case_list['qingxu_help_name'].' '.$case_list['qingxu_other'];
                    }
                  
                }
                
                if($key=='shanghai_help'){  //是否有伤害自己的想法单选选项数组
                
                    foreach ($sh_list as $k => $v) {
                        if($v['value']==$value && !empty($value)){
                            $case_list['shanghai_help_name']=$v['name'];
                        }
                    }
                    
                    isset($case_list['shanghai_help_name']) || $case_list['shanghai_help_name']='';
                  
                }
            }else{
                  
                    if($value>0){
                        $case_list[$key]='YES';
                    }else{
                       $case_list[$key]='No'; 
                    }
                    
                
            }
            if(is_null($value)||$value===''){
                    $case_list[$key]='';
                }
        }
       //生成html
        
        $html=<<<EOD
<style type="text/css">
 table{
     width:100% ;
     height:100%;
     text-align:center;
     vertical-align:middle;    
     margin: auto;  
                
     }
 th{
    width:30%;

     }
 td{
     width:70%;           
     }
.te{
   width:25%; 
   display:table-cell; 
   vertical-align:middle;
  
   }
.tc{
   width:25%; 
   }
</style>
<!-- <h4 style="text-align:center;font-weight:bolder;">Case details</h4>-->
<table border="1" >
                
                
             
                

                <tr>
                  <th>CaseID</th>
                  <td>{$case_list['case_code']}</td>
                </tr>
                  <tr>
                  <th>Medical record number</th>
                  <td>{$case_list['record_number']}</td>
                  </tr>
                  <tr>
                  <th>Service Item</th>
                  <td>{$case_list['typeename']}</td>
                </tr>
                  <tr>
                  <th>Department</th>
                  <td>{$case_list['ks_name']}({$case_list['ks_ename']})</td>
                </tr>
                
                <tr>
                <th>Project doctor</th>
                <td>$manager_name</td>
                </tr>
                <tr>
                <th>Applicant</th>
                <td>{$case_list['case_username']}</td>
                </tr>
                <tr>
                <th>Service language</th>
                <td>{$case_list['service_lang_ename']}</td>
                </tr>
                <tr>
                  <th style="width:100%;height:20%;font-size:15px;">Case information</th>
             
                </tr>
                <tr>
                  <th class="te">Patient's name</th>
                  <td class="tc">{$case_list['username']}</td>
                  <th class="te">Date of birth</th>
                  <td class="tc">{$case_list['birthday']}</td>
                </tr>
               <tr>
                  <th class="te">Gender</th>
                  <td class="tc">{$case_list['sex']}</td>
                  <th class="te">If you are the patient</th>
                  <td class="tc">{$case_list['isme']}</td>
                </tr>
                 <tr>
                  <th class="te">Applicant's name</th>
                  <td class="tc">{$case_list['applicant_name']}</td>
                  <th class="te">Relationship with the patient</th>
                  <td class="tc">{$case_list['relationship']}</td>
                </tr>
                  <tr>
                  <th class="te">Country</th>
                  <td class="tc">{$case_list['country_ename']}</td>
                  <th class="te">Province where the city</th>
                  <td class="tc">$province_data</td>
                </tr>
                 <tr>
                  <th class="te">Address</th>
                  <td class="tc">{$case_list['address']}</td>
                  <th class="te">Zip code</th>
                  <td class="tc">{$case_list['zip_code']}</td>
                </tr>
                  <tr>
                  <th class="te">Email</th>
                  <td class="tc">{$case_list['email']}</td>
                  <th class="te">Preferred Telephone</th>
                  <td class="tc">{$case_list['preferred_phone']}</td>
                </tr>
                  <tr>
                  <th class="te">Convenient Time to be Contacted</th>
                  <td class="tc">{$case_list['preferred_time']}</td>
                  <th class="te">Doctor`s name</th>
                  <td class="tc">{$case_list['treatment_doctor']}</td>
                  
                 </tr>
                  <tr>
                  <th class="te">Hospital</th>
                  <td class="tc">{$case_list['treatment_hospital']}</td>
                  <th class="te">Specialist</th>
                  <td class="tc">{$case_list['specialty']}</td>
                 </tr>
                  
                  <tr>
                   <th>What kind of psychological support is needed?</th>
                   <td>{$case_list['xinli_help_name']}</td>
                  </tr>
                   <tr>
                   <th>Is there any idea of hurting yourself?</th>
                   <td>{$case_list['shanghai_help_name']}</td>
                  </tr>
                  <tr>
                   <th>Is there an emotional problem?</th>
                   <td>{$case_list['qingxu_help_name']}</td>
                  </tr>
                   <tr>
                   <th>Other symptoms</th>
                   <td>{$case_list['shenti_content']}</td>
                  </tr>
                   <tr>
                   <th>Are you taking medicine?</th>
                   <td>{$case_list['yaowu_help']}</td>
                  </tr>
                   <tr>
                   <th>Have you ever had a psychological consultation?</th>
                   <td>{$case_list['before_xinli']}</td>
                  </tr>
                   
                   
                 <tr>
                  <th style="width:100%;height:20%;font-size:15px;">Extra information</th>
             
                </tr>
                  <tr>
                  <th class="te">Year of diagnosis</th>
                  <td class="tc">{$case_list['diagnosisDate']}</td>
                  <th class="te">Current Weight</th>
                  <td class="tc">{$case_list['weight']}</td>
                 </tr>
                  <tr>
                  <th class="te">Height</th>
                  <td class="tc">{$case_list['height']}</td>
                  <th class="te">Medications for glucose</th>
                  <td class="tc">{$case_list['Me_glucose']}</td>
                 </tr>
                  <tr>
                  <th class="te">Medications for blood pressure</th>
                  <td class="tc">{$case_list['Me_bloodPressure']}</td>
                  <th class="te">Medications for cholesterol</th>
                  <td class="tc">{$case_list['Me_cholesterol']}</td>
                 </tr>
                  <tr>
                  <th class="te">Medications for kidney protection</th>
                  <td class="tc">{$case_list['Me_kidneyProtection']}</td>
                  <th class="te">Medications for arterial protection</th>
                  <td class="tc">{$case_list['Me_arterialProtection']}</td>
                 </tr>
                  <tr>
                  <th class="te">Hypertension</th>
                  <td class="tc">{$case_list['Hypertension']}</td>
                  <th class="te">High cholesterol</th>
                  <td class="tc">{$case_list['highCholestero']}</td>
                 </tr>
                  <tr>
                  <th class="te">Heart disease</th>
                  <td class="tc">{$case_list['heartDisease']}</td>
                  <th class="te">Kidney disease</th>
                  <td class="tc">{$case_list['kidneyDisease']}</td>
                 </tr>
                  <tr>
                  <th class="te">Eye disease</th>
                  <td class="tc">{$case_list['eyeDisease']}</td>
                  <th class="te">Foot or leg problems</th>
                  <td class="tc">{$case_list['footLegProblems']}</td>
                 </tr>
                  <tr>
                  <th class="te">Mental stress issues</th>
                  <td class="tc">{$case_list['msIssues']}</td>
                  <th class="te">Male or female concerns</th>
                  <td class="tc">{$case_list['mfConcerns']}</td>
                 </tr>
                  <tr>
                  <th class="te">Smoking stop date</th>
                  <td class="tc">{$case_list['smokingDate']}</td>
                  <th class="te">Alcohol stop date</th>
                  <td class="tc">{$case_list['alcoholDate']}</td>
                 </tr>
                  <tr>
                  <th class="te">Most recent blood pressure</th>
                  <td class="tc">{$case_list['MRBPressure']}</td>
                  <th class="te">Most recent HbA1c</th>
                  <td class="tc">{$case_list['HbA1c']}</td>
                 </tr>
                 <tr>
                  <th>Consent for medical records release</th>
                  <td>{$case_list['isAccept']}</td>
                 </tr> 
                  
               <tr>
                <th style="width:100%;">Description</th>
                
                </tr>
                <tr style="width:100%;text-align:left;">
                 <th  height="150px" style="width:100%;">&nbsp;&nbsp;&nbsp;&nbsp;{$case_list['illness']}</th>
                </tr>
                 <tr>
                 <th style="width:100%;">Case Remarks</th>
                
                </tr>
                <tr style="width:100%;text-align:left;">
                 <th  height="250px" style="width:100%;">&nbsp;&nbsp;&nbsp;&nbsp;{$case_list['case_note']}</th>
                </tr>
              
               
              
               
                
              
            
                
                
                
       
  
</table>        
       
      
EOD;
        $ceshi=new AddPdf();
        $data=$ceshi->create($html,$case_list['case_code'].'_EN');
        
        exit;
      
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
        //查询条件-负责人
        $managerid= $request->param('managerid');
        if ($managerid) {
            $managerid = intval($managerid);
            $map[$case_alias.'.case_manager'] = $managerid;
            $map[$case_alias.'.case_status'] = ['in','2,5'];
        }
        $this->assign('managerid', $managerid);
        
        // 分页列表
        $model = CaseModel::getInstance();
        $case_list=$model->getCaseList($map);
        
        $this->casePage($case_list);
        
      
        $this->getTypeList();
        
        $this->getStatusList();
        
        $this->getCountryList();
 
    }
//case分页
    public function casePage($model, $rowNum = null, \Closure $perform = null){
        $rowNum || $rowNum = config('manage_row_num');
        $rowNum || $rowNum = 10;
        
      $model = $this->buildModel($model);
        
        $list = $model->paginate($rowNum);
        $perform && $perform($list);
        foreach ($list as $key => $value) {
             $jtarr=[];
             $users=[];
            foreach ($value->jtarr as $k => $v) {
                $jtarr[]=$v['id'];
            }
            $list[$key]['jtarr']= $jtarr;
            if($value['case_manager']){
                $map=[
                   'managerid'=>$value['case_manager']
                ];
                $users=ChatUserLogic::getInstance()->getUsers($map,1);
            }
            if(!empty($users)){
                $list[$key]['managername']= $users['nickname'].'('.$users['user_name'].')';
            }else{
                if($value['case_status']==2){
                    $list[$key]['managername']= 'Waiting for acceptance'; 
                }else{
                   $list[$key]['managername']= 'Unspecified'; 
                }
                
            }
            //查询公司
            if($value['user_company']){
                $cmap=[
                    'id'=>$value['user_company']
                ];
                $company=CompanyModel::getInstance()->where($cmap)->value('name');
                $list[$key]['company_name']= $company;
            }
        }
     
        $this->assign('_list', $list);
        $this->assign('_page', $list->render());
        $this->assign('_total', $list->total());
    }

    //获取类型数组
     protected function getTypeList(){
         
         $logic =CaseTypeLogic::getInstance();
         $typelist=$logic->getSelectType(null,null,2);

         $this->assign('typelist',$typelist);
     }
    
 //获取科室数组
     protected function getKsList(){
         
         $logic =CaseTypeLogic::getInstance();
         $kslist=$logic->getSelectKs();

         $this->assign('kslist',$kslist);
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
         $country_list=$logic->getSelectCountry('sort','desc',2);
         $this->assign('country_list',$country_list);
     }
    
            //获取监听数组
    protected function getJtList(){
         
         $logic =UserLogic::getInstance();
         $where=[
             'user_gid'=>config('am_jianting'),
             'user_status'=>1,
             'delete_time'=>0
         ];
         $jt_list=$logic->getSelectList($where);
       
        ksort($jt_list);  //排序
         $this->assign('jt_list',$jt_list);
     }
            //获取普通用户数组
    protected function getUserList(){
         
         $logic = ChatUserLogic::getInstance();
         $where=[
             'is_manager'=>0,
             'managerid'=>0,
             'delete_time'=>0
         ];
         $chatuser=$logic->getSelectUser($where);
        
         $this->assign('chatuser',$chatuser);
     }
     //获取心理支持单选数组(问卷)
    protected function getXinliList(){
         
         $logic =CaseLogic::getInstance();
         $case_manager=$logic->getXinlihelp(2);
         $this->assign('xinlilist',$case_manager);
     }
      //获取情绪问题多选选项数组(问卷)
    protected function getQingxuList(){
         
         $logic =CaseLogic::getInstance();
         $case_manager=$logic->getQingxuhelp(2);
         $this->assign('qingxulist',$case_manager);
     }
      //是否有伤害自己的想法单选选项数组(问卷)
    protected function getShanghaiList(){
         
         $logic =CaseLogic::getInstance();
         $case_manager=$logic->getShanghaihelp(2);
         $this->assign('shanghailist',$case_manager);
     }
       //获取性别数组
    protected function getSexList(){
         
         $logic =CaseTypeLogic::getInstance();
         $case_manager=$logic->getSelectSex(2);
         $this->assign('sexlist',$case_manager);
     }
     //获取不是本人时性别数组
    protected function getCaxbList(){
         
         $logic =CaseLogic::getInstance();
         $case_manager=$logic->getCaxb(2);
         $this->assign('caxblist',$case_manager);
     }
     //获取首选联系方式数组
    protected function getSxlxList(){
         
         $logic =CaseLogic::getInstance();
         $case_manager=$logic->getSxff(2);
         $this->assign('sxlxlist',$case_manager);
     }
       //获取是否数组
    protected function getIsList(){
         
         $logic =CaseTypeLogic::getInstance();
         $case_manager=$logic->getSelectIs(2);
         $this->assign('islist',$case_manager);
     }
        //获取服务语言数组
    protected function getServiceLangList(){
         
         $logic =CaseTypeLogic::getInstance();
         $case_manager=$logic->getSelectServiceLang([],'',2);
         $this->assign('ServiceLangList',$case_manager);
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
            'case_status',
            'case_manager',
           
        ];
        
        $field=$request->param('field');
        $value=$request->param('value','');
        
        
      
        if($field=='case_manager'){
            $value && $this->actionByManager($value);
        }
        if($field=='case_status'){
            
            $value && $this->actionByStatus($value);
            
        }
        
        $this->_modify(CaseModel::class, $fields);
    }
 /*
  * 如果接收到case_manager的值需要将case assigned
  * 
  */
     public function actionByManager($value) {
         $map = [
            'id' => $this->_id(),
            'case_status'=>1
        ];
        //建立聊天群组
        $this->addGroup();
         
         $data=[
                    'case_status'=>2
                ];
         
       $result=CaseModel::getInstance()->save($data,$map);
       if(!$result){
           $this->error('Please adjust the status of the case pending and then modify');
           exit;
       }
       $data=ChatUserModel::where('managerid',$value)->find();
      
      
//       if($data){
//         $username=$data['nickname'];
//           $msg=new \message\mess();
//           $url='http://'.$_SERVER['HTTP_HOST'].'/service';
//           $data['url']=$url;
//           $mess_content=ChatUserLogic::getInstance()->getLanguage($data,1); //获取短信内容
//            $msg->send($data['tel'], $mess_content['content']);
//            
//       }
       
     }
  
     /*
      * 新建群组
      */
     public function addGroup(){
         $id= $this->_id();
         $casemodel=CaseModel::getInstance();
        //获取case详情
        $case=CaseLogic::getInstance()->casesById($id);
       

        //新建群组
        $data=[
            'group_name'=>$case['case_code'],
            'avatar'=>$case['user_avatar'],
            'owner_name'=>$case['case_username'],
            'owner_id'=>$case['userid'],
            'addtime'=>time(),
            'status'=>1
        ];
        // 添加
            $model = ChatGroupModel::getInstance();
            $status = $model->save($data);
            $casemodel->where(['id'=>$id])->update(['groupid'=>$model['id']]);
           
       
        
     }
     
    /*
     * 根据接收不同的状态进行不同的操作
     * 
     */
    public function actionByStatus($value) {
        $id= $this->_id();
        $where = [
            'id' => $id
        ];
       
        $case=CaseLogic::getInstance()->casesById($id);
      
        $group=GroupDetailModel::getInstance();
        switch ($value) {
                case 1:
                  //查询casemanager用户信息
                $managerdata=ChatUserModel::getInstance()->where(['managerid'=>$case['case_manager']])->find();
                $userid=$managerdata['id'];   //casemanager用户id
                $groupid=$case['groupid'];
                
                $map=[
                    'group_id'=>$groupid,
                    'user_id'=>$userid   
                ];
                 $group->where($map)->update(['status'=>0]);   //修改该用户在该表中的状态
                $data=[
                    'case_manager'=>0
                ];
               
                CaseModel::getInstance()->save($data,$where);
                    break;
                case 5:
                    //发送通知邮件
                  
                    
                    $userdata=ChatUserModel::getInstance()->where(['managerid'=>$case['case_manager'],'delete_time'=>0])->find();
                    $userdata['case_code']=$case['case_code'];
                   
                    $email=new SendUser();
                    $email->acceptCase($userdata);
                    $alldata=$this->editByJt($case);  //分配聊天室
                      $group->saveAll($alldata);
                      
                      break;
                
                default:
                    break;
            }
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
                'idcard' => $request->param('idcard'),
                'record_number' => $request->param('record_number'),
                'isme' => $request->param('isme'),
                'relationship' => $request->param('relationship'),
                'applicant_name' => $request->param('applicant_name'),
                'casexb' => $request->param('casexb'),
                'address' => $request->param('address'),
                'province' => $request->param('province', 110000),
                'city' => $request->param('city', 110100),
                'district' => $request->param('district', 110101),
                'zip_code' => $request->param('zip_code'),
                'sx_lxfs' => $request->param('sx_lxfs'),
                'preferred_phone' => $request->param('preferred_phone'),
                'service_lang' => $request->param('service_lang'),
                'standby_phone' => $request->param('standby_phone'),
                'preferred_time' => $request->param('preferred_time'),
                'illness' => $request->param('illness'),
                'treatment_doctor' => $request->param('treatment_doctor'),
                'treatment_hospital' => $request->param('treatment_hospital'),
                 'specialty' => $request->param('specialty'),
                 'case_type' => $request->param('case_type'),
                'case_note' => $request->param('case_note'),
                'sort' => $request->param('sort',0),
                'userid' => $request->param('userid'),
                'country'=>$request->param('country'),
                'email'=>$request->param('email'),
                'ks_type'=>$request->param('ks_type',1),
                'e_province'=>$request->param('e_province'),
                'Hypertension'=>$request->param('Hypertension'),
                'highCholestero'=>$request->param('highCholestero'),
                'heartDisease'=>$request->param('heartDisease'),
                'kidneyDisease'=>$request->param('kidneyDisease'),
                'eyeDisease'=>$request->param('eyeDisease'),
                'footLegProblems'=>$request->param('footLegProblems'),
                'msIssues'=>$request->param('msIssues'),
                'mfConcerns'=>$request->param('mfConcerns'),
                'smokingDate'=>$request->param('smokingDate')?$request->param('smokingDate'):null,
                'alcoholDate'=>$request->param('alcoholDate')?$request->param('alcoholDate'):null,
                'MRBPressure'=>$request->param('MRBPressure'),
                'HbA1c'=>$request->param('HbA1c'),
                'isAccept'=>$request->param('isAccept'),
                'diagnosisDate'=>$request->param('diagnosisDate')?$request->param('diagnosisDate'):null,
                'weight'=>$request->param('weight'),
                'height'=>$request->param('height'),
                'Me_glucose'=>$request->param('Me_glucose'),
                'Me_bloodPressure'=>$request->param('Me_bloodPressure'),
                'Me_cholesterol'=>$request->param('Me_cholesterol'),
                'Me_kidneyProtection'=>$request->param('Me_kidneyProtection'),
                'Me_arterialProtection'=>$request->param('Me_arterialProtection'),
                'xinli_help'=>$request->param('xinli_help'), //问卷开始
                'qingxu_help'=>implode(',', array_filter($request->param('qingxu_help/a',[]))),
                'qingxu_other'=>$request->param('qingxu_other'),
                'shanghai_help'=>$request->param('shanghai_help'),
                'shenti_content'=>$request->param('shenti_content'),
                'yaowu_help'=>$request->param('yaowu_help'),
                'before_xinli'=>$request->param('before_xinli'),//问卷结束
            ];
           if(empty($data['province'])){
               $data['province']=110000;
           }
           if(empty($data['city'])){
               $data['city']=110100;
           }
           if(empty($data['district'])){
               $data['district']=110101;
           }
            if($request->param('options')){
                $file=FileModel::getInstance()->where(['file_url'=>$request->param('options')])->find();
                if(!empty($file)){
                    $data['options']=$file['id'];
                }
            }
             // 验证
            $this->_validate(EcaseValidate::class, $data, 'add');
            
            // 添加
            $model = CaseModel::getInstance();
           
            $status = $model->save($data);
            $this->success('Added successfully', self::JUMP_REFERER);
        } else {
            $this->siteTitle = 'Add case';
           
            
           //性别
            $this->getSexList();
            
          //是否
            $this->getIsList();
            
            //获取省列表
            $this->assignProvinceList();
            //获取国家列表
            $this->getCountryList();
          
                //获取监听列表
            $this->getJtList();
        
            //获取服务类型列表
        $this->getTypeList();
            //获取状态类型列表
        //$this->getStatusList();
           
                    //获取用户列表
        $this->getUserList();
        
        
        //获取选择不是本人性别下拉列表
        $this->getCaxbList();
        //获取首选联系方式列表列表
        $this->getSxlxList();
        
        
       //获取case科室列表
       $this->getKsList();
       
       //获取case服务语言列表
       $this->getServiceLangList();
       //获取额外表单信息表
       $this->assign('typemore', CaseLogic::getInstance()->getMoreContent([],2));
       
        //问卷表单
       //获取心理支持单选数组(问卷)
       $this->getXinliList();
       //获取情绪问题多选选项数组(问卷)
       $this->getQingxuList();
       //是否有伤害自己的想法单选选项数组(问卷)
       $this->getShanghaiList();
       
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
                'idcard' => $request->param('idcard'),
                'record_number' => $request->param('record_number'),
                'isme' => $request->param('isme'),
                'relationship' => $request->param('relationship'),
                'applicant_name' => $request->param('applicant_name'),
                'casexb' => $request->param('casexb'),
                'address' => $request->param('address'),
                'province' => $request->param('province', 110000),
                'city' => $request->param('city', 110100),
                'district' => $request->param('district', 110101),
                'zip_code' => $request->param('zip_code'),
                'sx_lxfs' => $request->param('sx_lxfs'),
                'preferred_phone' => $request->param('preferred_phone'),
                'service_lang' => $request->param('service_lang'),
                'standby_phone' => $request->param('standby_phone'),
                'preferred_time' => $request->param('preferred_time'),
                'illness' => $request->param('illness'),
                'treatment_doctor' => $request->param('treatment_doctor'),
                'treatment_hospital' => $request->param('treatment_hospital'),
                 'specialty' => $request->param('specialty'),
                 'case_type' => $request->param('case_type'),
                'case_note' => $request->param('case_note'),
                'sort' => $request->param('sort',0),
                 'country'=>$request->param('country'),
                'email'=>$request->param('email'),
                'ks_type'=>$request->param('ks_type'),
                'e_province'=>$request->param('e_province'),
                'Hypertension'=>$request->param('Hypertension'),
                'highCholestero'=>$request->param('highCholestero'),
                'heartDisease'=>$request->param('heartDisease'),
                'kidneyDisease'=>$request->param('kidneyDisease'),
                'eyeDisease'=>$request->param('eyeDisease'),
                'footLegProblems'=>$request->param('footLegProblems'),
                'msIssues'=>$request->param('msIssues'),
                'mfConcerns'=>$request->param('mfConcerns'),
                'smokingDate'=>$request->param('smokingDate')?$request->param('smokingDate'):null,
                'alcoholDate'=>$request->param('alcoholDate')?$request->param('alcoholDate'):null,
                'MRBPressure'=>$request->param('MRBPressure'),
                'HbA1c'=>$request->param('HbA1c'),
                'isAccept'=>$request->param('isAccept'),
                'diagnosisDate'=>$request->param('diagnosisDate')?$request->param('diagnosisDate'):null,
                'weight'=>$request->param('weight'),
                'height'=>$request->param('height'),
                'Me_glucose'=>$request->param('Me_glucose'),
                'Me_bloodPressure'=>$request->param('Me_bloodPressure'),
                'Me_cholesterol'=>$request->param('Me_cholesterol'),
                'Me_kidneyProtection'=>$request->param('Me_kidneyProtection'),
                'Me_arterialProtection'=>$request->param('Me_arterialProtection'),
                'xinli_help'=>$request->param('xinli_help'), //问卷开始
                'qingxu_help'=>implode(',', array_filter($request->param('qingxu_help/a',[]))),
                'qingxu_other'=>$request->param('qingxu_other'),
                'shanghai_help'=>$request->param('shanghai_help'),
                'shenti_content'=>$request->param('shenti_content'),
                'yaowu_help'=>$request->param('yaowu_help'),
                'before_xinli'=>$request->param('before_xinli'),//问卷结束
            ];
             if(empty($data['province'])){
               $data['province']=110000;
           }
           if(empty($data['city'])){
               $data['city']=110100;
           }
           if(empty($data['district'])){
               $data['district']=110101;
           }
            if($request->param('options')){
                $file=FileModel::getInstance()->where(['file_url'=>$request->param('options')])->find();
                if(!empty($file)){
                    $data['options']=$file['id'];
                }
            }
             // 验证
            $this->_validate(EcaseValidate::class, $data, 'edit');    
           
            // 修改
            $model = CaseModel::getInstance();
            $map = [
            'id' => $caseid
            ];
            $status = $model->save($data,$map);
            
            if($status){
                $jt_arr=$request->param('jtlist/a',[]);
               
                $case=CaseLogic::getInstance()->casesById($caseid);
                
                $group=GroupDetailModel::getInstance();
                if($case['groupid']){
                     
                          $jtid_arr=[];
                              foreach ($case->jtarr as $vo) {
                                      $jtid_arr[] = $vo['id'];
                                    }
                        foreach ($jtid_arr as $key => $value) {
                         $jtdata=ChatUserModel::getInstance()->where(['managerid'=>$value])->find();//jt
                         if(empty($jtdata)){
                          $this->error('Check the listener with unopened layim!');
                         }  
                         //查询该监听是否在群中或者有无加群记录
                         $map = [
                        'group_id' => $case['groupid'],
                        'user_id' => $jtdata['id']
                         ];
                         
                           $group->where($map)->update(['status'=>0]); 
                        }
                         
                      
                      
                     CaseLogic::getInstance()->joinjt($caseid, $jt_arr);
                     
                     if(!empty($jt_arr)){
                          //监听
                      $alldata=[];
                      
                      
                     foreach ($jt_arr as $key => $value) {
                         $jtdata=ChatUserModel::getInstance()->where(['managerid'=>$value])->find();//jt
                        if(empty($jtdata)){
                          $this->error('Check the listener with unopened layim!');
                         }  
                         //查询该监听是否在群中或者有无加群记录
                         $map = [
                        'group_id' =>$case['groupid'],
                        'user_id' => $jtdata['id']
                         ];
                          
                    $count=$group->where($map)->count();
                   
                    if($count){
                           $group->where($map)->update(['status'=>1]); 
                      }else{
                        
                          $data=[
                              'user_id'=>$jtdata['id'],
                              'user_name'=>$jtdata['user_name'],
                              'user_avatar'=>$jtdata['avatar'],
                              'group_id'=>$case['groupid'],
                              'status'=>1 
                          ];
                           
                          $alldata[]=$data;
                         
                       }
                      }
                      
                    $group->saveall($alldata);
                     }
                     
                }
               
            }
            $this->success('Edited successfully', self::JUMP_REFERER);
        } else {
            $this->siteTitle = 'Edit case';
            
            
        $model = CaseModel::getInstance();
        $case_list=[];
   
        if($caseid){
           $case_list=CaseLogic::getInstance()->casesById($caseid);

        }else{
            $this->error('Illegal operation', self::JUMP_REFERER);
        }
        
         $jtarr= [];
        foreach ($case_list->jtarr as $vo) {
            $jtarr[] = $vo['id'];
        }
        //查询附件信息
        $filearr=FileModel::getInstance()->where(['id'=>$case_list['options']])->find();
        $case_list['options_data']=$filearr;
        $case_list['case_jt']=$jtarr;
        
        //新加问卷的情绪问题
        $case_list['qingxu_help']= explode(',', $case_list['qingxu_help']);
        
        $this->assign('case_list', $case_list);
            
           //性别
            $this->getSexList();
            
          //是否
            $this->getIsList();
            
            //获取省列表
            $this->assignProvinceList();
            //获取国家列表
            $this->getCountryList();
          
          //获取监听列表
            $this->getJtList();
        //获取选择不是本人性别下拉列表
        $this->getCaxbList();
        //获取首选联系方式列表列表
        $this->getSxlxList();
            //获取服务类型列表
        $this->getTypeList();
        
            //获取状态类型列表
        // $this->getStatusList();
    
        //获取科室列表
        $this->getKsList();
        //获取case服务语言列表
        $this->getServiceLangList();
        //获取额外表单信息表
        $this->assign('typemore', CaseLogic::getInstance()->getMoreContent($case_list,2));
        
        //问卷表单
       //获取心理支持单选数组(问卷)
       $this->getXinliList();
       //获取情绪问题多选选项数组(问卷)
       $this->getQingxuList();
       //是否有伤害自己的想法单选选项数组(问卷)
       $this->getShanghaiList();
       
            return $this->fetch();
        
        }
    }
    
    /*
     * 根据case修改编辑聊天室
     */
    public function editByJt($case){
         $chatuser=ChatUserModel::getInstance();
                    
                    $managerdata=$chatuser->where(['managerid'=>$case['case_manager']])->find();//casemanager
                    $groupid=$case['groupid'];
                    $case_manager=$case['case_manager'];
                    $user_id=$case['userid'];
                    $user_name=$case['case_username'];
                    $user_avatar=$case['user_avatar'];
                   
                    
                    $alldata=[];
                   $group=GroupDetailModel::getInstance();
                   //配置默认监听
                   //查询该用户所属公司下方有效监听数组
                   $companyarr=CompanyModel::getInstance()->where(['id'=>$managerdata['company']])->find();
                   $default_jt=[];
                   empty($companyarr) || $default_jt=explode(',', $companyarr['default']);
                   $jt_arr=[];
                   foreach (@$default_jt as $key => $value) {
                       $jtcount= UserModel::getInstance()->where(['delete_time'=>0,'id'=>$value,'user_gid'=> config('am_jianting')])->count();
                       if($jtcount){
                           $jt_arr[]=$value;
                       }
                   }
                   //将监听数组分配至聊天室
                   foreach (@$jt_arr as $key => $value) {
                       $caseid=$case['id'];
                       //查询监听有没有跟被该case指定过
                      $cj_count=JtModel::getInstance()->where(['cases_id'=>$caseid,'user_id'=>$value])->count();
                      $cj_map=[
                          'cases_id'=>$caseid,
                          'user_id'=>$value                        
                      ];
                      if(!$cj_count){
                          JtModel::getInstance()->save($cj_map);
                      }
                       $jtdata=$chatuser->where(['managerid'=>$value])->find();//casemanager
                       $map=[
                          'group_id'=>$groupid,
                          'user_id'=>$jtdata['id']                         
                      ];
                      $count=$group->where($map)->count();
                      if($count){
                           $group->where($map)->update(['status'=>1]); 
                      }else{
                          $data=[
                              'user_id'=>$jtdata['id'],
                              'user_name'=>$jtdata['user_name'],
                              'user_avatar'=>$jtdata['avatar'],
                              'group_id'=>$groupid,
                              'status'=>1 
                          ];
                          $alldata[]=$data;
                      }
                   }
                    //查询该case_manager是否在群中或者有无加群记录
                    $map=[
                          'group_id'=>$groupid,
                          'user_id'=>$managerdata['id']                         
                      ];
                      $count=$group->where($map)->count();
                      if($count){
                           $group->where($map)->update(['status'=>1]); 
                      }else{
                          $data=[
                              'user_id'=>$managerdata['id'],
                              'user_name'=>$managerdata['user_name'],
                              'user_avatar'=>$managerdata['avatar'],
                              'group_id'=>$groupid,
                              'status'=>1 
                          ];
                          $alldata[]=$data;
                      }
                   
                   //患者
                    $map=[
                          'group_id'=>$groupid,
                          'user_id'=>$user_id                        
                      ];
                      $count=$group->where($map)->count();
                      if($count){
                           $group->where($map)->update(['status'=>1]); 
                      }else{
                          $data=[
                              'user_id'=>$user_id,
                              'user_name'=>$user_name,
                               'user_avatar'=>$user_avatar,
                              'group_id'=>$groupid,
                              'status'=>1 
                          ];
                          $alldata[]=$data;
                      }
                      
                      return $alldata;
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