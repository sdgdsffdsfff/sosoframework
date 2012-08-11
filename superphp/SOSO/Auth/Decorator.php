<?php
/**
 * @author moonzhang (zyfunny@gmail.com)
 * @version 1.0.0.1 2008-09-04 alpha
 */
/**
 * 修饰器类,修饰模式实现
 */
class SOSO_Auth_Decorator extends SOSO_Proxy implements SOSO_Auth_Interface {
	
	private $auth;
	
	public function __construct(SOSO_Auth_Interface $auth){
		$this->mObject = $this->auth = $auth;
	}
	
	public function getIdentify($key='currentUser'){
		return $this->auth->getIdentify($key);
	}

    /**
    * 返回当前请求用户的名称
    * @return   void
    */
    public function getName($key='currentUser'){
    	return $this->auth->getName($key);
    }

    /**
    * 检查当前请求的用户是否是合法用户
    * @return   void
    */
    public function isAuthorized($key='username'){
    	return $this->auth->isAuthorized($key);
    }

    /**
    * 进行用户认证,认证成功返回true，否则返回false
    * @param    string $pUserName
    * @param    string $pPassword
    * @return   boolean
    */
    public function login($pUserName, $pPassword, $pBackUrl='/'){
    	return $this->auth->login($pUserName, $pPassword, $pBackUrl);
    }

    /**
    * 登录退出
    * @return   boolean
    */
    public function logout($pBackUrl=null){
    	return $this->auth->logout($pBackUrl);
    }
    
    public function __get($member){
		return $this->mObject->$member;
	}
}