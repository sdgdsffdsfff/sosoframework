<?php
/**
 * @author moonzhang (zyfunny@gmail.com)
 * @version 0.0.1 2009-03-28
 * @created 01-六月-2009 17:43:07
 */
/**
 *  容器类
 * 
 * todo :
 *    1. globalize getKeyFn to save memory cost?!
 *	  2. iterator 2 array ?
 */
class SOSO_Base_Data_Collection extends SOSO_Base_Util_Observable {
	public $items = array();
	public $map = array();
	public $keys = array();
	public $length = 0;
	public $getKeyFn;

	/**
	 * 构造函数
	 *
	 * @param {Function/Array} $keyFn 
	 */
	public function __construct($keyFn=null){
		if (!is_null($keyFn) && is_callable($keyFn)){
			$this->registeGetKeyFn($keyFn);
		}else{
			//方法注入，hiahia!
			$this->registeGetKeyFn(array($this,'defaultGetKey'));
			//$this->registeGetKeyFn(create_function('$o','return $o->id;'));
		}
		$this->addEvents(array('clear','add','replace','remove'));
	}

	function __destruct(){
		$this->purgeListeners();
		$this->clear();
		unset($this->getKeyFn);
		$this->items = array();
		$this->map = array();
		$this->keys = array();
	}

	/**
	 * 
	 * @param string $key
	 * @param mixed  $val
	 */
	public function add($key, $val=null){
		if (is_null($val)){
			$val = $key;
			$key = $this->getKey($val);
		}
		if (is_null($key)){
			++$this->length;
			array_push($this->items,$val);
			array_push($this->keys,null);
		}else{
			$old = isset($this->map[$key]) ? $this->map[$key] : null;
			if($old){
				return $this->replace($key,$val);
			}
			++$this->length;
			array_push($this->items,$val);
			$this->map[$key] = $val;
			array_push($this->keys,$key);
		}
		$this->fireEvent('add',$this->length-1,$val,$key);
		return $val;
	}
	
	public function insert($index,$key,$o=null){
		if (is_null($o)){
			$o = $index;
			$key = $this->getKey($o);
		}
		if ($index >= $this->length){
			return $this->add($key,$o);
		}
		++$this->length;
		array_splice($this->items,$index,0,$o);
		if (!is_null($key)){
			$this->map[$key] = $o;
		}
		array_splice($this->keys,$index,0,$key);
		$this->fireEvent('add',$index,$o,$key);
		return $o;
	}
	
	public function getKey($o){
		return (is_array($o) || is_callable($this->getKeyFn)) ? call_user_func_array($this->getKeyFn,array($o)) : null;
	}
	
	/**
	 * 默认的获得主键方法，可通过构造函数参数或registeGetKeyFn方法进行覆盖
	 *
	 * @param {Object} $o
	 * @return {Number} 元素的主键
	 */
	public function defaultGetKey($o){
		return $o->id;		
	}
	
	/**
	 * 注册获得主键的函数，可是匿名，也可是指定对象的方法
	 *
	 * @param {Function/Array} $lambda
	 */
	public function registeGetKeyFn($lambda){
		$this->getKeyFn = $lambda;
	}
	
	public function remove($o){
		return $this->removeAt($this->indexOf($o));
	}
	
	public function removeAt($index){
		if ($index < $this->length && $index >= 0){
			--$this->length;
			$o = $this->items[$index];
			array_splice($this->items,$index,1);
			$key = $this->keys[$index];
			if (isset($key)){
				unset($this->map[$index]);
			}
			array_splice($this->keys,$index,1);
			$this->fireEvent('remove',$o,$key);
			return $o;
		}
		return false;
	}
	/**
	 * 
	 * @param key
	 * @param val
	 */
	public function replace($key, $val=null)	{
		if (is_null($val)){
			$val = $key;
			$key = $this->getKey($val);
		}
		$old = $this->item($key);
		if (is_null($key) || is_null($old)){
			return $this->add($key,$val);
		}
		
		$index = $this->indexOfKey($key);
		$this->items[$index] = $val;
		$this->map[$key] = $val;
		$this->fireEvent('replace',$key,$old,$val);
		return $val;
	}

	/**
	 * 
	 * @param objs
	 */
	public function addAll($objs){
		if (is_array($objs)){
			for($i=0,$len=count($objs);$i<$len;$i++){
				$this->add($objs[$i]);
			} 
		}else{
			foreach ($objs as $key=>$val){
				$this->add($key,$val);
			}
		}
		return $this;
	}

	/**
	 * 函数$fn可以返回boolean类型，如果返回false，则中断迭代
	 * @param fn
	 * @param scope
	 */
	public function each($fn, $scope = null){
		$items = array_merge(array(),$this->items);
		if (is_null($scope)){
			if (!is_callable($fn)){
				return;
			}
			$involker = $fn;
		}else /*if (method_exists($scope,$fn))*/{ // delegation pattern may not fit !
			$involker = array($scope,$fn);
		}
		for($i=0,$len=count($items);$i<$len;$i++){
			if (call_user_func_array($involker,array($items[$i],$i,$len)) === false){
				break;
			}
		}
	}

	/**
	 * 对所有key进行遍历
	 * @param fn
	 * @param scope
	 */
	public function eachKey($fn, $scope = null){
		$involker = is_null($scope) ? $fn : array($scope,$fn);
		for($i=0,$len=count($this->keys);$i<$len;$i++){
			call_user_func_array($involker,array($this->keys[$i],$this->items[$i],$i,$len));
		}
	}

	/**
	 * 
	 * @param fn
	 * @param scope
	 */
	public function find($fn, $scope = null){
		$involker = is_null($scope) ? $fn : array($scope,$fn);
		for($i=0,$len=count($this->items);$i<$len;$i++){
			if (call_user_func_array($involker,array($this->items[$i],$this->keys[$i]))){
				return $this->items[$i];
			}
		}
		return null;
	}

	/**
	 * 返回指定key（$obj）的索引(index)值
	 * @param obj
	 */
	public function indexOf($obj){
		$res = array_search($obj,$this->items);
		return false === $res ? -1 : $res;
	}
	
	/**
	 * 返回指定key（$obj）的索引(index)值
	 * @param obj
	 */
	public function indexOfKey($key){
		$res = array_search($key,$this->keys);
		return false === $res ? -1 : $res;
	}	
	
	/**
	 * 返回数据条数
	 *
	 * @return {Number} 
	 */
	public function getCount(){
		return $this->length;
	}
	
	/**
	 * 返回与指定key关联的元素.Key优先于索引(index).此函数是itemAt{@link #itemAt}与key方法的结合体。
	 * 
	 * @param {String/Number} key The key or index of the item.
	 * @param key
	 * @return {Object} 与指定key关联的元素.
	 */
	public function item($key){
		$item = isset($this->map[$key]) ? $this->map[$key] : $this->items[$key];
		return $item;
	}
	
	/**
	 * alias for item method
	 */
	public function get($key){
		return $this->item($key);
	}
	
	/**
	 * 
	 * @param index
	 */
	public function itemAt($index){
		return $this->items[$index];
	}

	public function first(){
		return $this->items[0];
	}
	
	public function last(){
		return $this->items[count($this->items)-1];
	}
	/**
	 * 根据key返回关联的数据
	 */
	public function key($key){
		return $this->map[$key];
	}

	/**
	 * 
	 * @param obj
	 */
	public function contains($obj){
		return -1 != $this->indexOf($obj);
	}
	
	/**
	 * 清除所有记录
	 *
	 */
	public function clear(){
		$this->items = array();
		$this->keys = array();
		$this->map = array();
		$this->length = 0;
		$this->fireEvent('clear');
		return $this;
	}

	/**
	 * 
	 * @param key
	 */
	public function offsetExists($key)
	{
	}

	/**
	 * 
	 * @param key
	 */
	public function offsetGet($key)	{
	}

	/**
	 * 
	 * @param key
	 * @param val
	 */
	public function offsetSet($key, $val){
	}

	/**
	 * 
	 * @param key
	 */
	public function offsetUnset($key)
	{
	}
	
	public function getIterator(){
		return new ArrayIterator($this->items);	
	}
}
?>