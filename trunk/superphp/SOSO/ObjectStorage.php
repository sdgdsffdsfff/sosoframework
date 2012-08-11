<?php
/**
 * @author moonzhang (zyfunny@gmail.com)
 * @version 1.0.1 2008-12-18
 *
 */
class SOSO_ObjectStorage implements Iterator,Countable{
	private $storage = array();
	private $index = 0;

	function rewind(){
		//rewind($this->storage);
		reset($this->storage);
	}

	function valid(){
		return current($this->storage) !== false;
	}

	function key(){
		return $this->index;
	}

	function current(){
		return current($this->storage);
	}

	function next(){
		next($this->storage);
		$this->index++;
	}

	function count(){
		return count($this->storage);
	}

	function contains($obj){
		if (is_object($obj)){
			foreach($this->storage as $object){
				if ($object === $obj){
					return true;
				}
			}
		}
		return false;
	}

	function attach($obj){
		if (is_object($obj) && !$this->contains($obj)){
			$this->storage[] = $obj;
		}
	}

	function detach($obj){
		if (is_object($obj)){
			foreach($this->storage as $idx => $object){
				if ($object === $obj){
					unset($this->storage[$idx]);
					$this->rewind();
					return;
				}
			}
		}
	}
	
	public function pop(){
		$len = $this->count();
		if ($len > 0) {
			return $this->storage[$len - 1];
		}
		return null;
	}
	
	public function shift(){
		$len = $this->count();
		if ($len > 0) {
			return $this->storage[0];
		}
		return null;
	}
	public function push($obj){
		$this->attach($obj);
	}
	
	public function unshift($obj){
		return array_unshift($this->storage,$obj);
	}
	public function clear(){
		$this->storage = array();
		return $this;
	}
	
	/**
	 * 
	 * @param index
	 */
	public function getAt($index){
		return isset($this->storage[$index]) ? $this->storage[$index] : false;
	}
	
	public function first(){
		return $this->storage[0];
	}
	
	public function last(){
		return $this->storage[count($this->storage)-1];
	}
	
	public function indexOf($obj){
		if (is_object($obj)){
			foreach($this->storage as $index => $object){
				if ($object === $obj){
					return $index;
				}
			}
		}
		return -1;
	}
	
	public function remove($o){
		return $this->removeAt($this->indexOf($o));
	}
	
	public function removeAt($index){
		if ($index < $this->count() && $index >= 0){
			$o = $this->storage[$index];
			array_splice($this->storage,$index,1);
			return $o;
		}
		return false;
	}
}
?>