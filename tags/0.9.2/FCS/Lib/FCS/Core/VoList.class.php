<?php 
// +---------------------------------------------------------------------------+
// | FCS -- Fast,Compatible & Simple OOP PHP Framework                         |
// +---------------------------------------------------------------------------+
// | Copyright (c) 2005-2006 liu21st.com.  All rights reserved.                |
// | Website: http://www.fcs.org.cn/                                           |
// | Author : Liu21st <liu21st@gmail.com>                                      |
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
 * @version    $Id: VoList.class.php 83 2006-11-10 02:08:52Z fcs $
 +------------------------------------------------------------------------------
 */

import("FCS.Util.ArrayList");

/**
 +------------------------------------------------------------------------------
 * �����б������ �̳���ArrayList��
 * VoList->getIterator() �������Ի�õ�����
 * VoList->size()��������б���
 * VoList->getRange() ����б������Ӽ�
 * VoList->get() ����б�����ĳһ��
 * VoList->set() ����ĳһ�����ݵ�ֵ
 * VoList->sortBy() ���б�����
 +------------------------------------------------------------------------------
 * @package   core
 * @author    liu21st <liu21st@gmail.com>
 * @version   0.8.0
 +------------------------------------------------------------------------------
 */
class VoList extends ArrayList
{

    /**
     +----------------------------------------------------------
     * ԭʼ���ݼ�
     +----------------------------------------------------------
     * @var array
     * @access protected
     +----------------------------------------------------------
     */
    var $resultSet  = array();

    /**
     +----------------------------------------------------------
     * ԭʼ���ݼ�
     +----------------------------------------------------------
     * @var array
     * @access protected
     +----------------------------------------------------------
     */
    var $rowNums  = 0;


    /**
     +----------------------------------------------------------
     * Json ����ַ���
     +----------------------------------------------------------
     * @var string
     * @access protected
     +----------------------------------------------------------
     */
    var $json = '';

    /**
     +----------------------------------------------------------
     * ��ȡ�����б������Ӽ�
     * �����б��ҳ��ʾ
     +----------------------------------------------------------
     * @access public 
     +----------------------------------------------------------
     * @param integer $offset ��ʼλ��
     * @param integer $length ����
     +----------------------------------------------------------
     * @return VoList
     +----------------------------------------------------------
     */
    function getRange($offset,$length=NULL)
    {
        return new VoList($this->range($offset,$length));
    }

    /**
     +----------------------------------------------------------
     * ת��ΪJson
     * 
     +----------------------------------------------------------
     * @access public 
     +----------------------------------------------------------
     * @return string
     +----------------------------------------------------------
     * @throws FcsException
     +----------------------------------------------------------
     */
    function toJson() 
    {
        if(empty($this->json)) {
            $json = '';
            foreach ($this->getIterator() as $vo)
            {
                if(!empty($vo)){
                    $json .= $vo->toJson().',';
                }
            }
            $this->json = '['.substr($json,0,-1).']';
        }
        return $this->json;
    }

    /**
     +----------------------------------------------------------
     * ת��Ϊ���ݼ�
     * 
     +----------------------------------------------------------
     * @access public 
     +----------------------------------------------------------
     * @return string
     +----------------------------------------------------------
     * @throws FcsException
     +----------------------------------------------------------
     */
    function toResultSet() 
    {
        if(empty($this->resultSet)) {
            $resultSet = array();
            foreach($this->getIterator() as $key=>$vo) {
                $result = get_object_vars($vo);
                $resultSet[$key] = $result;
            }
            $this->resultSet = $resultSet;
        }
        return $this->resultSet;
    }


    /**
     +----------------------------------------------------------
     * ȡ��ĳ���ֶε�����
	 * field����֧��������ַ�������,�ָ�)
     * ͨ����������volist��select���
     +----------------------------------------------------------
     * @access public 
     +----------------------------------------------------------
     * @param mixed $field vo�ֶ���
     +----------------------------------------------------------
     * @return string
     +----------------------------------------------------------
     * @throws FcsException
     +----------------------------------------------------------
     */
    function getCol($field,$sepa='') 
    {
        if(is_string($field)) {
            $field	=	explode(',',$field);
        }        
        $resultSet  = $this->toResultSet();
        $array      =   array();
        foreach($resultSet as $key=>$val) {
            if(!array_key_exists($field[0],$val)) {
                break;
            }
            if(count($field)>1) {
                $array[$val[$field[0]]] = '';
                $length	 = count($field);
                for($i=1; $i<$length; $i++) {
                    if(array_key_exists($field[$i],$val)) {
                        $array[$val[$field[0]]] .= $val[$field[$i]].$sepa;
                    }
                }
            }else {
                $array[] = $val[$field[0]];
            }
        }
        return $array;
    }

    /**
     +----------------------------------------------------------
     * �����ݼ������ȡnumber��Vo
     * 
     +----------------------------------------------------------
     * @access public 
     +----------------------------------------------------------
     * @param Integer $number �������
     +----------------------------------------------------------
     * @return mixed
     +----------------------------------------------------------
     * @throws FcsException
     +----------------------------------------------------------
     */
    function getRand($number=1) 
    {
        $resultSet = $this->toArray();
        $list   =   array_rand($resultSet,$number);
        if($number===1) {
            $list   =   $resultSet[$list];
        }
        return $list;
    }


    /**
     +----------------------------------------------------------
     * ת��Ϊ�ַ���
     * ��ʽΪCSV��ʽ
     +----------------------------------------------------------
     * @access public 
     +----------------------------------------------------------
     * @return string
     +----------------------------------------------------------
     * @throws FcsException
     +----------------------------------------------------------
     */
    function toString() 
    {
        $resultSet = $this->toResultSet();
        $str = '';
        foreach($resultSet as $key=>$val) {
            $str .= implode(',',$val)."\n";
        }
        return $str;
    }

    /**
     +----------------------------------------------------------
     * �б��������
     * 
     +----------------------------------------------------------
     * @access public 
     +----------------------------------------------------------
     * @return string
     +----------------------------------------------------------
     * @throws FcsException
     +----------------------------------------------------------
     */
    function sortBy($field,$sort='desc') 
    {
        $resultSet = array();
        foreach($this->getIterator() as $key=>$vo) {
            $resultSet[$vo->$field] = $vo;
        }
        ($sort=='desc')? krsort($resultSet):ksort($resultSet);
        return new VoList($resultSet);
    }

    /**
     +----------------------------------------------------------
     * ��ȡVo��������
     * 
     +----------------------------------------------------------
     * @access public 
     +----------------------------------------------------------
     * @return string
     +----------------------------------------------------------
     * @throws FcsException
     +----------------------------------------------------------
     */
    function getVoClass() 
    {
    	$vo  =  $this->get(0);
        return get_class($vo);
    }
}//�ඨ�����
?>