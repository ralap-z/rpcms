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
		$type=isset($this->params['type']) ? trim(strip_tags($this->params['type']),'/') : 'baidu';
		switch($type){
			
			case 'baidu':
			default:
				return $this->baidu();
		}
		exit;
	}
	
	private function getLogs(){
		return Db::name('logs')->where('status=0')->field('id,title,upateTime,createTime')->order(array('upateTime'=>'desc','id'=>'desc'))->limit('0,5000')->select();
	}
	
	private function getPages(){
		return Db::name('pages')->where('status=0')->field('id,title,createTime')->order(array('createTime'=>'desc','id'=>'desc'))->select();
	}
	
	private function getCates(){
		return Db::name('category')->field('id,cate_name')->order(array('id'=>'desc'))->select();
	}
	
	private function getLastTime(){
		$logTime=Db::name('logs')->where('status=0')->order(array('upateTime'=>'desc'))->field('upateTime')->find();
		$pageTime=Db::name('pages')->where('status=0')->order(array('createTime'=>'desc'))->field('createTime')->find();
		return max($logTime['upateTime'],$pageTime['createTime']);
	}
	
	private function baidu(){
		header('Content-type: application/xml');
		echo '<?xml version="1.0" encoding="utf-8"?>
				<urlset>
					<url>
						<loc>'.$this->App->baseUrl.'</loc>
						<lastmod>'.$this->getLastTime().'</lastmod>
						<changefreq>always</changefreq>
						<priority>1.0</priority>
					</url>';
		foreach($this->getLogs() as $v){
			echo '<url>
				<loc>'.Url::logs($v['id']).'</loc>
				<lastmod>'.(!empty($v['upateTime']) ? $v['upateTime'] : $v['createTime']).'</lastmod>
				<changefreq>weekly</changefreq>
				<priority>0.6</priority>
			</url>';
		}
		foreach($this->getPages() as $v){
			echo '<url>
				<loc>'.Url::page($v['id']).'</loc>
				<lastmod>'.$v['createTime'].'</lastmod>
				<changefreq>weekly</changefreq>
				<priority>0.6</priority>
			</url>';
		}
		foreach($this->getCates() as $v){
			echo '<url>
				<loc>'.Url::cate($v['id']).'</loc>
				<changefreq>weekly</changefreq>
				<priority>0.4</priority>
			</url>';
		}
		echo '</urlset>';
	}
}
