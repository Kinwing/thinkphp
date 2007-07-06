<?php
/*
+---------------------------------------------------------+
| ��Ŀ: FCS -- Fast,Compatible & Simple OOP PHP Framework |
| �ļ�: Stack.class.php								      |
| ����: Stack��											  |
+---------------------------------------------------------+
| ����ܴ������GPLЭ�飬�����ʹ�ã������뱣����Ȩ��Ϣ	  |
| ��Ȩ����: Copyright�� 2005-2006 �������� ��Ȩ����		  |
| �� ҳ:	http://www.liu21st.com						  |
| �� ��:	Liu21st <����> liu21st@gmail.com			  |
+---------------------------------------------------------+
*/
import("FCS.util.ArrayList");

class Stack extends ArrayList
{
	// +----------------------------------------
	// |	�ܹ�����
	// +----------------------------------------
	function __construct($values = array())
	{
		parent::__construct($values);
	}

	// +----------------------------------------
	// |	
	// +----------------------------------------
	function peek()
	{
		return reset($this->toArray());
	}

	// +----------------------------------------
	// |	���һ��Ԫ�س�ջ
	// +----------------------------------------
	function pop()
	{
		$el_array = $this->toArray();
		$return_val = array_pop($el_array);
		$this->_elements = $el_array;
		return $return_val;
	}

	//+----------------------------------------
	//|	Ԫ�ؽ�ջ
	//+----------------------------------------
	function push($value)
	{
		$this->add($value);
		return $value;
	}
}
?>
