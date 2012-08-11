<?php
/**
 * @author moonzhang (zyfunny@gmail.com)
 * @version 0.0.1 2009-03-28 
 *
 */

require_once(dirname(__FILE__).'/Event.php');
/**
 * 观察者基类,实现自定义事件模型
 *
 */
class SOSO_Base_Util_Observable {
	
	protected $events = array();
	//protected $listeners = array();
	protected $eventsSuspended = false;
	
	/**
     * 触发指定事件的处理函数以指定参数执行，参数至少有eventName
     * @param {String} eventName 事件名
     * @return {Boolean} 默认返回true ; 有处理函数的，以函数返回值作为本方法返回值
     */
	public function fireEvent($eventName/*,$arg=array()*/){
		if ($this->eventsSuspended !== true){
			$eventName = strtolower($eventName);
			$ce = isset($this->events[$eventName]) ? $this->events[$eventName] : null;
			if ($ce instanceof SOSO_Base_Util_Event) {
				$args = array_slice(func_get_args(),1);
				return $ce->fire($args);
			}
		}
		return true;
	}

	/**
     * 添加事件处理函数
     * @param {String}   $eventName 要监听的事件类型/名称
     * @param {Function} $fn 引入的事件方法（名）
     * @param {Object}   $scope (optional) 处理函数的作用域
     * @param {Array}	 $options 函数的参数
     */
	protected function addListener($eventName, $fn, $scope=null,$options=array()){
		$eventName = strtolower($eventName);
		$ce = isset($this->events[$eventName]) ? $this->events[$eventName] : true;
		//$scope = is_null($scope) ? $this : $scope;
		if (class_exists('Closure',false) && $fn instanceof Closure){
			$scope = null;
		}elseif(is_null($scope)){
			$scope = $this;
		}
		//var_dump($scope);
		if (!$scope || !method_exists($scope,$fn)){
			if (is_callable($fn)){
				$scope = null;
			}else{
				throw new Exception('listener($scope->$fn) is not executable!?');
			}
		}
		if (!$ce instanceof SOSO_Base_Util_Event){
			$ce = new SOSO_Base_Util_Event($this,$eventName);
			$this->events[$eventName] = $ce;
		}
		$ce->addListener($fn,$scope,$options);
		return $this;
	}
	/**
	 * Alias for addListener
	 *
	 */
	public function on($eventName, $fn, $scope=null,$options=array()){
		return $this->addListener($eventName, $fn, $scope,$options);
	}
	
	/**
	 * Alias for removeListener
	 *
	 */
	public function un($eventName, $fn, $scope=null){
		return $this->removeListener($eventName, $fn, $scope);
	}

	/**
     * Removes a listener
     * @param {String}   $eventName     侦听的事件名
     * @param {Function} $fn       要删除的函数
     * @param {Object}   $scope  (可选) $fn所在的对象
     */
	public function removeListener ($eventName, $fn, $scope){
		$ce = $this->events[strtolower($eventName)];
		if ($ce instanceof SOSO_Base_Util_Event){
			$ce->removeListener($fn,$scope);
		}
	}

	/**
     * 清除所有事件侦听器
     */
	public function purgeListeners(){
		foreach ($this->events as $evt=>$ce){
			if ($ce instanceof SOSO_Base_Util_Event){
				$this->events[$evt]->clearListeners();
			}
		}
		return $this;
	}

	/**
     * 用来定义事件名
     * @param {Object|Mixed} $obj 要定义的事件名或事件数组
     */
	public function addEvents($obj){
		if (is_string($obj)){
			for($i=0,$a=func_get_args();$v=$a[$i];$i++){
				if (!isset($this->events[$v])){
					$this->events[$v] = true;
				}
			}
		}else{
			foreach ($obj as $k=>$v){
				if (is_numeric($k)) $this->events[$v] = isset($this->events[$v]) ? $this->events[$v] : true;
				else $this->events[$k] = isset($this->events[$k]) ? $this->events[$k] : $v;
			}
		}
	}
	/**
	 * 暂停事件处理，使用本操作将中止事件处理。所有事件处理函数将不会被触发
	 * 可使用resumeEvents恢复
	 * @see resumeEvents
	 */
	public function suspendEvents(){
		$this->eventsSuspended = true;
	}
	
	/**
	 * 恢复事件处理
	 * @see suspendEvents
	 */
	public function resumeEvents(){
		$this->eventsSuspended = false;
	}
	
	/**
     * 
     * 检查本对象是否含有指定事件(eventName)的 listeners
     * @param {String} eventName 待检查的事件名
     * @return {Boolean} True if the event is being listened for, else false
     */
	public function hasListener($eventName){
		$ce = $this->events[strtolower($eventName)];
		return $ce instanceof SOSO_Base_Util_Event && $ce->hasListener();
	}

	/**
	 * 可使用事件既有的事件驱动模型进行AOP模拟，PHP不支持动态AOP,暂不实现下列方法
	public function getMethodEvent($event){}
	// 添加一个拦截器
	public function beforeMethod($method,$fn,$scope){}
	public function afterMethod($method,$fn,$scope){}
	public function removeMethodListener($method,$fn,$scope){}
	*/
}