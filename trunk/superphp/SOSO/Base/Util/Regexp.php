<?php
/**
 * @author moonzhang (zyfunny@gmail.com)
 * @version 0.0.1 2009-03-28 
 *
 */
class SOSO_Base_Util_Regexp {
	/**
	 * ���մ������
	 *
	 * @var {function}
	 */
	protected $mFilter;
	protected $pattern ;
	private $matched = array();
	private $matched_num = 0;
	private $mAppendArgs = array();
	public $debug = false;
	
	public function __construct($pattern=''){
		ini_set("pcre.backtrack_limit",1000000);
		if (strlen($pattern)) {
			$this->setPattern($pattern);	
		}
	}
	
	/**
	 * ��������
	 *
	 * @param string $pattern
	 * @return $this
	 */
	public function setPattern($pattern){
		if (strlen($pattern)) {
			$this->pattern = $pattern;	
		}
		return $this;
	}
	
	/**
	 * ���ö�Ӧ������ƥ�������pattern(s)
	 *
	 * @param array() $subpattern ��pattern,KEY=>pattern KEY��Ӧ����pattern��ƥ����
	 * @return clone($this)
	 */
	public function subPattern($subpattern=array()){
		if (!empty($subpattern)) {
			$this->pattern = array('pattern'=>$this->pattern,'sub'=>$subpattern);
		}
		return $this->compile();
	}
	
	/**
	 * compile object
	 *
	 * @return clone($this)
	 */
	public function compile(){
		return clone($this);
	}
	
	/**
	 * ���ı�ʱ��ƥ��
	 *
	 * @param string $content
	 * @return mixed
	 */
	public function match($content='',$mergable=true){
		$this->matched_num = 0;
		if (is_string($this->pattern)) {
            $s = gettimeofday();
			$this->matched_num = preg_match_all($this->pattern,$content,$match);
			if (!$this->matched_num){
				return $this->invokeFilter(array(array()));
			}
			if ($this->debug) {
				print_r($match);
			}
			//array_shift($match);
			unset($match[0]);
			$res = $this->invokeFilter(array($match),$mergable);
            return $res;
		}else{
			$bool = preg_match_all($this->pattern['pattern'],$content,$match,PREG_SET_ORDER);
			if (!$bool){
				return $this->invokeFilter(array(array()));
			}
			if (empty($this->pattern['sub'])) {
				return $this->invokeFilter(array($match));
			}
			if ($mergable){
				foreach ($match as $k=>$item){
					if (0 == $k){
						continue;
					}
					foreach($item as $subk=>$subv){
						$match[0][$subk] .= $subv;	
					}
				}
				$match = array($match[0]);
			}
			foreach ($match as $k=>$v){
				foreach ($v as $index=>$value){
					if (0 == $index) {
						continue;
					}
					//$ret[$index] = isset($ret[$index]) ? $ret[$index] : array();
					if (isset($this->pattern['sub'][$index])) {
						$bool = preg_match_all($this->pattern['sub'][$index],$value,$submatch);
						if (!$bool) {
							$v[$index] = '';
							continue;
						}
						if ($this->debug) {
							print_r($submatch);
						}
						array_shift($submatch);
						if (count($submatch) == 1) {
							$submatch = current($submatch);
						}
						$value = $submatch;
					}
					$v[$index] = $value;
					$this->matched_num += !is_array($value) ? 1 : count(current($value));
				}
				array_shift($v);
				
				if (1 == count($v)) {
					$v = $v[0];
				}
				$match[$k] = $v;
			}
//			if ($mergable != true){
//				$match = array($match);
//			}
			return $this->invokeFilter($match,$mergable);
		}
	}
	
	public function invokeFilter($matched=array(),$mergable=true){
		$this->matched = $matched;
		if ($mergable == true){
			$this->matched = $matched[0];
		}
		if ($this->matched_num > 0 && is_callable($this->mFilter)) {
			return call_user_func_array($this->mFilter,array($this->matched,$this->mAppendArgs));	
		}
		return $this->matched;
	}
	
	public function getMatched(){
		return $this->matched;
	}
	
	public function count(){
		return $this->matched_num;
	}
	
	public function __get($k){
		return $this->$k;
	}
	
	/**
	 * �ص�����
	 *
	 * @param {Function} $fn
	 * @param {Object} $scope     ���ö���
	 * @param {Mixed} $appendArg  �ص������ĸ��Ӳ�����Nbility Functional improvement)
	 * @return {Object} Instance of SOSO_Base_Util_Regexp
	 */
	public function registeFilter($fn,$scope=null,$appendArg=array()){
		$scope = $scope ? $scope : $this;
		
		if (method_exists($scope,$fn)) {
			$fn = array($scope,$fn);
		}
		$this->mAppendArgs = $appendArg;
		$this->mFilter = $fn;
		return $this;
	}

	public function reset(){
		foreach ($this as $k=>$v){
			$this->$k = null;
		}
		return $this;
	}
}
