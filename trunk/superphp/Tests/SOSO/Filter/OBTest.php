<?php
/**
* 测试脚本，测试用例在 Page 目录下
* @author : moonzhang (2009-12-06)
* @version v 1.1 2009-12-06
* @package SOSO_Filter
*/

require_once("SOSO/Filter/OB.php");
class SOSO_Filter_OBTest extends PHPUnit_Framework_TestCase{
	public function testRun(){
		$cache = new SOSO_Filter_OB();
//		$this->assertType('SOSO_Filter_Abstract',$cache);
		
		$context = SOSO_Frameworks_Context::getInstance();
		$cache->doPreProcessing($context);
		$this->assertGreaterThan(0, ob_get_level());
		$content = ob_get_contents();
		
		$this->assertEquals(str_repeat(' ',255), $content);
		
		$cache->doPostProcessing($context);
		$this->assertEquals(0, ob_get_level());
	}
//	public function testDoPreProcessing(){
//		//do test
//	}
//	public function testDoPostProcessing(){
//		//do test
//	}
		
}