<?php
/**
 * @author moonzhang (zyfunny@gmail.com)
 * @version 1.0.0.1 2008-09-04 alpha
 */
/**
 * ��������,����ģʽʵ��
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
    * ���ص�ǰ�����û�������
    * @return   void
    */
    public function getName($key='currentUser'){
    	return $this->auth->getName($key);
    }

    /**
    * ��鵱ǰ������û��Ƿ��ǺϷ��û�
    * @return   void
    */
    public function isAuthorized($key='username'){
    	return $this->auth->isAuthorized($key);
    }

    /**
    * �����û���֤,��֤�ɹ�����true�����򷵻�false
    * @param    string $pUserName
    * @param    string $pPassword
    * @return   boolean
    */
    public function login($pUserName, $pPassword, $pBackUrl='/'){
    	return $this->auth->login($pUserName, $pPassword, $pBackUrl);
    }

    /**
    * ��¼�˳�
    * @return   boolean
    */
    public function logout($pBackUrl=null){
    	return $this->auth->logout($pBackUrl);
    }
    
    public function __get($member){
		return $this->mObject->$member;
	}
}