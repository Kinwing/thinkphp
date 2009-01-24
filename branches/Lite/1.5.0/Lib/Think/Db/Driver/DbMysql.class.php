<?php
// +----------------------------------------------------------------------
// | ThinkPHP
// +----------------------------------------------------------------------
// | Copyright (c) 2008 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------
// $Id$

define('CLIENT_MULTI_RESULTS', 131072);
/**
 +------------------------------------------------------------------------------
 * Mysql数据库驱动类
 +------------------------------------------------------------------------------
 * @category   Think
 * @package  Think
 * @subpackage  Db
 * @author    liu21st <liu21st@gmail.com>
 * @version   $Id$
 +------------------------------------------------------------------------------
 */
class DbMysql extends Db{

    /**
     +----------------------------------------------------------
     * 架构函数 读取数据库配置信息
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @param array $config 数据库配置数组
     +----------------------------------------------------------
     */
    public function __construct($config=''){
        if ( !extension_loaded('mysql') ) {
            throw_exception(L('_NOT_SUPPERT_').':mysql');
        }
        if(!empty($config)) {
            $this->config   =   $config;
        }
    }

    /**
     +----------------------------------------------------------
     * 连接数据库方法
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @throws ThinkExecption
     +----------------------------------------------------------
     */
    public function connect($config='',$linkNum=0) {
        if ( !isset($this->linkID[$linkNum]) ) {
            if(empty($config))  $config =   $this->config;
            // 处理不带端口号的socket连接情况
            $host = $config['hostname'].($config['hostport']?":{$config['hostport']}":'');
            if($this->pconnect) {
                $this->linkID[$linkNum] = mysql_pconnect( $host, $config['username'], $config['password'],CLIENT_MULTI_RESULTS);
            }else{
                $this->linkID[$linkNum] = mysql_connect( $host, $config['username'], $config['password'],true,CLIENT_MULTI_RESULTS);
            }
            if ( !$this->linkID[$linkNum]) {
                throw_exception(mysql_error());
            }
            if (!empty($config['database']) && !mysql_select_db($config['database'], $this->linkID[$linkNum]) ) {
                throw_exception(mysql_error());
            }
            $dbVersion = mysql_get_server_info($this->linkID[$linkNum]);
            if ($dbVersion >= "4.1") {
                //使用UTF8存取数据库 需要mysql 4.1.0以上支持
                mysql_query("SET NAMES '".C('DB_CHARSET')."'", $this->linkID[$linkNum]);
            }
            //设置 sql_model
            if($dbVersion >'5.0.1'){
                mysql_query("SET sql_mode=''",$this->linkID[$linkNum]);
            }
            // 标记连接成功
            $this->connected    =   true;
            // 注销数据库连接配置信息
            if(1 != C('DB_DEPLOY_TYPE')) unset($this->config);
        }
        return $this->linkID[$linkNum];
    }

    /**
     +----------------------------------------------------------
     * 释放查询结果
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     */
    public function free() {
        @mysql_free_result($this->queryID);
        $this->queryID = 0;
    }

    /**
     +----------------------------------------------------------
     * 执行查询 主要针对 SELECT, SHOW 等指令
     * 返回数据集
     +----------------------------------------------------------
     * @access protected
     +----------------------------------------------------------
     * @param string $str  sql指令
     +----------------------------------------------------------
     * @return resultSet
     +----------------------------------------------------------
     * @throws ThinkExecption
     +----------------------------------------------------------
     */
    protected function _query($str='') {
        $this->initConnect(false);
        if ( !$this->_linkID ) return false;
        if ( $str != '' ) $this->queryStr = $str;
        //释放前次的查询结果
        if ( $this->queryID ) {    $this->free();    }
        $this->Q(1);
        $this->queryID = mysql_query($this->queryStr, $this->_linkID);
        $this->debug();
        if ( !$this->queryID ) {
            if ( $this->debug )
                throw_exception($this->error());
            else
                return false;
        } else {
            $this->numRows = mysql_num_rows($this->queryID);
            $this->resultSet = $this->getAll();
            return $this->resultSet;
        }
    }

    /**
     +----------------------------------------------------------
     * 执行语句 针对 INSERT, UPDATE 以及DELETE
     +----------------------------------------------------------
     * @access protected
     +----------------------------------------------------------
     * @param string $str  sql指令
     +----------------------------------------------------------
     * @return integer
     +----------------------------------------------------------
     * @throws ThinkExecption
     +----------------------------------------------------------
     */
    protected function _execute($str='') {
        $this->initConnect(true);
        if ( !$this->_linkID ) return false;
        if ( $str != '' ) $this->queryStr = $str;
        //释放前次的查询结果
        if ( $this->queryID ) {    $this->free();    }
        $this->W(1);
        $result =   mysql_query($this->queryStr, $this->_linkID) ;
        $this->debug();
        if ( false === $result) {
            if ( $this->debug )
                throw_exception($this->error());
            else
                return false;
        } else {
            $this->numRows = mysql_affected_rows($this->_linkID);
            $this->lastInsID = mysql_insert_id($this->_linkID);
            return $this->numRows;
        }
    }

    /**
     +----------------------------------------------------------
     * 插入记录
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @param mixed $data 数据
     * @param string $table  数据表名
     +----------------------------------------------------------
     * @return false | integer
     +----------------------------------------------------------
     */
    public function insert($data,$table) {
        foreach ($data as $key=>$val){
            $fields[] =  '`'.$key.'`';
            if(is_int($val)) {
                $values[]   =  intval($val);
            }elseif(is_float($val)){
                $values[]   =  floatval($val);
            }elseif(is_string($val)){
                $values[]  = '\''.$val.'\'';
            }elseif(is_null($val)){
                $values[]   =  'null';
            }
        }
        $fieldsStr    = implode(',', $fields);
        $valuesStr  = implode(',', $values);
        $sql   =  'INSERT INTO `'.$table.'` ('.$fieldsStr.') VALUES ('.$valuesStr.')';
        return $this->execute($sql);
    }

    /**
     +----------------------------------------------------------
     * 更新记录
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @param mixed $data 数据
     * @param array $options 表达式
     +----------------------------------------------------------
     * @return false | integer
     +----------------------------------------------------------
     */
    public function update($data,$options) {
        if(is_array($data)) {
            foreach ($data as $key=>$val){
                if(is_int($val)) {
                    $set[]   =  '`'.$key.'`='.intval($val);
                }elseif(is_float($val)){
                    $set[]   =  '`'.$key.'`='.floatval($val);
                }elseif(is_string($val)){
                    $set[]  = '`'.$key.'`=\''.$val.'\'';
                }elseif(is_null($val)){
                    $set[]   =  '`'.$key.'`=null';
                }elseif(is_array($val) && strtolower($val[0]) == 'exp') {
                    // 使用表达式
                    $set[]    =   '`'.$key.'`='.$val[1];
                }
            }
            $setStr  =  implode(',',$set);
        }elseif(is_string($data)){
            $setStr  =  $data;
        }
        $table = isset($options['table'])?$options['table']:'';
        $where  =  isset($options['where'])?$options['where']:'';
        $limit =  isset($options['limit'])?$options['limit']:'';
        $order   =  isset($options['order'])?$options['order']:'';
        $sql   =  'UPDATE `'.$table.'` SET '.$setStr;
        if(!empty($where)) {
            $sql   .= ' WHERE '.$where;
        }
        if(!empty($order)) {
            $sql   .= ' ORDER '.$order;
        }
        if(!empty($limit)) {
            $sql   .= ' LIMIT '.$limit;
        }
        return $this->execute($sql);
    }

    /**
     +----------------------------------------------------------
     * 删除记录
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @param array $options 表达式
     +----------------------------------------------------------
     * @return false | integer
     +----------------------------------------------------------
     */
    public function delete($options=array())
    {
        $table = isset($options['table'])?$options['table']:'';
        $where  =  isset($options['where'])?$options['where']:'';
        $limit =  isset($options['limit'])?$options['limit']:'';
        $order   =  isset($options['order'])?$options['order']:'';
        $sql   = 'DELETE FROM `'.$table.'`';
        if(!empty($where)) {
            $sql   .= ' WHERE '.$where;
        }
        if(!empty($order)) {
            $sql   .= ' ORDER '.$order;
        }
        if(!empty($limit)) {
            $sql   .= ' LIMIT '.$limit;
        }
        return $this->execute($sql);
    }

    /**
     +----------------------------------------------------------
     * 查找记录
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @param array $options 表达式
     +----------------------------------------------------------
     * @return array
     +----------------------------------------------------------
     */
    public function select($options=array()) {
        $table = isset($options['table'])?$options['table']:'';
        $field   =  isset($options['field'])?$options['field']:'*';
        $distinct   =  isset($options['distinct'])?$options['distinct']:false;
        $where  =  isset($options['where'])?$options['where']:'';
        $limit =  isset($options['limit'])?$options['limit']:'';
        $order   =  isset($options['order'])?$options['order']:'';
        $join   =  isset($options['join'])?$options['join']:'';
        $group   =  isset($options['group'])?$options['group']:'';
        $having   =  isset($options['having'])?$options['having']:'';
        $sql   = 'SELECT ';
        if($distinct) {
            $sql   .=  ' DISTINCT ';
        }
        $sql   .= $field.' FROM `'.$table.'`';
        if(!empty($join)) {
            if(is_array($join)) {
                foreach ($join as $key=>$_join){
                    if(false !== stripos($_join,'JOIN')) {
                        $sql .= ' '.$_join;
                    }else{
                        $sql .= ' LEFT JOIN ' .$_join;
                    }
                }
            }else{
                if(false !== stripos($join,'JOIN')) {
                    $sql .= ' '.$join;
                }else{
                    $sql .= ' LEFT JOIN ' .$join;
                }
            }
        }
        if(!empty($where)) {
            $sql   .= ' WHERE '.$where;
        }
        if(!empty($group)) {
            $sql   .= ' GROUP '.$group;
        }
        if(!empty($order)) {
            $sql   .= ' ORDER '.$order;
        }
        if(!empty($limit)) {
            $sql   .= ' LIMIT '.$limit;
        }
        return $this->query($sql);
    }

    /**
     +----------------------------------------------------------
     * 启动事务
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @return void
     +----------------------------------------------------------
     * @throws ThinkExecption
     +----------------------------------------------------------
     */
    public function startTrans() {
        $this->initConnect(true);
        if ( !$this->_linkID ) return false;
        //数据rollback 支持
        if ($this->transTimes == 0) {
            mysql_query('START TRANSACTION', $this->_linkID);
        }
        $this->transTimes++;
        return ;
    }

    /**
     +----------------------------------------------------------
     * 用于非自动提交状态下面的查询提交
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @return boolen
     +----------------------------------------------------------
     * @throws ThinkExecption
     +----------------------------------------------------------
     */
    public function commit()
    {
        if ($this->transTimes > 0) {
            $result = mysql_query('COMMIT', $this->_linkID);
            $this->transTimes = 0;
            if(!$result){
                throw_exception($this->error());
                return false;
            }
        }
        return true;
    }

    /**
     +----------------------------------------------------------
     * 事务回滚
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @return boolen
     +----------------------------------------------------------
     * @throws ThinkExecption
     +----------------------------------------------------------
     */
    public function rollback()
    {
        if ($this->transTimes > 0) {
            $result = mysql_query('ROLLBACK', $this->_linkID);
            $this->transTimes = 0;
            if(!$result){
                throw_exception($this->error());
                return false;
            }
        }
        return true;
    }

    /**
     +----------------------------------------------------------
     * 获得所有的查询数据
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @param string $sql  sql语句
     +----------------------------------------------------------
     * @return array
     +----------------------------------------------------------
     * @throws ThinkExecption
     +----------------------------------------------------------
     */
    public function getAll($sql = null) {
        if (!empty($sql)) $this->_query($sql);
        if ( !$this->queryID ) {
            throw_exception($this->error());
            return false;
        }
        //返回数据集
        $result = array();
        if($this->numRows >0) {
            while($row = mysql_fetch_assoc($this->queryID)){
                $result[]   =   $row;
            }
            mysql_data_seek($this->queryID,0);
        }
        return $result;
    }

    /**
     +----------------------------------------------------------
     * 关闭数据库
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @throws ThinkExecption
     +----------------------------------------------------------
     */
    public function close() {
        if (!empty($this->queryID))
            mysql_free_result($this->queryID);
        if (!mysql_close($this->_linkID)){
            throw_exception($this->error());
        }
        $this->_linkID = 0;
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
     */
    public function error() {
        $this->error = mysql_error($this->_linkID);
        if($this->queryStr!=''){
            $this->error .= "\n [ SQL语句 ] : ".$this->queryStr;
        }
        return $this->error;
    }

    /**
     +----------------------------------------------------------
     * SQL指令安全过滤
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @param string $str  SQL字符串
     +----------------------------------------------------------
     * @return string
     +----------------------------------------------------------
     */
    public function escape_string($str) {
        return mysql_escape_string($str);
    }

}//类定义结束
?>