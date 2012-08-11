<?php
/**
 * @author moonzhang@tencent.com
 * @version 0.0.0.1 2009-07-20
 * 
 */
/**
 * 原Pretreating新实现
 */
class SOSO_Bingo_Pretreating extends SOSO_Filter_Abstract {
	public function __construct(){
		$this->doPreProcessing();
	}
	
	public function doPreProcessing(SOSO_Frameworks_Context $context){
		
		$a = $this->setKeywordData();
		
		$this->setActionParam();
		
		$this->setTabData();
		//add by ball
		$this->setId();
	}
	
	public function doPostProcessing(SOSO_Frameworks_Context $context){
	
	}
	
	protected function setKeywordData()	{
		Logger::getInstance()->makePair('w', WinRequest::getValue("w"));
		Logger::getInstance()->makePair('kwsvr_ret', '');
//		//add by ball for new search test
//		//Logger::getInstance()->makePair('testId', WinRequest::getValue("testId"));
		Logger::getInstance()->startTime('kwsvr_tv');
		$this->kwData = SOSO_Bingo_Access::keywordQuery( $_REQUEST['w'] );
		Logger::getInstance()->endTime('kwsvr_tv');

		//关键词svr出错
		if($this->kwData === false)	{
			Logger::getInstance()->makePair('kwsvr_ret', 'kwsvr not found');
			header("HTTP/1.0 404 Not Found");
			header("Cache-Control: no-cache");
        	header("Expires: Mon, 10 Jan 2000 00:00:00 GMT");
			exit;
		}
		//关键词svr返回未找到该词
		else if($this->kwData === '') {
			//DM::dump("kwsvr not found");
			Logger::getInstance()->makePair('kwsvr_ret', 'not found');
			header("HTTP/1.0 404 Not Found");
			exit;
		}
		SOSO_Frameworks_Registry::getInstance()->set('Pretreating',$this);
		Logger::getInstance()->makePair('kwsvr_ret', 'ok');
	}
	
	protected function setActionParam()	{
		$this->_selTypeIndex = 0;
		$c = WinRequest::getValue('c');
		
		if(!empty($c)){
			foreach($this->kwData as $kwData){
				if($c == $kwData['type'])
					break;
				$this->_selTypeIndex++;
			}
			if($this->_selTypeIndex >= count($this->kwData))
				$this->_selTypeIndex = 0;
		}
		
		$typeId = $this->kwData[$this->_selTypeIndex]["type"];
		// for transition
		$bingoType = BingoType::getDisplayName($typeId);
		// end
		WinRequest::setValue('type', $typeId);
		// for transition
		WinRequest::setValue('c', $bingoType);
		// end
	}
	
	protected function setTabData(){
		Logger::getInstance()->startTime('inter_tv');
		$kwDatas = $this->kwData;
		if(count($kwDatas) <= 0){
			Logger::getInstance()->endTime('inter_tv');
			$this->_data = false;
			return true;
		}
		
		if(!file_exists(ROOT_PRO_PATH."/etc/typeDesc.inc")) return false;
		include(ROOT_PRO_PATH."/etc/typeDesc.inc");
		$data = array();
		foreach($kwDatas as $kwData){
			if(isset($typeDesc[$kwData['type']]))
				$data[] = array('type' => $kwData['type'], 'term' => $kwData['term'], 'desc' => $typeDesc[$kwData['type']], 'selected' => 0);
		}
		$data[$this->_selTypeIndex]['selected'] = 1;
		
		//for w3c header only
		if (count($kwDatas) == 1){
			$data[0]['noDelimiter'] = 1;
			$this->_tabData['output_w3c'] = $data;
			$this->_tabData['input_w3c'] = WinRequest::getRequest();		
		}
		//for both
		else {
			$this->_tabData['output'] = $data;
			$this->_tabData['input'] = WinRequest::getRequest();
			
			//w3c
			if ($this->_selTypeIndex > 0){
				$data[$this->_selTypeIndex - 1]['noDelimiter'] = 1;
			}
			$data[$this->_selTypeIndex]['noDelimiter'] = 1;
			$data[sizeof($data) - 1]['noDelimiter'] = 1;
			$this->_tabData['output_w3c'] = $data;
			$this->_tabData['input_w3c'] = WinRequest::getRequest();
		}
//		DM::dump($data);
		Logger::getInstance()->endTime('inter_tv');
	}
	
	//setActionParam()之后调用
	protected function setId(){
		$this->_id['zdqId'] = WinRequest::getValue('type');
		if(!file_exists(ROOT_PRO_PATH."/etc/zdq2icenter")){
			$this->_id['icenterId'] = 0;
			include(ROOT_PRO_PATH."/etc/zdq2icenter.inc");
			$this->_id['icenterId'] = $zdq2icenter[$this->_id['zdqId']]; 
		}
	}
	
	public function getKwData($all = false){
		if($all == true) return $this->kwData;
		return $this->kwData[$this->_selTypeIndex];
	}
	
	public function getTabData() {
		return $this->_tabData;
	}
	
	public function getId(){
		return $this->_id;
	}
	
	
	protected $kwData;
	
	protected $_tabData;
	
	protected $_selTypeIndex;
	
	//直达区id及个人中心id(add by ball)
	protected $_id;
}
?>
