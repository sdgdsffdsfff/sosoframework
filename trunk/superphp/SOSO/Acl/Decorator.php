<?php
/**
 * @author moonzhang (zyfunny@gmail.com)
 * @version 1.0.0.1 2010-05-04 alpha
 */
class SOSO_Acl_Decorator extends SOSO_Proxy implements SOSO_Acl_Interface {
	
	/**
	 * Enter description here...
	 *
	 * @var SOSO_Acl_Interface
	 */
	protected $acl;
	
	public function __construct(SOSO_Acl_Interface $auth,$pUsername=''){
		$this->acl = $auth;	
		parent::__construct($auth,$pUsername);
	}
	
	/**
	 * �����û����õ�Ȩ�����
	 *
	 * @param string $pUsername
	 * @throws ErrorException
	 */
	function setUser($pUsername){
		return $this->acl->setUser($pUsername);
	}
	
	/**
	 * ���ָ���û������н�ɫ
	 *
	 * @param int $pUserID
	 */
	function getRoles($pUserID){
		return $this->acl->getRoles($pUserID);
	}
	
	function getParentRoles($pRoleList){
		return $this->acl->getParentRoles($pRoleList);
	}
	
	/**
	 * ��������б������Ȩ��
	 *
	 * @param mixed $pRoleIDList
	 */
	function getRoleRights($pRoleIDList){
		return $this->acl->getRoleRights($pRoleIDList);
	}
	
	/**
	 * ��õ�ǰ�û�������Ȩ��
	 *
	 */
	function getRights(){
		return $this->acl->getRights();
	}
	
	function getImplyingRights(){
		return $this->acl->getImplyingRights();
	}
	
	function getImpliedRights(){
		return $this->acl->getImpliedRights();
	}
	
	/**
	 * ����û��ǹ���Ա������为�������ģ��
	 *
	 * @param int $pUserID
	 */
	function getDutiedModules($pUserID){
		return $this->acl->getDutiedModules($pUserID);
	}
	/**
	 * Enter description here...
	 *
	 * @return Acl_Concrete
	 */
	public function getAcl(){
		return $this->acl;
	}
	
	 public function __get($member){
		return $this->acl->$member;
	}
}