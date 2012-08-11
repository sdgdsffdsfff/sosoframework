<?php
/**
* 测试脚本，测试用例在 Page 目录下
* @author : moonzhang (2009-11-13)
* @version v 1.1 2009-11-13
* @package SOSO_Filter
*/


require_once("SOSO/Filter/StorageCookie.php");
class SOSO_Filter_StorageCookieTest extends PHPUnit_Framework_TestCase{
	public $mCookieName;
		
	public function testRun(){
		$cache = new SOSO_Filter_StorageCookie();
		//$this->assertType('SOSO_Filter_Abstract',$cache);
		
		$this->assertObjectHasAttribute('mCookieName', $cache);
		
		$context = SOSO_Frameworks_Context::getInstance();
		$cache->doPreProcessing($context);
		$cache->doPostProcessing($context);
	}
//	public function testDoPreProcessing(){
//		//do test
//	}
//	public function testDoPostProcessing(){
//		//do test
//	}
//	public function testSetPartCookie(){
//		//do test
//	}
		
}