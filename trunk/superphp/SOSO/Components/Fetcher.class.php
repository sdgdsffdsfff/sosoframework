<?php
class Fetcher
{
	private $ch;
	private $headers = array();
	private $opts = array();

	function __construct()
	{
		// default headers
		$this->headers['Pragma'] = 'no-cache'; 
		$this->headers['Cache-Control'] = 'no-cache'; 
		$this->headers['Accept'] = '*/*'; 
		$this->headers['Accept-Language'] = 'zh-cn'; 
		$this->headers['Connection'] = 'Keep-Alive';
		$this->headers['User-Agent'] = 'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; SV1; MAXTHON 2.0)';
		// default opts
		$this->opts[CURLOPT_HTTP_VERSION] = CURL_HTTP_VERSION_1_1;
		$this->opts[CURLOPT_NOPROGRESS] = 1;
		$this->opts[CURLOPT_FOLLOWLOCATION] = 1;
		$this->opts[CURLOPT_MAXREDIRS] = 15;
		$this->opts[CURLOPT_RETURNTRANSFER] = 1;
		$this->opts[CURLOPT_CONNECTTIMEOUT] = 15;
		$this->opts[CURLOPT_TIMEOUT] = 30;
		$this->opts[CURLOPT_VERBOSE] = 0;
		// init
		$this->ch = curl_init();
	}
	
	function __destruct()
	{
		if($this->ch)
			curl_close($this->ch);
	}
	
	public function setHeader($key, $value)
	{
		$this->headers[$key] = $value;
	}

	public function setOpt($key, $value)
	{
		$this->opts[$key] = $value;
	}
	
	public function get($url)
	{
		$this->opts[CURLOPT_HTTPGET] = 1;
		$this->opts[CURLOPT_POST] = 0;
		return $this->doRequest($url);
	}
	
	public function post($url, $dataAry)
	{
		$postData = '';
		foreach($dataAry as $key => $value){
			if(!empty($postData)) $postData .= "&";
			$postData .= urlencode($key) . "=" . urlencode($value);
		}
		$this->opts[CURLOPT_POST] = 1;
		$this->opts[CURLOPT_HTTPGET] = 0;
		$this->opts[CURLOPT_POSTFIELDS] = $postData;
		return $this->doRequest($url);
	}
	
	public function head($url){
		$this->opts[CURLOPT_CUSTOMREQUEST] = 'HEAD';
		$this->opts[CURLOPT_HEADER] = 1;
		$this->opts[CURLOPT_NOBODY] = true;
		return $this->doRequest($url);
	}
	
	public function getInfo($infoId)
	{
		return curl_getinfo($this->ch, $infoId);
	}
	
	public function getCode()
	{
		return intval($this->getInfo(CURLINFO_HTTP_CODE));
	}
	
	public function getError()
	{
		return curl_error($this->ch);
	}
	
	private function format_url($url)
	{
		$url = preg_replace('/#.*$/', '', $url);
		$url = str_replace('\\', '', $url);
		if(strpos($url, "http://") === false) $url = "http://$url";
		return $url;
	}
	
	private function doRequest($url)
	{
		$url = $this->format_url($url);
		$urls = parse_url($url);
		$this->headers['host'] = $urls['host'];
		$this->opts[CURLOPT_URL] = $url;
		// header
		$headerAry = array();
		foreach($this->headers as $key => $value){
			if(empty($value)) continue;
			$headerAry[] = "$key: $value";
		}
		$this->opts[CURLOPT_HTTPHEADER] = $headerAry;
		// exec
		foreach($this->opts as $optKey => $optValue){
			if(empty($optValue)) continue;
			curl_setopt($this->ch, $optKey, $optValue);
		}
		return curl_exec($this->ch);
	}
}
?>