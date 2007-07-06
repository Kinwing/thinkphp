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
 * @package    Util
 * @link       http://www.fcs.org.cn
 * @copyright  Copyright (c) 2005-2006 liu21st.com.  All rights reserved. 
 * @author     liu21st <liu21st@gmail.com>
 * @version    $Id$
 +------------------------------------------------------------------------------
 */

import("FCS.Util.ArrayList");

/**
 +------------------------------------------------------------------------------
 * Stackʵ����
 +------------------------------------------------------------------------------
 * @package   Util
 * @author    liu21st <liu21st@gmail.com>
 * @version   0.8.0
 +------------------------------------------------------------------------------
 */
class Stack extends ArrayList
{//�ඨ�忪ʼ

    /**
     +----------------------------------------------------------
     * �ܹ�����
     * 
     +----------------------------------------------------------
     * @access public 
     +----------------------------------------------------------
     * @param array $values  ��ʼ������Ԫ��
     +----------------------------------------------------------
     */
    function __construct($values = array())
    {
        parent::__construct($values);
    }

    /**
     +----------------------------------------------------------
     * ����ջ���ڲ�ָ��ָ���һ����Ԫ
     * 
     +----------------------------------------------------------
     * @access public 
     +----------------------------------------------------------
     * @return mixed
     +----------------------------------------------------------
     */
    function peek()
    {
        return reset($this->toArray());
    }

    /**
     +----------------------------------------------------------
     * ���һ��Ԫ�س�ջ
     * 
     +----------------------------------------------------------
     * @access public 
     +----------------------------------------------------------
     * @return mixed
     +----------------------------------------------------------
     */
    function pop()
    {
        $el_array = $this->toArray();
        $return_val = array_pop($el_array);
        $this->_elements = $el_array;
        return $return_val;
    }

    /**
     +----------------------------------------------------------
     * Ԫ�ؽ�ջ
     * 
     +----------------------------------------------------------
     * @access public 
     +----------------------------------------------------------
     * @param mixed $value 
     +----------------------------------------------------------
     * @return mixed
     +----------------------------------------------------------
     */
    function push($value)
    {
        $this->add($value);
        return $value;
    }

}//�ඨ�����
?>
