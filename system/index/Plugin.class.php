<?php
namespace rp\index;

use rp\Db;

class Plugin extends base{
	private $params;
	public function __construct($params){
		parent::__construct();
		$this->params=$params;
	}
	
	public function run(){
		if(!isset($this->params[1]) || empty($this->params[1])){
			redirect($this->App->baseUrl);
		}
		if((!is_string($this->params[1]) || !preg_match("/^[\w\-\_]+$/", $this->params[1])) || 
			(isset($this->params[2]) && (!is_string($this->params[2]) || !preg_match("/^[\w\-\_\/]+$/", $this->params[2]))) ||
			(isset($this->params[3]) && (!is_string($this->params[3]) || !preg_match("/^[\w\-\_\/]+$/", $this->params[3])))
		){
			rpMsg('非法链接');
		}
		$pluginName=trim(strip_tags(strDeep($this->params[1])));
		$controller=isset($this->params[2]) ? trim(strip_tags(strDeep($this->params[2])),'/') : 'index';
		$action=isset($this->params[3]) ? trim(strip_tags(strDeep($this->params[3])),'/') : 'index';
		$plugin=PLUGINPATH .'/'.$pluginName;
		if(!is_dir($plugin)){
			rpMsg('插件不存在');
		}
		if(!Db::name('plugin')->where("ppath='".$pluginName."' and status = 0")->find()){
			rpMsg('插件未安装');
		}
		$controllerFile=$plugin .'/'.ucfirst(strtolower($controller)).'.class.php';
		if(!file_exists($controllerFile)){
			rpMsg('插件控制器不存在');
		}
		$pluginObj='plugin\\'.strtolower($pluginName).'\\'.ucfirst($controller);
		$pluginClass=new $pluginObj;
		if(method_exists($pluginClass,$action)){
			return $pluginClass->$action();
		}else{
			rpMsg('运行插件action错误，请检查');
		}
	}
}
