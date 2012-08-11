<?php
class SOSO_Base_Data_DataFormat
{
	private $encoding;
	private $rootNode;
	
	function __construct(SOSO_Base_Data_DataFormatNode $rootNode, $encoding=''){
		if(empty($encoding))
			$encoding = 'gbk';
		$this->rootNode = $rootNode;
		$this->encoding = $encoding;
	}
	
	public function getRootNode(){
		return $this->rootNode;
	}
	
	public function getEncoding(){
		return $this->encoding;
	}
}
?>