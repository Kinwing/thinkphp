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

import("FCS.Util.ArrayList");

/**
 +------------------------------------------------------------------------------
 * Stackʵ����
 +------------------------------------------------------------------------------
 * @author    liu21st <liu21st@gmail.com>
 * @version   $Id$
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
