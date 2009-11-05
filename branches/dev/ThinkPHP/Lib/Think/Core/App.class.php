<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2009 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------
// $Id$

/**
 +------------------------------------------------------------------------------
 * ThinkPHP 应用程序类 执行应用过程管理
 +------------------------------------------------------------------------------
 * @category   Think
 * @package  Think
 * @subpackage  Core
 * @author    liu21st <liu21st@gmail.com>
 * @version   $Id$
 +------------------------------------------------------------------------------
 */
class App
{//类定义开始

    /**
     +----------------------------------------------------------
     * 应用程序初始化
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @return void
     +----------------------------------------------------------
     */
    static public function init()
    {
        // 设定错误和异常处理
        set_error_handler(array('App','appError'));
        set_exception_handler(array('App','appException'));
        //[RUNTIME]
        // 检查项目是否编译过
        // 在部署模式下会自动在第一次执行的时候编译项目
        if(defined('RUNTIME_MODEL')){
            // 运行模式无需载入项目编译缓存
        }elseif(is_file(RUNTIME_PATH.'~app.php') && (!is_file(CONFIG_PATH.'config.php') || filemtime(RUNTIME_PATH.'~app.php')>filemtime(CONFIG_PATH.'config.php'))) {
            // 直接读取编译后的项目文件
            C(include RUNTIME_PATH.'~app.php');
        }else{
            // 预编译项目
            App::build();
        }
        //[/RUNTIME]

        // 项目开始标签
        if(C('APP_PLUGIN_ON'))   tag('app_begin');

        // 设置系统时区 PHP5支持
        if(function_exists('date_default_timezone_set'))
            date_default_timezone_set(C('DEFAULT_TIMEZONE'));

        // 允许注册AUTOLOAD方法
        if(C('APP_AUTOLOAD_REG') && function_exists('spl_autoload_register'))
                spl_autoload_register(array('Think', 'autoload'));

        if(C('SESSION_AUTO_START'))  session_start(); // Session初始化

        // 应用调度过滤器
        // 如果没有加载任何URL调度器
        // 默认只支持 QUERY_STRING 方式
        if(C('URL_DISPATCH_ON'))   Dispatcher::dispatch();

        if(!defined('PHP_FILE'))
            // PHP_FILE 由内置的Dispacher定义
            // 如果不使用该插件，需要重新定义
            define('PHP_FILE',_PHP_FILE_);

        // 取得模块和操作名称
        // 可以在Dispatcher中定义获取规则

        // 加载项目分组公共文件
        if(C('APP_GROUP_LIST')) {
            $Group_name = App::getGroup();
            if($Group_name != '') {

            if(!defined('GROUP_NAME')) define('GROUP_NAME', $Group_name);       // Group名称
            // 分组配置文件
            if(is_file(CONFIG_PATH.GROUP_NAME.'/config.php'))
                C(include CONFIG_PATH.GROUP_NAME.'/config.php');
            // 分组函数文件
            if(is_file(COMMON_PATH.GROUP_NAME.'/function.php'))
                include COMMON_PATH.GROUP_NAME.'/function.php';
            }
        }

        if(!defined('MODULE_NAME')) define('MODULE_NAME',   App::getModule());       // Module名称
        if(!defined('ACTION_NAME')) define('ACTION_NAME',   App::getAction());        // Action操作

        // 加载模块配置文件
        if(is_file(CONFIG_PATH.strtolower(MODULE_NAME).'_config.php'))
            C(include CONFIG_PATH.strtolower(MODULE_NAME).'_config.php');

        // 系统检查
        App::checkLanguage();     //语言检查
        App::checkTemplate();     //模板检查
        if(C('HTML_CACHE_ON')) // 开启静态缓存
            HtmlCache::readHTMLCache();

        // 项目初始化标签
        if(C('APP_PLUGIN_ON'))   tag('app_init');
        return ;
    }
    //[RUNTIME]
    /**
     +----------------------------------------------------------
     * 读取配置信息 编译项目
     +----------------------------------------------------------
     * @access private
     +----------------------------------------------------------
     * @return string
     +----------------------------------------------------------
     */
    static private function build()
    {
        // 加载惯例配置文件
        C(include THINK_PATH.'/Common/convention.php');
        // 加载项目配置文件
        if(is_file(CONFIG_PATH.'config.php'))
            C(include CONFIG_PATH.'config.php');

        $runtime = defined('RUNTIME_ALLINONE');
        $common   = '';
         //是否调试模式 ALL_IN_ONE模式下面调试模式无效
        $debug  =  C('APP_DEBUG') && !$runtime;
        // 加载项目公共文件
        if(is_file(COMMON_PATH.'common.php')) {
            include COMMON_PATH.'common.php';
            if(!$debug) // 编译文件
                $common   .= compile(COMMON_PATH.'common.php',$runtime);
        }
        // 加载项目编译文件列表
        if(is_file(CONFIG_PATH.'app.php')) {
            $list   =  include CONFIG_PATH.'app.php';
            foreach ($list as $file){
                // 加载并编译文件
                require $file;
                if(!$debug) $common   .= compile($file,$runtime);
            }
        }
        // 读取扩展配置文件
        $list = C('APP_CONFIG_LIST');
        foreach ($list as $val){
            if(is_file(CONFIG_PATH.$val.'.php'))
                C('_'.$val.'_',array_change_key_case(include CONFIG_PATH.$val.'.php'));
        }
        // 如果是调试模式加载调试模式配置文件
        if($debug) {
            // 加载系统默认的开发模式配置文件
            C(include THINK_PATH.'/Common/debug.php');
            if(is_file(CONFIG_PATH.'debug.php'))
                // 允许项目增加开发模式配置定义
                C(include CONFIG_PATH.'debug.php');
        }else{
            // 部署模式下面生成编译文件
            // 下次直接加载项目编译文件
            if(defined('RUNTIME_ALLINONE')) {
                // 获取用户自定义变量
                $defs = get_defined_constants(TRUE);
                $content  = array_define($defs['user']);
                $content .= substr(file_get_contents(RUNTIME_PATH.'~runtime.php'),5);
                $content .= $common."\nreturn ".var_export(C(),true).';';
                file_put_contents(RUNTIME_PATH.'~allinone.php',strip_whitespace('<?php '.$content));
            }else{
                $content  = "<?php ".$common."\nreturn ".var_export(C(),true).";\n?>";
                file_put_contents(RUNTIME_PATH.'~app.php',strip_whitespace($content));
            }
        }
        return ;
    }
    //[/RUNTIME]
    /**
     +----------------------------------------------------------
     * 获得实际的模块名称
     +----------------------------------------------------------
     * @access private
     +----------------------------------------------------------
     * @return string
     +----------------------------------------------------------
     */
    static private function getModule()
    {
        $var  =  C('VAR_MODULE');
        $module = !empty($_POST[$var]) ?
            $_POST[$var] :
            (!empty($_GET[$var])? $_GET[$var]:C('DEFAULT_MODULE'));
        if(C('URL_CASE_INSENSITIVE')) {
            // URL地址不区分大小写
            define('P_MODULE_NAME',strtolower($module));
            // 智能识别方式 index.php/user_type/index/ 识别到 UserTypeAction 模块
            $module = ucfirst(parse_name(P_MODULE_NAME,1));
        }
        unset($_POST[$var],$_GET[$var]);
        return $module;
    }

    /**
     +----------------------------------------------------------
     * 获得实际的操作名称
     +----------------------------------------------------------
     * @access private
     +----------------------------------------------------------
     * @return string
     +----------------------------------------------------------
     */
    static private function getAction()
    {
        $var  =  C('VAR_ACTION');
        $action   = !empty($_POST[$var]) ?
            $_POST[$var] :
            (!empty($_GET[$var])?$_GET[$var]:C('DEFAULT_ACTION'));
        unset($_POST[$var],$_GET[$var]);
        return $action;
    }

    /**
     +----------------------------------------------------------
     * 获得实际的分组名称
     +----------------------------------------------------------
     * @access private
     +----------------------------------------------------------
     * @return string
     +----------------------------------------------------------
     */
    static private function getGroup()
    {
        $var  =  C('VAR_GROUP');
        $group   = !empty($_POST[$var]) ?
            $_POST[$var] :
            (!empty($_GET[$var])?$_GET[$var]:C('DEFAULT_GROUP'));
        unset($_POST[$var],$_GET[$var]);
        return ucfirst(strtolower($group));
    }

    /**
     +----------------------------------------------------------
     * 语言检查
     * 检查浏览器支持语言，并自动加载语言包
     +----------------------------------------------------------
     * @access private
     +----------------------------------------------------------
     * @return void
     +----------------------------------------------------------
     */
    static private function checkLanguage()
    {
        $langSet = C('DEFAULT_LANG');
        // 不开启语言包功能，仅仅加载框架语言文件直接返回
        if (!C('LANG_SWITCH_ON')){
            L(include THINK_PATH.'/Lang/'.$langSet.'.php');
            return;
        }
        // 启用了语言包功能
        // 根据是否启用自动侦测设置获取语言选择
        if (C('LANG_AUTO_DETECT')){
            if(isset($_GET[C('VAR_LANGUAGE')])){// 检测浏览器支持语言
                $langSet = $_GET[C('VAR_LANGUAGE')];// url中设置了语言变量
                cookie('think_language',$langSet,3600);
            }elseif(cookie('think_language'))// 获取上次用户的选择
                $langSet = cookie('think_language');
            elseif(isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])){// 自动侦测浏览器语言
                preg_match('/^([a-z\-]+)/i', $_SERVER['HTTP_ACCEPT_LANGUAGE'], $matches);
                $langSet = $matches[1];
                cookie('think_language',$langSet,3600);
            }
        }
        // 定义当前语言
        define('LANG_SET',strtolower($langSet));
        // 加载框架语言包
        if(is_file(THINK_PATH.'/Lang/'.$langSet.'.php'))
            L(include THINK_PATH.'/Lang/'.$langSet.'.php');
        // 读取项目公共语言包
        if (is_file(LANG_PATH.$langSet.'/common.php'))
            L(include LANG_PATH.$langSet.'/common.php');
        $group = '';
        // 读取当前分组公共语言包
        if (defined('GROUP_NAME')){
            $group = GROUP_NAME.C('TMPL_FILE_DEPR');
            if (is_file(LANG_PATH.$langSet.'/'.$group.'lang.php'))
                L(include LANG_PATH.$langSet.'/'.$group.'lang.php');
        }
        // 读取当前模块语言包
        if (is_file(LANG_PATH.$langSet.'/'.$group.strtolower(MODULE_NAME).'.php'))
            L(include LANG_PATH.$langSet.'/'.$group.strtolower(MODULE_NAME).'.php');
    }

    /**
     +----------------------------------------------------------
     * 模板检查，如果不存在使用默认
     +----------------------------------------------------------
     * @access private
     +----------------------------------------------------------
     * @return void
     +----------------------------------------------------------
     */
    static private function checkTemplate()
    {
        if(C('TMPL_DETECT_THEME')) {// 自动侦测模板主题
            $t = C('VAR_TEMPLATE');
            if (isset($_GET[$t])){
                $templateSet = $_GET[$t];
                cookie('think_template',$templateSet,3600);
            }else{
                if(cookie('think_template')){
                    $templateSet = cookie('think_template');
                }else{
                    $templateSet =    C('DEFAULT_THEME');
                    cookie('think_template',$templateSet,3600);
                }
            }
            if(!is_dir(TMPL_PATH.$templateSet))
                //模版不存在的话，使用默认模版
                $templateSet =    C('DEFAULT_THEME');
        }else{
            $templateSet =    C('DEFAULT_THEME');
        }
        //模版名称
        define('TEMPLATE_NAME',$templateSet);
        // 当前模版路径
        define('TEMPLATE_PATH',TMPL_PATH.TEMPLATE_NAME);
        $tmplDir = TMPL_DIR.'/'.TEMPLATE_NAME.'/';

        //当前项目地址
        define('__APP__',PHP_FILE);
        //当前页面地址
        define('__SELF__',$_SERVER['PHP_SELF']);
        // 应用URL根目录
        if(C('APP_DOMAIN_DEPLOY')) {
            // 独立域名部署需要指定模板从根目录开始
            $appRoot   =  '/';
        }else{
            $appRoot   =  __ROOT__.'/'.APP_NAME.'/';
        }
        $depr = C('URL_PATHINFO_MODEL')==2?C('URL_PATHINFO_DEPR'):'/';
        $module = defined('P_MODULE_NAME')?P_MODULE_NAME:MODULE_NAME;
        if(defined('GROUP_NAME')) {
            $group   = C('URL_CASE_INSENSITIVE') ?strtolower(GROUP_NAME):GROUP_NAME;
            define('__URL__',PHP_FILE.'/'.((GROUP_NAME != C('DEFAULT_GROUP'))?$group.$depr:'').$module);
            C('TMPL_FILE_NAME',TEMPLATE_PATH.'/'.GROUP_NAME.'/'.MODULE_NAME.C('TMPL_FILE_DEPR').ACTION_NAME.C('TMPL_TEMPLATE_SUFFIX'));
            C('CACHE_PATH',CACHE_PATH.GROUP_NAME.'/');
        }else{
            define('__URL__',PHP_FILE.'/'.$module);
            C('TMPL_FILE_NAME',TEMPLATE_PATH.'/'.str_replace(C('APP_GROUP_DEPR'),'/',MODULE_NAME).'/'.ACTION_NAME.C('TMPL_TEMPLATE_SUFFIX'));
            C('CACHE_PATH',CACHE_PATH);
        }
        //当前操作地址
        define('__ACTION__',__URL__.C('URL_PATHINFO_DEPR').ACTION_NAME);
        define('__CURRENT__', __ROOT__.'/'.APP_NAME.'/'.$tmplDir.MODULE_NAME);
        //项目模板目录
        define('APP_TMPL_PATH', $appRoot.$tmplDir);
        //网站公共文件目录
        define('WEB_PUBLIC_PATH', __ROOT__.'/Public');
        //项目公共文件目录
        define('APP_PUBLIC_PATH', APP_TMPL_PATH.'Public');
        return ;
    }

    /**
     +----------------------------------------------------------
     * 执行应用程序
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @return void
     +----------------------------------------------------------
     * @throws ThinkExecption
     +----------------------------------------------------------
     */
    static public function exec()
    {
        // 是否开启标签扩展
        $tagOn   =  C('APP_PLUGIN_ON');
        // 项目运行标签
        if($tagOn)  tag('app_run');

        //创建Action控制器实例
        $group =  defined('GROUP_NAME') ? GROUP_NAME.C('APP_GROUP_DEPR') : '';
        $module  =  A($group.MODULE_NAME);
        if(!$module) {
            // 是否存在扩展模块
            $_module = C('_modules_.'.MODULE_NAME);
            if($_module) {
                // 'module'=>array('classImportPath'[,'className'])
                import($_module[0]);
                $class = isset($_module[1])?$_module[1]:MODULE_NAME.'Action';
                $module = new $class;
            }else{
                // 是否定义Empty模块
                $module = A("Empty");
            }
            if(!$module)
                // 模块不存在 抛出异常
                throw_exception(L('_MODULE_NOT_EXIST_').MODULE_NAME);
        }

        //获取当前操作名
        $action = ACTION_NAME;
        if(strpos($action,':')) {
            // 执行操作链 最多只能有一个输出
            $actionList	=	explode(':',$action);
            foreach ($actionList as $action){
                $module->$action();
            }
        }else{
            if (method_exists($module,'_before_'.$action)) {
                // 执行前置操作
                call_user_func(array(&$module,'_before_'.$action));
            }
            //执行当前操作
            call_user_func(array(&$module,$action));
            if (method_exists($module,'_after_'.$action)) {
                //  执行后缀操作
                call_user_func(array(&$module,'_after_'.$action));
            }
        }
        // 项目结束标签
        if($tagOn)  tag('app_end');
        return ;
    }

    /**
     +----------------------------------------------------------
     * 运行应用实例 入口文件使用的快捷方法
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @return void
     +----------------------------------------------------------
     */
    static public function run() {
        App::init();
        // 记录应用初始化时间
        if(C('SHOW_RUN_TIME'))  $GLOBALS['_initTime'] = microtime(TRUE);
        App::exec();
        // 保存日志记录
        if(C('LOG_RECORD')) Log::save();
        return ;
    }

    /**
     +----------------------------------------------------------
     * 自定义异常处理
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @param mixed $e 异常对象
     +----------------------------------------------------------
     */
    static public function appException($e)
    {
        halt($e->__toString());
    }

    /**
     +----------------------------------------------------------
     * 自定义错误处理
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @param int $errno 错误类型
     * @param string $errstr 错误信息
     * @param string $errfile 错误文件
     * @param int $errline 错误行数
     +----------------------------------------------------------
     * @return void
     +----------------------------------------------------------
     */
    static public function appError($errno, $errstr, $errfile, $errline)
    {
      switch ($errno) {
          case E_ERROR:
          case E_USER_ERROR:
            $errorStr = "[$errno] $errstr ".basename($errfile)." 第 $errline 行.";
            if(C('LOG_RECORD')) Log::write($errorStr,Log::ERR);
            halt($errorStr);
            break;
          case E_STRICT:
          case E_USER_WARNING:
          case E_USER_NOTICE:
          default:
            $errorStr = "[$errno] $errstr ".basename($errfile)." 第 $errline 行.";
            Log::record($errorStr,Log::NOTICE);
            break;
      }
    }

};//类定义结束
?>