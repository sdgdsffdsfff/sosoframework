<?php
/**
* 测试脚本，测试用例在 Page 目录下
* @author : moonzhang (2010-02-07)
* @version v 1.1 2010-02-07
* @package SOSO_Util
*/

require_once("SOSO/Util/String.php");
class SOSO_Util_StringTest extends PHPUnit_Framework_TestCase{
		
	public function testDetectUTF8(){
		$txt = '中华人民共和国';
		$res = SOSO_Util_String::isUTF8($txt);
		$this->assertFalse($res);
		
		$txt = iconv('gbk','utf-8',$txt);
		$res = SOSO_Util_String::isUTF8($txt);
		$this->assertTrue($res);
		
		$this->assertTrue(SOSO_Util_String::isUTF8('hello,world'));
		
	}
	
	public function testHtmlspecialcharsUni(){
		
	}
	public function testParseQuery(){
		//do test
	}
	public function testFilterParam(){
		//do test
	}
	public function testGetDomain(){
		//do test
	}
	public function testStrip(){
		//do test
	}
	public function testRemove(){
		//do test
	}
	public function testChop(){
		//do test
	}
	public function testLeft(){
		$str = SOSO_Util_String::left('abcefg', 3);
		$this->assertEquals('abc', $str);
	}
	public function testRandString(){
		$str = SOSO_Util_String::randString(5,'d');
		$this->assertTrue((bool)preg_match("#\d+#",$str));
		
		
	}
	public function testMoney2cn(){
		$str = SOSO_Util_String::money2cn(1024);
		$this->assertEquals('壹仟零贰拾肆元整',$str);
		
		
		$str = SOSO_Util_String::money2cn('1024.24');
		$this->assertEquals('壹仟零贰拾肆元贰角肆分整',$str);
	}
	public function testTruncate(){
		//do test
	}
	public function testPageUrls(){
		//do test
	}
	public function testSubstr(){
		//do test
	}
	public function testStrlen(){
		//do test
	}
	public function testEncode(){
		//do test
	}
	public function testDecode(){
		//do test
	}
	public function testUnicode2any(){
		//do test
	}
	public function testAny2unicode(){
		//do test
	}
	public function testUnescape(){
		//do test
	}
	public function testCode2utf(){
		//do test
	}
	public function testIsUTF8(){
		//do test
	}
	public function testUtf8RawUrlDecode(){
		//do test
	}
		
}