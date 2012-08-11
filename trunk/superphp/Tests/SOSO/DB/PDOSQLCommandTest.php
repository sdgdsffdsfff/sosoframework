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
    
    //�޴����ݿ�
    public function testGetInstance1() {
        $dbIndex = 3;
        try {
            $this->_obj = SOSO_DB_PDOSQLCommand::getInstance($dbIndex);
        }
        catch(Exception $e) {
            $msg = $e->getMessage();
            $this->assertEquals('��ȡ������ʧ��', $msg);
        }
    }
    
    //���ݿ����Ͳ�֧��
    public function testGetInstance2() {
        $dbIndex = 1;
        try {
            $this->_obj = SOSO_DB_PDOSQLCommand::getInstance($dbIndex);
        }
        catch(Exception $e) {
            $msg = $e->getMessage();
            $this->assertEquals('��֧��nodb���ݿ�', $msg);
        }
    }
    
    //��������
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
    
    //����
    public function testDb_connect1() {
        $this->_obj->db_close();
        $res = $this->_obj->db_connect();
        $this->assertTrue($res instanceof SOSO_DB_PDOSQLCommand);
        
        $active = $this->_obj->getActive();
        $this->assertTrue($active);
    }
    
    //�쳣
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
        //��ԭһ�£���Ϊ�ǵ����࣬������������û������
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
        
        //sql��������
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
        //����������ʽ
        $type = 'assoc';
        $res = $this->_obj->ExecuteArrayQuery($sql, $pageNo, $pageSize, $type);
        //var_dump($res);
        $this->assertEquals(2, count($res));
        $name = $res[0]['name'];
        $this->assertEquals('pennywang', $name);
        
        //������ʽ
        $type = 'num';
        $res = $this->_obj->ExecuteArrayQuery($sql, $pageNo, $pageSize, $type);
        //var_dump($res);
        $this->assertEquals(2, count($res));
        $name = $res[1][0];
        $this->assertEquals('goodenpei', $name);
        
        //����ͨ�÷�
        $res = $this->_obj->ExecuteArrayQuery($sql);
        $this->assertEquals(4, count($res));
    }
    
    public function testExecuteIteratorQuery() {
        $sql = "select name from people";
        //��������
        $it = $this->_obj->ExecuteIteratorQuery($sql);
        $this->assertTrue($it instanceof PDOStatement);
        foreach ($it as $row) {
            $this->assertTrue(!!strlen($row['name']));
        }
        //������������
        $it = $this->_obj->ExecuteIteratorQuery($sql, 0, 10, PDO::FETCH_NUM);
        foreach ($it as $row) {
            //var_dump($row);
            $this->assertTrue(!!strlen($row[0]));
        }
    }
    
    public function testExecuteQuery() {
        //PDOStatment::execute�ķ���ֵ������ֻ��true/false
        $sql = "select name from people";
        $res = $this->_obj->ExecuteQuery($sql);
        $this->assertTrue($res);
        
        $sql = "insert into people values('', '29', 'xuanliu');";
        $res = $this->_obj->ExecuteQuery($sql);
        $this->assertTrue($res);
        
        $sql = "delete from people where name='xuanliu';";
        $res = $this->_obj->ExecuteQuery($sql);
        $this->assertTrue($res);
        //ɶʱ���ܷ���false�أ�
    }
    
    /**
     * prepare�Ĺ��ܣ�
     * 1.�������ڳ�Ա����query
     * 2.���query����_STMT���PDOStatment
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
