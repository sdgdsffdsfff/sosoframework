<?php 
/**
 * 测试脚本，测试用例在 Page 目录下
 * @author : moonzhang (2010-02-09)
 * @version v 1.1 2010-02-09
 * @package SOSO_Cache
 */
 
class SOSO_Cache_MemcacheTest extends PHPUnit_Framework_TestCase {
    protected $_host = '0.0.0.0';
    protected $_port1 = 1234;
    protected $_port2 = 1235;
    protected $_memObj = null;
    
    public function testRun() {
        $res = extension_loaded('memcache');
        if (!$res) {
            $this->markTestSkipped('The Memcache extension is not available.');
        }
    }
    
    public function setUp() {
        system('memcached -d -p 1234');
        system('memcached -d -p 1235');
        
        $option = array('host'=>$this->_host,
                'port'=>$this->_port1);
        $this->_memObj = new SOSO_Cache_Memcache($option);
    }
    
    public function testConstruct() {
        //setUp有了
    }
    
    public function testInitialize() {
        $option = array('servers'=>array(array('host'=>$this->_host,
                'port'=>$this->_port1),
                array('host'=>$this->_host,
                'port'=>$this->_port2)));
        
        $obj = new SOSO_Cache_Memcache();
        $obj->initialize($option);
        
        $res = $obj->getServers();
        //待assert
        //var_dump($res);
    }
    
    public function testAddServer1() {
        $obj = new SOSO_Cache_Memcache();
        $obj->addServer($this->_host, $this->_port1);
        $obj->addServer($this->_host, $this->_port2);
        
        $res = $obj->getServers();
        $expect = array("{$this->_host}:{$this->_port1}",
                "{$this->_host}:{$this->_port2}");
        $this->assertEquals($expect, $res);
    }
    
    public function testAddServer2() {
        $obj = new SOSO_Cache_Memcache();
        $obj->addServer(array(array($this->_host,$this->_port1),array($this->_host,$this->_port2)));
        
        $res = $obj->getServers();
        $expect = array("{$this->_host}:{$this->_port1}", "{$this->_host}:{$this->_port2}");
        $this->assertEquals($expect, $res);
    }
    
    public function testFlush() {
        $key = 'name';
        $value = 'ball';
        $this->_memObj->write($key, $value);
        
        $res = $this->_memObj->flush();
        $this->assertTrue($res);
        
        $res = $this->_memObj->read($key);
        $this->assertFalse($res);
    }
    
    public function testRead() {
        //testIsCached
    }
    
    public function testCount() {
        $key = 'name1';
        $value = 'ball';
        $this->_memObj->write($key, $value);
        
        $key = 'name2';
        $value = 'xuan';
        $this->_memObj->write($key, $value);
        
        $res = $this->_memObj->count();
        $this->assertEquals(2, $res);
    }
    
    public function testCall() {
        $method = 'no';
        $this->_memObj->$method();
    }
    
    public function testGetServers() {
        //addServer中一起测了
    }
    
    public function testGetKeys() {
        $key = 'name1';
        $value = 'ball';
        $this->_memObj->write($key, $value);
        
        $key = 'name2';
        $value = 'xuan';
        $this->_memObj->write($key, $value);
        
        $res = $this->_memObj->getKeys();
        $expect = array(0=>'name2',
                1=>'name1',);
        $this->assertEquals($expect, $res);
    }
    
    public function testWrite() {
        $key = 'name';
        $value = 'ball';
        
        $this->_memObj->write($key, $value);
        $res = $this->_memObj->isCached($key);
        $this->assertEquals($value, $res);
    }
    
    public function testIsCached() {
        $key = 'name';
        $value = 'ball';
        $this->_memObj->write($key, $value);
        
        $res = $this->_memObj->isCached($key);
        $this->assertEquals($value, $res);
        
        $this->_memObj->delete($key);
        $res = $this->_memObj->isCached($key);
        $this->assertFalse($res);
    }
    
    public function testDelete() {
        //testIsCached中测了
    }
    
    public function testIsExpired() {
        $key = 'name1';
        $value = 'ball';
        $this->_memObj->write($key, $value);
        
        $this->_memObj->write($key, $data);
        $res = $this->_memObj->isExpired($key);
        $this->assertFalse($res);
        
        $this->_memObj->delete($key);
        $expire = 2;
        $this->_memObj->write($key, $data, $expire);
        
        sleep($expire + 1);
        $res = $this->_memObj->isExpired($key);
        $this->assertTrue($res);
    }
    
    public function testGc() {
        $this->_memObj->gc();
    }
    
    public function testNext() {
        //testKey中测
    }
    public function testValid() {
        //do test
    }
    public function testKey() {
        $key1 = 'name1';
        $value1 = 'ball';
        $this->_memObj->write($key1, $value1);
        
        $key2 = 'name2';
        $value2 = 'xuan';
        $this->_memObj->write($key2, $value2);
        
        $r = $this->_memObj->getKeys();
        $res = $this->_memObj->key();
        $this->assertEquals($r[0], $res);
        
        $res = $this->_memObj->current();
        $this->assertEquals($value2, $res);
        
        $this->_memObj->next();
        $this->_memObj->rewind();
        $res = $this->_memObj->key();
        $this->assertEquals($r[0], $res);
        
        $res = $this->_memObj->valid();
        $this->assertTrue($res);
    }
    
    public function testCurrent() {
        //testKey中测
    }
    public function testRewind() {
        //testKey中测
    }
    public function testFactory() {
        //do test
    }
    public function testToString() {
        //do test
    }
    public function testGetInstances() {
        //do test
    }
    public function testDestruct() {
        //do test
    }
    public function testGetKey() {
        //do test
    }
    public function testSetCachingTime() {
        //do test
    }
    public function testSet() {
        //do test
    }
    public function testSetCaching() {
        //do test
    }
    
    public function testSetOption() {
        //do test
    }
    public function testGetOption() {
        //do test
    }
    public function testGarbageCollection() {
        //do test
    }
    public function testCalExpireTime() {
        //do test
    }
    
    public function tearDown() {
        $this->_memObj->flush();
    }
    
}

