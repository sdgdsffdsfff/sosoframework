<?php
/**
 * @author moonzhang (zyfunny@gmail.com)
 * @version 1.0.0.0 2011-01-10 21:21:01
 * QBC implementation
 * 
 * Updates:
 * 	1.enableLike 实现
 */
require_once dirname(__FILE__).'/Criterion.php';
require_once dirname(__FILE__).'/Restrictions.php';
class SOSO_ORM_Criteria implements IteratorAggregate {

	/**
	 * 
	 * 对table类成员tableFiledhash的引用
	 * @var unknown_type
	 */
	private $tableFields = array();
	private $ignoreCase = true;
	
	private $selectModifiers = array();
	private $selectColumns = array();
	private $orderByColumns = array();
	private $groupByColumns = array();
	private $having = null;
	private $asColumns = array();
	private $joins = array();

	protected $dbIndex = 0;
	private $tableName;
	private $likeEnabled;
	private $matchMode;
	private $quoteFunction;
	/**
	 * 
	 * 交叉关联的表
	 * @var unknown_type
	 */
	private $from=array();

	/**
	 * 主表 - 一般指Table的表名，除非..单独创建，并覆盖掉table自己的criteria
	 * 
	 * 
	 * @var        string
	 */
	private $primaryTableName;


	/**
	 * 行数限制，０表示返回全部
	 * rows.
	 */
	private $limit = 0;

	/** 
	 * 偏移量
	 * @var unknown_type
	 */
	private $offset = 0;
	private $aliases = array();

	/**
	 *
	 * 是否使用事务
	 * @var boolean
	 */
	private $useTransaction = false;
	
	const LEFT_JOIN = "LEFT JOIN";

	const RIGHT_JOIN = "RIGHT JOIN";

	const INNER_JOIN = "INNER JOIN";

	/**
	 * @todo 是否删除掉dbIndex引用
	 * @var  Criterion[]
	 */
	private $map = array();

	public function __construct($dbIndex=null){
		if(is_numeric($dbIndex)){
			$this->setDbIndex($dbIndex);
		}
	}
	
	public function _setFields($fields){
		$this->tableFields = $fields;
		return $this;
	}
	
	/**
	 * 
	 * 获得字段类型，通过表定义
	 * @param string $column
	 */
	public function getColumnType($column){
		$column = str_replace('`', '', $column);
		$real = $this->getColumnForAlias($column);
		$real = strlen($real) ? $real : $column;
		if ($this->isIgnoreCase()) $real = strtolower($real);
		if (!$this->tableFields || !array_key_exists($real, $this->tableFields)) return null;
		$type = $this->tableFields[$real]['Type'];
		$pos = strpos($type,'(');
		if(false !== $pos) $type = substr($type,0,$pos);
		return $type;
	}
	
	/**
	 * 
	 * 打开模糊查询模式，打开后所有非digit类型字段查询全使用like
	 * @param unknown_type $mode
	 * @see SOSO_ORM_MatchMode
	 */
	public function enableLike($mode=SOSO_ORM_MatchMode::ANYWHERE){
		if(!strlen($mode)) return $this;
		$this->likeEnabled = true;
		$this->matchMode = $mode;
		return $this;
	}
	
	public function disableLike(){
		$this->likeEnabled = null;
		return $this;
	}
	
	public function getMatchMode(){
		return $this->matchMode;
	}
	public function isLikeEnabled(){
		return $this->likeEnabled == true;
	}
	
	public function getIterator(){
		return new ArrayIterator($this->map);
	}

	public function getMap(){
		return $this->map;
	}
	
	public function merge(SOSO_ORM_Criteria $crit){
		$this->map = array_merge($this->map,$crit->getMap());
	}

	/**
	 *重置成初始状态，以重用
	 * @return     SOSO_ORM_Criteria
	 */
	public function clear(){
		$this->from = array();
		$this->map = array();
		$this->ignoreCase = false;
		$this->selectModifiers = array();
		$this->selectColumns = array();
		$this->orderByColumns = array();
		$this->groupByColumns = array();
		$this->having = null;
		$this->asColumns = array();
		$this->joins = array();
		$this->offset = 0;
		$this->limit = 0;
		$this->aliases = array();
		$this->useTransaction = false;
		return $this;
	}
	
	public function clearMap(){
		$this->map = array();
		return $this;
	}
	
	public function clearJoin(){
		$this->joins = array();
		return $this;
	}

	/**
	 * 为列设置别名. 
	 * Usage:
	 * 	$myCrit = new SOSO_ORM_Criteria();
	 * 	$myCrit->addAsColumn("alias_name", "ALIAS(ID)");
	 * 
	 *
	 * @param      string $name Wanted Name of the column (alias).
	 * @param      string $clause SQL clause to select from the table
	 *
	 * If the name already exists, it is replaced by the new clause.
	 *
	 * @return     SOSO_ORM_Criteria A modified SOSO_ORM_Criteria object.
	 */
	public function addAsColumn($name, $clause){
		return $this->addAliasColumn($name, $clause);
	}

	public function addAliasColumn($name,$clause){
		$this->asColumns[$name] = $clause;
		return $this;
	}
	/**
	 * 获得字段别名.
	 *
	 * @return array 
	 */
	public function getAsColumns(){
		return $this->asColumns;
	}
	
	public function getAsTables(){
		return $this->aliases;
	}

	/**
	 * 返回指定别名的字段名
	 *
	 * @param      string $alias
	 * @return     string $string
	 */
	public function getColumnForAlias($as){
		if(!is_scalar($as)) return false;
		if (isset($this->asColumns[$as])) {
			return $this->asColumns[$as];
		}
		return false;
	}
	
	/**
	 * @todo 重构　-　迁移到外部类或util类中去
	 * 
	 * Enter description here ...
	 * @param unknown_type $pType
	 */
	public function isDigtial($pType){
		return in_array(strtolower($pType),array('int','bigint','tinyint','smallint','mediumint','integer','bigint'));
	}
	
	public function isText($pType) {
		return in_array( strtolower( $pType ), array('char', 'varchar', 'text', 'mediumtext', 'longtext', 'tinytext', 'tinyblob', 'blog', 'mediumblog', 'longblog' ) );
	}

	/**
	 * 添加一个表别名，添加后在SQL出现的表都会替换为别名
	 *
	 * @param      string $alias
	 * @param      string $table
	 * @return     void
	 */
	public function addAlias($alias, $table){
		$this->aliases[$alias] = $table;
		return $this;
	}

	/**
	 * 返回指定别名的表名
	 *
	 * @param      string $alias
	 * @return     string $string
	 */
	public function getTableForAlias($alias){
		if (isset($this->aliases[$alias])) {
			return $this->aliases[$alias];
		}
	}
	
	/**
	 * 返回指定表的表别名
	 * @param string $table
	 */
	public function getAliasTable($table){
		if (false === ($alias=array_search($table,$this->aliases))){
			return false;
		}
		return $alias;
	}

	/**
	 * 获得条件map的所有key
	 * @return     array
	 */
	public function keys(){
		return array_keys($this->map);
	}

	/**
	 * 判断是否存在指定字段的条件
	 *
	 * @param      string $column [table.]column
	 * @return     boolean True 如果存在这个KEY，否则返回false
	 */
	public function containsKey($column){
		return array_key_exists($column, $this->map);
	}

	/**
	 * 存在指定字段条件，且具备非"空"值
	 *
	 * @param      string $column [table.]column
	 * @return     boolean True 
	 */
	public function keyContainsValue($column){
		return (array_key_exists($column, $this->map) && ($this->map[$column]->getTypedValues() !== Criterion::NO_VALUE) );
	}

	/**
	 * 指定是否使用事务；
	 * @return     void
	 */
	public function setUseTransaction($v){
		$this->useTransaction = (boolean) $v;
	}

	/**
	 * 是否使用事务
	 *
	 * @return     boolean
	 */
	public function isUseTransaction(){
		return $this->useTransaction;
	}

	/**
	 * 获得指定字段的条件对象
	 *
	 * @param      string $column 字段名
	 * @return     Criterion 
	 */
	public function getCriterion($column){
		if ( isset ( $this->map[$column] ) ) {
			return $this->map[$column];
		}
		return null;
	}

	/**
	 * 
	 * 创建一个新的条件(SimpleExpression)
	 *
	 * @param      string $column 字段名全名 (如：TABLE.COLUMN).
	 * @param      mixed $value
	 * @param      string $comparison
	 * @return     Criterion
	 */
	public function getNewCriterion($column, $value, $comparison = null){
		if (is_null($comparison)) $comparison = SOSO_ORM_Restrictions::EQUAL;
		if ($this->isIgnoreCase()) $column = strtolower($column);
		return SOSO_ORM_Restrictions::getNewCriterion($column, $value, $comparison);
	}

	/**
	 * 返回指定名字的字段名
	 *
	 * @param      string $name 
	 * @return     string 
	 */
	public function getColumnName($name){
		if (isset($this->map[$name])) {
			return $this->map[$name]->getColumn();
		}
		return null;
	}

	public function getDbIndex(){
		return $this->dbIndex;
	}

	public function setDbIndex($index=0){
		$this->dbIndex = $index;
	}

	/**
	 * 获得主表名
	 * 不需要显式指定
	 *
	 * @return     string
	 */
	public function getPrimaryTableName(){
		return $this->primaryTableName;
	}

	/**
	 * 指定主表名
	 *
	 *
	 * @param      string $tableName
	 */
	public function setPrimaryTableName($tableName){
		$this->primaryTableName = $tableName;
		return $this;
	}

	public function setFrom($tables){
		$this->from = $tables;
		return $this;
	}
	
	public function getFrom(){
		return $this->from;
	}
	/**
	 * 获得表名
	 *
	 * @param      string $name 
	 * @return     string 
	 */
	public function getTableName($name){
		if (isset($this->map[$name])) {
			return $this->map[$name]->getTable();
		}
		return null;
	}
	
	public function getTableFields(){
		return $this->tableFields;
	}

	/**
	 * 返回指定key对应的数据
	 *
	 * @param      string $name A String with the name of the key.
	 * @return     mixed The value of object at key.
	 */
	public function getValue($name){
		if (isset($this->map[$name])) {
			return $this->map[$name]->getTypedValues($this);
		}
		return null;
	}

	/**
	 * 
	 *
	 * @param      string $key 
	 * @return     
	 */
	public function get($key){
		return $this->getValue($key);
	}

	/**
	 *　添加一个Criterion到条件列表中，可用于条件查询，也可用于赋值语句
	 *  如果指定字段条件已经存在，则覆盖之
	 * 
	 * 范例:
	 * $crit = new SOSO_ORM_Criteria();
	 * $crit->add("column","value",SOSO_ORM_Restrictions::GREATER_THAN);
	 * 它与下面语句有相同作用
	 * $crit->add(SOSO_ORM_Restrictions::gt('column','value'));
	 *
	 * 用于多表条件时，非主表字段名不能省略，否则会有不正确行为
	 * 
	 * @see Restrictions
	 * @param  mixed $p1 用于比较的字段名或对象(Criterion)
	 * @param  mixed $value
	 * @param  string $comparison 使用Restrictions里面的常量
	 *
	 * @return SOSO_ORM_Criteria
	 */
	public function add($p1, $value = null, $comparison = null){
		if ($p1 instanceof Criterion) {
			$key = $p1->getFullName();
			if ($this->isIgnoreCase()) $key = strtolower($key);
			if(strlen($key)){
				$this->map[$key] = $p1;
			}else{
				$this->map[] = $p1;
			}
		} else {
			if ($this->isIgnoreCase()) $p1 = strtolower($p1);
			if (is_null($value)){
				if (is_null($comparison) || $comparison == SOSO_ORM_Restrictions::EQUAL){
					$nc = SOSO_ORM_Restrictions::isNull($p1);
				}else{
					$nc = SOSO_ORM_Restrictions::isNotNull($p1);
				}
			}else{
				$nc = $this->getNewCriterion($p1, $value, $comparison);
			}
			$this->map[$p1] = $nc;
		}
		return $this;
	}

	/**
	 * 
	 * 添加表关联查询方法
	 *
	 * @see    SOSO_ORM_Table::join 使用范例见SOSO_ORM_Table::join方法
	 * @param  mixed $left 
	 * @param  mixed $right 
	 * @param  mixed $operator SOSO_ROM_Criteria::(LEFT_JOIN|RIGHT_JOIN|INNER_JOIN)
	 *
	 * @return SOSO_ORM_Criteria 
	 */
	public function addJoin($left, $right, $type=null,$operator = '='){
		$join = new SOSO_ORM_Join();
		if (!is_array($left)) {
			$join->addCondition($left, $right,$operator);
		} else {
			//复杂条件时使用addMultipleJoin
			foreach ($left as $key => $value){
				$join->addCondition($value, $right[$key]);
			}
		}
		$join->setType($type);

		return $this->addJoinObject($join);
	}

	/**
	 * 添加一个有多条语句的关联对象
	 * 
	 *　
	 * $x->addMultipleJoin(array(
	 *     array('tb1.left_column', 'tb2.right_column'),  //如果没有第三个参数，默认使用=
	 *     array('TB.left_column', 'TBX.right_column', SOSO_ORM_Restrictions::LESS_EQUAL )
	 *   ),
	 *   SOSO_ORM_Criteria::LEFT_JOIN
	 * );
	 *
	 * @see        addJoin()
	 * @param      array $conditions 条件数组, 每个条件需要是一个有２到３个元素的数组(left, right, operator)，如果没有第三个元素，默认使用"="
	 * @param      string $joinType  关联类型，可以是左、右、内联，默认是隐式关联
	 *
	 * @return     SOSO_ORM_Criteria 
	 */
	public function addMultipleJoin($conditions, $joinType = null){
		$join = new SOSO_ORM_Join();
		foreach ($conditions as $condition) {
			$join->addCondition($condition[0], $condition[1], isset($condition[2]) ? $condition[2] : SOSO_ORM_Criteria::EQUAL);
		}
		$join->setJoinType($joinType);

		return $this->addJoinObject($join);
	}

	/**
	 * 添加一个关联（SOSO_ORM_Join）对象
	 *
	 * @param SOSO_ORM_Join $join 一个关联对象
	 *
	 * @return SOSO_ORM_Criteria
	 */
	public function addJoinObject(SOSO_ORM_Join $join){
		if (!in_array($join, $this->joins)) {
			$this->joins[] = $join;
		}
		return $this;
	}


	/**
	 * 获得所有关联对象数组
	 * @return array SOSO_ORM_Join[]
	 */
	public function getJoins(){
		return $this->joins;
	}

	/**
	 * 添加ALL操作符到SQL语句中
	 * @return SOSO_ORM_Criteria 
	 */
	public function setAll(){
		$this->selectModifiers[] = SOSO_ORM_Restrictions::ALL;
		return $this;
	}

	/**
	 * 添加DISTINCT操作符到SQL语句中
	 * @return SOSO_ORM_Criteria 
	 */
	public function setDistinct(){
		$this->selectModifiers[] = SOSO_ORM_Restrictions::DISTINCT;
		return $this;
	}

	/**
	 * 设置大小写敏感
	 *
	 * @param      boolean $b True 如果大小写可以被忽略.
	 * @return     SOSO_ORM_Criteria 
	 */
	public function setIgnoreCase($b){
		$this->ignoreCase = (boolean) $b;
		return $this;
	}

	/**
	 * 是否忽略了大小写
	 *
	 * @return     boolean True 如果忽略了大小写.
	 */
	public function isIgnoreCase(){
		return $this->ignoreCase;
	}

	/**
	 * Set limit.
	 *
	 * @param   int  $limit 
	 * @return  SOSO_ORM_Criteria
	 */
	public function setLimit($limit){
		$this->limit = $limit;
		return $this;
	}
	/**
	 * 
	 * @see setLimit
	 * @return     SOSO_ORM_Criteria 
	 */
	public function setPagesize($limit){
		return $this->setLimit($limit);
	}

	/**
	 * Get limit.
	 *
	 * @return int 
	 */
	public function getLimit(){
		return $this->limit;
	}

	/**
	 * Set offset.
	 *
	 * @param      int $offset 
	 * @return     SOSO_ORM_Criteria 
	 */
	public function setOffset($offset){
		$this->offset = (int) $offset;
		return $this;
	}

	public function setPage($page,$pagesize=null){
		if (!is_null($pagesize) && (int) $pagesize > 0)
		$this->setLimit($pagesize);
		$this->setOffset(((int)$page - 1) * $this->getLimit());
		return $this;
	}
	
	public function getPage(){
		$limit = $this->getLimit();
		$offset = $this->getOffset();
		if ($limit == 0) return 1;
		return (int)($offset / $limit) + 1;	
	}
	
	public function getOffset(){
		return $this->offset;
	}

	public function setSelect($columns,$clear=true){
		if(! is_array( $columns )) {
			$pieces = explode( ',', $columns );
			$columns = array();
			$stack = array();
			$depth = 0;
			foreach ($pieces as $k=>$v){
				$stack[] = $v;
				$len = strlen($v);
				$depth += $len - strlen(str_replace('(','',$v));
				$depth -= $len - strlen(str_replace(')','',$v));
				if($depth == 0){
					$columns[] = join(",",$stack);
					$stack = array();
				}  
			}
		}
		$clear && $this->clearSelectColumns();
		foreach( $columns as $col ) {
			if($this->isIgnoreCase())
			$col = strtolower( $col );
			$this->addSelectColumn( $col );
		}
		return $this;
	}
	/**
	 * 添加查询字段.
	 *
	 * @param      string $name 
	 * @return     SOSO_ORM_Criteria
	 */
	public function addSelectColumn($name){
		$this->selectColumns[] = $name;
		return $this;
	}

	/**
	 * 
	 * 获得select字段数据
	 * 
	 */
	public function getSelectClause() {
		$aliases = $this->getAsColumns();
		$select = $this->getSelectColumns();
		$selectClause = array();
		$baseTable = $this->getPrimaryTableName();
		$tableAlias = $this->getAliasTable($baseTable);
		$table = $tableAlias ? $tableAlias : $baseTable;
		$joins = !!$this->getJoins();
		
		if (!$select && !$aliases){
			return array("*");
		}
		$tableQuoted = $joins ? $this->quoteIdentifier($table).'.' : '';
		$columns = array_keys($this->tableFields);

		if ((!$select || trim(join("",$select)) == '*') && $aliases){
			$columnAlias = array();
			$columnAliasKeys = array();
			foreach ($columns as $key){
				$alias = array_search($key, $aliases);
				if ($alias){
					$columnAliasKeys[trim($alias,' `')] = true;
					$columnAlias[] = $tableQuoted.$this->quoteIdentifier($key) . ' AS ' . $this->quoteIdentifier($alias);
					continue;
				}
				$selectClause[] = $tableQuoted.$key;
			}
			$diff = array_diff_key($aliases, $columnAliasKeys);
			foreach ($diff as $as=>$col){
				$columnAlias[] = $this->quoteIdentifier($col) . ' AS ' . $this->quoteIdentifier($as);
			}
			$selectClause = $joins ? array('*') : $selectClause;
			return array_merge($selectClause,$columnAlias); 
			//return $selectClause;
		}
		
		$selectClause = array_map('trim',$select);
		
		foreach ($selectClause as $index=>$column){
			$alias = array_search($column, $aliases);
			if ($alias !== false){
				unset($aliases[$alias]);
				$selectClause[$index] = $tableQuoted.$this->quoteIdentifier($column) . " AS " . $this->quoteIdentifier($alias);
			}else{
				$hasDot = false !== strpos($column,'.');
				$hasExpression = false !== strpos($column,'(') || false !== strpos($column,')');
				$hasSpace = false !== strpos($column,' ');
				
				if (!$hasDot && !$hasExpression && !$hasSpace){
					$this->isIgnoreCase() && $column = strtolower($column);
					$isValid = array_search($column,$columns) !== false;
					$selectClause[$index] = $isValid ? $tableQuoted.$this->quoteIdentifier($column) : $column;
				}elseif(!$hasDot && $hasSpace && !$hasExpression){
					$pieces = preg_split('#\s+(?:as\s+)?#i',$column);
					$selectClause[$index] = $column;
					if(2 == count($pieces)){
						$tableInfo = array_key_exists($pieces[0], $this->tableFields) ? $tableQuoted : '';
						$selectClause[$index] = $tableInfo.$this->quoteIdentifier($pieces[0]).' AS '.$this->quoteIdentifier($pieces[1]);
					}
				}elseif($tableAlias && $hasDot){
					//有表别名时，select 指定的字段如果为老表名，会执行失败，如
					/*
					$oUsers->alias('hello');
        			$oUsers->add('users.qq',$qq);
        			这应该替换成为hello.qq
        			*/
					$p = "#(\s*)(`|)\b{$baseTable}\b(\\2\s*\.)#Ui";
					$selectClause[$index] = preg_replace($p, "\\1\\2$tableAlias\\3" , $selectClause[$index]);
				}
			}
		}

		if(! $selectClause)
			$selectClause = array('*');

		if ($aliases){
			foreach ($aliases as $as=>$col){
				$selectClause[] = $joins && array_key_exists($col, $this->tableFields) ?
					  $tableQuoted.$this->quoteIdentifier($col). ' AS '.$as 
					: $this->quoteIdentifier($col). ' AS '.$as; 
			}
		}
		return array_merge(array(),$selectClause);
	}
	
	/**
	 * 
	 * 获得from信息
	 * @param SOSO_ORM_Criteria $criteria
	 */
	public function getFromClause() {
		$criteria = $this;
		$aliasTables = $criteria->getAsTables();
		$fromClause = array($this->getPrimaryTableName());
		foreach( $fromClause as $k => $table ) {
			$tablename = $criteria->getTableForAlias( $table );
			if(! $tablename && $t = array_search( $table, $aliasTables )) {
				$fromClause [$k] = $table . ' ' . $t;
			} else {
				$fromClause [$k] = $tablename . ' ' . $table;
			}
		}
		foreach( $criteria as $criterion ) {
			$tables = $criterion->getTables();
			$tables = array_filter( $tables, 'strlen' );
			if(! $tables)
				continue;
			foreach( $tables as $table ) {
				$alias = $criteria->getAliasTable( $table );
				$real = $criteria->getTableForAlias( $table );
				if($real) {
					$alias = $table;
					$table = $real;
				}
				$fromClause[] = $table . ' ' . $alias;
			}
		}
		return $fromClause;
	}
	
	/**
	 * 
	 * 获得from、where及join信息
	 * @param array() $fromClause 
	 */
	public function getJoinClause($fromClause) {
		$criteria = $this;
		$joinClause = array();
		$joinTables = array();
		$whereClause = array();
		//print_r($criteria->getJoins());
		//$aliasTables = $criteria->getAsTables();
		$ignoreCase = $criteria->isIgnoreCase();
		//$left = $right = array();
		
		foreach(( array ) $criteria->getJoins() as $join ) {
			$condition = '';
			$joinType = $join->getType();
			
			foreach( $join->getConditions() as $index => $conditionDesc ) {
				$leftTable = $join->getLeftTableName( $index );
				if(! $leftTable) {
					$leftTable = $this->getPrimaryTableName();
					if (array_key_exists($conditionDesc ['left'], $this->tableFields))
						$conditionDesc ['left'] = $leftTable . '.' . $conditionDesc ['left'];
				}
				$leftTableAlias = $criteria->getAliasTable( $leftTable );
				
				if(! $joinType) {
					$real = $criteria->getTableForAlias( $leftTable );
					if($real) {
						$right [] = $real . ' ' . $leftTable;
					} else {
						$right [] = $leftTable . ' ' . $leftTableAlias;
					}
				}
				$rightTable = $join->getRightTableName( $index );
				$rightTableAlias = $criteria->getAliasTable( $rightTable );
				
				$right [] = $rightTable . ' ' . $rightTableAlias;
				
				$leftTableAlias && $conditionDesc ['left'] = str_replace( $leftTable . '.', $leftTableAlias . '.', $conditionDesc ['left'] );
				$rightTableAlias && $conditionDesc ['right'] = str_replace( $rightTable . '.', $rightTableAlias . '.', $conditionDesc ['right'] );
				
				if($ignoreCase) {
					//$condition .= $db->ignoreCase($conditionDesc['left']) . $conditionDesc['operator'] . $db->ignoreCase($conditionDesc['right']);
					$condition .= $conditionDesc ['left'] . $conditionDesc ['operator'] . $conditionDesc ['right'];
				} else {
					$condition .= implode( $conditionDesc );
				}
				if($index + 1 < $join->countConditions()) {
					$condition .= ' AND ';
				}
			}
			
			$right = array_unique( $right );
			
			if($joinType) {
				// real join
				if(! $fromClause) {
					$fromClause [] = $leftTable . ' ' . $leftTableAlias;
				}
				$joinTables = array_merge( $joinTables, $right );
				$joinClause [] = $joinType . '(' . implode( ',', $right ) . ") ON($condition)";
			} else {
				// implicit join, translates to a where
				/*$fromClause[] = $leftTable .' '. $leftTableAlias;
				$fromClause[] = $rightTable .' '. $rightTableAlias;
				*/
				$fromClause = array_merge( $right, $fromClause );
				$whereClause [] = $condition;
			}
			$right = array();
		}
		
		$fromClause = array_unique( array_map( 'trim',$fromClause ) );
		$fromClause = array_diff( $fromClause, array('' ) );
		
		$joinTables = array_unique( array_map( 'trim',$joinTables ) );
		$joinTables = array_diff( $joinTables, array('' ) );
		
		//$primary = array_shift($fromClause);
		array_push( $fromClause, array_shift( $fromClause ) );
		// tables should not exist in both the from and join clauses
		if($joinTables && $fromClause) {
			foreach( $fromClause as $fi => $ftable ) {
				if(in_array( $ftable, $joinTables )) {
					unset( $fromClause [$fi] );
				}
			}
		}

		return array('where' => $whereClause, 'from' => $fromClause, 'join' => $joinClause );
	}
	
	public function getGroupByClause() {
		$tGroupBy = $this->getGroupByColumns();
		$tPrimary = '';
		$columnAliases = $this->getAsColumns();
		$hasJoin = !!$this->getJoins();
		if($hasJoin){
			$tPrimary = $this->getPrimaryTableName();
			$tPrimaryAlias = $this->getAliasTable($tPrimary);
			$tPrimary = $tPrimaryAlias ? $tPrimaryAlias : $tPrimary;
			$tPrimary .= '.';
		}
		//if(!$hasJoin) return $tGroupBy;
		foreach( $tGroupBy as $index => $group ) {
			$pos = strpos( $group, '.' );
			if(false !== $pos){
				continue;
			}
			
			if (array_key_exists( $group, $this->tableFields)){
				$tGroupBy[$index] = $tPrimary . $this->quoteIdentifier($group);
			}
		}
		return $tGroupBy;
	}
	
	/**
	 * 
	 * 获得排序字段信息
	 * updates: 
	 * 	1.解决有join时，调用orderBy时只传递字段名可能会出现的bug
	 */
	public function getOrderByClause() {
		$criteria = $this;
		$orderBy = $criteria->getOrderByColumns();
		if(empty( $orderBy ))
			return array();
		$orderByClause = array();

		foreach( $orderBy as $orderByColumn ) {
			if(strpos( $orderByColumn, '(' ) !== false) {
				$orderByClause[] = $orderByColumn;
				continue;
			}
			$orderByColumn = trim($orderByColumn);
			$dotPos = strrpos( $orderByColumn, '.' );
			
			if($dotPos !== false) {
				$tableName = substr( $orderByColumn, 0, $dotPos );
				$columnName = substr( $orderByColumn, $dotPos + 1 );
			} else {
				$tableName = $this->getPrimaryTableName();
				$columnName = $orderByColumn;
			}
			if($criteria->isIgnoreCase()) {
				$columnName = strtolower( $columnName );
			}
			
			$spacePos = strrpos( $columnName, ' ' );
			
			if($spacePos !== false) {
				$direction = substr( $columnName, $spacePos );
				$columnName = substr( $columnName, 0, $spacePos );
			} else {
				$direction = '';
			}
			
			$tableAlias = $tableName;
			$aliasTableName = $criteria->getTableForAlias( $tableName );
			if($aliasTableName) {
				$tableName = $aliasTableName;
			} elseif(false !==($index = array_search( $tableName, $criteria->getAsTables() ))) {
				$tableAlias = $index;
				$index = null;
			}
			
			$columnAlias = $columnName;
			$asColumnName = $criteria->getColumnForAlias( $columnName );
			if($asColumnName) {
				$columnName = $asColumnName;
				if($tableName != $tableAlias)
					$columnAlias = $columnName;
			}
			/*
			 先注掉！等事后完善
			 $column = null;
			 	
			 if(!strlen($tableName) || 0 == strcasecmp($tableName, $this->getTable())){
				$column = $this->tableFieldHash[$columnName];
				if(false !==($pos=strpos($column['Type'],'('))){
				$column['Type'] = substr($column['Type'],0,$pos);
				}
				}

				if($this->criteria->isIgnoreCase() && $column && $this->isText($column['Type'])) {
				$orderByClause[] = "$tableAlias.$columnAlias" . $direction;
				//$orderByClause[] = $this->mSQLCommand->ignoreCaseInOrderBy("$tableAlias.$columnAlias") . $direction;
				//$selectClause[] = $this->mSQLCommand->ignoreCaseInOrderBy("$tableAlias.$columnAlias");
				} else */			
			{
				
				if($dotPos === false){
					$table = $this->quoteIdentifier($tableAlias);
					$temp = trim($columnAlias," \t`");
					if ($this->ignoreCase) $temp = strtolower($temp);
					
					if($this->tableFields && array_key_exists($temp, $this->tableFields)){
						$column = $this->quoteIdentifier($columnAlias);
						$orderByClause [] = $table.'.'.$column.$direction;
					}else{
						//$orderByClause [] = $table.'.'.$column.$direction;
						$orderByClause [] = $columnAlias.$direction;
					}
				}else
					/*if($this->tableFields && array_key_exists($temp, $this->tableFields)){
						$orderByClause [] = "$tableAlias.$columnAlias$direction";
						continue;
					}*/
					$orderByClause [] = "$tableAlias.$columnAlias$direction";
			}
		}
		return $orderByClause;
	}
	
/**
	 *
	 * 获得条件及对应的param
	 * @param {SOSO_ORM_Criteria|SOSO_ORM_Criterion} $data
	 * @param Boolean   $native 多用于update操作；是否是本表，如为true，则所有column限定为本表字段；否则不限；
	 */
	public function getCriterionPairs($data = null, $native = false) {
		if(is_null( $data ))
			return null;
		$whereClause = array();
		$res = array();
		if($data instanceof Criterion) {
			$col = $data->getColumn();
			if($this->isIgnoreCase())
				$col = strtolower( $col );
			if($native && array_key_exists( $col, $this->tableFields )) {
				$sql = $data->toSqlString( $this );
				$param = $data->getTypedValues( $this );
			} elseif(! $native) {
				$sql = $data->toSqlString( $this );
				$param = $data->getTypedValues( $this );
			}
			if(! is_array( $param ))
				$param = array($param );
			
			$param = self::paramFilter($param);
			return array($sql, $param );
		}
		//fixed:修复传错参数的bug（在select/update时，传递的是某个criteria的修改副本，不能传递$this->criteria进行计算
		foreach( $data as $column => $criterion ) {
			if($native && ! is_numeric( $column ) && array_key_exists( $column, $this->tableFields )) {
				$whereClause [] = $criterion->toSqlString( $data, true );
				$res [] = $criterion->getTypedValues( $data );
			} elseif(! $native || is_numeric( $column )) {
				$whereClause [] = $criterion->toSqlString( $data );
				$res [] = $criterion->getTypedValues( $data );
			}
		}
		
		$res = self::paramFilter($res);
		return array($whereClause, $res );
	}
	
	/**
	 * 
	 * moved from from SOSO_ORM_Table::paramFilter
	 * @param mixed $param
	 */
	public static function paramFilter($param){
		$param = SOSO_Util_Util::arrayFlatten( $param ); 
		return SOSO_Util_Util::arrayFilter( $param, Criterion::NO_VALUE );
	}
	
	/**
	 *
	 * 获得having子句
	 * @param array $params
	public function getHavingString(&$params) {
		$having = $this->getHaving();
		$havingString = null;
		
		if($having instanceof Criterion) {
			
			$havingString = $having->toSqlString( $this );
			$params [] = $having->getTypedValues( $this );
		}
		return $havingString;
	}	 */
	
	/**
	 *
	 * @return     boolean
	 * @see        addAsColumn()
	 * @see        addSelectColumn()
	 */
	public function hasSelectClause(){
		return (!empty($this->selectColumns) || !empty($this->asColumns));
	}

	public function getSelectColumns(){
		return $this->selectColumns;
	}

	public function clearSelectColumns() {
		$this->selectColumns =  array();
		return $this;
	}
	
	public function clearAliasColumns(){
		$this->asColumns = array();
		return $this;
	}

	public function getSelectModifiers(){
		return $this->selectModifiers;
	}
	
	public function setQuoteFn($method,$class){
		$this->quoteFunction = array($class,$method);
	}
	
	public function quoteIdentifier($value){
		if ($this->quoteFunction && is_callable($this->quoteFunction)){
			return call_user_func($this->quoteFunction,$value);
		}
		return $value;
	}

	/**
	 *　添加group by 字段.
	 *
	 * @param 　string $groupBy 
	 * @return SOSO_ORM_Criteria
	 */
	public function addGroupByColumn($groupBy){
		$this->groupByColumns[] = $groupBy;
		return $this;
	}

	/**
	 * 
	 * 添加排序字段(正序) 
	 * @param unknown_type $name
	 */
	public function orderByASC($name){
		$this->orderByColumns[] = $name . ' ' . SOSO_ORM_Restrictions::ASC;
		return $this;
	}

	/**
	 * 倒序
	 * 
	 * @param unknown_type $name
	 */
	public function orderByDESC($name){
		$this->orderByColumns[] = $name . ' ' . SOSO_ORM_Restrictions::DESC;
		return $this;
	}
	/**
	 * 获得排序字段数组
	 *
	 * @return     array 
	 */
	public function getOrderByColumns(){
		return $this->orderByColumns;
	}

	/**
	 * 清空排序数组
	 *
	 * @return     SOSO_ORM_Criteria 
	 */
	public function clearOrderByColumns(){
		$this->orderByColumns = array();
		return $this;
	}

	/**
	 * 清空分组数组
	 *
	 * @return     SOSO_ORM_Criteria
	 */
	public function clearGroupByColumns(){
		$this->groupByColumns = array();
		return $this;
	}

	public function getGroupByColumns()	{
		return $this->groupByColumns;
	}

	public function getHaving(){
		return $this->having;
	}

	/**
	 * 删除指定key的对象.
	 *
	 * @param      string $key
	 * @return     mixed 
	 */
	public function remove($key){
		if ( isset ( $this->map[$key] ) ) {
			$removed = $this->map[$key];
			unset ( $this->map[$key] );
			if ( $removed instanceof Criterion ) {
				return $removed->getTypedValues($this);
			}
			return $removed;
		}
	}

	public function __toString(){
		$tSQL = array();
		$tParams = array();
		foreach ($this->map as $criterion){
			$tSQL[] = $criterion->toSqlString($this);
			$tParams[] = $criterion->getTypedValues($this);
		}
		
		$tParams = SOSO_Util_Util::arrayFlatten($tParams);
		$tParams = SOSO_ORM_Table::paramFilter($tParams);
		
		$string = "SOSO_ORM_Criteria:";
		$string.= "\nSQL(可能不完整):\n\t";
		$string.= join(" AND ",$tSQL);
		
		$string.= "\n\nParams: \n\t";
		$string.= join(",",$tParams);
		return $string;
	}

	public function size(){
		return count($this->map);
	}

	/**
	 *　
	 * 添加having
	 * $table = new SOSO_ORM_Table('member');
	 * $table->addHaving(Restrictions::lt('ID', 5));
	 * $table->addHaving('ID',5,Restrictions::LESS_THAN);
	 *
	 * @param   Criterion
	 *
	 * @return  SOSO_ORM_Criteria
	 */
	public function addHaving(Criterion $having){
		$this->having = $having;
		return $this;
	}

	/**
	 *　添加一条and条件，多用于同一字段，如果不用此方法，会被覆盖
	 *
	 * @return     SOSO_ORM_Criteria 
	 */
	public function addAnd($p1, $p2 = null, $p3 = null){
		if ($p1 instanceof Criterion && $p2 instanceof Criterion){
			$key = $p1->getFullName();
			$key = $key ? $key : $p2->getFullName();
				
			if (!strlen($key)){
				$this->map[] = SOSO_ORM_Restrictions::andd($p1, $p2);
			}else{
				$this->map[$key] = SOSO_ORM_Restrictions::andd($p1, $p2);
			}
			return $this;
		}
		if ($p3 !== null) {
			$oc = $this->getCriterion($p1);
			$nc = $this->getNewCriterion($p1, $p2, $p3);
			if ( $oc === null) {
				if($this->size()){
					$oc = end($this->map);
					$p1 = key($this->map);
					$this->map[$p1] = SOSO_ORM_Restrictions::andd($oc, $nc);
				}else{
					$this->map[$p1] = $nc;
				}
			} else {
				$this->map[$p1] = SOSO_ORM_Restrictions::andd($oc, $nc);
			}
		} elseif ($p2 !== null) {
			$this->addAnd($p1, $p2, SOSO_ORM_Restrictions::EQUAL);
		} elseif ($p1 instanceof Criterion) {
			$key = $p1->getFullName();
			$oc = $this->getCriterion($key);
			if ($oc === null) {
				$this->add($p1);
			} else {
				//$oc->addAnd($p1);
				if (!strlen($key)){
					$this->map[] = SOSO_ORM_Restrictions::andd($oc, $p1);
				}else{
					//$oc->addOr($p1);
					$this->map[$key] = SOSO_ORM_Restrictions::andd($oc, $p1);
				}
			}
		} elseif ($p2 === null && $p3 === null) {
			$this->addAnd($p1, $p2, SOSO_ORM_Restrictions::EQUAL);
		}
		return $this;
	}

	/**
	 *
	 * @return     SOSO_ORM_Criteria
	 */
	public function addOr($p1, $p2 = null, $p3 = null){
		if ($p1 instanceof Criterion && $p2 instanceof Criterion){
			$key = $p1->getFullName();
			$key = $key ? $key : $p2->getFullName();
			
			if (!strlen($key)){
				$this->map[] = SOSO_ORM_Restrictions::orr($p1, $p2);
			}else{
				if ($this->isIgnoreCase()) $key = strtolower($key);
				$this->map[$key] = SOSO_ORM_Restrictions::orr($p1, $p2);
			}
			return $this;
		}
		
		if ($p3 !== null) {
			if ($this->isIgnoreCase()) $p1 = strtolower($p1);
			$nc = $this->getNewCriterion($p1, $p2, $p3);
			$oc = $this->getCriterion($p1);
			if ($oc === null) {
				if($this->size()){
					$oc = end($this->map);
					$p1 = key($this->map);
					$this->map[$p1] = SOSO_ORM_Restrictions::orr($oc, $nc);
				}else{
					$this->map[$p1] = $nc;
				}
			}else{
				$this->map[$p1] = SOSO_ORM_Restrictions::orr($oc, $nc);
			}
		} elseif ($p2 !== null) {
			$this->addOr($p1, $p2, SOSO_ORM_Restrictions::EQUAL);
		} elseif ($p1 instanceof Criterion) {				
			$key = $p1->getFullName();
				
			$oc = $this->getCriterion($key);
			if ($oc === null){
				if($this->size()){
					$oc = end($this->map);
					$key = key($this->map);
					$this->map[$key] = SOSO_ORM_Restrictions::orr($oc, $p1);
				}else{
					$this->map[$key] = $p1;
				}
			}
			/*if ($oc === null) {
				$this->add($p1);
			} else {
				if (!strlen($key)){
					$this->map[] = SOSO_ORM_Restrictions::orr($oc, $p1);
				}else{
					//$oc->addOr($p1);
					$this->map[$key] = SOSO_ORM_Restrictions::orr($oc, $p1);
				}
			}*/
		} elseif ($p2 === null && $p3 === null) {
			$nc = SOSO_ORM_Restrictions::isNull($p1);
			$oc = $this->getCriterion($p1);
			if ($oc === null) {
				
				if($this->size()){
					$oc = end($this->map);
					$p1 = key($this->map);
					$this->map[$p1] = SOSO_ORM_Restrictions::orr($oc, $nc);
				}else{
					$this->map[$p1] = $nc;
				}
				//$this->add(SOSO_ORM_Restrictions::isNull($p1));
			} else {
				//$oc->addOr($p1);
				$this->map[$p1] = SOSO_ORM_Restrictions::orr($oc, $nc);
			}
		}

		return $this;
	}
}