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

use rp\Db;
use rp\View;
use rp\Hook;
use rp\Config;

class Plugin{
	public $App;
	public $Db;
	public $Db_prefix;
	public $webConfig;
	public $pluginName;
	public $pluginPath;
	protected static $instance;
	public function __construct(){
		global $App;
		$options = Config::get('db');
		$this->App=$App;
		$this->webConfig=Config::get('webConfig');
		$this->Db=Db::instance();
		$this->Db_prefix=$options['prefix'];
		$this->pluginName=$this->getPlugin();
		$this->pluginPath='/plugin/'.$this->pluginName;
		if($this->App->route['module'] != 'admin'){
			$indexBase=new \rp\index\base();
		}
	}
	
	public static function instance(){
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }
	
	public function assign($name,$value){
		return View::assign($name,$value);
	}
	
	public function display($tmp){
		if(empty($this->pluginName)){
			rpMsg('调用错误，未匹配到插件');
		}
		if(0 !== strpos($tmp, '/')){
			$tmp='/plugin/'.$this->pluginName .'/'. $tmp;
		}
		$this->getDefault();
		View::assign('pluginPath',$this->pluginPath);
		view::setPluginName($this->pluginName);
		return View::display($tmp);
	}
	
	public function checkTemp($tmp){
		return View::checkTemp($tmp);
	}
	
	public function setConfig($plugin,$data=array()){
		if(empty($plugin)){
			rpMsg('调用错误，插件不能为空');
		}
		$res=Db::name('plugin')->where("ppath='".$plugin."' and status = 0")->find();
		if(empty($res)){
			rpMsg('该插件未安装');
		}
		$res=Db::name('plugin')->where("ppath='".$plugin."'")->update(array('config'=>addslashes(json_encode($data))));
		if(!$res){
			rpMsg('修改配置失败，请检查插件是否安装或稍后重试');
		}
		return true;
	}
	
	public function getConfig($plugin=''){
		if(empty($plugin)){
			$plugin=$this->getPlugin();
			if(empty($plugin)){
				rpMsg('调用错误，未匹配到插件');
			}
		}
		$res=Db::name('plugin')->where("ppath='".$plugin."' and status = 0")->find();
		$config=!empty($res) ? $res['config'] : '';
		return json_decode($config,true);
	}
	
	public function doPlugin($name,$controller='index',$action='index',$data=array()){
		$plugin=PLUGINPATH .'/'.$name;
		if(!is_dir($plugin)){
			return false;
		}
		if(!Db::name('plugin')->where("ppath='".$name."' and status = 0")->find()){
			return false;
		}
		$controllerFile=$plugin .'/'.ucfirst(strtolower($controller)).'.class.php';
		if(!file_exists($controllerFile)){
			return false;
		}
		$pluginObj='plugin\\'.strtolower($name).'\\'.ucfirst($controller);
		$pluginClass=new $pluginObj;
		if(method_exists($pluginClass,$action)){
			return $pluginClass->$action($data);
		}
		return false;
	}
	
	public function checkFormAdmin(){
		if(empty($this->App)){
			$this->App=self::instance()->App;
		}
		$isAjax=$this->App->isAjax();
		if($this->App->route['module'] != 'admin'){
			if($isAjax){
				echo json_encode(array('code'=>-1, 'msg'=>'非法访问'));exit;
			}else{
				rpMsg('非法访问');
			}
		}
		if(!isLogin()){
			if($isAjax){
				echo json_encode(array('code'=>-1, 'msg'=>'请先登录'));exit;
			}else{
				redirect(url('/admin/login/index'));
			}
		}
		$session=session('MEADMIN');
		$this->admin=Db::name('user')->where('id='.$session['uid'])->find();
	}
	
	public static function getAllPlugin(){
		$plugin=Db::name('plugin')->where('status=0')->select();
		self::instance()->App->allPlugin=array_column($plugin,'ppath');
	}
	
	/*重置插件HOOK*/
	public static function ResetAllHook(){
		$plugin=Db::name('plugin')->where('status=0')->select();
		foreach($plugin as $v){
			if(file_exists(PLUGINPATH .'/'. $v['ppath']) && file_exists(PLUGINPATH . '/'. $v['ppath'] .'/Index.class.php')){
				$nameF='plugin\\'.strtolower($v['ppath']).'\\Index';
				$plugin=new $nameF;
				if(method_exists($plugin,'addHook')){
					$hooks=$plugin->addHook();
					if(!empty($hooks) && is_array($hooks)){
						foreach($hooks as $k=>$v){
							Hook::addHook($k,$v);
						}
					}
				}
			}
		}
		return true;
	}
	
	private function getPlugin(){
		$class=get_class($this);
		preg_match("/(?<=\\\)\w+(?=\\\)/i", $class,$matches);
		return (isset($matches[0]) && !empty($matches[0])) ? $matches[0] : '';
	}
	
	/*
	*获取公共配置
	*/
	private function getDefault(){
		$this->webConfig['totalCode']=stripslashes($this->webConfig['totalCode']);
		View::assign('webConfig',$this->webConfig);
		View::assign('App',$this->App);
		View::assign('baseUrl',$this->App->baseUrl);
	}
}