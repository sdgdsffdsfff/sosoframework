<?php
/**
* 测试脚本，测试用例在 Page 目录下
* @author : moonzhang (2010-02-13)
* @version v 1.1 2010-02-13
* @package SOSO_Cache
*/

class SOSO_Cache_ApcTest extends PHPUnit_Framework_TestCase{
		
	public function testRun(){
		$res = extension_loaded('apc');
		//$this->assertTrue($res,'APC extension must be loaded');
		
		if($res){
			$test = new SOSO_Cache_Apc();
			$val = array('name'=>'apc_test','val'=>'soso_cache_apc');
			$key = 'hello';
			$res = $test->add($key,$val);
			$this->assertTrue($res);
			
			$data = $test->read($key);
			$this->assertSame($val,$data);
			
			$test->add($key,'OtherNewValue');
			$this->assertSame($val,$test->read($key));
			
			$new_value = 'apc-test';
			$test->write($key,$new_value);
			$this->assertEquals($new_value, $test->read($key));
			
			$this->assertTrue($test->isCached($key));
			
			$test->delete($key);
			$this->assertFalse($test->isCached($key));
			$this->assertNull($test->read($key));
			
			$this->assertTrue($test->gc());
		}else{
			$this->markTestSkipped('The APC extension is not available.');
		}
	}
	
	public function testFlush(){
		//$this->markTestIncomplete('This test has not been implemented yet.');
	}
	public function testAdd(){
		//do test
	}
	public function testRead(){
		//do test
	}
	public function testWrite(){
		//do test
	}
	public function testIsCached(){
		//do test
	}
	public function testDelete(){
		//do test
	}
	public function testIsExpired(){
		//do test
	}
	public function testGc(){
		//do test
	}
	public function testFactory(){
		//do test
	}
	public function testToString(){
		//do test
	}
	public function testGetInstances(){
		//do test
	}
	public function testDestruct(){
		//do test
	}
	public function testGetKey(){
		//do test
	}
	public function testSetCachingTime(){
		//do test
	}
	public function testSet(){
		//do test
	}
	public function testSetCaching(){
		//do test
	}
	public function testEncode(){
		//do test
	}
	public function testDecode(){
		//do test
	}
	public function testSetOption(){
		//do test
	}
	public function testGetOption(){
		//do test
	}
	public function testGarbageCollection(){
		//do test
	}
	public function testCalExpireTime(){
		//do test
	}
		
}