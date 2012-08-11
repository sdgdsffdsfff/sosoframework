<?php
/**
 * @author beatashao
 * @version 0.0.0.1 2009-06-07
 * 
 * json对象 节点选择类
 *  follow the methods of DomQuery :)
 *
 * @todo 
 * 	1.
 * 
 * 目前支持的选择器：
 * 	选择节点时需要指明节点的选择路径
 *  	[] 每级路径用[]分隔
 * 	    当某一级路径是数字索引时,用[NUMERIC]表示
 *      否则，这级路径一定是对象的key值，用[$key]表示
 * 
 */
class SOSO_Base_Util_JsonQuery {
	const NUMERIC ='NUMERIC';

	/**
	 * 选择一组节点
	 *
	 * @param String $selector
	 * @param stdClass|Array $root
	 * @return array
	 */
	//[NUMERIC][father1,father2][NUMERIC][item]
	public static function select($selector,$root=null){
		if(preg_match_all('#(\[(.*)\])+#Usi',$selector,$m)){
			$search_paths=$m[2];//匹配查找路径
			$node_arr=array($root);
			//按照spath定位到所有的子节点
			foreach($search_paths as $spath)
			{
				$node_arr=self::expand($node_arr,$spath);		
			}		
		}
		
		return $node_arr;
	}
	/**
	 * 以node_arr为根节点集合，按照spath找到找到它们对应的子节点，返回子节点集合
	 *
	 * @param array $node_arr 父节点集合
	 * @param string（NUMERIC|KEY） $spath
	 * @return array 子节点集合
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
	 * 取得节点的值
	 *
	 * @param string $path '[]'分隔的节点查找路径 [$key1][$index_num][$key2]
	 * @param stdClass|array $root 根节点
	 * @param unknown_type $defaultValue 默认值
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
		return ((is_null($v)|| $v==='') ? $defaultValue : $v);//如果没有找到值，用默认值($defaultValue)代替
	}
}
?>