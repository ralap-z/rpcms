<?php
namespace rp\index;

use rp\Config;
use rp\Cache;
use rp\View;
use rp\Db;
use rp\Url;
use rp\Hook;

class Base{
	public $member;
	public $App;
	public $webConfig;
	public $tempUrl;
	public static $user;
	public $template=array();
	public $param; //验证码参数
	
	public function __construct($param=''){
		global $App;
		$this->param=$param;
		$this->App=$App;
		$this->webConfig=Config::get('webConfig');
		$waptemplate=Cache::read('waptemplate');
		if(isMobile() && !empty($waptemplate['name'])){
			$this->template=$waptemplate;
		}else{
			$this->template=Cache::read('template');
		}
		$this->tempUrl=str_replace(CMSPATH, $this->App->appPath, TMPPATH) . '/index/' . $this->template['name'];
		$this->getUser();
		$this->getDefault();
		$App->indexTemp=$this->template['name'];
		$tempCommon=TMPPATH . '/index/'.$this->template['name'].'/common.php';
		if(file_exists($tempCommon)){
			include_once $tempCommon;
		}
		$this->checkAppStatus();
	}
	
	public function getUser(){	
		if(!empty(self::$user)) return;
		$user=session('MEUSER');
		if(!empty($user)){
			$userData=Db::name('user')->where(array('id'=>intval($user['id']),'role'=>array('in',"'admin','member'")))->field('id,username,phone,email,nickname,role,isCheck,status')->find();
			$userData['home']='';
		}else{
			$userData=array(
				'nickname'=>cookie('comment_cookie_name') ? cookie('comment_cookie_name') : '访客',
				'email'=>cookie('comment_cookie_email') ? cookie('comment_cookie_email') : '',
				'home'=>cookie('comment_cookie_home') ? cookie('comment_cookie_home') : '',
			);
		}
		self::$user=$userData;
	}
	
	public function assign($name,$value){
		return View::assign($name,$value);
	}
	
	public function display($tmp){
		$tmp='/'.$this->template['name'] . $tmp;
		return View::display($tmp);
	}
	
	public function captcha(){
		$id=isset($this->param['type']) ? trim(strip_tags($this->param['type']),'/') : '';
		(new \rp\Captcha())->outImg($id);
	}
	
	public function getLogOrder($orderAdd=array()){
		$order=array();
		switch($this->webConfig['logOrder']){
			case 'updateTime':
				$order['a.upateTime']='desc';
				break;
			case 'weight':
				$logWeight=explode(PHP_EOL,$this->webConfig['logWeight']);
				$logWeight=array_map(function($v){
					list($sk, $sv)=explode('=',(!empty($v) ? $v : '='));
					return array($sk=>$sv);
				},$logWeight);
				$logWeight=array_reduce($logWeight, 'array_merge', array());
				$orderStr=array();
				$orderStr[]=isset($logWeight['views']) ? 'a.views*'.$logWeight['views'] : '';
				$orderStr[]=isset($logWeight['comnum']) ? 'a.comnum*'.$logWeight['comnum'] : '';
				$orderStr[]=isset($logWeight['upnum']) ? 'a.upnum*'.$logWeight['upnum'] : '';
				$orderStr=array_filter($orderStr);
				if(!empty($orderStr)){
					$order['('.join('+',$orderStr).')']='desc';
				}
			break;
		}
		$order=array_merge($orderAdd, $order);
		Hook::doHook('index_logs_order',array(&$order));
		return $order;
	}
	
	protected function setKeywords($keyword=''){
		if(!empty($keyword)){
			$key=explode(',',$keyword);
			$keywords=join(',',array_filter($key));
		}else{
			$keywords=$this->webConfig['keyword'];
		}
		View::assign('keywords',$keywords);
	}
	
	protected function setDescription($desc='',$firstAppned=false){
		View::assign('description',!empty($desc) ? ($firstAppned ? $desc.'，'.$this->webConfig['description'] : $desc) : $this->webConfig['description']);
	}
	
	/*
	*获取公共配置
	*/
	private function getDefault(){
		$this->webConfig['totalCode']=stripslashes($this->webConfig['totalCode']);
		View::assign('host',$this->App->baseUrl);
		View::assign('webConfig',$this->webConfig);
		View::assign('tempConfig',$this->template['config']);
		View::assign('tempUrl',$this->tempUrl);
		View::assign('user',self::$user);
		defined('TEMPURL') or define('TEMPURL', $this->tempUrl);
	}
	
	private function checkAppStatus(){
		if($this->webConfig['webStatus'] == 1){
			echo '<!doctype html><html><head>
					<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
					<title>网站关闭声明-'.$this->webConfig['webName'].'</title>
					<meta name="keywords" content="'.$this->webConfig['keyword'].'">
					<meta name="description" content="'.$this->webConfig['description'].'">
					<meta name="viewport" content="width=device-width, initial-scale=1.0">
					<style>
						*{margin: 0;padding: 0;}
						body{height: 100%;overflow: hidden;background: #f1f1f1;}
						.closeBox{width: 600px;margin: -200px 0 0 -300px;border-radius: 4px;padding: 30px 0;text-align: center;position: absolute;top: 50%;left: 50%;z-index: 100;}
						.closeBox h3{font-size: 24px;font-weight: 700;color: #333;}
						.closeText{margin-top: 20px;padding: 15px;border: 1px solid #ccc;border-radius: 3px;text-align: left;}
					</style>
				</head>
				<body>
					<div class="closeBox">
						<h3>网站关闭/维护，敬请开启！</h3>
						<div class="closeText">'.$this->webConfig['closeText'].'</div>
					</div>
				</body>
				</html>';
			exit;
		}
	}
	
	protected function checkPassword($postpwd,$cookiepwd,$password,$cookieName){
		$pwd = !empty($cookiepwd) ? _decrypt($cookiepwd) : $postpwd . ip();
		if($pwd !== $password . ip()){
			$tempPswDir=TMPPATH . '/index/'.$this->template['name'].'/password.php';
			$tempPswFile='/'.$this->template['name'].'/password';
			if(file_exists($tempPswDir)){
				echo View::display($tempPswFile);
			}else{
				echo '<!doctype html><html><head>
					<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
					<title>'.$GLOBALS['title'].'访问授-'.$this->webConfig['webName'].'</title>
					<meta name="keywords" content="'.$this->webConfig['keyword'].'">
					<meta name="description" content="'.$this->webConfig['description'].'">
					<meta name="viewport" content="width=device-width, initial-scale=1.0">
					<style>
						*{margin: 0;padding: 0;}
						body{height: 100%;overflow: hidden;background: #f1f1f1;}
						.passport{width: 30%;max-width: 600px;min-width:270px;margin: -160px 0 0 -15%;border-radius: 4px;padding: 30px 0;text-align: center;position: absolute;top: 50%;left: 50%;z-index: 100;}
						.passport h3{font-size: 24px;font-weight: 700;color: #333;}
						.passport form{margin: 20px auto;display: block;width: 100%;border-radius: 6px;position: relative;}
						.passport form .text{width: 90%;display: block;height: 48px;padding: 0;border-radius: 100px 0 0 100px;font-size: 16px;color: #555;background: #fff;text-indent: 52px;border: none;outline: none;}
						.passport form .btn{color: #fff;position: absolute;right: 0;top: 0;width: 18%;display: inline-block;font-size: 16px;text-align: center;cursor: pointer;height: 48px;line-height: 48px;border-radius: 100px;background: #19afdc;border: none;outline: none;}
						.passport p{font-size: 14px;color: #f90;}
						@media only screen and (max-width: 959px){
							.passport{margin: -13rem 0 0 -48%;width: 96%;}
							.passport form .btn{width:25%;}
						}
					</style>
				</head>
				<body>
					<div class="passport">
						<h3>请输入访问密码</h3>
						<form action="" method="post">
							<input type="password" class="text" name="pagepwd">
							<input type="submit" class="btn" value="确定">
						</form>
						<p>！《'.$GLOBALS['title'].'》需要密码才能查看，请输入访问密码！</p>
					</div>
				</body>
				</html>';
			}
			if($cookiepwd){
                cookie('rpcms_'.$cookieName, NULL, -31536000);
            }
			exit;
		}else{
			empty($cookiepwd) && cookie('rpcms_'.$cookieName,_encrypt($password . ip()),24*2600);
			$app=$this->App;
			$referer=$app::server('HTTP_REFERER');
			!empty($postpwd) && redirect($referer);
		}
	}
}
