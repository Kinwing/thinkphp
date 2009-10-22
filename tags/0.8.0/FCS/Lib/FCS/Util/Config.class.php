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
 * @package    Core
 * @link       http://www.fcs.org.cn
 * @copyright  Copyright (c) 2005-2006 liu21st.com.  All rights reserved. 
 * @author     liu21st <liu21st@gmail.com>
 * @version    $Id$
 +------------------------------------------------------------------------------
 */

//配置文件类型常量定义

/**
 +------------------------------------------------------------------------------
 * 配置文件读取类
 +------------------------------------------------------------------------------
 * @package   core
 * @author    liu21st <liu21st@gmail.com>
 * @version   0.8.0
 +------------------------------------------------------------------------------
 */
class Config extends Base
{//类定义开始

    /**
     +----------------------------------------------------------
     * 配置参数的内部存储
     +----------------------------------------------------------
     * @var integer
     * @access protected
     +----------------------------------------------------------
     */
    var $_config = array();

    /**
     +----------------------------------------------------------
     * 配置文件类型
     +----------------------------------------------------------
     * @var integer
     * @access protected
     +----------------------------------------------------------
     */
    var $_type = array('xml','ini','object','array','define','dao');

    /**
     +----------------------------------------------------------
     * 配置文件名称
     +----------------------------------------------------------
     * @var integer
     * @access protected
     +----------------------------------------------------------
     */
    var $filename;

    /**
     +----------------------------------------------------------
     * 是否连接
     +----------------------------------------------------------
     * @var integer
     * @access protected
     +----------------------------------------------------------
     */
    var $_connect;


    /**
     +----------------------------------------------------------
     * 架构函数
     * 
     +----------------------------------------------------------
     * @access public 
     +----------------------------------------------------------
     */
    function __construct()
    {
    }


    /**
     +----------------------------------------------------------
     * 加载配置文件
     * 
     +----------------------------------------------------------
     * @access public 
     +----------------------------------------------------------
     * @param mixed $name 数据
     * @param string $filename  数据表名
     +----------------------------------------------------------
     * @return string
     +----------------------------------------------------------
     * @throws FcsException
     +----------------------------------------------------------
     */
    function parse($config,$type='') 
    {
        if(is_array($config)) {
            $confType  = 'array';
        }elseif(is_object($config)) {
            $confType  = 'object';
        }elseif(is_string($config) && empty($type) ) {
            $info = pathinfo($config);
            $confType = strtolower($info['extension']);
        }else {
            if(in_array(strtolower($type),$this->_type)) {
                $confType  = $type;
            }
        }

        $confClass = 'Config_'.ucwords(strtolower($confType));
        $configClassPath = dirname(__FILE__).'/Config/';		
        if(file_exists($configClassPath.$confClass . '.class.php')){
            include_once ($configClassPath.$confClass . '.class.php');
            $con = & new $confClass($config);
            if(!$con->connect()){
                throw_exception('无法加载配置文件或者配置文件为空！');
            }
            return $con;
        }else {
            throw_exception('系统不支持该配置文件类型！');
        }
    }


    /**
     +----------------------------------------------------------
     * 获取配置项
     * 
     +----------------------------------------------------------
     * @access public 
     +----------------------------------------------------------
     * @param string $name 数据
     +----------------------------------------------------------
     * @return string
     +----------------------------------------------------------
     * @throws FcsException
     +----------------------------------------------------------
     */
    function get($name) 
    {
        return $this->_config[$name];
    }


    /**
     +----------------------------------------------------------
     * 设置配置项
     * 
     +----------------------------------------------------------
     * @access public 
     +----------------------------------------------------------
     * @param string $name 配置项名称
     * @param string $value  配置项值
     +----------------------------------------------------------
     * @return string
     +----------------------------------------------------------
     * @throws FcsException
     +----------------------------------------------------------
     */
    function set($name,$value) 
    {
        $previous = $this->get($name);
        $this->_config[$name] = $value;
        return $previous;
    }


    /**
     +----------------------------------------------------------
     * 转换成字符串
     * 
     +----------------------------------------------------------
     * @access public 
     +----------------------------------------------------------
     * @return string
     +----------------------------------------------------------
     */
    function toString() 
    {
        
    }

    /**
     +----------------------------------------------------------
     * 转换成数组，如果指定文件则保存到数组文件
     * 
     +----------------------------------------------------------
     * @access public 
     +----------------------------------------------------------
     * @param string $filename  文件名
     +----------------------------------------------------------
     * @return array
     +----------------------------------------------------------
     */
    function toArray($filename='') 
    {
        if(!empty($filename) && file_exists(dirname($filename))) {
            $content  = "<?php\n\r";
            $content .= "return ".var_export($this->_config,true);
            $content .= ";\n\r?>";
            file_put_contents($filename,$content);
            return ;
        }
        return $this->_config;
    }

    /**
     +----------------------------------------------------------
     * 把配置文件转换成XML字串
     * 如果指定文件则保存到xml文件
     +----------------------------------------------------------
     * @access public 
     +----------------------------------------------------------
     * @param string $root XML文件的根节点名
     * @param string $encoding  XML文件编码
     * @param string $filename  文件名
     +----------------------------------------------------------
     * @return string|void
     +----------------------------------------------------------
     */
    function toXML($root='config',$encoding='utf-8',$filename='') 
    {
        $xml = '<?xml version="1.0" encoding="'.$encoding.'"?>';
        $xml.= '<'.$root.'>';
        $xml.= $this->arrayToXml($this->toArray());   
        $xml.= '</'.$root.'>'; 
        if(!empty($filename) && file_exists(dirname($filename))) {
            file_put_contents($filename,$xml);
            return ;
        }
        return $xml; 
    }

    /**
     +----------------------------------------------------------
     * 把数组转换成XML节点
     * 
     +----------------------------------------------------------
     * @access public 
     +----------------------------------------------------------
     * @param string $root XML文件的根节点名
     * @param string $encoding  XML文件编码
     +----------------------------------------------------------
     * @return string
     +----------------------------------------------------------
     */
    function arrayToXml($array) {
        $xml = '';
        foreach($array as $key=>$val) {
            is_numeric($key) && $key="item id=\"$key\"";
            $xml.="<$key>";
            $xml.=is_array($val)?$this->arrayToXml($val):$val;
            list($key,)=explode(' ',$key);
            $xml.="</$key>";
        }
        return $xml;
    }


    /**
     +----------------------------------------------------------
     * 转换成INI文件
     * 如果指定文件名，则生成ini文件 否则返回ini字符串
     +----------------------------------------------------------
     * @access public 
     +----------------------------------------------------------
     * @param string $filename  文件名
     +----------------------------------------------------------
     * @return string|void
     +----------------------------------------------------------
     */
    function toIni($filename='') 
    {
        $ini = $this->arrayToIni($this->toArray());
        if(!empty($filename) && file_exists(dirname($filename))) {
            file_put_contents($filename,$ini);
            return ;
        }
        return $ini;
    }

    /**
     +----------------------------------------------------------
     * 把数组转换成ini文件内容
     * 
     +----------------------------------------------------------
     * @access public 
     +----------------------------------------------------------
     * @param array $array  数组
     +----------------------------------------------------------
     * @return string
     +----------------------------------------------------------
     */
    function arrayToIni($array) 
    {
        foreach($array as $key => $val) {
         if(is_array($val)) {
           $content .= "\n[{$key}]\n";
           foreach ($val as $key2 => $val2) {
               if(is_int($val2) || is_float($val2)) {
                   $content .= "{$key2} = {$val2}\n";
               }
               elseif(is_bool($val2)) {
                   $val2 = ($val2)?'true':'false';
                   $content .= "{$key2} = {$val2}\n";
               }
               else{
                   $content .= "{$key2} = \"{$val2}\"\n";
               }
           }        
         }
         else
         {
           if(is_int($val) || is_float($val)) {
               $content .= "{$key} = {$val}\n";
           }
           elseif(is_bool($val)) {
               $val = ($val)?'on':'off';
               $content .= "{$key} = {$val}\n";
           }
           else{
               $content .= "{$key} = \"{$val}\"\n";
           }
         }
       }   
       return $content;
    }

    /**
     +----------------------------------------------------------
     * 转换成常量
     * 
     +----------------------------------------------------------
     * @param array $filename 需要输出的常量定义文件名
     * @param array $prefix 常量前缀
     +----------------------------------------------------------
     * @access public 
     +----------------------------------------------------------
     * @return void
     +----------------------------------------------------------
     */
    function toConst($filename='',$prefix='') 
    { 
        $content = $this->arrayToConst($this->_config,$prefix);
        if(!empty($filename) && file_exists(dirname($filename))) {
            file_put_contents($filename,$content);
        }
        return ;
    }


    /**
     +----------------------------------------------------------
     * 把数组转换成系统常量 键名作为常量名，并转换为大写
     +----------------------------------------------------------
     * @access public 
     +----------------------------------------------------------
     * @param array $array 数组
     * @param array $prefix 常量前缀
     +----------------------------------------------------------
     * @return void
     +----------------------------------------------------------
     */
    function arrayToConst($array,$prefix='') 
    {
        $content  = "<?php\n\r";
        $content .= "/**\n\r";
        $content .= "+------------------------------------------------------------------------------\n\r";
        $content .= "* 项目配置文件 本配置文件由系统自动生成\n\r";
        $content .= "+------------------------------------------------------------------------------\n\r";
        $content .= "*/\n\r";
        $content .= "if (!defined('FCS_PATH')) exit();\n\r";
        foreach($array as $key=>$val) {
            if(!defined(strtoupper($prefix.$key))) {
                define(strtoupper($prefix.$key),$val);
            }
            if(is_int($val) || is_float($val)) {
                $content .= "define('".strtoupper($prefix.$key)."',".$val."); \n\r";   
            }
            elseif(is_bool($val)) {
                $val = ($val)?'true':'false';
                $content .= "define('".strtoupper($prefix.$key)."',".$val."); \n\r";  
            }
            elseif(is_string($val)) {
                $content .= "define('".strtoupper($prefix.$key)."','".addslashes($val)."'); \n\r";   
            }
        }
        $content .= "\n\r//配置文件定义结束";
        $content .= "\n\r?>";
		return $content;
    }

    /**
     +----------------------------------------------------------
     * 转换成对象
     * 
     +----------------------------------------------------------
     * @access public 
     +----------------------------------------------------------
     * @return object
     +----------------------------------------------------------
     */
    function toObject() 
    {	
        $object = new stdClass();
        foreach($this->_config as $key=>$val) {
            $object->$key = $val;
        }
        return $object;
    }


}//类定义结束
?>