<?php
/**
 * 测试脚本，测试用例在 Page 目录下
 * @author : moonzhang (2009-12-17)
 * @version v 1.1 2009-12-17configed
 * @package SOSO_Frameworks
 */

require_once 'SOSO/Frameworks/Config.php';

class SOSO_Frameworks_ConfigTest extends PHPUnit_Framework_TestCase{
	public $mAppID;
	//add by ball
	public function setUp(){
		SOSO_Frameworks_Config::initialize('web.xml-configTest');
	}

	//change by ball
	public function testRun(){
		$this->assertEquals(getcwd(), SOSO_Frameworks_Registry::getInstance()->get('root_path'));

		//$system = SOSO_Frameworks_Config::getSystemPath('system');
		//$this->assertSame(stat(getcwd()), stat($system));
		$this->assertStringStartsWith(SOSO_Frameworks_Config::document_root_path(), getcwd());
		
		$this->assertEquals('/usr/local/bin/php',SOSO_Frameworks_Config::getPath('phpbin'));
		$this->assertSame(stat(getcwd()), stat(SOSO_Frameworks_Config::getParam('system')));

		$res = SOSO_Frameworks_Config::getAllParams();
		$params = $res['param'];
		$this->assertEquals(2, count($params));
		$this->assertTrue(isset($params['name']));
		$this->assertEquals($params['value'], SOSO_Frameworks_Config::getConfigParam($params['name']));
	}

	//add by ball
	public function testGetConfigParam(){
		$res = SOSO_Frameworks_Config::getConfigParam('hello');
		$this->assertEquals('world', $res);
		$res = SOSO_Frameworks_Config::getConfigParam('hi');
		$this->assertEquals('pig', $res);
	}

	//add by ball
	public function testGetMode(){
		$mode = SOSO_Frameworks_Config::getMode();
		$this->assertEquals('debug',$mode);
	}

	//add by ball
	public function testGetPath(){
		$res = SOSO_Frameworks_Config::getPath();
		$this->assertEquals(array(), $res);
		
		$res = SOSO_Frameworks_Config::getPath('system');
		$this->assertEquals(4, count($res));
		$this->assertEquals('cache_path', $res[3]);

		$res = SOSO_Frameworks_Config::getPath('params');
		$this->assertEquals(2, count($res));
		$this->assertEquals('hello', $res[0]['name']);
		$this->assertEquals('world', $res[0]['value']);
		
		$res = SOSO_Frameworks_Config::getPath('params', 1);
		$this->assertTrue(isset($res['param']));
		$this->assertEquals('hi', $res['param']['name']);
		$this->assertEquals('pig', $res['param']['value']);
	}
	
	//add by ball web.xml为空的情况
	public function testGetPath2(){
		$registry = SOSO_Frameworks_Registry::getInstance();
		$registry->set('project', null);
		$res = SOSO_Frameworks_Config::getPath();
		$this->assertEquals(array(), $res);
	}
	
	public function testIsCached(){
		$cached = SOSO_Frameworks_Config::isCached();
		$this->assertFalse($cached);
	}
	
	public function testDocumentRootPath(){
		$res = SOSO_Frameworks_Config::document_root_path();
		$this->assertEquals(dirname(getcwd()), $res);
	}
	
	public function testGetSystemPath(){
		$res = SOSO_Frameworks_Config::getSystemPath();
		$this->assertEquals(getcwd(), $res);
		$res = SOSO_Frameworks_Config::getSystemPath('class');
		$this->assertEquals(getcwd().'/class_path', $res);
	}

	//	public function testInitialize(){
	//		//do test
	//	}
	//	public function testGetParam(){
	//		//do test
	//	}
	//	public function testGetAllParams(){
	//		//do test
	//	}
		//	public function testConstruct(){
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
