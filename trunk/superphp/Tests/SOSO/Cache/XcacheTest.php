<?php
/**
* 测试脚本，测试用例在 Page 目录下
* @author : moonzhang (2010-01-08)
* @version v 1.1 2010-01-08
* @package SOSO_Cache
*/

class SOSO_Cache_XcacheTest extends PHPUnit_Framework_TestCase{
	public function testRun(){
		$res = extension_loaded('xcache');
		//$this->assertTrue($res,'XCACHE extension must be loaded');
		
		if($res){
			$test = new SOSO_Cache_Xcache();
			$val = array('name'=>'xcache_test','val'=>'soso_cache_xcache');
			$key = 'hello';
			
			$test->write($key,$val);
			
			$data = $test->read($key);
			$this->assertSame($val,$data);
			
			$test->write($key,'OtherNewValue');
			$this->assertNotSame($val,$test->read($key));
			
			$new_value = 'xcache-test';
			$test->write($key,$new_value);
			$this->assertEquals($new_value, $test->read($key));
			
			$this->assertTrue($test->isCached($key));
			
			$test->delete($key);
			$this->assertFalse($test->isCached($key));
			$this->assertNull($test->read($key));
			
		}else{
			$this->markTestSkipped('The XCache extension is no available.');
		}
	}	
	public function testFlush(){
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