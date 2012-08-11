<?php
/**
 * SOSO Framework
 * @category   SOSO
 * @package    SOSO
 * @copyright  Copyright (c) 2007-2008 Soso.com
 * @author moonzhang
 * @version 1.0
 * @created 15-四月-2008 16:59:22
 * Updates : 2008-09-07 对session database进行了重构；增加了memcache存储的支持 
 */
/**
 * session操作类
 * @author moonzhang
 *
 */
class SOSO_Session{
	const ON = 1;
	const OFF = 0;
	public static $session_status = self::OFF;
	/**
	 * 检查是否启动了session
	 * @var bool
	 */
	public static $mSessionStarted = false;
	
	/**
	 * @var int
	 */
	public static $mRegenerateIdState = 0;
	
	public $mOptions = array();
	/**
	 * @var array
	 */
	public $mDefaultOptions = array(
	        'save_path'                 => null,
	        'name'                      => null, /* 每个应用采用不同的唯一标识 */
	        'save_handler'              => null,
	        'auto_start'                => false, /* intentionally excluded (see manual) */
	        'gc_probability'            => null,
	        'gc_divisor'                => null,
	        'gc_maxlifetime'            => 72000, /** 20小时 */
	        'serialize_handler'         => null,
	        'cookie_lifetime'           => null,
	        'cookie_path'               => null,
	        'cookie_domain'             => null,
	        'cookie_secure'             => null,
	        'use_cookies'               => null,
	        'use_only_cookies'          => 'on',
	        'referer_check'             => null,
	        'entropy_file'              => null,
	        'entropy_length'            => null,
	        'cache_limiter'             => null,
	        'cache_expire'              => null,
	        'use_trans_sid'             => null,
	        'bug_compat_42'             => null,
	        'bug_compat_warn'           => null,
	        'hash_function'             => null,
	        'hash_bits_per_character'   => null
	    );
	    
	/**
	 * 是否写关闭
	 * @var bool
	 */
	public static $mWriteClosed = false;
	/**
	 * Whether or not session id cookie has been deleted
	 * @var bool
	 */
	public static $mSessionCookieDeleted = false;
	/**
	 * 是否通过 session_destroy() 注销session
	 * @var bool
	 */
	public static $mDestroyed = false;
	/**
	 * @var 
	 */
	public static $mSaveHandler = null;
	private static $instance;
	
	private function __construct($options=array()){
		if (!empty($options)) {
			$cookieDefaults = session_get_cookie_params();
			$cfg = array_merge($this->mDefaultOptions,array(
		      'session_name' => session_name(),
		      'session_id'   => null,
		      'auto_start' => false,
		      'session_cookie_lifetime' => $cookieDefaults['lifetime'],
		      'session_cookie_path' => $cookieDefaults['path'],
		      'session_cookie_domain' => $cookieDefaults['domain'],
		      'session_cookie_secure' => $cookieDefaults['secure'],
		      'session_cookie_httponly' => isset($cookieDefaults['httponly']) ? $cookieDefaults['httponly'] : false
		    ),$options);
		    
		    $this->setOptions($cfg);
		    
		    $cfg = array_intersect_key($cfg,$this->mDefaultOptions);
		    $lambda = create_function('$v','return !is_null($v);');
		    if (!empty($cfg) && $cfg=array_filter($cfg,$lambda)) {
		    	foreach ($cfg as $k=>$v){
			    	@ini_set("session.".$k,$v);
			    }	
		    }
			
			$sessionName = $this->mOptions['session_name'];
		    session_name($sessionName);
			$sessionId = $this->mOptions['session_id'];
		    if (strlen($sessionId)) {
		      $this->setId($sessionId);
		    }
		
		    $lifetime = $this->mOptions['session_cookie_lifetime'];
		    $path     = $this->mOptions['session_cookie_path'];
		    $domain   = $this->mOptions['session_cookie_domain'];
		    $secure   = $this->mOptions['session_cookie_secure'];
		    $httpOnly = $this->mOptions['session_cookie_httponly'];
		    session_set_cookie_params($lifetime, $path, $domain, $secure, $httpOnly);	
		}
	}
	
	public static function get_unique_app_name() {
		return 'ISOSO'.substr(strtoupper(md5(SOSO_Frameworks_Config::document_root_path())),0,9);
	}

	public function setOptions($userOptions = array()){
	    return $this->mOptions = array_merge($this->mOptions,$userOptions);
	}

	public static function setSaveHandler($saveHandler){
		session_module_name("user");
		session_set_save_handler(
            array($saveHandler, 'open'),
            array($saveHandler, 'close'),
            array($saveHandler, 'read'),
            array($saveHandler, 'write'),
            array($saveHandler, 'destroy'),
            array($saveHandler, 'gc')
            );
        self::$mSaveHandler = $saveHandler;
	}

	public static function getSaveHandler(){
		return self::$mSaveHandler;
	}

	/**
	 * regenerateId() - Regenerate the session id.  Best practice is to call this
	 * after session is started.  If called prior to session starting, session id will
	 * be regenerated at start time.
	 * @return void
	 */
	public static function regenerateId(){
		if (headers_sent($filename, $linenum)) {
			return false;
		}
		if (self::$mSessionStarted && self::$mRegenerateIdState <= 0) {
            session_regenerate_id(true);
            self::$mRegenerateIdState = 1;
        } else {
        	self::$mRegenerateIdState = -1;
        }
	}

	/**
	 * sessionExists() - whether or not a session exists for the current request
	 * @return bool
	 */
	public static function sessionExists(){
		 if (ini_get('session.use_cookies') == '1' && isset($_COOKIE[session_name()])) {
            return true;
        } elseif (!empty($_REQUEST[session_name()])) {
            return true;
        }

        return false;
	}

	/**
	 * 根据配置项，初始化session
	 * 
	 * @param array $options 
	 *                
	 * @param options
	 * @throws Exception
	 * @return boolean
	 * 
	 * Updates: 2008-09-07: 对代码进行了重构，将获得参数的逻辑放在外面（dispatcher）中，方法只关心参数options
	 */
	public static function start($options=array()){
		if (headers_sent($filename, $linenum)) {
			return false; 	
		}
		if (self::isStarted()) {
			return true;
		}
		self::$instance = new self($options);
		if (isset(self::$instance->mOptions['handler']) && class_exists(self::$instance->mOptions['handler'])) {
			$parents = class_parents(self::$instance->mOptions['handler']);
			if ($parents && in_array('SOSO_Session_Storage',$parents)) {
				try{
					self::setSaveHandler(new self::$instance->mOptions['handler'](self::$instance->mOptions));
				}catch (Exception $e){
					throw $e;
				}
			}
		}
		session_start();
		self::$mSessionStarted = true;
	}

	/**
	 * isStarted() - convenience method to determine if the session is already started.
	 * 
	 * @return bool
	 */
	public static function isStarted(){
		return self::$mSessionStarted;
	}

	public static function isRegenerated(){
		return ( (self::$mRegenerateIdState > 0) ? true : false );
	}

	/**
	 * getId() - get the current session id
	 * @return string
	 */
	public static function getId(){
		return session_id();
	}

	/**
	 * setId() - set an id to a user specified id
	 * @param string $id
	 * @return void
	 * 
	 * @param id
	 */
	public static function setId($id){
		return session_id($id);
	}

	/**
	 * @return void
	 * 
	 * @param readonly
	 */
	public static function writeClose($readonly = true){
		if (self::$mWriteClosed) {
            return;
        }
        if ($readonly) {
            self::$mWriteClosed = false;
        }
        session_write_close();
        self::$mWriteClosed = true;
	}

	/**
	 *
	 * @return void
	 * @param remove_cookie
	 * @param readonly
	 */
	public static function destroy($remove_cookie = true)	{
		 if (self::$mDestroyed) {
            return;
        }

        session_destroy();
        self::$mDestroyed = true;

        if ($remove_cookie) {
            self::expireSessionCookie();
        }
	}

	/**
	 * 发送过期的session id cookie,使客户端删除session cookie
	 * @return void
	 */
	public static function expireSessionCookie(){
		if (self::$_sessionCookieDeleted) {
            return;
        }

        self::$_sessionCookieDeleted = true;

        if (isset($_COOKIE[session_name()])) {
            $cookie_params = session_get_cookie_params();
            setcookie(
                session_name(),
                false,
                315554400, // strtotime('1980-01-01'),
                $cookie_params['path'],
                $cookie_params['domain'],
                $cookie_params['secure'],
                true
                );
        }
	}
}