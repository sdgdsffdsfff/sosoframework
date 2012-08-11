<?php
/**
 * SOSO Framework
 * 
 * @category   SOSO
 * @package    SOSO_ORM
 * @author moonzhang
 * @version 1.0 12-����-2010 14��59
 */
class SOSO_ORM_ViewObject /*extends SOSO_Object*/ implements SOSO_Interface_Subject,SOSO_Interface_Observer /*extends ArrayObject implements IteratorAggregate*/ {
	
	private $mTable;
	public $mTableHash;
	public $mTableFieldHash;
	private $mHashMap;
	private $mPrimaryKey = array();
	private $mAutoKey;
	private $mCharset = 'gbk';
	/**
	 * �����ṩ��
	 * 
	 * @var SOSO_DB_SQLCommand
	 */
	public $mSourceObject;
	/**
	 * Ӧ��Ŀ����
	 * @var SOSO_DB_SQLCommand
	 */
	public $mObjectDestination;
	/**
	 * ���ݿ������
	 *
	 * @var SOSO_DB_SQLCommand
	 */
	public $mSQLCommand;
	/**
	 * ��������
	 */
	public $mAdditionalCondition;
	//public $mLastQuery;
	/**
	 * Enter description here...
	 *
	 * @var SOSO_Util_Pagination
	 */
	public $mPagination;
	//public $mTableStatus = array();
	protected $observers = array();
	
	/**
	 * ��������״̬
	 *
	 * @var string
	 */
	private $state;
	private $mDbIndex = 0;
	
	const ACTION_LIST   = "listed";
	const ACTION_SELECT = "selected";
	const ACTION_UPDATE = "updated";
	const ACTION_DELETE = "deleted";
	const ACTION_INSERT = "inserted";
	const ACTION_CACHED_LIST = "cachelisted";
	const ACTION_ON_ITERATE = "iterating";

	/**
	 * @access public
	 * @param string $pTableName ���ݿ����
	 * @param int $pDBConfig ���ݿ����������ļ�������ID
	 * @param string $pTableName ����
	 * @param int $pDBConfig ���ݿ�����
	 * 
	 */
	public function __construct($pTableName, $pDBConfig = 0){
		$this->mTable = $pTableName;
		$this->mSQLCommand = SOSO_DB_SQLCommand::getInstance($pDBConfig);
		$this->mDbIndex = $pDBConfig;
		$this->prepareHashMap();
	}
	/**
	 * �����Զ�������������ݿ�����
	 *
	 * @example SOSO_ORM_TableObject::factory('flight',array('username'=>'user','password'=>'pwd','host'=>'10.1.146.158','database'=>'test'));
	 * @param string $tablename
	 * @param array $config
	 * @return SOSO_ORM_TableObject
	 */
	public static function factory($tablename,$config=array()){
		if (empty($config)) {
			throw new RuntimeException('blank config passed in!',1024);
		}
		$tBlank = array('type'=>'MySQL','useraneme'=>'mysql','password'=>'','database'=>'','host'=>'');
		$config = array_merge($tBlank,$config);
		$registry = SOSO_Frameworks_Registry::getInstance();
		$orig = $databases = $registry->get('databases');
		$len = array_push($databases,$config);
		$registry->set('databases',$databases);
		$product = new self($tablename,$len-1);
		$registry->set('databases',$orig);
		return $product;
	}
	
	public function getTable(){
		return $this->mTable;
	}
	/**
	 * @access private
	 */
	private function prepareHashMap(){
		$tConfig = array('cache_dir'=>'tables/'.$this->mDbIndex,'auto_hash'=>true,'hash_level'=>1,'hash_dirname_len'=>1,'gc_probability'=>0,'cache_time'=>0);
		$tCache = SOSO_Cache::factory('file',$tConfig);
		$tKey = strval($tCache->getKey($this->getTable())); 
		if (!($tFields = $tCache->read($tKey))) {
			$tFields = $this->mSQLCommand->getTableFields($this->getTable());
			$tCache->write($tKey,$tFields);
		}
		
		if ($tFields) {
			$columns = new ArrayObject(array_keys($tFields['Fields']));
			$this->mTableFieldHash = $tFields['Fields'];
			$this->mPrimaryKey = $tFields['Primary'];
			$this->mAutoKey = $tFields['auto'];
			$this->mCharset = $tFields['charset'];
			foreach ($columns as $k=>$v){
				$key = SOSO_Util_Util::magicName($v);			
				$this->{$key} = &$this->mHashMap[$v];
			}
		}
		$this->mSQLCommand->setCharset($this->mCharset);
	}

	/**
	 *@access private
	 * @param int $pType �Զ�������
	 * @param SOSO_ORM_TableObject $pObjectSource �����ṩʵ��
	 * @return array 
	 */
	private function prepareSQL($pType = 0, $pObjectSource = null){
		$ObjectSource = (!empty($pObjectSource))?$pObjectSource:$this;
		$return = array();
		$array = array();

		foreach($ObjectSource->mHashMap as $key => $value) {
			if (isset($ObjectSource->mHashMap[$key])) {
				$type = substr($this->mTableFieldHash[$key]['Type'],0,strpos($this->mTableFieldHash[$key]['Type'],'('));
				if (!is_array($value) && !$this->isDigtial($type)) {
					$array[$key] = "'".mysql_escape_string($value)."'";	
				}else {
					$array[$key] = intval($value);
				}
			}
		}

		switch ($pType) {
			case 0://��ȷ
				foreach($array as $key => $value) {
					$return[] = "{$key} = {$value}";
				}
				break;

			case 1://ģ��
				foreach($array as $key => $value) {
					$type = substr($ObjectSource->mTableFieldHash[$key]['Type'],0,strpos($ObjectSource->mTableFieldHash[$key]['Type'],'('));
					if ($ObjectSource->isDigtial($type)) {
						if(!is_array($value)) {
							$value = explode(',',$value);
						}
						sort($value);
						if(count($value) == 2) {
							$return[] = "{$key} >= {$value[0]} and {$key} < {$value[1]}";
						}
						elseif(count($value) == 1) {
							$return[] = "{$key} = {$value[0]}";
						}
						else {
							$return[] = "{$key} = ".implode(" or {$key} =",$value);
						}
					}
					else {
						$return[] = "{$key} like '%" . addslashes(trim($value,"'")) . "%'";
					}
				}
				break;
		}
		return $return;
	}
		
	public function getCondition($pSmartCode = 0, $pObject) {
		if (function_exists('array_intersect_key')) {
			$array = array_intersect_key($this->mHashMap,array_flip($this->mPrimaryKey));
		}
		else {
			$keys = array_intersect(array_keys($this->mHashMap),$this->mPrimaryKey);
			foreach ($keys as $key) {
				$array[$key] = $this->mHashMap[$key];
			}
		}
		//$lambda = create_function('$a,$b','return ($a && !is_null($b));');
		if ($array && array_reduce($array,create_function('$a,$b','return ($a && !is_null($b));'), true)) {
			$condition_array = $this->prepareSQL(2, $pObject);
		}else {
			$condition_array = $this->prepareSQL($pSmartCode, $pObject);
			if (!empty($pObject->mAdditionalCondition)) {
				array_push($condition_array, "({$pObject->mAdditionalCondition})");
			}
		}
		if (count($condition_array) > 0) {
			return " WHERE " . implode(" and ", $condition_array);
		}
		return '';
	}

	private function generateSql($pOrder = '', $pSmartCode = 1, $pColumns = '*', $pGroupBy = '') {
		$tGroupBy = '';
		$tOrderBy = '';
		$tCondition = $this->getCondition($pSmartCode,$this);
		if (!empty($pGroupBy)) {
			$tGroupBy = "GROUP BY {$pGroupBy}";
		}
		if (!empty($pOrder)) {
			$tOrderBy = "ORDER BY {$pOrder}";
		}
		return array('query'=>"SELECT {$pColumns} FROM {$this->mTable}{$tCondition} {$tGroupBy} {$tOrderBy}",
					 'count_query'=>"SELECT COUNT(".(empty($tGroupBy)?"*":"DISTINCT {$pGroupBy}").") FROM {$this->mTable}{$tCondition}"
				);
	}
	/**
	 * @access public
	 * 
	 * @param pLimit
	 * @param pOrder
	 */
	public function _select($pLimit=1, $pOrder=NULL,$pSmartCode=false){
		if($pLimit > 1) {
			$result = $this->_list($pLimit, 1, $pOrder, $pSmartCode);
		}
		else {
			$result = $this->_list($pLimit, 1, $pOrder, $pSmartCode);
		}
		$this->setState(self::ACTION_SELECT);
		$this->notify();
		if (count($result)>0) {
			return $this->_fill($result[0]);
		}
		return false;
	}
	
	public function getLastQuery(){
		return $this->mSQLCommand->getLastQuery();
	}
	
	/**
	 * @access private
	 * 
	 * @param pType
	 */
	private function isDigtial($pType){
		return in_array(strtolower($pType),array('int','bigint','tinyint','smallint','mediumint','integer','bigint'));
	}
	
  /**
   * ���ʵ��������ֵ
   *
   * @param string $pKey
   * @param mix $pValue
   * @return bool
   */
  public function setObjectData($pKey, $pValue) {
    if (array_key_exists($pKey,$this->mHashMap)) {
      $this->mHashMap[$pKey] = $pValue;
      return true;
    }
    return false;
  }

  /**
   * �������ʵ��������ֵ
   *
   * @param array $pKey
   * @param mix $pValues
   */
  public function fillObjectData($pValues) {
    foreach ($pValues as $key => $value){
      $this->setObjectData($key, $value);
    }
  }

  /**
   * ��ȡʵ��������ֵ
   *
   * @param string $pKey
   * @return mix
   */
  public function getObjectData($pKey) {
    if (array_key_exists($pKey,$this->mHashMap)) {
      return $this->mHashMap[$pKey];
    }
    return null;
  }

	/**
	 * @access public
	 * 
	 * @param int $pPage ��ǰҳ
	 * @param int $pPageSize ÿҳ��Ŀ
	 * @param string $pOrder ����ʽ
	 * @param bool/int $pSmartCode ����ģ����ѯ
	 * @param string $pColumns ��ѯ��
	 * @param string $pGroupBy ����
	 */
	public 	function _list($pPage = 0, $pPageSize = 10, $pOrder = null, $pSmartCode = 1, $pColumns = '*', $pGroupBy = '') {
		if($pPage >= 0 && $pPageSize>=1) {
			$count = $this->_count($pSmartCode,$pColumns,$pGroupBy);
			$this->mPagination = new SOSO_Util_Pagination ($pPage, $pPageSize, $count,true);
		}
		if (!($pPage>0 && $pPageSize==1) && empty($pOrder) && count($this->mPrimaryKey) > 0) {
			$pOrder = implode(",", $this->mPrimaryKey);
		}
		$query = $this->generateSql($pOrder, $pSmartCode, $pColumns, $pGroupBy);
		
		$return = $this->mSQLCommand->ExecuteArrayQuery($query['query'], $pPage, $pPageSize, 'assoc');
		$this->setState(self::ACTION_LIST);
		$this->notify();
		return $return;
	}
	/**
	 * �����ѯ���
	 *
	 * @param int $pCacheTime ����ʱ�䣬��Ϊ��λ��Ĭ��Ϊһ��(86400��)
	 * @param int $pPage ��ǰҳ
	 * @param int $pPageSize ÿҳ��Ŀ
	 * @param string $pOrder ����ʽ
	 * @param bool/int $pSmartCode ����ģ����ѯ
	 * @param string $pColumns ��ѯ��
	 * @param string $pGroupBy ����
	 * @return unknown
	 */
	public function _cached_list($pCacheTime=86400,$pPage = 0, $pPageSize = 10, $pOrder = null, $pSmartCode = 1, $pColumns = '*', $pGroupBy = ''){
		$tKey = $this->mDbIndex.$this->getTable().$pPage. $pPageSize. $pOrder . $pSmartCode . $pColumns . $pGroupBy;
		
		$tCache = SOSO_Cache::factory('file',array('cache_time'=>$pCacheTime,'cache_dir'=>'sql_cache','auto_hash'=>true,'hash_dirname_len'=>1));
		$tCacheKey = $tCache->getKey($tKey);
		$tData = $tCache->read($tCacheKey);
		if (!is_null($tData)) {
			$this->setState(self::ACTION_CACHED_LIST);
			$this->notify();
			return $tData;
		}
		
		$tData = $this->_list($pPage, $pPageSize,$pOrder , $pSmartCode , $pColumns , $pGroupBy);
		$tCache->write($tCacheKey,$tData,$pCacheTime);
		return $tData;
	}
	/**
	 * Enter description here...
	 *
	 * @param int $pPage
	 * @param int $pPageSize
	 * @param mixed $pOrder
	 * @param int $pSmartCode
	 * @param string $pColumns
	 * @param string $pGroupBy
	 * @return array
	 */
	public function _iterate($pPage = 0, $pPageSize = 10, $pOrder = null, $pSmartCode = 1, $pColumns = '*', $pGroupBy = '') {
		if($pPage != 0 && $pPageSize!=1) {
			$count = $this->_count($pSmartCode,$pColumns,$pGroupBy);
			$this->mPagination = new SOSO_Util_Pagination ($pPage, $pPageSize, $count,true);
		}
		if (!($pPage>0 && $pPageSize==1) && empty($pOrder) && count($this->mPrimaryKey) > 0) {
			$pOrder = implode(",", $this->mPrimaryKey);
		}
		$query = $this->generateSql($pOrder, $pSmartCode, $pColumns, $pGroupBy);
		$return = $this->mSQLCommand->ExecuteIteratorQuery($query['query'], $pPage, $pPageSize, 'assoc');
		$this->setState(self::ACTION_ON_ITERATE);
		$this->notify();
		return $return;
	}

	/**
	 * @access public
	 * 
	 * @param pArray
	 * �޸ļ�¼��2009-6-11 jessicaguo ��if (isset($this->mHashMap[$key]))������Ϊif (array_key_exists($key, $this->mHashMap))
	 */
	public function _fill(&$pArray) {
		if (is_array($pArray) && count($pArray) > 0) {
			foreach($pArray as $key => $value) {
				if (array_key_exists($key, $this->mHashMap)){
					$this->mHashMap[$key] = $value;
				}
			}
			return true;
		}
		return false;
	}

	public function _getPagination(){
		return is_object($this->mPagination) ? clone($this->mPagination) : null;
	}

	/**
	 * @access public
	 * 
	 * @param boolean $pSmartCode �Ƿ�ʹ��ģ����ѯ
	 * @param string $pColumns Ҫ��ѯ��
	 * @param string $pGroupBy ����
	 * @return integer
	 */
	public function _count($pSmartCode = 1, $pColumns = '*', $pGroupBy = ''){
		$query = $this->generateSql('', $pSmartCode, $pColumns, $pGroupBy);
		$return = $this->mSQLCommand->ExecuteArrayQuery($query['count_query'], 0, 10, 'num');
		return $return[0][0];
	}

	/**
	 * @access public
	 * @return void
	 */
	public function _reset(){
		$keys = array_keys($this->mHashMap);
		$length = count($keys);
		for($i =0; $i<$length; $i++) {
			$this->mHashMap[$keys[$i]] = null;
		}
		$this->mAdditionalCondition = '';
	}

	/**
	 * 
	 * @param pDeep
	 */
	public function _toDOM($pDeep = 0){
		parent::_toDom();
	}

	public function  getIterator() { 
		return new ArrayIterator($this->mHashMap); 
	}
	
	public function getMapHash(){
		return $this->mHashMap;
	}
	
	/**
	 * alias for notify
	 *
	 */
	public function notifyObservers(){
		$this->notify();
	}
	
	/**
	 * ʵ�ֹ۲���ģʽ��Observer::update
	 */
	public function update(SplSubject $obj){
		return $obj;	
	}
	
	/**
	 * ʵ�ֹ۲���ģʽ��Subject::attach
	 * 
	 */
	public function attach(SplObserver $observer){
		if (array_search($observer,$this->observers) === false) {
			array_push($this->observers,$observer);
		}
	}
	
	/**
	 * ʵ�ֹ۲���ģʽ��Subject::detach
	 * 
	 */
	public function detach(SplObserver $observer){
		$index = array_search($observer,$this->observers);
		if ($index !== false) {
			unset($this->observers[$index]);
		}
	}
	/**
	 * ʵ�ֹ۲���ģʽ��Subject::notify
	 * 
	 */
	public function notify(){
		foreach ($this->observers as $observer){
			$observer->update($this);
		}
	}
	
	/**
	 * 
	 * @param observer
	 */
	public function addObserver(&$observer){
		if ($observer instanceof SOSO_Interface_Observer) {
			$this->observers[] = $observer;	
		}
		return $observer;
	}

	public function getState(){
		return $this->state;
	}

	/**
	 * 
	 * @param state
	 */
	public function setState($state){
		$this->state = $state;
	}
	
	public function _get($pKey){
		if (isset($this->$pKey)) {
			return $this->$pKey;
		}
		$key = SOSO_Util_Util::magicName($pKey);
		if (isset($this->$key)) {
			return $this->$key;
		}
		return null;
	}
	
	public function getCharset(){
		return $this->mCharset;
	}
}
?>
