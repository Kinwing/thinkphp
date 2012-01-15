<?php

if (!defined('THINK_PATH'))	exit();

$config = require("../config.php");
$array = array(
    'URL_ROUTER_ON' => true,
	'DEFAULT_MODULE' =>	'Blog',
    'APP_AUTOLOAD_PATH'=>'@.TagLib,@.ORG',
    'TOKEN_ON'  => false,
    'URL_ROUTE_RULES' => array(
        array('cate','Blog/category','id'),
        array('/^Blog\/(\d+)$/is','Blog/show','id'),
        array('/^Blog\/(\d+)\/(\d+)/is','Blog/archive','year,month'),
    ),
    'SHOW_PAGE_TRACE'=>1,
);
return array_merge($config,$array);
?>