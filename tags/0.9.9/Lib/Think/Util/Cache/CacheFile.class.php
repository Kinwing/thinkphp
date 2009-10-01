<?php
// +----------------------------------------------------------------------
// | ThinkPHP
// +----------------------------------------------------------------------
// | Copyright (c) 2008 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------
// $Id$

/**
 +------------------------------------------------------------------------------
 * 文件类型缓存类
 +------------------------------------------------------------------------------
 * @category   Think
 * @package  Think
 * @subpackage  Util
 * @author    liu21st <liu21st@gmail.com>
 * @version   $Id$
 +------------------------------------------------------------------------------
 */
class CacheFile extends Cache
{//类定义开始

    /**
     +----------------------------------------------------------
     * 架构函数
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     */
    function __construct($options='')
    {
        if(!empty($options['temp'])){
            $this->options['temp'] = $options['temp'];
        }else {
            $this->options['temp'] = TEMP_PATH;
        }
        $this->expire = isset($options['expire'])?$options['expire']:C('DATA_CACHE_TIME');
        if(substr($this->options['temp'], -1) != "/")    $this->options['temp'] .= "/";
        $this->connected = is_dir($this->options['temp']) && is_writeable($this->options['temp']);
        $this->type = strtoupper(substr(__CLASS__,6));
        $this->init();

    }

    /**
     +----------------------------------------------------------
     * 初始化检查
     +----------------------------------------------------------
     * @access private
     +----------------------------------------------------------
     * @return boolen
     +----------------------------------------------------------
     */
    function init()
    {
        $stat = stat($this->options['temp']);
		$dir_perms = $stat['mode'] & 0007777; // Get the permission bits.
		$file_perms = $dir_perms & 0000666; // Remove execute bits for files.

		// 创建项目缓存目录
		if (!file_exists($this->options['temp'])) {
			if (!  mkdir($this->options['temp']))
				return false;
			 chmod($this->options['temp'], $dir_perms);
		}
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
     * 取得变量的存储文件名
     +----------------------------------------------------------
     * @access private
     +----------------------------------------------------------
     * @param string $name 缓存变量名
     +----------------------------------------------------------
     * @return string
     +----------------------------------------------------------
     * @throws ThinkExecption
     +----------------------------------------------------------
     */
    function filename($name)
    {
		$name	=	md5($name);
		if(C('DATA_CACHE_SUBDIR')) {
			// 使用子目录
			$dir	=	$name{0};
			if(!is_dir($this->options['temp'].$dir)) {
				mkdir($this->options['temp'].$dir);
			}
			$filename	=	$dir.'/'.$this->prefix.$name.'.php';
		}else{
			$filename	=	$this->prefix.$name.'.php';
		}
        return $this->options['temp'].$filename;
    }

    /**
     +----------------------------------------------------------
     * 读取缓存
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
        $filename   =   $this->filename($name);
        if (!$this->isConnected() || !file_exists($filename)) {
           return false;
        }
		$this->Q(1);
        $content    =   file_get_contents($filename);
        if( false !== $content) {
            $expire  =  (int)substr($content,strlen(C('CACHE_SERIAL_HEADER')), 12);
            if($expire != -1 && time() > filemtime($filename) + $expire) {
                //缓存过期删除缓存文件
                unlink($filename);
                return false;
            }
            if(C('DATA_CACHE_CHECK')) {//开启数据校验
                $check  =  substr($content,strlen(C('CACHE_SERIAL_HEADER'))+12, 32);
                $content   =  substr($content,strlen(C('CACHE_SERIAL_HEADER'))+12+32, -strlen(C('CACHE_SERIAL_FOOTER')));
                if($check != md5($content)) {//校验错误
                    return false;
                }
            }else {
            	$content   =  substr($content,strlen(C('CACHE_SERIAL_HEADER'))+12, -strlen(C('CACHE_SERIAL_FOOTER')));
            }
            if(C('DATA_CACHE_COMPRESS') && function_exists('gzcompress')) {
                //启用数据压缩
                $content   =   gzuncompress($content);
            }
            $content    =   unserialize($content);
            return $content;
        }
        else {
            return false;
        }
    }

    /**
     +----------------------------------------------------------
     * 写入缓存
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @param string $name 缓存变量名
     * @param mixed $value  存储数据
     * @param int $expire  有效时间 -1 为永久
     +----------------------------------------------------------
     * @return boolen
     +----------------------------------------------------------
     */
    function set($name,$value,$expire='')
    {
		$this->W(1);
        if(empty($expire)) {
        	$expire =  $this->expire;
        }
        $filename   =   $this->filename($name);
        $data   =   serialize($value);
        if( C('DATA_CACHE_COMPRESS') && function_exists('gzcompress')) {
            //数据压缩
            $data   =   gzcompress($data,3);
        }
        if(C('DATA_CACHE_CHECK')) {//开启数据校验
        	$check  =  md5($data);
        }else {
        	$check  =  '';
        }
        $data    = C('CACHE_SERIAL_HEADER').sprintf('%012d',$expire).$check.$data.C('CACHE_SERIAL_FOOTER');
        $result  =   file_put_contents($filename,$data);
        if($result) {
            clearstatcache();
            return true;
        }else {
        	return false;
        }
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
        return unlink($this->filename($name));
    }

    /**
     +----------------------------------------------------------
     * 清除缓存
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @param string $name 缓存变量名
     +----------------------------------------------------------
     * @return boolen
     +----------------------------------------------------------
     */
    function clear()
    {
        import("ORG.Io.Dir");
        Dir::del($this->options['temp']);
    }

}//类定义结束
?>