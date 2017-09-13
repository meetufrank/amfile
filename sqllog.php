//nd_cases_chatuser表添加语言字段
ALTER TABLE `nd_cases_chatuser`
ADD COLUMN `language`  int(11) NOT NULL DEFAULT 1 COMMENT '//1为中文简体 2为中文繁体 3为英文' AFTER `idnumber`;

//nd_cases_chatuser表添加字段
ALTER TABLE `nd_cases_chatuser`
ADD COLUMN `start_time`  date NULL AFTER `language`;
ALTER TABLE `nd_cases_chatuser`
ADD COLUMN `stop_time`  date NULL AFTER `start_time`;
ALTER TABLE `nd_cases_chatuser`
ADD COLUMN `change_content`  text NULL AFTER `stop_time`;


//nd_cases_company表添加字段
ALTER TABLE `nd_cases_company`
ADD COLUMN `default`  text NULL COMMENT '//默认' AFTER `type`;


//nd_cases_case表添加字段
ALTER TABLE `nd_cases_case`
ADD COLUMN `ks_type`  int(11) NOT NULL DEFAULT 1 COMMENT '//科室' AFTER `options`;

//nd_cases_company表添加字段
ALTER TABLE `nd_cases_company`
ADD COLUMN `abbreviation`  varchar(10) NULL AFTER `default`;
ALTER TABLE `nd_cases_company`
ADD COLUMN `apiid`  varchar(100) NULL COMMENT '//公司api Id' AFTER `abbreviation`,
ADD COLUMN `apipwd`  varchar(255) NULL COMMENT '//公司API密码' AFTER `apiid`;


//新建表
CREATE TABLE `nd_cases_chatuser_ks` (
`id`  int(10) NOT NULL AUTO_INCREMENT COMMENT '//id' ,
`user_id`  int(10) NOT NULL COMMENT 'casemanagerid' ,
`ks_id`  int(10) NOT NULL COMMENT '//科室id',
`status`  int(2) NOT NULL DEFAULT 1 COMMENT '//状态' ,
PRIMARY KEY (`id`)
)
;

//添加表字段
ALTER TABLE `nd_cases_chatuser`
ADD COLUMN `workid`  int(10) NOT NULL DEFAULT 0 COMMENT '//casemanager额外信息id' AFTER `u_status`;

