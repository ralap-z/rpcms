<?php
namespace rp\api;

use rp\Config;
use rp\Hook;
use rp\Db;

class Base{
	
	protected $webConfig;
	protected static $user;
	protected $me_alias=array('index','post','search','author','category','html','tag','date','plugin','page','special');
	protected $me_tmpName=array('common','setting','password','index','list','page','search','detail','default','hook','special');
	
	public function __construct(){
		$this->webConfig=Config::get('webConfig');
		if(!isset($this->webConfig['api_status'])){
			$this->webConfig['api_status']=0;
		}
		if(!isset($this->webConfig['api_token_key'])){
			$this->webConfig['api_token_key']='';
		}
		$this->checkStatus();
		$this->checkthrottle($this->webConfig['api_max_req'],60);
		$this->getUser();
		Hook::doHook('api_begin');
	}
	
	protected function checkStatus(){
		if($this->webConfig['api_status'] != 1){
			$this->response('',503,'API 未启用！');
		}
	}
	
	protected function chechAuth($login=false){
		if($login && empty(self::$user)){
			$this->response('',401,'请先登录！');
		}
	}
	
	protected function checkthrottle($max = 60, $period = 60){
		//若使用限流功能，请先下载并启用filecache插件
		$max=intval($max);
		if(pluginCheck('filecache') && $max > 0){
			$filecache=pluginDo('filecache', 'index', 'connect' ,'apiData');
			$key='api-throttle:'.ip();
			$value = $filecache->get($key);
			if(!$value || (time() >= $value['expire'])){
				$value = array('hits' => 0, 'expire' => (time() + $period));
			}
			if($value['hits'] >= $max){
				$this->response('',410,'访问频繁，请稍后访问！');
			}
			$value['hits']++;
			$filecache->set($key, $value, ($value['expire'] - time()));
		}
	}
	
	protected function response($data, $code=200, $message='success'){
		$response=array(
			'code' => $code,
			'message' => $message,
		);
		if($code == 200){
			$response['data'] = $data;
		}
		return json($response);
	}
	
	protected function getOrder($orderField=array()){
		$sort=explode(',',input('sort'));
		$order=explode(',',input('order'));
		$orderData=array();
		$orderFieldKey=array_keys($orderField);
		foreach($sort as $k=>$v){
			if(in_array($v,$orderFieldKey)){
				$orderData[(!empty($orderField[$v]) ? $orderField[$v].'.' : '').$v]=(isset($order[$k]) && !empty($order[$k])) ? $order[$k] : 'desc';
			}
		}
		return $orderData;
	}
	
	private function getUser(){
		if(!empty(self::$user)) return;
		$user=session('MEUSER');
		$userData='';
		if(!empty($user)){
			$userData=Db::name('user')->where(array('id'=>intval($user['id'])))->find();
		}else if($token=$this->getToken()){
			$token=base64_decode($token);
			$tokenData=explode('|',$token);
			if(count($tokenData) != 2 || !preg_match('/^[\w]+$/', $tokenData[0])){
				return;
			}
			$userInfo=Db::name('user')->where(array('username'=>_decrypt($tokenData[0])))->find();
			if($this->verifyToken($userInfo,$tokenData[1])){
				$userData=$userInfo;
				unset($userInfo);
			}
		}
		self::$user=$userData;
	}
	
	protected function checkAlias($alias=''){
		if(!empty($alias)){
			if(!preg_match('/^[A-Za-z0-9\-]+$/u',$alias)){
				$this->response('',401,'别名错误，应由字母、数字、短横线组成！');
			}
			if(in_array($alias,$this->me_alias)){
				$this->response('',401,'别名重复，请更换别名！');
			}
		}
	}
	
	protected function checkTemplate($template='', $msg=''){
		if(!empty($template)){
			if(!preg_match('/^[A-Za-z0-9_\-]+$/u',$template)){
				$this->response('',401,'模板名称错误，应由字母、数字、下划线、短横线组成！');
			}
			if(in_array($template,$this->me_tmpName)){
				$this->response('',401,'该名称是系统保留名称，请更换'.$msg.'模板名称！');
			}
		}
	}
	
	protected function extendPost($post=array()){
		$extend=array();
		foreach($post as $key => $value){
			if(substr($key, 0, 7) == 'extend_'){
				$name = substr($key, 7);
				$extend[$name] = $value;
			}
		}
		return !empty($extend) ? addslashes(json_encode($extend)) : '';
	}
	
	protected function setToken($user){
		$appkey=Config::get('app_key');
		$hash=hash_hmac('sha256', $this->webConfig['api_token_key'].'-'.$user['id'].'-'.$appkey, $user['password']);
		return base64_encode(_encrypt($user['username']).'|'.$hash);
	}
	
	protected function thumb($data,$len=0){
		preg_match_all("|<img[^>]+src=\"([^>\"]+)\"?[^>]*>|is", $data, $img);
		$thumb=array();
		if(isset($img[1]) && !empty($img[1])){
			$max=!empty($len) ? min(count($img[1]),$len) : count($img[1]);
			for($i=0;$i<$max;$i++){
				$imgs=$img[1][$i];
				$newimg=dirname($imgs). '/thum-' .basename($imgs);
				$thumb[]=file_exists(CMSPATH .'/'. $newimg) ? $newimg : $imgs;
			}
		}
		return $thumb;
	}
	protected function pregReplaceImg($content,$prefix){
		$content = preg_replace_callback('/(<[img|IMG].*?src=[\'\"])([\s\S]*?)([\'\"])[\s\S]*?/i', function($match)use($prefix){
			if(strstr($match[2], 'http://') == false && strstr($match[2], 'https://') == false){
				return $match[1].$prefix.$match[2].$match[3];
			}else{
				return $match[1].$match[2].$match[3];
			}
		} , $content);
		return $content;
	}
	protected function checkPassword($postpwd,$password){
		return $postpwd == $password;
	}
	
	private function verifyToken($user, $token){
		if(empty($user)){
			return false;
		}
		$appkey=Config::get('app_key');
		$hash=hash_hmac('sha256', $this->webConfig['api_token_key'].'-'.$user['id'].'-'.$appkey, $user['password']);
		return $hash == $token ? true : false;
	}
	
	private function getToken(){
		$auth=input('SERVER.HTTP_AUTHORIZATION');
		$token=substr($auth, 6);
		if(empty($token) || substr($auth, 0, 6) !== 'Basic '){
			$token=input('token');
		}
		return $token;
	}
	
}
