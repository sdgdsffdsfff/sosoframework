<?php
/**
* 测试脚本，测试用例在 Page 目录下
* @author : moonzhang (2009-12-13)
* @version v 1.1 2009-12-13
* @package SOSO_Filter
*/

require_once("SOSO/Filter/Cache.php");
class SOSO_Filter_CacheTest extends PHPUnit_Framework_TestCase{
	public $mCacheHandle;
	public $mCacheTime;
		
	public function testCache(){
		$cache = new SOSO_Filter_Cache();
		//$this->assertType('SOSO_Filter_Abstract',$cache);
		$this->assertObjectHasAttribute('mCacheTime', $cache);
		$this->assertObjectHasAttribute('mCacheHandle', $cache);
		
	}
	
	public function testDoPreProcessing(){
		//do test
	}
	public function testDoPostProcessing(){
		//do test
	}
	public function testSetHeader(){
		//do test
	}
		
}