<?php
// +----------------------------------------------------------------------
// | ThinkPHP
// +----------------------------------------------------------------------
// | Copyright (c) 2008 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------
// $Id$

require_once THINK_PATH . '/Lib/Think/Core/Base.class.php';

class MyBase extends Base
{
    public $Test;
    public $Hello;
}

class Lib_Think_Core_BaseTest extends PHPUnit_Framework_TestCase
{
    /**
     * ȷ��__set()�ܹ���ȷ��������
     */
    public function test__set()
    {
        $expected = 'OK';
        $c = new MyBase();
        $c->Test = $expected;
        $this->assertEquals($expected, $c->Test);
        $c->msg = 'test null';
        $this->assertNull($c->msg);
    }
    /**
     * ȷ��__get()�ܹ���ȷ��ȡ����
     */
    public function test__get()
    {
        $expected = 'Bingo';
        $c = new MyBase();
        $c->Hello = $expected;
        $this->assertEquals($expected, $c->Hello);
    }
}
?>