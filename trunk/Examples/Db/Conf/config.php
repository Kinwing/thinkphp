<?php


------------------------------------------------------------------------------


------------------------------------------------------------------------------

if (!defined('THINK_PATH')) exit();
$config  =   require '../config.php';
$array   =  array(
		'DEBUG_MODE'=>TRUE,	 //		显示运行时间
        );
return array_merge($config,$array);


