<?php
// +----------------------------------------------------------------------
// | ThinkPHP
// +----------------------------------------------------------------------
// | Copyright (c) 2009 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------
// $Id$

// 命令行模式核心定义文件列表
return array(
    THINK_PATH.'/Lib/Think/Exception/ThinkException.class.php',
    THINK_PATH.'/Lib/Think/Core/Log.class.php',
    THINK_PATH.'/Mode/Cli/App.class.php',
    THINK_PATH.'/Mode/Cli/Action.class.php',
    THINK_PATH.'/Mode/Cli/Model.class.php',
    THINK_PATH.'/Common/alias.php',  // 加载别名
);
?>