<?php 
// +---------------------------------------------------------------------------+
// | FCS -- Fast,Compatible & Simple OOP PHP Framework                         |
// +---------------------------------------------------------------------------+
// | Copyright (c) 2005-2006 liu21st.com.  All rights reserved.                |
// | Website: http://www.fcs.org.cn/                                           |
// | Author : Liu21st 流年 <liu21st@gmail.com>                                 |
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
 * FCS公共函数库
 +------------------------------------------------------------------------------
 * @package    Common
 * @link       http://www.fcs.org.cn
 * @copyright  Copyright (c) 2005-2006 liu21st.com.  All rights reserved. 
 * @author     liu21st <liu21st@gmail.com>
 * @version    $Id: functions.php 116 2006-12-08 09:25:50Z fcs $
 +------------------------------------------------------------------------------
 */
/**
 +----------------------------------------------------------
 * 检测浏览器语言
 +----------------------------------------------------------
 * @return string
 +----------------------------------------------------------
 */
function detect_browser_language()
{
    if ( isset($_GET[VAR_LANGUAGE]) ) {
        $langSet = $_GET[VAR_LANGUAGE];
        setcookie('FCS_'.VAR_LANGUAGE,$langSet,time()+3600,'/');
    } else {
        if ( !isset($_COOKIE['FCS_'.VAR_LANGUAGE]) ) {
            preg_match('/^([a-z\-]+)/i', $_SERVER['HTTP_ACCEPT_LANGUAGE'], $matches);
            $langSet = $matches[1];
            setcookie('FCS_'.VAR_LANGUAGE,$langSet,time()+3600,'/');
        }
        else {
            $langSet = $_COOKIE['FCS_'.VAR_LANGUAGE];
        }
    }
    return $langSet;
}

/**
 +----------------------------------------------------------
 * 检测服务器是否支持URL Rewrite
 +----------------------------------------------------------
 * @return string
 +----------------------------------------------------------
 */
function checkModRewrite() 
{
    static $_modRewrite;
    if(isset($_modRewrite)) {
        return $_modRewrite;
    }
    $_modRewrite = true;
    if ( ! IS_APACHE ){
        $_modRewrite = false;
    }elseif ( function_exists('apache_get_modules') ) {
        if ( !in_array('mod_rewrite', apache_get_modules()) )
            $_modRewrite = false;
    }
    return $_modRewrite;
}

/**
 +----------------------------------------------------------
 * 错误输出 
 * 在调试模式下面会输出详细的错误信息
 * 否则就定向到指定的错误页面
 +----------------------------------------------------------
 * @param mixed $error 错误信息 可以是数组或者字符串
 * 数组格式为异常类专用格式 不接受自定义数组格式
 +----------------------------------------------------------
 * @return void
 +----------------------------------------------------------
 */
function halt($error) {

    //如果配置文件还没有加载，则使用exit方法输出错误
    if(!defined('TEMPLATE_NAME')) {
        if(is_array($error)) {
            exit($error['message']);
        }else {
            exit($error);
        }
    }
    //读取错误模板文件
    $tpl    =   get_instance_of('Template');
    $tpl->assign('publicCss',APP_PUBLIC_URL.'/css/FCS.css');

    if(DEBUG_MODE){//调试模式下输出错误信息
        if(!file_exists(TEMPLATE_PATH.'/Public/debug.html')) {
            exit($error);
        }
        if(is_array($error)){//抛出异常
            $tpl->assign("exception",true);
        }
        $tpl->assign("error",$error);
        $tpl->display(TEMPLATE_PATH.'/Public/debug.html');

    }else {//否则定向到错误页面
        if(ERROR_PAGE!=''){
            redirect(ERROR_PAGE); 
        }else {
            if(!file_exists(TEMPLATE_PATH.'/Public/error.html')) {
                exit(ERROR_MESSAGE);
            }
            $tpl->assign("error",ERROR_MESSAGE);
            $tpl->display(TEMPLATE_PATH.'/Public/error.html');
        }
    }
    exit;
}

/**
 +----------------------------------------------------------
 * URL重定向
 * 
 +----------------------------------------------------------
 * @static
 * @access public 
 +----------------------------------------------------------
 * @param string $url  要定向的URL地址
 * @param integer $time  定向的延迟时间，单位为秒
 * @param string $msg  提示信息
 +----------------------------------------------------------
 * @throws FcsException
 +----------------------------------------------------------
 */
function redirect($url,$time=0,$msg='')
{
    //多行URL地址支持
    $url = str_replace(array("\n", "\r"), '', $url);
    if(empty($msg)) {
        $msg    =   "系统将在{$time}秒之后自动跳转到{$url}！";
    }
    if (!headers_sent()) {
        // redirect
        if(0===$time) {
        	header("Location: ".$url); 
        }else {
            header("refresh:{$time};url={$url}");
            echo($msg);
        }
        exit();
    }else {
        $str    = "<meta http-equiv='Refresh' content='{$time};URL={$url}'>";
        if($time!=0) {
            $str   .=   $msg;
        }
        exit($str);
    }
}

/**
 +----------------------------------------------------------
 * 自定义异常处理 支持 PHP4和PHP5
 +----------------------------------------------------------
 * @param string $msg 错误信息
 * @param string $type 异常类型 默认为FcsException
 * 如果指定的异常类不存在，则直接输出错误信息
 +----------------------------------------------------------
 * @return void
 +----------------------------------------------------------
 */
function throw_exception($msg,$type='FcsException',$code=0)
{
    if(class_exists($type)){
        if(version_compare(PHP_VERSION, '5.0.0', '<')){
            $e = & new $type($msg,$code);
            halt($e->__toString());
        }else {
            // PHP5使用 throw关键字抛出异常
            // 出于兼容考虑包含下面语句实现
            // throw new $type($msg,$code);
        	include('_throw_exception.php');
        }
    }else {// 异常类型不存在则输出错误信息字串
        halt($msg);
    }
}

/**
 +----------------------------------------------------------
 *  区间调试开始
 +----------------------------------------------------------
 * @param string $label  标记名称
 +----------------------------------------------------------
 * @return void
 +----------------------------------------------------------
 */
function debug_start($label='')
{
    $GLOBALS[$label]['_beginTime'] = array_sum(split(' ', microtime()));
    if ( MEMORY_LIMIT_ON )	$GLOBALS[$label]['memoryUseStartTime'] = number_format((memory_get_usage() / 1024));
}

/**
 +----------------------------------------------------------
 *  区间调试结束，显示指定标记到当前位置的调试
 +----------------------------------------------------------
 * @param string $label  标记名称
 +----------------------------------------------------------
 * @return void
 +----------------------------------------------------------
 */
function debug_end($label='')
{
    $GLOBALS[$label]['_endTime'] = array_sum(split(' ', microtime()));
    echo '<div style="text-align:center;width:100%">Process: '.$label.' Times '.number_format($GLOBALS[$label]['_endTime']-$GLOBALS[$label]['_beginTime'],6).'s </div>';
    if ( MEMORY_LIMIT_ON )	{
        $GLOBALS[$label]['memoryUseEndTime'] = number_format((memory_get_usage() / 1024));
        echo '<div style="text-align:center;width:100%">Process: '.$label.' Memorys '.number_format($GLOBALS[$label]['memoryUseEndTime']-$GLOBALS[$label]['memoryUseStartTime']).' k</div>';
    }
}

/**
 +----------------------------------------------------------
 * 系统调试输出 Log::Write 的一个调用方法
 +----------------------------------------------------------
 * @param string $msg 调试信息
 +----------------------------------------------------------
 * @return void
 +----------------------------------------------------------
 */
function system_out($msg)
{
    if(defined('WEB_LOG_RECORD') && !empty($msg))
        Log::Write($msg,WEB_LOG_DEBUG);
}

/**
 +----------------------------------------------------------
 * 变量输出
 +----------------------------------------------------------
 * @param string $var 变量名
 * @param string $label 显示标签
 * @param string $echo 是否显示
 +----------------------------------------------------------
 * @return string
 +----------------------------------------------------------
 */
function dump($var, $label=null, $echo=true)
{
    $label = ($label===null) ? '' : rtrim($label) . ' ';
    ob_start();
    var_dump($var);
    $output = ob_get_clean();
    if(!extension_loaded('xdebug')) {
        $output = preg_replace("/\]\=\>\n(\s+)/m", "] => ", $output);
        $output = '<pre>'
                . $label
                . htmlentities($output, ENT_QUOTES,OUTPUT_CHARSET)
                . '</pre>';    	
    }
    if ($echo) {
        echo($output);
    }
    return $output;
}


/**
 +----------------------------------------------------------
 * 自动转换字符集 支持数组转换
 * 需要 iconv 或者 mb_string 模块支持
 * 如果 输出字符集和模板字符集相同则不进行转换
 +----------------------------------------------------------
 * @param string $fContents 需要转换的字符串
 +----------------------------------------------------------
 * @return string
 +----------------------------------------------------------
 */
function auto_charset($fContents,$from=TEMPLATE_CHARSET,$to=OUTPUT_CHARSET){
    if( strtoupper($from) === strtoupper($to) || empty($fContents) || (is_scalar($fContents) && !is_string($fContents)) ){
        //如果编码相同或者非字符串标量则不转换
        return $fContents;
    }
    $from   =  strtoupper($from)=='UTF8'? 'utf-8':$from;
    $to       =  strtoupper($to)=='UTF8'? 'utf-8':$to;
    if(is_string($fContents) ) {
        if(function_exists('iconv')){
            Return iconv($from,$to,$fContents);
        }
        elseif(function_exists('mb_convert_encoding')){
            return mb_convert_encoding ($fContents, $to, $from);
        }else{
            halt(_NO_AUTO_CHARSET_);
            return $fContents;
        }
    }
    elseif(is_array($fContents)){
        foreach ( $fContents AS $key => $val ) {
            $fContents[$key] = auto_charset($val,$from,$to);
        }
        return $fContents;
    }
    elseif(is_instance_of($fContents,'Vo')) {
        foreach($fContents as $key=>$val) {
            $fContents->$key = auto_charset($val,$from,$to);
        }
        return $fContents;
    }
    elseif(is_instance_of($fContents,'VoList')) {
        foreach($fContents->getIterator() as $key=>$vo) {
            $fContents->set($key,auto_charset($vo,$from,$to));
        }
        return $fContents;
    }
    else{
        //halt('系统不支持对'.gettype($fContents).'类型的编码转换！');
        return $fContents;
    }
}

/**
 +----------------------------------------------------------
 * 取得对象实例 支持调用类的静态方法
 +----------------------------------------------------------
 * @param string $className 对象类名
 * @param string $method 类的静态方法名
 +----------------------------------------------------------
 * @return object
 +----------------------------------------------------------
 */
function get_instance_of($className,$method='',$args=array()) 
{
    static $_instance = array();
    if (!isset($_instance[$className])) {
        if(class_exists($className)){
            $o = & new $className();
            if(method_exists($o,$method)){
                if(!empty($args)) {
                	$_instance[$className] = call_user_func_array(array(&$o, $method), $args);;
                }else {
                	$_instance[$className] = $o->$method();
                }
            }
            else 
                $_instance[$className] = $o;
        }
        else 
            halt(_CLASS_NOT_EXIST_);
    }
    return $_instance[$className];
}

/**
 +----------------------------------------------------------
 * 系统自动加载FCS基类库和当前项目的Dao和Vo对象
 * 需要PHP5支持
 +----------------------------------------------------------
 * @param string $classname 对象类名
 +----------------------------------------------------------
 * @return void
 +----------------------------------------------------------
 */
function __autoload($classname)
{
    $autoLoad = array('FCS.Core','FCS.Util','FCS.Exception','@.Dao','@.Vo');
    foreach($autoLoad as $val){
        if( import($val.'.'.$classname) )    return;
    }
    //halt("不能自动载入".$classname." 类库。");
}

/**
 +----------------------------------------------------------
 * 反序列化对象时自动回调方法 
 +----------------------------------------------------------
 * @param string $classname 对象类名
 +----------------------------------------------------------
 * @return void
 +----------------------------------------------------------
 */
function unserialize_callback($classname) 
{
    $autoLoad = array('Vo','Util');
    foreach($autoLoad as $key=>$val) {
        if(import(APP_NAME.'.'.$val.'.'.$classname) ) 	return ;
    }
    halt(_UNSERIALIZE_CLASS_NOT_EXIST_.$classname);
}

/**
 +----------------------------------------------------------
 * 优化的include_once
 +----------------------------------------------------------
 * @param string $filename 文件名
 +----------------------------------------------------------
 * @return boolen
 +----------------------------------------------------------
 */
function include_cache($filename)
{
    static $_importFiles = array();
    if(file_exists($filename)){
        if (!isset($_importFiles[strtolower(basename($filename))])) {
            include($filename);
            $_importFiles[strtolower(basename($filename))] = true;
            //$GLOBALS['LoadFile']++;//统计加载文件数
            return true;
        }
        return false;
    }
    return false;
}

/**
 +----------------------------------------------------------
 * 优化的require_once
 +----------------------------------------------------------
 * @param string $filename 文件名
 +----------------------------------------------------------
 * @return boolen
 +----------------------------------------------------------
 */
function require_cache($filename)
{
    static $_importFiles = array();
    if(file_exists($filename)){
        if (!isset($_importFiles[strtolower(basename($filename))])) {
            require($filename);
            $_importFiles[strtolower(basename($filename))] = true;
            //$GLOBALS['LoadFile']++;//统计加载文件数
            return true;
        }
        return false;
    }
    return false;
}

/**
 +----------------------------------------------------------
 * 获取include的内容 
 +----------------------------------------------------------
 * @param string $filename 文件名
 * @param string $decode 解密方法
 +----------------------------------------------------------
 * @return false|string
 +----------------------------------------------------------
 */
function get_include_contents($filename,$decode='') 
{ 
    if (is_file($filename)) { 
        ob_start(); 
        include $filename; 
        $contents = ob_get_clean(); 
        if(!empty($decode)) {
        	$contents = $decode($contents); 
        }
        return $contents; 
    } 
    return false; 
} 

/**
 +----------------------------------------------------------
 * 导入所需的类库 支持目录和* 同java的Import
 * 本函数有缓存功能 
 +----------------------------------------------------------
 * @param string $class 类库命名空间字符串
 * @param string $baseUrl 起始路径
 * @param string $appName 项目名
 * @param string $ext 导入的文件扩展名
 * @param string $subdir 是否导入子目录 默认false
 +----------------------------------------------------------
 * @return boolen
 +----------------------------------------------------------
 */
function import($class,$baseUrl = '',$appName=APP_NAME,$ext='.class.php',$subdir=false)
{
      //echo($class.$baseUrl.'<br>');
      static $_importClass = array();
      if(isset($_importClass[$class.$baseUrl]))
            return true;
      else 
            $_importClass[$class.$baseUrl] = true;
      //if (preg_match('/[^a-z0-9\-_.*]/i', $class)) throw_exception('Import非法的类名或者目录！');
      if( 0 === strpos($class,'@')) 	$class =  str_replace('@',$appName,$class);
      if(empty($baseUrl)) {
            // 默认方式调用应用类库
      	    $baseUrl   =  dirname(LIB_PATH);
      }else {
            //相对路径调用
      	    $isPath =  true;
      }
      $class_strut = explode(".",$class);
      if('*' == $class_strut[0] || isset($isPath) ) {
      	//多级目录加载支持
        //用于子目录递归调用
      }
      elseif($appName == $class_strut[0]) {
          //加载项目应用类库
          $class =  str_replace($appName.'.','Lib.',$class);
      }else {
          //加载FCS基类库或者公共类库
          $baseUrl =  FCS_PATH.'/Lib/';
      }
      if(substr($baseUrl, -1) != "/")    $baseUrl .= "/";
      $classfile = $baseUrl.str_replace('.', '/', $class).$ext;
      //echo($classfile.'<br>');
      if(array_pop($class_strut) == "*"){
          //包含 * 符号导入该目录下面所有的类库 
          //如果 subdir为true 则包含子目录
           $tmp_base_class = dirname($classfile);
           // 使用glob方式遍历目录 需要PHP 4.3.0以上版本
           $dir = glob ( $tmp_base_class . '/*'.$ext  );
           if($dir) {
               foreach($dir as $key=>$val) {
                    if( is_dir($val)){    
                        if($subdir) import('*',$val.'/',$appName,$ext,$subdir);
                    }else{    
                        //导入类库文件 后缀默认为 class.php
                        require_cache($val);
                    }
               }           	
           }
           /*
           $dir = dir($tmp_base_class);
           while (false !== ($entry = $dir->read())) {
                //如果是特殊目录继续
                if($entry == "." || $entry == "..")   continue;
                //如果是目录 ，并且定义了导入子目录，则递归调用import
                if( is_dir($tmp_base_class.'/'.$entry)){    
                    if ($subdir)  import('*',$tmp_base_class.'/'.$entry.'/',$appName,$ext,$subdir);
                }else{    
                    //导入类库文件 后缀默认为 class.php
                    if(strpos($entry, $ext)){
                        require_cache($tmp_base_class.'/'.$entry);
                    }
                }
           }
           $dir->close(); */
           return true;
      }else{
        //导入目录下的指定类库文件
        return require_cache($classfile);
      }
   
} 

/**
 +----------------------------------------------------------
 * import方法的别名 
 +----------------------------------------------------------
 * @param string $package 包名
 * @param string $baseUrl 起始路径
 * @param string $ext 导入的文件扩展名
 * @param string $subdir 是否导入子目录 默认false
 +----------------------------------------------------------
 * @return boolen
 +----------------------------------------------------------
 */
function using($class,$baseUrl = LIB_PATH,$ext='.class.php',$subdir=false)
{
    return import($class,$baseUrl,$ext,$subdir);
}

/**
 +----------------------------------------------------------
 * 根据PHP各种类型变量生成唯一标识号 
 +----------------------------------------------------------
 * @param mixed $mix 变量
 +----------------------------------------------------------
 * @return string
 +----------------------------------------------------------
 */
function to_guid_string($mix)
{
    if(is_resource($mix)){
        $mix = get_resource_type($mix).strval($mix);
    }else{
        $mix = serialize($mix);
    }
    return md5($mix);
}

/**
 +----------------------------------------------------------
 * 获得迭代因子  使用foreach遍历对象
 +----------------------------------------------------------
 * @param mixed $values 对象元素
 +----------------------------------------------------------
 * @return string
 +----------------------------------------------------------
 */
function getIterator($values) 
{
    if(version_compare(PHP_VERSION, '5.0.0', '<')){
        //PHP4下面的ArrayObject模拟了Iterator接口
        return new ArrayObject($values);
    }else {
        //ListIterator在PHP5中实现了Iterator接口
        return new ListIterator($values);
    }
}

/**
 +----------------------------------------------------------
 * 判断是否为对象实例
 +----------------------------------------------------------
 * @param mixed $object 实例对象
 * @param mixed $className 对象名
 +----------------------------------------------------------
 * @return boolean
 +----------------------------------------------------------
 */
function is_instance_of($object, $className)
{
   if(version_compare(PHP_VERSION, '5.0.0', '<')){
        return is_a($object, $className);
   }
   else{
       include ('_instanceof.php');
       return $is;
   }
}

/**
 +----------------------------------------------------------
 * 创建一个GUID 可通用于window和unix
 +----------------------------------------------------------
 * @return string
 +----------------------------------------------------------
 */
function create_guid() 
{
	$charid = strtoupper(md5(uniqid(mt_rand(), true)));
	$hyphen = chr(45);// "-"
	$uuid = chr(123)// "{"
		   .substr($charid, 0, 8).$hyphen
		   .substr($charid, 8, 4).$hyphen
		   .substr($charid,12, 4).$hyphen
		   .substr($charid,16, 4).$hyphen
		   .substr($charid,20,12)
		   .chr(125);// "}"
	return $uuid;
}

/**
 +----------------------------------------------------------
 * 创建Dao类，自动导入Dao类库
 +----------------------------------------------------------
 * @return Dao
 +----------------------------------------------------------
 */
function D($daoClassName) 
{
	import("@.Dao.".$daoClassName);
    $dao = new $daoClassName();
    return $dao;
}

/**
 +----------------------------------------------------------
 * 判断目录是否为空
 +----------------------------------------------------------
 * @return void
 +----------------------------------------------------------
 */
function empty_dir($directory)
{
    $handle = opendir($directory);
    while (($file = readdir($handle)) !== false)
    {
        if ($file != "." && $file != "..")
        {
            closedir($handle);
            return false;
        }
    }
    closedir($handle);
    return true;
}

/**
 +----------------------------------------------------------
 * 读取插件
 +----------------------------------------------------------
 * @param string $path 插件目录
 * @param string $app 所属项目名
 +----------------------------------------------------------
 * @return Array
 +----------------------------------------------------------
 */
function get_plugins($path=PLUGIN_PATH,$app=APP_NAME,$ext='.php') 
{
	static $plugins = array ();
    if(isset($plugins[$app])) {
    	return $plugins[$app];
    }
    // 如果插件目录为空 返回空数组
    if(empty_dir($path)) {
        return array();
    }
    if(PLUGIN_CACHE_ON) {
         // 开启插件缓存
         $cache = new Cache();
         $cache = $cache->connect('File');
        //$cache = Cache::getInstance('File');
        $result = $cache->get($app);
    }
    if(empty($result) ){
        // 缓存无效 重新读取插件文件
        /*
        $dir = glob ( $path . '/*' );
        if($dir) {
           foreach($dir as $val) {
                if(is_dir($val)){    
                    $subdir = glob($val.'/*'.$ext);
                    if($subdir) {
                        foreach($subdir as $file) 
                            $plugin_files[] = $file;
                    }
                }else{    
                    if (strrchr($val, '.') == $ext) 
                        $plugin_files[] = $val;
                }
           } 
           */

        $dir = dir($path);
        if($dir) {
            $plugin_files = array();
            while (false !== ($file = $dir->read())) {
                if($file == "." || $file == "..")   continue;
                if(is_dir($path.'/'.$file)){    
                        $subdir = @ dir($path.'/'.$file);
                        if ($subdir) {
                            while (($subfile = $subdir->read()) !== false) {
                                if($subfile == "." || $subfile == "..")   continue;
                                if (preg_match('/\.php$/', $subfile))
                                    $plugin_files[] = "$file/$subfile";
                            }
                            $subdir->close();
                        }            
                }else{    
                    $plugin_files[] = $file;
                }
            }    
            $dir->close(); 

            //对插件文件排序
            if(count($plugin_files)>1) {
                sort($plugin_files);
            }
            $plugins[$app] = array();
            foreach ($plugin_files as $plugin_file) {
                if ( !is_readable("$path/$plugin_file"))		continue;
                //取得插件文件的信息
                $plugin_data = get_plugin_info("$path/$plugin_file");
                if (empty ($plugin_data['name'])) {
                    continue;
                }
                $plugins[$app][] = $plugin_data;
            }
        }
        if(PLUGIN_CACHE_ON) {
             // 缓存插件数据
        	 $cache->set($app,$plugins[$app]);
        }
       return $plugins[$app];    	
    }else {
    	return $result;
    }

}

/**
 +----------------------------------------------------------
 * 获取插件信息
 +----------------------------------------------------------
 * @param string $plugin_file 插件文件名
 +----------------------------------------------------------
 * @return Array
 +----------------------------------------------------------
 */
function get_plugin_info($plugin_file) {

	$plugin_data = file_get_contents($plugin_file);
	preg_match("/Plugin Name:(.*)/i", $plugin_data, $plugin_name);
    if(empty($plugin_name)) {
    	return false;
    }
	preg_match("/Plugin URI:(.*)/i", $plugin_data, $plugin_uri);
	preg_match("/Description:(.*)/i", $plugin_data, $description);
	preg_match("/Author:(.*)/i", $plugin_data, $author_name);
	preg_match("/Author URI:(.*)/i", $plugin_data, $author_uri);
	if (preg_match("/Version:(.*)/i", $plugin_data, $version))
		$version = trim($version[1]);
	else
		$version = '';
    if(!empty($author_name)) {
        if(!empty($author_uri)) {
            $author_name = '<a href="'.trim($author_uri[1]).'" target="_blank">'.$author_name[1].'</a>';
        }else {
            $author_name = $author_name[1];
        }    	
    }else {
    	$author_name = '';
    }
	return array ('file'=>$plugin_file,'name' => trim($plugin_name[1]), 'uri' => trim($plugin_uri[1]), 'description' => trim($description[1]), 'author' => trim($author_name), 'version' => $version);
}

/**
 +----------------------------------------------------------
 * 动态添加模块
 +----------------------------------------------------------
 * @param string $module 模块名
 * @param string $class 类名
 +----------------------------------------------------------
 * @return Boolean
 +----------------------------------------------------------
 */
function add_module($module,$class) 
{
	static $_module = array();
    $_module[APP_NAME.'_'.$module] = $class;
    Session::set('_modules',$_module);
    return true;
}

/**
 +----------------------------------------------------------
 * 移除模块 仅限动态加载的模块
 +----------------------------------------------------------
 * @param string $module 模块名
 +----------------------------------------------------------
 * @return boolean
 +----------------------------------------------------------
 */
function remove_module($module) 
{
    if(Session::is_set('_modules')) {
    	$_module = Session::get('_modules');
        if(isset($_module[APP_NAME.'_'.$module])) {
            unset($_module[APP_NAME.'_'.$module]);
            Session::set('_modules',$_module);        	
        }
        return true;
    }
}

/**
 +----------------------------------------------------------
 * 动态添加操作
 +----------------------------------------------------------
 * @param mixed $action 操作名称
 * @param string $function 操作方法名
 +----------------------------------------------------------
 * @return boolean
 +----------------------------------------------------------
 */
function add_action($action,$function) 
{
	static $_action = array();
    if(is_array($action)) {
    	$module   = $action[0];
        $action     =  $action[1];
    }else {
    	$module   = 'public';
    }
    $_action[APP_NAME.'_'.$module][$action] = $function;
    Session::set('_actions',$_action);
    return true;
}

/**
 +----------------------------------------------------------
 * 移除动态操作
 +----------------------------------------------------------
 * @param mixed $action 操作名称
 +----------------------------------------------------------
 * @return boolean
 +----------------------------------------------------------
 */
function remove_action($action) 
{
    if(Session::is_set('_actions')) {
        if(is_array($action)) {
            $module   = $action[0];
            $action     =  $action[1];
        }else {
            $module   = 'public';
        }
    	$_action = Session::get('_actions');
        if(isset($_action[APP_NAME.'_'.$module][$action])) {
            unset($_action[APP_NAME.'_'.$module][$action]);
            Session::set('_actions',$_action);        	
        }
        return true;
    }
}

/**
 +----------------------------------------------------------
 * 动态添加模版编译引擎
 +----------------------------------------------------------
 * @param string $tag 模版引擎定义名称
 * @param string $compiler 编译器名称
 +----------------------------------------------------------
 * @return boolean
 +----------------------------------------------------------
 */
function add_compiler($tag,$compiler) 
{
	$GLOBALS['template_compiler'][strtoupper($tag)] = $compiler ;
    return ;
}

/**
 +----------------------------------------------------------
 * 使用模版编译引擎
 +----------------------------------------------------------
 * @param string $tag 模版引擎定义名称
 +----------------------------------------------------------
 * @return boolean
 +----------------------------------------------------------
 */
function use_compiler($tag) 
{
	$args = array_slice(func_get_args(), 1);
    call_user_func_array($GLOBALS['template_compiler'][strtoupper($tag)],$args);    
    return ;
}
/**
 +----------------------------------------------------------
 * 动态添加过滤器
 +----------------------------------------------------------
 * @param string $tag 过滤器标签
 * @param string $function 过滤方法名
 * @param integer $priority 执行优先级
 * @param integer $args 参数
 +----------------------------------------------------------
 * @return boolean
 +----------------------------------------------------------
 */
function add_filter($tag,$function,$priority = 10,$args = 1) 
{
    static $_filter = array();
	if ( isset($_filter[APP_NAME.'_'.$tag]["$priority"]) ) {
		foreach($_filter[APP_NAME.'_'.$tag]["$priority"] as $filter) {
			if ( $filter['function'] == $function ) {
				return true;
			}
		}
	}
    $_filter[APP_NAME.'_'.$tag]["$priority"][] = array('function'=> $function,'args'=> $args);
    Session::set('_filters',$_filter);
    return true;
}

/**
 +----------------------------------------------------------
 * 删除动态添加的过滤器
 +----------------------------------------------------------
 * @param string $tag 过滤器标签
 * @param string $function 过滤方法名
 * @param integer $priority 执行优先级
 +----------------------------------------------------------
 * @return boolean
 +----------------------------------------------------------
 */
function remove_filter($tag, $function_to_remove, $priority = 10) {
	$_filter  = Session::get('_filters');
	if ( isset($_filter[APP_NAME.'_'.$tag]["$priority"]) ) {
		$new_function_list = array();
		foreach($_filter[APP_NAME.'_'.$tag]["$priority"] as $filter) {
			if ( $filter['function'] != $function_to_remove ) {
				$new_function_list[] = $filter;
			}
		}
		$_filter[APP_NAME.'_'.$tag]["$priority"] = $new_function_list;
	}
    Session::set('_filters',$_filter);
	return true;
}

/**
 +----------------------------------------------------------
 * 执行过滤器
 +----------------------------------------------------------
 * @param string $tag 过滤器标签
 * @param string $string 参数
 +----------------------------------------------------------
 * @return boolean
 +----------------------------------------------------------
 */
function apply_filter($tag,$string='') 
{
    $_filter  = Session::get('_filters');
	if ( !isset($_filter[APP_NAME.'_'.$tag]) ) {
		return $string;
	}
    ksort($_filter[APP_NAME.'_'.$tag]);
    $args = array_slice(func_get_args(), 2);
	foreach ($_filter[APP_NAME.'_'.$tag] as $priority => $functions) {
		if ( !is_null($functions) ) {
			foreach($functions as $function) {
                if(is_callable($function['function'])) {
                    $args = array_merge(array($string), $args);
                    $string = call_user_func_array($function['function'],$args);                 	
                }
			}
		}
	}
	return $string;	
}

    /**
 +----------------------------------------------------------
 * 字符串截取，支持中文和其他编码
 * 
 * 
 +----------------------------------------------------------
 * @param string $fStr 需要转换的字符串
 * @param string $fStart 开始位置
 * @param string $fLen 截取长度
 * @param string $fCode 编码格式
 * @param string $show 截断显示字符
 +----------------------------------------------------------
 * @return string
 +----------------------------------------------------------
 */
function msubstr (&$fStr, $fStart, $fLen, $fCode = "utf-8",$show='...') {
    if(function_exists('mb_substr')) {
        if(mb_strlen($fStr,$fCode)>$fLen) {
            return mb_substr ($fStr,$fStart,$fLen,$fCode).$show;
        }
        return mb_substr ($fStr,$fStart,$fLen,$fCode);
    }else if(function_exists('iconv_substr')) {
        if(iconv_strlen($fStr,$fCode)>$fLen) {
            return iconv_substr ($fStr,$fStart,$fLen,$fCode).$show;
        }
        return iconv_substr ($fStr,$fStart,$fLen,$fCode);
    }

    $fCode = strtolower($fCode);
    switch ($fCode) {
        case "utf-8" : 
            preg_match_all("/[\x01-\x7f]|[\xc2-\xdf][\x80-\xbf]|\xe0[\xa0-\xbf][\x80-\xbf]|[\xe1-\xef][\x80-\xbf][\x80-\xbf]|\xf0[\x90-\xbf][\x80-\xbf][\x80-\xbf]|[\xf1-\xf7][\x80-\xbf][\x80-\xbf][\x80-\xbf]/", $fStr, $ar);  
            if(func_num_args() >= 3) {  
                if (count($ar[0])>$fLen) {
                    return join("",array_slice($ar[0],$fStart,$fLen)).$show; 
                }
                return join("",array_slice($ar[0],$fStart,$fLen)); 
            } else {  
                return join("",array_slice($ar[0],$fStart)); 
            } 
            break;
        default:
            $fStart = $fStart*2;
            $fLen   = $fLen*2;
            $strlen = strlen($fStr);
            for ( $i = 0; $i < $strlen; $i++ ) {
                if ( $i >= $fStart && $i < ( $fStart+$fLen ) ) {
                    if ( ord(substr($fStr, $i, 1)) > 129 ) $tmpstr .= substr($fStr, $i, 2);
                    else $tmpstr .= substr($fStr, $i, 1);
                }
                if ( ord(substr($fStr, $i, 1)) > 129 ) $i++;
            }
            if ( strlen($tmpstr) < $strlen ) $tmpstr .= $show;
            Return $tmpstr;
    }
}

/**
 +----------------------------------------------------------
 * 产生随机字串，可用来自动生成密码 默认长度6位 字母和数字混合
 +----------------------------------------------------------
 * @param string $len 长度 
 * @param string $type 字串类型 
 * 0 字母 1 数字 其它 混合
 * @param string $addChars 额外字符 
 +----------------------------------------------------------
 * @return string
 +----------------------------------------------------------
 */
function rand_string($len=6,$type='',$addChars='') { 
    $str ='';
    switch($type) { 
        case 0:
            $chars='ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz'.$addChars; 
            break;
        case 1:
            $chars='0123456789'; 
            break;
        case 2:
            $chars='ABCDEFGHIJKLMNOPQRSTUVWXYZ'.$addChars; 
            break;
        case 3:
            $chars='abcdefghijklmnopqrstuvwxyz'.$addChars; 
            break;
        default :
            // 默认去掉了容易混淆的字符oOLl和数字01，要添加请使用addChars参数
            $chars='ABCDEFGHIJKMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz23456789'.$addChars; 
            break;
    }
    if($len>10 ) {//位数过长重复字符串一定次数
        $chars= $type==1? str_repeat($chars,$len) : str_repeat($chars,5); 
    }
    $chars   =   str_shuffle($chars);
    $str     =   substr($chars,0,$len);

    return $str;
}

/**
 +----------------------------------------------------------
 * 生成一定数量的随机数，并且不重复
 +----------------------------------------------------------
 * @param integer $number 数量
 * @param string $len 长度
 * @param string $type 字串类型 
 * 0 字母 1 数字 其它 混合
 +----------------------------------------------------------
 * @return string
 +----------------------------------------------------------
 */
function build_count_rand ($number,$length=4,$mode=1) { 
        if($mode==1 && $length<strlen($number) ) {
            //不足以生成一定数量的不重复数字
    		return false;        	
        }
        $rand   =  array();
        for($i=0; $i<$number; $i++) {
            $rand[] =   rand_string($length,$mode);
        }
        $unqiue = array_unique($rand);
        if(count($unqiue)==count($rand)) {
            return $rand;
        }
        $count   = count($rand)-count($unqiue);
        for($i=0; $i<$count*3; $i++) {
            $rand[] =   rand_string($length,$mode);
        }
        $rand = array_slice(array_unique ($rand),0,$number);    	
        return $rand;
}

/**
 +----------------------------------------------------------
 *  带格式生成随机字符 支持批量生成 
 *  但可能存在重复
 +----------------------------------------------------------
 * @param string $format 字符格式
 *     # 表示数字 * 表示字母和数字 $ 表示字母
 * @param integer $number 生成数量
 +----------------------------------------------------------
 * @return string | array
 +----------------------------------------------------------
 */
function build_format_rand($format,$number=1) 
{
    $str  =  array();
    $length =  strlen($format);
    for($j=0; $j<$number; $j++) {
        $strtemp   = '';
        for($i=0; $i<$length; $i++) {
            $char = substr($format,$i,1);
            switch($char){
                case "*"://字母和数字混合
                    $strtemp   .= rand_string(1);
                    break;
                case "#"://数字
                    $strtemp  .= rand_string(1,1);
                    break;
                case "$"://大写字母
                    $strtemp .=  rand_string(1,2);
                    break;
                default://其他格式均不转换
                    $strtemp .=   $char;
                    break;
           }
        } 
        $str[] = $strtemp;
    }
    
    return $number==1? $strtemp : $str ;
}

/**
 +----------------------------------------------------------
 * 获取一定范围内的随机数字 位数不足补零
 +----------------------------------------------------------
 * @param integer $min 最小值
 * @param integer $max 最大值
 +----------------------------------------------------------
 * @return string
 +----------------------------------------------------------
 */
function rand_number ($min, $max) {
	Return sprintf("%0".strlen($max)."d", mt_rand($min,$max));
}

/**
 +----------------------------------------------------------
 * 获取登录验证码 默认为4位数字
 +----------------------------------------------------------
 * @param string $fmode 文件名
 +----------------------------------------------------------
 * @return string
 +----------------------------------------------------------
 */
function build_verify ($length=4,$mode=1) { 
    return rand_string($length,$mode);
}

function toDate($time,$format='Y年m月d日 H:i:s') 
{
	if( empty($time)) {
		return '';
	}
	return date(auto_charset($format),$time);
}
?>