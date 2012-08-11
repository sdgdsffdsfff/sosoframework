<?php 
/**
 * 
 * mysql 事务一般性支持
 * @author moonzhang
 * @version 1.0 2010-12-16 19:40:01
 */
class SOSO_DB_Transaction_Mysql extends SOSO_DB_Transaction{

	/**
     *
     * creates a new savepoint
     *
     * @param string $savepoint     name of a savepoint to set
     * @return void
     */
    protected function createSavePoint($savepoint) {
        $query = 'SAVEPOINT ' . $savepoint;

        return $this->_command->ExecuteQuery($query);
    }

    /**
     * 
     * releases given savepoint
     *
     * @param string $savepoint     name of a savepoint to release
     * @return void
     */
    protected function releaseSavePoint($savepoint){
        $query = 'RELEASE SAVEPOINT ' . $savepoint;

        return $this->_command->ExecuteQuery($query);
    }

    /**
     * 
     * releases given savepoint
     *
     * @param string $savepoint     name of a savepoint to rollback to
     * @return void
     */
    protected function rollbackSavePoint($savepoint){
        $query = 'ROLLBACK TO SAVEPOINT ' . $savepoint;

        return $this->_command->ExecuteQuery($query);
    }

    /**
     * Set the transacton isolation level.
     *
     * @param   string  standard isolation level
     *                  READ UNCOMMITTED (allows dirty reads)
     *                  READ COMMITTED (prevents dirty reads)
     *                  REPEATABLE READ (prevents nonrepeatable reads)
     *                  SERIALIZABLE (prevents phantom reads)
     *
     * @throws SOSO_Exception           if using unknown isolation level
     * @return void
     */
    public function setIsolation($isolation){
        switch ($isolation) {
            case 'READ UNCOMMITTED':
            case 'READ COMMITTED':
            case 'REPEATABLE READ':
            case 'SERIALIZABLE':
                break;
            default:
                throw new SOSO_Exception('Isolation level ' . $isolation . ' is not supported.');
        }

        $query = 'SET SESSION TRANSACTION ISOLATION LEVEL ' . $isolation;

        return $this->_command->ExecuteQuery($query);
    }

    /**
     * getTransactionIsolation
     *
     * @return string	returns the current session transaction isolation level
     */
    public function getIsolation() {
    	$this->_command->setQuery('SELECT @@tx_isolation');
        $row = $this->_command->queryRow(false);
        return $row[0];
    }
}