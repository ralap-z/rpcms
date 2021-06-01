<?php
namespace rp\api;

use rp\Cache;
use rp\Db;
use rp\Url;
use rp\Hook;
use rp\Config;

class Index extends Base{
	
	public function __construct(){
		parent::__construct();
	}
	
	public function getTotal(){
		$this->response(Cache::read('total'));
	}
	
	public function getSetting(){
		$this->chechAuth(true);
		if(self::$user['role'] != 'admin'){
			$this->response('',403,'无权限！');
		}
		$this->response(Cache::read('option'));
	}
	
	public function getLink(){
		$this->response(Cache::read('links'));
	}
	
	public function getTempConfig(){
		$this->chechAuth(true);
		if(self::$user['role'] != 'admin'){
			$this->response('',403,'无权限！');
		}
		$type=intval(input('type')) ? intval(input('type')) : 'pc';
		$waptemplate=Cache::read('waptemplate');
		$template=$type == 'wap' && !empty($waptemplate['name']) ? $waptemplate : Cache::read('template');
		$this->response($template['config']);
	}
	
	public function upload(){
		$file=isset($_FILES['files']) ? $_FILES['files'] : '';
		$logid=intval(input('logid')) ? intval(input('logid')) : '';
		if(empty($logid)){
			return json(array('code'=>-1,'msg'=>'数据错误，ID为空'));
		}
		$res=uploadFiles($file,$logid);
		if($res['code'] == 200){
			return json(array('code'=>200, 'msg'=>'success'));
		}else{
			return json(array('code'=>-1, 'msg'=>$res['msg']));
		}
	}
	
	
}
