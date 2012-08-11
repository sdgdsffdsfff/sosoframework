<?php
/**
* 测试脚本，测试用例在 Page 目录下
* @author : moonzhang (2009-11-21)
* @version v 1.1 2009-11-21
* @package SOSO
*/

require_once("SOSO/Filter.php");
class SOSO_FilterTest extends PHPUnit_Framework_TestCase{
		
	public function testRun(){
		$cache = SOSO_Filter::getInstance();
		//$this->assertType('SOSO_Filter_Abstract',$cache);
	}
	public function testGetInstance(){
		//do test
	}
	public function testGetFilterConfig(){
		//do test
	}
	public function testDoFilter(){
		//do test
	}
	public function testDoPreProcessing(){
		//do test
	}
	public function testDoPostProcessing(){
		//do test
	}
		
};