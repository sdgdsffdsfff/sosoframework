<?php
/**
 * SOSO Framework
 * 
 * @package SOSO_DB
 * @desc ͨ�����ݿ����㣬�����Ż�
 * @copyright  Copyright (c) 2007-2008 Soso.com
 * @author moonzhang
 * @version 1.0
 * @created 15-����-2008 16:59:20
 */
abstract class SOSO_DB_SQLCommand extends SOSO_Object /*implements SOSO_DB_Abstract */{
	
	public static $theInstances = array();
	public $mDriver;
	public $mLink;
	public $_pdo;
	
	public function __construct() {
		parent::__construct();
	}
	public function __destruct() {
		$this->closeDB();
	}

	/**
	 * �������� �� �������ݿ�����ѡ����Ӧ������
	 * 
	 * @param ���ݿ���������0��ʼ $pDBIndex
	 * @return SOSO_DB_SQLCommand
	 */
	static function &getInstance($pDBIndex=0){
		if (! isset(self::$theInstances[$pDBIndex]) ) {
			$registry = & SOSO_Frameworks_Registry::getInstance();
			$tDatabaseConfig = $registry->get('databases');
			if (is_null($tDatabaseConfig) || !isset($tDatabaseConfig[$pDBIndex])) {
				trigger_error("��ȡ������ʧ��");
				exit(1);
			}
			$tConfig = $tDatabaseConfig[$pDBIndex];
			$tDriver = sprintf("SOSO_DB_Driver_%s",$tConfig['type']);

			if (!class_exists($tDriver) && !class_exists($tConfig['type'])) {
				trigger_error("��֧��{$tDatabaseConfig[$pDBIndex]['type']}���ݿ�",E_USER_ERROR);
				exit(1);
			}
			
			self::$theInstances[$pDBIndex] = new $tDriver($tConfig);
		}
		return self::$theInstances[$pDBIndex];
	}

	/**
	 * ��ȡ���ݰ汾��Ϣ
	 * @return string
	 */
	public function getServerInfo(){
		return '';
	}

	/**
	 * 
	 * @param DBConfig
	 */
	public function resetDB($DBConfig=array()){
		$this->closeDB();
		$this->connectDB();
	}

	public function connectDB(){
		$this->mLink = $this->db_connect();
	}

	public function closeDB(){
		return $this->db_close();
	}

	/**
	 * 
	 * @param sql
	 */
	public function ExecuteInsertQuery($sql){
		if ($this->ExecuteQuery($sql)) {
			return $this->db_insert_id();
		}else{
			return 0;
		}
	}

	/**
	 * 
	 * @param pSql
	 */
	public function ExecuteCountQuery($pSQL){
		$tSQL = preg_replace('~^SELECT\s+(?:.+?)\s+FROM\s+(\w+)(\s*.+?)?~i','SELECT count(*) FROM \\1 \\2',$pSQL);
		return $this->ExecuteScalar($tSQL);
	}

	/**
	 * ��÷ֲ�����
	 * @param string $sql Ҫִ�е�SQL���
	 * @param int $pPageNo ҳ��
	 * @param int $pPageSize ÿҳ
	 * @param string $pResultType
	 */
	public function ExecuteArrayQuery($sql, $pPageNo=0, $pPageSize = 10, $pResultType = 'assoc'){
		if (0 >= $pPageNo || $pPageSize < 1) {
			$result = $this->db_query($sql);
		}else{
			$result = $this->db_query($this->limit($sql,$pPageNo,$pPageSize));
		}
		return $this->db_fetch_all($result,$pResultType);
	}

	/**
	 * ��÷ֲ�����,���ڻ����
	 * @param string $sql Ҫִ�е�SQL���
	 * @param int $pPageNo ҳ��
	 * @param int $pPageSize ÿҳ
	 * @param string $pResultType
	 * @param int $pCacheTime  ����ʱ��
	 * @return  array();
	 */
	public function ExecuteCachedArrayQuery($pCacheTime=86400,$sql, $pPageNo=0, $pPageSize = 10, $pResultType = 'assoc'){
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
		$result = $this->db_query($tSQL);
		if (!$result) {
			return array();
		}
		$tData = $this->db_fetch_all($result,$pResultType);
		$tCache->set($tCacheKey,$tData,$pCacheTime);
		return $tData;
	}

	/**
	 * 
	 * @param string $sql Ҫִ�е�SQL���
	 * @param int $pPageNo ҳ��
	 * @param int $pPageSize ÿҳ
	 * @param string $pResultType
	 */
	public function ExecuteIteratorQuery($sql, $pPageNo=0, $pPageSize = 10){
		if (0 >= $pPageNo || $pPageSize < 1) {
			$result = $this->db_query($sql);
		}else{
			$result = $this->db_query($this->limit($sql,$pPageNo,$pPageSize));
		}
		return new SOSO_DB_Iterator($this,$result);
	}
	/**
	 * ��Ȩģʽ(Delegation Pattern)ʵ��,ʹ��Reflection��չʹ�����׳
	 *
	 * @param string $pMethod
	 * @param mixed $pParams
	 * @return mixed
	 */
	public function __call($pMethod,$pParams=array()){
		if (extension_loaded('Reflection')) {
			$class = new ReflectionClass($this->mDriver);
			$tMethod = $class->getMethod($pMethod);
			if ($tMethod) {
				if ($tMethod->isPublic() && !$tMethod->isAbstract()) {
					$ins = $tMethod->isStatic()?NULL:$this->mDriver;
					return $tMethod->invokeArgs($ins,$pParams);
				}
			}
		}elseif (method_exists($this->mDriver,$pMethod)) {
			return call_user_func_array(array($this->mDriver,$pMethod),$pParams);
		}
		trigger_error("����(<b>{$this->mDriver}::$pMethod</b>)�����ڣ�����ű�",E_USER_ERROR);
	}
	/**
	 * 
	 * @param string $sql
	 */
	public function ExecuteScalar($sql)	{
		$result = $this->ExecuteQuery($sql);
		$tArray = $this->db_fetch_all($result,'num');
		$this->db_free_result($result);
		return $tArray[0];
	}

	public function db_fetch_all($result, $resultType='assoc') {
		$return = array();
		while ($return[] = $this->db_fetch_array($result, $resultType)) {
		}
		$this->db_free_result($result);
		array_pop($return);
		return $return;
	}

	/**
	 * 
	 * @param pQuery
	 */
	public function ExecuteQuery($pQuery){
		return $this->db_query($pQuery);
	}
	public function ignoreCaseInOrderBy($in){
		return $this->ignoreCase($in);
	}

	/**
	 * Quotes a database table which could have space seperating it from an alias, both should be identified seperately
	 * @param      string $table The table name to quo
	 * @return     string The quoted table name
	 **/
	public function quoteIdentifierTable($table) {
		return implode(" ", array_map(array($this, "quoteIdentifier"), explode(" ", $table) ) );
	}
	abstract function getTableFields($pTable);

	abstract function getTablePK($pTable);

	abstract function db_insert_id();

	abstract function db_affected_rows();

	abstract function db_num_rows($pResult) ;

	abstract function db_fetch_array($pResult ,$pResultType = 'both');

	abstract function db_free_result($pResult);

	abstract function db_query($pQuery);

	abstract function db_connect();

	abstract function db_close();

	abstract function db_data_seek($result_identifier, $row_number);
	
	abstract function limit($sql, $pPageNo=0, $pPageSize = 10);
	
	abstract function getLastQuery();
	
	abstract function select_db($pDB);
	
	abstract function setCharset($pCharset='gbk');
	
	/**
	 * ����֧��
	 */
	abstract function getCurrentTransaction();
	
	abstract function beginTransaction();
	
	public abstract function ignoreCase($in);
	public abstract function concatString($s1, $s2);
	public abstract function random($seed = null);
	public abstract function strLength($s);
	public abstract function subString($s, $pos, $len);
	public abstract function toUpperCase($in);
	public abstract function applyLimit(&$sql, $offset, $limit);
	public abstract function quoteIdentifier($text);
	public abstract function useQuoteIdentifier();
}
?>