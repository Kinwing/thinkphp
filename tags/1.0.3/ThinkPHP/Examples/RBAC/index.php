<?php 
// ������ļ���ThinkPHP�Զ����� 
define('THINK_PATH', '../../');  //�������,�Լ��޸�Thinkphp��·��
//������Ŀ���ƣ���������壬Ĭ��Ϊ����ļ����� 
define('APP_NAME', 'RBAC'); 
define('APP_PATH', '.'); 
//����ThinkPHP��ܹ�������ļ� 
require(THINK_PATH.'/ThinkPHP.php');
//ʵ����һ����վӦ��ʵ�� 
$App = new App(); 
//ִ��Ӧ�ó���
$App->run(); 
?> 
