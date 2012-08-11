<?php
/**
* 测试脚本，测试用例在 Page 目录下
* @author : moonzhang (2010-02-04)
* @version v 1.1 2010-02-04
* @package SOSO_Filter
*/
require_once("SOSO/Filter/Session.php");
class SOSO_Filter_SessionTest extends PHPUnit_Framework_TestCase{
	
	public function testRun(){
		session_start();
		$cache = new SOSO_Filter_Session();
		$this->assertType('SOSO_Filter_Abstract',$cache);
		$context = SOSO_Frameworks_Context::getInstance();
		$this->assertNull($context->mCurrentUser);
		
		$cache->doPreProcessing($context);
		$this->assertNull($context->mCurrentUser);
		
		$_SESSION['currentUser'] = 'user';
		
		$cache->doPreProcessing($context);
		$this->assertEquals('user', $context->mCurrentUser);
		
		$cache->doPostProcessing($context);
		$this->assertEquals('user', $context->mCurrentUser);
	}		
}