<?php
/**
* 测试脚本，测试用例在 Page 目录下
* @author : moonzhang (2009-11-20)
* @version v 1.1 2009-11-20
* @package SOSO_Filter
*/

require_once("SOSO/Filter/StorageSession.php");
class SOSO_Filter_StorageSessionTest extends PHPUnit_Framework_TestCase{
	
	public function testRun(){
		$cache = new SOSO_Filter_StorageSession();
		$context = SOSO_Frameworks_Context::getInstance();
		
		//$this->assertType('SOSO_Filter_Abstract',$cache);
		session_start();
		$context->mStorage = '';
		$cache->doPreProcessing($context);
		$this->assertObjectHasAttribute('mStorage', $context);
		
		
		$_SESSION['Context'] = '';
		
		$cache->doPreProcessing($context);
		$this->assertEquals('', $context->mStorage);
		
		$context->mStorage = 'new';
		
		$cache->doPostProcessing($context);
		$this->assertEquals('new', $_SESSION['Context']);
		
		$_SESSION['Context'] = array('name'=>'filter_test');
		$cache->doPreProcessing($context);
		$this->assertSame($_SESSION['Context'],$context->mStorage);
		$_SESSION['Context'] = '';
		$this->assertNotSame($_SESSION['Context'],$context->mStorage);
		$cache->doPostProcessing($context);
		$this->assertSame($context->mStorage,$_SESSION['Context']);
		
	}
	public function testDoPreProcessing(){
		//do test
	}
	public function testDoPostProcessing(){
		//do test
	}
		
}