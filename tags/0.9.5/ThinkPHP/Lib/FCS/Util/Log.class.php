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
// $Id: Log.class.php 11 2007-01-04 03:57:34Z liu21st $

/**
 +------------------------------------------------------------------------------
 * 日志处理类 在日志处理类中不抛出异常，而使用halt方法
 * 因为异常处理类中包含了日志记录 会导致循环错误
 +------------------------------------------------------------------------------
 * @author    liu21st <liu21st@gmail.com>
 * @version   $Id: Log.class.php 11 2007-01-04 03:57:34Z liu21st $
 +------------------------------------------------------------------------------
 */

class Log extends Base
{//类定义开始

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
     * 日志写入
     * 
     +----------------------------------------------------------
     * @static
     * @access public 
     +----------------------------------------------------------
     * @param string $message 日志信息
     * @param string $type  日志类型
     * WEB_LOG_DEBUG 调试信息
     * WEB_LOG_ERROR 错误信息
     * @param string $file  写入文件 默认取定义日志文件
     * WEB_LOG_DEBUG类型取系统日志目录下面的 systemOut.log
     * WEB_LOG_ERROR类型取系统日志目录下面的 systemErr.log
     +----------------------------------------------------------
     * @throws FcsException
     +----------------------------------------------------------
     */
    function write($message,$type=WEB_LOG_ERROR,$file='')
    {
        $now = date('[ y-m-d H:i:s ]');
        switch($type){
            case WEB_LOG_DEBUG:
                $logType ='[调试]';
                $destination = $file == ''? LOG_PATH.date('y_m_d')."_systemOut.log" : $file;
                break;
            default :
                $logType ='[错误]';
                $destination = $file == ''? LOG_PATH.date('y_m_d')."_systemErr.log" : $file;
        }
        if(!is_writable(LOG_PATH)){
            halt(_FILE_NOT_WRITEABLE_.':'.$destination);
        }
        //检测日志文件大小，超过配置大小则备份日志文件重新生成
        if(file_exists($destination)) {
            if( defined('LOG_FILE_SIZE')  && floor(LOG_FILE_SIZE) <= filesize($destination) ){
                  rename($destination,dirname($destination).'/'.time().'-'.basename($destination));
            }        	
        }
        error_log("$now\n$message\n", FILE_LOG,$destination );

    }

}//类定义结束
?>