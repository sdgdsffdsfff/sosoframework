<?php
class RequestBuilder
{
	private $total;
	private $params;

	public function __construct(){
		$this->reset();
	}

	public function addString($s){
		$this->total += strlen($s);
		$this->params[] = $s;
	}

	public function addInt($i, $len){
		$this->total += $len;
		$this->params[] = sprintf("%0{$len}d", $i);
	}

	public function build(){
		$stotal = sprintf('%04d', $this->total + 4);
		$s = implode('', $this->params);
		return $stotal . $s;
	}

	public function reset(){
		$this->total = 0;
		$this->params = array();
	}
}
?>
