<?php
/**
 * @author moonzhang (zyfunny@gmail.com)
 * @version 1.0 2010-10-09 17:28:01Z
 * 
 */

class SOSO_DB_Driver_PDOMySQL {
	
//	private $mDatabaseLink;
//	private $mDatabaseHost = 'localhost';
//	private $mDatabaseUser;
//	private $mDatabasePass;
//	private $mDatabaseName;
//	private $mDatabaseConfig = array('host'=>'localhost','username'=>'','password'=>'','port'=>3306,'socket'=>'');
//	private $mDatabasePort = 3306;
//	const DEFAULT_PORT = 3306;
	
	/**
	 * 
	 *
	 * @var string
	 */
	private $mLastQuery;
	private $_pdo;
//	/**
//	 * mysql.sock
//	 *
//	 * @var string
//	 */
//	private $mDatabaseSock = '';
//	private $mDBCharset = 'GBK';
//	protected $mOptions = array();
	//protected $mSchema;
	
//	public function __construct($config=''){
//		if (is_string($config) && strpos($config, 'mysql:') === 0){
//			$dsn = $config;
//		}else{
//			$tConfig = array_merge($this->mDatabaseConfig,array_filter($config));
//			$this->mDBCharset = (isset($tConfig['charset']) && strlen($tConfig['charset'])) ? $tConfig['charset'] : $this->mDBCharset; 
//			$this->mDatabaseHost = isset($tConfig['host']) ? $tConfig['host'] : null;
//			$this->mDatabaseUser = isset($tConfig['username']) ? $tConfig['username'] : null;
//			$this->mDatabasePass = isset($tConfig['password']) ? $tConfig['password'] : null;
//			//$this->mDatabasePort = isset($tConfig['port']) ? $tConfig['port'] : null;
//			//$this->mDatabaseSock = isset($tConfig['socket']) ? $tConfig['socket'] : null;
//			$this->mDatabaseName = isset($tConfig['database']) ? $tConfig['database'] : null;
//			//$this->mDBCharset    = $pCharset;
//			$this->mOptions      = isset($tConfig['config']) ? explode(',',$tConfig['config']) : array();
//			$dsn = sprintf("mysql:host=%s;dbname=%s",$this->mDatabaseHost,$this->mDatabaseName);
//		}
//		parent::__construct($dsn, $this->mDatabaseUser, $this->mDatabasePass, $this->mOptions);
//	}
	public function __construct(PDO $pdo){
		$this->_pdo = $pdo;
	}
	
	public function __call($method,$parameters){
		return call_user_func_array(array($method,$this->_pdo),$parameters);
	}
	
	/**
	 * 
	 *
	 * @param resource $pResult
	 * @return mixed
	 */
	public function db_fetch_assoc($pResult){
		$pResult->fetch(PDO::FETCH_ASSOC);
	}

	/**
	 * 
	 * 
	 * @param PDOStatement $result
	 * @param array
	 */
	public function db_fetch_array($result, $pResultType='assoc'){
		$types = array('num'=>PDO::FETCH_NUM,'both'=>PDO::FETCH_BOTH,'assoc'=>PDO::FETCH_ASSOC,'bound'=>PDO::FETCH_BOUND);
		if (!$result instanceof PDOStatement) {
			return array();
		}
		$tReturn = array();
		$tResultType = array_key_exists($pResultType,$types) ? $types[$pResultType] : $pResultType;
		return $result->fetch($tResultType);
	}
	
	/**
	 * 
	 * @param pTable
	 */
	public function getTableFields($pTable){
		$tSQL = "SHOW FULL COLUMNS FROM `$pTable`";
		$tFields = array();
		$primary_key = array();
		$auto = '';
		$stmp = $this->_pdo->query($tSQL);
		if (!$stmp instanceof PDOStatement) {
			throw new Exception("TABLE ($pTable) DOES NOT EXISTS! ");
		}
		
		while($field = $stmp->fetch(PDO::FETCH_ASSOC)){
			$column = array_shift($field);
			$tFields[$column] = $field;
			if ('PRI' == $field['Key']) {
				$primary_key[] = $column;
			}
			if ($field['Extra'] == 'auto_increment') {
				$auto = $column;
			}
		}
		$smtm = $this->_pdo->query("SHOW CREATE TABLE `$pTable`");
		$tRow = $smtm->fetch(PDO::FETCH_NUM);
		$pattern = "#\).+?CHARSET=\s*([^\s]+)#ism";
		$tCharset = '';
		if (preg_match_all($pattern,$tRow[1],$m)) {
			$tCharset = trim($m[1][0]);
		}
		return array('Fields'=>$tFields,'Primary'=>$primary_key,'auto'=>$auto,'charset'=>$tCharset);
	}
	
	/**
	 * 
	 * @param pTable
	 */
	public function getTablePK($pTable){
		$schema = $this->getTableFields($pTable);
		return $schema['Primary'];
	}


	/**
	 * 
	 * @param pQuery
	 */
	public function db_query($pQuery){
		$this->mLastQuery = $pQuery;
		return $this->_pdo->query($pQuery);
	}
	
	public function setCharset($pCharset='gbk'){
		$this->_pdo->exec("SET NAMES '$pCharset'");
		return $this;
	}

	/**
	 * 
	 * @param sql
	 * @param pPageNo
	 * @param pPageSize
	 */
	public function limit($sql, $pPageNo = 0, $pPageSize = 10){
		return $sql.sprintf(" LIMIT %s,%s",($pPageNo-1)*$pPageSize,$pPageSize);
	}
	
	public function applyLimit(&$sql, $offset, $limit){
		if ( $limit > 0 ) {
			$sql .= " LIMIT " . ($offset > 0 ? $offset . ", " : "") . $limit;
		} else if ( $offset > 0 ) {
			$sql .= " LIMIT " . $offset . ", 18446744073709551615";
		}
		return $sql;
	}
	public function getLastQuery(){
		return $this->mLastQuery;
	}
	
	/**
	 * This method is used to ignore case.
	 *
	 * @param      in The string to transform to upper case.
	 * @return     The upper case string.
	 */
	public function toUpperCase($in){
		return "UPPER(" . $in . ")";
	}

	/**
	 * This method is used to ignore case.
	 *
	 * @param      in The string whose case to ignore.
	 * @return     The string in a case that can be ignored.
	 */
	public function ignoreCase($in){
		return "UPPER(" . $in . ")";
	}

	/**
	 * Returns SQL which concatenates the second string to the first.
	 *
	 * @param      string String to concatenate.
	 * @param      string String to append.
	 * @return     string
	 */
	public function concatString($s1, $s2){
		return "CONCAT($s1, $s2)";
	}

	/**
	 * Returns SQL which extracts a substring.
	 *
	 * @param      string String to extract from.
	 * @param      int Offset to start from.
	 * @param      int Number of characters to extract.
	 * @return     string
	 */
	public function subString($s, $pos, $len){
		return "SUBSTRING($s, $pos, $len)";
	}

	/**
	 * Returns SQL which calculates the length (in chars) of a string.
	 *
	 * @param      string String to calculate length of.
	 * @return     string
	 */
	public function strLength($s){
		return "CHAR_LENGTH($s)";
	}

	/**
	 * Locks the specified table.
	 *
	 * @param      string $table The name of the table to lock.
	 * executed.
	 */
	public function lockTable($table){
		return $this->db_query("LOCK TABLE " . $table . " WRITE");
	}

	/**
	 *
	 */
	public function unlockTable(){
		return $this->db_query("UNLOCK TABLES");
	}
	
	public function random($seed = null){
		return 'rand('.((int) $seed).')';
	}
	
	public function quoteIdentifier($text){
		$text = trim($text,'` ');
		return '`' . $text . '`';
	}

	public function useQuoteIdentifier(){
		return true;
	}
}