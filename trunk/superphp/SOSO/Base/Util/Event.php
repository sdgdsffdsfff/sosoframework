<?php
/**
 * @author moonzhang (zyfunny@gmail.com)
 * @version 0.0.1 2009-06-04 
 *
 * super class for Event-Driven Model
 */
/**
 * 事件类
 *
 */
class SOSO_Base_Util_Event {
	protected $name;
	protected $listeners = array();
	/**
	 * hook for observable object
	 * @var SOSO_Base_Util_Observable
	 */
	protected $obj;
	/**
	 * 事件初始化
	 *
	 * @param SOSO_Base_Util_Observable $observable
	 * @param string $event
	 */
	public function __construct($observable,$event){
		$this->name = $event;
		$this->obj = $observable;
	}
	
	public function addListener($fn,$scope=null,$options=array()){
		$l = array('fn'=>$fn,'fireFn'=>$fn,'scope'=>$scope,'options'=>$options);
		array_push($this->listeners,$l);
		return $this;
	}
	
	private function isListening($fn,$scope){
		return $this->findListener($fn,$scope) != -1;
	}
	
	public function findListener($fn,$scope=null){
		//$scope = is_null($scope) ? $this->obj : $scope;
		$ls = $this->listeners;
		for($i=0,$len=count($ls);$i<$len;$i++){
			$l = $ls[$i];
			if ($l['fn'] == $fn && $l['scope'] == $scope){
				return $i;
			}
		}
		return -1;
	}
	
	public function removeListener($fn,$scope=null){
		$index=$this->findListener($fn,$scope);
		if (-1 != $index){
			array_splice($this->listeners,$index,1);
			return true;
		}
		return false;
	}
	public function clearListeners(){
		$this->listeners = array();
	}
	
	public function hasListener(){
		return !!$this->listeners;		
	}
	/**
	 * 执行侦听器函数
	 */
	public function fire($param=array()){
		$ls = $this->listeners;
		
		$len = count($ls);
		if ($len > 0) {
			for($i=0;$i<$len;$i++){
				$l = $ls[$i];
				$arg = $param;
				if ($l['options']){
					$arg = array_merge($arg,array('options'=>$l['options']));
				}
				// may be optimized later?
				$scope = isset($l['scope']) ? $l['scope'] : null;
				if ($scope){
					$involker = array($scope,$l['fireFn']);
				}else{
					$involker = $l['fireFn'];
				}
				if (call_user_func_array($involker,$arg) === false){
					return false;
				}
			}
		}
		return true;
	}
}

?>