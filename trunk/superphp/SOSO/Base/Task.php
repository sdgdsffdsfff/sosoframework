<?php
/**
 * @author moonzhang
 * @version 1.0
 * @created 28-三月-2009 11:11:12
 */
class SOSO_Base_Task {
	/**
	 * 用于请求文件
	 *
	 * @var SOSO_Base_Data_Connection
	 */
	private $mConnection;
	
	private $mStore;
	/**
	 * 用于正则匹配
	 *
	 * @var SOSO_Base_Util_Regexp
	 */
	private $mRegexp;
	private $mUrl;
	private $mMethod = 'get';
	protected $mOptions = array ();
	protected $mPattern;
	protected $mEncoding = 'gbk';
	protected $mSubpatterns = array ();
	private $debug = false;
	private $mCache = array();
	private $mMergable = true;
	
	public function __construct($url = '', $options = array()) {
		$this->mConnection = new SOSO_Base_Data_Connection ( $options );
		$this->mRegexp = new SOSO_Base_Util_Regexp ( $this->mPattern );
		if (strlen ( $url )) {
			$this->setURL ( $url, $options );
		}
		if (strlen ( $this->mPattern )) {
			$this->setPattern ( $this->mPattern );
		}
		if (! empty ( $this->mSubpatterns )) {
			$this->setSubPattern ( $this->mSubpatterns );
		}
	}
	
	public function useProxyRelay($options=array()){
		$this->mConnection->useProxyRelay($options);
		return $this;	
	}
	
	public function getContent() {
		if (isset($this->mCache[$this->mUrl])){
			return $this->mCache[$this->mUrl];
		}
		if ($this->debug) {
			$tCache = SOSO_Cache::factory ( 'file', array ("cache_dir" => "temp_files", "auto_hash" => 3 ) );
			$tCache->getKey ( $this->mUrl );
			$tContent = $tCache->read ( $tCache->getKey() );
			if (! $tContent) {
				$tContent = $this->mConnection->request( $this->mUrl );
				$tCharset = strtolower ( mb_detect_encoding ( $tContent, array ('utf-8', 'gbk', 'gb2312' ) ) );
				if ($tCharset == 'utf-8') {
					$tContent = mb_convert_encoding ( $tContent, $this->mEncoding, $tCharset );
				}
				$tCache->write ( $tCache->getKey(), $tContent );
			}
			$this->mCache = array();
			return $this->mCache[$this->mUrl] = $tContent;
		} else {
			$tContent = $this->mConnection->request ( $this->mUrl );
			$tCharset = strtolower ( mb_detect_encoding ( $tContent, array ('utf-8', 'gbk', 'gb2312' ) ) );
			if ($tCharset == 'utf-8' && $this->mEncoding != 'utf-8') {
				$tContent = mb_convert_encoding ( $tContent, $this->mEncoding, $tCharset );
			}
			$this->mCache = array();
			return $this->mCache[$this->mUrl] = $tContent;
		}
	}
	
	/**
	 * 设置请求地址及请求方式，
	 * 		options： method => get | post
	 *				  args   => 如methos为post时的参数
	 *
	 * @param unknown_type $url
	 * @param unknown_type $options
	 */
	public function setURL($url, $options = array()) {
		$this->mUrl = $url;
		$this->mConnection->setOptions ( $options );
	}
	
	public function setMerge($flag=true){
		$this->mMergable = $flag;
		return $this;
	}
	
	public function getMerge(){
		return $this->mMergable;
	}
	
	public function setPattern($pattern = '') {
		$this->mPattern = $pattern;
		$this->mRegexp->setPattern ( $pattern );
	}
	
	public function setSubPattern($patterns = array()) {
		$this->mSubpatterns = $patterns;
		$this->mRegexp->subPattern ( $patterns );
	}
	
	/**
	 * 回调函数
	 *
	 * @param {Function} $fn
	 * @param {Object} $scope
	 * @param {Mixed} $appendArg  回调函数的附加参数（Nbility Functional improvement)
	 * @return {Object} Instance of SOSO_Base_Task
	 */
	public function setFilter($fn=null, $scope=null,$appendArg=array()) {
		$this->mRegexp->registeFilter ( $fn, $scope ,$appendArg);
		return $this;
	}
	
	public function setEncoding($encoding = 'gbk') {
		$this->mEncoding = $encoding;
	}
	
	public function setCallback($fn, $scope) {
		return $this->setFilter ( $fn, $scope );
	}
	
	public function setDebug($flag = true) {
		$this->debug = $flag;
	}
	
	public static function build($url, $pattern) {
		$obj = new SOSO_Base_Task($url);
		$obj->setPattern($pattern);
		return $obj;
	}
	
	public function getURL() {
		return $this->mUrl;
	}
	
	public function getRegExp() {
		return $this->mRegexp;
	}
	
	public function getConnector() {
		return $this->mConnection;
	}
	
	public function execute($merge=null) {
		if (is_null($merge)){
			$merge = $this->mMergable;
		}else{
			$this->setMerge($merge);
		}
		if (! strlen ( $this->mPattern )) {
			throw new RuntimeException ( "pattern is not specified!", 1111 );
		}
		if (! strlen ( $this->mUrl )) {
			throw new RuntimeException ( "URL is not specified!", 2222 );
		}
	
		return $this->mRegexp->match ( $this->getContent(),$merge );

	}

}
?>
