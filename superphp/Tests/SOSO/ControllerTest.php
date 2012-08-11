<?php
/**
* 测试脚本，测试用例在 Page 目录下
* @author : moonzhang (2010-01-28)
* @version v 1.1 2010-01-28
* @package SOSO
*/

require_once("SOSO/Interface/Runnable.php");
require_once "SOSO/Filter/Abstract.php";

if(!class_exists('ControllerDummyFilter')){
	class ControllerDummyFilter extends SOSO_Filter_Abstract{
		function doPreProcessing(SOSO_Frameworks_Context $context){
			return true;
		}
		function doPostProcessing(SOSO_Frameworks_Context $context){
			
		}
	}
	
	class ControllerDummy implements SOSO_Interface_Runnable{
		const FILTERS = "ControllerDummyFilter";
		const CACHE_TIME = 3600;
		
		public function run(){
			echo  'dummy test';
		}
	}
}
class SOSO_ControllerTest extends PHPUnit_Framework_TestCase{
	public $mAppID;
	
	public function setUp(){
		$_SERVER['argc'] = 2;
		$_SERVER['argv'] = array(basename(__FILE__),'ControllerDummy');
	}
	public function testRun(){
		$controller = new SOSO_Controller();
		$context = SOSO_Frameworks_Context::getInstance();
		$this->assertSame(array('ControllerDummyFilter'), $context->get('filters'));
		$this->assertTrue(in_array('ControllerDummyFilter',$context->get('filters')));
		if (php_sapi_name() == 'cli')
			$this->assertEquals('ControllerDummy', $context->get('page_class'));
		else 
			$this->markTestSkipped('It\'s not Command-Line mode');
		$this->assertEquals(3600, $context->get('cacheTime'));
		$this->assertEquals('SOSO_Controller', strval($controller));
		
		
	}
	
	public function testDispatch(){
		$controller = new SOSO_Controller();
		if (ob_get_level() == 0) {
			ob_start();
		}
		
		$controller->dispatch();
		$tContent = ob_get_contents();
		
		$this->assertEquals('dummy test', $tContent);
	}
//	public function testDoSession(){
//		//do test
//	}
//	public function testDestruct(){
//		//do test
//	}
//	public function testToString(){
//		//do test
//	}
//	public function testToDom(){
//		//do test
//	}
		
}