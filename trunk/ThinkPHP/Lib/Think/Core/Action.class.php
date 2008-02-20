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
 * ThinkPHP Action控制器基类 抽象类
 +------------------------------------------------------------------------------
 * @category   Think
 * @package  Think
 * @subpackage  Core
 * @author   liu21st <liu21st@gmail.com>
 * @version  $Id$
 +------------------------------------------------------------------------------
 */
abstract class Action extends Base
{//类定义开始

	// Action控制器名称
	protected $name;

    // 模板实例对象
    protected $tpl;

	// 是否启用action缓存
	protected $useCache = false;

	// 需要缓存的action
	protected $_cacheAction = array();

    // 上次错误信息
    protected $error;

   /**
     +----------------------------------------------------------
     * 架构函数 取得模板对象实例
     +----------------------------------------------------------
     * @access public 
     +----------------------------------------------------------
     */
    public function __construct()
    {
        //实例化模板类
        $this->tpl = View::getInstance();    
		$this->name	=	$this->getActionName();
        //控制器初始化
        $this->_initialize();
    }

	/**
     +----------------------------------------------------------
     * 得到当前的Action对象名称
     +----------------------------------------------------------
     * @access public 
     +----------------------------------------------------------
     * @return string
     +----------------------------------------------------------
     */
	public function getActionName() {
		if(empty($this->name)) {
			$prefix		=	C('CONTR_CLASS_PREFIX');
			$suffix		=	C('CONTR_CLASS_SUFFIX');
			if($suffix) {
				$this->name	=	substr(substr(get_class($this),strlen($prefix)),0,-strlen($suffix));
			}else{
				$this->name	=	substr(get_class($this),strlen($prefix));
			}
		}
		return $this->name;
	}

    /**
     +----------------------------------------------------------
     * 控制器初始化操作
     +----------------------------------------------------------
     * @access public 
     +----------------------------------------------------------
     * @return void
     +----------------------------------------------------------
     * @throws ThinkExecption
     +----------------------------------------------------------
     */
    protected function _initialize() 
    {
		//判断是否有Action缓存
		if($this->useCache && in_array(ACTION_NAME,$this->_cacheAction,true)) {
			$guid	=	md5(__SELF__);
			$content	=	S($guid);
			if($content) {
				echo $content;
				exit;
			}
		}
        if(isset($_REQUEST[C('VAR_AJAX_SUBMIT')]) ) {
            // 判断Ajax方式提交
            $this->assign('ajax',true);
        }
        return ;
    }

    /**
     +----------------------------------------------------------
     * 设置Action缓存
     +----------------------------------------------------------
     * @access public 
     +----------------------------------------------------------
     * @return void
     +----------------------------------------------------------
     * @throws ThinkExecption
     +----------------------------------------------------------
     */
	public function setCache($cache) {
		$this->useCache	=	$cache;
	}

    /**
     +----------------------------------------------------------
     * 记录乐观锁
     +----------------------------------------------------------
     * @access public 
     +----------------------------------------------------------
     * @param mixed $data 数据对象
     +----------------------------------------------------------
     * @return string
     +----------------------------------------------------------
     * @throws ThinkExecption
     +----------------------------------------------------------
     */
	protected function cacheLockVersion($data) {
		$model	=	D($this->name);
		if($model->optimLock) {
			if(is_object($data))	$data	=	get_object_vars($data);
			if(isset($data[$model->optimLock]) && isset($data[$model->getPk()])) {
				$_SESSION[$model->getModelName().'_'.$data[$model->getPk()].'_lock_version']	=	$data[$model->optimLock];
			}
		}
	}

    /**
     +----------------------------------------------------------
     * 取得数据访问类的实例
     +----------------------------------------------------------
     * @access protected 
     +----------------------------------------------------------
     * @return mixed
     +----------------------------------------------------------
     * @throws ThinkExecption
     +----------------------------------------------------------
     */
    protected function getModelClass() 
    {
        $model        = D($this->name);
        return $model;
    }

    /**
     +----------------------------------------------------------
     * 取得操作成功后要返回的URL地址
     * 默认返回当前模块的默认操作 
     +----------------------------------------------------------
     * @access public 
     +----------------------------------------------------------
     * @return string
     +----------------------------------------------------------
     * @throws ThinkExecption
     +----------------------------------------------------------
     */
    protected function getReturnUrl() 
    {
		return url(C('DEFAULT_ACTION'));
    }

    /**
     +----------------------------------------------------------
     * 模板显示
     * 调用内置的模板引擎显示方法，
     +----------------------------------------------------------
     * @access public 
     +----------------------------------------------------------
     * @param string $templateFile 指定要调用的模板文件
     * 默认为空 由系统自动定位模板文件
     * @param string $charset 输出编码
     * @param string $contentType 输出类型
     * @param string $varPrefix 模板变量前缀
     +----------------------------------------------------------
     * @return void
     +----------------------------------------------------------
     */
    public function display($templateFile='',$charset='',$contentType='text/html',$varPrefix='')
    {
		if($this->useCache && in_array(ACTION_NAME,$this->_cacheAction,true)) {
			// 启用Action缓存
			$guid	=	md5(__SELF__);
			$content	=	$this->fetch($templateFile,$charset,$contentType,$varPrefix);
			S($guid,$content);
			echo $content;
		}else{
	        $this->tpl->display($templateFile,$charset,$contentType,$varPrefix);
		}
    }

    /**
     +----------------------------------------------------------
     *  获取输出页面内容
     * 调用内置的模板引擎fetch方法，
     +----------------------------------------------------------
     * @access public 
     +----------------------------------------------------------
     * @param string $templateFile 指定要调用的模板文件
     * 默认为空 由系统自动定位模板文件
     * @param string $charset 输出编码
     * @param string $contentType 输出类型
     * @param string $varPrefix 模板变量前缀
     +----------------------------------------------------------
     * @return void
     +----------------------------------------------------------
     */
    public function fetch($templateFile='',$charset='',$contentType='text/html',$varPrefix='')
    {
        return $this->tpl->fetch($templateFile,$charset,$contentType,$varPrefix,false);
    }

    /**
     +----------------------------------------------------------
     *  输出布局页面内容
     * 调用内置的模板引擎fetch方法，
     +----------------------------------------------------------
     * @access public 
     +----------------------------------------------------------
     * @param string $templateFile 指定要调用的布局模板文件
     * @param string $charset 输出编码
     * @param string $contentType 输出类型
     * @param string $varPrefix 模板变量前缀
     * @param boolean $display 是否输出
     +----------------------------------------------------------
     * @return void
     +----------------------------------------------------------
     */
    public function layout($templateFile,$charset='',$contentType='text/html',$varPrefix='',$display=true)
    {
        return $this->tpl->layout($templateFile,$charset,$contentType,$varPrefix,$display);
    }

    /**
     +----------------------------------------------------------
     * 模板变量赋值
     +----------------------------------------------------------
     * @access public 
     +----------------------------------------------------------
     * @param mixed $name 要显示的模板变量
     * @param mixed $value 变量的值
     +----------------------------------------------------------
     * @return void
     +----------------------------------------------------------
     */
    public function assign($name,$value='')
    {
        $this->tpl->assign($name,$value);
    }

    /**
     +----------------------------------------------------------
     * Trace变量赋值
     +----------------------------------------------------------
     * @access public 
     +----------------------------------------------------------
     * @param mixed $name 要显示的模板变量
     * @param mixed $value 变量的值
     +----------------------------------------------------------
     * @return void
     +----------------------------------------------------------
     */
    public function trace($name,$value='')
    {
        $this->tpl->trace($name,$value);
    }

    /**
     +----------------------------------------------------------
     * 取得模板显示变量的值
     +----------------------------------------------------------
     * @access public 
     +----------------------------------------------------------
     * @param string $name 模板显示变量
     +----------------------------------------------------------
     * @return mixed 
     +----------------------------------------------------------
     */
    public function get($name)
    {
        return $this->tpl->get($name);
    }

	protected function __set($name,$value) {
		$this->assign($name,$value);
	}

	protected function __get($name) {
		return $this->get($name);
	}
	
    /**
     +----------------------------------------------------------
     * 魔术方法 有不存在的操作的时候执行
     +----------------------------------------------------------
     * @access public 
     +----------------------------------------------------------
     * @param string $name 模板显示变量
     +----------------------------------------------------------
     * @return mixed 
     +----------------------------------------------------------
     */
	private function __call($method,$parms) {
		if(strtolower($method) == strtolower(ACTION_NAME.C('ACTION_SUFFIX'))) {
			// 检查是否存在模版 如果有直接输出模版
			if(file_exists(C('TMPL_FILE_NAME'))) {
				$this->display();
			}else { 
				// 如果定义了_empty操作 则调用
				if(method_exists($this,'_empty')) {
					$this->_empty();
				}else {
					if(C('DEBUG_MODE')) {
						// 调试模式抛出异常
						throw_exception(L('_ERROR_ACTION_').ACTION_NAME);      
					}else{
						// 执行默认操作
						$this->redirect(C('DEFAULT_ACTION'));
					}
				}
			}
		}
	}

    /**
     +----------------------------------------------------------
     * 操作错误跳转的快捷方法
     +----------------------------------------------------------
     * @access public 
     +----------------------------------------------------------
     * @param string $errorMsg 错误信息
     * @param Boolean $ajax 是否为Ajax方式
     +----------------------------------------------------------
     * @return void 
     +----------------------------------------------------------
     */
    public function error($errorMsg,$ajax=false) 
    {
        if($ajax || $this->get('ajax')) {
        	$this->ajaxReturn('',$errorMsg,0);
        }else {
            $this->assign('error',$errorMsg);
            $this->forward();        	
        }
    }

    /**
     +----------------------------------------------------------
     * 操作成功跳转的快捷方法
     +----------------------------------------------------------
     * @access public 
     +----------------------------------------------------------
     * @param string $message 提示信息
     * @param Boolean $ajax 是否为Ajax方式
     +----------------------------------------------------------
     * @return void 
     +----------------------------------------------------------
     */
    public function success($message,$ajax=false) 
    {
        if($ajax || $this->get('ajax')) {
        	$this->ajaxReturn('',$message,1);
        }else {
        	$this->assign('message',$message);
            $this->forward();
        }
    }

    /**
     +----------------------------------------------------------
     * Ajax方式返回数据到客户端
     +----------------------------------------------------------
     * @access public 
     +----------------------------------------------------------
     * @param mixed $data 要返回的数据
     * @param String $info 提示信息
     * @param String $status 返回状态
     * @param String $status ajax返回类型 JSON XML
     +----------------------------------------------------------
     * @return void 
     +----------------------------------------------------------
     */
    public function ajaxReturn($data='',$info='',$status='',$type='') 
    {
		// 保存日志
		Log::save();
        $result  =  array();
        if($status === '') {
        	$status  = $this->get('error')?0:1;
        }
        if($info=='') {
            if($this->get('error')) { 
                $info =   $this->get('error');                	
            }elseif($this->get('message')) {
                $info =   $this->get('message');                	
            }         	
        }
        $result['status']  =  $status;
   	    $result['info'] =  $info;
        $result['data'] = $data;
		if(empty($type)) $type	=	C('AJAX_RETURN_TYPE');
		if(strtoupper($type)=='JSON') {
			// 返回JSON数据格式到客户端 包含状态信息
			header("Content-Type:text/html; charset=".C('OUTPUT_CHARSET'));
			exit(json_encode($result));
		}elseif(strtoupper($type)=='XML'){
			// 返回xml格式数据
			header("Content-Type:text/xml; charset=".C('OUTPUT_CHARSET'));
			exit(xml_encode($result));
		}else{
			// TODO 增加其它格式
		}
    }

    /**
     +----------------------------------------------------------
     * 执行某个Action操作（隐含跳转） 支持指定模块和延时执行
     +----------------------------------------------------------
     * @access public 
     +----------------------------------------------------------
     * @param mixed $action 要跳转的Action 默认为_dispatch_jump
     * @param string $module 要跳转的Module 默认为当前模块
     * @param string $app 要跳转的App 默认为当前项目
     * @param boolean $exit  是否继续执行
     * @param integer $delay 延时跳转的时间 单位为秒
     +----------------------------------------------------------
     * @return void
     +----------------------------------------------------------
     */
    public function forward($action='_dispatch_jump',$module=MODULE_NAME,$app=APP_NAME,$exit=false,$delay=0)
    {
        if(!empty($delay)) {
            //指定延时跳转 单位为秒
        	sleep(intval($delay));
        }
        if(is_array($action)) {
            //通过类似 array(&$module,$action)的方式调用
        	call_user_func($action);
        }else {
            if( MODULE_NAME!= $module) {
				$class =	 A($module,$app);
                call_user_func(array(&$class,$action));
            }else {
                // 执行当前模块操作
                $this->{$action}();
            }
        }
        if($exit) {
        	exit();
        }else {
        	return ;
        }
    }

    /**
     +----------------------------------------------------------
     * Action跳转(URL重定向） 支持指定模块和延时跳转
     +----------------------------------------------------------
     * @access public 
     +----------------------------------------------------------
     * @param string $action 要跳转的Action 
     * @param string $module 要跳转的Module 默认为当前模块
     * @param string $app 要跳转的App 默认为当前项目
     * @param string $route 路由名
     * @param array $params 其它URL参数
     * @param integer $delay 延时跳转的时间 单位为秒
     * @param string $msg 跳转提示信息
     +----------------------------------------------------------
     * @return void
     +----------------------------------------------------------
     */
	public function redirect($action,$module=MODULE_NAME,$route='',$app=APP_NAME,$params=array(),$delay=0,$msg='') {
		$url	=	url($action,$module,$route,$app,$params);
		redirect($url,$delay,$msg);
	}

    /**
     +----------------------------------------------------------
     * 默认跳转操作 支持错误导向和正确跳转 
     * 调用模板显示 默认为public目录下面的success页面
     * 提示页面为可配置 支持模板标签
     +----------------------------------------------------------
     * @access private 
     +----------------------------------------------------------
     * @return string
     +----------------------------------------------------------
     * @throws ThinkExecption
     +----------------------------------------------------------
     */
    private function _dispatch_jump() 
    {
        if($this->get('ajax') ) {
            // 用于Ajax附件上传 显示信息
            if($this->get('_ajax_upload_')) {
                header("Content-Type:text/html; charset=".C('OUTPUT_CHARSET'));
            	exit($this->get('_ajax_upload_'));
            }else {
            	$this->ajaxReturn();
            }
        }
        // 普通方式跳转
        $templateFile = TEMPLATE_PATH.'/Public/success'.C('TEMPLATE_SUFFIX');
        //样式表文件
        if($this->get('error') ) {
            $msgTitle    =   L('_OPERATION_FAIL_');
        }else {
            $msgTitle    =   L('_OPERATION_SUCCESS_');
        }
        //提示标题
        $this->assign('msgTitle',$msgTitle);
        if($this->get('message')) { //发送成功信息
            //成功操作后停留1秒
            if(!$this->get('waitSecond')) 
                $this->assign('waitSecond',"1");
            //默认操作成功自动返回操作前页面
            if(!$this->get('jumpUrl')) 
                $this->assign("jumpUrl",$_SERVER["HTTP_REFERER"]);
        }
        if($this->get('error')) { //发送错误信息
            //发生错误时候停留3秒
            if(!$this->get('waitSecond')) 
                $this->assign('waitSecond',"3");
            //默认发生错误的话自动返回上页
            if(!$this->get('jumpUrl')) 
                $this->assign('jumpUrl',"javascript:history.back(-1);");
        }
        //如果设置了关闭窗口，则提示完毕后自动关闭窗口
        if($this->get('closeWin')) {
            $this->assign('jumpUrl','javascript:window.close();');
        }
    	$this->display($templateFile);
       
        // 中止执行  避免出错后继续执行
        exit ;       
    }
	
}//类定义结束
?>