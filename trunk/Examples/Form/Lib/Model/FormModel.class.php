<?php
// 导入高级模型类 用以支持表单自动验证和自动完成
import('AdvModel');
class FormModel extends AdvModel {
	// 自动验证设置
	protected $_validate	 =	 array(
		array('title','require','标题必须！',1),
		array('email','email','邮箱格式错误！',2),
		array('content','require','内容必须'),
		array('title','','标题已经存在',0,'unique',self::INSERT_STATUS),
		);
	// 自动填充设置
	protected $_auto	 =	 array(
		array('status','1',self::MODEL_INSERT),
		array('create_time','time',self::MODEL_INSERT,'function'),
		);

}
?>