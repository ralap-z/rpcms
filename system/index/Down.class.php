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
		$attr=Db::name('attachment')->where(array('token'=>$token))->field('logId,pageId,filename,filepath')->find();
		if(empty($token) || empty($attr)){
			return rpMsg('下载错误，附件错误或不存在');
		}
		$attrfile=CMSPATH . $attr['filepath'];
		if(!file_exists($attrfile)){
			return rpMsg('下载错误，附件错误或不存在');
		}
		\rp\Hook::doHook('index_down',$attr);
		$zip=new Zip($attrfile);
		$zip->down($attr['filename']);
	}
}
