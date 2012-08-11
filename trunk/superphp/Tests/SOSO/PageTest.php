<?php
/**
* 测试脚本，测试用例在 Page 目录下
* @author : moonzhang (2010-01-01)
* @version v 1.1 2010-01-01
* @package SOSO
*/

require_once("SOSO/View/Abstract.php");
require_once("SOSO/View/Exception.php");
require_once("SOSO/View/Page.php");
require_once("Smarty/Smarty.class.php");
class SOSO_PageTest extends PHPUnit_Framework_TestCase{
	public $mDefault;
	public $mTypeArray;
	public $mType;
	public $instance;
	public $mCurrentUser;
	public $mGET;
	public $mPOST;
	public $mRequest;
	public $mAppID;
	
	public function setUp(){
		$this->instance = new SOSO_View_Page();
		$this->instance->initTemplate();
	}
	
	public function tearDown(){
		$this->instance->__destruct();
		$this->instance = null;
	}
	public function testConstruct(){
//		$this->assertType('SOSO_View_Abstract', $this->instance);
//		$this->assertType('SOSO_Interface_Runnable',$this->instance);
	}
	
	public function testInitTemplate(){
		//$this->assertType('Smarty',$this->instance->instance);
		$tTplPath = SOSO_Frameworks_Config::getSystemPath('template');
		$this->assertEquals($tTplPath, $this->instance->instance->template_dir);
	}

	public function testShowMessage(){
		//do test
	}
	public function testRun(){
		//do test
	}
	public function testIsLogin(){
		//do test
	}
	public function testRedirect(){
		//do test
	}
	public function testLogin(){
		//do test
	}
	public function testLogout(){
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