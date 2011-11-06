<?php
// +----------------------------------------------------------------------
// | TOPThink [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2010 http://topthink.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------
// $Id$


/**
 +------------------------------------------------------------------------------
 * TOPThink MongoModel模型类
 * 实现了ODM和ActiveRecords模式
 +------------------------------------------------------------------------------
 * @category   Think
 * @package  Think
 * @subpackage  Core
 * @author    liu21st <liu21st@gmail.com>
 * @version   $Id$
 +------------------------------------------------------------------------------
 */
class MongoModel extends Model{
    // 主键类型
    const TYPE_OBJECT = 1; 
    const TYPE_INT = 2;
    const TYPE_STRING = 3;

    // 主键名称
    protected $pk  = '_id';
    // _id 类型 1 Object 采用MongoId对象 2 Int 整形 支持自动增长 3 String 字符串Hash
    protected $_idType  =  self::TYPE_OBJECT;
    // 主键是否自动增长 支持Int型主键
    protected $_autoInc =  false;

    /**
     +----------------------------------------------------------
     * 利用__call方法实现一些特殊的Model方法
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
        if(in_array(strtolower($method),array('field','table','where','order','limit','page'),true)) {
            // 连贯操作的实现
            $this->options[strtolower($method)] =   $args[0];
            return $this;
        }elseif(strtolower(substr($method,0,5))=='getby') {
            // 根据某个字段获取记录
            $field   =   parse_name(substr($method,5));
            $where[$field] =$args[0];
            return $this->where($where)->find();
        }elseif(strtolower(substr($method,0,10))=='getfieldby') {
            // 根据某个字段获取记录的某个值
            $name   =   parse_name(substr($method,10));
            $where[$name] =$args[0];
            return $this->where($where)->getField($args[1]);
        }else{
            throw_exception(__CLASS__.':'.$method.L('_METHOD_NOT_EXIST_'));
            return;
        }
    }

    /**
     +----------------------------------------------------------
     * 获取字段信息并缓存 主键和自增信息直接配置
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @return void
     +----------------------------------------------------------
     */
    public function flush() {
        // 缓存不存在则查询数据表信息
        $fields =   $this->db->getFields();
        if(!$fields) { // 暂时没有数据无法获取字段信息 下次查询
            return false;
        }
        $this->fields   =   array_keys($fields);
        foreach ($fields as $key=>$val){
            // 记录字段类型
            $type[$key]    =   $val['type'];
        }
        // 记录字段类型信息
        if(C('DB_FIELDTYPE_CHECK'))   $this->fields['_type'] =  $type;

        // 2008-3-7 增加缓存开关控制
        if(C('DB_FIELDS_CACHE')){
            // 永久缓存数据表信息
            $db   =  $this->dbName?$this->dbName:C('DB_NAME');
            F('_fields/'.$db.'.'.$this->name,$this->fields);
        }
    }

    // 写入数据前的回调方法 包括新增和更新
    protected function _before_write(&$data) {
        $pk   =  $this->getPk();
        // 根据主键类型处理主键数据
        if(isset($data[$pk]) && $this->_idType == self::TYPE_OBJECT) {
            $data[$pk] =  new MongoId($data[$pk]);
        }    
    }

    /**
     +----------------------------------------------------------
     * count统计 配合where连贯操作
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @return integer
     +----------------------------------------------------------
     */
    public function count(){
        // 分析表达式
        $options =  parent::_parseOptions();
        return $this->db->count($options);
    }

    /**
     +----------------------------------------------------------
     * 获取下一ID 用于自动增长型
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @param string $pk 字段名 默认为主键
     +----------------------------------------------------------
     * @return mixed
     +----------------------------------------------------------
     */
    public function getMongoNextId($pk=''){
        if(empty($pk)) {
            $pk   =  $this->getPk();
        }
        return $this->db->mongo_next_id($pk);
    }

    // 插入数据前的回调方法
    protected function _before_insert(&$data,$options) {
        // 写入数据到数据库
        if($this->_autoInc && $this->_idType== self::TYPE_INT) { // 主键自动增长
            $pk   =  $this->getPk();
            if(!isset($data[$pk])) {
                $data[$pk]   =  $this->db->mongo_last_id($pk);
            }
        }
    }

    public function clear(){
        return $this->db->clear();
    }

    // 查询成功后的回调方法
    protected function _after_select(&$resultSet,$options) {
        $resultSet   =  iterator_to_array($resultSet);
        array_walk($resultSet,array($this,'checkMongoId'));
    }

    /**
     +----------------------------------------------------------
     * 获取MongoId
     +----------------------------------------------------------
     * @access protected
     +----------------------------------------------------------
     * @param array $result 返回数据
     +----------------------------------------------------------
     * @return array
     +----------------------------------------------------------
     */
    protected function checkMongoId(&$result){
        if(is_object($result['_id'])) {
            $result['_id'] = $result['_id']->__toString();
        }
        return $result;
    }

    // 表达式过滤回调方法
    protected function _options_filter(&$options) {
        $id = $this->getPk();
        if(isset($options['where'][$id]) && $this->_idType== self::TYPE_OBJECT) {
            $options['where'][$id] = new MongoId($options['where'][$id]);
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
         if( is_numeric($options) || is_string($options)) {
            $id   =  $this->getPk();
            $where[$id] = $options;
            $options = array();
            $options['where'] = $where;
         }
        // 分析表达式
        $options =  parent::_parseOptions($options);
        $result = $this->db->find($options);
        if(false === $result) {
            return false;
        }
        if(empty($result)) {// 查询结果为空
            return null;
        }else{
            $this->checkMongoId($result);
        }
        $this->data = $result;
        $this->_after_find($this->data,$options);
        return $this->data;
     }

    /**
     +----------------------------------------------------------
     * 字段值增长
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @param string $field  字段名
     * @param mixed $condition  条件
     * @param integer $step  增长值
     +----------------------------------------------------------
     * @return boolean
     +----------------------------------------------------------
     */
    public function setInc($field,$condition='',$step=1) {
        return $this->setField($field,array('inc',$step),$condition);
    }

    /**
     +----------------------------------------------------------
     * 字段值减少
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @param string $field  字段名
     * @param mixed $condition  条件
     * @param integer $step  减少值
     +----------------------------------------------------------
     * @return boolean
     +----------------------------------------------------------
     */
    public function setDec($field,$condition='',$step=1) {
        return $this->setField($field,array('inc','-'.$step),$condition);
    }

    /**
     +----------------------------------------------------------
     * 获取一条记录的某个字段值
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @param string $field  字段名
     * @param mixed $condition  查询条件
     * @param string $spea  字段数据间隔符号
     +----------------------------------------------------------
     * @return mixed
     +----------------------------------------------------------
     */
    public function getField($field,$condition='',$sepa=' ') {
        if(empty($condition) && isset($this->options['where']))
            $condition   =  $this->options['where'];
        $options['where'] =  $condition;
        $options['field']    =  $field;
        $options =  parent::_parseOptions($options);
        if(strpos($field,',')) { // 多字段
            $resultSet = $this->db->select($options);
            if(!empty($resultSet)) {
                $field  =   explode(',',$field);
                $key =  array_shift($field);
                $cols   =   array();
                foreach ($resultSet as $result){
                    $name   = $result[$key];
                    $cols[$name] =  '';
                    foreach ($field as $val)
                        $cols[$name] .=  $result[$val].$sepa;
                    $cols[$name]  = substr($cols[$name],0,-strlen($sepa));
                }
                return $cols;
            }
        }else{   // 查找一条记录
            $result = $this->db->find($options);
            if(!empty($result)) {
                return $result[$field];
            }
        }
        return null;
    }

    /**
     +----------------------------------------------------------
     * 执行Mongo指令
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @param string $command  指令
     +----------------------------------------------------------
     * @return mixed
     +----------------------------------------------------------
     */
    public function command($command) {
        return $this->db->command($command);
    }

    /**
     +----------------------------------------------------------
     * 执行MongoCode指令
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @param string $code  MongoCode指令
     * @param array $args   参数
     +----------------------------------------------------------
     * @return mixed
     +----------------------------------------------------------
     */
    public function execute($code,$args=array()) {
        return $this->db->execute($code,$args);
    }

    /**
     +----------------------------------------------------------
     * 切换当前的数据库连接
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @param integer $linkNum  连接序号
     * @param mixed $config  数据库连接信息
     * @param array $params  模型参数
     +----------------------------------------------------------
     * @return Model
     +----------------------------------------------------------
     */
    public function db($linkNum,$config='',$params=array()){
        static $_db = array();
        if(!isset($_db[$linkNum])) {
            // 创建一个新的实例
            $_db[$linkNum]            =    Db::getInstance($config);
        }elseif(NULL === $config){
            $_db[$linkNum]->close(); // 关闭数据库连接
            unset($_db[$linkNum]);
            return ;
        }
        if(!empty($params)) {
            if(is_string($params))    parse_str($params,$params);
            foreach ($params as $name=>$value){
                $this->setProperty($name,$value);
            }
        }
        // 切换数据库连接
        $this->db   =    $_db[$linkNum];
        // 切换Collection
        $this->db->switchCollection($this->getTableName(),$this->dbName?$this->dbName:C('db_name'));
        return $this;
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
    public function getTableName() {
        if(empty($this->trueTableName)) {
            $tableName  = !empty($this->tablePrefix) ? $this->tablePrefix : '';
            if(!empty($this->tableName)) {
                $tableName .= $this->tableName;
            }else{
                $tableName .= parse_name($this->name);
            }
            $this->trueTableName    =   strtolower($tableName);
        }
        return $this->trueTableName;
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
        return $this->pk;
    }

};
?>