<?php
// +----------------------------------------------------------------------
// | RPCMS
// +----------------------------------------------------------------------
// | Copyright (c) 2019 http://www.rpcms.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: ralap <www.rpcms.cn>
// +----------------------------------------------------------------------

namespace rp;
class Db{
	protected static $instance;
	private static $_mysqli=[];
	private static $prefix;
	private static $table;
	private $field = '*';
	private $join = '';
	private $where = array();
	private $limit = '';
	private $order = '';
	private $group = '';
	private $results = '';
	
	public function __construct(){
		$this->options=Config::get('db');
		self::$prefix=$this->options["prefix"];
	}
	
	public static function instance($type=''){
        if(is_null(self::$instance)){
            self::$instance = new self();
        }
		if(!empty($type) && empty(self::$_mysqli[$type])){
			self::$_mysqli[$type]=self::$instance->connect();
		}
        return self::$instance;
    }
	
	public static function close(){
		if(!empty(self::$_mysqli)){
			foreach(self::$_mysqli as $_v){
				$_v->close();
			}
			self::$_mysqli=NULL;
			self::$instance=NULL;
		}
	}

	public static function name($table){
		$con=self::instance();
		self::$table=self::$prefix . $table;
		return $con;
	}
	
	public static function table($table){
		$con=self::instance();
		self::$table=$table;
		return $con;
	}
	
	public static function transaction(){
		$con=self::instance('w');
		self::$_mysqli['w']->begin_transaction();
	}
	
	public static function commit(){
		$con=self::instance('w');
		self::$_mysqli['w']->commit();
	}
	
	public static function rollback(){
		$con=self::instance('w');
		self::$_mysqli['w']->rollback();
	}
	
	public function field($field='*'){
		if(is_array($field)){
			$field=join(",",$field);
		}
		$this->field=$field;
		return $this;
	}
	
	public function alias($alias=''){
		!empty($alias) && self::$table.=" as ".$alias;
		return $this;
	}

	public function join($table, $condition = null, $type = 'left'){
		if(is_array($table) && count($table) != count($table,true)){
			foreach($table as $key=>$val){
				if(is_array($val) && !empty($val)){
					$jtype=(isset($val[2]) && !empty($val[2])) ? $val[2] : $type;
					$jointable = is_array($val[0]) ? $val[0][0].' '.$val[0][1] : (0 === strpos($val[0], '(') ? $val[0] : self::$prefix . $val[0]);
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
				$jointable = self::$prefix . $table;
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
					$v=count($v) == count($v,1) ? $this->parseItem($v) : array_map(array($this,'buildValue'), $v);
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
				case 'exists':
				case 'not exists':
					return '{key} '.$value[0].'('.$value[1].')';
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
	
	public function find($type="assoc"){
		$this->limit=' limit 1';
		$sql="select ".$this->field." from ".self::$table.$this->join.$this->buildWhere().$this->group.$this->order.$this->limit;
		$this->results=$this->execute($sql, 'r');
		$res=$this->result($type);
		$this->_reset_sql();
		return $res;
	}
	
	public function count($key="*"){
		$sql="select count(".$key.") as me_count from ".self::$table.$this->join.$this->buildWhere().$this->group;
		$this->results=$this->execute($sql, 'g');
		$res=$this->result();
		$this->_reset_sql();
		return $res["me_count"];
	}
	
	public function sum($field){
		$sql="select sum(".$field.") as me_sum from ".self::$table.$this->join.$this->buildWhere().$this->group;
		$this->results=$this->execute($sql, 'g');
		$res=$this->result();
		$this->_reset_sql();
		return $res["me_sum"];
	}
	
	public function select($type="assoc"){
		$sql="select ".$this->field." from ".self::$table.$this->join.$this->buildWhere().$this->group.$this->order.$this->limit;
		$this->results=$this->execute($sql, 'r');	
		$res=$this->result($type,"all");
		$this->_reset_sql();
		return $res;
	}
	
	public function getSql(){
		$sql="select ".$this->field." from ".self::$table.$this->join.$this->buildWhere().$this->group.$this->order.$this->limit;
		$this->_reset_sql();
		return $sql;
	}
	
	public function query($sql){
		$this->results=$this->execute($sql, 'r');
		$this->_reset_sql();
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
			$v=array_map(function($vv){return $this->escapeString($vv);},$v);
			$val_arr[]="('".join("','",$v)."')";
		}
		$key="(`".join("`,`",$key_arr)."`)";
		$vals=join(",",$val_arr).";";
		$sql="insert ".$modifier." into ".self::$table.$key." values".$vals;
		$this->execute($sql, 'w');
		$this->_reset_sql();
		return !empty($this->insert_id()) ? $this->insert_id() : $this->affected_rows();
	}
	
	public function update($data=array(),$modifier=''){
		$strs=array();
		foreach($data as $k=>$v){
			$strs[]=$v === NULL  ? "`".$k."` = NULL" : "`".$k."` ='".$this->escapeString($v)."'";
		}
		$updata=join(" , ",$strs);
		$sql="update ".$modifier." ".self::$table." SET ".$updata.$this->buildWhere();
		$this->_reset_sql();
		return $this->execute($sql, 'w');
	}
	
	public function setInc($field,$val=1){
		$sql="update ".self::$table." SET `".$field."`=".$field."+".$val." ".$this->buildWhere();
		$this->_reset_sql();
		return $this->execute($sql, 'w');
	}
	
	public function setDec($field,$val=1){
		$sql="update ".self::$table." SET `".$field."`=".$field."-".$val." ".$this->buildWhere();
		$this->_reset_sql();
		return $this->execute($sql, 'w');
	}
	
	public function dele(){
		$sql="delete from ".self::$table.$this->buildWhere().$this->order.$this->limit;
		$this->_reset_sql();
		return $this->execute($sql, 'w');
	}
	
	public function insert_id(){
		return self::$_mysqli['w']->insert_id;
	}
	
	public function affected_rows(){
		return self::$_mysqli['w']->affected_rows;
	}
	
	public function server_info(){
		return self::$_mysqli['r']->server_info;
	}
	
	public function server_version(){
		return self::$_mysqli['r']->server_version;
	}
	
	private function connect(){
		$link = mysqli_init();
		$link->options(MYSQLI_OPT_CONNECT_TIMEOUT, 10);
		if(!$link->real_connect($this->options["hostname"], $this->options["username"], $this->options["password"], $this->options['database'])){
			die('Connect Error (' . $link->connect_errno . ') '. $link->connect_error());
		}
		$link->set_charset($this->options["charset"]);
		return $link;
	}
	
	private function _reset_sql(){
		self::$table='';
		$this->field = '*';
		$this->join = '';
		$this->where = array();
		$this->limit = '';
		$this->order = '';
		$this->group = '';
	}
	
	private function execute($sql, $type='r'){
		if(empty(self::$_mysqli[$type])){
			self::$_mysqli[$type]=$this->connect();
		}
		$res=self::$_mysqli[$type]->query($sql, MYSQLI_USE_RESULT);
		if(!$res){$this->error(self::$_mysqli[$type]->error_list,$sql);}
		return $res;
	}
	private function escapeString($str){
        return addslashes(stripslashes($str));
    }
	private function error($msg,$sql=""){
		global $App;
		if(!Config::get('webConfig.isDevelop')){
			echo $App->isAjax() ? json(array('code'=>-1,'msg'=>'SQL执行错误')) : rpMsg('SQL执行错误');exit;
		}else{
			$error="";
			if(is_array($msg)){
				foreach($msg as $k=>$v){
					$error.="Error Number:".$v["errno"]."<br>".$v['error'];
				}
			}else{
				$error.=$msg;
			}
			if($App->isAjax()){
				json(array('code'=>-1,'msg'=>$error));
			}else{
				!empty($sql) && $error.="<p>".$sql."</p>";
				$heading="A Database Error Occurred";
				$message=array();
				$trace = debug_backtrace();
				foreach ($trace as $call){
					if (isset($call['file'], $call['class'])){
						if (DIRECTORY_SEPARATOR !== '/'){
							$call['file'] = str_replace('\\', '/', $call['file']);
						}
						$message[] = 'Filename: '.$call['file'].'    On Line Number: '.$call['line'];
					}
				}
				$message = '<p>'.(is_array($message) ? implode('</p><p>', $message) : $message).'</p>';
				echo "<h3>".$heading."</h3>";
				echo "<div style='border: 1px solid #ccc;padding: 10px;color: #313131;font-size: 15px;'>".$error."</div><div style='font-size: 13px;color: #444444;line-height: 13px;'>".$message."</div>";
				exit(8);
			}
		}
	}
	
}