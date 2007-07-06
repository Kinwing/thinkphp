<?php 
// +----------------------------------------------------------------------+
// | ThinkCMS                                                             |
// +----------------------------------------------------------------------+
// | Copyright (c) 2006 liu21st.com All rights reserved.                  |
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
// $Id: CategoryAction.class.php 2 2007-01-03 07:52:09Z liu21st $

/**
 +------------------------------------------------------------------------------
 * CMS 分类管理
 +------------------------------------------------------------------------------
 * @author liu21st <liu21st@gmail.com>
 * @version  $Id: CategoryAction.class.php 2 2007-01-03 07:52:09Z liu21st $
 +------------------------------------------------------------------------------
 */
  import('@.Action.AdminAction');
class CategoryAction extends AdminAction 
{
   /**
     +----------------------------------------------------------
     * 列表过滤
     * 
     +----------------------------------------------------------
     * @access public 
     +----------------------------------------------------------
     * @param object $map 条件Map
     +----------------------------------------------------------
     * @return void
     +----------------------------------------------------------
     * @throws FcsException
     +----------------------------------------------------------
     */
	function _filter(&$map) 
	{
		if(ACTION_NAME=='index') {
            if(!$map->containsKey('pid') ) {
            	$map->put('pid',0);
            }
            Session::set('currentCategoryId',$map->get('pid'));
            //获取上级节点
            $dao  = new CategoryDao();
            $vo = $dao->getById($map->get('pid'));
            if($vo) {
                $this->assign('level',$vo->level+1);
            	$this->assign('categoryName',$vo->name);
            }else {
            	$this->assign('level',1);
            }
		}

	}

    /**
     +----------------------------------------------------------
     * 表单提交预处理
     * 
     +----------------------------------------------------------
     * @access public 
     +----------------------------------------------------------
     * @return void
     +----------------------------------------------------------
     * @throws FcsException
     +----------------------------------------------------------
     */
	function _operation() 
	{
       	if(Session::is_set('currentCategoryId')) {
       		$_POST['pid']	=	Session::get('currentCategoryId');
       	}else {
       		$_POST['pid']	=	0;
       	}
		$dao = new CategoryDao();
        if(empty($_POST['name'])) {
        	$_POST['name'] = urlencode($_POST['title']);
        }
        if(!empty($_POST['id'])) {
        	$result = $dao->find("name='".$_POST['name']."' and id !='".$_POST['id']."' and pid='".$_POST['pid']."'");
        }else {
        	$result = $dao->find("name='".$_POST['name']."' and pid='".$_POST['pid']."'");
        }
        if($result) {
        	$this->assign("error",'分类已经存在！');
            $this->forward();
        }		
	}

    /**
     +----------------------------------------------------------
     * 新增页面重载
     * 
     +----------------------------------------------------------
     * @access public 
     +----------------------------------------------------------
     * @param object $map 条件Map
     +----------------------------------------------------------
     * @return void
     +----------------------------------------------------------
     * @throws FcsException
     +----------------------------------------------------------
     */
	function add() 
	{

		$dao	= new CategoryDao();
		$vo = $dao->getById(Session::get('currentCategoryId'));
        $this->assign('parentCategory',$vo->name);
		$this->assign('level',$vo->level+1);

		parent::add();
	}

    /**
     +----------------------------------------------------------
     * 默认排序操作
     * 
     +----------------------------------------------------------
     * @access public 
     +----------------------------------------------------------
     * @return void
     +----------------------------------------------------------
     * @throws FcsException
     +----------------------------------------------------------
     */
    function sort() 
    {
		$dao	= new CategoryDao();
        if(!empty($_GET['pid'])) {
        	$parentId  = $_GET['pid'];
        }else {
   	        $parentId  = Session::get('currentCategoryId');
        }
		$vo = $dao->getById($parentId);
        if($vo) {
        	$level   =  $vo->level+1;
        }else {
        	$level   =  1;
        }
        $this->assign('level',$level);
        $sortList   =   $dao->findAll('pid='.$parentId.' and level='.$level,'','*','seqNo asc');
        $this->assign("sortList",$sortList);
        $this->display();
        return ;
    }

    /**
     +----------------------------------------------------------
     * 生成树型列表XML文件
     * 
     +----------------------------------------------------------
     * @access public 
     +----------------------------------------------------------
     * @return string
     +----------------------------------------------------------
     * @throws FcsException
     +----------------------------------------------------------
     */
    function tree() 
    {
        $dao	=	$this->getDaoClass();
        $map	=	$this->_search();
        $level	=	$dao->getMin('level',$map);
        $map->put('level',$level);
        $list	=	$dao->findall($map,'','*','seqNo');
        header("content-type:text/xml;charset=utf-8");
        $xml	=  '<?xml version="1.0" encoding="utf-8" ?>';
        if($map->containsKey('pid')) {
            $vo		=	$dao->find('id='.$map->get('pid'));
            $xml	.= '<tree caption="'.$vo->title.'" >';
        }else {
            $xml	.= '<tree caption="分类选择" >';
        }
        $xml	.=	$this->_toXmlTree($list,'title');
        $xml	.= '</tree>'; 
        exit($xml);
    }	
}//end class
?>