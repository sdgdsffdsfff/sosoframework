<?php
/**
 * 
 * Enter description here ...
 * @author moonzhang
 *
 */
class SOSO_DistributedFileSystem {
	private $_Root;
	private $_HashMethod = 'uniqid';
	private $_Methods = array('uniqid','md5');
	
	public function __construct($pRootPath=null) {
		$this->_Root = strlen($pRootPath) ? $pRootPath : SOSO_Frameworks_Config::getSystemPath('temp').'/cache/';
		if (!file_exists($this->_Root)) $this->_exec("mkdir -p {$this->_Root}");
	}
	
	public function delete($pFilename) {
		$filename = $this->_Root.$pFilename;
		@unlink($filename);
		return !file_exists($filename);
	}
	
	public function setHashMethod($hash){
		if (is_array($hash) && is_callable($hash) 
		  || function_exists($hash) 
		  || in_array(strtolower($hash), $this->_Methods)){
			$this->_HashMethod = $hash;
			return $this;
		}
		$this->_HashMethod = 'uniqid';
		return $this;
	}
	
	protected function md5($pCheckMd5){
		if (!strlen($pCheckMd5)) return $this->uniqid();
		$path_relatively = substr($pCheckMd5,0,2).'/'.substr($pCheckMd5,2,2).'/';
		
		return array($path_relatively=>$pCheckMd5);
	}
	
	protected function uniqid(){
		$path_relatively = date("/Y/m/d/", time());
		$path_absolute = "{$this->_Root}{$path_relatively}";
		
		do {
			$file = uniqid();
			$file_absolute = "{$path_absolute}{$file}";
		}while (file_exists($file_absolute));
		
		return array($path_relatively=>$file);
	}
	/**
	 * 
	 * Enter description here ...
	 */
	public function freeSpace() {
		return disk_free_space($this->_Root)/1024;
	}
	
	public function binaryUpload($data,$pFile='',$pCheckSize=0,$pCheckMd5='',$decoder='base64_decode'){
		$return = array();
		if (is_string($decoder) && function_exists($decoder)) $data = base64_decode($data);
		elseif(is_array($decoder)){
			foreach ($decoder as $fn){
				$data = $fn($data);
			}
		}
		if (!strlen($pFile)){
			list($path_relatively,$file) = each(call_user_func_array(array($this,$this->_HashMethod), array($pCheckMd5)));
		}else{
			if (false === strpos($pFile,'/')){
				$path_relatively = key(call_user_func_array(array($this,$this->_HashMethod), array($pCheckMd5)));
			}else{
				$path_relatively = dirname($pFile);
			}
			$file = basename($pFile);
		}
		$path_absolute = $this->_Root.$path_relatively;
		$file_absolute = $path_absolute.$file;
		if (!file_exists($path_absolute)) $this->_exec('mkdir -p '.$path_absolute);
		if (!file_exists($file_absolute))
			file_put_contents($file_absolute, $data);
		if (file_exists($file_absolute) && ((0 == $pCheckSize) || (filesize($file_absolute) == $pCheckSize)) && (('' == $pCheckMD5) || (md5_file($file_absolute) == $pCheckMD5))) {
			$return = array();
			$return['size'] = filesize($file_absolute);
			$return['system_path'] = $path_relatively;
			$return['system_file'] = basename($file_absolute);
			return $return;
		}
		@unlink($file_absolute);
		return array();
	}
	
	public function uploadByURL($pUrl, $pFile='',$pCheckSize=0, $pCheckMD5='') {
		//$url_info = parse_url($pUrl);
		//$return = pathinfo($url_info['path']);	
		if (!strlen($pFile)){
			list($path_relatively,$file) = each(call_user_func_array(array($this,$this->_HashMethod), array($pCheckMd5)));
		}else{
			if (false === strpos($pFile,'/')){
				$path_relatively = key(call_user_func_array(array($this,$this->_HashMethod), array($pCheckMd5)));
			}else{
				$path_relatively = dirname($pFile);
			}
			$file = basename($pFile);
		}	
		//list($path_relatively,$file) = each(call_user_func_array(array($this,$this->_HashMethod), array($pCheckMD5)));
		$path_absolute = $this->_Root.$path_relatively;
		$file_absolute = $path_absolute.$file;
		if (!file_exists($path_absolute)) $this->_exec('mkdir -p '.$path_absolute);
		if (!file_exists($file_absolute))
			$this->_exec("wget -q -O '{$file_absolute}' '{$pUrl}'");
		if (file_exists($file_absolute) && ((0 == $pCheckSize) 
		  || (filesize($file_absolute) == $pCheckSize)) 
		  && (('' == $pCheckMD5) 
		  || (md5_file($file_absolute) == $pCheckMD5))) {
			
		  	$return['size'] = filesize($file_absolute);
			$return['system_path'] = $path_relatively;
			$return['system_file'] = basename($file_absolute);
			return $return;
		}
		@unlink($file_absolute);
		return array();
	}
	function startDownload($pRealFile, $pCaption) {
		$pRealFile = $this->_Root.$pRealFile;
		if (!is_file($pRealFile)) {
			header("HTTP/1.0 404 Not Found");
			return;
		}
		header("Pragma: public");
		header("Expires: 0");
		header("Cache-Control: must-revalidate, post-check=0,pre-check=0");
		#header("Cache-Control:");
		#header("Cache-Control: public");
		header("Content-Description: File Transfer");
		header("Content-Type: ".$this->_get_content_type($pCaption));
		$filespaces = str_replace("_", " ", $pCaption);
		header("Content-Disposition: attachment; filename={$filespaces}");
		header("Content-Transfer-Encoding: binary");

		$size=filesize($pRealFile);
		//check if http_range is sent by browser (or download manager)
		if(isset($_SERVER['HTTP_RANGE'])) {
			list($a, $range)=explode("=",$_SERVER['HTTP_RANGE']);
			//if yes, download missing part
			str_replace($range, "-", $range);
			$size2=$size-1;
			header("Content-Range: $range$size2/$size");
			$new_length=$size2-$range;
			header("Content-Length: $new_length");
			//if not, download whole file
		} else {
			$size2=$size-1;
			header("Content-Range: bytes 0-$size2/$size");
			header("Content-Length: ".$size2);
		}
		//open the file
		$fp = @fopen ($pRealFile, "rb");
		//seek to start of missing part
		fseek($fp,$range);
		//start buffered download
		while(!feof($fp)) {
			//reset time limit for big files
			set_time_limit();
			print(fread($fp,1024*8));
			flush();
		}
		fclose($fp);
	}
	private function _exec($pCommand) {
		return `$pCommand`;
	}
	private function _get_content_type($pFilename) {
		$file = new SOSO_Util_File();
		return $file->get_content_type($pFilename);
	}
}