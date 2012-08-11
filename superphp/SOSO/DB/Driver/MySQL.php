<?php
/**
 * SOSO Framework
 * 
 * @package SOSO_DB
 * @copyright  Copyright (c) 2007-2008 Soso.com
 * @author moonzhang
 * @version 1.0
 */
class SOSO_DB_Driver_MySQL extends SOSO_DB_SQLCommand {

	private $mDatabaseLink;
	private $mDatabaseHost = 'localhost';
	private $mDatabaseUser;
	private $mDatabasePass;
	private $mDatabaseName;
	private $mDatabaseConfig = array('host'=>'localhost','username'=>'','password'=>'','port'=>3306,'socket'=>'');
	private $mDatabasePort = 3306;
	const DEFAULT_PORT = 3306;
	
	private $mLastQuery;
	
	private $mDatabaseSock = '';
	private $mDBCharset = 'GBK';
	protected $_transaction;
	/**
	 * 
	 * ±í±àÂë
	 * @var unknown_type
	 */
	private $charset;
	
	const DB_PORT = 3306;

	public function __construct($pDBConfig=array(),$pCharset='GB2312'){
		$tConfig = array_merge($this->mDatabaseConfig,array_filter($pDBConfig));
		$pCharset = (isset($tConfig['charset']) && strlen($tConfig['charset'])) ? $tConfig['charset'] : $pCharset; 
		$this->mDatabaseHost = isset($tConfig['host']) ? $tConfig['host'] : null;
		$this->mDatabaseUser = isset($tConfig['username']) ? $tConfig['username'] : null;
		$this->mDatabasePass = isset($tConfig['password']) ? $tConfig['password'] : null;
		$this->mDatabasePort = isset($tConfig['port']) ? $tConfig['port'] : null;
		$this->mDatabaseSock = isset($tConfig['socket']) ? $tConfig['socket'] : null;
		$this->mDatabaseName = isset($tConfig['database']) ? $tConfig['database'] : null;
		$this->mDBCharset    = $pCharset;
		parent::__construct();
	}
	
	public function ping(){
		if (!mysql_ping($this->mDatabaseLink)) {
			$this->db_close();
			return $this->db_connect();
		}
		return $this->mDatabaseLink;
	}
	
	public function db_fetch_assoc($pResult){
		return $this->db_fetch_array($pResult,'assoc');
	}

	public function db_fetch_array($result, $pResultType='assoc'){
		$types = array('num'=>MYSQL_NUM,'both'=>MYSQL_BOTH,'assoc'=>MYSQL_ASSOC);
		if (!is_resource($result)) {
			return array();
		}
		$tReturn = array();
		$tResultType = array_key_exists($pResultType,$types) ? $types[$pResultType] : $types['assoc'];
		return mysql_fetch_array($result,$tResultType);
	}
	
	/**
	 * 
	 * @param pTable
	 */
	public function getTableFields($pTable){
		$this->db_connect();
		$tSQL = "SHOW FULL COLUMNS FROM `$pTable`";
		$tFields = array();
		$primary_key = array();
		$auto = '';
		$result = $this->db_query($tSQL);
		if (!is_resource($result)) {
			throw new Exception("TABLE ($pTable) DOES NOT EXISTS! ");
		}
		while($field = $this->db_fetch_array($result)){
			$column = array_shift($field);
			$tFields[$column] = $field;
			if ('PRI' == $field['Key']) {
				$primary_key[] = $column;
			}
			if ($field['Extra'] == 'auto_increment') {
				$auto = $column;
			}
		}
		$tRes = $this->db_query("SHOW CREATE TABLE `$pTable`");
		$tRow = mysql_fetch_array($tRes,MYSQL_NUM);
		$pattern = "#\).+?CHARSET=\s*([^\s]+)#ism";
		if (preg_match_all($pattern,$tRow[1],$m)) {
			$this->mDBCharset = trim($m[1][0]);
		}
		return array('Fields'=>$tFields,'Primary'=>$primary_key,'auto'=>$auto,'charset'=>$this->mDBCharset);
	}
	
	/**
	 * 
	 * @param pTable
	 */
	public function getTablePK($pTable){
		$this->db_connect();
		$tSQL = "DESC `$pTable`";
		$tArr = $this->db_fetch_array($this->db_query($tSQL));
		return $tArr;
	}

	public function db_insert_id(){
		if (!is_resource($this->mDatabaseLink)) {
			return false;
		}
		return mysql_insert_id($this->mDatabaseLink);
	}
	
	
	public function db_affected_rows(){
		if (!is_resource($this->mDatabaseLink)) {
			return 0;
		}
		return mysql_affected_rows($this->mDatabaseLink);
	}

	/**
	 * 
	 * @param resource $pResult
	 */
	public function db_num_rows($pResult){
		if (!is_resource($pResult)) {
			return 0;
		}
		return mysql_num_rows($pResult);
	}

	/**
	 * 
	 * @param pResult
	 */
	public function db_free_result($pResult){
		if (!is_resource($pResult)) {
			return false;
		}
		return @mysql_free_result($pResult);
	}

	/**
	 * 
	 * @param pQuery
	 */
	public function db_query($pQuery){
		$this->db_connect();
		$this->mLastQuery = $pQuery;
		return mysql_query($pQuery,$this->mDatabaseLink);
	}

	public function db_connect(){
		if (is_resource($this->mDatabaseLink)) {
			if ($this->mDatabaseName) {
				$this->select_db($this->mDatabaseName);
			}
			return $this->mDatabaseLink;
		}
		if (!empty($this->mDatabaseSock)){
			$hostHolder = "{$this->mDatabaseHost}:{$this->mDatabaseSock}";
		}elseif (!empty($this->mDatabasePort) && $this->mDatabasePort != self::DEFAULT_PORT){
			$hostHolder = "{$this->mDatabaseHost}:{$this->mDatabasePort}";
		}else{
			$hostHolder = "{$this->mDatabaseHost}";
		}
		
		$this->mDatabaseLink = mysql_connect($hostHolder,$this->mDatabaseUser,$this->mDatabasePass);
		if (!$this->mDatabaseLink) {
			trigger_error('Database connect failed',E_USER_ERROR);
			return false;
		}
		if ($this->mDatabaseName) {
			$this->select_db($this->mDatabaseName);
		}
//		if ($this->mDBCharset) {
//			$this->db_query("SET NAMES '{$this->mDBCharset}'");
//		}
		return $this->mDatabaseLink;
	}
	
	public function setCharset($pCharset='gbk'){
		$this->db_connect();
		if (function_exists('mysql_set_charset')) {
			return mysql_set_charset($pCharset);
		}
		$this->db_query("SET NAMES '{$pCharset}'");
		$this->charset = strtolower($pCharset);
	}
	
	public function getCharset(){
		return $this->charset;
	}
	
	public function select_db($pDB){
		$this->mDatabaseName = $pDB;
		return mysql_select_db($pDB);
	}
	
	public function db_close(){
		if (is_resource($this->mDatabaseLink)) {
			return mysql_close($this->mDatabaseLink);
		}
		return false;
	}

	/**
	 * 
	 * @param result_identifier
	 * @param row_number
	 */
	public function db_data_seek($result_identifier, $row_number){
		$this->db_connect();
		if (!is_resource($result_identifier)) {
			return false;
		}
		return @mysql_data_seek($result_identifier,$row_number);
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
	
	public function getLastQuery(){
		return $this->mLastQuery;
	}
	
	public function getPdoInstance(){
		return $this;
	}
	
	public function getCurrentTransaction(){
		if($this->_transaction!==null){
			return $this->_transaction;
		}
		return null;
	}
	
	public function setAutoCommit($value){
		$sql = sprintf("SET autocommit=%d;",$value?1:0);
		$this->db_query($sql);
		return $this;
	}
	public function beginTransaction(){
		$this->setAutoCommit(0);
		$sql = "BEGIN";
		$this->db_query($sql);
		return $this->_transaction = new SOSO_DB_Transaction($this);
	}
	
	public function rollBack(){
		$sql = "rollback";
		return $this->db_query($sql);
	}
	
	public function commit(){
		$sql = "COMMIT";
		return $this->db_query($sql);
	}

	public function applyLimit(&$sql, $offset, $limit){
		if ( $limit > 0 ) {
			$sql .= " LIMIT " . ($offset > 0 ? $offset . ", " : "") . $limit;
		} else if ( $offset > 0 ) {
			$sql .= " LIMIT " . $offset . ", 18446744073709551615";
		}
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
		return '`' . $text . '`';
	}

	public function useQuoteIdentifier(){
		return true;
	}
}
?>
