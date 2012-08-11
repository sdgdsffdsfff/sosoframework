<?php
/**
 * @author beatashao
 * @version 0.0.0.1 2009-06-07
 * 
 * json���� �ڵ�ѡ����
 *  follow the methods of DomQuery :)
 *
 * @todo 
 * 	1.
 * 
 * Ŀǰ֧�ֵ�ѡ������
 * 	ѡ��ڵ�ʱ��Ҫָ���ڵ��ѡ��·��
 *  	[] ÿ��·����[]�ָ�
 * 	    ��ĳһ��·������������ʱ,��[NUMERIC]��ʾ
 *      �����⼶·��һ���Ƕ����keyֵ����[$key]��ʾ
 * 
 */
class SOSO_Base_Util_JsonQuery {
	const NUMERIC ='NUMERIC';

	/**
	 * ѡ��һ��ڵ�
	 *
	 * @param String $selector
	 * @param stdClass|Array $root
	 * @return array
	 */
	//[NUMERIC][father1,father2][NUMERIC][item]
	public static function select($selector,$root=null){
		if(preg_match_all('#(\[(.*)\])+#Usi',$selector,$m)){
			$search_paths=$m[2];//ƥ�����·��
			$node_arr=array($root);
			//����spath��λ�����е��ӽڵ�
			foreach($search_paths as $spath)
			{
				$node_arr=self::expand($node_arr,$spath);		
			}		
		}
		
		return $node_arr;
	}
	/**
	 * ��node_arrΪ���ڵ㼯�ϣ�����spath�ҵ��ҵ����Ƕ�Ӧ���ӽڵ㣬�����ӽڵ㼯��
	 *
	 * @param array $node_arr ���ڵ㼯��
	 * @param string��NUMERIC|KEY�� $spath
	 * @return array �ӽڵ㼯��
	 */
	public static function expand($node_arr,$spath){
		$result=array();
		foreach($node_arr as $node){	
			if($spath=='NUMERIC'){
				if($node instanceof stdClass ){
					array_push($result,$node);
				}
				else{
					if(is_array($node)&&count($node)>0){
						$result=array_merge($result,$node);
					}
				}
			}
			else{
				$keys=explode(",",$spath);
				foreach($keys as $key){
					if(isset($node->$key)){
						$n=$node->$key;
						if($n instanceof stdClass ||is_array($n))
							array_push($result,$n);
					}
				}
			}
		}
		return $result;
	}
	/**
	 * ȡ�ýڵ��ֵ
	 *
	 * @param string $path '[]'�ָ��Ľڵ����·�� [$key1][$index_num][$key2]
	 * @param stdClass|array $root ���ڵ�
	 * @param unknown_type $defaultValue Ĭ��ֵ
	 * @return unknown 
	 */
	public static function selectValue($path,$root,$defaultValue=''){
		$path = trim($path);
		if(preg_match_all('#(\[(.*)\])+#Usi',$path,$m)){
			$path_seg=$m[2];
			foreach ($path_seg as $seg){
			//	if(is_numeric($seg)){
				if(!($root instanceof stdClass)){
					if(isset($root[$seg])){//in case some value does not exist
						$root=$root[$seg];
					}
					else{
						$root=null;
						break;
					}
				}
				else{
					if(isset($root->$seg)){//in case some value does not exist
						$root=$root->$seg;
					}
					else{
						$root=null;
						break;
					}
				}
			}		
		}		
		
		$root = (is_array($root) && isset($root[0])) ? $root[0] : $root;
		$v = ($root && isset($root)) ? $root : null;
		return ((is_null($v)|| $v==='') ? $defaultValue : $v);//���û���ҵ�ֵ����Ĭ��ֵ($defaultValue)����
	}
}
?>