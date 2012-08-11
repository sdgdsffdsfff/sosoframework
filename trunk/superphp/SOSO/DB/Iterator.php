<?php
/**
 * @author moonzhang (zyfunny@gmail.com)
 * @version 1.0.1 2008-05-08 17:23:22
 * 数据库迭代器类实现
 */
class SOSO_DB_Iterator implements Iterator,ArrayAccess,Countable{

	/**
	 * 游标
	 *
	 * @var int
	 */
	public $mCursor = 0;
	public $mLength = 0;
	/**
	 * 数据库类
	 *
	 * @var SOSO_DB_SQLCommand
	 */
	public $mSQLCommand;
	public $mResult;
	public $mResultType = MYSQL_ASSOC;
	public function __construct(SOSO_DB_SQLCommand $pSQLCommand, $pResult, $pResultType=MYSQL_ASSOC) {
		if (!is_resource($pResult)) {
			throw new SOSO_Exception('不正确的result类型,请检查数据库查询是否成功');
		}
		$this->mSQLCommand = $pSQLCommand;
		$this->mResult = $pResult;
		$this->mLength = $this->mSQLCommand->db_num_rows($this->mResult);
		if (in_array($pResultType,array(MYSQL_ASSOC,MYSQL_NUM,MYSQL_BOTH))) {
			$this->mResultType = $pResultType;	
		}
	}
	
	public function rewind() {
		$this->mCursor = 0;
	}

	public function length() {
		return $this->mLength;
	}
	
	public function count() {
		return $this->mLength;
	}

	public function key() {
		return $this->mCursor;
	}

	public function current() {
		$this->mSQLCommand->db_data_seek($this->mResult,$this->mCursor);
		return $this->mSQLCommand->db_fetch_array($this->mResult, $this->mResultType);
	}

	public function next() {
		$this->mCursor++;
	}

	public function valid() {
		return $this->mCursor < $this->mLength;
	}

	function offsetExists($index) {
		return ((int)$index >= 0 && (int)$index < $this->mLength);
	}

	function offsetGet($index) {
		if (!$this->offsetExists($index)) {
			return array();
		}
		$this->mSQLCommand->db_data_seek($this->mResult,$index);
		return $this->mSQLCommand->db_fetch_array($this->mResult, $this->mResultType);
	}

	function offsetSet($name, $id) {
	}

	function offsetUnset($name) {
	}
}