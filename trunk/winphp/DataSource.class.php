<?php
    abstract class PDODataSource
    { 
        private $dsn;
        private $username;
        private $password;

        protected function __construct($dsn, $username, $password)
        {
            $this->dsn = $dsn;
            $this->username = $username;
            $this->password = $password;
        }
        public function connect()
        {
            return new PDO($this->dsn, $this->username, $this->password);
        }
    }
    class MysqlDataSource extends PDODataSource
    {
        public function __construct($dbhost, $dbname, $username, $password,$port=3306)
        {
            parent::__construct("mysql:host=$dbhost;dbname=$dbname;port=$port", $username, $password);
        }
    }
    class OracleDataSource extends PDODataSource
    {
        public function __construct($dbhost, $dbname, $username, $password)
        {
            parent::__construct("oci:dbname=//$dbhost:1521/$dbname", $username, $password);
        }
    }
?>
