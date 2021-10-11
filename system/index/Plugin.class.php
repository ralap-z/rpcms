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
		if(!isset($this->params['plugin']) || empty($this->params['plugin'])){
			redirect($this->App->baseUrl);
		}
		if((!is_string($this->params['plugin']) || !preg_match("/^[\w\-\_]+$/", $this->params['plugin'])) || 
			(isset($this->params['controller']) && (!is_string($this->params['controller']) || !preg_match("/^[\w\-\_\/]+$/", $this->params['controller']))) ||
			(isset($this->params['action']) && (!is_string($this->params['action']) || !preg_match("/^[\w\-\_\/]+$/", $this->params['action'])))
		){
			rpMsg('非法链接');
		}
		$pluginName=trim(strip_tags(strDeep($this->params['plugin'])));
		$controller=isset($this->params['controller']) ? trim(strip_tags(strDeep($this->params['controller'])),'/') : 'index';
		$action=isset($this->params['action']) ? trim(strip_tags(strDeep($this->params['action'])),'/') : 'index';
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
