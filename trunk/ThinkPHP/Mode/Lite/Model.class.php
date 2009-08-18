<?php
// +----------------------------------------------------------------------
// | ThinkPHP
// +----------------------------------------------------------------------
// | Copyright (c) 2009 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------
// $Id$

/**
 +------------------------------------------------------------------------------
 * ThinkPHP 精简模式Model模型类
 * 只支持CURD和连贯操作 以及常用查询 去掉回调接口
 +------------------------------------------------------------------------------
 */
class Model extends Think
{
    // 当前数据库操作对象
    protected $db = null;
    // 主键名称
    protected $pk  = 'id';
    // 数据表前缀
    protected $tablePrefix  =   '';
    // 模型名称
    protected $name = '';
    // 数据库名称
    protected $dbName  = '';
    // 数据表名（不包含表前缀）
    protected $tableName = '';
    // 实际数据表名（包含表前缀）
    protected $trueTableName ='';
    // 数据信息
    protected $data =   array();
    // 查询表达式参数
    protected $options  =   array();
    // 数据列表信息
    protected $dataList =   array();
    // 最近错误信息
    protected $error = '';

    /**
     +----------------------------------------------------------
     * 架构函数
     * 取得DB类的实例对象
     +----------------------------------------------------------
     * @param string $name 模型名称
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     */
    public function __construct($name='')
    {
        // 模型初始化
        $this->_initialize();
        // 获取模型名称
        if(!empty($name)) {
            $this->name   =  $name;
        }elseif(empty($this->name)){
            $this->name =   $this->getModelName();
        }
        // 数据库初始化操作
        import("Db");
        // 获取数据库操作对象
        $this->db = Db::getInstance(empty($this->connection)?'':$this->connection);
        // 设置表前缀
        $this->tablePrefix = $this->tablePrefix?$this->tablePrefix:C('DB_PREFIX');
        // 字段检测
        if(!empty($this->name))    $this->_checkTableInfo();
    }

    /**
     +----------------------------------------------------------
     * 自动检测数据表信息
     +----------------------------------------------------------
     * @access protected
     +----------------------------------------------------------
     * @return void
     +----------------------------------------------------------
     */
    protected function _checkTableInfo() {
        // 如果不是Model类 自动记录数据表信息
        // 只在第一次执行记录
        if(empty($this->fields)) {
            // 如果数据表字段没有定义则自动获取
            if(C('DB_FIELDS_CACHE')) {
                $this->fields = F('_fields/'.$this->name);
                if(!$this->fields)   $this->flush();
            }else{
                // 每次都会读取数据表信息
                $this->flush();
            }
        }
    }

    /**
     +----------------------------------------------------------
     * 获取字段信息并缓存
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @return void
     +----------------------------------------------------------
     */
    public function flush() {
        // 缓存不存在则查询数据表信息
        $fields =   $this->db->getFields($this->getTableName());
        $this->fields   =   array_keys($fields);
        $this->fields['_autoinc'] = false;
        foreach ($fields as $key=>$val){
            // 记录字段类型
            $type[$key]    =   $val['type'];
            if($val['primary']) {
                $this->fields['_pk'] = $key;
                if($val['autoinc']) $this->fields['_autoinc']   =   true;
            }
        }
        // 记录字段类型信息
        if(C('FIELD_TYPE_CHECK'))   $this->fields['_type'] =  $type;

        // 2008-3-7 增加缓存开关控制
        if(C('DB_FIELDS_CACHE'))
            // 永久缓存数据表信息
            F('_fields/'.$this->name,$this->fields);
    }

    // 回调方法 初始化模型
    protected function _initialize() {}
    /**
     +----------------------------------------------------------
     * 利用__call方法实现一些特殊的Model方法 （魔术方法）
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @param string $method 方法名称
     * @param array $args 调用参数
     +----------------------------------------------------------
     * @return mixed
     +----------------------------------------------------------
     */
    public function __call($method,$args) {
        if(in_array(strtolower($method),array('field','table','where','order','limit','page','having','group','lock','distinct'),true)) {
            // 连贯操作的实现
            $this->options[strtolower($method)] =   $args[0];
            return $this;
        }elseif(in_array(strtolower($method),array('count','sum','min','max','avg'),true)){
            // 统计查询的实现
            $field =  isset($args[0])?$args[0]:'*';
            return $this->getField($method.'('.$field.') AS tp_'.$method);
        }elseif(strtolower(substr($method,0,5))=='getby') {
            // 根据某个字段获取记录
            $field   =   parse_name(substr($method,5));
            $options['where'] =  $field.'=\''.$args[0].'\'';
            return $this->find($options);
        }else{
            throw_exception(__CLASS__.':'.$method.L('_METHOD_NOT_EXIST_'));
            return;
        }
    }

    /**
     +----------------------------------------------------------
     * 设置数据对象的值 （魔术方法）
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @param string $name 名称
     * @param mixed $value 值
     +----------------------------------------------------------
     * @return void
     +----------------------------------------------------------
     */
    public function __set($name,$value) {
        // 设置数据对象属性
        $this->data[$name]  =   $value;
    }

    /**
     +----------------------------------------------------------
     * 获取数据对象的值 （魔术方法）
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @param string $name 名称
     +----------------------------------------------------------
     * @return mixed
     +----------------------------------------------------------
     */
    public function __get($name) {
        return isset($this->data[$name])?$this->data[$name]:null;
    }

    /**
     +----------------------------------------------------------
     * 新增数据
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @param mixed $data 数据
     * @param array $options 表达式
     +----------------------------------------------------------
     * @return mixed
     +----------------------------------------------------------
     */
    public function add($data='',$options=array()) {
        if(empty($data)) {
            // 没有传递数据，获取当前数据对象的值
            if(!empty($this->data)) {
                $data    =   $this->data;
            }else{
                $this->error = L('_DATA_TYPE_INVALID_');
                return false;
            }
        }
        // 分析表达式
        $options =  $this->_parseOptions($options);
        // 写入数据到数据库
        if(false === ($result = $this->db->insert($data,$options))){
            // 数据库插入操作失败
            $this->error = L('_OPERATION_WRONG_');
            return false;
        }else {
            $insertId   =   $this->getLastInsID();
            if($insertId) {
                return $insertId;
            }
            //成功后返回插入ID
            return $result;
        }
    }

    /**
     +----------------------------------------------------------
     * 保存数据
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @param mixed $data 数据
     * @param array $options 表达式
     +----------------------------------------------------------
     * @return boolean
     +----------------------------------------------------------
     */
    public function save($data='',$options=array()) {
        if(empty($data)) {
            // 没有传递数据，获取当前数据对象的值
            if(!empty($this->data)) {
                $data    =   $this->data;
            }else{
                $this->error = L('_DATA_TYPE_INVALID_');
                return false;
            }
        }
        // 分析表达式
        $options =  $this->_parseOptions($options);
        if(!isset($options['where']) ) {
            // 如果存在主键数据 则自动作为更新条件
            if(isset($data[$this->getPk()])) {
                $pk   =  $this->getPk();
                $options['where']  =  $pk.'=\''.$data[$pk].'\'';
                $pkValue = $data[$pk];
                unset($data[$pk]);
            }else{
                // 如果没有任何更新条件则不执行
                $this->error = L('_OPERATION_WRONG_');
                return false;
            }
        }
        if(false === ($result = $this->db->update($data,$options))){
            $this->error = L('_OPERATION_WRONG_');
            return false;
        }else {
            return $result;
        }
    }

    /**
     +----------------------------------------------------------
     * 删除数据
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @param mixed $options 表达式
     +----------------------------------------------------------
     * @return mixed
     +----------------------------------------------------------
     */
    public function delete($options=array()) {
        if(empty($options) && empty($this->options)) {
            // 如果删除条件为空 则删除当前数据对象所对应的记录
            if(!empty($this->data) && isset($this->data[$this->getPk()]))
                return $this->delete($this->data[$this->getPk()]);
            else
                return false;
        }
        if(is_numeric($options)  || is_string($options)) {
            // 根据主键删除记录
            $pk   =  $this->getPk();
            $where  =  $pk.'=\''.$options.'\'';
            $pkValue = $options;
            $options =  array();
            $options['where'] =  $where;
        }
        // 分析表达式
        $options =  $this->_parseOptions($options);
        $result=    $this->db->delete($options);
        if(false === $result ){
            $this->error =  L('_OPERATION_WRONG_');
            return false;
        }else {
            // 返回删除记录个数
            return $result;
        }
    }

    /**
     +----------------------------------------------------------
     * 查询数据集
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @param array $options 表达式参数
     +----------------------------------------------------------
     * @return mixed
     +----------------------------------------------------------
     */
    public function select($options=array()) {
        // 分析表达式
        $options =  $this->_parseOptions($options);
        if($resultSet = $this->db->select($options)) {
            $this->dataList = $resultSet;
            return $resultSet;
        }else{
            return false;
        }
    }

    /**
     +----------------------------------------------------------
     * 查询数据
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @param mixed $options 表达式参数
     +----------------------------------------------------------
     * @return mixed
     +----------------------------------------------------------
     */
     public function find($options=array()) {
         if(is_numeric($options) || is_string($options)) {
             $where = $this->getPk().'=\''.$options.'\'';
             $options = array();
             $options['where'] = $where;
         }
         // 总是查找一条记录
        $options['limit'] = 1;
        // 分析表达式
        $options =  $this->_parseOptions($options);
        if($result = $this->db->select($options)) {
            $this->data = $result[0];
            return $this->data;
        }else{
            return false;
        }
     }

    /**
     +----------------------------------------------------------
     * 分析表达式
     +----------------------------------------------------------
     * @access private
     +----------------------------------------------------------
     * @param array $options 表达式参数
     +----------------------------------------------------------
     * @return array
     +----------------------------------------------------------
     */
    private function _parseOptions($options) {
        if(is_array($options))
            $options =  array_merge($this->options,$options);
        // 查询过后清空sql表达式组装 避免影响下次查询
        $this->options  =   array();
        if(!isset($options['table']))
            // 自动获取表名
            $options['table'] =$this->getTableName();
        return $options;
    }

    /**
     +----------------------------------------------------------
     * SQL查询
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @param mixed $sql  SQL指令
     +----------------------------------------------------------
     * @return array
     +----------------------------------------------------------
     */
    public function query($sql)
    {
        if(is_array($sql)) {
            return $this->patchQuery($sql);
        }
        if(!empty($sql)) {
            if(strpos($sql,'__TABLE__')) {
                $sql    =   str_replace('__TABLE__',$this->getTableName(),$sql);
            }
            return $this->db->query($sql);
        }else{
            return false;
        }
    }

    /**
     +----------------------------------------------------------
     * 执行SQL语句
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @param string $sql  SQL指令
     +----------------------------------------------------------
     * @return false | integer
     +----------------------------------------------------------
     */
    public function execute($sql='')
    {
        if(!empty($sql)) {
            if(strpos($sql,'__TABLE__')) {
                $sql    =   str_replace('__TABLE__',$this->getTableName(),$sql);
            }
            $result =   $this->db->execute($sql);
            return $result;
        }else {
            return false;
        }
    }

    /**
     +----------------------------------------------------------
     * 得到当前的数据对象名称
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @return string
     +----------------------------------------------------------
     */
    public function getModelName()
    {
        if(empty($this->name)) {
            $this->name =   substr(get_class($this),0,-5);
        }
        return $this->name;
    }

    /**
     +----------------------------------------------------------
     * 得到完整的数据表名
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @return string
     +----------------------------------------------------------
     */
    public function getTableName()
    {
        if(empty($this->trueTableName)) {
            $tableName  = !empty($this->tablePrefix) ? $this->tablePrefix : '';
            if(!empty($this->tableName)) {
                $tableName .= $this->tableName;
            }elseif(C('AUTO_NAME_IDENTIFY')){
                // 智能识别表名
                $tableName .= parse_name($this->name);
            }else{
                $tableName .= $this->name;
            }
            if(!empty($this->dbName)) {
                $tableName    =  $this->dbName.'.'.$tableName;
            }
            $this->trueTableName    =   strtolower($tableName);
        }
        return $this->trueTableName;
    }

    /**
     +----------------------------------------------------------
     * 启动事务
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @return void
     +----------------------------------------------------------
     */
    public function startTrans()
    {
        $this->commit();
        $this->db->startTrans();
        return ;
    }

    /**
     +----------------------------------------------------------
     * 提交事务
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @return boolean
     +----------------------------------------------------------
     */
    public function commit()
    {
        return $this->db->commit();
    }

    /**
     +----------------------------------------------------------
     * 事务回滚
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @return boolean
     +----------------------------------------------------------
     */
    public function rollback()
    {
        return $this->db->rollback();
    }
    /**
     +----------------------------------------------------------
     * 获取主键名称
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @return string
     +----------------------------------------------------------
     */
    public function getPk() {
        return isset($this->pk)?$this->pk:'id';
    }

    /**
     +----------------------------------------------------------
     * 返回最后执行的sql语句
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @return string
     +----------------------------------------------------------
     */
    public function getLastSql() {
        return $this->db->getLastSql();
    }
};
?>