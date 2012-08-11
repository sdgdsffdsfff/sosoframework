<?php
/**
 * @author moonzhang (zyfunny@gmail.com)
 * @version 0.0.1 2009-03-28 
 *
 */
class SOSO_Base_Data_Proxy {
	protected $ch;
	protected $header;
	protected $response;
	protected $host;
	protected $cookie;
	protected $body;
	protected $user_agent = "";
	protected $timeout = 20;
	protected $options = array(
		CURLOPT_AUTOREFERER=>true,
		CURLOPT_HEADER=>true,
		CURLOPT_RETURNTRANSFER=>true,
	);
	
	/**
	 * 总的请求数
	 *
	 * @var int
	 */
	protected $mRequestCount = 0;
	
	public function __construct($pHost=''){
		$this->ch = curl_init();
		if (strlen($pHost)) {
			$this->setHost($pHost);
		}
		$this->setUA();
		$this->options[CURLOPT_HEADERFUNCTION] = array($this,'_setHeader');
	}
	
	public function setHost($host){
		$this->host = $host;
		return $this;
	}
	
	public function clear(){
		$this->options = array(
			CURLOPT_AUTOREFERER=>true,
			CURLOPT_HEADER=>true,
			CURLOPT_RETURNTRANSFER=>true,
		);
		$this->cookie = '';
		$this->user_agent = '';
		$this->header = '';
		return $this;
	}
	
	public function setHeader($header=array()){
		$this->options[CURLOPT_USERAGENT] = $this->user_agent;
		$this->options[CURLOPT_HTTPHEADER] = $header;
		curl_setopt_array($this->ch, $this->options);
		$this->response = $this->header = '';
		return $this;
	}
	
	protected function _setHeader($ch,$head){
		$this->header .= $head;
		return strlen($head);
	}
	
	public function setUA($ua=''){
		if (!strlen($ua)){
			$this->user_agent = "Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; SV1; .NET CLR 1.1.4322; InfoPath.2)";
		}else{
			$this->user_agent = $ua;
		}
		return $this;
	}
	
	/**
	 * 伪装成baidu机器人
	 *
	 * @param string $ScriptFile
	 * @return mixed
	 */
	public function baidu($ScriptFile=''){
		$this->setUA("Baiduspider+(+http://www.baidu.com/search/spider.htm)");
		return strlen($ScriptFile) ? $this->get($ScriptFile) : $this;
	}

	/**
	 * 伪装成gg机器人
	 *
	 * @param string $ScriptFile
	 * @return mixed
	 */
	public function google($ScriptFile=''){
		$this->setUA("Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)");
		return strlen($ScriptFile) ? $this->get($ScriptFile) : $this;
	}
	
	public function get($ScriptFile){
		if (strlen($this->cookie)) {
			$this->setCookie();
		}
		if(preg_match("#^https#i",$ScriptFile)) {
			$this->setOption(CURLOPT_SSL_VERIFYPEER,false);
			$this->setOption(CURLOPT_SSL_VERIFYHOST,2);
		}
		
		$this->setOption(CURLOPT_URL,$this->host.$ScriptFile);
		$this->setOption(CURLOPT_POST,0);
		$this->setOption(CURLOPT_COOKIE,$this->cookie);
		$this->setHeader();
		$this->exec();
		$this->parseResponse();
		return $this;
	}
	
	protected function parseResponse(){
		$this->body = strpos($this->response,$this->header) === false ? $this->response : substr($this->response,strlen($this->header));
		preg_match_all('#^Set-Cookie:(.+)\s*$#Umsi',$this->header,$m);
		$this->cookie = join(";",$m[1]);
	}
	
	public function post($url,$data=array()){
		$this->setOption(CURLOPT_URL,$this->host.$url);
		$this->setOption(CURLOPT_POST,1);
		$this->setOption(CURLOPT_POSTFIELDS,$data);
		$this->setHeader();
		$this->exec();
		$this->parseResponse();
		return $this;
	}
	public function exec(){
		++$this->mRequestCount;
		$this->response = curl_exec($this->ch);
		return $this->response;
	}
	
	public function getBody(){
		return $this->body;
	}
	
	public function getHeader(){
		return $this->header;
	}
	
	public function setCookie($cookie=''){
		if (strlen($cookie)) {
			$this->cookie = $cookie;
		}
		$this->setOption(CURLOPT_COOKIE,$this->cookie);
		return $this;
	}
	
	public function getStatus(){
		return $this->getInfo(CURLINFO_HTTP_CODE);
	}
	
	public function setReferrer($url){
		$this->setOption(CURLOPT_REFERER,$url);
		return $this;
	}
	
	public function setTimeout($timeout=15){
		$this->timeout = $timeout;
		$opt = $timeout > 100 ? 156 : CURLOPT_CONNECTTIMEOUT;
		$this->setOption($opt, $timeout);
		return $this;
	}
	
	public function hasError(){
		return curl_errno($this->ch);	
	}
	
	public function getError(){
		return curl_error($this->ch);
	}
	
	public function setOption($option,$v=null){
		if (is_array($option)){
			$this->options = array_merge($this->options,$option);
		}else{
			//curl_setopt($this->ch,$option,$v);
			$this->options[$option] = $v;	
		}
		
		return $this;
	}
	
	public function getInfo($opt=null){
		if (is_null($opt)){
			return curl_getinfo($this->ch);
		}
		return curl_getinfo($this->ch,$opt);
	}
	
	public function setProxy($ip){
		return $this->setOption(CURLOPT_HTTPPROXYTUNNEL,true)
					->setOption(CURLOPT_PROXY,$ip)
					->setOption(CURLOPT_HTTPHEADER, array("Pragma: "));
	}
	
	public function __destruct(){
		curl_close($this->ch);
		$this->options = null;
		$this->response = null;
		$this->body = null;
		$this->header = null;
		$this->cookie = null;
	}
}