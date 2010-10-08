<?php 
// +----------------------------------------------------------------------+
// | ThinkPHP                                                             |
// +----------------------------------------------------------------------+
// | Copyright (c) 2006 liu21st.com All rights reserved.                  |
// +----------------------------------------------------------------------+
// | Licensed under the Apache License, Version 2.0 (the 'License');      |
// | you may not use this file except in compliance with the License.     |
// | You may obtain a copy of the License at                              |
// | http://www.apache.org/licenses/LICENSE-2.0                           |
// | Unless required by applicable law or agreed to in writing, software  |
// | distributed under the License is distributed on an 'AS IS' BASIS,    |
// | WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or      |
// | implied. See the License for the specific language governing         |
// | permissions and limitations under the License.                       |
// +----------------------------------------------------------------------+
// | Author: liu21st <liu21st@gmail.com>                                  |
// +----------------------------------------------------------------------+
// $Id$

import("Think.Db.Db");
import("Think.Util.Cache");
import("Think.Core.VoList");

define('HAS_ONE',1);
define('BELONGS_TO',2);
define('HAS_MANY',3);
define('MANY_TO_MANY',4);

define('MUST_TO_VALIDATE',1);
define('SET_TO_VAILIDATE',0);

/**
 +------------------------------------------------------------------------------
 * 数据访问基础类
 +------------------------------------------------------------------------------
 * @package   core
 * @author    liu21st <liu21st@gmail.com>
 * @version   $Id$
 +------------------------------------------------------------------------------
 */
class Dao extends Base
{
    /**
     +----------------------------------------------------------
     * 数据库底层操作对象
     +----------------------------------------------------------
     * @var Db
     * @access protected
     +----------------------------------------------------------
     */
    var $db ;                

    /**
     +----------------------------------------------------------
     * 主键名
     +----------------------------------------------------------
     * @var string
     * @access protected
     +----------------------------------------------------------
     */
    var $pk =   'id';

    /**
     +----------------------------------------------------------
     * 主键是否自动增加
     +----------------------------------------------------------
     * @var string
     * @access protected
     +----------------------------------------------------------
     */
    var $autoIncrement =   true;

    /**
     +----------------------------------------------------------
     * 父键名
     +----------------------------------------------------------
     * @var string
     * @access protected
     +----------------------------------------------------------
     */
    var $cacheQuery =   true;

    // 返回元数据类型
    var $resultType =  '';

    /**
     +----------------------------------------------------------
     * 数据表前缀
     +----------------------------------------------------------
     * @var string
     * @access protected
     +----------------------------------------------------------
     */
    var $tablePrefix	=	'';            

    /**
     +----------------------------------------------------------
     * 数据表后缀
     +----------------------------------------------------------
     * @var string
     * @access protected
     +----------------------------------------------------------
     */
	var $tableSuffix = '';
    /**
     +----------------------------------------------------------
     * 数据表名
     +----------------------------------------------------------
     * @var string
     * @access protected
     +----------------------------------------------------------
     */
    var $tableName;            

    /**
     +----------------------------------------------------------
     * 真实表名
     +----------------------------------------------------------
     * @var string
     * @access protected
     +----------------------------------------------------------
     */
    var $realTableName;        

    /**
     +----------------------------------------------------------
     * 错误信息
     +----------------------------------------------------------
     * @var string
     * @access protected
     +----------------------------------------------------------
     */
    var $error;        

	// 乐观锁
	var $lock_optimistically = 'lock_version';

	
	var $auto_save_relations = false;		// 自动关联保存
	var $auto_delete_relations = false;	// 自动关联删除
	var $auto_add_relations = false;		// 自动关联增加

	// 自动写入时间戳
	var $auto_create_timestamps = array('create_at','create_on','cTime');
	var $auto_update_timestamps = array('update_at','update_on','mTime');

    /**
     +----------------------------------------------------------
     * 架构函数 取得DB类的实例对象
     * 
     +----------------------------------------------------------
     * @access public 
     +----------------------------------------------------------
     * @param string $tablePrefix 数据库前缀
     * @param string $tableName 数据表名
     * @param string $pk  主键名
     * @param boolean $autoIncrement  是否自动增长
     +----------------------------------------------------------
     */
    function __construct($tablePrefix='',$tableName='',$pk='',$autoIncrement=true,$resultType='')
    {
        $this->db = Db::getInstance();
        if(!empty($tablePrefix))  $this->tablePrefix    =   $tablePrefix;
		else $this->tablePrefix = C('DB_PREFIX');
        if(!empty($tableName))  $this->tableName    =   $tableName;
        if(!empty($pk))         $this->pk = $pk;
        if(!empty($resultType))         $this->resultType = $resultType;
		else $this->resultType = C('DATA_RESULT_TYPE');
        if(is_bool($autoIncrement))   $this->autoIncrement = $autoIncrement;
		
        //自动加载所需的Vo类文件
        // import(APP_NAME.'.Vo.'.$this->getVo());

        //如果存在initialize方法，就首先调用
        if(method_exists($this,'_initialize')) {
            $this->_initialize();
        }
    }


    /**
     +----------------------------------------------------------
     * 新增数据
     * 
     +----------------------------------------------------------
     * @access public 
     +----------------------------------------------------------
     * @param mixed $data 数据
     * @param string $table  数据表名
     * @param string $pk 主键名
     +----------------------------------------------------------
     * @return boolen
     +----------------------------------------------------------
     * @throws ThinkExecption
     +----------------------------------------------------------
     */
    function add($data,$table=NULL,$pk='')
    {
        if(is_array($data)){
            $map = new HashMap($data);
        }else if(is_instance_of($data,'Vo')){
            $map = $data->toMap();
        }else if(is_instance_of($data,'HashMap')){
            $map = $data;
        }else {
            $this->error = L('_DATA_TYPE_INVALID_');
            return false;
        }
        if($this->autoIncrement) {
            //如果主键为自动增长类型
            //删除主键属性 由数据库自动生成
            $map->remove($pk?$pk:$this->pk); 
        }
		// 增加对数据库映射字段和属性不同的支持
		$map	=	$this->dealMap($map);
        $table = empty($table)?$this->getRealTableName():$table;

		// 记录乐观锁
		if($this->lock_optimistically && !$map->get($this->lock_optimistically) ) {
			$voClass = $this->getVo();
			$vo =  V($voClass);
			if(property_exists($vo,$this->lock_optimistically)) {
				$map->put($this->lock_optimistically,0);
			}
		}

        if(FALSE === $this->db->add($map,$table)){
            $this->error = L('_OPERATION_WRONG_');
            return false;
        }else {
			$insertId	=	$this->db->getLastInsID();
			// 保存关联记录
			if ($this->auto_add_relations){
				if(empty($pk)) $pk	=	$this->pk;
				if(is_array($data)) {
					$data[$pk]	 =	 $insertId;
				}else{
					$data->$pk = $insertId;
				}
				$this->opRelation('ADD',$data);
			}
            //成功后返回插入ID
            return $insertId;
        }
    }

	// 对保存到数据库的Map对象进行处理
	// 增加对数据库映射字段和属性不同的支持
	function dealMap($map,$vo=NULL) {
		if(empty($vo)) {
			$voClass = $this->getVo();
			$vo = V($voClass);
		}
		if(isset($vo->_map)) {
			foreach ($vo->_map as $key=>$val){
				$map->put($val,$map->get($key));
				$map->remove($key);
			}
		}
		return $map;
	}

    /**
     +----------------------------------------------------------
     * 批量新增数据
     * 
     +----------------------------------------------------------
     * @access public 
     +----------------------------------------------------------
     * @param mixed $dataList 数据
     * @param string $table  数据表名
     * @param string $pk 主键名
     +----------------------------------------------------------
     * @return boolen
     +----------------------------------------------------------
     * @throws ThinkExecption
     +----------------------------------------------------------
     */
    function addAll($dataList,$table=null,$pk='')
    {
        if(is_object($dataList) && is_instance_of($dataList,'VoList')){
            $dataList = $dataList->getIterator();
        }elseif(!is_array($dataList)) {
            $this->error = L('_DATA_TYPE_INVALID_');
			return false;
		}
        //启用事务操作
        $this->startTrans();
        foreach ($dataList as $data){
            $this->add($data,$table,$pk);
        }
        //提交
        $this->commit();
        return true;
    }

    /**
     +----------------------------------------------------------
     * 更新数据
     * 
     +----------------------------------------------------------
     * @access public 
     +----------------------------------------------------------
     * @param mixed $data 要更新的数据
     * @param string $table  数据表名
     * @param mixed $where 更新数据的条件
     * @param string $pk 主键名
     * @param integer $limit 要更新的记录数
     * @param string $order  更新的顺序
     +----------------------------------------------------------
     * @return boolen
     +----------------------------------------------------------
     * @throws ThinkExecption
     +----------------------------------------------------------
     */
    function save($data,$table=NULL,$where='',$limit=0,$order='',$pk='')
    {
        if(is_array($data)){
            $map = new HashMap($data);
        }
        elseif(is_instance_of($data,'Vo')){
            $map = $data->toMap();
        }
        elseif(is_instance_of($data,'HashMap')) {
            $map    = $data;
        }
		elseif(is_instance_of($data,'VoList')){
			//启用事务操作
			$this->startTrans();
			foreach ($data as $val){
				$result   =  $this->save($val,$table);
			}
			$this->commit();
			return $result;
		}
        else {
            $this->error = L('_DATA_TYPE_INVALID_');
            return false;
        }
		
        $pk     = $pk?$pk:$this->pk;
        if($map->containsKey($pk)) {
			$id	=	$map->get($pk);
            $where  = $pk."=".$id;
            $map->remove($pk);         	
        }
		// 增加对数据库映射字段和属性不同的支持
		$map	=	$this->dealMap($map);		
        $table = empty($table)?$this->getRealTableName():$table;

		// 检查乐观锁
		$guid   = strtoupper($this->getVo()).'_'.$id;
		if($this->lock_optimistically && Session::is_set($guid.'_lock_version')) {
			$lock_version = Session::get($guid.'_lock_version');
			if(!empty($where)) {
				$vo = $this->find($where,$table,$this->lock_optimistically);
			}else {
				$vo = $this->find($map,$table,$this->lock_optimistically);
			}
			Session::set($guid.'_lock_version',$lock_version);
			$curr_version = is_array($vo)?$vo[$this->lock_optimistically]:$vo->{$this->lock_optimistically};
			if(isset($curr_version)) {
				if($curr_version>0 && $lock_version != $curr_version) {
					// 记录已经更新
					$this->error = L('_RECORD_HAS_UPDATE_');
					return false;
				}else{
					// 更新乐观锁
					$save_version = $map->get($this->lock_optimistically);
					if($save_version != $lock_version+1) {
						$map->put($this->lock_optimistically,$lock_version+1);
					}
					Session::set($guid.'_lock_version',$lock_version+1);
				}
			}
		}

        if(FALSE === $this->db->save($map,$table,$where,$limit,$order)){
            $this->error = L('_OPERATION_WRONG_');
            return false;
        }else {
			// 保存关联记录
			if ($this->auto_save_relations){
				$this->opRelation('SAVE',$data);
			}
            return True;
        }
    }

    // 操作关联数据
    function opRelation($opType,$data,$type='',$name='') 
    {
		$voClass    =   $this->getVo();
		$vo   = V($voClass);        	
  		$result	=	false;
        if(!empty($vo->_link)) {
            foreach($vo->_link as $val) {
                if(empty($type) || $val['mapping_type']==$type) {
                    $mappingType = $val['mapping_type'];
                    $mappingVo  = $val['class_name'];
                    $mappingFk   =  $val['foreign_key'];
                    if(empty($mappingFk)) {
                    	$mappingFk  =  $this->getTableName().'_id';
                    }
                    $mappingName =  $val['mapping_name'];
                    $mappingCondition = $val['condition'];
                    if(empty($mappingCondition)) {
                        $pk   =  is_array($data)? $data[$this->pk]:$data->{$this->pk};
                        $mappingCondition = "$mappingFk={$pk}";
                    }
                    if( empty($name) || $mappingName == $name) {
                        $dao = D($mappingVo);
						$mappingData	=	is_array($data)?$data[$mappingName]:$data->$mappingName;
						if(!empty($mappingData)) {
							switch($mappingType) {
								case HAS_ONE:
								case HAS_MANY:
									switch (strtoupper($opType)){
										case 'ADD'	 :	// 增加关联数据
											$result   =  $dao->add($mappingData);
											break;
										case 'SAVE' :	// 更新关联数据
											$result   =  $dao->save($mappingData,'',$mappingCondition);
											break;
										case 'DEL' :	// 删除关联数据
											$result   =  $dao->delete($mappingCondition);
											break;
										default:
											return false;
									}
									break;
							}
						}
                     }                	
                }
            }
        }      
		return $result;
    }

	// 保存多对多中间表
	function saveHabtm($data,$name,$relationData) 
	{
		$voClass    =   $this->getVo();
		$vo   = V($voClass,$data);        	
		$result	=	false;
		if(!empty($vo->_link)) {
			// 存在关联
			foreach ($vo->_link as $val){
				if($val['mapping_type']==MANY_TO_MANY && $val['mapping_name'] == $name) {
					// habtm 关联
                    $mappingVo  = $val['class_name'];
                    $mappingFk   =  $val['foreign_key'];
                    if(empty($mappingFk)) {
                    	$mappingFk  =  $this->getTableName().'_id';
                    }
					$dao = D($mappingVo);
					$mappingRelationFk = $val['relation_foreign_key'];
					if(empty($mappingRelationFk)) {
						$mappingRelationFk   = $dao->getTableName().'_id';
					}
					$mappingRelationTable  =  $val['relation_table'];
					if(empty($mappingRelationTable)) {
						$mappingRelationTable  =  $this->getRelationTableName($dao);
					}
					if(is_array($relationData)) {
						$relationData	=	implode(',',$relationData);
					}
			        $this->startTrans();
					// 删除关联表数据
					$this->db->remove($mappingFk.'='.$vo->{$this->pk},$mappingRelationTable);
					// 插入关联表数据
					$sql  = 'INSERT INTO '.$mappingRelationTable.' ('.$mappingFk.','.$mappingRelationFk.') SELECT a.'.$this->pk.',b.'.$dao->pk.' FROM '.$this->getRealTableName().' AS a ,'.$dao->getRealTableName()." AS b where a.".$this->pk.' ='. $vo->{$this->pk}.' AND  b.'.$dao->pk.' IN ('.$relationData.") ";	
					$result	=	$dao->execute($sql);
					if($result) {
				        // 提交事务
				        $this->commit();
					}else {
						// 事务回滚
						$this->rollback();
					}
				}
			}
		}
		return $result;
	}

    /**
     +----------------------------------------------------------
     * 删除数据
     * 
     +----------------------------------------------------------
     * @access public 
     +----------------------------------------------------------
     * @param integer $id 主键值
     * @param string $table  数据表名
     +----------------------------------------------------------
     * @return boolen
     +----------------------------------------------------------
     */
    function deleteById($id,$table='',$pk='')
    {
        $table  = empty($table)?$this->getRealTableName():$table;
        $pk     = $pk?$pk:$this->pk;
        if(FALSE === $this->db->remove($pk."='$id'",$table)){
            $this->error =  L('_OPERATION_WRONG_');
            return false;
        }else {
            return True;
        }
    }

    /**
     +----------------------------------------------------------
     * 根据条件删除表数据
     * 如果成功返回删除记录个数
     +----------------------------------------------------------
     * @access public 
     +----------------------------------------------------------
     * @param mixed $condition 删除条件
     * @param string $table  数据表名
     * @param integer $limit 要删除的记录数
     * @param string $order  删除的顺序
     +----------------------------------------------------------
     * @return boolen
     +----------------------------------------------------------
     * @throws ThinkExecption
     +----------------------------------------------------------
     */
    function delete($data,$table='',$limit='',$order='')
    {
        $table = empty($table)?$this->getRealTableName():$table;
        if(is_array($data)){
            $map = new HashMap($data);
        }
        elseif(is_instance_of($data,'Vo')){
            $map = $data->toMap();
        }
        elseif(is_instance_of($data,'HashMap')) {
            $map    = $data;
        }
        if(!empty($map)) {
			$map	=	$this->dealMap($map);
            $pk     = $pk?$pk:$this->pk;
            $where  = $pk."=".$map->get($pk);            
        }else {
            $where  =   $data;
        }
        $result=    $this->db->remove($where,$table,$limit,$order);
        if(FALSE === $result ){
            $this->error =  L('_OPERATION_WRONG_');
            return false;
        }else {
			// 删除关联记录
			if ($this->auto_delete_relations){
				$this->opRelation('DEL',$data);
			}
            //返回删除记录个数
            return $result;
        }
    }

    /**
     +----------------------------------------------------------
     * 根据条件删除表数据
     * 
     +----------------------------------------------------------
     * @access public 
     +----------------------------------------------------------
     * @param mixed $condition 删除条件
     * @param string $table  数据表名
     +----------------------------------------------------------
     * @return boolen
     +----------------------------------------------------------
     * @throws ThinkExecption
     +----------------------------------------------------------
     */
    function deleteAll($condition,$table='')
    {
        $table = empty($table)?$this->getRealTableName():$table;
        if(FALSE === $this->db->remove($condition,$table)){
            $this->error =  L('_OPERATION_WRONG_');
            return false;
        }else {
            return True;
        }
    }

    /**
     +----------------------------------------------------------
     * 清空表数据
     * 
     +----------------------------------------------------------
     * @access public 
     +----------------------------------------------------------
     * @param string $table  数据表名
     +----------------------------------------------------------
     * @return boolen
     +----------------------------------------------------------
     * @throws ThinkExecption
     +----------------------------------------------------------
     */
    function clear($table='')
    {
        $table = empty($table)?$this->getRealTableName():$table;
        if(FALSE === $this->db->clear($table)){
            $this->error =  L('_OPERATION_WRONG_');
            return false;
        }else {
            return True;
        }
    }

    /**
     +----------------------------------------------------------
     * 根据主键得到一条记录
     * 
     +----------------------------------------------------------
     * @access public 
     +----------------------------------------------------------
     * @param int $id 主键的值
     * @param string $table  数据表名
     * @param string $fields 字段名，默认为*
     +----------------------------------------------------------
     * @return Vo
     +----------------------------------------------------------
     * @throws ThinkExecption
     +----------------------------------------------------------
     */
    function getById($id,$table='',$fields='*',$pk='',$relation=false)
    {
        $table  = empty($table)?$this->getRealTableName():$table;
        if(C('DATA_CACHE_ON')) {//启用动态数据缓存
        	$vo  =  $this->getCacheVo('',$id);
            if(false !== $vo) {
            	return $vo;
            }
        }
        $pk     = $pk?$pk:$this->pk;
        $rs     = $this->db->find($pk."='{$id}'",$table,$fields);
        if($rs && $rs->size()>0) {
                $vo  =  $this->rsToVo($rs->get(0),'','',$relation);
                if(C('DATA_CACHE_ON')) 
                    $this->CacheVo($vo,$id);
                return $vo;
        }else {
            return false;
        }        	

    }

    /**
     +----------------------------------------------------------
     * 根据某个字段得到一条记录
     * 
     +----------------------------------------------------------
     * @access public 
     +----------------------------------------------------------
     * @param string $field 字段名称
     * @param mixed $value 字段的值
     * @param string $table  数据表名
     * @param string $fields 字段名，默认为*
     +----------------------------------------------------------
     * @return Vo
     +----------------------------------------------------------
     * @throws ThinkExecption
     +----------------------------------------------------------
     */
    function getBy($field,$value,$table='',$fields='*',$relation=false)
    {
        $table  = empty($table)?$this->getRealTableName():$table;
        if(C('DATA_CACHE_ON')) {//启用动态数据缓存
        	$vo  =  $this->getCacheVo('',$field.'_'.$value);
            if(false !== $vo) {
            	return $vo;
            }
        }
        $rs     = $this->db->find($field."='{$value}'",$table,$fields);
        if($rs && $rs->size()>0) {
                $vo  =  $this->rsToVo($rs->get(0),'','',$relation);
                if(C('DATA_CACHE_ON')) 
                    $this->CacheVo($vo,$field.'_'.$value);
                return $vo;
        }else {
            return false;
        }        	
    }

    /**
     +----------------------------------------------------------
     * 根据条件得到一条记录
     * 
     +----------------------------------------------------------
     * @access public 
     +----------------------------------------------------------
     * @param mixed $condition 条件
     * @param string $table  数据表名
     * @param string $fields 字段名，默认为*
     * @param boolean $cache 是否读取缓存
     +----------------------------------------------------------
     * @return Vo | false
     +----------------------------------------------------------
     * @throws ThinkExecption
     +----------------------------------------------------------
     */
    function find($condition='',$table=NULL,$fields='*',$cache=true,$relation=false)
    {
        $table  = empty($table)?$this->getRealTableName():$table;
        $identify   = to_guid_string($condition);
        if(C('DATA_CACHE_ON') && $cache) {//启用动态数据缓存
        	$vo  =  $this->getCacheVo('',$identify);
            if(false !== $vo) {
            	return $vo;
            }
        }
        $rs = $this->db->find($condition,$table,$fields,NULL,NULL,NULL,NULL,$cache);
        if($rs && $rs->size()>0) {
            $vo  =  $this->rsToVo($rs->get(0),'','',$relation);
            if(C('DATA_CACHE_ON'))
                $this->cacheVo($vo,$identify);
            return $vo;
        }else {
			$this->error = L('_SELECT_NOT_EXIST_');
            return false;
        }
    }

    /**
     +----------------------------------------------------------
     * 根据条件得到一条记录
     * 并且返回关联记录
     +----------------------------------------------------------
     * @access public 
     +----------------------------------------------------------
     * @param mixed $condition 条件
     * @param string $table  数据表名
     * @param string $fields 字段名，默认为*
     * @param boolean $cache 是否读取缓存
     +----------------------------------------------------------
     * @return Vo | false
     +----------------------------------------------------------
     * @throws ThinkExecption
     +----------------------------------------------------------
     */
    function xFind($condition='',$table=NULL,$fields='*',$cache=true) 
    {
        return $this->find($condition,$table,$fields,$cache,true);
    }

    /**
     +----------------------------------------------------------
     * 获取返回数据的关联记录
     * 
     +----------------------------------------------------------
     * @access public 
     +----------------------------------------------------------
     * @param mixed $result  返回数据
     * @param string $type  关联类型
     * @param string $name 关联名称
     +----------------------------------------------------------
     * @return mixed
     +----------------------------------------------------------
     * @throws ThinkExecption
     +----------------------------------------------------------
     */
    function getRelation($result,$type='',$name='') 
    {
        if(is_array($result)) {
            $voClass    =   $this->getVo();
            $vo   = V($voClass);        	
        }else {
        	$vo  =  &$result;
        }
        if(!empty($vo->_link)) {
            foreach($vo->_link as $val) {
                if(empty($type) || $val['mapping_type']==$type) {
                    $mappingType = $val['mapping_type'];
                    $mappingVo  = $val['class_name'];
                    $mappingFk   =  $val['foreign_key'];
                    if(empty($mappingFk)) {
                    	$mappingFk  =  $this->getTableName().'_id';
                    }
                    $mappingName =  $val['mapping_name'];
                    $mappingFields = $val['mapping_fields'];
                    $mappingCondition = $val['condition'];
                    $dao = D($mappingVo);
                    if(strtoupper($mappingVo)==strtoupper($this->getTableName())) {
                        // 自引用
                        $mappingParentKey = !empty($val['parent_key'])? $val['parent_key'] : 'parent_id';
                    }
                    if(empty($mappingCondition)) {
                        $fk   =  is_array($result)? $result[$this->pk]:$result->{$this->pk};
                        if(empty($mappingParentKey)) {
                        	$mappingCondition = "{$mappingFk}={$fk}";
                        }else {
                            if($mappingType== BELONGS_TO) {
                                $parentKey   =  is_array($result)? $result[$mappingParentKey]:$result->{$mappingParentKey};
                            	$mappingCondition = "{$dao->pk}={$parentKey}";
                            }else {
                            	$mappingCondition = "{$mappingParentKey}={$fk}";
                            }
                        }
                    }
                    if(empty($name) || $mappingName == $name) {
                        switch($mappingType) {
                            case HAS_ONE:
                            case BELONGS_TO:
                                // 不再获取关联记录的关联
                                $relation   =  $dao->find($mappingCondition,'',$mappingFields,false,false);
                                break;
                            case HAS_MANY:
                                $mappingOrder =  $val['mapping_order'];
                                $mappingLimit =  $val['mapping_limit'];
                                // 不再获取关联记录的关联
                                $relation   =  $dao->findAll($mappingCondition,'',$mappingFields,$mappingOrder,$mappingLimit);
                                break;
                            case MANY_TO_MANY:
                                $mappingOrder =  $val['mapping_order'];
                                $mappingLimit =  $val['mapping_limit'];
                                $mappingRelationFk = $val['relation_foreign_key'];
                                if(empty($mappingRelationFk)) {
                                	$mappingRelationFk   = $dao->getTableName().'_id';
                                }
                                $mappingRelationTable  =  $val['relation_table'];
                                if(empty($mappingRelationTable)) {
                                	$mappingRelationTable  =  $this->getRelationTableName($dao);
                                }
								$sql = "SELECT b.* FROM {$mappingRelationTable} AS a, ".$mappingDao->getRealTableName()." AS b WHERE a.{$mappingRelationFk} = b.{$dao->pk} AND a.{$mappingCondition}";
                                if(!empty($mappingOrder)) {
                                	$sql .= ' ORDER BY '.$mappingOrder;
                                }
                                if(!empty($mappingLimit)) {
                                	$sql .= ' LIMIT '.$mappingLimit;
                                }
                                $rs   =  $this->query($sql);
                                $relation   =  $dao->rsToVoList($rs,'','',false);
                                break;
                        }
                        is_array($result)?
                            $result[$mappingName] = $relation :
                            $result->{$mappingName} = $relation;
                     }                	
                }
            }
        }
        return $result;
    }

    /**
     +----------------------------------------------------------
     * 查找记录
     * 
     +----------------------------------------------------------
     * @access public 
     +----------------------------------------------------------
     * @param mixed $condition  条件
     * @param string $table  数据表名
     * @param string $fields  需要显示的字段
     * @param string $order  排序字段
     * @param string $limit  
     * @param string $group  
     * @param string $having 
     +----------------------------------------------------------
     * @return VoList
     +----------------------------------------------------------
     * @throws ThinkExecption
     +----------------------------------------------------------
     */
    function findAll($condition='',$table='',$fields='*',$order='',$limit='',$group='',$having='',$relation=false)
    {
        $table = empty($table)?$this->getRealTableName():$table;
        //数据集唯一标识符
        $identify   =   to_guid_string(func_get_args());
        if(C('DATA_CACHE_ON') && $this->cacheQuery) { //启用数据动态缓存
            $voList = $this->getCacheVoList('',$identify);
            if (false !== $voList) {
                return $voList;
            }
        }
        $rs = $this->db->find($condition,$table,$fields,$order,$limit,$group,$having,$this->cacheQuery);
        $voList  = $this->rsToVoList($rs,'','',$relation);
        if(C('DATA_CACHE_ON'))
            $this->cacheVoList($voList,$identify);
        return $voList;
    }

    // 查询记录并返回相应的关联记录
    function xFindAll($condition='',$table='',$fields='*',$order='',$limit='',$group='',$having='') 
    {
    	return $this->findAll($condition,$table,$fields,$order,$limit,$group,$having,true);
    }
    /**
     +----------------------------------------------------------
     * 查询SQL语句
     * 
     +----------------------------------------------------------
     * @access public 
     +----------------------------------------------------------
     * @param string $sql  SQL指令
     +----------------------------------------------------------
     * @return false | integer
     +----------------------------------------------------------
     * @throws ThinkExecption
     +----------------------------------------------------------
     */
    function query($sql)
    {
        if(empty($sql)) return false;
        $identify   = md5($sql);
        if(C('DATA_CACHE_ON') && $this->cacheQuery) {//启用动态数据缓存
        	$result =   $this->getCacheResultSet($identify);
            if(false !== $result) {
            	return $result;
            }
        }
        $result =   $this->db->query($sql);
        if(C('DATA_CACHE_ON'))
            $this->cacheResultSet($result,$identify);
        return $result;
    }

    /**
     +----------------------------------------------------------
     * 执行SQL语句
     * 
     +----------------------------------------------------------
     * @access public 
     +----------------------------------------------------------
     * @param string $sql  SQL指令
     +----------------------------------------------------------
     * @return false | integer
     +----------------------------------------------------------
     * @throws ThinkExecption
     +----------------------------------------------------------
     */
    function execute($sql)
    {
        if(!empty($sql)) {
            $result =   $this->db->execute($sql);
            return $result;
        }else {
        	return false;
        }
    }

    /**
     +----------------------------------------------------------
     * 获取一条记录的某个字段值
     * 
     +----------------------------------------------------------
     * @access public 
     +----------------------------------------------------------
     * @param string $field  字段名
     * @param mixed $condition  条件
     * @param string $table  数据表名
     +----------------------------------------------------------
     * @return mixed
     +----------------------------------------------------------
     * @throws ThinkExecption
     +----------------------------------------------------------
     */
    function getOne($field,$condition='',$table='')
    {
        $result    =   $this->find($condition,$table);
        if(!empty($result)) {
            return is_array($result)? $result[$field]:$result->{$field};
        }else {
            return null;
        }
    }

    /**
     +----------------------------------------------------------
     * 获取没有vo对象的查询结果中的某个字段值
     * 
     +----------------------------------------------------------
     * @access public 
     +----------------------------------------------------------
     * @param ResultSet $rs  查询结果
     * @param string $col  列名
     +----------------------------------------------------------
     * @return integer
     +----------------------------------------------------------
     * @throws ThinkExecption
     +----------------------------------------------------------
     */
    function getCol($rs,$col) 
    {
        if(!empty($rs)) {
            $result =   $rs->get(0);
            $field  =   is_array($result)? $result[$col]:$result->{$col};
            return $field;         	
        }else {
        	return NULL;
        }
    }

    /**
     +----------------------------------------------------------
     * 统计满足条件的记录个数
     * 
     +----------------------------------------------------------
     * @access public 
     +----------------------------------------------------------
     * @param mixed $condition  条件
     * @param string $table  数据表名
     +----------------------------------------------------------
     * @return integer
     +----------------------------------------------------------
     * @throws ThinkExecption
     +----------------------------------------------------------
     */
    function getCount($condition='',$field='*',$table='')
    {
        $table = empty($table)?$this->getRealTableName():$table;
        $fields = 'count('.$field.') as count';
        $rs = $this->db->find($condition,$table,$fields);
        if($rs && $rs->size()>0) {
        	return $this->getCol($rs,'count');
        }else {
        	return 0;
        }
    }

    /**
     +----------------------------------------------------------
     * 取得某个字段的最大值
     * 
     +----------------------------------------------------------
     * @access public 
     +----------------------------------------------------------
     * @param string $field  字段名
     * @param mixed $condition  条件
     * @param string $table  数据表名
     +----------------------------------------------------------
     * @return integer
     +----------------------------------------------------------
     * @throws ThinkExecption
     +----------------------------------------------------------
     */
    function getMax($field,$condition='',$table='')
    {
        $table = empty($table)?$this->getRealTableName():$table;
        $fields = 'MAX('.$field.') as max';
        $rs = $this->db->find($condition,$table,$fields);
        if($rs) {
        	return $this->getCol($rs,'max');
        }else {
        	return NULL;
        }
    }

    /**
     +----------------------------------------------------------
     * 取得某个字段的最小值
     * 
     +----------------------------------------------------------
     * @access public 
     +----------------------------------------------------------
     * @param string $field  字段名
     * @param mixed $condition  条件
     * @param string $table  数据表名
     +----------------------------------------------------------
     * @return integer
     +----------------------------------------------------------
     * @throws ThinkExecption
     +----------------------------------------------------------
     */
    function getMin($field,$condition='',$table='')
    {
        $table = empty($table)?$this->getRealTableName():$table;
        $fields = 'MIN('.$field.') as min';
        $rs = $this->db->find($condition,$table,$fields);
        if($rs) {
        	return $this->getCol($rs,'min');
        }else {
        	return NULL;
        }
    }

    /**
     +----------------------------------------------------------
     * 统计某个字段的总和
     * 
     +----------------------------------------------------------
     * @access public 
     +----------------------------------------------------------
     * @param string $field  字段名
     * @param mixed $condition  条件
     * @param string $table  数据表名
     +----------------------------------------------------------
     * @return integer
     +----------------------------------------------------------
     * @throws ThinkExecption
     +----------------------------------------------------------
     */
    function getSum($field,$condition='',$table='')
    {
        $table = empty($table)?$this->getRealTableName():$table;
        $fields = 'SUM('.$field.') as sum';
        $rs = $this->db->find($condition,$table,$fields);
        if($rs) {
        	return $this->getCol($rs,'sum');
        }else {
        	return NULL;
        }
    }

    /**
     +----------------------------------------------------------
     * 查询符合条件的第N条记录
     * 0 表示第一条记录 -1 表示最后一条记录
     +----------------------------------------------------------
     * @access public 
     +----------------------------------------------------------
     * @param integer $position 记录位置
     * @param mixed $condition 条件
     * @param string $table  数据表名
     * @param string $fields 字段名，默认为*
     * @param boolean $relation 是否读取关联
     +----------------------------------------------------------
     * @return Vo | null
     +----------------------------------------------------------
     */
    function getN($position=0,$condition='',$table=NULL,$fields='*',$relation=false)
    {
        $table  = empty($table)?$this->getRealTableName():$table;
        $rs = $this->db->find($condition,$table,$fields,NULL,NULL,NULL,NULL);
        if($rs && $rs->size()>0) {
			if($position<0) {
				// 逆序查找
				$position = $rs->size()-abs($position);
			}
			if($position<$rs->size()) {
				$vo  =  $this->rsToVo($rs->get($position),'','',$relation);
				return $vo;
			}else {
				return null;
			}
        }else {
            return null;
        }
    }

	// 获取第一条记录
	function getFirst($condition='',$table=NULL,$fields='*',$relation=false) {
		return $this->getN(0,$condition,$table,$fields,$relation);
	}

	// 获取最后一条记录
	function getLast($condition='',$table=NULL,$fields='*',$relation=false) {
		return $this->getN(-1,$condition,$table,$fields,$relation);
	}

    /**
     +----------------------------------------------------------
     * 缓存VoList对象
     +----------------------------------------------------------
     * @access public 
     +----------------------------------------------------------
     * @param VoList $voList VoList对象实例
     * @param string $identify 缓存标识
     +----------------------------------------------------------
     * @return void
     +----------------------------------------------------------
     * @throws ThinkExecption
     +----------------------------------------------------------
     */
    function cacheVoList($voList,$identify='') 
    {
        $guid   = strtoupper($voList->getVoClass()).'List_'.$identify;
        $vo  =  $voList->get(0);
        if(empty($vo->_info)) {
			S($guid,$voList);
        }else {
            // 读取vo对象的缓存属性
            // 进行相应的缓存
            switch($vo->_info['cache']) {
            	case Think_CACHE_STATIC:
                     // 永久缓存
                    file_put_contents(TEMP_PATH.$guid,serialize($voList));
                    break;
                case Think_CACHE_NO:
                	// 禁止缓存
                	break;
                case Think_CACHE_DYNAMIC:
                    // 动态缓存
					S($guid,$voList);
                	break;
            }        	
        }
    }

    /**
     +----------------------------------------------------------
     * 缓存Vo对象
     +----------------------------------------------------------
     * @access public 
     +----------------------------------------------------------
     * @param Vo $vo Vo对象实例
     * @param string $identify 缓存标识
     +----------------------------------------------------------
     * @return void
     +----------------------------------------------------------
     * @throws ThinkExecption
     +----------------------------------------------------------
     */
    function cacheVo($vo,$identify='') 
    {
        if(empty($identify)) {
        	$identify   =  $vo->{$this->pk};
        }
    	$guid    = strtoupper(get_class ($vo)).'_'.$identify;
        if(empty($vo->_info)) {
			S($guid,$vo);
        }else {
            // 读取vo对象的缓存属性
            // 进行相应的缓存
            switch($vo->_info['cache']) {
            	case Think_CACHE_STATIC:
                     // 永久缓存
                    file_put_contents(TEMP_PATH.$guid,serialize($vo));
                    break;
                case Think_CACHE_NO:
                	// 禁止缓存
                	break;
                case Think_CACHE_DYNAMIC:
                    // 动态缓存
					S($guid,$vo);
                	break;
            }
        }
    }

    /**
     +----------------------------------------------------------
     * 缓存ResultSet对象
     +----------------------------------------------------------
     * @access public 
     +----------------------------------------------------------
     * @param ResultSet $resultSet 插件结果集
     * @param string $identify 缓存标识
     +----------------------------------------------------------
     * @return void
     +----------------------------------------------------------
     * @throws ThinkExecption
     +----------------------------------------------------------
     */
    function cacheResultSet($resultSet,$identify) 
    {
        $guid    = 'ResultSet_'.$identify;
		S($guid,$resultSet);
    }

    /**
     +----------------------------------------------------------
     * 获取缓存ResultSet对象
     +----------------------------------------------------------
     * @access public 
     +----------------------------------------------------------
     * @param string $identify 缓存标识
     +----------------------------------------------------------
     * @return void
     +----------------------------------------------------------
     * @throws ThinkExecption
     +----------------------------------------------------------
     */
    function getCacheResultSet($identify) 
    {
        $guid    = 'ResultSet_'.$identify;
        return S($guid);   	
    }

    /**
     +----------------------------------------------------------
     * 获取缓存的Vo对象
     +----------------------------------------------------------
     * @access public 
     +----------------------------------------------------------
     * @param string $voClass Vo对象名
     * @param string $identify 缓存标识
     +----------------------------------------------------------
     * @return Vo
     +----------------------------------------------------------
     * @throws ThinkExecption
     +----------------------------------------------------------
     */
    function getCacheVo($voClass,$identify='') 
    {
        $voClass    = !empty($voClass)? $voClass : $this->getVo();
        $guid    = strtoupper($voClass).'_'.$identify;
        //判断是否存在永久缓存
        if(file_exists(TEMP_PATH.$guid)) {
            $vo =   unserialize(file_get_contents(TEMP_PATH.$guid));
        }else {
			$vo	=	S($guid);
        }
		$this->cacheLockVersion($vo);
        return $vo;
    }

    /**
     +----------------------------------------------------------
     * 获取缓存的VoList对象
     +----------------------------------------------------------
     * @access public 
     +----------------------------------------------------------
     * @param string $voClass Vo对象名
     * @param string $identify 缓存标识
     +----------------------------------------------------------
     * @return VoList
     +----------------------------------------------------------
     * @throws ThinkExecption
     +----------------------------------------------------------
     */
    function getCacheVoList($voClass='',$identify='') 
    {
        $voClass    = !empty($voClass)? $voClass : $this->getVo();
        $guid    = strtoupper($voClass).'List_'.$identify;
		$voList	=	S($guid);
        return $voList;
    }

	// 记录乐观锁
	function cacheLockVersion($vo) {
		if($this->lock_optimistically) {
			$lock = is_array($vo)? $vo[$this->lock_optimistically]:$vo->{$this->lock_optimistically};
			$id	=	is_array($vo)? $vo[$this->pk]:$vo->{$this->pk};
			if(isset($lock) && isset($id)) {
				Session::set(strtoupper($this->getVo()).'_'.$id.'_lock_version',$lock);
			}
		}
	}

    /**
     +----------------------------------------------------------
     * 把一条查询结果转换为Vo对象
     +----------------------------------------------------------
     * @access public 
     +----------------------------------------------------------
     * @param Result $result 记录
     * @param string $voClass Vo对象名
     +----------------------------------------------------------
     * @return Vo
     +----------------------------------------------------------
     * @throws ThinkExecption
     +----------------------------------------------------------
     */
    function rsToVo($result,$voClass='',$resultType='',$relation=false) 
    {
        $resultType = !empty($resultType)? $resultType : $this->resultType ;
        if($resultType== DATA_TYPE_VO) {
            $voClass    = !empty($voClass)? $voClass : $this->getVo();
            $vo = V($voClass,$result);       
			if(isset($vo->_link)) unset($vo->_link);
			if(isset($vo->_auto)) unset($vo->_auto);
			if(isset($vo->_validate)) unset($vo->_validate);
        }else{
        	$vo  =  &$result;
        }
		// 记录乐观锁
		$this->cacheLockVersion($vo);

	   // 获取关联
       if( $relation ) {
           $type = isset($relation['type'])?$relation['type']:'';
           $name   =  isset($relation['name'])?$relation['name']:'';
       	    $vo  =  $this->getRelation($vo,$type,$name);
       }
       return auto_charset($vo,C('DB_CHARSET'),C('TEMPLATE_CHARSET'));
    }

    /**
     +----------------------------------------------------------
     * 把查询记录集转换为VoList对象
     +----------------------------------------------------------
     * @access public 
     +----------------------------------------------------------
     * @param Result $resultSet 记录集
     * @param string $voClass Vo对象名
     +----------------------------------------------------------
     * @return VoList
     +----------------------------------------------------------
     * @throws ThinkExecption
     +----------------------------------------------------------
     */
    function rsToVoList($resultSet,$voClass='',$resultType='',$relation=false) 
    {
        $resultType = !empty($resultType)? $resultType : $this->resultType ;
       if( $relation) {
           $type = isset($relation['type'])?$relation['type']:'';
           $name   =  isset($relation['name'])?$relation['name']:'';
       }
	   if(is_object($resultSet)) $resultSet =	 $resultSet->toArray();
        if($resultType== DATA_TYPE_VO ) {
            $voClass    =   !empty($voClass)? $voClass : $this->getVo();
            $list     = new VoList();
            if($resultSet) {
                foreach ( $resultSet as $result)
                {
                    if(!empty($result)){
                        $vo     = V($voClass,$result);
                        if( $relation ) {
                            $vo  =  $this->getRelation($vo,$type,$name);
                        }
						// 去掉多余的属性
						if(isset($vo->_link)) unset($vo->_link);
						if(isset($vo->_auto)) unset($vo->_auto);
						if(isset($vo->_validate)) unset($vo->_validate);						
                        $list->add($vo);
                    }
                }             	
            }
 
        }else {
            if($resultSet) {
                $list  = $resultSet;
                if( $relation ) {
                    foreach($list as $key=>$val) {
                        $val  = $this->getRelation($val,$type,$name);
                        $list[$key] = $val;
                    }
                }            	
            }else {
            	$list  = array();
            }

        }
        return auto_charset($list,C('DB_CHARSET'),C('TEMPLATE_CHARSET'));
    }

    /**
     +----------------------------------------------------------
     * 根据表单创建Vo对象
     * 
     +----------------------------------------------------------
     * @access public 
     +----------------------------------------------------------
     * @param string $type 创建类型
     * @param string $voClass 创建Vo对象的名称
     * @param string $pk Vo对象的主键名
     +----------------------------------------------------------
     * @return Vo
     +----------------------------------------------------------
     * @throws ThinkExecption
     +----------------------------------------------------------
     */
    function createVo($type='add',$voClass='',$resultType=null,$pk='id')
    {
        if(empty($voClass)){
            $voClass = $this->getVo();
        }
        if ( strtolower($type) == "add" ) { //新增
            $vo = V($voClass); //新建Vo对象
        } else { //编辑
            //根据编号获取Vo对象
    		$table	= empty($table)?$this->getRealTableName():$table;
            $pk       = $pk?$pk:$this->pk;
            $value   = isset($_GET[$pk])?$_GET[$pk]:$_POST[$pk];
			$rs		= $this->db->find($pk."='{$value}'",$table);
			if($rs && $rs->size()>0) {
				$voClass    = !empty($voClass)? $voClass : $this->getVo();
				$vo = V($voClass,$rs->get(0)); 
			}else {
				return false;
			}   
        }

        //给Vo对象赋值
        foreach ( $vo->__varList() as $name){
            $val = isset($_REQUEST[$name])?$_REQUEST[$name]:null;
            //保证赋值有效
            if(!is_null($val) && property_exists($vo,$name)){
				// 首先保证表单赋值
                $vo->$name = $val;
            }elseif(	(strtolower($type) == "add" && in_array($name,$this->auto_create_timestamps)) || 
						(strtolower($type) == "edit" && in_array($name,$this->auto_update_timestamps)) ){ 
				// 自动保存时间戳
				$vo->$name = time();
			}elseif($this->lock_optimistically && $name==$this->lock_optimistically ){
				// 自动保存乐观锁
				if(strtolower($type) == "add" ) {
					$vo->{$name} = 0;
				}else {
					$vo->{$name} += 1 ;
				}
			}elseif(strtolower($type) == "edit"){
				unset($vo->{$name});
			}
        }

        // 属性验证
        if(isset($vo->_validate)) {
            // 如果设置了Vo验证
            // 则进行数据验证
            import("ORG.Text.Validation");
        	$validation = Validation::getInstance();
            foreach($vo->_validate as $key=>$val) {
				if(isset($val[3]) && $val[3]== MUST_TO_VALIDATE) {
					// 必须验证字段
					if(isset($val[4]) && $val[4] && function_exists($val[1]) ) {
						// 使用函数验证是否相等
						if(!$val[1]($vo->{$val[0]})) {
							$this->error = $val[2];
							return false;
						}
					}elseif(!$validation->check($vo->{$val[0]},$val[1])) {
						$this->error    =   $val[2];
						return false;
					} 
				}elseif(isset($vo->{$val[0]})){
					// 默认表单有设置才验证
					if(isset($val[4]) && $val[4] && function_exists($val[1]) ) {
						if(!$val[1]($vo->{$val[0]})) {
							$this->error = $val[2];
							return false;
						}
					}elseif( !$validation->check($vo->{$val[0]},$val[1])) {
						$this->error    =   $val[2];
						return false;
					} 
				}
            }
			unset($vo->_validate);
        }

		// Vo自动填充
		if(!empty($vo->_auto)) {
			foreach ($vo->_auto as $auto){
				if(property_exists($vo,$auto[0])) {
					if(empty($auto[2])) $auto[2] = 'ADD';// 默认为新增的时候自动填充
					if( (strtolower($type) == "add"  && $auto[2] == 'ADD') || 	(strtolower($type) == "edit"  && $auto[2] == 'UPDATE') || $auto[2] == 'ALL') 
					{
						if( function_exists($auto[1])) {
							// 如果定义为函数则调用
							$vo->{$auto[0]} = $auto[1]($vo->{$auto[0]});
						}else {
							// 否则作为字符串处理
							$vo->{$auto[0]} = $auto[1];
						}
					}
				}
			}
			unset($vo->_auto);
		}
		if(isset($vo->_link))  unset($vo->_link);
		if(isset($vo->_info))  unset($vo->_info);
		if(is_null($resultType))	$resultType = $this->resultType;

        if($resultType == DATA_TYPE_ARRAY ) {
        	// 返回数组
            $vo  =  $vo->toArray();
        }
        return $vo;
    }

    /**
     +----------------------------------------------------------
     * 得到完整的数据表名
     * 
     +----------------------------------------------------------
     * @access public 
     +----------------------------------------------------------
     * @return string
     +----------------------------------------------------------
     */
    function getRealTableName()
    {
        if(empty($this->realTableName)) {
            $realTableName  = !empty($this->tablePrefix) ? $this->tablePrefix.'_' : '';
            $realTableName .= !empty($this->tableName) ? $this->tableName : strtolower(substr($this->__toString(),0,-3));
            $realTableName .= !empty($this->tableSuffix) ? '_'.$this->tableSuffix : '';    
            $this->realTableName    =   $realTableName;
        }
        return $this->realTableName;
    }

    /**
     +----------------------------------------------------------
     * 得到完整的数据表名
     * 
     +----------------------------------------------------------
     * @access public 
     +----------------------------------------------------------
     * @return string
     +----------------------------------------------------------
     */
    function getRelationTableName($relationDao)
    {
        $relationTable  = !empty($this->tablePrefix) ? $this->tablePrefix.'_' : '';
        $relationTable .= !empty($this->tableName) ? $this->tableName : strtolower(substr($this->__toString(),0,-3));
        $relationTable .= '_'.strtolower(substr($relationDao->__toString(),0,-3));
        $relationTable .= !empty($this->tableSuffix) ? '_'.$this->tableSuffix : '';    
        return $relationTable;
    }

    /**
     +----------------------------------------------------------
     * 得到基本表名
     * 
     +----------------------------------------------------------
     * @access public 
     +----------------------------------------------------------
     * @return string
     +----------------------------------------------------------
     * @throws ThinkExecption
     +----------------------------------------------------------
     */
    function getTableName()
    {
        if($this->tableName){
            return $this->tableName;
        }else 
            return substr($this->__toString(),0,-3);
    }

    /**
     +----------------------------------------------------------
     * 启动事务
     * 
     +----------------------------------------------------------
     * @access public 
     +----------------------------------------------------------
     * @return void
     +----------------------------------------------------------
     * @throws ThinkExecption
     +----------------------------------------------------------
     */
    function startTrans()
    {
        $this->commit();
        $this->db->autoCommit = 0;
        return ;
    }

    /**
     +----------------------------------------------------------
     * 提交事务
     * 
     +----------------------------------------------------------
     * @access public 
     +----------------------------------------------------------
     * @return boolean
     +----------------------------------------------------------
     * @throws ThinkExecption
     +----------------------------------------------------------
     */
    function commit()
    {
        return $this->db->commit();
    }

    /**
     +----------------------------------------------------------
     * 事务回滚
     * 
     +----------------------------------------------------------
     * @access public 
     +----------------------------------------------------------
     * @return boolean
     +----------------------------------------------------------
     * @throws ThinkExecption
     +----------------------------------------------------------
     */
    function rollback()
    {
        return $this->db->rollback();
    }


    /**
     +----------------------------------------------------------
     * 取得当前Vo对象的名称
     * 
     +----------------------------------------------------------
     * @access public 
     +----------------------------------------------------------
     * @return string
     +----------------------------------------------------------
     * @throws ThinkExecption
     +----------------------------------------------------------
     */
    function getVo()
    {
        return $this->getTableName().'Vo';
    }

    /**
     +----------------------------------------------------------
     * 取得所在对象的Dao类名
     * 
     +----------------------------------------------------------
     * @access public 
     +----------------------------------------------------------
     * @return string
     +----------------------------------------------------------
     * @throws ThinkExecption
     +----------------------------------------------------------
     */
    function getDao()
    {
        return $this->__toString();
    }

    /**
     +----------------------------------------------------------
     * 获取数据库查询次数
     * 
     +----------------------------------------------------------
     * @access public 
     +----------------------------------------------------------
     * @return integer
     +----------------------------------------------------------
     */
    function queryTimes()
    {
        return $this->db->getQueryTimes();
    }

    /**
     +----------------------------------------------------------
     * 获取数据库写入次数
     * 
     +----------------------------------------------------------
     * @access public 
     +----------------------------------------------------------
     * @return integer
     +----------------------------------------------------------
     */
    function writeTimes()
    {
        return $this->db->getWriteTimes();
    }

};
?>