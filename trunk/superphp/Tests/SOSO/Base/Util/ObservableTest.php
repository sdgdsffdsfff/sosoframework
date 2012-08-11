<?php 
/**
 * @author : ballqiu (2012-3-27)
 * @version v 1.1 2009-11-24
 * @package SOSO_Base_Util
 */
 
class SOSO_Base_Util_Observable_ForTest extends SOSO_Base_Util_Observable {
    public function getEvents() {
        return $this->events;
    }
    
    public function getSuspended() {
        return $this->eventsSuspended;
    }
}

class myTest {
    protected $_num = 0;
    public function add() {
        $this->_num++;
    }
    public function sub() {
        $this->_num--;
    }
    public function get() {
        return $this->_num;
    }
}

class SOSO_Base_Util_ObservableTest extends PHPUnit_Framework_TestCase {
    public $eventsSuspended;
    
    protected $obj = null;
    public function setUp() {
        $this->obj = new SOSO_Base_Util_Observable_ForTest();
    }
    
    public function testConstruct() {
        //do test
    }
    
    public function testFireEvent() {
        $t = new myTest();
        $eName1 = 'f_add';
        $fn1 = 'add';
        $eName2 = 'f_sub';
        $fn2 = 'sub';
        
        $this->obj->on($eName1, $fn1, $t)->on($eName2, $fn2, $t);
        $this->obj->fireEvent($eName1);
        $this->assertEquals(1, $t->get());
        
        //测试暂停的情况
        $this->obj->suspendEvents();
        $this->obj->fireEvent($eName1);
        $this->assertEquals(1, $t->get());
        
        //测试继续的情况
        $this->obj->resumeEvents();
        $this->obj->fireEvent($eName1);
        $this->assertEquals(2, $t->get());
		
		//解除监听的情况
        $this->obj->un($eName1, $fn1, $t);
        $this->obj->fireEvent($eName1);
        $this->assertEquals(2, $t->get());
    }
    
    //函数不可执行
    public function testOn1() {
        $eventName = 'test';
        $fn = 'CannotRun';
        try {
            $this->obj->on($eventName, $fn);
        }
        catch(Exception $e) {
            $this->assertEquals('listener($scope->$fn) is not executable!?', $e->getMessage());
        }
    }
    
    //全局可执行函数
    public function testOn2() {
        $eventName = 'test';
        $fn = 'getcwd';
        $this->obj->on($eventName, $fn);
        
        $events = $this->obj->getEvents();
        $e = $events[$eventName];
        $this->assertTrue($e instanceof SOSO_Base_Util_Event);
        //$res = var_export($e);
        //var_dump($res);
        //$this->assertEquals($eventName, $e->name);
        
        //$l = $e->listeners[0];
        //$this->assertNull($l);
    }
    
    public function testRemoveListener() {
        $eventName = 'test';
        $fn = 'getcwd';
        $this->obj->on($eventName, $fn);
        
        $events = $this->obj->getEvents();
        $event = $events[$eventName];
        $res = $event->hasListener();
        $this->assertTrue($res);
        
        $this->obj->removeListener($eventName, $fn);
        $res = $event->hasListener();
        $this->assertFalse($res);
    }
    
    public function testPurgeListeners() {
        $eventName1 = 'test1';
        $fn = 'getcwd';
        $this->obj->on($eventName1, $fn);
        $eventName2 = 'test2';
        $fn = 'phpinfo';
        $this->obj->on($eventName2, $fn);
        
        $events = $this->obj->getEvents();
        $this->assertEquals(2, count($events));
        $event1 = $events[$eventName1];
        $event2 = $events[$eventName2];
        //var_dump($events);
        
        $res = $event1->hasListener();
        $this->assertTrue($res);
        $res = $event2->hasListener();
        $this->assertTrue($res);
        
        $this->obj->purgeListeners();
        $res = $event1->hasListener();
        $this->assertFalse($res);
        
        $res = $event2->hasListener();
        $this->assertFalse($res);
    }
    
    //数组形式
    public function testAddEvents1() {
        $this->obj->addEvents(array('clear','add','replace','remove'));
        $events = $this->obj->getEvents();
        $this->assertEquals(4, count($events));
        $this->assertTrue($events['remove']);
        //var_dump($events);
    }
    
    public function testAddEvents2() {
        $this->obj->addEvents('clear', 'add', 'replace', 'remove');
        $events = $this->obj->getEvents();
        $this->assertEquals(4, count($events));
        $this->assertTrue($events['add']);
        //var_dump($events);
    }
    
	 public function testAddEvents3() {
        $this->obj->addEvents(array('e1'=>'clear', 'e2'=>'add'));
        $events = $this->obj->getEvents();
        $this->assertEquals(2, count($events));
        $this->assertEquals('clear', $events['e1']);
        //var_dump($events);
    }
	
    public function testSuspendEvents() {
        $this->obj->suspendEvents();
        $status = $this->obj->getSuspended();
        $this->assertTrue($status);
    }
    
    public function testResumeEvents() {
        $this->obj->resumeEvents();
        $status = $this->obj->getSuspended();
        $this->assertFalse($status);
    }
    
    public function testHasListener() {
        $eventName = 'test';
        $fn = 'getcwd';
        $this->obj->on($eventName, $fn);
        
        $res = $this->obj->hasListener($eventName);
        $this->assertTrue($res);
        
        $res = $this->obj->hasListener('otherName');
        $this->assertFalse($res);
    }
}
