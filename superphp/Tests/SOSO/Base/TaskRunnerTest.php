<?php 
/**
 * @author : ballqiu (2012-04-12)
 * @version v 1.1 2010-02-01
 * @package SOSO_Base
 */
 
class SOSO_Base_TaskRunnerForTest extends SOSO_Base_TaskRunner {
    public function logPID() {
        return parent::logPID();
    }
    
    public function getPID() {
        return parent::getPID();
    }
    
    public function serverLimit() {
        return parent::serverLimit();
    }
    
    public function removePID($log) {
        return parent::removePID($log);
    }
    
    public function getTaskPool() {
        return parent::$mTaskPool;
    }
    
    public function getUserAppName() {
        return $this->mUseAppName;
    }
    
    public function getToken() {
        return parent::getToken();
    }
	
	public function limit($pScript, $pMaxThread=null){
		return parent::limit($pScript, $pMaxThread);
	}
}

class SOSO_Base_TaskRunnerTest extends PHPUnit_Framework_TestCase {
    protected $_obj = null;
    
    public function setUp() {
        $this->_obj = new SOSO_Base_TaskRunnerForTest();
    }
    
    public function tearDown() {
        if ($this->_obj) {
            $this->_obj->__destruct();
        }
    }
    
    public $Registry;
    
    public function testConstruct() {
        //do test
    }
    
    public function testInitialize() {
        //$this->_obj->initialize();构造函数中调了
        $config = SOSO_Base_TaskRunner::$Registry;
        $this->assertEquals('WEB-INF/entry.php ', $config['ENTRY_PATH']);
    }
    
    public function testLogPID() {
        $this->_obj->logPID();
        $tTemp = SOSO_Frameworks_Config::getSystemPath('temp');
        $tPIDPath = 'SOSO_Base_TaskRunnerForTest';
        $dir = $tTemp."/".$tPIDPath;
        //var_dump($tPIDPath);
        $this->assertTrue(file_exists($dir));
        
        $tSeed = $tPIDPath."/Worker_".$this->_obj->getPID();
        $tPath = $tTemp.'/'.$tSeed;
        //var_dump($tPath);
        $this->assertTrue(file_exists($tPath));
    }
    
    public function testServerLimit() {
        //$res = $this->_obj->serverLimit();
        //var_dump($res);
    }
    
    public function testRemovePID() {
        $f = '/tmp/ball';
        system("touch $f");
        $this->assertTrue(file_exists($f));
        
        $this->_obj->removePID($f);
        $this->assertFalse(file_exists($f));
    }
    
    public function testUseAppName() {
        $this->_obj->useAppName();
        $this->assertTrue($this->_obj->getUserAppName());
        
        $this->_obj->useAppName(false);
        $this->assertFalse($this->_obj->getUserAppName());
    }
    
    public function testGetToken() {
    	//__TASKRUNNER_KEY__ = 项目名
        $this->_obj->useAppName(true);
        $res = $this->_obj->getToken();
		$this->assertTrue(!!strpos($res, APP_NAME));
        //__TASKRUNNER_KEY__ = pid
        $this->_obj->useAppName(false);
        $res = $this->_obj->getToken();
		$pid = strval(getmypid());
		$this->assertTrue(!!strpos($res, $pid));
    }
    
	public function testLimit(){
		$script = 'test';
		$maxThread = 0;
		$res = $this->_obj->limit($script, $maxThread);
		$this->assertFalse($res);
		
		$maxThread = 10;
        $res = $this->_obj->limit($script, $maxThread);
		$this->assertTrue($res);
		
		//使用默认$maxThread
		$res = $this->_obj->limit($script);
        $this->assertTrue($res);
	}
	
    public function testDestruct() {
        //do test
    }
	
    public function testGetCommand() {
        //$pScript,$pParams=array(),$pBlocked=false,$pRedirect='/dev/null'
		$script = 'test';
		$params = array('name'=>'ball', 'age'=>30);
		$block = false;
		$redirect = '/tmp/log';
		$this->_obj->useAppName(true);
		
		$cmd = $this->_obj->getCommand($script);
		$expect = '/usr/local/bin/php WEB-INF/entry.php  test   __TASKRUNNER_KEY__=mytest >/dev/null &';
        $this->assertEquals($expect, $cmd);
		//var_dump($cmd);
		
		$cmd = $this->_obj->getCommand($script, $params, $block, $redirect);
		//var_dump($cmd);
		
		$block = true;
		$cmd = $this->_obj->getCommand($script, $params, $block, $redirect);
        //var_dump($cmd);
    }
	
    public function testGetCommand2() {
        //do test
    }
    public function testStartThread() {
        /*
         foreach($array as $options){
         $this->startThread($task,$options->params,$options->blocking,$options->log,$options->thread_limit);
         }*/
        
    }
    public function testStartThread2() {
        //do test
    }
    public function testSeed() {
        //do test
    }
    public function testFork() {
        //do test
    }
    public function testSetOptions() {
        //do test
    }
    public function testSetOption() {
        //do test
    }
    public function testSetBlocking() {
        //do test
    }
    public function testSetLogFile() {
        //do test
    }
    public function testSetThreadLimit() {
        //do test
    }
    public function testSetTask() {
        //do test
    }
    public function testPushTask() {
        $task1 = array("WorkerServer1"=>array('thread_limit'=>10,
                'log'=>'worker.log',
                'blocking'=>false));
        $task2 = array("WorkerServer2"=>array('thread_limit'=>10,
                'log'=>'worker.log',
                'blocking'=>false));
        
        $this->_obj->pushTask($task1);
        $pool = $this->_obj->getTaskPool();
        //var_dump($pool);
        $task = (array) $pool['WorkerServer1'][0];
        
        $this->assertEquals(10, $task['thread_limit']);
        $this->assertFalse($task['blocking']);
        $this->assertEquals(1, count($pool));
        
        //再另一个任务，目前有bug
        $this->_obj->pushTask($task2, array(), false);
        $pool = $this->_obj->getTaskPool();
        $this->assertEquals(2, count($pool));
    }
    
    public function testAddTask() {
        //do test
    }
    public function testStart() {
        //do test
    }
    public function testStop() {
        //do test
    }
    public function testStopAll() {
        //do test
    }
    
}
