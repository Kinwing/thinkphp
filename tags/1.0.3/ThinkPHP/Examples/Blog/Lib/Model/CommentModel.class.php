<?php 
import('@.Model.CommonModel');
class CommentModel extends CommonModel 
{


	protected $_auto	 =	 array(
		array('cTime','time','ADD','function'),	
		array('content','htmlspecialchars','ADD','function'),
		array('status','1','ADD'),		
		array('ip','get_client_ip','ADD','function'),
		array('agent','userAgent','ADD','callback'),
		);
	protected $_validate	 =	 array(
		array('author','require','用户名必须!'),
		array('email','email','邮箱格式错误',2),
		array('content','require','回复内容必须'),
		array('verify','require','验证码必须'),
		array('verify','CheckVerify','验证码错误',0,'callback'),
		);


	public function userAgent() {
		return strval($_SERVER["HTTP_USER_AGENT"]);
	}
}
?>