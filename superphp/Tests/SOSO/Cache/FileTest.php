<?php
/**
* 测试脚本，测试用例在 Page 目录下
* @author : moonzhang (2009-12-16)
* @version v 1.1 2009-12-16
* @package SOSO_Cache
*/
require_once 'SOSO/Cache.php';
require_once 'SOSO/Cache/File.php';
class SOSO_Cache_FileTest extends PHPUnit_Framework_TestCase{
	public $mCache,
			$key = 'hello',
			$val = array('name'=>'file_test','val'=>'soso_cache_file');
	
	public function setUp(){
		$tConfig = array('cache_dir'=>'phpunit_test','auto_hash'=>true,'hash_level'=>1,'hash_dirname_len'=>1,'gc_probability'=>0,'cache_time'=>0);
		$this->mCache = new SOSO_Cache_File($tConfig);
		$this->mCache->write($this->key,$this->val);
	}
	
	public function testRun(){
		$tCacheDir = $this->mCache->getOption('cache_dir');
		if(!file_exists($tCacheDir) || !is_writable($tCacheDir)){
			$this->markTestSkipped('Cache directory ('.dirname($tCacheDir).' must be writable');
			return;
		}
		$test = $this->mCache;
		
		$this->assertTrue($test->getOption('use_encode'));
		
		$val = array('name'=>'file_test','val'=>'soso_cache_file');
		$key = 'hello';
		
		$res = $test->write($key,$val);
		$this->assertTrue($res);
			
		$tPath = $test->getHash($key);
		$tData = $this->getData($val,$test);
		
		$this->assertEquals($tData, file_get_contents($tPath.$key));
		$this->assertTrue($test->isCached($key));
		$this->assertFalse($test->isExpired($key));
		$this->assertEquals($key, $test->getKey());
		
		$data = $test->read($key);
		$this->assertSame($val,$data);
			
		$test->setOption('use_encode', false);
		
		$this->assertFalse($test->getOption('use_encode'));
		$test->write($key,'OtherNewValue');
		$this->assertNotSame($val,$test->read($key));
		$tData2 = $this->getData('OtherNewValue',$test);
		$this->assertEquals($tData2, file_get_contents($tPath.$key));
			
		$new_value = 'file-test';
		$test->write($key,$new_value);
		$this->assertEquals($new_value, $test->read($key));
			
		$this->assertTrue($test->isCached($key));
		
		
		
		$this->assertFileExists($tPath.$key);
			
		$test->delete($key);
		$this->assertFalse($test->isCached($key));
		$this->assertNull($test->read($key));
		
		$key2 = 'new';
		$val2 = 'new_value';
		$test->write($key2,$val2);
		$this->assertTrue($test->isCached($key2));
		$this->assertFalse($test->isExpired($key2));
		
		$test->flush();
		$this->assertFalse($test->isCached($key2));
		$this->assertTrue($test->isExpired($key2));
	}
	public function getData($val,SOSO_Cache $test){
		$expire = $test->calExpireTime($test->getOption('cache_time'));
		$tData = $expire."\n";
		
		if ($test->getOption('use_encode')){
			$tData .= base64_encode(serialize($val));
		}else{
			$tData .= serialize($val);
		}
		
		return $tData;
	}
	
	
	public function testRead(){
		$data = $this->mCache->read($this->key);
		$this->assertSame($this->val, $data);
	}
	public function testWrite(){
		$res = $this->mCache->write($this->key,$this->val);
		$this->assertTrue($res);
	}
	public function testGetHash(){
		$tPath = $this->mCache->getHash($this->key);
		$this->assertFileExists($tPath);
	}
	public function testIsCached(){
		$this->assertTrue($this->mCache->isCached($this->key));
	}
	public function testDelete(){
		$this->assertTrue($this->mCache->isCached($this->key));
		$this->mCache->delete($this->key);
		$this->assertFalse($this->mCache->isCached($this->key));
		$this->assertNull($this->mCache->read($this->key));
	}
	public function testIsExpired(){
		$this->assertFalse($this->mCache->isExpired($this->key));
	}
	public function testFlush(){
		$this->assertTrue($this->mCache->isCached($this->key));
		$this->mCache->flush();
		$this->assertFalse($this->mCache->isCached($this->key));
	}
	
	/*
	public function testToString(){
		$this->assertType('SOSO_Cache', $this->mCache);
	}
	*/
	
	public function testGetInstances(){
		//do test
	}
	
	public function testEncode(){
		$tmp = array("name"=>'tester');
		$tmp_data = $this->mCache->encode($tmp);
		$this->assertSame($tmp, $this->mCache->decode($tmp_data));
		
		$this->mCache->setOption('use_encode', false);
		
		$tmp_data = $this->mCache->encode($tmp);
		$this->assertSame($tmp, $this->mCache->decode($tmp_data));
	}
	public function testDecode(){
		$tmp = array("name"=>'tester');
		$tmp_data = $this->mCache->encode($tmp);
		$this->assertSame($tmp, $this->mCache->decode($tmp_data));
		
		$this->mCache->setOption('use_encode', false);
		
		$tmp_data = $this->mCache->encode($tmp);
		$this->assertSame($tmp, $this->mCache->decode($tmp_data));
	}
	public function testSetOption(){
		$this->assertTrue($this->mCache->getOption('use_encode'));
		
		$this->mCache->setOption('use_encode', false);
		
		$this->assertFalse($this->mCache->getOption('use_encode'));
	}
	public function testGetOption(){
		$this->assertTrue($this->mCache->getOption('Cacheing'));
	}
//	public function testGarbageCollection(){
//		
//	}
//	public function testCalExpireTime(){
//		//do test
//	}
		
}