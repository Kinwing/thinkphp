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

import("Think.Util.ArrayList");
/**
 +------------------------------------------------------------------------------
 * ���ݼ��� ������size()������ȡ���ݼ�������
 +------------------------------------------------------------------------------
 * @package   Db
 * @author    liu21st <liu21st@gmail.com>
 * @version   $Id$
 +------------------------------------------------------------------------------
 */
class ResultSet extends ArrayList
{

    /**
     +----------------------------------------------------------
     * �ܹ�����
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
     * @throws ThinkExecption
     +----------------------------------------------------------
     */
    function getCol($field) 
    {
        if(is_string($field)) {
            $field	=	explode(',',$field);
        }        
        $array      =   array();
        foreach($this->toArray() as $key=>$val) {
            if(is_object($val)) {
            	$val  = get_object_vars($val);
            }
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