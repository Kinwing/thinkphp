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
 * 项目入口文件
 +------------------------------------------------------------------------------
 * @package    Core
 * @link       http://www.fcs.org.cn
 * @copyright  Copyright (c) 2005-2006 liu21st.com.  All rights reserved. 
 * @author     liu21st <liu21st@gmail.com>
 * @version    $Id$
 +------------------------------------------------------------------------------
 */
$GLOBALS['_beginTime'] = array_sum(split(' ', microtime()));
// 定义FCS框架路径和网站路径 使用绝对路径
define('FCS_PATH', dirname(__FILE__).'/FCS');
define('WEB_ROOT', dirname(__FILE__));
//定义项目名称，如果不定义，默认为入口文件名称
define('APP_NAME', 'Admin');
// 加载FCS框架公共入口文件 
require(FCS_PATH."/FCS.php");
//实例化一个网站应用实例
$App = App::getInstance(); 
//应用程序初始化
$App->init();
//装载过滤器
$App->loadFilters('VarFilter,AccessDecision');
$App->exec();
//启动应用程序
if(SHOW_RUN_TIME) {
echo '<div style="text-align:center;width:100%">Process: '.number_format((array_sum(split(' ', microtime())) - $GLOBALS['_beginTime']), 6).'s</div>';
}
?>