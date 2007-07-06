<?php 
// +---------------------------------------------------------------------------+
// | FCS -- Fast,Compatible & Simple OOP PHP Framework                         |
// +---------------------------------------------------------------------------+
// | Copyright (c) 2005-2006 liu21st.com.  All rights reserved.                |
// | Website: http://www.fcs.org.cn/                                           |
// | Author : Liu21st ���� <liu21st@gmail.com>                                 |
// +---------------------------------------------------------------------------+
// | This program is free software; you can redistribute it and/or modify it   |
// | under the terms of the GNU General Public License as published by the     |
// | Free Software Foundation; either version 2 of the License,  or (at your   |
// | option) any later version.                                                |
// |                                                                           |
// | This program is distributed in the hope that it will be useful,  but      |
// | WITHOUT ANY WARRANTY; without even the implied warranty of                |
// | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU General |
// | Public License for more details.                                          |
// +---------------------------------------------------------------------------+

/**
 +------------------------------------------------------------------------------
 * FCS
 +------------------------------------------------------------------------------
 * @package    Util
 * @link       http://www.fcs.org.cn
 * @copyright  Copyright (c) 2005-2006 liu21st.com.  All rights reserved. 
 * @author     liu21st <liu21st@gmail.com>
 * @version    $Id$
 +------------------------------------------------------------------------------
 */

if(!class_exists('DirectoryIterator')){//PHP5����������DirectoryIterator�࣬����Ҫ���¶���

    import("FCS.Util.ListIterator");
    /**
     +------------------------------------------------------------------------------
     * DirectoryIteratorʵ���� PHP5����������DirectoryIterator��
     +------------------------------------------------------------------------------
     * @package   Util
     * @author    liu21st <liu21st@gmail.com>
     * @version   0.8.0
     +------------------------------------------------------------------------------
     */
    class DirectoryIterator extends ListIterator 
    {//�ඨ�忪ʼ

        /**
         +----------------------------------------------------------
         * Ŀ¼����
         +----------------------------------------------------------
         * @var array
         * @access protected
         +----------------------------------------------------------
         */
        var $_dir = array();

        /**
         +----------------------------------------------------------
         * �ܹ�����
         * 
         +----------------------------------------------------------
         * @access public 
         +----------------------------------------------------------
         * @param string $path  Ŀ¼·��
         +----------------------------------------------------------
         */
        function __construct($path)
        {
            if(substr($path, -1) != "/")    $path .= "/";
            $this->listFile($path);
            parent::__construct($this->_dir);
        }

        /**
         +----------------------------------------------------------
         * ȡ��Ŀ¼������ļ���Ϣ
         * 
         +----------------------------------------------------------
         * @access public 
         +----------------------------------------------------------
         * @param mixed $pathname ·��
         +----------------------------------------------------------
         */
        function listFile($pathname) 
        {
            static $ListDirs = array();
            if(!isset($ListDirs[$pathname])){
                $handle = opendir($pathname);
                $i =0;
                $dir = array();
                while ( false !== ($file = readdir($handle)) ) {
                    if ($file != "." && $file != "..") {
                        $path = $pathname . $file;
                        $dir[$i]['filename']    = $file;
                        $dir[$i]['pathname']    = realpath($path);
                        $dir[$i]['owner']        = fileowner($path);
                        $dir[$i]['perms']        = fileperms($path);
                        $dir[$i]['inode']        = fileinode($path);
                        $dir[$i]['group']        = filegroup($path);
                        $dir[$i]['path']        = dirname($path);
                        $dir[$i]['atime']        = fileatime($path);
                        $dir[$i]['ctime']        = filectime($path);
                        $dir[$i]['size']        = filesize($path);
                        $dir[$i]['type']        = filetype($path);
                        $dir[$i]['mtime']        = filemtime($path);
                        $dir[$i]['isDir']        = is_dir($path);
                        $dir[$i]['isFile']        = is_file($path);
                        $dir[$i]['isLink']        = is_link($path);
                        //$dir[$i]['isExecutable']= function_exists('is_executable')?is_executable($path):'';
                        $dir[$i]['isReadable']    = is_readable($path);
                        $dir[$i]['isWritable']    = is_writable($path);
                    }
                    $i++;
                }
                closedir($handle);
                $this->_dir = $dir;
                $ListDirs[$pathname] = $dir;
            }else{
                $this->_dir = $ListDirs[$pathname];
            }
        }

        /**
         +----------------------------------------------------------
         * �ļ��ϴη���ʱ��
         * 
         +----------------------------------------------------------
         * @access public 
         +----------------------------------------------------------
         * @return integer
         +----------------------------------------------------------
         */
        function getATime()
        {
            $current = $this->current($this->_dir);
            return $current['atime'];
        }

        /**
         +----------------------------------------------------------
         * ȡ���ļ��� inode �޸�ʱ��
         * 
         +----------------------------------------------------------
         * @access public 
         +----------------------------------------------------------
         * @return integer
         +----------------------------------------------------------
         */
        function getCTime()
        {
            $current = $this->current($this->_dir);
            return $current['ctime'];
        }

        /**
         +----------------------------------------------------------
         * ������Ŀ¼�ļ���Ϣ
         * 
         +----------------------------------------------------------
         * @access public 
         +----------------------------------------------------------
         * @return DirectoryIterator
         +----------------------------------------------------------
         */
        function getChildren()
        {
            $current = $this->current($this->_dir);
            if($current['isDir']){
                return new DirectoryIterator($current['pathname']);
            }
            return false;
        }

        /**
         +----------------------------------------------------------
         * ȡ���ļ���
         * 
         +----------------------------------------------------------
         * @access public 
         +----------------------------------------------------------
         * @return string
         +----------------------------------------------------------
         */
        function getFilename()
        {
            $current = $this->current($this->_dir);
            return $current['filename'];
        }

        /**
         +----------------------------------------------------------
         * ȡ���ļ�����
         * 
         +----------------------------------------------------------
         * @access public 
         +----------------------------------------------------------
         * @return integer
         +----------------------------------------------------------
         */
        function getGroup()
        {
            $current = $this->current($this->_dir);
            return $current['group'];
        }

        /**
         +----------------------------------------------------------
         * ȡ���ļ��� inode
         * 
         +----------------------------------------------------------
         * @access public 
         +----------------------------------------------------------
         * @return integer
         +----------------------------------------------------------
         */
        function getInode()
        {
            $current = $this->current($this->_dir);
            return $current['inode'];
        }

        /**
         +----------------------------------------------------------
         * ȡ���ļ����ϴ��޸�ʱ��
         * 
         +----------------------------------------------------------
         * @access public 
         +----------------------------------------------------------
         * @return integer
         +----------------------------------------------------------
         */
        function getMTime()
        {
            $current = $this->current($this->_dir);
            return $current['mtime'];
        }
        
        /**
         +----------------------------------------------------------
         * ȡ���ļ���������
         * 
         +----------------------------------------------------------
         * @access public 
         +----------------------------------------------------------
         * @return string
         +----------------------------------------------------------
         */
        function getOwner()
        {
            $current = $this->current($this->_dir);
            return $current['owner'];
        }

        /**
         +----------------------------------------------------------
         * ȡ���ļ�·�����������ļ���
         * 
         +----------------------------------------------------------
         * @access public 
         +----------------------------------------------------------
         * @return string
         +----------------------------------------------------------
         */
        function getPath()
        {
            $current = $this->current($this->_dir);
            return $current['path'];
        }

        /**
         +----------------------------------------------------------
         * ȡ���ļ�������·���������ļ���
         * 
         +----------------------------------------------------------
         * @access public 
         +----------------------------------------------------------
         * @return string
         +----------------------------------------------------------
         */
        function getPathname()
        {
            $current = $this->current($this->_dir);
            return $current['pathname'];
        }

        /**
         +----------------------------------------------------------
         * ȡ���ļ���Ȩ��
         * 
         +----------------------------------------------------------
         * @access public 
         +----------------------------------------------------------
         * @return integer
         +----------------------------------------------------------
         */
        function getPerms()
        {
            $current = $this->current($this->_dir);
            return $current['perms'];
        }

        /**
         +----------------------------------------------------------
         * ȡ���ļ��Ĵ�С
         * 
         +----------------------------------------------------------
         * @access public 
         +----------------------------------------------------------
         * @return integer
         +----------------------------------------------------------
         */
        function getSize()
        {
            $current = $this->current($this->_dir);
            return $current['size'];
        }

        /**
         +----------------------------------------------------------
         * ȡ���ļ�����
         * 
         +----------------------------------------------------------
         * @access public 
         +----------------------------------------------------------
         * @return string
         +----------------------------------------------------------
         */
        function getType()
        {
            $current = $this->current($this->_dir);
            return $current['type'];
        }

        /**
         +----------------------------------------------------------
         * �Ƿ�ΪĿ¼
         * 
         +----------------------------------------------------------
         * @access public 
         +----------------------------------------------------------
         * @return boolen
         +----------------------------------------------------------
         */
        function isDir()
        {
            $current = $this->current($this->_dir);
            return $current['isDir'];
        }

        /**
         +----------------------------------------------------------
         * �Ƿ�Ϊ�ļ�
         * 
         +----------------------------------------------------------
         * @access public 
         +----------------------------------------------------------
         * @return boolen
         +----------------------------------------------------------
         */
        function isFile()
        {
            $current = $this->current($this->_dir);
            return $current['isFile'];
        }

        /**
         +----------------------------------------------------------
         * �ļ��Ƿ�Ϊһ����������
         * 
         +----------------------------------------------------------
         * @access public 
         +----------------------------------------------------------
         * @return boolen
         +----------------------------------------------------------
         */
        function isLink()
        {
            $current = $this->current($this->_dir);
            return $current['isLink'];
        }


        /**
         +----------------------------------------------------------
         * �ļ��Ƿ����ִ��
         * 
         +----------------------------------------------------------
         * @access public 
         +----------------------------------------------------------
         * @return boolen
         +----------------------------------------------------------
         */
        function isExecutable()
        {
            $current = $this->current($this->_dir);
            return $current['isExecutable'];
        }


        /**
         +----------------------------------------------------------
         * �ļ��Ƿ�ɶ�
         * 
         +----------------------------------------------------------
         * @access public 
         +----------------------------------------------------------
         * @return boolen
         +----------------------------------------------------------
         */
        function isReadable()
        {
            $current = $this->current($this->_dir);
            return $current['isReadable'];
        }

        /**
         +----------------------------------------------------------
         * ��ȡforeach�ı�����ʽ
         * 
         +----------------------------------------------------------
         * @access public 
         +----------------------------------------------------------
         * @return string
         +----------------------------------------------------------
         */
        function getIterator()
        {
             return new ArrayObject($this->_dir);
        }

    }//�ඨ�����
}
?>