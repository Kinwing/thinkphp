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
// $Id: FileIterator.class.php 11 2007-01-04 03:57:34Z liu21st $

if(version_compare(PHP_VERSION, '5.0.0', '<')){
    
    import("FCS.Util.ListIterator");
    import("FCS.Util.ArrayObject");
    /**
     +------------------------------------------------------------------------------
     * �ļ������� PHP4ʵ��
     +------------------------------------------------------------------------------
     * @author    liu21st <liu21st@gmail.com>
     * @version   $Id: FileIterator.class.php 11 2007-01-04 03:57:34Z liu21st $
     +------------------------------------------------------------------------------
     */
    class FileIterator extends ListIterator
    {//�ඨ�忪ʼ

        /**
         +----------------------------------------------------------
         * �ļ���������
         +----------------------------------------------------------
         * @var array
         * @access protected
         +----------------------------------------------------------
         */
        var $_line = array();

        /**
         +----------------------------------------------------------
         * �ܹ����� ���Լ̳�ListIterator�����з���
         * 
         +----------------------------------------------------------
         * @access public 
         +----------------------------------------------------------
         * @param string $filename  �ļ���
         * @param string $buffer  �����ȡ��С
         +----------------------------------------------------------
         */
        function __construct($filename, $buffer = 1024) 
        {
            $this->readLine($filename,$buffer);
            parent::__construct($this->_line);
        }

        /**
         +----------------------------------------------------------
         * ��ȡ�ļ�����
         * 
         +----------------------------------------------------------
         * @access public 
         +----------------------------------------------------------
         * @param mixed $filename ·��
         * @param mixed $buffer �����ȡ��С
         +----------------------------------------------------------
         */
        function readLine($filename,$buffer)
        {
            $i = 0;
            $line = array();
            $fp = fopen($filename, 'rb');
            while (!feof($fp)) {
                $line[$i] = fgets($fp, $buffer);
                $i++;
            }
            fclose($fp);
            $this->_line = $line;
        }

        /**
         +----------------------------------------------------------
         * ʹ��foreach����
         * 
         +----------------------------------------------------------
         * @access public 
         +----------------------------------------------------------
         * @param mixed $filename ·��
         * @param mixed $buffer �����ȡ��С
         +----------------------------------------------------------
         */
        function getIterator()
        {
             return new ArrayObject($this->_line);
        }

    }//�ඨ�����

}else {
    //����PHP5֧�ֵ�FileIterator��
	import("FCS.Util._FileIterator");
}
?>