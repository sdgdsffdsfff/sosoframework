<?php
/**
 * @package Session
 * @author zyfunny <zhanghe525@hotmail.com>
 * @version v 1.0 2006/02/27
 * @created 15-����-2008 16:59:23
 */

/**
 * Session_Database | session ���ݿ������
 * Update: 
 *   2008-08-07 1.��ԭ�д���������ع��ˣ�����storage���������Լ��������
 *              2.������session���ֶε����ù��ܣ�ʹ���Ӧ����������У� 
 *
 */
class SOSO_Session_Database extends SOSO_Session_Storage {

	/**
	 * Enter description here...
	 *
	 * @var SOSO_ORM_TableObject
	 */
	protected static $instance;
	protected $mOptions = array('table'=>'','dataField'=>'','idField'=>'','timeField'=>'','dbIndex'=>'');
	private $mColumns = array();

	public function setOptions($pOptions=array()){
		parent::setOptions(array_merge($this->mOptions,$pOptions));
		$this->mOptions = ($this->mOptions);
		
		if (empty($this->mOptions['table']) || !strlen($this->mOptions['dbIndex'])) {
			trigger_error("session���ݿ�������ô���,��Ҫָ������(table)�����ݿ���������(dbIndex)",E_USER_WARNING);
			exit;
		}
		if (empty($this->mOptions['dataField']) || empty($this->mOptions['timeField']) || empty($this->mOptions['idField'])) {
			throw new Exception("����ָ�����ݱ����dataField��timeField��idField",8000);
		}
		$this->mColumns['dataField'] = SOSO_Util_Util::magicName($this->mOptions['dataField']);
		$this->mColumns['idField'] = SOSO_Util_Util::magicName($this->mOptions['idField']);
		$this->mColumns['timeField'] = SOSO_Util_Util::magicName($this->mOptions['timeField']);
	}
	
	public function open() {
		if (is_null(self::$instance)) {
			$tParam = $this->getOptions();
			try{
				self::$instance = new SOSO_ORM_TableObject($tParam['table'],$tParam['dbIndex']);
			}catch (Exception $e){
				throw $e;
			}
		}
		return true;
	}

	public function close() {
		//return self::$instance->mSQLCommand && self::$instance->mSQLCommand->db_close();
		return true;
	}

	/**
	 * 
	 * @param id
	 * @return mixed
	 */
	public function read($id){
		self::$instance->_reset();
		$tDataCol = $this->mColumns['dataField'];
		
		$tTimeCol = $this->mColumns['timeField'];
		$lifetime = @ini_get("session.gc_maxlifetime");
		self::$instance->mAdditionalCondition = "{$this->mOptions['idField']} = '$id'";
		if ($lifetime > 0) {
			self::$instance->mAdditionalCondition .= " and {$this->mOptions['timeField']} >= ".time();
		}
		if (self::$instance->_select()) {
			$tTimeCol = $this->mColumns['timeField'];
			self::$instance->$tTimeCol = time()+$lifetime;
			self::$instance->_update();
			return (self::$instance->$tDataCol);
		}
		return '';
	}

	/**
	 * 
	 * @param id
	 * @param data
	 */
	public function write($id, $data) {
		self::$instance->_reset();
		$tDataCol = $this->mColumns['dataField'];
		$tIDCol = $this->mColumns['idField'];
		$tTimeCol = $this->mColumns['timeField'];
		$lifetime = @ini_get("session.gc_maxlifetime");
		
		self::$instance->$tIDCol = $id;
		if (!self::$instance->_select()) {
			self::$instance->$tDataCol = $data;
			self::$instance->$tTimeCol = time()+$lifetime;
			$ret = self::$instance->_insert();
			return $ret;
		}
		self::$instance->$tDataCol = $data;
		self::$instance->$tTimeCol = time()+$lifetime;
		$ret = self::$instance->_update();
		return $ret;
	}
	
	/**
	 * 
	 * @param id
	 */
	public function destroy($id){
		$this->open();
		self::$instance->_reset();
		self::$instance->mAdditionalCondition = '';
		$tIDCol = $this->mColumns['idField'];
		
		self::$instance->$tIDCol = $id;
		return self::$instance->_delete();
	}

	/**
	 * garbage
	 * @param lifetime
	 */
	public function gc($lifetime=null){
		$this->open();
		if (is_null($lifetime)) {
			$lifetime = @ini_get("session.gc_maxlifetime");
		}
		if ($lifetime == 0) {
			return true;
		}  
		
		self::$instance->mAdditionalCondition = "{$this->mOptions['timeField']} <= ".time();
		return self::$instance->_delete();
	}
}