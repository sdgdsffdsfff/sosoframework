<?php
/**
 * @author moonzhang (zyfunny@gmail.com)
 * 
 * XML/(x)html 节点选择类
 *
 * @todo 
 * 	1.(搁置）深度封装，使其可以以类似jQuery的形式，进行级联操作
 *  (See Updates 1)2.伪类支持 ,所有选择器、属性、过滤器、伪类可以以任意顺序组合，支持形如 div.foo:nth-child(odd)[@foo=bar].bar:first 这样的选择器 。。。。。。。真变态！！
 *  3.
 * Updates:
 *  1.完成todo:2 伪类支持 ,所有选择器、属性、过滤器、伪类可以以任意顺序组合，支持形如 div.foo:nth-child(odd)[@foo=bar].bar:first 这样的选择器
 * 目前支持的选择器：
 * 	1.元素选择符
 *  	* 			任意节点
 * 	    X 			节点名为X的节点
 *     X Y  		X的所有节点名为Y的子孙节点
 *    X > Y 或 X/Y 	X的一级子节点，节点名为Y
 *    X + Y 		与X紧挨着的所有Y节点
 * 	  X ~ Y			与X紧挨着的所有Y节点
 *  2.属性选择符
	使用符号@, 例如div[@foo='bar']
    E[foo] 有属性"foo"
    E[foo=bar]		有属性"foo" 且等于 "bar"
    E[foo^=bar]		有属性"foo" 且以"bar"开头
    E[foo$=bar]		有属性"foo" 且以"bar"结尾
    E[foo*=bar]		有属性"foo" 且含有"bar"作为一部分
    E[foo%=n]		有属性"foo" 且被n取模值为０的节点
    E[foo!=bar]	 	有属性"foo" 且不等于"bar"
 * 
 */
class SOSO_Base_Util_XMLQuery {
	public $document;
	private $options = array ();
	public $first;
	public $last;
	public $nextSibling;
	public $matches = array();
	
	public static $cache = array();
	public $simpleCache = array();
	public $valueCache = array();
//	public $nonSpace = '/\S/';
	public $trimRe = '/^\s+|\s+$/';
	public $tplRe = "/\{(\d+)\}/U";
	//public $modeRe = "/^(\s?[\/>+~]\s?|\s|$)/";
	public $modeRe = "/^(\s?[\/>+~]\s?|\s|$)/";
	public $tagTokenRe = "/^(#)?([\w-\*\x80-\xff]+)/";
	private $mFunctionStatck = array();
	private $mCompiled = false;
	private static $pseudo;
	
	protected static $matchers = array(
			array(
                're'=>'/^\.([\x80-\xff\w-]+)/',
                'select'=>array('byClassName','null,{1}')
            ),array(
				're'=>'/^\:([\x80-\xff\w-]+)(?:\(((?:[^\s>\/]*|.*?))\))?/',
                'select'=>array('byPseudo','{1}, {2}')
            ),array(
                're'=>'/^(?:([\[\{])(?:@)?([\x80-\xff\w-]+)\s?(?:(=|.=)\s?[\'"]?(.*?)["\']?)?[\]\}])/',
                'select'=>array('byAttribute','{2}, {4}, {3}, {1}')
            ), array(
                're'=>'/^#([\x80-\xff\w-]+)/',
                'select'=>array('byId','null, {1}')
            ),array(
                're'=>'/^@([\x80-\xff\w-]+)/',
                'select'=>array('attrValue','{1}')
            )
        );
    protected static $operators = array(
            "="  => 'return $a == $v;',
            "!=" => 'return $a != $v;',
            "^=" => 'return $a && substr($a,0,strlen($v)) == $v;',
            "$=" => 'return $a && substr($a,strlen($a)-strlen($v)) == $v;',
            "*=" => 'return ($a && false !== strpos($v,$a));',
            "%=" => 'return ($a % $v) == 0;',
            "|=" => 'return $a && ($a == $v || substr($a,0,strlen($v)+1) == $v."-");',
            "~=" => 'return $a && strpos((" ".$a." "),(" "+$v+" ")) !== false;'
        );
        
	private function __construct($document = null, $string = null, $options = array()) {
		$string = trim ( $string );
		$this->options = $options;
		if (empty($document)) {
			$this->document = new DOMDocument();
			$this->matches = array();
		} elseif (is_object($document)) {
			if ($document instanceof self) {
				$this->matches = $document->get();
				if (! empty ( $this->matches )){
					$this->document = $this->matches [0]->ownerDocument;
				}
			} elseif ($document instanceof DOMDocument) {
				$this->document = $document;
				$this->matches = array ($document->documentElement );
			} elseif ($document instanceof DOMNode) {
				$this->document = $document->ownerDocument;
				$this->matches = array ($document );
			} elseif ($document instanceof SimpleXMLElement) {
				$import = dom_import_simplexml ( $document );
				$this->document = $import->ownerDocument;
				$this->matches = array ($import );
			} else {
				throw new Exception ( 'Unsupported class type: ' . get_class ( $document ) );
			}
		}
	
		if (!is_null($string) && strlen($string)){
			$this->select($string,$this);
		}
	}

	
	/**
	 * singleton
	 * 
	 */
	public function compile($path,/*$root=null,*/$type=null){
		$type = is_null($type) ? 'select' : $type;
		//$n = $root = is_null($root) ? $this->document : $root;
		$fn = array();
		$q = $path;
		$mode = null;
		$lq = null;
		$tk = self::$matchers;
		$tklen = count($tk);
		$mm = null;
		$lmode = '';
		if(preg_match_all($this->modeRe,$q,$lmode)){
			$q = str_replace($lmode[1][0],'',$q);
		}
		//去掉前导符号 "/"
		$path = preg_replace("#^\s*/+#",'',$path);
		while($q && $lq!=$q){
			$lq = $q;
			$tm = array();
			$bool = preg_match_all($this->tagTokenRe,$q,$tm);
			if ($type == 'select') {
				if ($bool) {
					if ($tm[1][0] == '#') {
						//$fn[] = array(array($this,'quickId'),array(&$n,&$mode,&$root,$tm[2][0]));
						$fn[] = array(array($this,'quickId'),array(&$mode,$this->document,$tm[2][0]));
					}else{
						//$fn[] = array(array($this,'getNodes'),array(&$n,&$mode,$tm[2][0]));
						$fn[] = array(array($this,'getNodes'),array(&$mode,$tm[2][0]));
					}
					//cmt - $q = str_replace($tm[0],'',$q);
					$q = substr($q,strlen($tm[0][0]));
				}else if (!in_array(substr($q,0,1),array(':','@'))){
					$fn[] = array(array($this,'getNodes'),array(&$mode,'*'));
				}
			}else{
				if ($bool) {
					if ($tm[1][0] == '#') {
						//$fn[] = array(array($this,'byId'),array(&$n,null,$tm[2][0]));
						$fn[] = array(array($this,'byId'),array(null,$tm[2][0]));
					}else{
						$fn[] = array(array($this,'byTag'),array($tm[2][0]));
					}
					//$q = str_replace($tm[0],'',$q);
					foreach ($tm[0] as $tag){
						//cmt - $q = preg_replace("#^$tag#",'',$q);
						$q = substr($q,strlen($tag));
					}
				}
			}
			while(!preg_match_all($this->modeRe,$q,$mm)){
				$matched = false;
				for($j=0;$j<$tklen;$j++){
					$t = $tk[$j];
					if (preg_match_all($t['re'],$q,$m)) {
						if(preg_match_all($this->tplRe,$t['select'][1],$m2)){
							for($index=0,$len=count($m2[1]);$index<$len;$index++){
								$key = $m2[1][$index];
								$t['select'][1] = str_replace($m2[0][$index],$m[$key][0],$t['select'][1]);
							}
							
							//$fn[] = array(array($this,$t['select'][0]),array_merge(array(&$n), explode(',',$t['select'][1])));
							$fn[] = array(array($this,$t['select'][0]),explode(',',$t['select'][1]));
							//cmt - $q = str_replace($m[0][0],'',$q);
							$q = substr($q,strlen($m[0][0]));
							$matched = true;
							break;
						}
					}
				}
				if (!$matched) {
					throw new Exception('Error parsing selector, parsing failed at "' . $q . '"');
				}
			}
			if ($mm[1][0]) {
				//$fn[] = '$mode="'.trim($mm[1][0]).'";';
				$mode = trim($mm[1][0]);
				//cmt - $q = str_replace($mm[1][0],'',$q);
				$q = substr($q,strlen($mm[1][0]));
			}
		}
		$this->mCompiled = true;
		$this->mFunctionStatck = $fn;
		return $this;
//		foreach ($fn as $function){
//			$n = call_user_func_array($function[0],$function[1]);
//		}
//		//print_r($fn);
//		return $n;
	}
	/**
	 * 解决xml文档有命名空间问题
	 */
	public function fixSelector($selector){
		$ns = $this->document->documentElement->getAttribute('xmlns');
		if ($ns){
			$xpath = new DOMXPath($this->document);
			$ns_name = 'ns-moon';
			$xpath->registerNamespace($ns_name,$ns);
			$selector = preg_replace("#(/?)([^/]+)#","\\1$ns_name:\\2",$selector);
		}
		return $selector;
	}
	/**
	 * 选择一组节点
	 *
	 * @param String $selector
	 * @param Domdocument|DomNode|DomElement $root
	 * @return array
	 */
	public static function select($selector,$root=null){
		$ids = array();
		if (is_null($root)) {
			return null;
		}
		$paths = explode(',',$selector);
		$results = array();
		$obj = new self($root);
		
		foreach ($paths as $k=>$v){
			$p = trim($v);
			$query = $obj->compile($p,'select');
			//$result = $obj->compile($p,$root);
			foreach ($query->mFunctionStatck as $item){
				$root = call_user_func_array($item[0],array_merge(array($root),$item[1]));
				//print_r($root);
			}
			
			if ($root && $root != $obj->document) {
				if (!is_array($root)){
					$root = array($root);
				}
				$results = array_merge($results,$root);
			}
		}
		return $results;
	}
	
	public static function selectValue($path,$root,$defaultValue=''){
		$path = trim($path);
		$q = new self($root);
		$q->compile($path,'select');
		//print_r($q->mFunctionStatck);
		foreach ($q->mFunctionStatck as $item){
			$root = call_user_func_array($item[0],array_merge(array($root),$item[1]));
		}
		$root = (is_array($root) && isset($root[0])) ? $root[0] : $root;
		$v = null;
		if($root && isset($root->firstChild)){
			$v = $root->firstChild->nodeValue;
			if(isset($root->childNodes) && $root->childNodes->length > 1)
				$v = $root->textContent;
		}
		//$v = ($root && isset($root->firstChild) ? $root->firstChild->nodeValue : null);
		return ((is_null($v)|| $v==='') ? $defaultValue : $v);
	}
	
	public function setMatches($res){
		$this->last = $this->matches;
		$this->matches = $res;
	}
	
	public function concat($a,$b=null){
		if (is_array($b)) {
			return array_merge($a,$b);
		}
		
        for($i = 0, $l = count($b); $i < $l; $i++){
            $a[] = $b[$i];
        }
        return $a;
	}
		
	public function selectNode($path,$root){
		$result = self::select($path,$root);
		return count($result) ? $result[0] : null;
	}
	
	public function size() {
		return count($this->matches);
	}
	
	public function get($index = null) {
		return isset($this->matches[$index]) ? $this->matches[$index] : null;
	}
	
	public function parent() {
		//return 
	}
	
	public function val($val=null){
		if (!is_null($val)){
			foreach ($this->matches as $m){
				//$m->attr()
			}
		}
		return empty($this->matches) ? null : $this->matches[0]->nodeValue;
	}
	
	public function next() {
	}
	
	public function first() {
		
	}
	
	public function byId($cs, $attr, $id){

        if(isset($cs->tagName) || $cs == $this->document){
            $cs = array($cs);
        }
        if(!$id){
            return $cs;
        }
        $r = array(); $ri = -1;
        for($i = 0,$ci,$len=count($cs); ($i<$len && $ci = $cs[$i]); $i++){
            if($ci && trim($ci->getAttribute('id')) == trim($id)){
                $r[++$ri] = $ci;
                return $r;
            }
        }
        return $r;
    }
	
	private function byPseudo($cs,$name,$value){
		//singleton
		$pseudo = Pseudos::instance();
		return $pseudo->$name($cs,$value);
	}
	/**
	 * 通过属性获得节点
	 */
	private function byAttribute($cs, $attr, $value, $op, $custom){
        $r = array(); $ri = -1; $st = $custom=="{";
        $op = trim($op);$value = trim($value);
	    $f = isset(self::$operators[$op]) ? create_function('$a,$v',self::$operators[$op]) : null;
        for($i = 0, $ci; isset($cs[$i]) && $ci = $cs[$i]; $i++){
            $a = $ci->getAttribute($attr);
            
            if(($f && $f($a, $value)) || (!$f && $a)){
                $r[++$ri] =$ci;
            }
        }
        return $r;
    }
    public function byClassName($c, $a, $v){
        if(!$v){
            return $c;
        }
        $r = array();
        $ri = -1;

        for($i = 0, $ci; isset($c[$i]) && $ci = $c[$i]; $i++){
            if(false !== strpos(' '.$ci->getAttribute('class').' ',$v)){
                $r[++$ri] = $ci;
            }
        }
        return $r;
    }
    /**
     * 获得节点属性值
     *
     */
    public function attrValue($n, $attr){
        if(!isset($n->tagName) && $n && is_array($n)){
            $n = $n[0];
        }
        if(!$n){
            return null;
        }
        $ret = $n->getAttribute($attr);
        if (is_array($ret) && isset($n[$attr])) {
        	$ret = $n[$attr];
        }
        $stdClass = new stdClass();
        $stdClass->firstChild = new stdClass();
        $stdClass->firstChild->nodeValue = $ret;
        return $stdClass;
    }
    
	public function getNodes($ns,$mode=null,$tagName='*'){
		$result = array();
		$ri = -1;
		$cs = null;
		if (!$ns) {
			return $result;
		}
		if (!is_array($ns)) {
			$ns = array($ns);	
		}
		if (!$mode) {
			for($i=0,$ni;(isset($ns[$i])&&$ni=$ns[$i]);$i++){
				$cs = $ni->getElementsByTagName($tagName);
				for($j=0,$len=$cs->length;$j<$len;$j++){
					$result[++$ri] = $cs->item($j);
				}
			}
		}else if ($mode == '/' || $mode == '>'){
			$utag = strtoupper($tagName);
			for($i=0,$ni,$cn;(isset($ns[$i])&&$ni=$ns[$i]);$i++){
				$cn = $ni->childNodes;
				for($j=0,$cj;$cj=$cn->item($j);$j++){
					if (strtoupper($cj->nodeName) == $utag || $cj->nodeName == $tagName || $tagName == '*') {
						$result[++$ri] = $cj;
					}
				}
			}
		}else if ($mode == '+'){
			$utag = strtoupper($tagName);
			for($i = 0, $n; $n = $ns[$i]; $i++){
                while(($n = $n->nextSibling) && $n->nodeType != 1);
                if($n && ($n->nodeName == $utag || $n->nodeName == $tagName || $tagName == '*')){
                    $result[++$ri] = $n;
                }
            }
        }else if($mode == "~"){
            for($i = 0, $n; $n = $ns[$i]; $i++){
                while(($n = $n->nextSibling) && ($n->nodeType != 1 || ($tagName == '*' || strtoupper($n->tagName)!=$tagName)));
                if($n){
                    $result[++$ri] = $n;
                }
            }
        }
		return $result;
	}
	
	/**
	 * 实现伪类（Pseudo Classes）
	 * 
	 */
	public function __get($name) {
		
	}
	
	public function children($selector = null) {
	
	}
}

class Pseudos {
	public static $instance;
	private $nthRe = '/(\d*)n\+?(\d*)/';
	private $nthRe2= '/\D/';
	private $batch = 12321;
	private function __construct(){}
	private function __clone(){}
	public static function instance(){
		if (is_null(self::$instance)){
			self::$instance = new self();
		}
		return self::$instance;
	}
	private function _FirstChild($c){
		 $r = array();
		 $ri = -1;
		 $n=null;
         for($i=0,$ci;(isset($c[$i]) && $ci = $n = $c[$i]); $i++){
         	while(($n = $n->previousSibling) && $n->nodeType != 1);
				if(!$n){
					$r[++$ri] = $ci;
			}
		}
		return $r;
	}
	
	private function _LastChild($c){
		$r = array();
		$ri = -1;
		$n=null;
		for($i = 0, $ci; isset($c[$i]) && $ci = $n = $c[$i]; $i++){
			while(($n = $n->nextSibling) && $n->nodeType != 1);
			if(!$n){
				$r[++$ri] = $ci;
			}
		}
		return $r;		
	}
	
	
	
	private function _Parent($c){
		if ($c){
			if (is_array($c)) {
				$c = $c[0];
			}
			while(($c=$c->parentNode) && $c->nodeType != 1){}
		}
		return array($c); 
	}
	private function _NthChild($c,$a){
		$r = array();
		$a = trim($a);
		$ri = -1;
		$type = array('odd'=>'2n+1','even'=>'2n');
		if (array_key_exists($a,$type)){
			$a = $type[$a];
		}elseif (!preg_match($this->nthRe2,$a)){
			$a = 'n+'.$a;
		}
		preg_match($this->nthRe,$a,$m);
		$f = (isset($m[1])&&$m[1] ? intval($m[1]) : 1) - 0;
		$l = intval($m[2]) - 0;
		for($i = 0,$n; isset($c[$i]) && $n = $c[$i]; $i++){
			$pn = $n->parentNode;
			if ($this->batch != $pn->getAttribute('_batch')){
				$j = 0;
	            for($cn=$pn->firstChild; $cn; $cn = $cn->nextSibling){
					if($cn->nodeType == 1){
						$cn->setAttribute('nodeIndex',++$j);
					}
				}
				$pn->setAttribute('_batch',$this->batch);
			}
				
			if ($f == 1) {	
				if ($l == 0 || $n->getAttribute('nodeIndex') == $l){
					$r[++$ri] = $n;
				}
			} else if ($f == 0 || ($n->getAttribute('nodeIndex') + $l) % $f == 0){
				$r[++$ri] = $n;
			}
		}

		return $r;
	}
	
	private function _OnlyChild($c){
		$r = array();
		$ri = -1;
		for($i=0,$ci;isset($c[$i]) && $ci=$c[$i]; $i++){
			if (!$this->prev($ci) && !$this->next($ci)){
				$r[++$ri] = $ci;
			}
		}
		return $r;
	}
	private function _NodeValue($c,$v){
		$r = array();
		$ri = -1;
		if (is_array($c)){
			$c = $c[0];
		}
		return $c;//isset($c->nodeValue) ? $c->nodeValue 
		if (isset($ci->nodeValue) || (isset($ci->firstChild) && $ci->firstChild->nodeValue))
		for($i=0,$i; isset($c[$i]) && $ci=$c[$i]; $i++){
			if ($ci->firstChild && $ci->firstChild->nodeValue == $v){
				$r[++$ri] = $ci;
			}
		}
		return $r;
	}
	private function _NodeValue2($c,$v){
		$r = array();
		$ri = -1;
		if (!is_array($c)){
			$c = array($c);
		}
		for($i=0,$i; isset($c[$i]) && $ci=$c[$i]; $i++){
			if ($ci->firstChild && $ci->firstChild->nodeValue == $v){
				$r[++$ri] = $ci;
			}
		}
		return $r;
	}
	
	private function _Odd($c){
		return $this->_NthChild($c,'odd');
	}
	
	private function _Even($c){
		return $this->_NthChild($c,'even');
	}
	
	private function _Nth($c,$a){
		return isset($c[$a-1]) ? $c[$a-1] : array(); 
	}
	
	private function _First($c){
		return isset($c[0]) ? $c[0] : array();
	}
	
	private function _Last($c){
		$b = $c;
		return $b ? array_pop($b) : array();
	}
	
	// to be implemented
	private function _Prev($c,$ss){
		if(!is_array($c) && !$c instanceof DOMNodeList ) {
			$c = array($c);	
		}
		$r = array();
		$ri = -1;
        for($i = 0, $ci; $ci = is_array($c) ? isset($c[$i]) ? $c[$i] : null : $c->item($i); $i++){
        	$n = $this->__prev($ci);
            if($n /*&& Base_Util_XMLQuery::is($n, $ss)*/){
				//$r[++$ri] = $ci;
				$r[++$ri] = $n;
			}
		}
        return $r;
	}
	//to be implemented
		public function _Next($c,$ss){
//		$n = is_array($c) ? $c[0] : $c;
//		while(($n=$n->nextSibling) && $n->nodeType!=1);
//		return $n;
		if(!is_array($c) && !$c instanceof DOMNodeList ) {
			$c = array($c);	
		}
		$r = array();
		$ri = -1;
        for($i = 0, $ci; $ci = is_array($c) ? isset($c[$i]) ? $c[$i] : null : $c->item($i); $i++){
        	$n = $this->__next($ci);
            if($n /*&& Base_Util_XMLQuery::is($n, $ss)*/){
				//$r[++$ri] = $ci;
				$r[++$ri] = $n;
			}
		}
        return $r;
	}
	
	public function _Text($c){
		if ($c && is_array($c)){
			$c = $c[0];
		}
		if ($c  instanceof DOMElement) {
			return (object)array('firstChild'=>(object)array('nodeValue'=>$c->nodeValue));
		}
	}
	
	protected function __prev($n){
		while(($n=$n->previousSibling) && $n->nodeType!=1);
		return $n;
	}
	
	private function __next($n){
		while(($n=$n->nextSibling) && $n->nodeType!=1);
		return $n;
	}
	protected function children($d){
		$n = $d->firstChild;
		$ni = -1;
		while($n){
			$nx = $n->nextSibling;
			if ($n->nodeType == 3 && !preg_match('/\S+/',$n->nodeValue)){
				$d->removeChild($n);
			}else{
				$n->setAttribute('nodeIndex',++$ni);
			}
			$n = $nx;
		}
		return $this; 
	}
	/**
	 * innerHTML method
	 *
	 * @param DOMHTMLNode $d
	 */
	protected function _html($d){
		$dom = new DOMDocument('1.0','utf-8');
		$node = $dom->importNode($d,true);
		$dom->appendChild($node);
		$tHTML = $dom->saveHTML();
		$tHTML = html_entity_decode($tHTML,ENT_QUOTES,"UTF-8");
		return (object)array('firstChild'=>(object)array('nodeValue'=>$tHTML));
	}
	
	public function __call($m,$a){
		$ms = explode('-',$m);
		$m2 = '_'.join(array_map('ucfirst',$ms),'');

		if (method_exists($this,$m2)) {
			return call_user_func_array(array($this,$m2),$a);
		}
		return null;
	}
}
?>