<?php
/**
 * @author moonzhang
 * @version 1.0 2008-05-08
 *
 */
 /**
  * ��¼��֤�ӿ�
  */
interface SOSO_Auth_Interface {

    /**
    * �õ���ǰ�����û���Ψһ��ʶ
    * @return   void
    */
    function getIdentify($key='currentUser');

    /**
    * ���ص�ǰ�����û�������
    * @return   void
    */
    function getName($key='currentUser');

    /**
    * ��鵱ǰ������û��Ƿ��ǺϷ��û�
    * @return   void
    */
    function isAuthorized($key='currentUser');

    /**
    * �����û���֤,��֤�ɹ�����true�����򷵻�false
    * @param    string $pUserName
    * @param    string $pPassword
    * @return   boolean
    */
    function login($pUserName, $pPassword, $pBackUrl='/');

    /**
    * ��¼�˳�
    * @return   boolean
    */
    function logout($pBackUrl=null);
}