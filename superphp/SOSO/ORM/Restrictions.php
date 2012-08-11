<?php
/**
 * @author moonzhang
 * @todo 
 * 1、more criterions
 * 2、projections
 * 3、subcriteria
 */
require_once dirname(__FILE__).'/Criterion.php';
require_once dirname(__FILE__).'/Table.php';
/**
 * 工厂类
 * 
 * @author moonzhang
 *
 */
class SOSO_ORM_Restrictions {

	const EQUAL = "=";

	const NOT_EQUAL = "<>";
	
	const ALT_NOT_EQUAL = "!=";
	
	const GREATER_THAN = ">";
	
	const LESS_THAN = "<";
	
	const GREATER_EQUAL = ">=";
	
	const LESS_EQUAL = "<=";
	
	const LIKE = " LIKE ";
	
	const NOT_LIKE = " NOT LIKE ";
	
	const DISTINCT = "DISTINCT ";
	
	const IN = " IN ";
	
	const NOT_IN = " NOT IN ";
	
	const ALL = "ALL ";
	
	const JOIN = "JOIN";

	const BINARY_AND = "&";

	const BINARY_OR = "|";

	const ASC = "ASC";

	const DESC = "DESC";

	const ISNULL = " IS NULL ";

	const ISNOTNULL = " IS NOT NULL ";
	
	private function __construct(){}

	public static function instance($column,$value,$op='='){
		return new SimpleExpression($column, $value, $op);
	}
	
	public static function getNewCriterion($column,$value,$op='='){
		if ($op == self::IN) return self::in($column,$value);
		if ($op == self::NOT_IN) return self::notIn($column,$value);
		if ($op == self::LIKE) return self::like($column,$value);
		if ($op == self::NOT_LIKE) return self::notlike($column,$value);
		return new SimpleExpression($column, $value, $op);
	}

	/**
	 * 对指定字段应用“=”进行约束
	 * @param propertyName
	 * @param value
	 * @return Criterion
	 */
	public static function eq($propertyName, $value) {
		return new SimpleExpression($propertyName, $value, "=");
	}
	/**
	 * !=
	 * @param propertyName
	 * @param value
	 * @return Criterion
	 */
	public static function ne($propertyName, $value) {
		return new SimpleExpression($propertyName, $value, self::NOT_EQUAL);
	}
	/**
	 * like
	 * @param propertyName
	 * @param value
	 * @param SOSO_ORM_MatchMode 
	 * @return Criterion
	 */
	public static function like($propertyName, $value,$mode=SOSO_ORM_MatchMode::ANYWHERE) {
		return new SimpleExpression($propertyName, SOSO_ORM_MatchMode::toMatchString($value, $mode), self::LIKE);
	}

	/**
	 * not like
	 * @param propertyName
	 * @param value
	 * @return Criterion
	 */
	public static function notlike($propertyName, $value,$mode=SOSO_ORM_MatchMode::ANYWHERE) {
		return new SimpleExpression($propertyName, SOSO_ORM_MatchMode::toMatchString($value, $mode), self::NOT_LIKE);
	}
	
	/**
	 * >
	 * @param propertyName
	 * @param value
	 * @return Criterion
	 */
	public static function gt($propertyName, $value) {
		return new SimpleExpression($propertyName, $value, self::GREATER_THAN);
	}
	/**
	 * <
	 * @param propertyName
	 * @param value
	 * @return Criterion
	 */
	public static function lt(/*String*/ $propertyName, $value) {
		return new SimpleExpression($propertyName, $value, self::LESS_THAN);
	}
	/**
	 * <=
	 * @param propertyName
	 * @param value
	 * @return Criterion
	 */
	public static function le(/*String*/ $propertyName, $value) {
		return new SimpleExpression($propertyName, $value, self::LESS_EQUAL);
	}
	/**
	 * >=
	 * @param propertyName
	 * @param value
	 * @return Criterion
	 */
	public static function ge(/*String*/ $propertyName, /*Object*/ $value) {
		return new SimpleExpression($propertyName, $value, self::GREATER_EQUAL);
	}
	/**
	 * 对指定字段应用"between"约束
	 * @param propertyName
	 * @param lo value
	 * @param hi value
	 * @return Criterion
	 */
	public static function between(/*String*/ $propertyName, $lo, $hi) {
		return new BetweenExpression($propertyName, $lo, $hi);
	}
	/**
	 * 
	 * @param propertyName
	 * @param values
	 * @return Criterion
	 */
	public static function in($propertyName, $values,$param=null) {
		return new InExpression($propertyName, $values,$param);
	}
	
	/**
	 * 
	 * @param propertyName
	 * @param values
	 * @return Criterion
	 */
	public static function notIn($propertyName, $values) {
		return new NotExpression(new InExpression($propertyName, $values));
	}
	
	/**
	 * 对指定字段应用　is　null　约束
	 * @return Criterion
	 */
	public static function isNull(/*String*/ $propertyName) {
		return new NullExpression($propertyName);
	}
	/**
	 * 指定二个字段的值相等 "equal"
	 */
	public static function eqProperty(/*String*/ $propertyName, $otherPropertyName) {
		return new PropertyExpression($propertyName, $otherPropertyName, self::EQUAL);
	}
	/**
	 * 指定二个字段的值不等 "not equal"
	 */
	public static function neProperty(/*String*/ $propertyName, $otherPropertyName) {
		return new PropertyExpression($propertyName, $otherPropertyName, self::NOT_EQUAL);
	}
	/**
	 * <
	 */
	public static function ltProperty(/*String*/ $propertyName, $otherPropertyName) {
		return new PropertyExpression($propertyName, $otherPropertyName, self::LESS_THAN);
	}
	/**
	 * <=
	 */
	public static function leProperty(/*String*/ $propertyName, $otherPropertyName) {
		return new PropertyExpression($propertyName, $otherPropertyName, self::LESS_EQUAL);
	}
	/**
	 * >
	 */
	public static function gtProperty(/*String*/ $propertyName, $otherPropertyName) {
		return new PropertyExpression($propertyName, $otherPropertyName, self::GREATER_THAN);
	}
	/**
	 * >=
	 */
	public static function geProperty(/*String*/ $propertyName, $otherPropertyName) {
		return new PropertyExpression($propertyName, $otherPropertyName, self::GREATER_EQUAL);
	}
	
	
	/**
	 * "is not null"
	 * @return Criterion
	 */
	public static function isNotNull(/*String*/ $propertyName) {
		return new NotNullExpression($propertyName);
	}
	/**
	 * 返回二个表达式的与关联
	 * 
	 * @param Criterion $lhs
	 * @param Criterion $rhs
	 * @return LogicalExpression
	 */
	public static function andd(Criterion $lhs, Criterion $rhs) {
		return new LogicalExpression($lhs, $rhs, "and");
	}
	/**
	 * 返回二个表达式的或关联
	 *
	 * @param Criterion $lhs
	 * @param Criterion $rhs
	 * @return Criterion
	 */
	public static function orr(Criterion $lhs, Criterion $rhs) {
		return new LogicalExpression($lhs, $rhs, "or");
	}
	/**
	 * 否定表达式
	 *
	 * @param expression
	 * @return Criterion
	 */
	public static function not(Criterion $expression) {
		return new NotExpression($expression);
	}
	/**
	 * 创建一个基于SQL表达式的条件；{alias}将被替换为表别名
	 * @param sql
	 * @param values
	 * @return Criterion
	 */
	public static function sqlRestriction($sql, $values=array(Criterion::NO_VALUE)) {
		return new SQLCriterion($sql, $values);
	}

	/**
	 * 一组“与”关系表达式的conjunction (A and B and C...)
	 *
	 * @return Conjunction
	 */
	public static function conjunction() {
		return new Conjunction();
	}

	/**
	 * 一组“或”关系表达式的disjunction (A or B or C)
	 * @return Disjunction
	 */
	public static function disjunction() {
		return new Disjunction();
	}

	/**
	 *  对哈希数组的每一个key应用equal约束
	 *
	 * @param array $propertyNameValues 
	 * @return conjunction
	 */
	public static function allEq($propertyNameValues) {
		$conj = self::conjunction();
		foreach ($propertyNameValues as $column=>$value){
			if($value instanceof Criterion) $c = $value;
			else $c = self::eq($column, $value);
			$conj->add($c);
		}

		return $conj;
	}	
}

class SimpleExpression extends Criterion {
	/**
	 * 
	 * Enter description here ...
	 * @var String
	 */
	protected $propertyName;
	/**
	 * 
	 * Enter description here ...
	 * @var Object
	 */
	private $value;
	
	protected $transformed = false;
	/**
	 * 
	 * Enter description here ...
	 * @var boolean 
	 */
	private $ignoreCase = false;
	/**
	 * 
	 * 操作符
	 */
	private $op;

	public function __construct($propertyName, $value, $op, $ignoreCase=null) {
		$this->propertyName = $propertyName;
		$this->value = $value;
		if (!is_null($ignoreCase)) $this->ignoreCase = $ignoreCase;
		$this->op = $op;
	}

	public function ignoreCase() {
		$this->ignoreCase = true;
		return this;
	}

	/**
	 * @param bool $pKeep 如果为真，一般为update或insert使用，不进行like转换
	 * @see Criterion::toSqlString()
	 */
	public function toSqlString(SOSO_ORM_Criteria $criteria,$pKeep=false){
		$column = $this->pickColumn($criteria, $this->propertyName);
		$op = $this->getOp();
		if (!$pKeep && $criteria->isLikeEnabled() && stripos($op, 'like') === false){
			$array = array('='=>SOSO_ORM_Restrictions::LIKE,
				'!='=>SOSO_ORM_Restrictions::NOT_LIKE,
				'<>'=>SOSO_ORM_Restrictions::NOT_LIKE
			);
			if (!$this->transformed && isset($array[trim($op)]) && strlen($this->value) && !$criteria->isDigtial($criteria->getColumnType($column))) {
				$op = $array[trim($op)];
				$this->value = SOSO_ORM_MatchMode::toMatchString($this->value,$criteria->getMatchMode());
				$this->transformed = true;
			}
		}
		return $column .' '. $op . ' ? ';
	}

	public function getTypedValues(SOSO_ORM_Criteria $criteria){
		if ($this->ignoreCase) $this->value = strtolower($this->value);
		return $this->value;
	}

	public function __toString() {
		return $this->propertyName . $this->getOp() . $this->value;
	}

	protected function getOp() {
		return $this->op;
	}

}

class BetweenExpression extends Criterion {

	protected $propertyName;
	private $lo;
	private $hi;

	public function __construct($propertyName, $lo, $hi) {
		$this->propertyName = $propertyName;
		$param = array($lo,$hi);
		sort($param);
		$this->lo = $param[0];
		$this->hi = $param[1];
	}

	public function toSqlString(SOSO_ORM_Criteria $criteria,$alternate=false){
		$column = $this->pickColumn($criteria, $this->propertyName);
		return sprintf("(%s between ? and ?)",$column);
	}

	public function getTypedValues(SOSO_ORM_Criteria $criteria){
		return array($this->lo,$this->hi);
	}

	public function __toString() {
		return $this->propertyName . " between " . $this->lo . " and " . $this->hi;
	}
}

class InExpression extends Criterion {

	protected $propertyName;
	private $values;
	protected $param;

	public function __construct($propertyName, $values,$param=null) {
		$this->propertyName = $propertyName;
		$this->values = $values;
		$this->param = $param;
	}

	public function toSqlString(SOSO_ORM_Criteria $criteria,$alternate=false){
		$column = $this->pickColumn($criteria, $this->propertyName);

		if (is_array($this->values)){
			$this->values = sprintf("'%s'",implode("','",$this->values));
		}
		//不能用bind,否则需要按values的个数，构造对应的'?'
		return $column . ' IN ('. $this->values.')';
	}

	public function getTypedValues(SOSO_ORM_Criteria $criteria){
		return $this->param ? $this->param : self::NO_VALUE;
	}

	public function toString() {
		return $this->propertyName . " in ('" . join("','",$this->values) . "')";
	}

}

/**
 * 字段约束为null
 * 
 */
class NullExpression extends Criterion {

	protected $propertyName;

	private static $NO_VALUES ;

	public function __construct($propertyName) {
		$this->propertyName = $propertyName;
		self::$NO_VALUES = self::NO_VALUE;
	}

	public function toSqlString(SOSO_ORM_Criteria $criteria,$alternate=false){
		$column = $this->pickColumn($criteria, $this->propertyName);
		return $alternate ? " $column = null " : ' ( ' . $column .' is null ) ';
	}

	public function getTypedValues(SOSO_ORM_Criteria $criteria){
		return self::$NO_VALUES;
	}

	public function __toString() {
		return $this->propertyName . " is null";
	}

}

/**
 * 
 * 针对二个字段属性比较的超类
 * @author moonzhang
 */
class PropertyExpression extends Criterion {

	protected $propertyName;
	private $otherPropertyName;
	private $op;

	private static $NO_TYPED_VALUES;

	public function __construct($propertyName, $otherPropertyName, $op) {
		$this->propertyName = $propertyName;
		$this->otherPropertyName = $otherPropertyName;
		$this->op = $op;
		self::$NO_TYPED_VALUES = self::NO_VALUE;
	}

	public function toSqlString(SOSO_ORM_Criteria $criteria,$alternate=false) {
		$xcolumn = $this->pickColumn($criteria, $this->propertyName);
		$ycolumn = $this->pickColumn($criteria, $this->otherPropertyName);
		return $xcolumn . $this->getOp() . $ycolumn;
	}

	public function getTypedValues(SOSO_ORM_Criteria $criteria){
		return self::$NO_TYPED_VALUES;
	}

	public function __toString() {
		return $this->propertyName . $this->getOp() . $this->otherPropertyName;
	}

	public function getOp() {
		return $this->op;
	}
	
	public function getTables(){
		$pos = strpos($this->propertyName, '.');
		$pos2 = strpos($this->otherPropertyName,'.');
		$tables = array();
		if (false !== $pos) $tables[] = substr($this->propertyName,0,$pos);
		if (false !== $pos2) $tables[] = substr($this->otherPropertyName,0,$pos2);
		return array_unique($tables);
	}
}

/**
 * 约束某个字段为非空
 * @author moonzhang
 */
class NotNullExpression extends Criterion {

	protected $propertyName;

	private static $NO_VALUES;

	public function __construct($propertyName) {
		$this->propertyName = $propertyName;
		self::$NO_VALUES = self::NO_VALUE;
	}

	public function toSqlString(SOSO_ORM_Criteria $criteria,$alternate=false){
		$column = $this->pickColumn($criteria, $this->propertyName);
		return ' ( ' . $column .' IS NOT NULL ) ';
	}

	public function getTypedValues(SOSO_ORM_Criteria $criteria) {
		return self::$NO_VALUES;
	}

	public function __toString() {
		return $this->propertyName . " is not null";
	}

}

/**
 * 
 * 逻辑表达式的超类
 * @author moonzhang
 */
class LogicalExpression extends Criterion {

	/*
	 * @var Criterion
	 */
	private $lhs;
	/*
	 * @var Criterion
	 */
	private $rhs;
	private $op;

	public function __construct(Criterion $lhs, Criterion $rhs, $op) {
		$this->lhs = $lhs;
		$this->rhs = $rhs;
		$this->op = $op;
	}

	public function getTypedValues(SOSO_ORM_Criteria $criteria) {
		$lvalue = $this->lhs->getTypedValues($criteria);
		$rvalue = $this->rhs->getTypedValues($criteria);
		$ret = array($lvalue,$rvalue);
		return $ret;
	}
	
	public function getTables(){
		$tables = array();
		$tables[] = $this->lhs->getTable();
		$tables[] = $this->rhs->getTable();
		$tables = array_filter($tables);
		return $tables;
	}

	public function toSqlString(SOSO_ORM_Criteria $criteria,$alternate=false){
		return '(' . 
			' ' . $this->lhs->toSqlString($criteria) .
		 	' ' . $this->getOp() .
		 	' ' . $this->rhs->toSqlString($criteria) .
		 	')';
	}

	public function getOp() {
		return $this->op;
	}

	public function __toString() {
		return $this->lhs . ' ' . $this->getOp() . ' ' + $this->rhs;
	}
}

class NotExpression extends Criterion {

	/**
	 * 
	 * Enter description here ...
	 * @var Criterion
	 */
	private $criterion;

	public function __construct(Criterion $criterion) {
		$this->criterion = $criterion;
	}

	public function toSqlString(SOSO_ORM_Criteria $criteria,$alternate=false) {
		return ' not (' . $this->criterion->toSqlString($criteria) . ')';
	}

	public function getTypedValues(SOSO_ORM_Criteria $criteria){
		return $this->criterion->getTypedValues($criteria);
	}

	public function __toString() {
		return "not " . $this->criterion;
	}
	
	public function getTable(){
		return $this->criterion->getTable();
	}
	
	public function getTables(){
		return $this->criterion->getTables();
	}
}

/**
 * SQL 片段. 可用于prepare+bind
 * @todo 实现一种super sql语言??faint!!
 */
class SQLCriterion extends Criterion {

	private $sql;
	private $typedValues;

	/**
	 * 
	 * 支持将SQL中的{alias}替换为主表别名
	 */
	public function toSqlString(SOSO_ORM_Criteria $criteria,$alternate=false){
		return str_replace('{alias}',$criteria->getAliasTable($criteria->getPrimaryTableName()),$this->sql);
	}

	public function getTypedValues(SOSO_ORM_Criteria $criteria) {
		return $this->typedValues;
	}

	public function __toString() {
		return $this->sql;
	}

	public function __construct($sql, $values) {
		$this->sql = $sql;
		$this->typedValues = $values;
	}
}

class Conjunction extends Junction {

	public function __construct() {
		parent::__construct("AND");
	}

}

class Disjunction extends Junction {

	public function __construct() {
		parent::__construct("OR");
	}

}
/**
 * 逻辑表达式队列
 * 
 */
class Junction extends Criterion {

	private $criterions = array();
	private $op;
	
	public function __construct($op) {
		$this->op = $op;
	}
	
	/**
	 * 
	 * Enter description here ...
	 * @param Criterion $criterion
	 * @return Junction
	 */
	public function add(Criterion $criterion) {
		$this->criterions[] = $criterion;
		return $this;
	}

	public function getOp() {
		return $this->op;
	}
	
	public function getTypedValues(SOSO_ORM_Criteria $criteria){
		$typedValues = array();
		
		foreach ($this->criterions as $criterion){
			$typedValues[] = $criterion->getTypedValues($criteria);
		}
		return $typedValues;
	}

	public function toSqlString(SOSO_ORM_Criteria $criteria,$alternate=false){
		if (count($this->criterions) == 0) return "1=1";
		$buffer = array();
		foreach ($this->criterions as $criterion){
			$buffer[] = $criterion->toSqlString($criteria);
		}
		
		return '(' . join(' '.$this->getOp().' ',$buffer) . ')';
	}

	public function __toString() {
		return '('.join(' '.$this->op.' ',$this->criterions) . ')';
	}

}

class Restrictions extends SOSO_ORM_Restrictions{}