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
// $Id: VoList.class.php 33 2007-02-25 07:06:02Z liu21st $

import("Think.Util.ArrayList");

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
 * @version   $Id: VoList.class.php 33 2007-02-25 07:06:02Z liu21st $
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
     * @throws ThinkExecption
     +----------------------------------------------------------
     */
    function toJson($fields=array()) 
    {
        if(empty($this->json)) {
            $json = '';
            foreach ($this->getIterator() as $vo)
            {
                if(!empty($vo)){
                    $json .= $vo->toJson($fields).',';
                }
            }
            $this->json = '['.substr($json,0,-1).']';
        }
        return $this->json;
    }
    /*
    function toJson($fields=array()) 
    {
        if(empty($this->json)) {
            $this->json = json_encode($this->toResultSet);
        }
        return $this->json;
    }*/

    /**
     +----------------------------------------------------------
     * ת��Ϊ���ݼ�
     * 
     +----------------------------------------------------------
     * @access public 
     +----------------------------------------------------------
     * @return string
     +----------------------------------------------------------
     * @throws ThinkExecption
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
     * @throws ThinkExecption
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
     * @throws ThinkExecption
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
     * @throws ThinkExecption
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
     * @throws ThinkExecption
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
     * @throws ThinkExecption
     +----------------------------------------------------------
     */
    function getVoClass() 
    {
    	$vo  =  $this->get(0);
        return get_class($vo);
    }
}//�ඨ�����
?>