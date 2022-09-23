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

class App{
	
	public $pathinfo;
	public $varPathinfo='s';
	public $pathinfoFetch=array('ORIG_PATH_INFO', 'REDIRECT_PATH_INFO', 'REDIRECT_URL');
	public $path;
	public $pathArr;
	public $pageExt;
	public $diyAdmin='';
	public $appPath;
	public $baseUrl;
	public $route=['module'=>'','controller'=>'','action'=>''];
	public $routePath=array();
	public $params;
	public $allPlugin=array();
	public $errorMsgReturn=false;
	public static $server=array();
	
	public function __construct(){
		self::$server = $_SERVER;
		$this->pageExt=Config::get('url_html_suffix', 'html');
		$this->diyAdmin=Config::get('diy_admin');
		$this->appPath=!empty(Config::get('app_default_path')) ? '/'.Config::get('app_default_path') : '';
		$this->baseUrl=self::server('REQUEST_SCHEME') . '://'.self::server('HTTP_HOST').$this->appPath;
	}
	
	public function __destruct(){
		Db::close();
	}
	
	public function run(){
		register_shutdown_function('Debug_Shutdown_Handler');
		set_error_handler('Debug_Error_Handler');
        set_exception_handler('Debug_Exception_Handler');
		$this->pathinfo();
		$this->path();
		$this->pathArr=array_filter(explode('/', $this->path));
		$this->route['module']=isset($this->pathArr[0]) ? $this->pathArr[0] : '';
		$this->denyModule();//检查module是否允许访问
		$this->isInstall();
		$this->upgradeCheck();//升级提示
		$this->routeCheck();//路由分发
		Hook::doHook('cms_index_run');
		$htmlData=$this->makeContent();
		echo (is_array($htmlData) || is_object($htmlData)) ? rpMsg('请求错误') : $htmlData;
		exit;
	}
	
	private function upgradeCheck(){
		if(is_file(CMSPATH.'/data/upgrade.lock') && $this->route['module'] != 'admin'){
			echo '系统正在升级...';exit;
		}
	}
	
	public function makeContent(){
		if(!preg_match('/^[A-Za-z](\w|\.)*$/', $this->route['controller'])){
			rpMsg('controller not exists:' . $this->route['controller']);
		}
		$dir=LIBPATH .'/'. $this->route['module'];
		if($this->route['module'] != 'plugin'){
			if(!is_dir($dir)){
				rpMsg($this->route['module'].' module is not find');
			}
			$controllerName='\rp\\'.$this->route['module'] .'\\'. $this->route['controller'];
		}else{
			if(!in_array($this->route['controller'],$this->allPlugin)){
				rpMsg($this->route['controller'].' plugin in not enabled');
			}
			$controllerName = '\plugin\\'. $this->route['controller'].'\\'.$this->route['action'];
			$this->route['action']=isset($this->routePath[3]) ? $this->routePath[3] : (isset($this->pathArr[3]) ? $this->pathArr[3] : 'index');
		}
		$moduleConfig=SETTINGPATH.'/config/'.$this->route['module'].'.php';
		if(!in_array($this->route['module'],array('index','install','admin','api')) && is_file($moduleConfig)){
			Config::set(include_once $moduleConfig);
			Db::close();
		}
		return $this->invokeClass($controllerName, $this->route['action']);
	}
	
	public function invokeClass($class, $action){
		$class=new \ReflectionClass($class);
		$file=pathinfo(basename($class->getFileName(), '.php'), PATHINFO_EXTENSION);
		if($file != 'class'){
			rpMsg($this->route['controller'].'控制器加载失败。');
		}
		$object=$class->newInstanceArgs([$this->params]);
		if(!is_callable([$object, $action])){
			rpMsg($action.' action is not find');
		}
		$reflect=new \ReflectionMethod($object, $action);
		if(!empty($reflect->getParameters())){
			rpMsg($action.' action is not find');
		}
		return $reflect->invokeArgs($object, []);
	}
	
	public function getUrlModule(){
		return !empty($this->diyAdmin) ? str_replace('admin',$this->diyAdmin,$this->route['module']) : $this->route['module'];
	}
	public function nowUrl($ext=true){
		$module=$this->getUrlModule();
		return Url::setUrl('/'. $module .'/'. $this->route['controller'] .'/'. $this->route['action']);
	} 
	
	public function isAjax(){
		$value=self::server('HTTP_X_REQUESTED_WITH');
		$accept=self::server('HTTP_ACCEPT');
		$result='xmlhttprequest' == strtolower($value) ? true : false;
		if(!$result && (
			false !== stripos($accept, 'application/json, text/javascript') || 
			false !== stripos($accept, 'application/json, text/plain') || 
			false !== stripos($accept, 'text/javascript, application/javascript')
		)){
			$result=true;
		}
		return $result;
	}
	
	public function isGet(){
		$method=self::server('REQUEST_METHOD', 'GET');
        return $method == 'GET';
    }

    public function isPost(){
		$method=self::server('REQUEST_METHOD', 'GET');
        return $method == 'POST';
    }

    public function isPut(){
		$method=self::server('REQUEST_METHOD', 'GET');
        return $method == 'PUT';
    }
	
	/**
	* 获取server参数
	* @access public
	* @param  string $name 数据名称
	* @param  string $default 默认值
	* @return mixed
	*/
	public static function server($name = '', $default = ''){
		if(empty($name)){
			return self::$server;
		}else{
			$name=strtoupper($name);
		}
		return isset(self::$server[$name]) ? self::$server[$name] : $default;
	}
	
	/**
	* 加载HOOK
	* @access public
	*/
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
					Hook::addHook($k,$sv);
				}
			}
		}
	}
	
	/**
	* 重置HOOK
	* @access public
	*/
	public function resetHook(){
		Hook::setHookNull();
		$defaultHook=Config::get('default_hook');
		foreach($defaultHook as $k=>$v){
			foreach($v as $sk=>$sv){
				Hook::addHook($k,$sv);
			}
		}
		Plugin::ResetAllHook();
		$tempName=Cache::read('template');
		$nameF='templates\\index\\'.strtolower($tempName['name']).'\\Hook';
		if(file_exists(TMPPATH . '/index/'. $tempName['name'] .'/Hook.class.php') && class_exists($nameF)){
			$temp=new $nameF;
			if(method_exists($temp,'addHook')){
				$hooks=$temp->addHook();
				if(!empty($hooks) && is_array($hooks)){
					foreach($hooks as $k=>$v){
						Hook::addHook($k,$v);
					}
				}
			}
		}
		Hook::saveHook();
	}
	
	/**
	* 执行路由
	* @access private
	*/
	private function routeCheck(){
		$host=self::server('HTTP_HOST');
		$this->rootDomain=Config::get('domain_root');
		$this->domainRules=Config::get('domain_root_rules');
		if(!empty($this->rootDomain)){
			$domain=explode('.', rtrim(stristr($host, $this->rootDomain, true), '.'));
		}else{
			$domain=explode('.', $host, -2);
		}
		$this->subDomain=implode('.', $domain);
		$this->isWapAuto();
		$this->isSubDomain();
		$pluginRouteArr=array_filter(Hook::doHook('cms_index_begin'));
		$pluginRoute=array();
		if(!empty($pluginRouteArr)){
			foreach($pluginRouteArr as $v){
				$pluginRoute=array_merge($pluginRoute, $v);
			}
		}
		$routeFiles=glob(SETTINGPATH.'/route/*.php');
		$moduleRoute=array();
		foreach($routeFiles as $file){
			$rules=include $file;
			$moduleRoute=array_merge($moduleRoute, $rules);
		}
		$rules=array_merge($moduleRoute, $pluginRoute);
		Route::rules($rules);
		Route::subDomain($this->subDomain);
		$result=Route::check($this->path ? $this->path : 'index');
		if(!empty($result['model'])){
			$path=array_values(array_filter(explode('/', $result['model'])));
			if(count($path) < 3){
				rpMsg('路由分发规则错误');
			}
			$this->routePath=$path;
			$this->params=$result['params'];
			$_GET=array_merge($_GET, $this->params);
			$_REQUEST=array_merge($_REQUEST, $this->params);
			$this->parseModule($path);
		}
		if(empty($this->route['action'])){
			if(isset($this->domainRules[$this->subDomain])){
				$this->pathArr=array_merge(array_values(array_filter($this->route)), $this->pathArr);
			}elseif(count($this->pathArr) < 3 && !is_dir(LIBPATH.'/'.$this->pathArr[0]) && $this->pathArr[0] != $this->diyAdmin){
				$this->pathArr=array_merge([Config::get('default_module')], $this->pathArr);
			}
			if(!isset($this->pathArr[0])){
				$this->pathArr[0]=Config::get('default_module');
			}
			if(!isset($this->pathArr[1])){
				$this->pathArr[1]=Config::get('default_controller');
			}
			if(!isset($this->pathArr[2])){
				$this->pathArr[2]=Config::get('default_action');
			}
			$this->parseModule($this->pathArr);
		}
		if($this->route['module'] == $this->diyAdmin){
			$this->route['module']='admin';
		}
	}
	
	/**
	* 判断是否安装系统
	* @access private
	*/
	private function isInstall(){
		if(!is_file(CMSPATH .'/data/install.lock')){
			$rootPath=strtolower(str_replace(DIRECTORY_SEPARATOR, '/', input('SERVER.DOCUMENT_ROOT')));
			$runPath=strtolower(str_replace(DIRECTORY_SEPARATOR, '/', CMSPATH));
			if($rootPath != $runPath){
				$appPath=str_replace($rootPath.'/', '', $runPath);
				$this->appPath='/'.$appPath;
				$this->baseUrl.=$this->appPath;
				Config::set('app_default_path', $appPath);
			}
			if($this->route['module'] != 'install'){
				redirect($this->appPath.'/install/index/index');
			}
		}else{
			$option=Cache::read('option');
			Config::set('webConfig',$option);
			Plugin::getAllPlugin();
			$this->runHook();
		}
	}
	
	/**
	* 判断module是否禁止
	* @access private
	*/
	private function denyModule(){
		if(empty($this->pathArr)){
			return;
		}
		$module=$this->route['module'];
		$denyModule=explode(',', Config::get('deny_module'));
		if(!empty($this->diyAdmin)){
			$denyModule[]='admin';
			if($module == $this->diyAdmin){
				$this->route['module']='admin';
			}
		}
		if(!empty($module) && in_array($module, $denyModule)){
			rpMsg($module.' module is not find');
		}
	}

	/**
	* 判断手机端
	* @access private
	*/
	private function isWapAuto(){
		$wapAuto=Config::get('webConfig.wap_auto');
		$wapDomain=Config::get('webConfig.wap_domain');
		if($wapAuto == 1 && isMobile() && !empty($wapDomain) && $this->subDomain != $wapDomain){
			if(!empty($this->rootDomain)){
				$mhost=$wapDomain.'.'.$this->rootDomain;
			}else{
				$hostData=explode('.',$host);
				$hostData[0]=$wapDomain;
				$mhost=implode('.',$hostData);
			}
			$url=self::server('REQUEST_SCHEME').'://'. $mhost .self::server('REQUEST_URI');
			redirect($url);
			exit;
		}
	}
	
	/**
	* 判断子域名
	* @access private
	*/
	private function isSubDomain(){
		if(!empty($this->subDomain) && $this->subDomain != Config::get('webConfig.wap_domain') && !empty($this->domainRules) && isset($this->domainRules[$this->subDomain])){
			$domainRulesPattern=$this->domainRules[$this->subDomain];
			$domainPath=array_values(array_filter(explode('/', $domainRulesPattern)));
			if(!empty($domainPath)){
				$this->parseModule($domainPath);
			}
		}
	}
	
	/**
	* 解析URL地址为 模块/控制器/操作
	* @access private
	* @return array
	*/
	private function parseModule($path){
		if(isset($path[0]) && !empty($path[0])){
			$this->route['module']=$path[0];
		}
		if(isset($path[1]) && !empty($path[1])){
			$this->route['controller']=$path[1];
		}
		if(isset($path[2]) && !empty($path[2])){
			$this->route['action']=$path[2];
		}
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
				foreach($this->pathinfoFetch as $type){
					if(self::server($type)){
						$pathinfo = (0 === strpos(self::server($type), self::server('SCRIPT_NAME'))) ? substr(self::server($type), strlen(self::server('SCRIPT_NAME'))) : self::server($type);
						break;
					}
				}
			}
			$this->pathinfo = empty($pathinfo) || '/' == $pathinfo ? '' : ltrim($pathinfo, '/');
		}
		return $this->pathinfo;
	}
	
	/**
	* 获取当前请求URL的pathinfo信息(不含URL后缀)
	* @access private
	* @return string
	*/
	private function path(){
		if(is_null($this->path)){
			$pathinfo=$this->pathinfo();
			if(false === $this->pageExt){
				$this->path=$pathinfo;
			}elseif($this->pageExt){
				$this->path=preg_replace('/\.('.$this->pageExt.')$/i', '', $pathinfo);
			}else{
				$this->path=preg_replace('/\.'.$this->ext().'$/i', '', $pathinfo);
			}
			$this->path=str_replace(Config::get('app_default_path'), '', $this->path);
			$this->path=strip_tags(trim($this->path, '/'));
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
	
}