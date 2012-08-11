<?php
/**
* 测试脚本，测试用例在 Page 目录下
* @author : moonzhang (2009-12-09)
* @version v 1.1 2009-12-09
* @package SOSO
*/
require_once("SOSO/ObjectStorage.php");
class SOSO_ObjectStorageTest extends PHPUnit_Framework_TestCase{
	/**
	 * 
	 * Enter description here ...
	 * @var SOSO_ObjectStorage
	 */
	public static $mStorage;
	
	public function setUp(){
		self::$mStorage = new SOSO_ObjectStorage();
		self::$mStorage->attach((object)array('name'=>'moonzhang','pass'=>'test'));
	}
	
	public function testRun(){
		
		$obj = (object)array('name'=>'moonzhang2','pass'=>'test2');
		$this->assertEquals(count(self::$mStorage), 1);
		
		self::$mStorage->attach($obj);
		$this->assertEquals(count(self::$mStorage), 2);
		$this->assertEquals(self::$mStorage->shift()->name, 'moonzhang');
		$this->assertEquals(self::$mStorage->pop()->name, $obj->name);
		
		
		self::$mStorage->detach($obj);
		$this->assertEquals(count(self::$mStorage), 1);
		
		self::$mStorage->clear();
		$this->assertEquals(count(self::$mStorage), 0);
		
		self::$mStorage->push($obj);
		$this->assertEquals(count(self::$mStorage), 1);
		$this->assertEquals(self::$mStorage->shift()->name, $obj->name);
		$this->assertTrue(self::$mStorage->contains($obj));
		
	}
	
	public function testRewind(){
		//do test
	}
	public function testValid(){
		//do test
	}
	public function testKey(){
		//$this->assertType($this->mStorage->key(),'int');
	}
	public function testCurrent(){
		
	}
	public function testNext(){
		//do test
	}
	public function testCount(){
		//do test
	}
	public function testContains(){
		//do test
	}
	public function testAttach(){
		//do test
	}
	public function testDetach(){
		//do test
	}
	public function testPop(){
		//do test
	}
	public function testShift(){
		//do test
	}
	public function testPush(){
		//do test
	}
	public function testUnshift(){
		//do test
	}
	public function testClear(){
		//do test
	}
		
}