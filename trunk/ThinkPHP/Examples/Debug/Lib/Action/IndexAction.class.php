<?php 
class IndexAction extends Action{
	// 首页
	public function index(){
		// 设置调试点
		Debug::mark(1);
		$Form	= D("Form");
		Debug::mark(2);
		debug_start('数据查询');
		$list	=	$Form->top3('','id,title,content','id desc');
		Debug::mark(3);
		debug_end('数据查询');
		// 输出调试时间
		$this->assign('time1','useTime:'.Debug::useTime(1,2).'s useMem:'.Debug::useMemory(1,2).'kb');
		$this->assign('time2','useTime:'.Debug::useTime(2,3).'s useMem:'.Debug::useMemory(2,3).'kb');
		$this->assign('time3','useTime:'.Debug::useTime(1,3).'s useMem:'.Debug::useMemory(1,3).'kb');
		// 输出变量
		$this->assign('list',dump($list,false));

		$this->display();
	}
} 
?>