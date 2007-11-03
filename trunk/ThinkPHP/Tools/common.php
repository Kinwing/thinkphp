<?php 
// +----------------------------------------------------------------------+
// | ThinkPHP                                                             |
// +----------------------------------------------------------------------+
// | Copyright (c) 2006~2007 http://thinkphp.cn All rights reserved.      |
// +----------------------------------------------------------------------+
// | Licensed under the Apache License, Version 2.0 (the 'License');      |
// | you may not use this file except in compliance with the License.     |
// | You may obtain a copy of the License at                              |
// | http://www.apache.org/licenses/LICENSE-2.0                           |
// | Unless required by applicable law or agreed to in writing, software  |
// | distributed under the License is distributed on an 'AS IS' BASIS,    |
// | WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or      |
// | implied. See the License for the specific language governing         |
// | permissions and limitations under the License.                       |
// +----------------------------------------------------------------------+
// | Author: liu21st <liu21st@gmail.com>                                  |
// +----------------------------------------------------------------------+
// $Id$

//����ThinkPHP����
include THINK_PATH."/Lib/Think/Core/Base.class.php";
include THINK_PATH."/Lib/Think/Util/Config.class.php";

function init() {
	mk_dir(APP_PATH.'Lib/Model/');
	mk_dir(APP_PATH.'Lib/Action/');
}
function mk_dir($dir, $mode = 0755)
{
  if (is_dir($dir) || @mkdir($dir,$mode)) return true;
  if (!mk_dir(dirname($dir),$mode)) return false;
  return @mkdir($dir,$mode);
}
function buildModel($name='') {
	if(empty($name)) {
		import("Think.Db.Db");
		$db	=	DB::getInstance();
		$tables = $db->getTables(DB_NAME);
		foreach ($tables as $table){
			$table    =   str_replace(DB_PREFIX,'',$table);	
			buildModel($table);
		}
	}else{
		$name	=	ucwords($name);
		echo '��������'.$name.'Model��...';
		$filename = APP_PATH.'Lib/Model/'.$name.'Model.class.php';
		if(!file_exists($filename)) {
			$content =   "<?php \n";
			$content .= "class ".$name."Model extends Model \n{\n";
			$content .= "}\n?>";
			if(file_put_contents($filename,$content)){
				echo "...Complete\n";
			}else{
				echo "...Fail\n";
			};
		}else{
			echo "...Exists\n";
		}
	}
}

function buildAction($name='') {
	if(empty($name)) {
		import("Think.Db.Db");
		$db	=	DB::getInstance();
		$tables = $db->getTables(DB_NAME);
		foreach ($tables as $table){
			$table    =   str_replace(DB_PREFIX,'',$table);	
			buildAction($table);
		}
	}else{
		$name	=	ucwords($name);
		echo '��������'.$name.'Action��...';
		$filename = APP_PATH.'Lib/Action/'.$name.'Action.class.php';
		if(!file_exists($filename)) {
			$content =   "<?php \n";
			$content .= "class ".$name."Action extends Action \n{\n";
			$content .= "}\n?>";
			if(file_put_contents($filename,$content)){
				echo "...Complete\n";	
			}else{
				echo "...Fail\n";
			};
		}else{
			echo "...Exists\n";
		}
	}
}
function begin() {
echo "
+------------------------
| [ 1 ] ����Model 
| [ 2 ] ����Action  
| [ 0 ] �˳�
+------------------------
��������ѡ��:";
	$number = trim(fgets(STDIN,256));
	//fscanf(STDIN, "%d\n", $number); // �� STDIN ��ȡ����
	switch($number) {
	case 0:
		break;
	case 1:
		echo "����Model����[���� User���������ɵ�ǰ���ݿ��ȫ��Model�� ]:";
		$model = trim(fgets(STDIN,256));
		if(strpos($model,',')) {
			echo "��������Model...\n";
			$models = explode(',',$model);
			foreach ($models as $model){
				buildModel($model);
			}
		}else{
			// ����ָ����Model��
			buildModel($model);
		}
		begin();
		break;
	case 2:
		// ����ָ����Action��
		echo "����Action����[���� User ]:";
		$action = trim(fgets(STDIN,256));
		buildAction($action);
		begin();
		break;
	default:
		begin();
	}
}

function help() {
	echo "
�����ʽ��php Build type <...>
example:
build model <name> ����Model��
build action <name> ����Action��
build help ����
	";
	exit;
}
?>