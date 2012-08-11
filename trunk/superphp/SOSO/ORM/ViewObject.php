<?php
/**
 * SOSO Framework
 * 
 * @category   SOSO
 * @package    SOSO_ORM
 * @author moonzhang
 * @version 1.0 12-五月-2010 14：59
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
	 * 条件提供类
	 * 
	 * @var SOSO_DB_SQLCommand
	 */
	public $mSourceObject;
	/**
	 * 应用目标类
	 * @var SOSO_DB_SQLCommand
	 */
	public $mObjectDestination;
	/**
	 * 数据库操作类
	 *
	 * @var SOSO_DB_SQLCommand
	 */
	public $mSQLCommand;
	/**
	 * 附加条件
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
	 * 对象所处状态
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
	 * @param string $pTableName 数据库表名
	 * @param int $pDBConfig 数据库连接配置文件中配置ID
	 * @param string $pTableName 表名
	 * @param int $pDBConfig 数据库索引
	 * 
	 */
	public function __construct($pTableName, $pDBConfig = 0){
		$this->mTable = $pTableName;
		$this->mSQLCommand = SOSO_DB_SQLCommand::getInstance($pDBConfig);
		$this->mDbIndex = $pDBConfig;
		$this->prepareHashMap();
	}
	/**
	 * 根据自定义参数进行数据库连接
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
	 * @param int $pType 自定义类型
	 * @param SOSO_ORM_TableObject $pObjectSource 条件提供实例
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
			case 0://精确
				foreach($array as $key => $value) {
					$return[] = "{$key} = {$value}";
				}
				break;

			case 1://模糊
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
   * 填充实体类属性值
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
   * 批量填充实体类属性值
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
   * 获取实体类属性值
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
	 * @param int $pPage 当前页
	 * @param int $pPageSize 每页条目
	 * @param string $pOrder 排序方式
	 * @param bool/int $pSmartCode 启用模糊查询
	 * @param string $pColumns 查询列
	 * @param string $pGroupBy 分组
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
	 * 缓存查询结果
	 *
	 * @param int $pCacheTime 缓存时间，秒为单位，默认为一天(86400秒)
	 * @param int $pPage 当前页
	 * @param int $pPageSize 每页条目
	 * @param string $pOrder 排序方式
	 * @param bool/int $pSmartCode 启用模糊查询
	 * @param string $pColumns 查询列
	 * @param string $pGroupBy 分组
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
	 * 修改记录：2009-6-11 jessicaguo 将if (isset($this->mHashMap[$key]))条件改为if (array_key_exists($key, $this->mHashMap))
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
	 * @param boolean $pSmartCode 是否使用模糊查询
	 * @param string $pColumns 要查询列
	 * @param string $pGroupBy 分组
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
	 * 实现观察者模式－Observer::update
	 */
	public function update(SplSubject $obj){
		return $obj;	
	}
	
	/**
	 * 实现观察者模式－Subject::attach
	 * 
	 */
	public function attach(SplObserver $observer){
		if (array_search($observer,$this->observers) === false) {
			array_push($this->observers,$observer);
		}
	}
	
	/**
	 * 实现观察者模式－Subject::detach
	 * 
	 */
	public function detach(SplObserver $observer){
		$index = array_search($observer,$this->observers);
		if ($index !== false) {
			unset($this->observers[$index]);
		}
	}
	/**
	 * 实现观察者模式－Subject::notify
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
