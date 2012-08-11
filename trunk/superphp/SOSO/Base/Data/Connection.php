<?php
/**
 * @author moonzhang (zyfunny@gmail.com)
 * @version 0.0.1 2009-03-28 
 *
 */
class SOSO_Base_Data_Connection extends SOSO_Base_Util_Observable{
	/**
   * @cfg {Number} timeout (Optional) The timeout in milliseconds to be used for requests. (defaults to 30000)
   */
	protected $timeout = 30000;
	
	/**
	 * 请求失败后，重复次数
	 *
	 * @var int
	 */
	protected $try_times = 0;
	
	/**
	 * 每次重试间隔时间（秒）
	 *
	 * @var int
	 */
	protected $duration = 10;

	/**
     * @opt {String} http referrer
     * @type String
     */
	protected $referrer = '';
	
	private $options = array('method'=>'get','referer'=>'','args'=>array(),'auto_refer'=>false);
	
	protected $http_proxy;
	protected $status;
	public $mContent;
	protected $currentRequest = '';
	/**
	 *
	 * @param {array} $config 连接参数
	 * e.g: referer => 源地址
	 *      timeout => 连接超时时间
	 *      method  => GET | POST
	 *      cookie  => cookie内容
	 *      auto_refer=>自动附加refer
	 * 	   	relay   =>控制是否使用代理
	 *      relay_list => 如果使用代理，供选择的代理列表
	 */
	public function __construct($config=array()){
		if (!empty($config)) {
			$this->setOptions($config);	
		}
		if(isset($config['proxy'])){
			$this->http_proxy = $config['proxy'];
		}else{
			$this->http_proxy = new SOSO_Base_Data_Proxy();
			$this->setTimeout($this->timeout);
			if ($this->referrer) $this->http_proxy->setReferrer($this->referrer);
		}
		
		$this->addEvents(array('beforerequest','afterrequest','hitcache'));
	}
	
	public function useProxyRelay($options=array()){
		$tHost = isset($options['host']) ? $options['host'] : '';
		$tRelayList = isset($options['relay_list']) ? $options['relay_list'] : array();
		$this->http_proxy = new SOSO_Base_Data_ProxyRelay($tHost,$tRelayList);
		return $this;
	}
	
	public function getProxy(){
		return $this->http_proxy;
	}	
	public function setProxy($proxy){
		$this->http_proxy = $proxy;	
	}
	
	public function getStatus(){
		return is_nulL($this->status) ? $this->http_proxy->getStatus(): $this->status;
	}
	
	public function setOptions($options=array()){
		if (empty($options)) {
			return $this;
		}
		$this->options = array_merge($this->options,$options);
		if (!in_array($this->options['method'],array("get",'post','baidu','google'))) {
			$this->options['method'] = 'get';
		}
		if (isset($this->options['http_options']) && $this->http_proxy instanceof SOSO_Base_Data_Proxy ) {
			foreach ($this->options['http_options'] as $option=>$val){
				$this->http_proxy->setOption($option,$val);
			}
		}
		if (isset($this->options['try_times'])){
			$this->setTryTimes(intval($this->options['try_times']));
		}
		
		if (isset($this->options['referer'])){
			$this->referrer = $this->options['referer'];
		}
		
		if (isset($this->options['timeout'])){
			$this->setTimeout($this->options['timeout']);
		}
		return $this;
	}

	public function request($file,$option=array()){
		if($file && $this->currentRequest == $file && $this->mContent){
			$this->fireEvent('hitcache',$file,$option,$this);
			return $this->mContent;
		}
		$this->mContent = '';
		$this->currentRequest = $file;
		if($this->fireEvent('beforerequest',$file,$option,$this) === false){
			if (strlen($this->mContent)){
				$this->status = 200;
				$this->fireEvent('hitcache',$file,$option,$this);
				return $this->mContent;
			}
		}
		$this->setOptions($option);
		$method = $this->options['method'];
		if (method_exists($this->http_proxy,$method)) {
			if (preg_match("#^http#i",$file)) {
				try{
					$this->http_proxy->$method($file,$this->options['args']);
				}catch(Exception $e){
					$tStatus = $this->http_proxy->getStatus();
					$this->mContent = '';
					throw new SOSO_Exception("Status:{$tStatus}".$e->getMessage(),$tStatus);
				}
				$this->status = $tStatus = $this->http_proxy->getStatus();
				if (200 != $tStatus) {
					set_time_limit(0);
					while($this->try_times-- >0){
						sleep($this->duration);
						return $this->request($file);
					}
					throw new SOSO_Exception("Status:{$tStatus} : URL({$file})",$tStatus);
				}
				$this->mContent = $this->http_proxy->getBody();
			}else{
				if (!file_exists($file)) {
					throw new SOSO_Exception("file not found",'0' );
				}
				$this->mContent = file_get_contents($file);
				$this->status = 200;
			}
			$this->fireEvent('afterrequest',$file,$option,$this);
			return $this->mContent;
		}
		throw new RuntimeException("method not exists");
	}
	
	public function getResponse(){
		return $this->mContent ? $this->mContent : ($this->http_proxy ? $this->http_proxy->getBody() : ''); 
		//return $this->http_proxy ? $this->http_proxy->getBody() : $this->mContent;	
	}

	public function handleFailure(){
		
	}

	public function getContent(){
		return $this->mContent;
	}
	
	public function setTimeout($msec){
		return $this->http_proxy->setTimeout($msec);
	}
	
	public function setProxyIp($ip){
		return $this->http_proxy->setProxy($ip);
	}
	
	public function setTryTimes($times){
		$this->try_times = (int)$times;
	}
}