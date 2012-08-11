<?php
/**
 * SOSO Framework
 * @category   SOSO
 * @package    SOSO
 * @copyright  Copyright (c) 2007-2008 Soso.com
 * @author moonzhang
 * @version 1.0
 * @created 15-四月-2008 16:59:19
 */
class SOSO_Object{
	private $mUniqID;
	public $mAppID;
	/**
	 * 构造函数.
	 * 所有类的基类
	 */
	public function __construct(){
		$this->mAppID = uniqid('SOSO_Project');
	}

	/**
	 * 析构函数
	 */
	public function __destruct(){
		
	}

	public function __toString(){
		return get_class($this);
	}

	/**
	 * 导出为DOM树
	 */
	public function _toDom(){
		$dom = new DOMDocument('1.0','UTF-8');
		$root = get_class($this);
		$dom->appendChild(new DOMElement($root));
		return $dom;
	}
}
?>