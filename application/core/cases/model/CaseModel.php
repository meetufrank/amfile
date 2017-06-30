<?php
namespace core\cases\model;

use core\Model;

class CaseModel extends Model
{

    /**
     * 去前缀表名
     *
     * @var unknown
     */
    protected $name = 'cases_case';

    /**
     * 自动写入时间戳
     *
     * @var unknown
     */
    protected $autoWriteTimestamp = true;

    /**
     * 新增时自动完成
     *
     * @var array
     */
    protected $insert = [
        'case_code'
    ];
/*
 * 定义别名变量
 */
   public $alias_name='a_case';
   
   /*
    * 获取全部管理case
    */
   public function getCaseList($map=null){
        $alias=$this->alias_name; //case表别名
        $aliastype=CaseTypeModel::getInstance()->alias_name; //类型表别名
        $counry=CountryModel::getInstance()->alias_name; //国家表别名
        $province=AreaModel::getInstance()->alias_name[0];  //省
        $city=AreaModel::getInstance()->alias_name[1];   //市
        $district=AreaModel::getInstance()->alias_name[2]; //区
        $user=ChatUserModel::getInstance()->alias_name; //用户
        $case_list = $this->withCates()->field($alias.'.*,'.$aliastype.'.typename,'.$counry.'.name as country_name,'.$province.'.area_name as province_name ,'.$city.'.area_name as city_name ,'.$district.'.area_name as district_name ,'.$user.'.user_name as case_username')->where($map)
            ->order($alias.'.sort desc, '.$alias.'.create_time desc');
        
        
        return $case_list;
   }
    /**
     * 使用别名
     *
     * @param unknown $query            
     */
    public function useAlias()
    {
        return $this->alias($this->alias_name);
    }
   /**
     * 连接分类
     *
     * @return \think\db\Query
     */
    public function withCates()
    {
        $query = $this->useAlias();
        $query=$this->joinCates($query);//加入分类
        $query= $this->withUser($query);//加入用户
        $query=$this->joinCountry($query);//加入国家
        return $this->joinAddress($query);
    }

    /**
     * 连接提交case用户
     *
     * @return \think\db\Query
     */
     public function withUser($query)
    {
       $user=ChatUserModel::getInstance();
       return $query->join($user->getTableShortName() . ' '.$user->alias_name, $this->alias_name.'.userid = '.$user->alias_name.'.id');
    }
    /**
     * 连接分类
     *
     * @return \think\db\Query
     */
    public function joinCates($query)
    {
        $casetype=CaseTypeModel::getInstance();
        return $query->join($casetype->getTableShortName() . ' '.$casetype->alias_name, $this->alias_name.'.case_type = '.$casetype->alias_name.'.id');
    }

    /**
     * 连接国家
     *
     * @return \think\db\Query
     */
    public function joinCountry($query)
    {
        $casetype=CountryModel::getInstance();
        return $query->join($casetype->getTableShortName() . ' '.$casetype->alias_name, $this->alias_name.'.country = '.$casetype->alias_name.'.id');
    }
    
       /**
     * 连接省市区
     *
     * @return \think\db\Query
     */
    public function joinAddress($query)
    {
        $casetype=AreaModel::getInstance();
        return $query->join($casetype->getTableShortName() . ' '.$casetype->alias_name[0], $this->alias_name.'.province = '.$casetype->alias_name[0].'.id')
                     ->join($casetype->getTableShortName() . ' '.$casetype->alias_name[1], $this->alias_name.'.city = '.$casetype->alias_name[1].'.id')
                     ->join($casetype->getTableShortName() . ' '.$casetype->alias_name[2], $this->alias_name.'.district = '.$casetype->alias_name[2].'.id');
    }
    
    
 
    /**
     * 自动设置文章key
     *
     * @return string
     */
    protected function setCaseCodeAttr()
    {
        return $this->getNewCaseKey();
    }

    /**
     * 获取一个新的文章Key
     *
     * @return string
     */
    public function getNewCaseKey()
    {
       
        $articleKey=$this->gethtime();
        $map = [
            'case_code' => $articleKey
        ];
        $record = $this->where($map)->find();
        if (empty($record)) {
            return $articleKey;
        } else {
            return $this->getNewCaseKey();
        }
    }
    
    /*
     * 获取当前毫秒时间戳
     */
    public function gethtime(){
         $articleKey = microtime();
               list($s1, $s2) = explode(' ', $articleKey);		
         return (float)sprintf('%.0f', (floatval($s1) + floatval($s2)) * 1000);
    }

}