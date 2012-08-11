<?php 
/**
 * 测试脚本，测试用例在 Page 目录下
 * @author : moonzhang (2009-12-17)
 * @version v 1.1 2009-12-17
 * @package SOSO_Frameworks
 */
 
class SOSO_Frameworks_ContextTest extends PHPUnit_Framework_TestCase {
    public $mCurrentUser;
    
    public function testGetInstance() {
        $reg = SOSO_Frameworks_Context::getInstance();
        //$this->assertType('SOSO_Frameworks_Context', $reg);
        $this->assertTrue($reg instanceof SOSO_Frameworks_Context);
        $this->assertObjectHasAttribute('mCurrentUser', $reg);
        $this->assertClassHasStaticAttribute('instance', 'SOSO_Frameworks_Context');
    }
    
    //add by ballqiu
    public function testToString() {
        $context = SOSO_Frameworks_Context::getInstance();
        $context->set('k1', 'v1');
        $context->set('k2', 'v2');
        $context->set('k3', 'v3');
        
        $res = sprintf("%s", $context);
        
        $expect = 'O:13:"ArrayIterator":3:{s:2:"k1";s:2:"v1";s:2:"k2";s:2:"v2";s:2:"k3";s:2:"v3";}';
        $this->assertEquals($expect, $res);
    }
    
    //change by ballqiu
    public function testReConfigure() {
        $context = SOSO_Frameworks_Context::getInstance();
        $context->set('originalKey', 'originalValue');
        $this->assertEquals('originalValue', $context->get('originalKey'));
        
        $context->reConfigure(array('newKey'=>'newValue'));
        $this->assertArrayHasKey('newKey', $context->getArrayCopy());
        $this->assertEquals('newValue', $context->get('newKey'));
        $this->assertNull($context->get('originalKey'));
    }
    
    public function testGet() {
        $context = SOSO_Frameworks_Context::getInstance();
        $res = $context->get('page_class');
        //$this->assertType('string',$res);
    }
    
    public function testSet() {
        $context = SOSO_Frameworks_Context::getInstance();
        $res = $context->set('context', 'context-test');
        $res = $context->get('context');
        
        $this->assertEquals('context-test', $res);
        $this->assertEquals('context-test', $context['context']);
    }
    
    public function testIsRegistered() {
        $context = SOSO_Frameworks_Context::getInstance();
        //$this->assertTrue($context->isRegistered('page_class'));
        $this->assertFalse($context->isRegistered('NotExistsKey'));
        
        $context->set('context', 'context-test');
        $this->assertTrue($context->isRegistered('context'));
    }
    
    public function testOffsetExists() {
        //do test
    }
    public function testConstruct() {
        //do test
    }
    public function testOffsetGet() {
        //do test
    }
    public function testOffsetSet() {
        //do test
    }
    public function testOffsetUnset() {
        //do test
    }
    public function testAppend() {
        //do test
    }
    public function testGetArrayCopy() {
        //do test
    }
    public function testCount() {
        //do test
    }
    public function testGetFlags() {
        //do test
    }
    public function testSetFlags() {
        //do test
    }
    public function testAsort() {
        //do test
    }
    public function testKsort() {
        //do test
    }
    public function testUasort() {
        //do test
    }
    public function testUksort() {
        //do test
    }
    public function testNatsort() {
        //do test
    }
    public function testNatcasesort() {
        //do test
    }
    public function testGetIterator() {
        //do test
    }
    public function testExchangeArray() {
        //do test
    }
    public function testSetIteratorClass() {
        //do test
    }
    public function testGetIteratorClass() {
        //do test
    }
    
}
