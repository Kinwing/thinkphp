#!/usr/local/php/bin/php
<?php 
// ���������ļ�
require "./config.php";
// ���ع����ļ�
require "./common.php";

echo "
+----------------------------------------------------------------------+
| ThinkPHP   ������ɹ���                                              |
+----------------------------------------------------------------------+
| Copyright (c) 2006~2007 http://thinkphp.cn All rights reserved.      |
+----------------------------------------------------------------------+
| Licensed under the Apache License, Version 2.0 (the 'License');      |
| you may not use this file except in compliance with the License.     |
| You may obtain a copy of the License at                              |
| http://www.apache.org/licenses/LICENSE-2.0                           |
| Unless required by applicable law or agreed to in writing, software  |
| distributed under the License is distributed on an 'AS IS' BASIS,    |
| WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or      |
| implied. See the License for the specific language governing         |
| permissions and limitations under the License.                       |
+----------------------------------------------------------------------+
| Author: liu21st <liu21st@gmail.com>                                  |
+----------------------------------------------------------------------+ 
";

init();
// ��������
if($argc ==1) {
	// ���뽻��ģʽ
	begin();
}else{
	// �����Զ�ģʽ
	$type = $argv[1];
	$name = $argv[2];
	switch(strtolower($type)) {
		case 'help':
			help();
			break;
		case 'model':
			buildModel($name);
			break;
		case 'action':
			buildAction($name);
			break;
	}
}

?>