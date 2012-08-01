<?php

class DbExecuter
{
	private $connection = null;
	private $fetchMode = PDO::FETCH_ASSOC;
 
	public function __construct($dataSource)
	{
	 	$this->connection = $dataSource->connect();

		// force names to lower case
        $this->connection->setAttribute(PDO::ATTR_CASE, PDO::CASE_LOWER);
        // always use exceptions.
        $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        // use utf-8
//        $this->execute("SET NAMES gbk");
//        $this->query("SET NAMES utf8");
	}
	
	private function execute($sql, $bind = array())
	{
		try
		{	
			return $this->connection->exec($sql);
		}
		catch (Exception $e)
		{
			throw new SystemException("SqlError : $sql, Message : ".$e->getMessage().", Trace : ".$e->getTraceAsString());
		}
	}
	public function prepare($sql)
	{
		try
		{	
		    return $this->connection->prepare($sql);
		}
		catch (Exception $e)
		{
			throw new SystemException("SqlError : $sql, Message : ".$e->getMessage().", Trace : ".$e->getTraceAsString());
		}
	}
	public function executeNoQuery($sql, $bind = array())
	{
        return $this->execute($sql, $bind);
	}
    public function query($sql, $bind = array())
    {
        $stmt = $this->prepare($sql);
        $stmt->execute((array) $bind);
        $rowSet = $stmt->fetchAll($this->fetchMode);
        return $rowSet;
    }
    
    public function queryForPage($sql, $pageSize, $pageNum, $bind = array())
    {
		$offset = ($pageNum - 1) * $pageSize;
		$sql = self::limit($sql, $offset, $pageSize);
		return $this->query($sql, $bind);
	}

    public function insert($table, $bind)
    {
        // col names come from the array keys
        $cols = array_keys($bind);
		$values = array_values($bind);
        // build the statement
        $sql = "INSERT INTO $table "
             . '(' . implode(', ', $cols) . ') '
             . 'VALUES (\'' . implode('\',\'', $values) . '\')';
        // execute the statement and return the number of affected rows
        if(!$result = $this->execute($sql))
        {
        	throw new BizException("ÐÂÔö²Ù×÷Ê§°Ü¡£");
        }
        return $result;
    }
    
    public function update($table, $bind, $where)
    {
        // build "col = :col" pairs for the statement
        $set = array();
        foreach ($bind as $col => $val) 
		{
            $set[] = "$col = '$val'";
        }

        // build the statement
        $sql = "UPDATE $table "
             . 'SET ' . implode(', ', $set)
             . (($where) ? " WHERE $where" : '');
        // execute the statement and return the number of affected rows
        return $this->execute($sql, $bind);
    }
    
    public function delete($table, $where)
    {
        // build the statement
        $sql = "DELETE FROM $table"
             . (($where) ? " WHERE $where" : '');

        // execute the statement and return the number of affected rows
        return $this->execute($sql);
    }

    public function beginTransaction()
    {
		$this->connection->beginTransaction();
    }

    public function commit()
    {
		$this->connection->commit();     
	}
	
	public function rollBack()
    {
		$this->connection->rollBack();     
    }

    public static function quoteIdentifier($ident)
    {
        $ident = str_replace('`', '\`', $ident);
        return "`$ident`";
    }
    
    public static function limit($sql, $offset, $count)
    {
        if ($count > 0) 
		{
            $offset = ($offset > 0) ? $offset : 0;
            $sql .= ' LIMIT $offset, $count';
        }
        return $sql;
    }

	public function getConnection()
	{
		return $this->connection;
	}
}
?>
