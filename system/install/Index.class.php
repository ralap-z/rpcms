<?php
namespace rp\install;
use rp\Url;
use rp\View;
use rp\Db;
use rp\Cache;

class Index{
	private $links;
	
	public function __construct(){
		global $App;
		if(file_exists(CMSPATH .'/data/install.lock')){
			if($App->isAjax()){
				return json(array('code'=>404, 'msg'=>'404 Not Found'));
			}else{
				return rpMsg('404');
			}
		}
		restore_error_handler();
	}
	
	public function index(){
		return View::display('/index');
	}
	
	public function step1(){
		$license=CMSPATH .'/data/defend/license.txt';
		$licenseData=@file_get_contents($license);
		$licenseData=strip_tags($licenseData); 
		$licenseData=str_replace(PHP_EOL,'<br>',$licenseData);
		return json(array('code'=>200, 'msg'=>'success', 'data'=>$licenseData));
	}
	
	public function step2(){
		$data=array(
			'server'=>input('SERVER.SERVER_SOFTWARE'),
			'phpver'=>version_compare("5.6", PHP_VERSION, ">") ? '<font>RPCMS需要PHP版本最低5.6</font>' : PHP_VERSION,
			'cmspath'=>CMSPATH,
			'gd2'=>'<font>不支持</font>',
			'mbstring'=>'<font>不支持</font>',
			'mysqli'=>'<font>不支持</font>',
		);
		if(function_exists("gd_info")){
			$info = gd_info();
			$data['gd2'] = $info['GD Version'];
		}
		if(function_exists("mb_language")){
		   $data['mbstring'] = mb_language();
		}
		if(function_exists("mysqli_get_client_info")){
			$data['mysqli'] = strtok(mysqli_get_client_info(), '$');
		}
		$checkFileRW=[
			'config/default.php'=>SETTINGPATH.'/config/default.php',
			'data'=>CMSPATH . '/data',
			'plugin'=>CMSPATH . '/plugin',
			'templates/index'=>CMSPATH . '/templates/index',
			'uploads'=>CMSPATH . '/uploads',
		];
		foreach($checkFileRW as $k=>$v){
			$isRW=$this->isWritable($v);
			$data[$k]=$isRW ? '可读写' : '<font>不可读写</font>';
		}
		return json(array('code'=>200, 'msg'=>'success', 'data'=>$data));
	}
	
	public function step4(){
		global $App;
		set_error_handler(null);
		$data=input('post.');
		$data['tablepre']=!empty($data['tablepre']) ? $data['tablepre'] : 'rp_';
		if(empty($data['dbhost']) || empty($data['dbuser']) || empty($data['dbpsw']) || empty($data['dbname']) || empty($data['username']) || empty($data['userpsw'])){
			return json(array('code'=>-1, 'msg'=>'数据错误，请填写完整信息！'));
		}
		try{
			if(!$this->links=@mysqli_connect($data['dbhost'], $data['dbuser'], $data['dbpsw'])){
				return json(array('code'=>-1, 'msg'=>'无法连接数据库服务器，请检查配置！'));
			}
			if(!@mysqli_query($this->links,"CREATE DATABASE IF NOT EXISTS `".$data['dbname']."`;")){
				return json(array('code'=>-1, 'msg'=>'成功连接数据库，但是指定的数据库不存在并且无法自动创建，请先通过其他方式建立数据库！'));
			}
			mysqli_select_db($this->links,$data['dbname']);
		}catch(\mysqli_sql_exception $e){
			return json(array('code'=>-1, 'msg'=>'数据库操作异常，请检查配置。'."\r\n".'错误：'.$e->getMessage()));
		}
		$query = mysqli_query($this->links,"SELECT COUNT(*) as nums FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA='".$data['dbname']."' AND TABLE_NAME='".$data['tablepre']."config'");
		$row=mysqli_fetch_row($query);
		if($row[0] > 0){
			return json(array('code'=>-1, 'msg'=>'您已经安装RPCMS，请手动删除所有数据表后再安装'));
		}
		$keyData=json_decode(http_post('https://www.rpcms.cn/upgrade/auth/getKey', array('host'=>$App->baseUrl)),true);
		$errorTip='';
		if(!isset($keyData['data']) || empty($keyData['data'])){
			$keyData['data']='';
			$errorTip='获取网站KEY失败，原因：'.$keyData['msg'];
		}
		$installSql=CMSPATH . '/data/defend/sql.sql';
		if(!file_exists($installSql)){
			return json(array('code'=>-1, 'msg'=>'安装的数据库文件丢失'));
		}
		$sql = @file_get_contents($installSql);
		$this->_sql_execute($sql,$data['tablepre']);
		$config=array(
			'webName'=>'',
			'keyword'=>'',
			'description'=>'',
			'key'=>$keyData['data'],
			'icp'=>'',
			'totalCode'=>'',
			'isDevelop'=>'0',
			'webStatus'=>'0',
			'closeText'=>'',
			'pagesize'=>'10',
			'fileTypes'=>'rar,zip,gz,gif,jpg,jpeg,png,txt,pdf,docx,doc,xls,xlsx',
			'fileSize'=>'20',
			'logOrder'=>array('updateTime'),
			'logWeight'=>'',
			'cateAlias'=>'0',
			'logAlias'=>'0',
			'pageAlias'=>'0',
			'tagAlias'=>'0',
			'specialAlias'=>'0',
			'api_status'=>'0',
			'api_max_req'=>'',
			'wap_auto'=>'0',
			'wap_domain'=>'',
			'wap_template'=>'',
			'attImgWitch'=>'400',
			'attImgHeight'=>'400',
			'commentStatus'=>'0',
			'commentCheck'=>'0',
			'commentCN'=>'0',
			'commentVcode'=>'0',
			'commentSort'=>'new',
			'commentPage'=>'10',
			'commentInterval'=>'30',
		);
		$this->_sql_execute("INSERT INTO ".$data['tablepre']."config (`cname`,`cvalue`) VALUES ('webconfig','".json_encode($config)."'),('template','defaults'),('temp_defaults', '{\"layout\":\"right\",\"appWidth\":\"1000\",\"bgColor\":\"#f1f1f1\"}');");
		$this->_sql_execute("INSERT INTO ".$data['tablepre']."links (`sitename`,`sitedesc`,`siteurl`) VALUES ('RPCMS', 'RPCMS内容管理系统', 'http://www.rpcms.cn');");
		$data['baseUrl']=$App->baseUrl;
		$data['appPath']=ltrim($App->appPath, '/');
		$randStr=randStr(6);
		if($this->setConfig($data,$randStr)){
			\rp\Config::set(array(
				'db'=>array(
					'hostname'=>$data['dbhost'],
					'username'=>$data['dbuser'],
					'password'=>$data['dbpsw'],
					'database'=>$data['dbname'],
					'prefix'=>$data['tablepre'],
					'charset'=>'utf8',
				),
				'app_key' => 'rpcms'.$randStr,
			));
			$this->_sql_execute("INSERT INTO ".$data['tablepre']."user (`username`,`password`,`nickname`,`role`,`status`) VALUES ('".$data['username']."','".psw($data['userpsw'])."','".$data['username']."','admin','0')");
			Cache::update();
			$lock=@file_put_contents(CMSPATH .'/data/install.lock', 'installed');
			return json(array('code'=>200, 'msg'=>'success', 'data'=>$data['baseUrl'], 'errorTip'=>$errorTip));
		}
		return json(array('code'=>-1, 'msg'=>'config/default.php写入失败，请确保文件存在并拥有读写权限'));
	}
	
	private function setConfig($data,$randStr){
		$config="<?php
	return array(
		//数据库信息
		'db'=>array(
			'hostname'=>'".$data['dbhost']."',
			'username'=>'".$data['dbuser']."',
			'password'=>'".$data['dbpsw']."',
			'database'=>'".$data['dbname']."',
			'prefix'=>'".$data['tablepre']."',
			'charset'=>'utf8',
		),
		//cms安装目录，适用于子文件适用
		'app_default_path'       => '".$data['appPath']."',
		// 域名根，如：rpcms.com
		'domain_root'        => '',
		//二级域名绑定关系
		'domain_root_rules'        => array(),
		//默认跳转地址，当没有referer的时候
		'app_default_referer'    => '".$data['baseUrl']."',
		//数据加密key
		'app_key'                => 'rpcms".$randStr."',
		//默认module
		'default_module'         => 'index',
		//默认controller
		'default_controller'     => 'Index',
		//默认action
		'default_action'         => 'index',
		//禁止通过URL访问的module，多个用“,”隔开
		'deny_module'			 => '',
		//自定义后台地址，请勿和伪静态命名和二级域名重复，否则可能会被规则覆盖
		'diy_admin'        		 => '".($data['diyname'] == 'admin' ? '' : $data['diyname'])."',
		//url后缀
		'url_html_suffix'        => 'html',
		//是否缓存模板，当适用模板标签的时候必须开启
		'tpl_cache'     => true,
		//模板禁用函数
		'tpl_deny_func_list'     => 'echo,exit',
		//验证码
		'captha_style_width'     => 90,
		'captha_style_height'    => 30,
		//默认启用的hook，请勿修改
		'default_hook'=>array(
			'admin_left_menu'=>[],
			'admin_top_menu'=>[],
		),
	);";
		$configFile = SETTINGPATH.'/config/default.php';
		return @file_put_contents($configFile, $config);
	}
	
	private function _sql_execute($sql,$tablepre = '',$default = 'rp_') {
		$sqls = $this->_sql_split($sql,$tablepre,$default);
		if(is_array($sqls)){
			foreach($sqls as $sql){
				if(trim($sql) != ''){
					mysqli_query($this->links,$sql);
				}
			}
		}else{
			mysqli_query($this->links,$sqls);
		}
		return true;
	}
	private function _sql_split($sql,$tablepre = '',$default='') {
		$tablepre = !empty($tablepre) ? $tablepre : $default;
		$sql = str_replace('%pre%', $tablepre, $sql);
		$sql = str_replace("\r", "\n", $sql);
		$ret = array();
		$num = 0;
		$endRet=array();
		preg_match_all('/DELIMITER\s+([^\s]+)\s+(.*?)\s+DELIMITER ;/s', $sql, $matches, PREG_SET_ORDER);
		if(!empty($matches)){
			foreach($matches as $k=>$v){
				if(empty($v[2])) continue;
				$endRet[]=str_replace($v[1], ';', $v[2]);
			}
			$sql=preg_replace('/DELIMITER\s+([^\s]+)\s+(.*?)\s+DELIMITER ;/s', '', $sql);
		}
		$queriesarray = explode(";\n", trim($sql));
		unset($sql);
		foreach($queriesarray as $query){
			$ret[$num] = '';
			$queries = explode("\n", trim($query));
			$queries = array_filter($queries);
			foreach($queries as $query){
				$str1 = substr($query, 0, 1);
				if($str1 != '#' && $str1 != '-') $ret[$num] .= $query;
			}
			$num++;
		}
		return array_merge($ret, $endRet);
	}

	private function isWritable($file){
		$writeable=true;
		if(is_dir($file)){
			$file2=rtrim($file, '/').'/'.md5(mt_rand());
			if(($fp=@fopen($file2, 'ab')) === false){
				$writeable=false;
			}else{
				@unlink($file2);
				$writeable=true;
			}
		}elseif(!is_file($file) or ($fp=@fopen($file, 'ab'))===false){
			$writeable=false;
		}
		$fp && fclose($fp);
		return $writeable && is_readable($file);
	}
}
