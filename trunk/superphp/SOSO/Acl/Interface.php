<?php
interface SOSO_Acl_Interface {
	/**
	 * �����û����õ�Ȩ�����
	 *
	 * @param string $pUsername
	 * @throws ErrorException
	 */
	function setUser($pUsername);
	
	/**
	 * ���ָ���û���������
	 *
	 * @param int $pUserID
	 */
	function getRoles($pUserID);
	
	function getParentRoles($pRoleList);
	
	/**
	 * ��������б������Ȩ��
	 *
	 * @param mixed $pRoleIDList
	 */
	function getRoleRights($pRoleIDList);
	
	/**
	 * ��õ�ǰ�û�������Ȩ��
	 *
	 */
	function getRights();
	
	function getImplyingRights();
	
	function getImpliedRights();
	
	/**
	 * ����û��ǹ���Ա������为�������ģ��
	 *
	 * @param int $pUserID
	 */
	function getDutiedModules($pUserID);

}