<?php
namespace rp\index;

use rp\Url;
use rp\Db;

class Rss extends base{
	
	private $params;
	public function __construct($params){
		parent::__construct();
		$this->params=$params;
	}
	
	public function index(){
		$type=isset($this->params[1]) ? trim(strip_tags($this->params[1]),'/') : 'baidu';
		switch($type){
			
			case 'baidu':
			default:
				return $this->baidu();
		}
		exit;
	}
	
	private function getLogs(){
		return Db::name('logs')->where('status=0')->field('id,title,excerpt,password,upateTime,createTime')->order(array('upateTime'=>'desc','id'=>'desc'))->limit('0,5000')->select();
	}
	
	private function baidu(){
		header('Content-type: application/xml');
		echo '<?xml version="1.0" encoding="utf-8"?>
				<urlset>';
		foreach($this->getLogs() as $v){
			echo '<url>
				<loc>'.Url::logs($v['id']).'</loc>
				<lastmod>'.(!empty($v['upateTime']) ? $v['upateTime'] : $v['createTime']).'</lastmod>
			</url>';
		}
		echo '</urlset>';
	}
}
