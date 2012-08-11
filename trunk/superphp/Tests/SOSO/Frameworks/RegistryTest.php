<?php
/**
* 测试脚本，测试用例在 Page 目录下
* @author : moonzhang (2010-02-12)
* @version v 1.1 2010-02-12
* @package SOSO_Frameworks
*/

class SOSO_Frameworks_RegistryTest extends PHPUnit_Framework_TestCase{

	/**
	 * 
	 * Enter description here ...
	 * @var SOSO_Frameworks_Registry
	 */
	private $instance;
	public function setUp(){
		$this->instance = SOSO_Frameworks_Registry::getInstance();
		$this->instance->set('hello',array('name'=>'test','id'=>1024));
	}
	
	public function testGet(){
		$res = $this->instance->get('hello');
		$this->assertArrayHasKey('name', $res);
		$this->assertArrayHasKey('id', $res);
		
		$this->assertEquals('test',$res['name']);
		$this->assertEquals(1024, $res['id']);
		
		$path = SOSO_Frameworks_Registry::getInstance()->get('root_path');
		$this->assertEquals($path, $this->instance->get('root_path'));
		
		$this->assertEquals($path,$this->instance->offsetGet('root_path'));
		$this->assertEquals($path,$this->instance['root_path']);
	}
	
	public function testSet(){
		$key = 'key_'.rand(0,1000);
		$val = 'val_'.rand(1000,5000);
		
		$this->instance->set($key,$val);
		
		$this->assertEquals($val,$this->instance->get($key));
		$this->assertEquals($val,$this->instance->offsetGet($key));
		$this->assertEquals($val,SOSO_Frameworks_Registry::getInstance()->get($key));
		
		
		$key2 = 'key_'.rand(0,1000);
		$val2 = 'val_'.rand(1000,5000);
		
		$this->instance[$key2] = $val2;
		
		$this->assertEquals($val2,$this->instance->get($key2));
		$this->assertEquals($val2,$this->instance->offsetGet($key2));
		$this->assertEquals($val2,SOSO_Frameworks_Registry::getInstance()->get($key2));
		
		$key3 = 'key_'.rand(0,1000);
		$val3 = 'val_'.rand(1000,5000);
		$this->instance->offsetSet($key3, $val3);
		$this->assertEquals($val3,$this->instance->get($key3));
		$this->assertEquals($val3,SOSO_Frameworks_Registry::getInstance()->get($key3));
	}
	
	public function testGetInstance(){
		$reg = SOSO_Frameworks_Registry::getInstance();
		//$this->assertType('SOSO_Frameworks_Registry', $reg);
		//$this->assertType('ArrayObject',$reg);
	}
	
	public function testIsRegistered(){
		$this->assertTrue($this->instance->isRegistered('hello'));
		$this->assertFalse($this->instance->isRegistered('world'));
	}
	public function testOffsetExists(){
		$this->assertTrue($this->instance->OffsetExists('hello'));
		$this->assertFalse($this->instance->OffsetExists('world'));
	}
	
	public function testOffsetGet(){
		$path = SOSO_Frameworks_Registry::getInstance()->get('root_path');
		$this->assertEquals($path, $this->instance->get('root_path'));
		
		$this->assertEquals($path,$this->instance->offsetGet('root_path'));
		$this->assertEquals($path,$this->instance['root_path']);
	}
	
	public function testOffsetSet(){
		//do test
	}
	public function testOffsetUnset(){
		$res = $this->instance->get('hello');
		$this->assertEquals('1024',$res['id']);
		
		$this->instance->offsetUnset('hello');
		$this->assertNull($this->instance->get('hello'));
		$this->assertFalse($this->instance->offsetExists('hello'));
	}
	
	public function testAppend(){
		//do test
	}
	public function testGetArrayCopy(){
		//do test
	}
	public function testCount(){
		//do test
	}
	public function testGetFlags(){
		//do test
	}
	public function testSetFlags(){
		//do test
	}
	public function testAsort(){
		//do test
	}
	public function testKsort(){
		//do test
	}
	public function testUasort(){
		//do test
	}
	public function testUksort(){
		//do test
	}
	public function testNatsort(){
		//do test
	}
	public function testNatcasesort(){
		//do test
	}
	public function testGetIterator(){
		//do test
	}
	public function testExchangeArray(){
		//do test
	}
	public function testSetIteratorClass(){
		//do test
	}
	public function testGetIteratorClass(){
		//do test
	}
		
}