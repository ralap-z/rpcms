<?php
namespace rp\index;

use rp\Db;
use rp\Zip;

class Down extends base{

	public function __construct(){
		parent::__construct();
	}
	
	public function index(){
		$token=strip_tags(input('token'));
		if(!preg_match('/^[\w]+$/', $token)){
			return rpMsg('下载错误，附件错误或不存在');
		}
		$attr=Db::name('attachment')->where(array('token'=>$token))->field('logId,pageId,filename,filepath')->find();
		if(empty($token) || empty($attr)){
			return rpMsg('下载错误，附件错误或不存在');
		}
		$attrfile=CMSPATH . $attr['filepath'];
		if(!is_file($attrfile)){
			return rpMsg('下载错误，附件错误或不存在');
		}
		\rp\Hook::doHook('index_down',array($attr));
		$zip=new Zip($attrfile);
		$zip->down($attr['filename']);
	}
}
