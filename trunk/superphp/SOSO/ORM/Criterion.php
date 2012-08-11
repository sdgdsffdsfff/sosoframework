<?php
/**
 * 
 * Enter description here ...
 * @author moonzhang (zyfunny@gmail.com)
 * @version 0.1 2010-01-01
 *
 * 抽象类
 */
abstract class Criterion {
	protected $propertyName;
	public $tableName;
	const NO_VALUE = '_NULL_VALUE_FOR_CRITERION_';
	
	abstract public function toSqlString(SOSO_ORM_Criteria $criteria,$alternate=false);
	abstract public function getTypedValues(SOSO_ORM_Criteria $criteria);
	
	public function getFullName(){
		$ret = $this->getColumn();
		if (strlen($this->getTable())){
			$ret = $this->tableName . '.' . $ret;
		}
		return $ret;
	}
	public function getTable(){
		if ($this->tableName) return $this->tableName;
		if (strlen($this->propertyName)){
			$index = strpos($this->propertyName,'.');
			if (false === $index) return '';
			return $this->tableName = substr($this->propertyName,0,$index);
		}
		return '';
	}
	
	/**
	 * 
	 * 非可能涉及多表的Criterion实例，进行表汇总，以便进行cross join计算
	 */
	public function getTables(){
		return array($this->getTable());
	}
	public function getColumn(){
		$index = strpos($this->propertyName,'.');
		if (false === $index) return $this->propertyName;
		return substr($this->propertyName,$index+1);
	}
	
	public function pickColumn(SOSO_ORM_Criteria $criteria,$column){
		$index = strpos($column,'.');
		$table = '';
		$postfix = '';
		$multiTable = ($index === false) 
						&& 1 < count($criteria->getFrom()) 
						|| $criteria->getJoins();
		
		if (false !== $index) {
			$table = substr($column,0,$index);
			$col = trim(substr($column,$index+1));
			$alias = $criteria->getAliasTable($table);
			if (!$multiTable){
				$column = $criteria->quoteIdentifier($col);
			}elseif ($alias){
				$column = $criteria->quoteIdentifier($alias).'.'.$criteria->quoteIdentifier($col);
			}else{
				$column = $criteria->quoteIdentifier($table).'.'.$criteria->quoteIdentifier($col);
			}
		}elseif ($multiTable){
			$table = $criteria->getPrimaryTableName();
			$alias = $criteria->getAliasTable($table);
			if (array_key_exists($column, $criteria->getTableFields())){
				$table = $alias ? $alias : $table;
				$column = $criteria->quoteIdentifier($table).'.'.$criteria->quoteIdentifier($column);
			}else{
				$column = $criteria->quoteIdentifier($column);
			}
		}else{
			$pattern = "#^([a-z_]\w*)([^\w]\w*)?$#Ui";
			if (!preg_match_all($pattern, $column, $matches)){
				return $column;
			}
			$postfix = $matches[2][0];
			$column = $matches[1][0];
			$column = $criteria->quoteIdentifier($column);
		}
		
		if ($criteria->isIgnoreCase()) $column = strtolower($column);
		return $column.$postfix;
	}
}