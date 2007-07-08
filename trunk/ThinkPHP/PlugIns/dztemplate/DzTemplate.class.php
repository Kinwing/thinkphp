<?php
/**
* DISUCZ Template Class
*
* @version 1.0
*/

class DzTemplate {
    /**
     * ģ���ļ����Ŀ¼[Template file dir]
     *
     * @var string
     */	
	var $tpl_dir;
    /**
     * ģ��Ĭ���ļ����Ŀ¼[Template file dir]
     *
     * @var string
     */	
	var $tpl_default_dir;
    /**
     * ģ��Ĭ�ϻ�����Ŀ¼[Template file dir]
     *
	 * @var string
     */	
	var $the_tpl_dir;
    /**
     * ģ����Ŀ¼[Template file dir]
     *
     * @var string
     */	
	var $tpl_cache_dir;
    /**
     * ģ��ˢ��ʱ��[Template refresh time]
     *
     * @var int
     */	
	var $tpl_refresh_time;
    /**
     * ���ر�����ģ���ļ�[Return compiled file]
     *
     * @return string
     */
	function tpl($file){
		$tplfile=$this->tpl_dir."/".$file.".html";
		if(!is_readable($tplfile)) {
			$tplfile=$this->tpl_default_dir."/".$file.".html";
		} 
		$compiledtpldir=$this->tpl_cache_dir.$this->the_tpl_dir.".tpl";//�������Ŀ¼[Define compile dir]
		$compiledtplfile=$compiledtpldir."/".$file.".tpl.php";//��������ļ�[Define compile file]
		is_dir($compiledtpldir) or @mkdir($compiledtpldir,0777);		
		if(!file_exists($compiledtplfile) || (time()-@filemtime($compiledtplfile) > $this->tpl_refresh_time))//�ļ������ڻ��ߴ������ڳ���ˢ��ʱ��
		{
			$this->tpl_compile($tplfile,$compiledtplfile);//����ģ��[Compile template]
		}
		clearstatcache();
		return $compiledtplfile;
	}
    /**
     * ����ģ���ļ�[Compile template]
     *
     * @return boolean
     */
	function tpl_compile($tplfile,$compiledtplfile){
		$str=$this->tpl_read($tplfile);
		$str=$this->tpl_parse($str);
		if($this->tpl_write($compiledtplfile,$str))
		{
			return true;
		}
		return false;      
	}
    /**
     * ����ģ���ļ�[Parse template]
     *
     * @return string
     */
	function tpl_parse($str){ 
		$str=preg_replace("/([\n\r]+)\t+/s","\\1",$str);
		//��Ŀ����Ŀ¼
		$str = str_ireplace('../public',APP_PUBLIC_URL,$str);
		$str = str_ireplace('../Public',APP_PUBLIC_URL,$str);
		//��վ����Ŀ¼
		$str = str_replace('__PUBLIC__',WEB_PUBLIC_URL,$str);
		//����Action����
		$str = str_replace('__VAR_ACTION__',C('VAR_ACTION'),$str);
		//����Module����
		$str = str_replace('__VAR_MODULE__',C('VAR_MODULE'),$str);  		
		$str=preg_replace("/\<\!\-\-\{(.+?)\}\-\-\>/s", "{\\1}",$str);	  
		$str=preg_replace("/\{template\s+(.+)\}/","\n<?php include template('\\1','{$this->the_tpl_dir}'); ?>\n",$str);
		$str = preg_replace("/[\n\r\t]*\{eval\s+(.+?)\}[\n\r\t]*/ies", "stripvtags('\n<? \\1 ?>\n','')", $str);
		$str=preg_replace("/\{include\s+(.+)\}/","\n<?php include \\1; ?>\n",$str);
		$str=preg_replace("/\{if\s+(.+?)\}/","<? if(\\1) { ?>",$str);
		$str=preg_replace("/\{else\}/","<? } else { ?>",$str);
		$str=preg_replace("/\{elseif\s+(.+?)\}/","<? } elseif (\\1) { ?>",$str);
		$str=preg_replace("/\{\/if\}/","<? } ?>",$str);
		$str=preg_replace("/\{loop\s+(\S+)\s+(\S+)\}/","<? if(is_array(\\1)) foreach(\\1 AS \\2) { ?>",$str);
		$str=preg_replace("/\{loop\s+(\S+)\s+(\S+)\s+(\S+)\}/","\n<? if(is_array(\\1)) foreach(\\1 AS \\2 => \\3) { ?>",$str);
		$str=preg_replace("/\{\/loop\}/","\n<? } ?>\n",$str);
		$str=preg_replace("/\{([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*\((.+)\))\}/","<?=\\1?>",$str);
		$str=preg_replace("/\{\\$([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*\((.+)\))\}/","<?=\\1?>",$str);
		$str=preg_replace("/\{(\\$[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)\}/","<?=\\1?>",$str);
		$str=preg_replace("/\{(\\$[a-zA-Z0-9_\[\]\'\"\$\x7f-\xff]+)\}/s", "<?=\\1?>",$str);
		$str=preg_replace("/\{([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)\}/s", "<?=\\1?>",$str);
		$str="<? if(!defined('THINK_PATH')) exit('Access Denied'); ?>\n".$str;//��ֱֹ�����ģ������ļ�
		return $str;
	}
    /**
     * ��ȡģ��Դ�ļ�[Read resource file]
     *
     * @return string
     */
	function tpl_read($tplfile){
		if($fp=@fopen($tplfile,"r"))
		{
			$str=fread($fp,filesize($tplfile));
			fclose($fp);
			return $str;	
		}else{
			throw_exception(L('_TEMPLATE_NOT_EXIST_')); 
		}
		return false;
	}
    /**
     * д��ģ������ļ�[Write compiled file]
     *
     * @return boolean
     */
	function tpl_write($compiledtplfile,$str){
		if($fp=@fopen($compiledtplfile,"w"))
		{
			flock($fp, 3);
			if(@fwrite($fp,$str))
			{
				fclose($fp);
				return true;
			}else{
				throw_exception('ģ�滺���ļ��޷�д�룡');
			}
			fclose($fp);
		}else{
			throw_exception('ģ�滺���ļ�д��ʧ�ܣ�');
		}
		return false;
	}
	/**
	 * �趨ģ�����
	 */
	function updir($path)
	{
		$paths=@explode('/',$path);
		$count=count($paths)-1;
		return $paths[$count];
	}
}
?>