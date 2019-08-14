<?php
namespace core\cases\model;

use core\Model;

class CaseServiceLangModel extends Model
{

    /**
     * 去前缀表名
     *
     * @var unknown
     */
    protected $name = 'cases_service_lang';

    /**
     * 自动写入时间戳
     *
     * @var unknown
     */
    protected $autoWriteTimestamp = true;

  
/*
 * 定义别名变量
 */
   public $alias_name='a_service_lang';
  
}