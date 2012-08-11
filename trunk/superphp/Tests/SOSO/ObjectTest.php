<?php
/**
* 测试脚本，测试用例在 Page 目录下
* @author : moonzhang (2010-02-06)
* @version v 1.1 2010-02-06
* @package SOSO
*/

require_once("SOSO/Object.php");
class SOSO_ObjectTest extends PHPUnit_Framework_TestCase{
	public $mAppID;
	
	public function testConstruct(){
		$obj = new SOSO_Object();
		$this->assertClassHasAttribute('mAppID','SOSO_Object');
		$this->assertEquals('SOSO_Object', strval($obj));
		$this->assertObjectHasAttribute('mAppID', $obj);
		//$this->assertType('DOMDocument',$obj->_toDom());
	}
}