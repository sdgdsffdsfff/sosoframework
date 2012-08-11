<?php
/**
 * @author moonzhang (zyfunny@gmail.com)
 * @version 0.0.1 2009-03-28 
 *
 */

require_once(dirname(__FILE__).'/Event.php');
/**
 * �۲��߻���,ʵ���Զ����¼�ģ��
 *
 */
class SOSO_Base_Util_Observable {
	
	protected $events = array();
	//protected $listeners = array();
	protected $eventsSuspended = false;
	
	/**
     * ����ָ���¼��Ĵ�������ָ������ִ�У�����������eventName
     * @param {String} eventName �¼���
     * @return {Boolean} Ĭ�Ϸ���true ; �д������ģ��Ժ�������ֵ��Ϊ����������ֵ
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
     * ����¼�������
     * @param {String}   $eventName Ҫ�������¼�����/����
     * @param {Function} $fn ������¼�����������
     * @param {Object}   $scope (optional) ��������������
     * @param {Array}	 $options �����Ĳ���
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
     * @param {String}   $eventName     �������¼���
     * @param {Function} $fn       Ҫɾ���ĺ���
     * @param {Object}   $scope  (��ѡ) $fn���ڵĶ���
     */
	public function removeListener ($eventName, $fn, $scope){
		$ce = $this->events[strtolower($eventName)];
		if ($ce instanceof SOSO_Base_Util_Event){
			$ce->removeListener($fn,$scope);
		}
	}

	/**
     * ��������¼�������
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
     * ���������¼���
     * @param {Object|Mixed} $obj Ҫ������¼������¼�����
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
	 * ��ͣ�¼�����ʹ�ñ���������ֹ�¼����������¼������������ᱻ����
	 * ��ʹ��resumeEvents�ָ�
	 * @see resumeEvents
	 */
	public function suspendEvents(){
		$this->eventsSuspended = true;
	}
	
	/**
	 * �ָ��¼�����
	 * @see suspendEvents
	 */
	public function resumeEvents(){
		$this->eventsSuspended = false;
	}
	
	/**
     * 
     * ��鱾�����Ƿ���ָ���¼�(eventName)�� listeners
     * @param {String} eventName �������¼���
     * @return {Boolean} True if the event is being listened for, else false
     */
	public function hasListener($eventName){
		$ce = $this->events[strtolower($eventName)];
		return $ce instanceof SOSO_Base_Util_Event && $ce->hasListener();
	}

	/**
	 * ��ʹ���¼����е��¼�����ģ�ͽ���AOPģ�⣬PHP��֧�ֶ�̬AOP,�ݲ�ʵ�����з���
	public function getMethodEvent($event){}
	// ���һ��������
	public function beforeMethod($method,$fn,$scope){}
	public function afterMethod($method,$fn,$scope){}
	public function removeMethodListener($method,$fn,$scope){}
	*/
}