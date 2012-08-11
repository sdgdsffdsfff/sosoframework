<?php
/**
* 测试脚本，测试用例在 Page 目录下
* @author : moonzhang (2009-12-14)
* @version v 1.1 2009-12-14
* @package SOSO_Util
*/
require_once("SOSO/Util/Util.php");
class SOSO_Util_UtilTest extends PHPUnit_Framework_TestCase{
	const SOME_CONST = 'some';
	
	public function testGetIP(){
		
	}
	public function testGetHost(){
		//do test
	}
	public function testRedirect(){
		//do test
	}
	public function testGo2info(){
		//do test
	}
	public function testRandomWeighted(){
		//do test
	}
	public function testByteFormat(){
		$prefix_arr = array("B", "K", "M", "G", "T",'P');
		$bytes = 1;
		$step = 1024;
		for($i=0,$len=count($prefix_arr);$i<$len;$i++){
			$res = soso_util_util::byte_format($bytes*pow($step,$i));
			$this->assertEquals($res, '1'.$prefix_arr[$i]);
		}
	}
	
	public function testStripslashesDeep(){
		$str = "hello\\world";
		$string = SOSO_Util_Util::stripslashesDeep($str);
		$this->assertEquals('helloworld', $string);
		
		$array = array("hello\\world",'test\\ed',array("sub\\client"));
		$base  = array("helloworld",'tested',array("subclient"));
		$striped = SOSO_Util_Util::stripslashesDeep($array);
		$this->assertSame($base, $striped);
		
	}
	
	public function testArrayDeepMerge(){
		$array1 = array('name'=>'moonzhang',array('hello'=>'kitty'));
		$array2 = array('name'=>'zhangyong',array('hello'=>'world','word'=>'welcome'));
		$array3 = array('text'=>'message','name'=>'燕山大酒店',array('hello'=>'moon'),array('hello'=>'what'));
		$result = SOSO_Util_Util::arrayDeepMerge($array1,$array2);
		
		$this->assertEquals($array2['name'], $result['name']);
		$this->assertEquals($array2[0]['hello'], $result[0]['hello']);
		$this->assertEquals($array2[0]['word'], $result[0]['word']);
		
		$result2 = SOSO_Util_Util::arrayDeepMerge($array1,$array2,$array3);
		
		$this->assertEquals($array3['name'], $result2['name']);
		$this->assertEquals($array3[0]['hello'], $result2[0]['hello']);
	}
	
	public function provider(){
		$arr1 = array(array('id'=>'100','name'=>'test_100'),
							array('id'=>'101','name'=>'test_101'),
							array('id'=>'102','name'=>'test_102'),
							array('id'=>'103','name'=>'test_103'),
							array('id'=>'104','name'=>'test_104')
		);
		return array($arr1);
//		$arr2 = array(array('id'=>'1000','name'=>'test_1000'),
//							array('id'=>'1001','name'=>'test_1001'),
//							array('id'=>'1002','name'=>'test_1002'),
//							array('id'=>'1003','name'=>'test_1003'),
//							array('id'=>'1004','name'=>'test_1004')
//		);
//		return array($arr1,$arr2);
	}
	
	public function testArray2String(){
		$arr1 = array(array('id'=>'100','name'=>'test_100'),
							array('id'=>'101','name'=>'test_101'),
							array('id'=>'102','name'=>'test_102'),
							array('id'=>'103','name'=>'test_103'),
							array('id'=>'104','name'=>'test_104')
		);
		
		$string = soso_util_util::Array2String($arr1, 'id');
		$this->assertStringStartsWith("'100'", $string);
		$this->assertStringEndsWith("'104'",$string);
	}
	
	public function testArray2Array(){
		$arr1 = array(
							array('id'=>'100','name'=>'test_100'),
							array('id'=>'101','name'=>'test_101'),
							array('id'=>'102','name'=>'test_102'),
							array('id'=>'103','name'=>'test_103'),
							array('id'=>'104','name'=>'test_104')
		);
		$array = soso_util_Util::Array2Array($arr1);
		$this->assertArrayHasKey('id', $array);
		$this->assertArrayHasKey('name', $array);
		$this->assertEquals(count($arr1), count($array['id']));
		$this->assertEquals(count($arr1), count($array['name']));
	}
	
	public function testArray2Hash(){
		$arr1 = array(
							array('id'=>'100','name'=>'test_100'),
							array('id'=>'101','name'=>'test_101'),
							array('id'=>'102','name'=>'test_102'),
							array('id'=>'103','name'=>'test_103'),
							array('id'=>'104','name'=>'test_104')
		);
		
		$array = soso_util_Util::Array2hash($arr1,'id');
		$this->assertEquals(count($arr1), count($array));
		$this->assertArrayHasKey('100', $array);
		$this->assertEquals('test_100', $array[100]);
		$this->assertEquals('test_101', $array[101]);
		$this->assertEquals('test_102', $array[102]);
		$this->assertEquals('test_103', $array[103]);
		$this->assertEquals('test_104', $array[104]);
		
		$array = soso_util_Util::Array2hash($arr1,'name');
		$this->assertEquals(count($arr1), count($array));
		$this->assertArrayHasKey('test_100', $array);
		$this->assertEquals('100', $array['test_100']);
		$this->assertEquals('101', $array['test_101']);
		$this->assertEquals('102', $array['test_102']);
		$this->assertEquals('103', $array['test_103']);
		$this->assertEquals('104', $array['test_104']);
	}
	public function testArray2Group(){
		//do test
	}
	public function testJOIN(){
		$arr1 = array(
							array('id'=>'100','name'=>'test_100'),
							array('id'=>'101','name'=>'test_101'),
							array('id'=>'102','name'=>'test_102'),
							array('id'=>'103','name'=>'test_103'),
							array('id'=>'104','name'=>'test_104')
		);
		
		$arr2 = array(
							array('r_id'=>100,'department'=>'spt','id'=>2000),
							array('r_id'=>101,'department'=>'spt1','id'=>2001),
							array('r_id'=>102,'department'=>'spt2','id'=>2002),
							array('r_id'=>103,'department'=>'spt3','id'=>2003),
							array('r_id'=>104,'department'=>'spt4','id'=>2004)
		);
		
		$joined = SOSO_Util_Util::JOIN($arr1, $arr2, array('id','r_id'));
		
		$array = soso_util_Util::Array2hash($arr1,'name');
		$this->assertEquals(count($arr1), count($joined));
		
		$this->assertEquals('spt', $joined[0]['department']);
		$this->assertEquals('spt1', $joined[1]['department']);
		$this->assertEquals('spt2', $joined[2]['department']);
		$this->assertEquals('spt3', $joined[3]['department']);
		$this->assertEquals('spt4', $joined[4]['department']);
		
		$this->assertEquals('100', $joined[0]['id']);
		$this->assertEquals('101', $joined[1]['id']);
		$this->assertEquals('102', $joined[2]['id']);
		$this->assertEquals('103', $joined[3]['id']);
		$this->assertEquals('104', $joined[4]['id']);
		
		$joined = SOSO_Util_Util::JOIN($arr1, $arr2, array('id','r_id'),true);
		$this->assertEquals('2000', $joined[0]['id']);
		$this->assertEquals('2001', $joined[1]['id']);
		$this->assertEquals('2002', $joined[2]['id']);
		$this->assertEquals('2003', $joined[3]['id']);
		$this->assertEquals('2004', $joined[4]['id']);
	}
	public function testMultiSubstr(){
		//SOSO_Util_Util::multi_substr();
	}
	public function testGetClassConstant(){
		$val = SOSO_Util_Util::getClassConstant(__CLASS__,'SOME_CONST');
		$this->assertEquals(self::SOME_CONST, $val);
	}
	public function testMagicName(){
		$str = 'hello_world';
		$string = SOSO_Util_Util::magicName($str);
		$this->assertStringStartsWith('m', $string);
		$this->assertEquals('mHelloWorld', $string);
		
		$string = SOSO_Util_Util::magicName('hello__world');
		$this->assertStringStartsWith('m', $string);
		$this->assertEquals('mHelloWorld', $string);
	}
	public function testNocacheHeaders(){
		SOSO_Util_Util::cache_javascript_headers();
	}
	public function testApacheModLoaded(){
		//do test
	}
	public function testUrlIsAccessableViaSsl(){
		//do test
	}
	public function testObEndFlushAll(){
		SOSO_Util_Util::ob_end_flush_all();
		$this->assertEquals(0, ob_get_level());
		ob_start();
		$this->assertEquals(1, ob_get_level());
		
		SOSO_Util_Util::ob_end_flush_all();
		$this->assertEquals(0, ob_get_level());
	}
	public function testCacheJavascriptHeaders(){
		//do test
	}
	public function testGetStatusHeaderDesc(){
		$desc = SOSO_Util_Util::get_status_header_desc(200);
		$this->assertEquals('OK', $desc);
		
		$desc = SOSO_Util_Util::get_status_header_desc(404);
		$this->assertEquals('Not Found', $desc);
	}
	public function testRemoteFopen(){
		//do test
	}
//	public function testStatusHeader(){
//		$status = soso_util_Util::status_header(200);
//		$list = headers_list();
//		print_r($list);
//	}
		
}