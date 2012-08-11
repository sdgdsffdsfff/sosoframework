<?php 
/**
 * @author ball
 */
class SOSO_DB_PDOSQLCommandTest extends PHPUnit_Framework_TestCase {

    protected $_obj = null;
    public function setUp() {
        SOSO_Frameworks_Config::initialize('web.xml-PDOTest');
        $this->_obj = SOSO_DB_PDOSQLCommand::getInstance();
    }
    
    public function testGetAvailableDrivers() {
        $drivers = SOSO_DB_PDOSQLCommand::getAvailableDrivers();
        $this->assertTrue(is_array($drivers));
        
        $index = array_search('mysql', $drivers);
        $this->assertTrue(!!$index);
        //var_dump($index, $drivers);
    }
    
    //无此数据库
    public function testGetInstance1() {
        $dbIndex = 3;
        try {
            $this->_obj = SOSO_DB_PDOSQLCommand::getInstance($dbIndex);
        }
        catch(Exception $e) {
            $msg = $e->getMessage();
            $this->assertEquals('读取配置项失败', $msg);
        }
    }
    
    //数据库类型不支持
    public function testGetInstance2() {
        $dbIndex = 1;
        try {
            $this->_obj = SOSO_DB_PDOSQLCommand::getInstance($dbIndex);
        }
        catch(Exception $e) {
            $msg = $e->getMessage();
            $this->assertEquals('不支持nodb数据库', $msg);
        }
    }
    
    //正常连接
    public function testGetInstance3() {
        $this->_obj = SOSO_DB_PDOSQLCommand::getInstance();
        $active = $this->_obj->getActive();
        $this->assertTrue($active);
        
        $pdo = $this->_obj->getPdoInstance();
        $this->assertTrue($pdo instanceof PDO);
        
        $connectString = 'mysql:host=10.1.146.144;dbname=test;unix_socket=/tmp/mysql.sock;port=3306';
        $this->assertEquals($connectString, $this->_obj->connectionString);
        //var_dump($this->_obj);
    }
    
    public function testGetDriverName() {
        $res = $this->_obj->getDriverName();
        $this->assertEquals('mysql', $res);
    }
    
    //正常
    public function testDb_connect1() {
        $this->_obj->db_close();
        $res = $this->_obj->db_connect();
        $this->assertTrue($res instanceof SOSO_DB_PDOSQLCommand);
        
        $active = $this->_obj->getActive();
        $this->assertTrue($active);
    }
    
    //异常
    public function testDb_connect2() {
        $this->_obj->db_close();
        $connectString = $this->_obj->connectionString;
        $this->_obj->connectionString = null;
        try {
            $res = $this->_obj->db_connect();
        }
        catch(Exception $e) {
            $msg = $e->getMessage();
            $this->assertEquals('PDOSQLCommand.connectionString cannot be empty.', $msg);
        }
        //复原一下，因为是单例类，否则下面例子没法跑了
        $this->_obj->connectionString = $connectString;
        //$this->assertTrue($res instanceof SOSO_DB_PDOSQLCommand);
    }
    
    public function testExecuteCountQuery() {
        $sql = "select * from people where name='ballqiu'";
        $res = $this->_obj->ExecuteCountQuery($sql);
        $this->assertEquals(1, $res);
        
        $sql = "select * from people where name='no'";
        $res = $this->_obj->ExecuteCountQuery($sql);
        $this->assertEquals(0, $res);
        
        $sql = "select * from people";
        $res = $this->_obj->ExecuteCountQuery($sql);
        $this->assertEquals(4, $res);
        
        //sql出错的情况
        $sql = "select from people";
        try {
            $res = $this->_obj->ExecuteCountQuery($sql);
        }
        catch(exception $e) {
            $msg = $e->getMessage();
            $this->assertTrue(strlen($msg) > 0);
        }
    }
    
    public function testExecuteArrayQuery() {
        $sql = "select name from people";
        $pageNo = 2;
        $pageSize = 2;
        //关联数组形式
        $type = 'assoc';
        $res = $this->_obj->ExecuteArrayQuery($sql, $pageNo, $pageSize, $type);
        //var_dump($res);
        $this->assertEquals(2, count($res));
        $name = $res[0]['name'];
        $this->assertEquals('pennywang', $name);
        
        //数字形式
        $type = 'num';
        $res = $this->_obj->ExecuteArrayQuery($sql, $pageNo, $pageSize, $type);
        //var_dump($res);
        $this->assertEquals(2, count($res));
        $name = $res[1][0];
        $this->assertEquals('goodenpei', $name);
        
        //最普通用法
        $res = $this->_obj->ExecuteArrayQuery($sql);
        $this->assertEquals(4, count($res));
    }
    
    public function testExecuteIteratorQuery() {
        $sql = "select name from people";
        //关联数组
        $it = $this->_obj->ExecuteIteratorQuery($sql);
        $this->assertTrue($it instanceof PDOStatement);
        foreach ($it as $row) {
            $this->assertTrue(!!strlen($row['name']));
        }
        //数字索引数组
        $it = $this->_obj->ExecuteIteratorQuery($sql, 0, 10, PDO::FETCH_NUM);
        foreach ($it as $row) {
            //var_dump($row);
            $this->assertTrue(!!strlen($row[0]));
        }
    }
    
    public function testExecuteQuery() {
        //PDOStatment::execute的返回值，所以只是true/false
        $sql = "select name from people";
        $res = $this->_obj->ExecuteQuery($sql);
        $this->assertTrue($res);
        
        $sql = "insert into people values('', '29', 'xuanliu');";
        $res = $this->_obj->ExecuteQuery($sql);
        $this->assertTrue($res);
        
        $sql = "delete from people where name='xuanliu';";
        $res = $this->_obj->ExecuteQuery($sql);
        $this->assertTrue($res);
        //啥时候能返回false呢？
    }
    
    /**
     * prepare的功能：
     * 1.设置类内成员变量query
     * 2.结合query，把_STMT搞成PDOStatment
     */
    public function testPrepare() {
        $sql = "select name from people";
        $res = $this->_obj->prepare($sql);
        
        $this->assertEquals($sql, $this->_obj->getQuery());
        $this->assertTrue($res instanceof PDOStatement);
    }
    
    public function testExecuteScalar() {
        $sql = "select name from people";
        $res = $this->_obj->ExecuteScalar($sql);
        $this->assertEquals('ballqiu', $res);
        
        $sql = "select :col from people";
        $parameter = array(':col'=>'i');
        $res = $this->_obj->ExecuteScalar($sql, $parameter);
        var_dump($res);
    }
    
    public function testBindValue() {
        $sql = "select * from people where id>:name;";
        $this->_obj->prepare($sql);
        $this->_obj->bindValue(':name', '1');
        
        $res = $this->_obj->queryAll(true);
        $this->assertEquals(3, count($res));
        var_dump($res);
    }
}
