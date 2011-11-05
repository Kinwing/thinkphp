<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2009 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------
// $Id$

/**
 +------------------------------------------------------------------------------
 * ResultIterator类 用于实现数据库的延迟加载
 * TODO 实现SQL的动态组装
 +------------------------------------------------------------------------------
 * @category   Think
 * @package  Think
 * @subpackage  Db
 * @author    liu21st <liu21st@gmail.com>
 * @version   $Id$
 +------------------------------------------------------------------------------
 */
class ResultIterator extends Base implements IteratorAggregate
{
    // 执行查询的SQL
    private $sql      =   null;
    // 查询的对象封装
    private $map    =   null;
    // 数据库操作对象
    private $db      =   null;
    // 返回的查询数据的数目
    private $size    =   null;
    // 返回的查询数据
    private $data   =   null;

    /**
     +----------------------------------------------------------
     * 架构函数
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @param string $array  初始化数组元素
     +----------------------------------------------------------
     */
    public function __construct($sql='')
    {
        $this->sql  =   $sql;
    }

    /**
     +----------------------------------------------------------
     * 获取Iterator因子
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @return Iterate
     +----------------------------------------------------------
     */
    public function getIterator()
    {
        $result =   $this->getData();
        return $result;
    }

    /**
     +----------------------------------------------------------
     * 实际获取查询结果
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @return ArrayObject
     +----------------------------------------------------------
     */
    public function getData() {
        if(empty($this->data)) {
            $this->db   =   Db::getInstance();
            $this->data =   $this->db->query($this->sql);
            if(is_array($this->data)) {
                $this->size  =   count($this->data);
            }
        }
        return $this->data;
    }

    /**
     +----------------------------------------------------------
     * 获取查询结果数目
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @return integer
     +----------------------------------------------------------
     */
    public function size() {
        if(empty($this->size)) {
            $this->getData();
        }
        return $this->size;
    }

    /**
     +----------------------------------------------------------
     * 获取要执行的SQL
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @return integer
     +----------------------------------------------------------
     */
    public function getSql() {
        return $this->sql;
    }

    /**
     +----------------------------------------------------------
     * 重置查询结果
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @return integer
     +----------------------------------------------------------
     */
    public function resetData() {
        $this->data = null;
    }
};
?>