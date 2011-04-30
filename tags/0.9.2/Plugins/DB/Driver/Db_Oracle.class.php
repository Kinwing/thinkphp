<?php 
// +---------------------------------------------------------------------------+
// | FCS -- Fast,Compatible & Simple OOP PHP Framework                         |
// +---------------------------------------------------------------------------+
// | Copyright (c) 2005-2006 liu21st.com.  All rights reserved.                |
// | Website: http://www.fcs.org.cn/                                           |
// | Author : Liu21st 流年 <liu21st@gmail.com>                                 |
// +---------------------------------------------------------------------------+
// | This program is free software; you can redistribute it and/or modify it   |
// | under the terms of the GNU General Public License as published by the     |
// | Free Software Foundation; either version 2 of the License,  or (at your   |
// | option) any later version.                                                |
// |                                                                           |
// | This program is distributed in the hope that it will be useful,  but      |
// | WITHOUT ANY WARRANTY; without even the implied warranty of                |
// | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU General |
// | Public License for more details.                                          |
// +---------------------------------------------------------------------------+

/**
 +------------------------------------------------------------------------------
 * FCS
 +------------------------------------------------------------------------------
 * @package    Db
 * @link       http://www.fcs.org.cn
 * @copyright  Copyright (c) 2005-2006 liu21st.com.  All rights reserved. 
 * @author     liu21st <liu21st@gmail.com>
 * @version    $Id: Db_Oracle.class.php 92 2006-11-11 08:47:04Z fcs $
 +------------------------------------------------------------------------------
 */


/**
 +------------------------------------------------------------------------------
 * Oracle数据库驱动类
 +------------------------------------------------------------------------------
 * @package   Db
 * @author    liu21st <liu21st@gmail.com>
 * @version   0.8.0
 +------------------------------------------------------------------------------
 */
Class Db_Oracle extends Db{

    var $sid;
    /**
     +----------------------------------------------------------
     * 架构函数 读取数据库配置信息
     * 
     +----------------------------------------------------------
     * @access public 
     +----------------------------------------------------------
     * @param array $config 数据库配置数组
     +----------------------------------------------------------
     */
    function __construct($config){    
        if ( !function_exists('oci_connect')) {    
            throw_exception('系统不支持oracle');
        }
        $this->username = $config['username'];
        $this->password = $config['password'];
        $this->sid      = $config['database'];//ORACLE_SID
        $this->hostname = $config['hostname'];
        $this->hostport = $config['hostport'];
        //$this->database = "(DESCRIPTION = ADDRESS_LIST (ADDRESS = (PROTOCOL= TCP (Host= ".$this->hostname.") (Port= ".$this->hostport.")))(CONNECT_DATA = (SID = ".$this->sid." )))"; 
        $this->database = "(DESCRIPTION = (ADDRESS_LIST = (ADDRESS =(COMMUNITY = ".$this->database.")(PROTOCOL = TCP)(Host =".$this->hostname.")(Port = ".$this->hostport.")))(CONNECT_DATA = (SID = ".$this->sid.")))";
    }

    /**
     +----------------------------------------------------------
     * 连接数据库方法
     * 
     +----------------------------------------------------------
     * @access public 
     +----------------------------------------------------------
     * @throws FcsException
     +----------------------------------------------------------
     */
    Function connect() {
        if ( !$this->linkID ) {
            $conn = $this->pconnect ? 'oci_pconnect':'oci_connect';
            $this->linkID = $conn($this->username, $this->password,$this->database);
            if ( !$this->linkID) {
                throw_exception($this->error());
                Return False;
            }
            $this->dbVersion = OCI_Server_Version($this->linkID);
            unset($this->username,$this->password,$this->database,$this->hostname,$this->hostport);
        }
        Return $this->linkID;
    }

    /**
     +----------------------------------------------------------
     * 释放查询结果
     +----------------------------------------------------------
     * @access public 
     +----------------------------------------------------------
     */
    Function free() {
        @oci_free_statement($this->queryID);
        $this->queryID = 0;
    }

    /**
     +----------------------------------------------------------
     * 执行查询 主要针对 SELECT, SHOW 等指令
     * 返回数据集
     +----------------------------------------------------------
     * @access public 
     +----------------------------------------------------------
     * @param string $str  sql指令
     +----------------------------------------------------------
     * @return resultSet
     +----------------------------------------------------------
     * @throws FcsException
     +----------------------------------------------------------
     */
    Function _query($str='') {
        if ( !$this->linkID ) Return False;
        if ( $str != '' ) $this->queryStr = $str;
        if ($this->autoCommit) {
            //释放前次的查询结果
            if ( $this->queryID ) {    $this->free();    }
        }
        $this->escape_string($this->queryStr);
        $this->queryTimes ++;
        if ( $this->debug ) Log::Write(" SQL = ".$this->queryStr,WEB_LOG_DEBUG);
        $this->queryID = @OCI_Parse($this->linkID,$this->queryStr);
        if ( !$this->queryID ) {
            throw_exception($this->error());
            Return False;
        } else {
            if(!$this->autoCommit && $this->isMainIps($this->queryStr)){
                $result = OCI_Execute($this->queryID,OCI_DEFAULT);
                $this->transTimes++;
            }else {
                $result = OCI_Execute($this->queryID,OCI_COMMIT_ON_SUCCESS);
            }
            if(!$result){
                throw_exception($this->error());
                Return False;
            }
            $this->numRows = oci_fetch_all($this->queryID,$this->resultSet);
            $this->numCols = oci_num_fields($this->queryID);
            $this->resultSet = $this->getAll();
            Return new resultSet($this->resultSet);
        }
    }

    /**
     +----------------------------------------------------------
     * 执行语句 针对 INSERT, UPDATE 以及DELETE
     * 
     +----------------------------------------------------------
     * @access public 
     +----------------------------------------------------------
     * @param string $str  sql指令
     +----------------------------------------------------------
     * @return integer
     +----------------------------------------------------------
     * @throws FcsException
     +----------------------------------------------------------
     */
    Function _execute($str='') {
        if ( !$this->linkID ) Return False;
        if ( $str != '' ) $this->queryStr = $str;
        if ($this->autoCommit) {
            //释放前次的查询结果
            if ( $this->queryID ) {    $this->free();    }
        }
        $this->escape_string($this->queryStr);
        $this->writeTimes ++;
        if ( $this->debug ) Log::Write(" SQL = ".$this->queryStr,WEB_LOG_DEBUG);
        if ( !@OCI_Parse($this->linkID,$this->queryStr) ) {
            throw_exception($this->error());
            Return False;
        } else {
            if(!$this->autoCommit && $this->isMainIps($this->queryStr)){
                $result = OCI_Execute($this->queryID,OCI_DEFAULT);
                $this->transTimes++;
            }else {
                $result = OCI_Execute($this->queryID,OCI_COMMIT_ON_SUCCESS);
            }
            if(!$result){
                throw_exception($this->error());
                Return False;
            }
            $this->numRows = oci_num_rows($this->linkID);
            Return $this->numRows;
        }
    }

    /**
     +----------------------------------------------------------
     * 用于非自动提交状态下面的查询提交
     * 
     +----------------------------------------------------------
     * @access public 
     +----------------------------------------------------------
     * @return boolen
     +----------------------------------------------------------
     * @throws FcsException
     +----------------------------------------------------------
     */
    function commit()
    {
        if ($this->transTimes > 0) {
            $result = @oci_commit($this->linkID);
            $this->transTimes = 0;
            if(!$result){
                throw_exception($this->error());
                return False;
            }
        }
        return true;
    }

    /**
     +----------------------------------------------------------
     * 事务回滚
     * 
     +----------------------------------------------------------
     * @access public 
     +----------------------------------------------------------
     * @return boolen
     +----------------------------------------------------------
     * @throws FcsException
     +----------------------------------------------------------
     */
    function rollback()
    {
        if ($this->transTimes > 0) {
            $result = @oci_rollback($this->linkID);
            $this->transTimes = 0;
            if(!$result){
                throw_exception($this->error());
                return False;
            }
        }
        return True;
    }

    /**
     +----------------------------------------------------------
     * 获得下一条查询结果 简易数据集获取方法
     * 查询结果放到 result 数组中
     +----------------------------------------------------------
     * @access public 
     +----------------------------------------------------------
     * @return boolen
     +----------------------------------------------------------
     * @throws FcsException
     +----------------------------------------------------------
     */
    Function next() {
        if ( !$this->queryID ) {
            throw_exception($this->error());
            Return False;
        }
        if($this->resultType==1){
            // 返回对象集
            $this->result = @oci_fetch_object($this->queryID);
            $stat = is_object($this->result);
        }else{
            // 返回数组集
            $this->result = @oci_fetch_assoc($this->queryID);
            $stat = is_array($this->result);
        }
        Return $stat;
    }

    /**
     +----------------------------------------------------------
     * 获得一条查询结果
     * 
     +----------------------------------------------------------
     * @access public 
     +----------------------------------------------------------
     * @param index $seek 指针位置
     * @param string $str  SQL指令
     +----------------------------------------------------------
     * @return array
     +----------------------------------------------------------
     * @throws FcsException
     +----------------------------------------------------------
     */
    Function getRow($sql = NULL) {
        if (!empty($sql)) $this->_query($sql);
        if ( !$this->queryID ) {
            throw_exception($this->error());
            Return False;
        }
        if($this->numRows >0) {
            if($this->resultType==1){
                //返回对象集
                $result = @oci_fetch_object($this->queryID);
            }else{
                // 返回数组集
                $result = @oci_fetch_assoc($this->queryID);
            }
            Return $result;
        }else {
        	return false;
        }
    }

    /**
     +----------------------------------------------------------
     * 获得所有的查询数据
     * 查询结果放到 resultSet 数组中
     +----------------------------------------------------------
     * @access public 
     +----------------------------------------------------------
     * @param string $resultType  数据集类型
     +----------------------------------------------------------
     * @return array
     +----------------------------------------------------------
     * @throws FcsException
     +----------------------------------------------------------
     */
    function getAll($resultType=NULL) {
        if ( !$this->queryID ) {
            throw_exception($this->error());
            Return False;
        }
        //返回数据集
        $result = array();
        if($this->numRows >0) {
            if(!is_null($resultType)){ $resultType   =  $this->resultType ; }
            for($i=0;$i<$this->numRows ;$i++ ){
                if($resultType==1){
                    //返回对象集
                    $result[$i] = @oci_fetch_object($this->queryID);
                }else{
                    // 返回数组集
                    $result[$i] = @oci_fetch_assoc($this->queryID);
                }
            }
        }
        Return $result;
    }

    /**
     +----------------------------------------------------------
     * 关闭数据库
     +----------------------------------------------------------
     * @access public 
     +----------------------------------------------------------
     * @throws FcsException
     +----------------------------------------------------------
     */
    function close() { 
        if (!empty($this->queryID))
            $this->free();
        if (!@OCI_close($this->linkID)){
            throw_exception($this->error());
        }
        $this->linkID = 0;
    } 

    /**
     +----------------------------------------------------------
     * 数据库错误信息
     * 并显示当前的SQL语句
     +----------------------------------------------------------
     * @access public 
     +----------------------------------------------------------
     * @return string
     +----------------------------------------------------------
     * @throws FcsException
     +----------------------------------------------------------
     */
    function error() {
        $this->error = OCI_Error($this->linkID);
        $this->error = $this->error["message"];
        if($this->queryStr!=''){
            $this->error .= "\n [ SQL语句 ] : ".$this->queryStr;
        }
        return $this->error;
    }

    /**
     +----------------------------------------------------------
     * SQL指令安全过滤
     * 
     +----------------------------------------------------------
     * @access public 
     +----------------------------------------------------------
     * @param string $str  SQL指令
     +----------------------------------------------------------
     * @return string
     +----------------------------------------------------------
     * @throws FcsException
     +----------------------------------------------------------
     */
    function escape_string(&$str) { 
        $str = str_replace("&quot;", "\"", $str);
        $str = str_replace("&lt;", "<", $str);
        $str = str_replace("&gt;", ">", $str);
        $str = str_replace("&amp;", "&", $str);

    } 

}//类定义结束
?>