<?php
/**
* 测试脚本，测试用例在 Page 目录下
* @author : moonzhang (2009-12-12)
* @version v 1.1 2009-12-12
* @package SOSO_Base_Data
*/

class SOSO_Base_Data_ConnectionTest extends PHPUnit_Framework_TestCase{
	public $timeout;
	public $try_times;
	public $duration;
	public $referrer;
	public $mContent;
	public $eventsSuspended;
	
    public function testRelayRequest(){
	    $connection=new SOSO_Base_Data_Connection(array('http_options'=>array(
            CURLOPT_TIMEOUT=>1,
            )));
        $connection->useProxyRelay(array(
        'relay_list'=>array('10.1.144.139:8080')
        ));
        $res=$connection->request('http://www.soso.com/');
        $this->assertTrue(!!preg_match('#an\.js#i',$res));
        
	}
    public function test(){
	    $connection=new SOSO_Base_Data_Connection();
        $res=$connection->request('http://10.130.74.17/css/style.css',array('http_options'=>array(
            CURLOPT_HTTPHEADER=>array('Host: msg.soso.com'),
            CURLOPT_TIMEOUT=>1,
            )));
        $this->assertTrue(!!preg_match('#body#i',$res));


        $proxy=new SOSO_Base_Data_Proxy();
        $proxy->setOption(
            CURLOPT_HTTPHEADER,array('Host: msg.soso.com')
            );
        $proxy->setOption(
            CURLOPT_TIMEOUT,1);
        $connection->setProxy($proxy);
        $this->assertSame($proxy,$connection->getProxy());
        $res=$connection->request('http://10.130.74.17/css/style.css');
        $this->assertTrue(!!preg_match('#body#i',$res));
    }
    public function testConstruct(){
        $connection=new SOSO_Base_Data_Connection(array(
                    'proxy'=>new SOSO_Base_Data_Proxy(),
                    ));
        $res=$connection->request('http://10.130.74.17/css/style.css',array(
                    'http_options'=>array(
                        CURLOPT_HTTPHEADER=>array('Host: msg.soso.com'),
                        CURLOPT_TIMEOUT=>1,
                        ),
                    'method'=>'xxx',//will use default,get
                    'try_times'=>2,
                    ));
        $this->assertTrue(!!preg_match('#body#i',$res));
        $this->assertEquals(200,$connection->getStatus());
        $this->assertEquals($res,$connection->getResponse());
    }
    public function testCurl(){
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'http://10.130.74.17/');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1); 
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 1);
        //output时忽略http响应头
        curl_setopt($ch, CURLOPT_HEADER, array("Host: msg.soso.com"));
        //设置http请求的头部信息 每行是数组中的一项
        //curl_setopt($ch, CURLOPT_HTTPHEADER, array('User-Agent:Mozilla/4.0'))
        $res = curl_exec($ch); 
        $this->assertTrue(!!preg_match('#passport.oa.com#i',$res));
        $code=curl_getinfo($ch,CURLINFO_HTTP_CODE);
        $this->assertEquals(200,$code);
    }


    public function testObserver(){
	    $connection=new SOSO_Base_Data_Connection();
        $triggered=false;
        $connection->on("beforerequest",function($file,$option,$connection)use(&$triggered){
                    if(!$triggered){
                        $triggered=true;
                    }else{
                        //可以在beforerequest事件中拦截要返回的内容，应该可以用做cache
                        $connection->mContent="aaaaaaaaaaaaaa";
                    }
                    return false;
                });
        $res=$connection->request('http://10.130.74.17/css/style.css',array(
            'http_options'=>array(
                    CURLOPT_HTTPHEADER=>array('Host: msg.soso.com'),
                    CURLOPT_TIMEOUT=>1,
                    ),
            'method'=>'post',
            ));
        $this->assertEquals(true,$triggered);
        $this->assertTrue(!!preg_match('#body#i',$res));
        
        
        $res=$connection->request('http://10.130.74.17/css/style.css',array(
            'http_options'=>array(
                    CURLOPT_HTTPHEADER=>array('Host: msg.soso.com'),
                    CURLOPT_TIMEOUT=>1,
                    ),
            'method'=>'post',
            ));
        $this->assertEquals("aaaaaaaaaaaaaa",$res);
    }

    public function testRedirect(){
	    $connection=new SOSO_Base_Data_Connection();
        $res=$connection->request('http://10.130.74.17/',array(
            'http_options'=>array(
                    CURLOPT_HTTPHEADER=>array('Host: msg.soso.com'),
                    CURLOPT_TIMEOUT=>1,
                    CURLOPT_FOLLOWLOCATION=>1,//加上这个参数，可以越过302
                    ),
            'method'=>'post',
            ));
        $this->assertTrue(!!preg_match('#passport.oa.com#i',$res));
	    
        
        
    }
    /**
       * @expectedException SOSO_Exception
    */
    public function testException(){
        $connection=new SOSO_Base_Data_Connection();
        $connection->duration=1;
        $res=$connection->request('http://10.130.74.17/',array(
            'http_options'=>array(
                    CURLOPT_HTTPHEADER=>array('Host: msg.soso.com'),
                    CURLOPT_TIMEOUT=>1,
                    ),
            'method'=>'post',
            'try_times'=>2,
            ));
    }
    
    /**
       * @expectedException SOSO_Exception
    */
    public function testFileNotFound(){
        $connection=new SOSO_Base_Data_Connection();
        $res=$connection->request("/tmp/file_not_found");
    }
    
    public function testFile(){
        $file="/tmp/file";
        file_put_contents($file,"content");
        chmod($file,777);
        $connection=new SOSO_Base_Data_Connection();
        $res=$connection->request($file);
        $this->assertEquals("content",$res);
    }
    public function tearDown(){
        @unlink("/tmp/file");
    }
		
		
}
