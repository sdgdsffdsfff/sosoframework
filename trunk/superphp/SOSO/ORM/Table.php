<?php
/**
 * SOSO Framework
 *
 * @category   SOSO
 * @package    SOSO_ORM
 * @copyright  Copyright(c) 2007-2008 Soso.com
 * @author moonzhang
 * @version 1.0
 * @created 15-����-2008 16:59:22
 *
 * @updates:
 * 1������֧��(2010-12-13 17:33)
 * 2.�޸�getSelectClause���Ա�����BUG��col���롡aliasд���ˣ�
 * 3.������ϲ�ѯ֧��
 * 4.prepareMapHash��Ϊbasetable��inline��ʽ���룬����ʹ���ⲿcache�ļ�
 *
 * @todo
 * 3��prepareMapHash��Ϊbasetable��inline��ʽ���룬����ʹ���ⲿcache�ļ�
 * 5��Relations��
 * 6. �ع�_update\_list����
 *
 *
 */
/**
 *
 * @todo �ع�����protected������
 *
 * @updates:
 * 1.�ָ���lastQuery���ԣ���֤ӳ������õ���һ�����Լ�ִ�е�sql���
 * 2.ͳһ��������CRUD���ӿڶ�Ӧ���¼���
 * 3.ӳ����������prepareMapHash����
 *
 */
$path = dirname(__FILE__);
require_once($path.'/../Base/Util/Observable.php');
require_once($path.'/Criteria.php');
require_once($path.'/../DB/PDOSQLCommand.php');
require_once($path.'/../DB/Driver/PDOMySQL.php');
require_once($path.'/MatchMode.php');unset($path);

class SOSO_ORM_Table extends SOSO_Base_Util_Observable implements IteratorAggregate, Countable {

	protected $tableFieldHash;
	protected $hashMap;
	protected $primaryKey = array();
	protected $autoKey;
	protected $charset = 'gbk';
	/**
	 * �����ṩ��
	 *
	 * @var SOSO_ORM_Table
	 */
	public $sourceObject;
	/**
	 * Ӧ��Ŀ����
	 * @var SOSO_ORM_Table
	 */
	public $objectDestination;
	/**
	 * ���ݿ������
	 *
	 * @var SOSO_DB_PDOSQLCommand
	 */
	public $mSQLCommand;
	/**
	 * ��������
	 */
	public $mAdditionalCondition;
	protected $lastQueryParams = array();
	protected $lastQuery;
	protected $lastCountQuery;
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
	private $dbIndex = 0;

	protected $debug = false;

	protected $criteria;
	protected $tableName;
	const ACTION_LIST = "listed";
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
	public function __construct($pTableName, $pDBConfig = 0, $pAutoConnect = false) {
		$this->tableName = $pTableName;
		$this->mSQLCommand = SOSO_DB_PDOSQLCommand::getInstance( $pDBConfig, $pAutoConnect );
		//$this->mSQLCommand->setActive(true);
		$this->dbIndex = $pDBConfig;
		$this->criteria = new SOSO_ORM_Criteria( $pDBConfig );
		$this->criteria->setQuoteFn( 'quoteIdentifier', $this->mSQLCommand );
		$this->prepareHashMap();
		$this->initCriteria( $this->criteria );
		$this->initEvents();
		if(SOSO_Frameworks_Context::getInstance()->get('debug')){
			$this->debug = true;
		}
	}

	private function log($sql=''){
		SOSO_Debugger::instance()->log($sql ? $sql : $this->getLastQuery(), 'SQLQuery');
	}
	/**
	 *
	 * @return SOSO_ORM_Criteria
	 */
	public function getCriteria() {
		return $this->criteria;
	}

	public function initCriteria(SOSO_ORM_Criteria $criteria) {
		$criteria->clear()->setPrimaryTableName( $this->getTable() )->setIgnoreCase( true )->_setFields( $this->tableFieldHash );
		$criteria->setQuoteFn( 'quoteIdentifier', $this->mSQLCommand );
		return $criteria;
	}
	protected function initEvents() {
		$this->addEvents( array('beforelist', 'afterlist', 'listexception', 'beforeinsert', 'insertexception', 'afterinsert', 'updateexception', 'beforeupdate', 'afterupdate', 'deleteexception', 'beforedelete', 'afterdelete' ) );
	}
	/**
	 * �����Զ�������������ݿ�����
	 *
	 * @example SOSO_ORM_Table::factory('flight',array('username'=>'user','password'=>'pwd','host'=>'10.1.146.158','database'=>'test'));
	 * @param string $tablename
	 * @param array $config
	 * @return SOSO_ORM_Table
	 */
	public static function factory($tablename, $config = array()) {
		if(empty( $config )) {
			throw new RuntimeException( 'blank config passed in!', 1024 );
		}
		$tBlank = array('type' => 'MySQL', 'useraneme' => 'mysql', 'password' => '', 'database' => '', 'host' => '' );
		$config = array_merge( $tBlank, $config );
		$registry = SOSO_Frameworks_Registry::getInstance();
		$orig = $databases = $registry->get( 'databases' );
		$len = array_push( $databases, $config );
		$registry->set( 'databases', $databases );
		$product = new self( $tablename, $len - 1 );
		$registry->set( 'databases', $orig );
		return $product;
	}

	/**
	 * @access protected
	 * @todo ȥ����
	 */
	protected function prepareHashMap() {
		if($this->tableFieldHash) {
			$this->criteria->_setFields( $this->tableFieldHash );
			return;
		}
		/* //ȥ��filecache����߼���
		 $tConfig = array('cache_dir' => 'tables/' . $this->dbIndex, 'auto_hash' => true, 'hash_level' => 1, 'hash_dirname_len' => 1, 'gc_probability' => 0, 'cache_time' => 0 );
		 $tCache = SOSO_Cache::factory( 'file', $tConfig );
		 $tKey = strval( $tCache->getKey( $this->getTable() ) );
		 if(!($tFields = $tCache->read( $tKey ))) {
			$this->mSQLCommand->setActive( true );
			$tFields = $this->mSQLCommand->getTableFields( $this->getTable() );
			$tCache->write( $tKey, $tFields );
			}*/

		$this->mSQLCommand->setActive( true );
		$tFields = $this->mSQLCommand->getTableFields( $this->getTable() );
		if($tFields) {
			$columns = new ArrayObject( array_keys( $tFields['Fields'] ) );
			$this->tableFieldHash = array(); //$tFields['Fields'];
			$this->primaryKey = $tFields['Primary'];
			$this->autoKey = $tFields['auto'];
			$this->charset = $tFields['charset'];
			foreach( $columns as $v ) {
				$key = $this->genKey( $v );
				$this->{$key} = &$this->hashMap[$v];
				$field = $this->criteria->isIgnoreCase() ? strtolower( $v ) : $v;
				$this->tableFieldHash[$field] = array('Column' => $v ) + $tFields['Fields'][$v];
			}
		}
		$this->mSQLCommand->setCharset( $this->charset );
	}
	/**
	 *
	 * ������������
	 */
	protected function rebind(){
		$map = $this->getMapHash();
		foreach( array_keys($this->tableFieldHash) as $field ) {
			$key = $this->genKey( $field );
			unset($this->hashMap[$field]);
			$this->{$key} = &$this->hashMap[$field];
			$this->{$key} = $map[$field];
		}
	}
	/**
	 * �ֶ�ӳ�䷽��(Ĭ��)���ɱ�����
	 *
	 * @param string $key
	 */
	protected function genKey($key) {
		return SOSO_Util_Util::magicName( $key );
	}

	/**
	 * @access public
	 *
	 * @param pLimit
	 * @param pOrder
	 */
	public function _select($pLimit = 1, $pSmartCode = false) {
		$criteria = clone($this->criteria);

		if($pSmartCode)
		$criteria->enableLike( is_bool( $pSmartCode ) ? SOSO_ORM_MatchMode::ANYWHERE : $pSmartCode );
		else
		$criteria->disableLike();
		$this->applyPropertyToCriterion( $criteria );
		if(! $criteria->size())
		return false;
		$criteria->setPage( $pLimit, 1 );
		//if(strlen($this->mAdditionalCondition)) $this->criteria->add(Restrictions::sqlRestriction($this->mAdditionalCondition));
		//if(!$criteria->size()) return false;
		return $this->select( $criteria );
	}

	public function _replace($pUpdateValues = null) {
		return $this->_update( $pUpdateValues, true );
	}

	/**
	 *
	 * @param unknown_type $pUpdateValues
	 * @param unknown_type $pReplace
	 */
	public function _update($pUpdateValues = null, $pReplace = false/*,SOSO_ORM_Table $pSourceObject=null*/){
		/**
		 *
		 * Source object for WHERE clause.
		 * @var SOSO_ORM_Table
		 */
		$oSource = clone($this);
		$hash = $this->getMapHash();
		
		foreach( $this->primaryKey as $col ) {
			if(isset( $hash[$col] ) && ! $oSource->criteria->containsKey( $col ))
			$oSource->add( $col, $hash[$col] );
		}
		if($this->mAdditionalCondition)
		$oSource->add( SOSO_ORM_Restrictions::sqlRestriction( $this->mAdditionalCondition ) );

		//$oSource->reset();
		if(! $pUpdateValues) {
			$pUpdateValues = new SOSO_ORM_Criteria();
			$pUpdateValues->setIgnoreCase( $this->criteria->isIgnoreCase() )->setQuoteFn( 'quoteIdentifier', $this->mSQLCommand );
			foreach( $hash as $key => $value ) {
				if(is_null( $value ))
				continue;
				$pUpdateValues->add( $key, $value );
			}
		} else {
			if(! $oSource->getCriteria()->size()) {
				$this->applyPropertyToCriterion( $oSource->criteria );
			}
		}

		return $this->update( $pUpdateValues, $oSource, $pReplace );
	}

	/**
	 * �������ʵ��������ֵ
	 *
	 * @param array $pKey
	 * @param mix $pValues
	 * @return SOSO_ORM_Table
	 */
	public function fillObjectData($pValues) {
		foreach( $pValues as $key => $value ) {
			$this->setObjectData( $key, $value );
		}
		return $this;
	}

	/**
	 * @access public
	 *
	 * @param array
	 * @return SOSO_ORM_Table
	 */
	public function _fill($pArray) {
		if(! is_array( $pArray ) || count( $pArray ) == 0) {
			return false;
		}
		$this->fillObjectData( $pArray );
		return $this;
	}

	/**
	 * ��ȡʵ��������ֵ
	 *
	 * @param string $pKey
	 * @return mix
	 */
	public function getObjectData($pKey) {
		if(array_key_exists( $pKey, $this->hashMap )) {
			return $this->hashMap[$pKey];
		}
		return null;
	}

	/**
	 * ���ʵ��������ֵ
	 *
	 * @param string $pKey
	 * @param mix $pValue
	 * @return bool
	 */
	public function setObjectData($pKey, $pValue) {
		if($this->criteria->isIgnoreCase())
		$pKey = strtolower( $pKey );
		if(array_key_exists( $pKey, $this->tableFieldHash )) {
			$this->hashMap[$this->tableFieldHash[$pKey]['Column']] = $pValue;
			return true;
		}
		return false;
	}
	/**
	 * �Դ������Ķ�ά����($pArray)���в��������������¼�� | �Զ�����ʵ����ӳ�����Ե�ֵ���в��루һ����¼��
	 * @param array $pArray ������Ķ�ά����
	 * @return integer affected_rows or last_insert_id
	 */
	public function _insert($pResult = array()) {
		if ($pResult)
		return $this->save($pResult);

		/*$fileds = $this->tableFieldHash;
		 $hash = $this->getMapHash();
		 foreach($fileds as $col=>$val){
			$column = $val['Column'];
			if(!is_null($hash[$column]) && !$this->criteria->containsKey($col)){
			$this->criteria->add($col,$hash[$column]);
			}
			}*/
		return $this->applyPropertyToCriterion()->save( $this->criteria );
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
	public function _list($pPage = 0, $pPageSize = 10, $pOrder = null, $pSmartCode = 1, $pColumns = '*', $pGroupBy = '') {
		$crit = clone($this->criteria);
		$crit->setLimit( 0 )->setOffset( 0 );
		$crit->clearGroupByColumns()->clearOrderByColumns()->clearSelectColumns();

		if($pPage * $pPageSize != 0) {
			$crit->setPage( $pPage, $pPageSize );
		}
		if(strlen( $pOrder )) {
			$arr = explode( ',', $pOrder );
			foreach( $arr as $orderString ) {
				$orderString = trim($orderString);
				$space = strpos( strtoupper( $orderString ), ' ' . Restrictions::DESC );
				$space2 = strpos( strtoupper( $orderString ), ' ' . Restrictions::ASC );
				$order = $orderString;
				$direction = '';
				if (false !== $space){
					$direction =  Restrictions::DESC;
					$order = substr( $orderString, 0, $space );
				}elseif(false !== $space2){
					$direction =  Restrictions::ASC;
					$order = substr( $orderString, 0, $space2 );
				}

				$direction == Restrictions::DESC ? $crit->orderByDESC( $order ) : $crit->orderByASC( $order );
			}
		}

		if($pSmartCode && ! $crit->isLikeEnabled()) {
			$crit->enableLike( SOSO_ORM_MatchMode::ANYWHERE );
		}

		$crit->setSelect($pColumns);
		/*if(is_string( $pColumns )) {
			$pColumns = explode( ',', $pColumns );
		}
		foreach( $pColumns as $col ) {
			$crit->addSelectColumn( $col );
		}*/
		if($pGroupBy) {
			$pGroupBy = is_array($pGroupBy) ? $pGroupBy : explode(',',$pGroupBy);
			foreach ($pGroupBy as $group) $crit->addGroupByColumn( $group );
		}
		$this->applyPropertyToCriterion( $crit );
		if(strlen( $this->mAdditionalCondition ))
		$crit->add( Restrictions::sqlRestriction( $this->mAdditionalCondition ) );
		return $this->find( $crit );
	}
	//feature start
	/**
	 *
	 * ��ģ����ѯģʽ��
	 * @param unknown_type $mode
	 */
	public function enableLike($mode = SOSO_ORM_MatchMode::ANYWHERE) {
		$this->criteria->enableLike( $mode );
		return $this;
	}
	/**
	 *
	 * Ϊָ�������ñ����������ָ���ڶ���������Ĭ��ʹ�ñ������
	 * @param string $alias ��������
	 * @param string $table ����
	 */
	public function alias($alias, $table = null) {
		$this->criteria->addAlias( $alias, strlen( $table ) ? $table : $this->getTable() );
		return $this;
	}

	/**
	 *
	 * ���÷�ҳ��ҳ��ߴ�
	 * @param unknown_type $page
	 * @param unknown_type $pagesize
	 */
	public function setPage($page, $pagesize = 20) {
		$this->criteria->setPage( $page, $pagesize );
		return $this;
	}

	/**
	 *
	 * <pre>���ϲ�ѯ����
	 * Example��
	 * $table->join('member_sites','memberid');
	 * $talbe->join('member_sites.memberid');
	 * ������������Ҫͬ�ֶεĹ�����ѯ�ȽϷ��㣬����ֶ�����ͬ���������淽��
	 * $table->join('member_sites.memberid','id');
	 * $table->join('member_sites.memberid',array('id'));
	 * $table->join('member_sites',array('id','memberid'));
	 *
	 * ˵��������ڶ����������ַ�������ָ��������ֶΣ���ʱ���������δָ���ֶΣ�Ҳʹ�ô˲�����Ϊ�ֶ�����
	 * ����ڶ�������Ϊ���飬������һ��Ԫ��Ϊ�����ֶ�,�ڶ���Ԫ��Ϊ�������ֶΣ����û�еڶ���Ԫ�أ���ʹ�õ�һ��Ԫ��
	 *
	 * ������һЩ�������
	 * ����Ŀǰjoin֧�ֽ����������
	 * $member->join(array('member_sites','orders','member_ip'),'memberid');
	 * ����������������䣺
	 * FROM orders , member_ip , member WHERE member.memberid=orders.memberid AND member.memberid=member_ip.memberid
	 * a) $member->join(array('member_sites.id','orders.mid','member_ip'),'memberid');
	 * b) $member->join(array('member_sites.id','orders.mid','member_ip.memberid'),array('memberid','memberid','memberid'));
	 * ����������������䣺
	 * FROM member_sites, orders , member_ip , member WHERE member.memberid=member_sites.id AND member.memberid=orders.mid AND m.memberid=member_ip.memberid
	 * ��(b)����£��ڶ�������Ԫ�ظ����������һ����ͬ�����ĳ��Ԫ��ָ���˱���ʹ��ָ��(��.�ֶ�)�����δָ������ʹ�õ�ǰ��
	 * ��(b)������չ���������ɸ�����һЩ�Ĳ�ѯ��䣬��
	 * c) $member->join(array('member_sites.id','orders.mid','member_ip.memberid'),array('memberid','member_sites.id','orders.mid'));
	 * ����������������
	 * WHERE member.memberid=member_sites.id AND member_sites.id=orders.mid AND orders.mid=member_ip.memberid
	 * </pre>
	 *
	 * @todo ����SOSO_ORM_Table����������ϲ�ѯ����Ҫ�����ݵĶ���ı�����joins������
	 * @param string $table
	 * @param mixed $on
	 * @param string $type���������ͣ�����ΪSOSO_ORM_Criteria::LEFT_JOIN,RIGHT_JOIN,INNER_JOIN
	 * @param string $operator
	 * @param boolean $clear ���Ϊ�棬���������join��Ϣ
	 * @throws Exception
	 */
	public function join($table, $on = array(), $type = null, $operator = '=',$clear=false) {
		if ($clear) $this->criteria->clearJoin();
		if($table instanceof SOSO_ORM_Join) {
			$this->criteria->addJoinObject( $table );
			return $this;
		}

		$primaryTable = $this->getTable();

		if(is_array( $table )) {
			$left = $on;
			if(is_string( $on )) {
				$left = array();
				//$on = false === strpos($on,'.') ? $primaryTable . '.' . $on : $on;
				$leftTable = false === strpos($on,'.') ? $primaryTable . '.' . $on : $on;
				foreach( $table as $k => $t ) {
					$left[] = $leftTable;
					if(false === strpos( $t, '.' )) {
						$table[$k] = $t . '.' . $on;
					}
				}
			}
			$this->criteria->addJoin( $left, $table, $type );
			return $this;
		}

		$cnt = is_array( $on ) ? count( $on ) : 0;
		$pos = strpos( $table, '.' );

		if(false !== $pos) {
			$col = strstr( $table, '.' );
			$right = $table;
			if(0 == $cnt) {
				if(!strlen(trim($on))) $on = substr($col,1);
				$on = false === strpos($on,'.') ? $primaryTable . '.' . $on : $on;
				$left = strlen( $on ) ? $on : $primaryTable . $col;
			} else {
				$left = $on = $on[0];
				$on = false === strpos($on,'.') ? $primaryTable . '.' . $on : $on;
			}
		} elseif(1 == $cnt || 0 == $cnt) {
			$cnt && $on = $on[0];
			$left = false === strpos($on,'.') ? $primaryTable . '.' . $on : $on;
			$right = false === $pos ?($table . '.' . $on) : $table;
		} else {
			//$left = $primaryTable . '.' . $on[0];
			$left = false === strpos($on[0],'.') ? $primaryTable . '.' . $on[0] : $on[0];
			$right = false === $pos ?(false === strpos($on[1],'.') ? $table . '.' . $on[1] : $on[1]) : $table;
		}
		$this->criteria->addJoin( $left, $right, $type, $operator );
		return $this;
	}

	/**
	 *
	 * Enter description here ...
	 * @param unknown_type $table
	 * @param unknown_type $on
	 * @throws Exception
	 */
	public function leftJoin($table, $on = array(), $operator = '=') {
		return $this->join( $table, $on, SOSO_ORM_Criteria::LEFT_JOIN, $operator );
	}

	public function rightJoin($table, $on = array(), $operator = '=') {
		$this->join( $table, $on, SOSO_ORM_Criteria::RIGHT_JOIN, $operator );
		return $this;
	}
	
	public function innerJoin($table, $on = array(), $operator = '=') {
		$this->join( $table, $on, SOSO_ORM_Criteria::INNER_JOIN, $operator );
		return $this;
	}
	/**
	 *
	 * �����ֶα���
	 * @param unknown_type $column
	 * @param unknown_type $as
	 */
	public function columnAlias($column, $as) {
		if($this->criteria->isIgnoreCase()) {
			$column = strtolower( $column );
			$as = strtolower( $as );
		}
		$this->criteria->addAsColumn( $as, $column );
		return $this;
	}

	/**
	 *
	 * ��������
	 * Update��
	 * 	���$p1Ϊ�ֶ��������Ѿ������˱�����ǰ�����ڽ���add֮ǰ�Ƚ�����columnAlias���ã�����ʹ��ԭ�ֶ������в�ѯ
	 * @param string|Criterion $p1���ֶ��������ú�������Criterion
	 * @param mixed $value			��Ӧ��ֵ
	 * @param string $comparison
	 * @return SOSO_ORM_Table
	 */
	public function add($p1, $value = null, $comparison = null) {
		if(is_array( $p1 )) {
			foreach( $p1 as $k => $v ) {
				$alias = $this->criteria->getColumnForAlias($k);
				false !== $alias && $k = $alias;
				$this->criteria->add( $k, $v, $comparison );
			}
			return $this;
		}

		$alias = $this->criteria->getColumnForAlias($p1);
		false !== $alias && $p1 = $alias;
		$this->criteria->add( $p1, $value, $comparison );
		return $this;
	}

	/**
	 *
	 * Enter description here ...
	 * @param mixed $p1
	 * @param mixed $value
	 * @param string $comparison
	 * @return SOSO_ORM_Table
	 */
	public function addOr($p1, $value = null, $comparison = null) {
		if(! $this->criteria->size())
		$this->applyPropertyToCriterion();
		$alias = $this->criteria->getColumnForAlias($p1);
		false !== $alias && $p1 = $alias;
		$this->criteria->addOr( $p1, $value, $comparison );
		return $this;
	}
	public function addHaving($name, $value = null, $cmp = null) {
		if($name instanceof Criterion) {
			$this->criteria->addHaving( $name );
			return $this;
		}
		$this->criteria->addHaving( SOSO_ORM_Restrictions::getNewCriterion( $name, $value, $cmp ) );
		return $this;
	}
	/**
	 *
	 * Enter description here ...
	 * @param mixed $p1
	 * @param mixed $value
	 * @param string $comparison
	 * @return SOSO_ORM_Table
	 */
	public function addAnd($p1, $value = null, $comparison = null) {
		if(! $this->criteria->size())
		$this->applyPropertyToCriterion();
		$alias = $this->criteria->getColumnForAlias($p1);
		false !== $alias && $p1 = $alias;
		$this->criteria->addAnd( $p1, $value, $comparison );
		return $this;
	}
	/**
	 *
	 * Enter description here ...
	 * @param unknown_type $groupBy
	 * @return SOSO_ORM_Table
	 */
	public function groupBy($groupBy) {
		$this->criteria->addGroupByColumn( $groupBy );
		return $this;
	}
	/**
	 *
	 *���������򣬿��Զ������
	 * @param string $orderBy�������ֶ�
	 * @param string $order��������ʽ
	 * @param boolean $clear  ���Ϊ�棬���������orderBy��Ϣ
	 * @return SOSO_ORM_Table
	 */
	public function orderBy($orderBy, $order = Restrictions::ASC,$clear=false) {
		if (!strlen($orderBy) || !$orderBy) return $this;
		if($clear) $this->criteria->clearOrderByColumns();
		$order == Restrictions::DESC ? $this->criteria->orderByDESC( $orderBy ) : $this->criteria->orderByASC( $orderBy );
		return $this;
	}
	public function in($column, $list) {
		$param = null;
		if ($list instanceof SOSO_ORM_Table){
			$list->apply();
			$crit = $list->getCriteria();
			$selectColumns = $crit->getSelectColumns();
			$func = create_function('$a','return trim($a)!="*";');
			if (!is_array($selectColumns)) $selectColumns = array_filter(explode(',',$selectColumns),$func);
			if (count($selectColumns) != 1){
				throw new Exception('Operand should contain 1 column(s)', 21000);
			}
			$sql = $list->prepareSelectSQLQuery($crit);
			if($crit->getLimit() || $crit->getOffset()) {
				$this->mSQLCommand->applyLimit( $sql, $crit->getOffset(), $crit->getLimit() );
			}
			$param = $list->getParams()->getArrayCopy();
			unset($list);
			$list = $sql;
		}
		$this->criteria->add( Restrictions::in( $column, $list ,$param) );
		return $this;
	}

	/**
	 *
	 * ��ò�ѯ���
	 * @param SOSO_ORM_Criteria $crit
	 */
	protected function prepareSelectSQLQuery(SOSO_ORM_Criteria $crit) {
		$this->lastQueryParams = array();
		//$selectClause = $this->getSelectClause( $crit );
		//$fromClause = $this->getFromClause( $crit );
		$crit->setPrimaryTableName($this->getTable());
		$selectClause = $crit->getSelectClause();
		$fromClause = $crit->getFromClause();

		//$clauses = $this->getJoinClause( $crit, $fromClause );
		$clauses = $crit->getJoinClause($fromClause);
		$joinClause = $clauses['join'];
		$whereClause = $clauses['where'];
		$fromClause = $clauses['from'];
		$crit->setFrom( $fromClause );
		//$orderByClause = $this->getOrderByClause( $crit );
		$orderByClause = $crit->getOrderByClause();

		//$groupByClause = $this->getGroupByClause( $crit );
		$groupByClause = $crit->getGroupByClause();
		//$ignoreCase = $crit->isIgnoreCase();

		$selectModifiers = $crit->getSelectModifiers();

		$from = implode( ", ", $fromClause );
		$from .= $joinClause ? ' ' . implode( ' ', $joinClause ) : '';
		//$from = implode(', ',array_unique($fromClause));


		$res = array();
		$havingString = null;
		$having = $crit->getCriterionPairs( $crit->getHaving() );
		/**
		 * update:
		 * 		ʹ���µı���$havingParam����$res,�����having�Ӿ�ʱ�����������bug
		 */
		$havingParam = array();
		$having ? list( $havingString, $havingParam ) = $having : null;

		//$this->lastQueryParams = array_merge($this->lastQueryParams,$res);
		$this->lastQueryParams = array_merge( $res, $this->lastQueryParams );
		list( $where, $res ) = $crit->getCriterionPairs( $crit );
		$whereClause = array_merge( $whereClause, $where );
		$this->lastQueryParams = array_merge( $this->lastQueryParams, $res ,$havingParam);

		//$this->lastQueryParams = is_array( $this->lastQueryParams ) ? SOSO_Util_Util::arrayFlatten( $this->lastQueryParams ) : array($res );
		$this->lastQueryParams = is_array( $this->lastQueryParams ) ? $this->lastQueryParams : array($res );
		$this->lastQueryParams = SOSO_ORM_Criteria::paramFilter( $this->lastQueryParams );
		//������˺�������������⣬�ᵼ��bind�쳣
		$this->lastQueryParams = array_merge( array(), $this->lastQueryParams );

		$sql = "SELECT " .($selectModifiers ? implode( " ", $selectModifiers ) . " " : "") . implode( ", ", $selectClause ) . " FROM " . $from .($whereClause ? " WHERE " . implode( " AND ", $whereClause ) : "") .($groupByClause ? " GROUP BY " . implode( ",", $groupByClause ) : "") .($havingString ? " HAVING " . $havingString : "") .($orderByClause ? " ORDER BY " . implode( ",", $orderByClause ) : "");

		$tSimpleCount = !($groupByClause || $havingString || in_array( Restrictions::DISTINCT, $selectModifiers ));
		if($tSimpleCount) {
			$this->lastCountQuery = "SELECT COUNT(*) FROM " . $from .($whereClause ? " WHERE " . implode( " AND ", $whereClause ) : "") .($orderByClause ? " ORDER BY " . implode( ",", $orderByClause ) : "");
		} else {
			//Updates:��select * չ����ĳ��ָ���������������1060���� @2011-02-10 11:50
			$sql2 = $sql;
			if($selectClause[0] == '*') {
				$sql2 = substr( $sql, strpos( $sql, ' FROM' ) );
				if($this->primaryKey) {
					$key = $this->primaryKey;
					if(is_array( $this->primaryKey ))
					$key = implode( ',', $key );
				} else {
					$keys = array_keys( $this->hashMap );
					$key = $keys[0];
				}
				$sql2 = 'SELECT ' . $this->getTable() . '.' . $key . $sql2;
			}
			$this->lastCountQuery = "SELECT COUNT(*) FROM(" . $sql2 . ") JuStFoRCnt";
		}

		return $sql;
	}

	/**
	 *
	 * Enter description here ...
	 * @param boolean $flag
	 * @return SOSO_ORM_Table
	 */
	public function setIgnoreCase($flag) {
		$this->criteria->setIgnoreCase( $flag );
		return $this;
	}

	/**
	 *
	 * ���ò�ѯ�ֶ�
	 * �����ѯ�ֶ��зǸ������ţ�columns���������鷽ʽ���ݣ������Ӱ���ѯ���
	 * @param string|array $columns
	 * @param bool $clear  �����Ƿ������ǰ��select�ֶ�
	 */
	public function setSelect($columns,$clear=true) {
		$this->criteria->setSelect($columns,$clear);
		return $this;
	}
	/**
	 *
	 * �б���
	 * @param SOSO_ORM_Criteria $crit
	 */
	public function find(SOSO_ORM_Criteria $crit = null) {
		$this->clearQuery();
		$crit = $crit ? clone($crit) : $this->criteria;
		if($this->fireEvent( 'beforelist', $crit, $this ) === false) {
			return false;
		}

		$this->mSQLCommand->setActive( true );
		$sql = $this->prepareSelectSQLQuery( $crit );

		if($crit->getLimit() || $crit->getOffset()) {
			$this->mSQLCommand->applyLimit( $sql, $crit->getOffset(), $crit->getLimit() );
		}
		if(0 != $crit->getLimit() * $crit->getPage()) {
			$count = $this->doCount( $this->lastCountQuery );
			if(!class_exists('SOSO_Util_Pagination',false))
			require_once(dirname(dirname(__FILE__)).'/Util/Pagination.php');
			$this->mPagination = new SOSO_Util_Pagination( $crit->getPage(), $crit->getLimit(), $count, true );
		}

		try {
			$this->checkCharset();
			$this->lastQuery = $sql;
			if($this->debug) $this->log();
			$stmt = $this->mSQLCommand->prepare( $sql );
			$stmt->execute( $this->lastQueryParams );
		} catch( Exception $e ) {
			$this->fireEvent( 'listexception', $e->getMessage(), $this->getLastQuery(), $sql, $this->lastQueryParams );
			return array();
		}
		if($crit->isUseTransaction())
		$this->beginTransaction();

		try {
			$result = $stmt->fetchAll( PDO::FETCH_ASSOC );
		} catch( Exception $e ) {
			if($crit->isUseTransaction()) {
				$this->rollback();
			}
			return array();
		}

		if($crit->isUseTransaction())
		$this->commit();
		$this->fireEvent( 'afterlist', $result, $this );
		$this->setState( self::ACTION_LIST );
		$this->notify();
		return $result;
	}


	protected function checkCharset() {
		$tCharset = $this->getCharset();
		if(strtolower( $tCharset ) != strtolower( $this->mSQLCommand->getCharset() )) {
			$this->mSQLCommand->setCharset( $this->getCharset( $tCharset ) );
		}
		return $this;
	}
	/**
	 *
	 * ʹ������ģʽ������Ϊ�������CRUD������ʹ������
	 */
	public function useTransaction($flag = true) {
		$this->criteria->setUseTransaction( $flag );
		return $this;
	}

	public function apply() {
		return $this->applyPropertyToCriterion();
	}
	/**
	 *
	 * ��$table->mColName���Ը���ֵת��Ϊ����������ڴ��ֶε�������������
	 */
	protected function applyPropertyToCriterion(SOSO_ORM_Criteria $criteria = null) {
		$criteria = $criteria ? $criteria : $this->criteria;
		foreach( $this->hashMap as $k => $v ) {
			$key = $criteria->isIgnoreCase() ? strtolower( $k ) : $k;
			//var_dump(array($key,$v,$this->criteria->containsKey($key)));
			if(is_null( $v ) || $criteria->containsKey( $key )) {
				continue;
			}
			$criteria->add( $key, $v );
		}
		if(strlen( $this->mAdditionalCondition ))
		$criteria->add( SOSO_ORM_Restrictions::sqlRestriction( $this->mAdditionalCondition ) );
		return $this;
	}

	private function clearQuery(){
		$this->lastCountQuery = $this->lastQuery = $this->lastQueryParams = '';
		return $this;
	}
	/**
	 *
	 * @todo transaction support
	 * ���·���
	 * @param {SOSO_ORM_Criteria|Array} $updateValues��Ҫ���µ�ֵ,����Ϊ����Ҳ������criteria����,����update����set����.
	 * @param {SOSO_ORM_Criteria|SOSO_ORM_Table|Array} $pSourceObject Դ�������ṩ�˲�������ʹ�ô˲������ɲ�ѯ������
	 * �����ִ�ж������Ա�������κ�Ӱ�죬��ͬʱִ�ж�����������Ҳ��������(�ڿ����Ƿ�Ҫ�������ϲ���)
	 * @return bool ִ��ʧ�ܷ���false�����򷵻�Ӱ���̽����
	 */
	public function update($pUpdateValues=null, $pSourceObject = null, $pReplace = false) {
		$this->clearQuery();
		if(! $pUpdateValues)
		return false;
		$oSourceObject =($pSourceObject instanceof SOSO_ORM_Table) ? $pSourceObject : $this;
		$cmd = $this->getCommand(); 
		
		if(is_array( $pUpdateValues )) {
			$crit = new SOSO_ORM_Criteria( $this->dbIndex );
			$crit->setIgnoreCase( $this->criteria->isIgnoreCase() )->setQuoteFn( 'quoteIdentifier', $cmd );

			foreach( $pUpdateValues as $key => $val ) {
				if($this->criteria->isIgnoreCase())
				$key = strtolower( $key );
				if($val instanceof Criterion){
					$crit->add( $val );
					continue;
				}elseif(! array_key_exists( $key, $this->tableFieldHash )) {
					continue;
				}
				$crit->add( $key, $val, SOSO_ORM_Restrictions::EQUAL );
			}
			$pUpdateValues = $crit;
		} elseif($pUpdateValues instanceof SOSO_ORM_Table) {
			$pUpdateValues->applyPropertyToCriterion();
			$pUpdateValues = $pUpdateValues->getCriteria();
		}

		if($pUpdateValues instanceof SOSO_ORM_Criteria) {
			if(! $pUpdateValues->size()) {
				return false;
			}
			list( $setClause, $tParams ) = $oSourceObject->getCriteria()->getCriterionPairs( $pUpdateValues, true );
			/*if($tParams) {
				$tParams = SOSO_Util_Util::arrayFlatten( $tParams );
				}*/
		} else {
			return false;
		}

		if($this->fireEvent( 'beforeupdate', $pUpdateValues, $oSourceObject, $this ) === false) {
			return false;
		}

		$tCondition = $oSourceObject->getCriteria();
		$tCondition->setDbIndex( $this->getDbIndex() );

		if(is_array( $pSourceObject )) {
			$this->initCriteria( $tCondition )->setIgnoreCase( $this->criteria->isIgnoreCase() );
			foreach( $pSourceObject as $key => $val ) {
				if($this->criteria->isIgnoreCase())
				$key = strtolower( $key );
				if(! array_key_exists( $key, $this->tableFieldHash ))
				continue;
				$tCondition->add( $key, $val );
			}
		} elseif($pSourceObject instanceof SOSO_ORM_Criteria || $pSourceObject instanceof Criterion) {
			$tCondition = $pSourceObject;
		}

		$tAffectedRows = 0;

		list( $whereClause, $this->lastQueryParams ) = $this->getCriteria()->getCriterionPairs( $tCondition );

		//$this->lastQueryParams = SOSO_Util_Util::arrayFlatten( array_merge( $tParams, $pReplace ? array() : $this->lastQueryParams ) );
		$this->lastQueryParams = array_merge( $tParams, $pReplace ? array() : $this->lastQueryParams );
		$this->lastQueryParams = SOSO_ORM_Criteria::paramFilter( $this->lastQueryParams );
		$operate = $pReplace ? 'REPLACE' : 'UPDATE';
		$sql = "$operate " . $this->getTable() . " SET ";
		$sql .= join( " , ", $setClause );
		!$pReplace && $sql .= $whereClause ? " WHERE " . join( " AND ", $whereClause ) : '';
		if($tCondition->getOffset() || $tCondition->getLimit()) {
			$this->mSQLCommand->applyLimit( $sql, $tCondition->getOffset(), $tCondition->getLimit() );
		}

		$this->lastQuery = $sql;

		try {
			if($this->debug) $this->log();
			$this->checkCharset();
			$cmd = $this->getCommand();
			$stmt = $cmd->prepare( $sql );
			$stmt->execute( $this->lastQueryParams );
			$tAffectedRows = $stmt->rowCount();
		} catch( Exception $e ) {
			$this->fireEvent( 'updateexception', $e->getMessage(), $this->getLastQuery(), $sql, $this->lastQueryParams );
			return false;
		}

		$this->fireEvent( 'afterupdate', $tAffectedRows, $this );
		$this->setState( self::ACTION_UPDATE );
		$this->notify();
		return $tAffectedRows;
	}

	/**
	 *
	 * Enter description here ...
	 * @param SOSO_ORM_Criteria||Array $crit
	 * @param {Array||SOSO_ORM_Criteria} $pCondition �����������紫�������ݣ���ɶ�ԭ���������в��䡢���ǣ���criteria���������criteria�ṩ���������в�ѯ
	 * �������criteria����ʱҪע�⣺�����ѯ�ɹ����ձ����criteria��գ�ʹ�ò�ѯ���ļ�¼���л������ԭ��criteria���ݱ��ֲ���
	 */
	public function select($crit = null) {
		$this->clearQuery();
		if($crit instanceof SOSO_ORM_Criteria) {
			$criteria = clone($crit);
		} else {
			$criteria = clone($this->criteria);

			if(is_array( $crit )) {
				foreach( $crit as $key => $val ) {
					if($criteria->isIgnoreCase())
					$key = strtolower( $key );
					if(! array_key_exists( $key, $this->tableFieldHash ))
					continue;
					$criteria->add( $key, $val, SOSO_ORM_Restrictions::EQUAL );
				}
			}
			if(! $criteria->size()) {
				return false;
			}
		}

		$criteria->setLimit( 1 )->clearSelectColumns()->addSelectColumn( '*' );
		//$criteria->setLimit( 1 );

		$result = $this->find( $criteria );
		if($result) {
			$this->reset();
			$result = $result[0];
			$tIsIgnore = $criteria->isIgnoreCase();
			$primaryKeys = $this->primaryKey;
			if($primaryKeys) {
				$this->initCriteria( $this->criteria );
				foreach( $result as $key => $val ) {
					$this->setObjectData( $key, $val );
				}
				foreach( $primaryKeys as $v ) {
					$k = $tIsIgnore ? strtolower( $v ) : $v;
					$this->criteria->add( $k, $this->hashMap[$v] );
				}
			} else {
				$this->fillObjectData( $result );
			}
		}
		return !!$result;
	}

	public function count() {
		return $this->_count();
	}
	/**
	 *
	 * ��ü�¼����
	 * @param string $sql
	 */
	protected function doCount($sql = '') {
		if(strlen( $sql )) {
			$this->lastCountQuery = $sql;
		} else {
			$this->lastQueryParams = array();
			$this->prepareSelectSQLQuery( $this->criteria );
			$sql = $this->lastCountQuery;
		}

		$this->checkCharset();
		$command = $this->getCommand();
		$command->setActive( true );
		$cnt = (int)$command->ExecuteScalar( $sql, $this->lastQueryParams );
		return $cnt;
	}
	/**
	 *
	 * ���淽��
	 * @param Array|SOSO_ORM_Criteria $criteria
	 * @todo transaction support
	 */
	public function save($pData = null/*SOSO_ORM_Criteria $criteria=null*/) {
		$this->clearQuery();
		if($pData && is_array($pData) || $pData instanceof Iterator ) {
			if (!is_array(current($pData))) $pData=array($pData);
			$ret = array();

			$criteria = clone($this->getCriteria());
			$criteria->clear()->setIgnoreCase( $this->criteria->isIgnoreCase() )->setUseTransaction( $this->criteria->isUseTransaction() );
			$tUseTrans = $this->criteria->isUseTransaction();
			if($tUseTrans){
				$this->beginTransaction();
				$this->criteria->setUseTransaction(false);
			}

			foreach( $pData as $data ) {
				$criteria->clearMap();
				foreach( $data as $column => $value ) {
					if($criteria->isIgnoreCase())
					$column = strtolower( $column );
					if(array_key_exists( $column, $this->tableFieldHash ))
					$criteria->add( $column, $value );
				}
				$ret[] = $tInsertId = $this->save( $criteria );
				if(! $tInsertId)
				break;
			}

			if($tUseTrans){
				$this->criteria->setUseTransaction(true);
				if($tInsertId) $this->commit();
				else {
					$this->rollback();
					$ret = array();	
				}
			}

			$ret = array_filter($ret);
			return $ret; //todo : ��Ҫ����ȷ���Ƿ���ִ�н��������false
		}
		
		$criteria = null;
		if($pData instanceof SOSO_ORM_Criteria) $criteria = $pData;
		if(! $criteria) {
			$this->applyPropertyToCriterion();
			$criteria = $this->criteria;
		}

		if(false === $this->fireEvent( 'beforeinsert', $criteria, $this )) {
			return false;
		}

		$sets = $vals = array();
		if(! $criteria->size()) {
			//���ֱ�ӵ���save������_insert����Ҫʹ��apply��mColumn����ֵת��Ϊ��ֵ���
			$this->fireEvent( 'insertexception', 'No key->values specified.', '' );
			return false;
		}

		$tSQLCommand = $this->getCommand();
		$tSQLCommand->setActive( true );

		foreach( $criteria as $criterion ) {
			$sets[] = $criterion->toSqlString( $criteria, true );
			$vals[] = $criterion->getTypedValues( $criteria );
		}

		//$vals = SOSO_Util_Util::arrayFlatten( $vals );
		$this->lastQueryParams = $vals = SOSO_ORM_Criteria::paramFilter( $vals );
		$sql = "INSERT INTO " . $tSQLCommand->quoteIdentifierTable( $this->getTable() );
		$sql .= " SET " . implode( ",", $sets );

		try {
			$this->checkCharset();
			$this->lastQuery = $sql;
			if($this->debug) $this->log();
			$stmt = $tSQLCommand->prepare( $sql );
			$res = $stmt->execute( $this->lastQueryParams );

			if(! $res) {
				$this->fireEvent( 'insertexception', $stmt->errorInfo(), $this->getLastQuery(), $this );
			}
		} catch( Exception $e ) {
			$this->fireEvent( 'insertexception', $e->getMessage(), $this->getLastQuery(), $sql, $this->lastQueryParams );
			return false;
		}

		$tID = $tSQLCommand->getLastInsertID();
		if($res && $tID && $this->autoKey) {
			$this->hashMap[$this->autoKey] = $tID;
		}

		$this->fireEvent( 'afterinsert', $res, $tID, $this );
		$this->setState( self::ACTION_INSERT );
		$this->notify();
		return $tID ? $tID : $res;
	}

	public function delete(SOSO_ORM_Criteria $pCondition = null) {
		$this->clearQuery();
		if(! $pCondition) {
			if(! $this->criteria->size() )
			$this->applyPropertyToCriterion();
			$pCondition = $this->criteria;
		}
		if(false === $this->fireEvent( 'beforedelete', $pCondition, $this )) {
			return false;
		}
		if(! $pCondition->size() && !$this->criteria->getJoins() ) {
			$this->fireEvent( 'deleteexception', 'Please use deleteAll instead', '' );
			return false;
		}

		$this->mSQLCommand->setActive( true );
		list( $where, $this->lastQueryParams ) = $this->criteria->getCriterionPairs( $pCondition );
		$sql = "DELETE FROM " . $this->getCommand()->quoteIdentifierTable( $this->getTable() );
		$sql .= " WHERE " . implode( " AND ", $where );

		try {
			$this->checkCharset();
			$this->lastQuery = $sql;
			if($this->debug) $this->log();
			$this->mSQLCommand->prepare( $sql );
			$tRowCount = $this->mSQLCommand->execute( $this->lastQueryParams );
			$this->fireEvent( 'afterdelete', $tRowCount, $this );
		} catch( Exception $e ) {
			$this->fireEvent( 'deleteexception', $e->getMessage(), $this->getLastQuery(), $sql, $this->lastQueryParams );
			return false;
		}

		return $tRowCount;
	}

	public function deleteAll() {
		$this->clearQuery();
		if(false === $this->fireEvent( 'beforedelete', null, $this )) {
			return false;
		}

		$this->mSQLCommand->setActive( true );
		$sql = "DELETE FROM " . $this->getCommand()->quoteIdentifierTable( $this->getTable() );

		try {
			$this->checkCharset();
			$this->lastQuery = $sql;
			if($this->debug) $this->log();
			$this->mSQLCommand->prepare( $sql );
			$tRowCount = $tRowCount = $this->mSQLCommand->execute();
			$this->fireEvent( 'afterdelete', $tRowCount, $this );
		} catch( Exception $e ) {
			$this->fireEvent( 'deleteexception', $e->getMessage(), $this->getLastQuery(), $sql, $this->lastQueryParams );
			return false;
		}
		return $tRowCount;
	}

	public function truncate() {
		$this->clearQuery();
		if(false === $this->fireEvent( 'beforetruncate', null, $this )) {
			return false;
		}

		$this->mSQLCommand->setActive( true );
		$sql = "TRUNCATE " . $this->getCommand()->quoteIdentifierTable( $this->getTable() );

		try {
			$this->lastQuery = $sql;
			if($this->debug) $this->log();
			$stmt = $this->mSQLCommand->prepare( $sql );
			$tResult = $stmt->execute();
			$this->fireEvent( 'aftertruncate', $tResult, $this );
			return $tResult;
		} catch( Exception $e ) {
			$this->fireEvent( 'truncateexception', $e->getMessage(), $this->getLastQuery(), $sql, $this->lastQueryParams );
			return false;
		}
	}
	public function getParams() {
		return new ArrayIterator($this->lastQueryParams?$this->lastQueryParams:array());
	}

	public function clear() {
		$this->criteria->clear();
		$this->criteria->setPrimaryTableName( $this->getTable() );
		$this->setIgnoreCase( true );
		return $this;
	}
	//feature end


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
	 * @return array
	 */
	public function _cached_list($pCacheTime = 86400, $pPage = 0, $pPageSize = 10, $pOrder = null, $pSmartCode = 1, $pColumns = '*', $pGroupBy = '') {
		$tKey = $this->dbIndex . $this->getTable() . $pPage . $pPageSize . $pOrder . $pSmartCode . $pColumns . $pGroupBy;

		$tCache = SOSO_Cache::factory( 'file', array('cache_time' => $pCacheTime, 'cache_dir' => 'sql_cache', 'auto_hash' => true, 'hash_dirname_len' => 1 ) );
		$tCacheKey = $tCache->getKey( $tKey );
		$tData = $tCache->read( $tCacheKey );
		if(! is_null( $tData )) {
			$this->setState( self::ACTION_CACHED_LIST );
			$this->notify();
			return $tData;
		}

		$tData = $this->_list( $pPage, $pPageSize, $pOrder, $pSmartCode, $pColumns, $pGroupBy );
		$tCache->write( $tCacheKey, $tData, $pCacheTime );
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
		$crit = clone($this->criteria);
		$crit->setLimit( 0 )->setOffset( 0 );
		$crit->clearGroupByColumns()->clearOrderByColumns()->clearSelectColumns();

		if($pPage * $pPageSize != 0) {
			$crit->setPage( $pPage, $pPageSize );
		}
		if(strlen( $pOrder )) {
			$arr = explode( ',', $pOrder );
			foreach( $arr as $orderString ) {
				$direction = strpos( strtoupper( $orderString ), ' ' . Restrictions::DESC ) !== false ? Restrictions::DESC : Restrictions::ASC;
				$space = strpos( $orderString, ' ' );
				$order = $space !== false ? substr( $orderString, 0, $space ) : $orderString;
				$direction == Restrictions::DESC ? $crit->orderByDESC( $order ) : $crit->orderByASC( $order );
			}
		}

		if($pSmartCode && ! $crit->isLikeEnabled()) {
			$crit->enableLike( SOSO_ORM_MatchMode::ANYWHERE );
		}

		/*if(is_string( $pColumns )) {
			$pColumns = explode( ',', $pColumns );
		}
		foreach( $pColumns as $col ) {
			$crit->addSelectColumn( $col );
		}*/
		$crit->setSelect($pColumns);
		if($pGroupBy) {
			$crit->addGroupByColumn( $pGroupBy );
		}
		$this->applyPropertyToCriterion( $crit );
		if(strlen( $this->mAdditionalCondition ))
		$crit->add( Restrictions::sqlRestriction( $this->mAdditionalCondition ) );

		if($this->fireEvent( 'beforelist', $crit, $this ) === false) {
			return false;
		}
		$this->mSQLCommand->setActive( true );
		$query = $this->prepareSelectSQLQuery( $crit );
		if($crit->getLimit() || $crit->getOffset()) {
			$this->mSQLCommand->setActive( true );
			$this->mSQLCommand->applyLimit( $query, $crit->getOffset(), $crit->getLimit() );
		}
		try {
			$this->checkCharset();
			$this->lastQuery = $query;
			if($this->debug) $this->log();
			$it = $this->mSQLCommand->prepare( $query );
			$it->setFetchMode( PDO::FETCH_ASSOC );
			$it->execute( $this->lastQueryParams );
		} catch( Exception $e ) {
			$this->fireEvent( 'listexception', $e->getMessage(), $this->getLastQuery(), $query, $this->lastQueryParams );
			return array();
		}
		$this->fireEvent( 'afterlist', $it, $this );
		$this->setState( self::ACTION_LIST );
		$this->notify();
		return $it;
	}
	
	/**
	 * ˢ�·�����������ڷ��������ļ�¼���������Ӧ�ֶΣ����򣬲���һ���µļ�¼�����������������������Ѹ�ֵ
	 * @see _replace
	 */
	public function _refresh() {
		$hash = $this->getMapHash();
		$crit = clone($this->criteria);
		$source = clone($this);
		//$this->criteria->clearMap();
		$source->getCriteria()->clearMap();
		
		foreach ($this->getPrimaryKey() as $key){
			if ($crit->containsKey($key)) $source->add($crit->getCriterion($key));
			!is_null($this->hashMap[$key]) && $source->add($key,$this->hashMap[$key]) && $crit->add($key,$this->hashMap[$key]);
		}
		
		$this->applyPropertyToCriterion($crit);
		if (0 == $source->getCriteria()->size()){
			$res = $this->save($crit);
			//$this->criteria->merge($crit);
			return $res;
		}
		
		if ($source->select()){
			$res = $this->update($crit,$source);
			//$this->criteria->merge($crit);
			return $res;
		}
		
		$res = $this->save($crit);
		//$this->criteria->merge($crit);
		return $res;
	}

	/**
	 * @access public
	 *
	 * @param pSmartCode
	 */
	public function _delete($pSmartCode = 0) {
		$crit = clone($this->criteria);
		if(strlen( $this->mAdditionalCondition ))
		$crit->add( Restrictions::sqlRestriction( $this->mAdditionalCondition ) );
		$this->applyPropertyToCriterion( $crit );

		if(! $crit->size())
		return false;
		if($pSmartCode)
		$crit->enableLike();
		else
		$crit->disableLike();

		return $this->delete( $crit );
	}

	public function _getPagination() {
		return is_object( $this->mPagination ) ? clone($this->mPagination) : null;
	}

	/**
	 * @access public
	 *
	 * @param int
	 * @param int
	 * @param string
	 * @param int 0 or 1
	 * @param string
	 * @param string
	 */
	public function _getObjects($pPage = 0, $pPageSize = 10, $pOrder = null, $pSmartCode = 1, $pColumns = '*', $pGroupBy = '') {
		$arrays = $this->_list( $pPage, $pPageSize, $pOrder, $pSmartCode, $pColumns, $pGroupBy );

		$return = array();
		for($i = 0,$len=count( $arrays ); $i < $len; $i ++) {
			$table = clone($this);
			$table->_reset();
				
			$table->fillObjectData( $arrays[$i] );
			$return[$i] = $table;
		}
		return $return;
	}

	/**
	 * @access public
	 *
	 * @param boolean $pSmartCode �Ƿ�ʹ��ģ����ѯ
	 * @param string $pColumns Ҫ��ѯ��
	 * @param string $pGroupBy ����
	 * @return integer
	 */
	public function _count($pSmartCode = 1, $pColumns = '*', $pGroupBy = '') {
		$criteria = clone($this->criteria);
		if($pSmartCode)
		$criteria->enableLike( SOSO_ORM_MatchMode::ANYWHERE );
		$pColumns = is_array( $pColumns ) ? $pColumns : explode( ',', $pColumns );
		$criteria->clearSelectColumns();
		/*foreach( $pColumns as $column )
		$criteria->addSelectColumn( $column );*/
		$criteria->setSelect($pColumns);
		$this->applyPropertyToCriterion( $criteria );
		if(strlen( $pGroupBy ))
		$criteria->addGroupByColumn( $pGroupBy );
		if(false === $this->fireEvent( 'beforecount', $criteria, $this )) {
			return false;
		}
		$this->prepareSelectSQLQuery( $criteria );
		$this->lastQuery = $this->lastCountQuery;
		if($this->debug) $this->log();
		return $this->doCount( $this->lastCountQuery );
	}

	/**
	 * @access public
	 * @return SOSO_ORM_Table
	 * updates :
	 * 2010-07-01: reset mAdditionalCondition to null;
	 */
	public function _reset() {
		$this->reset()->clear()->clearQuery();
		return $this;
	}

	public function reset() {
		$keys = array_keys( $this->hashMap );
		$length = count( $keys );
		for($i = 0; $i < $length; $i ++) {
			$this->hashMap[$keys[$i]] = null;
		}
		$this->mAdditionalCondition = '';
		$this->state = null;
		$this->mPagination = null;
		return $this;
	}

	/**
	 *
	 * Enter description here ...
	 * @param int $pRowed xml�з�ʽ����������������
	 * 	��.ֵΪtrueʱ��Ϊ���м���������->ֵ�ķ�ʽ���ڣ�����$pSubRowsָ������������tag�ķ�ʽչ�֣�
	 * 	��.ֵΪfalseʱ������$pSubRows�������������Ծ���tag��ʽ����
	 * @param bool $pFormatOutput
	 * @param bool $pExtra
	 */
	public function toDOM($pRowed=true,$pSubRows=array()) {
		$dom = new DOMDocument('1.0','UTF-8');
		$root = $this->getTable();
		$tCharset = $this->getCharset();
		$needEncode = strtolower($tCharset) != 'utf8';
		$oRoot = $dom->createElement($root);
		$oCriteria = $this->getCriteria();
		$tData = $this->find();

		foreach ($tData as $data){
			$oElement = $dom->createElement('item');
			foreach ($data as $key=>$val){
				$useSubRow = is_array($pSubRows) && in_array($key,$pSubRows);
				if(!preg_match("#^[_a-z][a-z0-9_]*$#Ui",$key)) continue;
				$isText = $oCriteria->isText($oCriteria->getColumnType($key));

				if ($pRowed && !$useSubRow){
					$val = $isText && $needEncode ? mb_convert_encoding($val, 'utf-8','gbk'): $val;
					$oElement->setAttribute($key,$val);
				}else{
					$oEl = $dom->createElement(strtolower($key),$isText ? null : $val);
					$isText && $needEncode && strlen($val) && $val = mb_convert_encoding($val, 'utf-8','gbk');
					$isText && strlen($val) && $oEl->appendChild($dom->createCDATASection($val));
					$oElement->appendChild($oEl);
				}
			}
			$oRoot->appendChild($oElement);
		}
		$dom->appendChild($oRoot);
		return $dom;
	}

	public function getIterator() {
		return new ArrayIterator( $this->getMapHash() );
	}
	/**
	 *
	 * return new array instead of array-reference
	 */
	public function getMapHash($ref=false) {
		if($ref) return $this->hashMap;
		$ret = array();
		foreach ($this->hashMap as $key=>$value) {
			$ret[$key] = $value;
		}
		return $ret;
	}

	/**
	 * alias for notify
	 *
	 */
	public function notifyObservers() {
		$this->notify();
	}

	/**
	 *
	 *
	 * @param SplObserver $observer
	 * @deprecated
	 * @see fireEvent
	 */
	public function attach(SplObserver $observer) {
		if($observer instanceof SplObserver) {
			if(array_search( $observer, $this->observers ) === false) {
				$this->observers[] = $observer;
			}
		}
		return $observer;
	}

	/**
	 *
	 * @deprecated
	 * @param SplObserver $observer
	 */
	public function detach(SplObserver $observer) {
		$index = array_search( $observer, $this->observers );
		if($index !== false) {
			unset( $this->observers[$index] );
		}
	}

	public function notify() {
		foreach( $this->observers as $observer ) {
			$observer->update( $this );
		}
	}

	public function addObserver(&$observer) {
		return $this->attach( $observer );
	}

	public function getState() {
		return $this->state;
	}

	/**
	 * $transaction = $tableObject->beginTransaction();
	 * $tableObject->_insert($array);
	 * //or $tableObject->_update()...
	 * $transaction->commit(); //or $transaction->rollback();
	 *
	 * ��ʼ����
	 *
	 */
	public function beginTransaction() {
		$this->mSQLCommand->setActive( true );
		return $this->mSQLCommand->beginTransaction();
	}
	/**
	 *
	 * �ύ����
	 */
	public function commit() {
		$transaction = $this->mSQLCommand->getCurrentTransaction();
		if(! is_null( $transaction )) {
			return $transaction->commit();
		}
		return false;
	}

	public function getCurrentTransaction() {
		$transaction = $this->mSQLCommand->getCurrentTransaction();
		if(! is_null( $transaction )) {
			return $transaction;
		}
		return null;
	}
	/**
	 *
	 * ����ع�
	 */
	public function rollback() {
		$transaction = $this->mSQLCommand->getCurrentTransaction();
		if(! is_null( $transaction )) {
			return $transaction->rollback();
		}
		return false;
	}

	public function setCharset($pCharset = 'gbk') {
		$this->charset = $pCharset;
		$this->mSQLCommand->setCharset( $pCharset );
		return $this;
	}
	/**
	 *
	 * @param state
	 */
	public function setState($state) {
		$this->state = $state;
	}

	/**
	 *
	 * @deprecated
	 * @see SOSO_ORM_Table::getObjectData()
	 * @param $pKey
	 */
	public function _get($pKey) {
		if(isset( $this->$pKey )) {
			return $this->$pKey;
		}
		return $this->getObjectData( $pKey );
	}

	public function getCharset() {
		return $this->charset;
	}
	public function getPrimaryKey() {
		return array()+$this->primaryKey;
	}
	public function getCountQuery() {
		return $this->lastCountQuery;
	}

	/**
	 *
	 * �Ӵճ�������SQL��䣬���ܻ��д�����SQL���С�����������£�
	 */
	public function getLastQuery() {
		$sql = $this->lastQuery; //$this->mSQLCommand->getLastQuery();
		$params = $this->lastQueryParams;
		$needle = '?';
		$pos = strpos( $sql, $needle );

		while( $pos !== false && $params ) {
			$sql = substr_replace( $sql, "'" . array_shift( $params ) . "'", $pos, 1 );
			$pos = strpos( $sql, '?' );
		}
		return $sql;
	}

	public function getTable() {
		return $this->tableName;
	}

	public function getDbIndex() {
		return $this->dbIndex;
	}

	public function setIndex($index) {
		$this->dbIndex = $index;
		$this->mSQLCommand = SOSO_DB_PDOSQLCommand::getInstance( $index, false );
		$this->mSQLCommand->setActive( true );
		$this->setCharset($this->getCharset());
		$this->criteria->setDbIndex($index);
		return $this;
	}
	/**
	 *
	 * @return SOSO_DB_PDOSQLCommand
	 */
	public function getCommand(){
		$this->mSQLCommand->setActive(true);
		return clone($this->mSQLCommand);
	}
	public function getError(){
		return $this->mSQLCommand->getErrorInfo();
	}

	public function __clone(){
		$this->criteria = clone($this->criteria);
		$this->rebind();
	}
}
