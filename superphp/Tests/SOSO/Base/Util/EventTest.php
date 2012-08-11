<?php 
/**
 * @author : ballqiu (2012-3-26)
 * @version v 1.1 2009-12-17
 * @package SOSO_Base_Util
 */
 
class DB_For_Test {
    public $insertNum = 0;
    public $selectNum = 0;
    public $updateNum = 0;
    
    public function insert($nums, $options) {
        if (!is_array($nums)) {
            $this->insertNum = $this->insertNum + $nums;
        }
        else {
            foreach ($nums as $n) {
                $this->insertNum += $n;
            }
        }
        
        if ($options) {
            foreach ($options as $n) {
                $this->insertNum += $n;
            }
        }
    }
    
    public function select($num = 0) {
        $this->selectNum++;
    }
    
    public function update($num = 0) {
        $this->updateNum++;
    }
}

class SOSO_Base_Util_Event_ForTest extends SOSO_Base_Util_Event {
    public function getlisteners() {
        return $this->listeners;
    }
    
    public function getName() {
        return $this->name;
    }
    
    public function getObj() {
        return $this->obj;
    }
}

class SOSO_Base_Util_EventTest extends PHPUnit_Framework_TestCase {
    protected $_event = null;
    protected $_eventName = null;
    protected $_observable = null;
    
    public function setUp() {
        $this->_observable = new DB_For_Test();
        $this->_eventName = 'db';
        $this->_event = new SOSO_Base_Util_Event_ForTest($this->_observable, $this->_eventName);
    }
    
    public function testConstruct() {
        $this->assertEquals($this->_eventName, $this->_event->getName());
        $this->assertSame($this->_observable, $this->_event->getObj());
    }
    
    public function testAddListener() {
        $this->_event->addListener("insert", $this->_observable);
        $this->_event->addListener("select", $this->_observable);
        
        $listeners = $this->_event->getlisteners();
        $this->assertEquals(2, count($listeners));
        $oneListener = $listeners[0];
        $this->assertEquals("insert", $oneListener['fn']);
        $this->assertEquals("insert", $oneListener['fireFn']);
        $this->assertEquals($this->_event->getObj(), $oneListener['scope']);
        
        $oneListener = $listeners[1];
        $this->assertEquals("select", $oneListener['fn']);
        $this->assertEquals("select", $oneListener['fireFn']);
        $this->assertEquals($this->_event->getObj(), $oneListener['scope']);
        
        //var_dump($this->_event->listeners);
    }
    
    //scope不为空时
    public function testFindListener1() {
        $this->_event->addListener("insert", $this->_observable);
        $this->_event->addListener("select", $this->_observable);
        
        //找不到
        $res = $this->_event->findListener("insert", new SOSO_Base_Util_Event_ForTest());
        $this->assertEquals(-1, $res);
        $res = $this->_event->findListener("update", $this->_observable);
        $this->assertEquals(-1, $res);
        
        //找到
        $res = $this->_event->findListener("select", $this->_observable);
        $listeners = $this->_event->getlisteners();
        $this->assertEquals(1, $res);
        
        $oneListener = $listeners[$res];
        $this->assertEquals('select', $oneListener['fn']);
    }
    
    //scope为空 时
    public function testFindListener2() {
        $this->_event->addListener("phpinfo");
        $res = $this->_event->findListener("phpinfo");
        //var_dump($res);
        //$this->assertEquals(0, $res);
    }
    
    public function testRemoveListener() {
        $this->_event->addListener("insert", $this->_observable);
        $this->_event->addListener("select", $this->_observable);
        $listeners = $this->_event->getlisteners();
        $this->assertEquals(2, count($listeners));
        
        //scope错误的情况
        $res = $this->_event->removeListener("select", new SOSO_Base_Util_Event_ForTest());
        $this->assertFalse($res);
        $listeners = $this->_event->getlisteners();
        $this->assertEquals(2, count($listeners));
        
        //正常remove
        $res = $this->_event->removeListener("select", $this->_observable);
        $this->assertTrue($res);
        $listeners = $this->_event->getlisteners();
        $this->assertEquals(1, count($listeners));
    }
    
    public function testClearListeners() {
        $this->_event->addListener("insert", $this->_observable);
        $listeners = $this->_event->getlisteners();
        $this->assertEquals(1, count($listeners));
        
        $this->_event->clearListeners();
        $listeners = $this->_event->getlisteners();
        $this->assertEquals(0, count($listeners));
        $this->assertTrue(is_array($listeners));
    }
    
    //无$options
    public function testFire1() {
        $this->_event->addListener("insert", $this->_observable);
        $this->_event->addListener("select", $this->_observable);
        $this->_event->fire(array(2));
        
        $this->assertEquals(1, $this->_observable->selectNum);
        $this->assertEquals(2, $this->_observable->insertNum);
        $this->assertEquals(0, $this->_observable->updateNum);
        
        $this->_event->fire(array(3));
        $this->assertEquals(2, $this->_observable->selectNum);
        $this->assertEquals(5, $this->_observable->insertNum);
        $this->assertEquals(0, $this->_observable->updateNum);
    }
    
    //有$options
    public function testFire2() {
        $options = array(9);
        $this->_event->addListener("insert", $this->_observable, $options);
        //var_dump($this->_event->getlisteners());
        $this->_event->fire(array(3));
        $this->assertEquals(12, $this->_observable->insertNum);
    }
    
    public function testHasLisenter() {
        $res = $this->_event->hasListener();
        $this->assertFalse($res);
        
        $this->_event->addListener("insert", $this->_observable);
        $res = $this->_event->hasListener();
        $this->assertTrue($res);
    }
}
