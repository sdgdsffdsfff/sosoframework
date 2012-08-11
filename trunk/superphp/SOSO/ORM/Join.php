<?php
class SOSO_ORM_Join{
	const EQUAL = "=";
	
	protected $left = array();

	protected $right = array();

	protected $operator = array();
	
	protected $joinType = null;
	
	protected $count = 0;

	public function __construct($leftColumn = null, $rightColumn = null, $type = null){
		if(!is_null($leftColumn)) {
		  if (!is_array($leftColumn)) {
		    $this->addCondition($leftColumn, $rightColumn);
		  } else {
		    if (count($leftColumn) != count($rightColumn) ) {
			    throw new SOSO_Exception("Unable to create join because the left column count isn't equal to the right column count");
		    }
		    foreach ($leftColumn as $key => $value){
		      $this->addCondition($value, $rightColumn[$key]);
		    }
		  }
		  $this->setType($type);
		}
	}
	
	public function addCondition($left, $right, $operator = self::EQUAL){
		$this->left[] = $left;
		$this->right[] = $right;
		$this->operator[] = $operator;
		$this->count++;
	}
	
	public function countConditions(){
	  return $this->count;
	}
	
	public function getConditions(){
	  $conditions = array();
	  for ($i=0; $i < $this->count; $i++) { 
	    $conditions[] = array(
	      'left'     => $this->getLeftColumn($i), 
	      'operator' => $this->getOperator($i),
	      'right'    => $this->getRightColumn($i)
	    );
	  }
	  return $conditions;
	}

  public function getOperator($index = 0){
    return $this->operator[$index];
  }
	
	public function getOperators(){
	  return $this->operator;
	}
  
	public function setType($type = null){
	  $this->joinType = $type;
	}
	
	public function getType(){
		return $this->joinType;
	}

	public function getLeftColumn($index = 0){
		return $this->left[$index];
	}
	
	public function getLeftColumns(){
		return $this->left;
	}


	public function getLeftColumnName($index = 0){
		return substr($this->left[$index], strrpos($this->left[$index], '.') + 1);
	}

	public function getLeftTableName($index = 0){
		return substr($this->left[$index], 0, strrpos($this->left[$index], '.'));
	}

	public function getRightColumn($index = 0){
		return $this->right[$index];
	}
	
	public function getRightColumns(){
		return $this->right;
	}

	public function getRightColumnName($index = 0){
		return substr($this->right[$index], strrpos($this->right[$index], '.') + 1);
	}

	public function getRightTableName($index = 0){
		return substr($this->right[$index], 0, strrpos($this->right[$index], '.'));
	}
}