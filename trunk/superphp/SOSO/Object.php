<?php
/**
 * SOSO Framework
 * @category   SOSO
 * @package    SOSO
 * @copyright  Copyright (c) 2007-2008 Soso.com
 * @author moonzhang
 * @version 1.0
 * @created 15-����-2008 16:59:19
 */
class SOSO_Object{
	private $mUniqID;
	public $mAppID;
	/**
	 * ���캯��.
	 * ������Ļ���
	 */
	public function __construct(){
		$this->mAppID = uniqid('SOSO_Project');
	}

	/**
	 * ��������
	 */
	public function __destruct(){
		
	}

	public function __toString(){
		return get_class($this);
	}

	/**
	 * ����ΪDOM��
	 */
	public function _toDom(){
		$dom = new DOMDocument('1.0','UTF-8');
		$root = get_class($this);
		$dom->appendChild(new DOMElement($root));
		return $dom;
	}
}
?>