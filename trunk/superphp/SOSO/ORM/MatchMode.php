<?php
 
/**
 * 指定模糊查询策略
 *
 * @see SOSO_ORM_Criteria#enableLike(SomeMatchMode)
 * @author moonzhang
 * @version 1.0 2010-02-07 03:02:01
 */
class SOSO_ORM_MatchMode {
	private $name;
	const EXACT = 'EXACT';
	const START = 'START';
	const END   = 'END';
	const ANYWHERE = 'ANYWHERE';

	private static $PATTERNS = array('EXACT'=>'%s','START'=>'%s%%','END'=>'%%%s','ANYWHERE'=>'%%%s%%');
	protected $pattern;
	
	protected function __construct($name) {
		$this->name=$name;
	}
	public function __toString() {
		return $this->name;
	}

//	public function getInstance($mode=SOSO_ORM_Criterion_MatchMode::ANYWHERE){
//		if (isset(self::$INSTANCES[$mode])) return self::$INSTANCES[$mode];
//		if (!in_array($mode,array_keys(self::$PATTERNS))) $mode = self::ANYWHERE;
//		
//		self::$INSTANCES[$mode] = new self($mode);
//		self::$INSTANCES[$mode]->pattern = self::$PATTERNS[$mode];
//		return self::$INSTANCES[$mode];
//	}
	
	public static function toMatchString($value,$mode=null){
		if (is_null($mode) || !in_array($mode,array_keys(self::$PATTERNS))) $mode = self::EXACT;
		$pattern = self::$PATTERNS[$mode];
		return sprintf($pattern,$value);
	}
}