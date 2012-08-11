<?php
/**
 * @author moonzhang <zyfunny@gmail.com>
 * @version 1.0 2009-05-05 
 * 
 * Proxy relay list
 */
class SOSO_Base_Data_ProxyRelay extends SOSO_Base_Data_Proxy implements IteratorAggregate  {
	/**
	 * 单个代理最多连续完成请求数
	 *
	 * @var int
	 */
	private $mMaxRequestNum = 50;
	private $mProxy;
	private $mLastRequest = '';
	/**
      * 控制是否在请求失败后进行代理有效性验证
      *
      * @var bool
      */
	public  $mFailCheck = true;
	private $mRelayList = array();
	private $mHealthQueque = array();
	private $mProxyQueue = array();
	private $mTestSites = array("http://www.baidu.com"=>"#hao123#i",
	"http://www.soso.com"=>'#an\.js#i',
	"http://www.google.com"=>'#<title>Google</title>#i',
	"http://www.youdao.com/"=>'#163\.com#i');

	public function __construct($host='',$relay=array()){
		parent::__construct($host);
		if (!empty($relay)){
			$this->append($relay);
		}
	}

	public function get($ScriptFile){
		$this->mLastRequest = $ScriptFile;
		//$tSum = 2 * count($this->mTestSites);
		curl_setopt($this->ch,CURLOPT_HTTPPROXYTUNNEL,true);
		$tProxy = $this->pick();
		curl_setopt($this->ch,CURLOPT_PROXY,$tProxy);
		return parent::get($ScriptFile);
	}

	public function exec(){
		echo $this->mProxy."    ".$this->mRequestCount."        ";
		if ($this->mRequestCount >= $this->mMaxRequestNum){
			$this->mRequestCount = 0;
			$this->mProxy = null;
			//      unset($this->mRelayList[array_search($this->mProxy,$this->mRelayList)]);
			//array_push($this->mRelayList,$this->mProxy);
		}

		$tResponse = parent::exec();
		if (200 != $this->getStatus() && $this->mFailCheck ){
			if($this->validateProxy($this->mProxy)){
				echo "Proxy ({$this->mProxy}) Usable!Access ({$this->mLastRequest}) failed\n";
				return $tResponse;
			}
			echo "Proxy {$this->mProxy} lost! Re-request : {$this->mLastRequest}\n";
			return $this->get($this->mLastRequest);
		}
		return $tResponse;
	}

	public function post($url,$data=array()){

	}

	/**
      * 选择代理
      *
      * @throws runtimeexception
      * @return bool
      */
	public function pick(){
		if (empty($this->mRelayList)){
			if(empty($this->mHealthQueque)){
				throw new RuntimeException('no more proxy usable!',1);
			}
			$this->mRelayList = $this->mHealthQueque;
			$this->mHealthQueque = array();
		}
		if(!is_null($this->mProxy)) return $this->mProxy;
		while($tProxy = array_shift($this->mRelayList)){
			//              foreach ($this->mRelayList as $k=>$tProxy){
			$this->mProxyQueue[] = $tProxy;
			if (!$this->validateProxy($tProxy)){
				//                              unset($this->mRelayList[$k]);
				continue;
			}
			curl_setopt($this->ch,CURLOPT_PROXY,$tProxy);
			return $this->mProxy = $tProxy;
		}

	}

	public function getDeadQueue(){
		return $this->mProxyQueue;
	}

	/**
      * 验证代理是否可用
      *
      * @param string $proxy {IP:PORT}
      * @return bool
      */
	final public function validateProxy($proxy){
		$tProxyObj = new SOSO_Base_Data_Proxy();
		$tProxyObj->setOption(CURLOPT_PROXY,$proxy);
		$tSites = array_keys($this->mTestSites);
		$tPatterns = array_values($this->mTestSites);
		$tIndex = rand(0,count($tSites)-1);
		$tSite = array($tSites[$tIndex]=>$tPatterns[$tIndex]);
		$tStatus = $tProxyObj->get(key($tSite))->getStatus();
		//              return 200 == $tStatus;
		echo $proxy."\n";
		print_r($tSite);
		echo "key".key($tSite);
		echo "\nvalue:".current($tSite);
		echo $tProxyObj->body;
		echo "\n\n------\n";
		echo current($tSite);
		echo "\n";
		echo "status:".$tStatus."\n";
		var_dump(preg_match(current($tSite),$tProxyObj->body));
		$tFlag = (200 == $tStatus && preg_match(current($tSite),$tProxyObj->body));
		if ($tFlag){
			$this->mHealthQueque[] = $proxy;
		}
		echo "validate proxy {$proxy}".($tFlag ? 'success' : 'failed')."\n";
		return $tFlag ;
	}

	public function append($arr=array()){
		$this->mRelayList = $arr;
	}
	public function getIterator(){
		return new ArrayIterator($this->mRelayList);
	}
	public function __get($k){
		return $this->$k;
	}
}
?>