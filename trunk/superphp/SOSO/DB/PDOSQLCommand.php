<?php
/**
 *
 * @author moonzhang (zyfunny@gmail.com)
 * @version $Id: PDOSQLCommand.php 434 2010-12-09 16:55:01Z moonzhang $
 * @package SOSO.DB
 * 数据库驱动
 */
require_once(dirname(__FILE__).'/Driver/PDOMySQL.php');
class SOSO_DB_PDOSQLCommand /*extends SOSO_DB_SQLCommand*/{

	private $_STMT;
	private $_pdo;
	private $_transaction;
	private $_driver;
	

	/**
	 * @var string The Data Source Name, or DSN, contains the information required to connect to the database.
	 */
	public $connectionString;

	public $username='';

	public $password='';

	public $autoConnect=true;

	public $charset;

	public $emulatePrepare=false;

	protected $query;
	private $_active = false;

	/**
	 *
	 * Enter description here ...
	 * @var SOSO_DB_PDOSQLCommand
	 */
	protected static $theInstances = array();

	/**
	 * database index
	 * Enter description here ...
	 * @var int
	 */
	protected $_index = 0;
	protected $_attributes = array();

	protected function __construct($dsn,$username='',$password=''){
		$this->connectionString = $dsn;
		$this->username = $username;
		$this->password = $password;
	}

	public function getDriver(){
		if($this->_driver!==null)
		return $this->_driver;
		else{
			if(!$this->getActive())
			throw new SOSO_Exception('PDOSQLCommand is inactive and cannot perform any DB operations.');

			$driver=$this->getDriverName();
				
			switch(strtolower($driver)){
				case 'mysqli':
				case 'mysql':
					return $this->_driver = new SOSO_DB_Driver_PDOMySQL($this->_pdo);
				default:
					throw new SOSO_Exception("PDOSQLCommand does not support reading schema for {$driver} database.");
			}
		}
	}
	/**
	 *
	 * Enter description here ...
	 * @throws SOSO_Exception
	 * @return SOSO_DB_PDOSQLCommand
	 */
	public function prepare($statement=null,$driver_options=array()){
		if($this->_STMT==null || $statement)	{
			try{
				$statement ? 
					$this->setQuery($statement) :
					$statement = $this->getQuery();
				
				$this->_STMT = $this->getPdoInstance()->prepare($statement,$driver_options);
			}catch(Exception $e){
				throw new SOSO_Exception(sprintf("PDOSQLCommand failed to prepare the SQL statement: \n\t{%s}",$e->getMessage()));
			}
		}
		return $this->_STMT;
	}
	public function getErrorCode(){
		if (!$this->_active) return false;
		return $this->_STMT->errorCode();
	}

	public function getErrorInfo(){
		if (!$this->_active) return false;
		return $this->_STMT ? $this->_STMT->errorInfo() : false;
	}
	/**
	 * 工厂方法 － 根据数据库类型选用相应操作类
	 *
	 * @param 数据库索引，以0开始 $pDBIndex
	 * @return SOSO_DB_PDOSQLCommand
	 */
	public static function &getInstance($pDBIndex=0,$pAutoConnect=true){
		if (! isset(self::$theInstances[$pDBIndex]) ) {
			$registry = & SOSO_Frameworks_Registry::getInstance();
			$tDatabaseConfig = $registry->get('databases');
			if (is_null($tDatabaseConfig) || !isset($tDatabaseConfig[$pDBIndex])) {
				throw new SOSO_Exception("读取配置项失败");
			}
			$tConfig = $tDatabaseConfig[$pDBIndex];

			$drivers = self::getAvailableDrivers();
			if(false === array_search(strtolower($tConfig['type']), $drivers)){
				throw new SOSO_Exception("不支持{$tDatabaseConfig[$pDBIndex]['type']}数据库");
			}

			$dsn = sprintf("%s:host=%s;dbname=%s",strtolower($tConfig['type']),$tConfig['host'],$tConfig['database']);
			if (isset($tConfig['socket']) && strlen($tConfig['socket'])){
				$dsn .= sprintf(';unix_socket=%s',$tConfig['socket']);
			} 
			if (isset($tConfig['port']) && strlen($tConfig['port'])){
				$dsn .= sprintf(';port=%s',$tConfig['port']);
			} 
			self::$theInstances[$pDBIndex] = new self($dsn,$tConfig['username'],$tConfig['password']);
			if (isset($tConfig['Persistent']) && !!$tConfig['Persistent']){
				self::$theInstances[$pDBIndex]->setAttribute(PDO::ATTR_PERSISTENT,true);
			}
			if ($pAutoConnect){
				self::$theInstances[$pDBIndex]->setActive(true);
			}
			self::$theInstances[$pDBIndex]->setIndex($pDBIndex);
			if(isset($tConfig['charset']) && strlen($tConfig['charset'])){
				self::$theInstances[$pDBIndex]->setCharset($tConfig['charset']);
			}
			//if (isset($tConfig['emulatePrepare'])) 
				//self::$theInstances[$pDBIndex]->emulatePrepare = !!$tConfig['emulatePrepare'];
		}
		if ($pAutoConnect){
			self::$theInstances[$pDBIndex]->setActive(true);
		}
		return self::$theInstances[$pDBIndex];
	}

	public function __call($method, $arguments) {
		return $this->getPdoInstance()->$method($arguments);
	}
	/**
	 * Set the statement to null when serializing.
	 */
	public function __sleep(){
		$this->_STMT=null;
		return array_keys(get_object_vars($this));
	}

	/**
	 * @return array list of available PDO drivers
	 * @see http://www.php.net/manual/en/function.PDO-getAvailableDrivers.php
	 */
	public static function getAvailableDrivers(){
		return PDO::getAvailableDrivers();
	}

	/**
	 * @return PDO the connection associated with this command
	 */
	public function getConnection(){
		return $this->_pdo;
	}

	/**
	 *
	 * @param array
	 */
	public function resetDB($DBConfig=array()){
		return $this->closeDB()->connectDB();
	}

	public function connectDB(){
		return $this;
	}

	public function closeDB(){
		$this->_pdo = null;
		return $this;
	}

	public function bindParam($name, &$value, $dataType=null, $length=null)	{
		$this->prepare();
		if($dataType===null)
		$this->_STMT->bindParam($name,$value,$this->getPdoType(gettype($value)));
		else if($length===null)
		$this->_STMT->bindParam($name,$value,$dataType);
		else
		$this->_STMT->bindParam($name,$value,$dataType,$length);
	}

	public function bindValue($name, $value, $dataType=null){
		$this->prepare();
		if($dataType===null)
		$this->_STMT->bindValue($name,$value,$this->getPdoType(gettype($value)));
		else
		$this->_STMT->bindValue($name,$value,$dataType);
		return $this;
	}

	/**
	 * Executes the SQL statement.
	 * This method is meant only for executing non-query SQL statement.
	 * No result set will be returned.
	 * @return integer number of rows affected by the execution.
	 * @throws Exception execution failed
	 */
	public function execute($input_parameters=null){
		if($this->_STMT instanceof PDOStatement){
			$this->_STMT->execute($input_parameters);
			return $this->_STMT->rowCount();
		}else
		return $this->getPdoInstance()->exec($this->getQuery());
	}

	/**
	 * 执行SQL语句，返回查询结果
	 *
	 * @return Array 查询结果
	 * @throws Exception 执行出错
	 */
	public function query()	{
		return $this->queryProxy('fetchAll',0);
	}

	/**
	 * 返回全部结果
	 * @param boolean 是否是关联数组
	 * @return array 结果集
	 * @throws SOSO_Exception 异常错误
	 */
	public function queryAll($fetchAssociative=true){
		return $this->queryProxy('fetchAll',$fetchAssociative ? PDO::FETCH_ASSOC : PDO::FETCH_NUM);
	}

	public function queryRow($fetchAssociative=true){
		return $this->queryProxy('fetch',$fetchAssociative ? PDO::FETCH_ASSOC : PDO::FETCH_NUM);
	}

	public function queryScalar($params=array()){
		$result=$this->queryProxy('fetchColumn',0,$params);
		return $result;
	}

	public function queryColumn(){
		return $this->queryProxy('fetchAll',PDO::FETCH_COLUMN);
	}

	private function queryProxy($method,$mode,$params=array()){
		try	{
			if($this->_STMT instanceof PDOStatement){
				if($params===array()){
					$this->_STMT->execute();
				}else{
					$this->_STMT->execute($params);
				}
			}else
				$this->_STMT=$this->getPdoInstance()->query($this->getQuery());
			if($method==='')
				$method = 'fetchAll';
			$result=$this->_STMT->{$method}($mode);
			$this->_STMT->closeCursor();
			return $result;
		}catch(Exception $e){
			throw new SOSO_Exception(sprintf("PDOSQLCommand failed to execute the SQL statement: \n\t{%s}",$e->getMessage()));
		}
	}

	/**
	 * @return PDO the PDO instance, null if the connection is not established yet
	 */
	public function getPdoInstance(){
		return $this->_pdo;
	}
	
	public function getStatementInstance(){
		return $this->_STMT;
	}

	/**
	 * @return Transaction the currently active transaction. Null if no active transaction.
	 */
	public function getCurrentTransaction()	{
		if($this->_transaction!==null){
			return $this->_transaction;
		}
		return null;
	}

	/**
	 *
	 * Starts a transaction.
	 * @return SOSO_DB_Transaction the transaction initiated
	 * @throws SOSO_Exception if the connection is not active
	 */
	public function beginTransaction(){
		$this->setAutoCommit(false);
		$this->_pdo->beginTransaction();
		
		if ($this->_transaction instanceof SOSO_DB_Transaction){
			return $this->_transaction;
		}
		if (!$this->getActive()){
			throw new SOSO_Exception('PDOSQLCommand is inactive and cannot perform any DB operations');
		}
		
		$type = $this->getDriverName();
		switch(strtolower($type)){
				case 'mysqli':
				case 'mysql':
					$this->_transaction=new SOSO_DB_Transaction_Mysql($this);
					break;
				default:
					$this->_transaction=new SOSO_DB_Transaction($this);
					break;
		}
		return $this->_transaction;
	}

	public function getLastInsertID($sequenceName=''){
		if (!$this->getActive()){
			throw new SOSO_Exception('PDOSQLCommand is inactive and cannot perform any DB operations');
		}
		return $this->getPdoInstance()->lastInsertId($sequenceName);
	}

	public function quoteValue($str){
		if (!$this->getActive()){
			throw new SOSO_Exception('PDOSQLCommand is inactive and cannot perform any DB operations');
		}
		return $this->getPdoInstance()->quote($str);
	}

	public function getPdoType($type){
		static $map=array(
			'boolean'=>PDO::PARAM_BOOL,
			'integer'=>PDO::PARAM_INT,
			'string'=>PDO::PARAM_STR,
			'NULL'=>PDO::PARAM_NULL,
		);
		return isset($map[$type]) ? $map[$type] : PDO::PARAM_STR;
	}

	public function getColumnCase(){
		return $this->getAttribute(PDO::ATTR_CASE);
	}

	public function setColumnCase($value){
		$this->setAttribute(PDO::ATTR_CASE,$value);
	}

	public function getNullConversion(){
		return $this->getAttribute(PDO::ATTR_ORACLE_NULLS);
	}

	public function setNullConversion($value){
		$this->setAttribute(PDO::ATTR_ORACLE_NULLS,$value);
	}

	public function getAutoCommit(){
		return $this->getAttribute(PDO::ATTR_AUTOCOMMIT);
	}

	public function setAutoCommit($value){
		$this->setAttribute(PDO::ATTR_AUTOCOMMIT,$value);
	}

	public function getPersistent(){
		return $this->getAttribute(PDO::ATTR_PERSISTENT);
	}

	public function setPersistent($value){
		return $this->setAttribute(PDO::ATTR_PERSISTENT,$value);
	}

	/**
	 * @return string name of the DB driver
	 */
	public function getDriverName(){
		return $this->getAttribute(PDO::ATTR_DRIVER_NAME);
	}

	/**
	 * @return string the version information of the DB driver
	 */
	public function getClientVersion(){
		return $this->getAttribute(PDO::ATTR_CLIENT_VERSION);
	}

	public function getConnectionStatus(){
		return $this->getAttribute(PDO::ATTR_CONNECTION_STATUS);
	}

	/**
	 * @return boolean whether the connection performs data prefetching
	 */
	public function getPrefetch(){
		return $this->getAttribute(PDO::ATTR_PREFETCH);
	}

	/**
	 * @return string the information of DBMS server
	 */
	public function getServerInfo(){
		return $this->getAttribute(PDO::ATTR_SERVER_INFO);
	}

	/**
	 * @return string the version information of DBMS server
	 */
	public function getServerVersion(){
		return $this->getAttribute(PDO::ATTR_SERVER_VERSION);
	}

	/**
	 * @return int timeout settings for the connection
	 */
	public function getTimeout(){
		return $this->getAttribute(PDO::ATTR_TIMEOUT);
	}

	public function getAttribute($name){
		return $this->getPdoInstance()->getAttribute($name);
	}

	public function setAttribute($name,$value){
		if($this->_pdo instanceof PDO)
		$this->_pdo->setAttribute($name,$value);
		else
		$this->_attributes[$name]=$value;
	}

	public function setAttributes($array){
		foreach($array as $name=>$value){
			$this->setAttribute($name,$value);
		}
	}

	public function ExecuteArrayQuery($sql, $pPageNo=0, $pPageSize = 10, $pResultType = 'assoc'){
		if (!(0 >= $pPageNo || $pPageSize < 1)) {
			$sql = $this->limit($sql,$pPageNo,$pPageSize);
		}
		$this->setQuery($sql);

		return $this->queryAll('assoc' == $pResultType || PDO::FETCH_ASSOC == $pResultType);
	}
	/**
	 *
	 * @param string $pQuery SQL to be executed
	 */
	public function ExecuteQuery($pQuery,$params=array()){
		$this->setQuery($pQuery);
		return $this->prepare()->execute($params);
	}

	public function ExecuteCountQuery($pSQL){
		$tSQL = preg_replace('~^SELECT\s+(?:.+?)\s+FROM\s+(\w+)(\s*.+?)?~i','SELECT count(*) FROM \\1 \\2',$pSQL);
		return $this->ExecuteScalar($tSQL);
	}
	public function init(PDO $pdo){
		//$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		if (SOSO_Frameworks_Config::getMode() != 'online'){
			$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		}
		
		if(strpos($this->getDriverName(),'mysql') === 0 || $this->emulatePrepare && constant('PDO::ATTR_EMULATE_PREPARES')){
			$pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES,true);
			$this->emulatePrepare = true;
		}
	}
	
	public function emulatePrepares($flag=true){
		$this->setAttribute(PDO::ATTR_EMULATE_PREPARES, $flag);
		return $this;
	}
	
	public function ExecuteScalar($sql,$params=array())	{
		$this->prepare($sql);
		return $this->queryScalar($params);
	}
	public function ExecuteInsertQuery($sql){
		$this->setQuery($sql);
		$count = $this->getPdoInstance()->exec($sql);
		if($count){
			$tAutoID = $this->getLastInsertID();
			return $tAutoID ? $tAutoID : true;
		}
		return false;
	}
	/**
	 * 获得分布数据,基于缓存的
	 * @param string $sql 要执行的SQL语句
	 * @param int $pPageNo 页数
	 * @param int $pPageSize 每页
	 * @param string $pResultType
	 * @param int $pCacheTime  缓存时间
	 * @return  array();
	 */
	public function ExecuteCachedArrayQuery($pCacheTime=86400,$sql, $pPageNo=0, $pPageSize = 10, $pResultType = PDO::FETCH_ASSOC){
		if (0 >= $pPageNo || $pPageSize < 1) {
			$tSQL = $sql;
		}else{
			$tSQL = $this->limit($sql,$pPageNo,$pPageSize);
		}
		$tCache = SOSO_Cache::getInstance('file',array('cache_time'=>$pCacheTime,'cache_dir'=>'SQL','auto_hash'=>true));
		$tCacheKey = $tCache->getKey($tSQL);
		$tData = $tCache->get($tCacheKey);
		if (!is_null($tData)) {
			return $tData;
		}
		$statement = $this->getPdoInstance()->query($tSQL,PDO::FETCH_ASSOC);
		if (!$statement) {
			return array();
		}
		$tData = $this->queryAll($pResultType==PDO::FETCH_ASSOC);
		$tCache->set($tCacheKey,$tData,$pCacheTime);
		return $tData;
	}
	/**
	 *
	 * @param string $sql 要执行的SQL语句
	 * @param int $pPageNo 页数
	 * @param int $pPageSize 每页
	 * @param string $pResultType
	 */
	public function ExecuteIteratorQuery($sql, $pPageNo=0, $pPageSize = 10,$mode=PDO::FETCH_ASSOC,$class=NULL){
		if (!(0 >= $pPageNo || $pPageSize < 1)) {
			$sql = $this->limit($sql,$pPageNo,$pPageSize);
		}
		$it = $this->getPdoInstance()->query($sql,$mode,$class);
		if(!$it) return array();
		return $it;
	}
	public function db_connect(){
		if($this->_pdo===null){
			if(empty($this->connectionString))
				throw new SOSO_Exception('PDOSQLCommand.connectionString cannot be empty.');
			try{
				$this->_pdo = new PDO($this->connectionString,$this->username,$this->password,$this->_attributes);
				$this->init($this->_pdo);
				$this->_active=true;
			}
			catch(PDOException $e){
				throw new SOSO_Exception(sprintf('PDOSQLCommand failed to open the DB connection: {%s}',$e->getMessage()));
			}
		}
		return $this;
	}

	public function getTableFields($pTable){
		return $this->getDriver()->getTableFields($pTable);
	}

	public function getTablePK($pTable){
		return $this->getDriver()->getTablePK($pTable);
	}

	public function db_insert_id(){
		return $this->getLastInsertID();
	}

	public function db_affected_rows(){
		$stmt = $this->getStatementInstance();
		if(null == $stmt) return 0; 
		return $stmt->rowCount();
	}

	public function db_num_rows($pResult){
		//return $this->getPdoInstance()->
	}

	public function db_fetch_array($pResultType = PDO::FETCH_BOTH){
		return $this->queryProxy('fetch',$pResultType);
	}

	public function db_free_result($pResult){
		if ($this->_STMT instanceof PDOStatement){
			$this->_STMT->closeCursor();
		}
		return $this;
	}

	public function db_fetch_all($resultType=PDO::FETCH_ASSOC){
		return $this->queryProxy('fetchAll',$resultType);
	}

	public function db_query($pQuery){
		$this->setQuery($pQuery);

		/**
		 return $this->_STMT = $this->_pdo->query($pQuery);
		 */
	}

	public function db_close(){
		$this->_pdo=null;
		$this->_active=false;
		$this->_driver = null;
	}

	public function db_data_seek($result_identifier, $row_number){
		return null;
	}

	public function limit($sql, $pPageNo=0, $pPageSize = 10){
		return $this->getDriver()->limit($sql,$pPageNo,$pPageSize);
	}

	public function getLastQuery(){
		if($this->_STMT instanceof PDOStatement)
		return $this->_STMT->queryString;
		else
		return $this->getQuery();
	}

	public function select_db($pDB){
		$databases = SOSO_Frameworks_Registry::getInstance()->get('databases');
		foreach ($databases as $index=>$db){
			if($db['database'] == $pDB){
				return SOSO_DB_PDOSQLCommand::getInstance($this->_index);
			}
		}
		throw new SOSO_Exception(sprintf('No such database(%s) found.',$pDB));
	}

	public function setCharset($pCharset='gbk'){
		if(!$this->getActive()){
			$this->charset = $pCharset;
			return $this;
		}
		if (!strlen($pCharset)){
			return $this;
		}
		$this->charset = $pCharset;
		$this->getDriver()->setCharset($pCharset);
		return $this;
	}
	
	public function getCharset(){
		return $this->charset;
	}

	public function setIndex($index=0){
		$this->_index = $index;
		return $this;
	}

	public function getIndex(){
		return $this->_index;
	}

	public function setActive($value){
		if($value != $this->_active){
			if($value){
				$this->db_connect();
				$this->setCharset($this->charset);
			}else{
				$this->db_close();
			}
		}
		return $this;
	}

	public function getActive(){
		return $this->_active;
	}
	/**
	 *
	 * 指定一个要执行的SQL语句
	 * @param string $query 要执行的SQL语句
	 * @return SOSO_DB_PDOSQLCommand
	 */
	public function setQuery($query){
		$this->query = $query;
		$this->cancel();
		return $this;
	}

	public function cancel(){
		$this->_STMT = null;
	}

	public function getQuery(){
		return $this->query;
	}
	//abstract methods implemented
	public function ignoreCaseInOrderBy($in){
		return $this->getDriver()->ignoreCase($in);
	}
		
	public function ignoreCase($in){
		return $this->getDriver()->ignoreCase($in);
	}
	public function concatString($s1, $s2){
		return $this->getDriver()->concatString($s1, $s2);
	}
	public function random($seed = null){
		return $this->getDriver()->random($seed);
	}
	public function strLength($s){
		return $this->getDriver()->strLength($s);
	}
	public function subString($s, $pos, $len){
		return $this->getDriver()->subString($s, $pos, $len);
	}
	public function toUpperCase($in){
		return $this->getDriver()->toUpperCase($in);
	}
	public function applyLimit(&$sql, $offset, $limit){
		return $this->getDriver()->applyLimit($sql, $offset, $limit);
	}
	public function quoteIdentifier($text){
		if (!$this->getActive()) return $text;
		return $this->getDriver()->quoteIdentifier($text);
	}
	public function useQuoteIdentifier(){
		return $this->getActive() && $this->getDriver()->useQuoteIdentifier();
	}
	

	/**
	 * Quotes a database table which could have space seperating it from an alias, both should be identified seperately
	 * @param      string $table The table name to quo
	 * @return     string The quoted table name
	 **/
	public function quoteIdentifierTable($table) {
		if (!strlen($table)) return '';
		return implode(" ", array_map(array($this, "quoteIdentifier"), explode(" ", $table) ) );
	}
}

