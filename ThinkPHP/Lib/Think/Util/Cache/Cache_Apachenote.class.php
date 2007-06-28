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

/**
 +------------------------------------------------------------------------------
 * Apachenote缓存类
 +------------------------------------------------------------------------------
 * @author    liu21st <liu21st@gmail.com>
 * @version   $Id$
 +------------------------------------------------------------------------------
 */
class Cache_Apachenote extends Cache
{//类定义开始


    /**
     +----------------------------------------------------------
     * 架构函数
     * 
     +----------------------------------------------------------
     * @access public 
     +----------------------------------------------------------
     */
    function __construct($options)
    {
        if(empty($options)){
            $options = array(           
                'host' => '127.0.0.1',
                'port' => 1042,
                'timeout' => 10
        );
        }
        $this->handler = null;
        $this->open();
        $this->options = $options;
        $this->type = strtoupper(substr(__CLASS__,6));

    }

    /**
     +----------------------------------------------------------
     * 是否连接
     +----------------------------------------------------------
     * @access public 
     +----------------------------------------------------------
     * @return boolen
     +----------------------------------------------------------
     */
    function isConnected()
    {
        return $this->connected;
    }


    /**
     +----------------------------------------------------------
     * 读取缓存
     * 
     +----------------------------------------------------------
     * @access public 
     +----------------------------------------------------------
     * @param string $name 缓存变量名
     +----------------------------------------------------------
     * @return mixed
     +----------------------------------------------------------
     */
     function get($name)
     {
         $this->open();
         $s = 'F' . pack('N', strlen($name)) . $name;
         fwrite($this->handler, $s);

         for ($data = ''; !feof($this->handler);) {
             $data .= fread($this->handler, 4096);
         }

         $this->close();
         return $data === '' ? '' : unserialize($data);
     }

    /**
     +----------------------------------------------------------
     * 写入缓存
     +----------------------------------------------------------
     * @access public 
     +----------------------------------------------------------
     * @param string $name 缓存变量名
     * @param mixed $value  存储数据
     +----------------------------------------------------------
     * @return boolen
     +----------------------------------------------------------
     */
    function set($name, $value)
    {
        $this->open();
        $value = serialize($value);
        $s = 'S' . pack('NN', strlen($name), strlen($value)) . $name . $value;

        fwrite($this->handler, $s);
        $ret = fgets($this->handler);
        $this->close();
        $this->setTime[$name] = time();
        return $ret === "OK\n";
    }

    /**
     +----------------------------------------------------------
     * 删除缓存
     +----------------------------------------------------------
     * @access public 
     +----------------------------------------------------------
     * @param string $name 缓存变量名
     +----------------------------------------------------------
     * @return boolen
     +----------------------------------------------------------
     */
     function rm($name)
     {
         $this->open();
         $s = 'D' . pack('N', strlen($name)) . $name;
         fwrite($this->handler, $s);
         $ret = fgets($this->handler);
         $this->close();

         return $ret === "OK\n";
     }

    /**
     +----------------------------------------------------------
     * 关闭缓存
     +----------------------------------------------------------
     * @access public 
     +----------------------------------------------------------
     */
     function close()
     {
         fclose($this->handler);
         $this->handler = false;
     }

    /**
     +----------------------------------------------------------
     * 打开缓存
     +----------------------------------------------------------
     * @access public 
     +----------------------------------------------------------
     */
     function open()
     {
         if (!is_resource($this->handler)) {
             $this->handler = fsockopen($this->options['host'], $this->options['port'], $_, $_, $this->options['timeout']);
             $this->connected = is_resource($this->handler);         
         }
     }

}//类定义结束
?>