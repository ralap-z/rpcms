<?php
// +----------------------------------------------------------------------
// | RPCMS
// +----------------------------------------------------------------------
// | Copyright (c) 2019 http://www.rpcms.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( https://www.rpcms.cn/html/license.html )
// +----------------------------------------------------------------------
// | Author: ralap <www.rpcms.cn>
// +----------------------------------------------------------------------

namespace rp;

class Db{
	protected static $instance;
	private $_mysqli=[];
	private $options;
	private $prefix;
	private $table;
	private $field = '*';
	private $join = '';
	private $where = array();
	private $limit = '';
	private $order = '';
	private $group = '';
	private $slave;
	private $isGetSql = false;
	private $results = '';
	
	public function __construct($connectName){
		$config=SETTINGPATH.'/config/'.$connectName.'.php';
		if(!is_file($config)){
			throw new \Exception(json_encode(['message'=>'database config file is not find']));
		}
		$config=include $config;
		$this->options=$config['db'];
		$this->prefix=$this->options["prefix"];
	}
	
	public static function connect($name=''){
		return self::instance($name);
	}
	
	public static function transaction(){
		$con=self::connect();
		empty($con->_mysqli['w']) && $con->_mysqli['w']=$con->createLink();
		$con->_mysqli['w']->begin_transaction();
	}

	public static function commit(){
		$con=self::connect();
		$con->_mysqli['w']->commit();
	}

	public static function rollback(){
		$con=self::connect();
		$con->_mysqli['w']->rollback();
	}
	
	public static function close(){
		self::$instance=NULL;
	}
	
	public static function instance($connectName=''){
		if(empty($connectName)){
			$connectName=config::get('db_connect', 'default');
		}
		if(empty(self::$instance[$connectName])){
			$obj=new self($connectName);
			empty($obj->_mysqli['r']) && $obj->_mysqli['r']=$obj->createLink();
			self::$instance[$connectName]=$obj;
		}
		return self::$instance[$connectName];
	}
	
	public function _name($table){
		$this->_reset_sql();
		$this->table=$this->prefix . $table;
		return $this;
	}
	
	public function _table($table){
		$this->_reset_sql();
		$this->table=$table;
		return $this;
	}
	
	public function alias($alias=''){
		!empty($alias) && $this->table.=" as ".$alias;
		return $this;
	}
	
	public function field($field='*'){
		if(is_array($field)){
			$field=join(",",$field);
		}
		$this->field=$field;
		return $this;
	}
	
	public function join($table, $condition = null, $type = 'left'){
		if(is_array($table) && count($table) != count($table,true)){
			foreach($table as $key=>$val){
				if(is_array($val) && !empty($val)){
					$jtype=(isset($val[2]) && !empty($val[2])) ? $val[2] : $type;
					$jointable = is_array($val[0]) ? $val[0][0].' '.$val[0][1] : (0 === strpos($val[0], '(') ? $val[0] : $this->prefix . $val[0]);
					$this->join.=" ".$jtype." join ". $jointable;
					!empty($val[1]) && $this->join.=" on ".$val[1];
				}
			}
		}else{
			if(is_array($table)){
				$jointable = $table[0].' '.$table[1];
			}else if(0 === strpos($table, '(')) {
				$jointable = $table;
			}else{
				$jointable = $this->prefix . $table;
			}
			
			$this->join.=" ".$type." join ". $jointable;
			!empty($condition) &&  $this->join.=" on ".$condition;
		}
		return $this;
	}
	
	public function where($where){
		if(empty($where)) return $this;
		if(is_array($where)){
			$whereStr=array();
			foreach($where as $k=>$v){
				if((false === strpos($k, '(') || 0 !== strpos($k, '(')) && (strpos($k, '&') || strpos($k, '|'))){
					$k='('.$k.')';
				}
				$oldV=$kn=explode('#',str_replace(array('&','|','(',')'),array('#','#','',''),$k));
				$kn = array_map(function($item){$item=trim($item);return false === strpos($item, '->') ? '/\b'.preg_quote($item).'\b/' : '/'.preg_quote($item).'/';}, $kn);
				$k=str_replace(array('&','|'),array(' and ',' or '),$k);
				$oldKn = array_map(function($item){return '/{key}/';}, $kn);
				if(is_array($v)){
					$v=count($v) == count($v,1) || in_array($v[0], ['in', 'not in']) ? $this->parseItem($v) : array_map(array($this,'buildValue'), $v);
				}else{
					$v=$this->parseValue($v);
				}
				$res=preg_replace($kn,$v,$k,1);
				$whereStr[]=preg_replace($oldKn,$oldV,$res,1);
			}
			$this->where=array_merge($this->where,$whereStr);
		}else{
			$this->where[]=$where;
		}
		return $this;
	}
	
	public function order($order,$by="desc"){
		if($order === null){
			$this->order=" order by NULL";
			return $this;
		}
		$by=strtolower($by);
		$by=$by == 'asc' ? 'asc' : 'desc';
		if(is_array($order)){
			$strs=array();
			foreach($order as $k=>$v){
				if(preg_match('/^[\w\.\*\+\(\)]+$/', $k)){
					$v=strtolower($v) == 'asc' ? 'asc' : 'desc';
					$strs[]=$k." ".$v;
				}
			}
			$order=join(" , ",$strs);
			!empty($order) && $this->order=" order by ".$order;
		}else{
			if(preg_match('/^[\w\.\*\+\(\)]+$/', $order)){
				$this->order=" order by ".$order." ".$by;
			}
		}
		return $this;
	}
	
	public function group($group){
		!empty($group) && $this->group=" group by ".$group;
		return $this;
	}
	
	public function slave($slaveName){
		$slaveName=(string)$slaveName;
		$this->slave=$slaveName;
		return $this;
	}
	
	public function find($type="assoc"){
		$this->limit=' limit 1';
		$sql="select ".$this->field." from ".$this->table.$this->join.$this->buildWhere().$this->group.$this->order.$this->limit;
		if($this->isGetSql){
			return $sql;
		}
		$this->results=$this->execute($sql, 'r');
		$res=$this->result($type);
		return $res;
	}
	
	public function count($key="*"){
		$sql="select count(".$key.") as me_count from ".$this->table.$this->join.$this->buildWhere().$this->group;
		if($this->isGetSql){
			return $sql;
		}
		$this->results=$this->execute($sql, 'g');
		$res=$this->result();
		return $res["me_count"];
	}
	
	public function sum($field){
		$sql="select sum(".$field.") as me_sum from ".$this->table.$this->join.$this->buildWhere().$this->group;
		if($this->isGetSql){
			return $sql;
		}
		$this->results=$this->execute($sql, 'g');
		$res=$this->result();
		return $res["me_sum"];
	}
	
	public function select($type="assoc"){
		$sql="select ".$this->field." from ".$this->table.$this->join.$this->buildWhere().$this->group.$this->order.$this->limit;
		if($this->isGetSql){
			return $sql;
		}
		$this->results=$this->execute($sql, 'r');
		if($type != 'yield'){
			return $this->returnResult($type);
		}
		return $this->yieldResult();
	}
	
	public function getSql($isGet=true){
		$this->isGetSql=$isGet;
		return $this;
	}
	
	public function query($sql){
		$this->results=$this->execute($sql, 'r');
		return $this;
	}
	
	public function result($type="assoc",$n="one"){
		$res=array();
		if(!$this->results) return;
		if($n=="one"){
			switch($type){
				case "row":$res=$this->results->fetch_row();break;
				case "assoc":$res=$this->results->fetch_assoc();break;
				case "array":$res=$this->results->fetch_array();break;
				case "object":$res=$this->results->fetch_object();break;
				case "num":$res=$this->results->num_rows;break;
				default :$res=$this->results->fetch_assoc();break;
			}
		}else{
			switch($type){
				case "row":
					while($row=$this->results->fetch_row()){
						$res[]=$row;
					}
					break;
				case "assoc":
					while($row=$this->results->fetch_assoc()){
						$res[]=$row;
					}
					break;
				case "array":
					while($row=$this->results->fetch_array()){
						$res[]=$row;
					}
					break;
				case "object":
					while($row=$this->results->fetch_object()){
						$res[]=$row;
					}
					break;
				case "all":$res=$this->results->fetch_all();break;
				case "num":$res=$this->results->num_rows;break;
				default:
					while($row=$this->results->fetch_assoc())
						{$res[]=$row;}
					break;
			}
		}
		$this->results->free();
		$this->results = '';
		return $res;
	}

	public function insert($data=array(),$modifier=''){
		if(count($data) == count($data, 1)){
			$datakey=$data;
			$dataval=array($data);
		}else{
			$datakey=$data[0];
			$dataval=$data;
		}
		$key_arr=array_keys($datakey);
		$val_arr=array();
		foreach($dataval as $k=>$v){
			if(!is_array($v)) continue;
			$v=array_map(function($vv){return $this->escapeString($vv, false);},$v);
			$val_arr[]="('".join("','",$v)."')";
		}
		$key="(`".join("`,`",$key_arr)."`)";
		$vals=join(",",$val_arr).";";
		$sql="insert ".$modifier." into ".$this->table.$key." values".$vals;
		if($this->isGetSql){
			return $sql;
		}
		$this->execute($sql, 'w');
		return !empty($this->insert_id()) ? $this->insert_id() : $this->affected_rows();
	}
	
	public function update($data=array(),$modifier=''){
		$strs=array();
		foreach($data as $k=>$v){
			$strs[]=$v === NULL  ? "`".$k."` = NULL" : "`".$k."` ='".$this->escapeString($v, false)."'";
		}
		$updata=join(" , ",$strs);
		$sql="update ".$modifier." ".$this->table." SET ".$updata.$this->buildWhere();
		if($this->isGetSql){
			return $sql;
		}
		return $this->execute($sql, 'w');
	}
	
	public function setInc($field,$val=1){
		$sql="update ".$this->table." SET `".$field."`=".$field."+".$val." ".$this->buildWhere();
		if($this->isGetSql){
			return $sql;
		}
		return $this->execute($sql, 'w');
	}
	
	public function setDec($field,$val=1){
		$sql="update ".$this->table." SET `".$field."`=".$field."-".$val." ".$this->buildWhere();
		if($this->isGetSql){
			return $sql;
		}
		return $this->execute($sql, 'w');
	}
	
	public function dele(){
		$sql="delete from ".$this->table.$this->buildWhere().$this->order.$this->limit;
		if($this->isGetSql){
			return $sql;
		}
		return $this->execute($sql, 'w');
	}
	
	public function insert_id(){
		return $this->_mysqli['w']->insert_id;
	}
	
	public function affected_rows(){
		return $this->_mysqli['w']->affected_rows;
	}
	
	public function server_info(){
		return $this->_mysqli['r']->server_info;
	}

	public function server_version(){
		return $this->_mysqli['r']->server_version;
	}
	
	private function returnResult($type){
		$res=$this->result($type,"all");
		return $res;
	}
	private function yieldResult(){
		while($row=$this->results->fetch_assoc()){
			yield $row;
		}
	}
	
	private function execute($sql, $slave='r'){
		if(!empty($this->slave)){
			$slave=$this->slave;
		}
		if(empty($this->_mysqli[$slave])){
			$this->_mysqli[$slave]=$this->createLink();
		}
		$res=$this->_mysqli[$slave]->query($sql, MYSQLI_USE_RESULT);
		$this->_reset_sql();
		if(!$res){
			$error=$this->_mysqli[$slave]->error_list;
			$errorMsg=[
				'message'=>[],
				'sql'=>$sql,
			];
			if(is_array($error)){
				foreach($error as $k=>$v){
					$errorMsg['message'][]='Error:'.$v["errno"].'<br> Message:'.$v['error'];
				}
			}else{
				$errorMsg['message']=$error;
			}
			throw new \Exception(json_encode($errorMsg), 1500);
		}
		return $res;
	}
	
	private function createLink(){
		$link = mysqli_init();
		$link->options(MYSQLI_OPT_CONNECT_TIMEOUT, 10);
		if(!$link->real_connect($this->options["hostname"], $this->options["username"], $this->options["password"], $this->options['database'])){
			throw new \Exception(json_encode(['message'=>'Connect Error (' . $link->connect_errno . ') '. $link->connect_error]), 1500);
		}
		$link->set_charset($this->options["charset"]);
		return $link;
	}
	
	private function _reset_sql(){
		$this->table='';
		$this->field = '*';
		$this->join = '';
		$this->where = array();
		$this->limit = '';
		$this->order = '';
		$this->group = '';
		$this->isGetSql = false;
	}
	
	private function buildWhere(){
		return !empty($this->where) ? " where ".join(" and ",$this->where) : "";
	}
	
	private function buildValue($sv){
		return is_array($sv) ? $this->parseItem($sv) : $this->parseValue($sv);
	}

	private function parseItem($value){
		if(in_array(strtolower($value[0]),array('=','<>','!=','>','<','>=','<=','like','not like','in','not in','between','not between','exists','not exists','exp','find_in_set'))){
			switch($value[0]){
				case 'in':
				case 'not in':
					$pre=is_string($value[1]) ? substr($value[1], 0, 4) : '';
					if($pre == 'sql:'){
						$val=substr($value[1], 4);
					}else{
						$val=array_unique(is_array($value[1]) ? $value[1] : explode(',', $value[1]));
						$val=array_map(function($v){return "'".$this->escapeString($v)."'";}, $val);
						$val=implode(',', $val);
					}
					return '{key} '.$value[0].'('.$val.')';
					break;
				case 'exists':
				case 'not exists':
					return ' '.$value[0].'('.$value[1].')';
					break;
				case 'between':
				case 'not between':
					return '({key} '.$value[0].' \''.$this->escapeString($value[1]).'\' and \''.$this->escapeString($value[2]).'\')';
					break;
				case 'exp':
					return "({key} regexp '".$this->escapeString($value[1])."')";
					break;
				case 'find_in_set':
					return "find_in_set('".$this->escapeString($value[1])."',{key})";
					break;
				case 'like':
				case 'not like':
					return "{key} ".$value[0]." '".$this->escapeString($value[1])."'";
					break;
				default:
					return count($value) > 1 ? "{key} ".$value[0]." '".$this->escapeString($value[1])."'" : "{key} = '".$this->escapeString($value[0])."'";
			}
		}else{
			$value = array_map(array($this,'parseValue'), $value);
		}
		return $value;
	}

	private function parseValue($value){
		return '{key} '.(in_array(strtolower($value),array('null','not null')) ? 'is '.$value : "= '".$this->escapeString($value)."'");
	}
	
	public function limit($limit){
		(!empty($limit) && false === strpos($limit, '(')) && $this->limit=" limit ".$limit;
		return $this;
	}
	
	private function escapeString($str, $filterBackslash=true){
		$filterBackslash && $str=str_replace('\\','\\\\',$str);
		return addslashes($str);
	}
	
	public function __call($method, $params){
		in_array($method, ['name', 'table']) && $method='_'.$method;
		return call_user_func_array([$this, $method], $params);
	}
	
	public static function __callStatic($method, $params){
		in_array($method, ['name', 'table']) && $method='_'.$method;
		return call_user_func_array([static::connect(), $method], $params);
	}
}