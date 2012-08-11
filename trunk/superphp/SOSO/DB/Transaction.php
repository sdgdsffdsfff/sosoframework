<?php
/**
 * 
 * ÊÂÎñÀà
 * @author moonzhang (zyfunny@gmail.com)
 * @version 1.0 2010-12-12 17:55:01Z
 */
class SOSO_DB_Transaction extends SOSO_Base_Util_Observable{
	/**
	 * 
	 * Enter description here ...
	 * @var SOSO_ORM_DB_PDOSQLCommand
	 */
	private $_command=null;
	protected $events = array('transactionstart','commit','rollback','beforecommit','aftercommit','beforerollback','afterrollback');
	/**
	 * Constructor.
	 * @param SOSO_DB_PDOSQLCommand the connection associated with this transaction
	 * @see SOSO_DB_PDOSQLCommand::beginTransaction
	 */
	public function __construct($command){
		$this->_command=$command;
		$this->fireEvent('transactionstart');
	}

	/**
	 * Commits a transaction.
	 */
	public function commit(){
		return $this->_command->getPdoInstance()->commit();
	}

	/**
	 * Rolls back a transaction.
	 */
	public function rollback(){
		return $this->_command->getPdoInstance()->rollBack();
	}

	/**
	 * @return SOSO_DB_PDOSQLCommand the DB connection for this transaction
	 */
	public function getSQLCommand(){
		return $this->_command;
	}

}
