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

class App{
	public $pathinfo;
	public $pathinfoFetch=array('ORIG_PATH_INFO', 'REDIRECT_PATH_INFO', 'REDIRECT_URL');
	public $pageExt='html';
	public $path;
	public $route;
	public $routePath=array();
	public $baseUrl;
	public $params='';
	public $varPathinfo='s';
	public $diyAdmin='';
	public $allPlugin=array();
	public static $server=array();
	
	public function __construct(){
		$this->appPath=!empty(Config::get('app_default_path')) ? '/'.Config::get('app_default_path') : '';
		$this->pageExt=Config::get('url_html_suffix');
		self::$server = $_SERVER;
		$this->baseUrl= self::$server['REQUEST_SCHEME'] . '://'.self::$server['HTTP_HOST'] . $this->appPath;
		$this->diyAdmin=Config::get('diy_admin');
	}
	
	public function run(){
		set_error_handler('Debug_Error_Handler');
        set_exception_handler('Debug_Exception_Handler');
        //register_shutdown_function('Debug_Shutdown_Handler');
		$this->route=$this->parseModule();
		$this->denyModule();//检查module是否允许访问
		$this->isInstall();
		$this->routeCheck();//路由分发
		\rp\Hook::doHook('cms_index_run');
		if(!preg_match('/^[A-Za-z](\w|\.)*$/', $this->route['controller'])){
			rpMsg('controller not exists:' . $this->route['controller']);
		}
		$dir=LIBPATH .'/'. $this->route['module'];
		if($this->route['module'] != 'plugin'){
			if(!is_dir($dir)){
				rpMsg($this->route['module'].' module is not find');
			}
			$controllerName = '\rp\\'.$this->route['module'] .'\\'. $this->route['controller'];
		}else{
			if(!in_array($this->route['controller'],$this->allPlugin)){
				rpMsg($this->route['controller'].' plugin in not enabled');
			}
			$controllerName = '\plugin\\'. $this->route['controller'].'\\'.$this->route['action'];
			$this->route['action']=isset($this->routePath[3]) ? $this->routePath[3] : 'index';
		}
		$controller = new $controllerName($this->params);
		$action=$this->route['action'];
		if(!method_exists($controller,$action)){
			rpMsg($action.' action is not find');
		}
		$htmlData=$controller->$action();
		echo $htmlData;
		exit;
	}
	
	public function runHook(){
		$hookFile=CMSPATH .'/data/hook.php';
		$hookArr=array();
		if(!is_file($hookFile) || filesize($hookFile) <= 0){
			$this->resetHook();
		}
		$hookArr=include_once $hookFile;
		if(is_array($hookArr) && !empty($hookArr)){
			foreach($hookArr as $k=>$v){
				foreach($v as $sk=>$sv){
					\rp\Hook::addHook($k,$sv);
				}
			}
		}
	}
	
	public function resetHook(){
		\rp\Hook::setHookNull();
		$defaultHook=Config::get('default_hook');
		foreach($defaultHook as $k=>$v){
			foreach($v as $sk=>$sv){
				\rp\Hook::addHook($k,$sv);
			}
		}
		\rp\Plugin::ResetAllHook();
		
		$tempName=Cache::read('template');
		if(file_exists(TMPPATH .'/index/'. $tempName['name']) && file_exists(TMPPATH . '/index/'. $tempName['name'] .'/Hook.class.php')){
			$nameF='template\\index\\'.strtolower($tempName['name']).'\\Hook';
			$temp=new $nameF;
			if(method_exists($temp,'addHook')){
				$hooks=$temp->addHook();
				if(!empty($hooks) && is_array($hooks)){
					foreach($hooks as $k=>$v){
						\rp\Hook::addHook($k,$v);
					}
				}
			}
		}
		
		\rp\Hook::saveHook();
	}
	
	private function isInstall(){
		if(!file_exists(CMSPATH .'/data/install.lock')){
			if($this->route['module'] != 'install'){
				redirect('install/index');
			}
		}else{
			$option=Cache::read('option');
			Config::set('webConfig',$option);
			\rp\Plugin::getAllPlugin();
			$this->runHook();
		}
	}
	
	public function getUrlModule(){
		return !empty($this->diyAdmin) ? str_replace('admin',$this->diyAdmin,$this->route['module']) : $this->route['module'];
	}
	public function nowUrl($ext=true){
		$module=$this->getUrlModule();
		return \rp\Url::setUrl('/'. $module .'/'. $this->route['controller'] .'/'. $this->route['action']);
	} 
	
	public function isAjax(){
		$value  = self::server('HTTP_X_REQUESTED_WITH');
		return 'xmlhttprequest' == strtolower($value) ? true : false;
	}
	
	/**
	* 解析URL地址为 模块/控制器/操作
	* @access private
	* @return array
	*/
	private function parseModule($pathIn=''){
		$path = !empty($pathIn) ? $pathIn : array_filter(explode('/', strtolower($this->path())));
		$action = Config::get('default_action');
		$controller = Config::get('default_controller');
		$module = Config::get('default_module');
		if(isset($path[0]) && !empty($path[0])){
			$module = $path[0];
		}
		if(isset($path[1]) && !empty($path[1])){
			$controller = $path[1];
		}
		if(isset($path[2]) && !empty($path[2])){
			$action = $path[2];
		}
		return array('action'=>$action,'controller'=>$controller,'module'=>$module);
	}
	
	private function denyModule(){
		$module=$this->route['module'];
		$denyModule=explode(',',Config::get('deny_module'));
		if(!empty($this->diyAdmin)){
			$denyModule[]='admin';
			if($this->route['module'] == $this->diyAdmin){
				$this->route['module']='admin';
			}
		}
		if(in_array($module,$denyModule)){
			rpMsg($module.' module is not find');
		}
	}
	
	private function routeCheck(){
		$host=self::server('HTTP_HOST');
		$rootDomain = Config::get('domain_root');
		$domainRules = Config::get('domain_root_rules');
		if(!empty($rootDomain)){
			$domain = explode('.', rtrim(stristr($host, $rootDomain, true), '.'));
		}else{
			$domain = explode('.', $host, -2);
		}
		$this->subDomain = implode('.', $domain);
		if(Config::get('webConfig.wap_auto') == 1 && isMobile() && !empty(Config::get('webConfig.wap_domain')) && $this->subDomain != Config::get('webConfig.wap_domain')){
			if(!empty($rootDomain)){
				$mhost=Config::get('webConfig.wap_domain').'.'.$rootDomain;
			}else{
				$hostData=explode('.',$host);
				$hostData[0]=Config::get('webConfig.wap_domain');
				$mhost=implode('.',$hostData);
			}
			$url=self::server('REQUEST_SCHEME').'://'. $mhost .self::server('REQUEST_URI');
			redirect($url);
		}
		if(!empty($this->subDomain) && $this->subDomain != Config::get('webConfig.wap_domain') && !empty($domainRules) && isset($domainRules[$this->subDomain])){
			$domainRulesPattern=$domainRules[$this->subDomain];
			$domainPath = array_values(array_filter(explode('/', $domainRulesPattern)));
			if(!empty($domainPath)){
				$domainPath=array_merge($domainPath, array_filter(explode('/', strtolower($this->path()))));
				$this->route=$this->parseModule($domainPath);
				return true;
			}
		}
		$pluginRouteArr=\rp\Hook::doHook('cms_index_begin');
		$pluginRoute=array();
		if(!empty($pluginRouteArr)){
			foreach($pluginRouteArr as $v){
				$pluginRoute=array_merge($pluginRoute,$v);
			}
		}
		if(file_exists(CMSPATH . '/route.php')){
			$rules=include CMSPATH . '/route.php';
			$rules=array_merge($pluginRoute,$rules);
			Route::rules($rules);
			Route::subDomain($this->subDomain);
		}
		$result = Route::check($this->path());
		if(!empty($result['model'])){
			$path = array_values(array_filter(explode('/', $result['model'])));
			if(count($path) < 3){
				rpMsg('路由分发规则错误');
			}
			$this->routePath=$path;
			$this->params=$result['params'];
			$this->route=array('action'=>$path[2],'controller'=>$path[1],'module'=>$path[0]);
		}
	}

	/**
	* 获取当前请求URL的pathinfo信息(不含URL后缀)
	* @access private
	* @return string
	*/
	private function path(){
		if (is_null($this->path)) {
			$suffix   = Config::get('url_html_suffix');
			$pathinfo = $this->pathinfo();
			if (false === $suffix) {
				$this->path = $pathinfo;
			} elseif ($suffix) {
				$this->path = preg_replace('/\.(' . ltrim($suffix, '.') . ')$/i', '', $pathinfo);
			} else {
				$this->path = preg_replace('/\.' . $this->ext() . '$/i', '', $pathinfo);
			}
			$this->path = str_replace(Config::get('app_default_path'), '', $this->path);
			$this->path = strip_tags(trim($this->path, '/'));
		}
		return $this->path;
	}
	
	/**
	* 当前URL的访问后缀
	* @access private
	* @return string
	*/
	private function ext(){
		return pathinfo($this->pathinfo(), PATHINFO_EXTENSION);
	}
	
	/**
	* 获取当前请求URL的pathinfo信息（含URL后缀）
	* @access private
	* @return string
	*/
	private function pathinfo(){
		if(is_null($this->pathinfo)){
			if (isset($_GET[$this->varPathinfo])){
                $pathinfo = $_GET[$this->varPathinfo];
                unset($_GET[$this->varPathinfo]);
            }elseif(self::server('PATH_INFO')){
				$pathinfo = self::server('PATH_INFO');
			}elseif('cli-server' == PHP_SAPI) {
				$pathinfo = strpos(self::server('REQUEST_URI'), '?') ? strstr(self::server('REQUEST_URI'), '?', true) : self::server('REQUEST_URI');
			}
			if(!isset($pathinfo)){
				foreach ($this->pathinfoFetch as $type) {
					if (self::server($type)) {
						$pathinfo = (0 === strpos(self::server($type), self::server('SCRIPT_NAME'))) ?
						substr(self::server($type), strlen(self::server('SCRIPT_NAME'))) : self::server($type);
						break;
					}
				}
			}
			$this->pathinfo = empty($pathinfo) || '/' == $pathinfo ? '' : ltrim($pathinfo, '/');
		}
		return $this->pathinfo;
	}
	
	/**
	* 获取server参数
	* @access public
	* @param  string $name 数据名称
	* @param  string $default 默认值
	* @return mixed
	*/
	public static function server($name = '', $default = ''){
		if (empty($name)){
			return self::$server;
		}else{
			$name = strtoupper($name);
		}
		return isset(self::$server[$name]) ? self::$server[$name] : $default;
	}
}