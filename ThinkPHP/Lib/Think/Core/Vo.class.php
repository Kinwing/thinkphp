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

define('Think_CACHE_NO',      -1);   //不缓存
define('Think_CACHE_DYNAMIC', 1);   //动态缓存
define('Think_CACHE_STATIC',  2);   //静态缓存（永久缓存）

/**
 +------------------------------------------------------------------------------
 * 数据对象类
 +------------------------------------------------------------------------------
 * @package   core
 * @author    liu21st <liu21st@gmail.com>
 * @version   $Id$
 +------------------------------------------------------------------------------
 */
import("Think.Util.HashMap");
class Vo extends Base
{
    /**
     +----------------------------------------------------------
     * 架构函数
     * 支持根据数组 对象 或者map对象构建Vo对象
     +----------------------------------------------------------
     * @access public 
     +----------------------------------------------------------
     * @param mixed $data 数据
     +----------------------------------------------------------
     */
    function __construct($data=NULL,$strict=true)
    {
        if(!empty($data)) {
            //把Map对象或者关联数组转换成Vo的属性
            if( is_instance_of($data,'HashMap')){
                $data = $data->toArray();
            }elseif( is_object($data)) {
                $data = get_object_vars($data);
            }
            if(is_array($data)) {
                foreach($data as $key=>$val) {
                    if(false===$strict || ($strict && property_exists($this,$key)) )
                        $this->$key = $val; 
					// 增加对数据库映射字段和属性不同的支持
					if(isset($this->_map) ){
						$_key = array_search($key,$this->_map);
						if($_key !== false) {
							$this->$_key = $val;
						}
					}
                }        	
            }        	
        }
    }

    /**
     +----------------------------------------------------------
     * 创建Vo对象并保存到数据库
     +----------------------------------------------------------
     * @access public 
     +----------------------------------------------------------
     * @param mixed $data 数据
     * @param Dao $dao Dao对象
     +----------------------------------------------------------
     */
	function create($data='',$dao=NULL) {
		if(empty($dao)) {
			$daoClass = $this->getDao();
			$dao	=	D($daoClass);
		}
		if(empty($data)) {
			$data	 =	 $this->toMap();
		}
		return $dao->add($data);
	}

    /**
     +----------------------------------------------------------
     * 把Vo对象转换为HashMap对象
     * 
     +----------------------------------------------------------
     * @access public 
     +----------------------------------------------------------
     * @return HashMap
     +----------------------------------------------------------
     */
    function toMap($strict=true)
    {
        $vars = get_object_vars($this);
        foreach($vars as $key=>$val) {
            if(is_null($val) || substr($key,0,1)=='_' || ($strict && !property_exists($this,$key))) {
                    unset($vars[$key]);	
            }
        }
        $map= new HashMap($vars);
        return $map;
    }

    /**
     +----------------------------------------------------------
     * 取得当前Dao对象的名称
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
        return substr($this->__toString(),0,-2).'Dao';
    }

    /**
     +----------------------------------------------------------
     * 把Vo对象转换为数组
     * 过滤特殊属性和空值
     +----------------------------------------------------------
     * @access public 
     +----------------------------------------------------------
     * @return string
     +----------------------------------------------------------
     */
    function toArray($fields=array()) 
    {
        $array   = $this->__toArray();
        foreach( $array as $key=>$val) {
            if( (!empty($fields) && !in_array($key,$fields)) ||  is_null($val) || substr($key,0,1)=='_' ) {
                unset($array[$key]);
            }                
        }   
        return $array;
    }


    /**
     +----------------------------------------------------------
     * 把Vo对象转换为Json
     * 
     +----------------------------------------------------------
     * @access public 
     +----------------------------------------------------------
     * @return string
     +----------------------------------------------------------
     */
    function toJson($fields=array()) 
    {
        return json_encode($this->toArray($fields));
    }
    
    /**
     +----------------------------------------------------------
     * Vo对象是否为空
     * 
     +----------------------------------------------------------
     * @access public 
     +----------------------------------------------------------
     * @return HashMap
     +----------------------------------------------------------
     */
    function isEmpty()
    {
        return $this->__toArray() == $this->__toOraArray();
    }


};
?>