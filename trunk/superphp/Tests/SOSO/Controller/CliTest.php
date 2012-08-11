<?php
/**
* 测试脚本，测试用例在 Page 目录下
* @author : moonzhang (2010-02-10)
* @version v 1.1 2010-02-10
* @package SOSO_Controller
*/

require_once("SOSO/Interface/Runnable.php");
require_once "SOSO/Filter/Abstract.php";
require_once "SOSO/Controller/Cli.php";

class DummyFilter extends SOSO_Filter_Abstract{
	function doPreProcessing(SOSO_Frameworks_Context $context){
		return true;
	}
	function doPostProcessing(SOSO_Frameworks_Context $context){
		
	}
}

class Dummy implements SOSO_Interface_Runnable{
	const FILTERS = "DummyFilter";
	const CACHE_TIME = 3600;
	
	public function run(){
		echo  'dummy test';
	}
}
class SOSO_Controller_CliTest extends PHPUnit_Framework_TestCase {
	public $mAppID;
	
	public function setUp(){
		$_SERVER['argc'] = 2;
		$_SERVER['argv'] = array(basename(__FILE__),'Dummy','name=moonzhang');
	}
	
	public function testConstruct(){
		$controller = new SOSO_Controller_Cli();
		
		$this->assertEquals('Dummy', $controller->getClass());
		$this->assertSame(array('name'=>'moonzhang'), $_GET);
		$this->assertEquals('SOSO_Controller_Cli', strval($controller));
		
		if (ob_get_level() == 0) {
			ob_start();
		}
		
		$controller->dispatch();
		$tContent = ob_get_contents();
		
		$this->assertEquals('dummy test', $tContent);
	}
	public function testDispatch(){
		//do test
	}
	public function testGetClass(){
		//do test
	}
	public function testDestruct(){
		//do test
	}
	public function testToString(){
		//do test
	}
	public function testToDom(){
		//do test
	}
		
}