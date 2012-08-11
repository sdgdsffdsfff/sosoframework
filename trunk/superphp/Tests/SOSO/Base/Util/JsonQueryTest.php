<?php 
/**
 * 测试脚本，测试用例在 Page 目录下
 * @author : ballqiu (2012-3-22)
 * @version v 1.1 2009-11-11
 * @package SOSO_Base_Util
 */
 
class SOSO_Base_Util_JsonQueryTest extends PHPUnit_Framework_TestCase {
    //key的选择
    public function testSelect() {
        $data = array('name'=>array('first'=>'qiu',
                'mid'=>'ming',
                'last'=>'hua'),
                'age'=>30,
                'sex'=>'male',
                'other'=>array('k'=>'v'));
        $data = json_decode(json_encode($data));
        
        $res = SOSO_Base_Util_JsonQuery::select('[name]', $data);
        //var_dump($res);
        $res = $res[0];
        $this->assertEquals('qiu', $res->first);
        $this->assertEquals('hua', $res->last);
        
        $res = SOSO_Base_Util_JsonQuery::select('[age]', $data);
        $this->assertEmpty($res);
        
        $res = SOSO_Base_Util_JsonQuery::select('[name,other]', $data);
        //var_dump($res);
        $name = $res[0];
        $other = $res[1];
        $this->assertEquals('hua', $name->last);
        $this->assertEquals('v', $other->k);
    }
    
    //NUMERIC的选择
    public function testSelect2() {
        $data = array(array(1,
                2,
                3),
                array(4,
                5,
                6),
                array(7,
                8,
                9));
        $data = json_decode(json_encode($data));
        $res = SOSO_Base_Util_JsonQuery::select("[NUMERIC]", $data);
        $this->assertEquals($data, $res);
    }
    
    //混合
    public function testSelect3() {
        $data = array('k11'=>array(array('k21'=>array(1,
                2,
                3)),
                array('k22'=>array(4,
                5)),
                array('k23'=>array(6,
                7))),
                'k12'=>array());
        $data = json_decode(json_encode($data));
        
        $res = SOSO_Base_Util_JsonQuery::select("[k11][NUMERIC][k22]", $data);
        //var_dump($res);
        $this->assertEquals(array(array(4,5)), $res);
    }
    
	public function testSelect4(){
		$root = null;
		$res = SOSO_Base_Util_JsonQuery::select("[k11][NUMERIC][k22]", $root);
        //var_dump($res);
        $this->assertEquals(array(), $res);
	}
	
    public function testExpand() {
        //do test
    }
    
    public function testSelectValue1() {
        $data = array('name'=>array('first'=>'qiu',
                'mid'=>'ming',
                'last'=>'hua'),
                'age'=>30,
                'sex'=>'male',
                'other'=>array('k'=>'v'));
        
        $data = json_decode(json_encode($data));
        $res = SOSO_Base_Util_JsonQuery::selectValue('[name][first]', $data);
    }
    
    public function testSelectValue2() {
        $data = array('k11'=>array(array('k21'=>array('x1',
                'x2',
                'x3')),
                array('k22'=>array(4,
                5)),
                array('k23'=>array(6,
                7))),
                'k12'=>array());
        
        $data = json_decode(json_encode($data));
        $res = SOSO_Base_Util_JsonQuery::selectValue('[k11][0][k21][1]', $data);
		//var_dump($res);
        $this->assertEquals('x2', $res);
			
		$res = SOSO_Base_Util_JsonQuery::selectValue('[k11][0][k21][8]', $data, 'default');
        $this->assertEquals('default', $res);
    }
}
