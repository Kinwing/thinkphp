<?php 
// +---------------------------------------------------------------------------+
// | FCS -- Fast,Compatible & Simple OOP PHP Framework                         |
// +---------------------------------------------------------------------------+
// | Copyright (c) 2005-2006 liu21st.com.  All rights reserved.                |
// | Website: http://www.fcs.org.cn/                                           |
// | Author : Liu21st ���� <liu21st@gmail.com>                                 |
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
 * @version    $Id$
 +------------------------------------------------------------------------------
 */

import("FCS.Util.ArrayList");

/**
 +------------------------------------------------------------------------------
 * ���ݼ��� ������size()������ȡ���ݼ�������
 +------------------------------------------------------------------------------
 * @package   Db
 * @author    liu21st <liu21st@gmail.com>
 * @version   0.8.0
 +------------------------------------------------------------------------------
 */
class ResultSet extends ArrayList
{

    /**
     +----------------------------------------------------------
     * �ܹ�����
     * 
     +----------------------------------------------------------
     * @access public 
     +----------------------------------------------------------
     * @param string $array  ��ʼ������Ԫ��
     +----------------------------------------------------------
     */
    function __construct($array=array())
    {
        parent::__construct($array);
    }

    /**
     +----------------------------------------------------------
     * ȡ�õ�ǰ��֤�ŵĲ���Ȩ���б�
     * 
     +----------------------------------------------------------
     * @param string $appPrefix ���ݿ�ǰ׺
     +----------------------------------------------------------
     * @access public 
     +----------------------------------------------------------
     */
    function toVoList($voClass) 
    {
        $voList     = new VoList();
        foreach ($this->getIterator() as $result)
        {
            if(!empty($result)){
                $vo     = new $voClass($result);
                $voList->add($vo);
            }
        }
        return $voList;    	
    }

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
        return new ResultSet($this->range($offset,$length));
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
    function getCol($field) 
    {
        if(is_string($field)) {
            $field	=	explode(',',$field);
        }        
        $array      =   array();
        foreach($this->getIterator() as $key=>$val) {
            if(!array_key_exists($field[0],$val)) {
                break;
            }
            if(count($field)>1) {
                $array[$val[$field[0]]] = '';
                $length	 = count($field);
                for($i=1; $i<$length; $i++) {
                    if(array_key_exists($field[$i],$val)) {
                        $array[$val[$field[0]]] .= $val[$field[$i]];
                    }
                }
            }else {
                $array[] = $val[$field[0]];
            }
        }
        return $array;
    }
};
?>