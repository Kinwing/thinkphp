ʹ�õ�Ԫ���Թ���
================

[��������]

��PHP�ű�·������PATH����������

[Ŀ¼����]

TestsĿ¼�µ�Docs��TempĿ¼�����д��

[��������]

Confiugre.php�ж�����Ĭ�ϵ����ڲ��Ե����ݿ��ʺţ�Ϊ�˷���������鲻Ҫ�޸ģ�����
���������ʺź����ݿ��ʺš�
Ĭ�����ݿ������ʺš����붼��test���˿�ʹ��Ĭ�ϡ�

[���в���]

����TestsĿ¼������test (Windows)��./test (Linux/Unix)��DocsĿ¼�»��������
��־log.txt��log.xml�Ͳ����ĵ�test.html��test.txt��

[���ɱ���]

��װxdebug��չ���޸�PHP�����ļ�����zend debugger��zend optimizerע�͵������һ�У�
zend_extension_ts="D:\PHP\ext\php_xdebug.dll"

����report��./report������Docs/ReportĿ¼�����ɴ��븲���ʱ��档

[����Ŀ¼]

����clean��./clean����Docs������־�ͱ��档

[Ŀ¼�ṹ]

Docs           �����ĵ�����Ŀ¼
Temp           ����������ʱĿ¼
ThinkPHP       ��������Ŀ¼
AllTests.php   �������ļ�
build.xml      ant�����ļ�
clean          Shell�ű���ִ��Clean.php
clean.bat      ������ű���ִ��Clean.php
Clean.php      PHP�ű�������DocsĿ¼
config.xml     �������ɴ��븲���ʱ����XML�����ļ�
Configure.php  ���������ļ�
README.txt     ��Ԫ����˵��
report         Shell�ű���ִ�в��Բ����ɱ���
report.bat     ������ű���ִ�в��Բ����ɱ���
test           Shell�ű���ִ�в���
test.bat       ������ű���ִ�в���

[�Զ�������]

ThinkPHP/Vendor/PHPUnit ΪPHPUnitĿ¼��
ThinkPHP/Tools/phpunit.php ΪPHPUnit�����й��ߣ������Զ���������С�

php "../ThinkPHP/Tools/phpunit.php" [����]

�Զ����ɲ�����
php "../ThinkPHP/Tools/phpunit.php" --skeleton [����] [Դ�ļ�]

ע�⣺Դ�ļ���������������࣬Ҫ�Ȱ��������࣬����ȷ�����ļ��ܵ������ж�������

ʹ��Apache Ant���в��ԡ�
ant         ִ�в���
ant report  ���ɲ��Ա���
ant clean   ���������־���ĵ�
