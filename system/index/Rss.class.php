<?php
namespace rp\index;

use rp\Url;
use rp\Db;

class Rss extends base{
	
	private $params;
	private $page;
	private $num=0;
	public function __construct($params){
		parent::__construct();
		$this->params=$params;
		$this->page=!empty($this->params['page']) ? $this->params['page'] : 1;
		$this->page < 0 && $this->page=0;
		$this->page > 100 && $this->page=100;
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
		$limitStart=0;
		$limitEnd=5000;
		if($this->page == 1){
			$limitEnd=$limitEnd - $this->num;
		}else{
			$limitStart=$limitEnd - count($this->getCates()) - count($this->getPages()) - 1;
		}
		$step=$this->page-2;
		$step < 0 && $step=0;
		return Db::name('logs')->where('status=0')->field('id,title,updateTime,createTime')->order(array('updateTime'=>'desc'))->limit($step*$limitEnd+$limitStart.','.$limitEnd)->select();
	}
	
	private function getPages(){
		return Db::name('pages')->where('status=0')->field('id,title,createTime')->order(array('createTime'=>'desc'))->select();
	}
	
	private function getCates(){
		return Db::name('category')->field('id,cate_name')->order(array('id'=>'desc'))->select();
	}
	
	private function getLastTime(){
		$logTime=Db::name('logs')->where('status=0')->order(array('updateTime'=>'desc'))->field('updateTime')->find();
		$pageTime=Db::name('pages')->where('status=0')->order(array('createTime'=>'desc'))->field('createTime')->find();
		$nowTime=date('Y-m-d H:i:s');
		$logTime=!empty($logTime) ? $logTime : ['updateTime'=>$nowTime];
		$pageTime=!empty($pageTime) ? $pageTime : ['createTime'=>$nowTime];
		return max($logTime['updateTime'],$pageTime['createTime']);
	}
	
	private function baidu(){
		header('Content-type: application/xml');
		echo '<?xml version="1.0" encoding="utf-8"?>
				<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
		if($this->page == 1){
			echo '<url>
						<loc>'.$this->App->baseUrl.'</loc>
						<lastmod>'.date('Y-m-d', strtotime($this->getLastTime())).'</lastmod>
						<changefreq>always</changefreq>
						<priority>1.0</priority>
					</url>';
			$this->num++;
			foreach($this->getCates() as $v){
				$this->num++;
				echo '<url>
					<loc>'.$this->App->baseUrl.Url::cate($v['id']).'</loc>
					<changefreq>weekly</changefreq>
					<priority>0.4</priority>
				</url>';
			}
			foreach($this->getPages() as $v){
				$this->num++;
				echo '<url>
					<loc>'.$this->App->baseUrl.Url::page($v['id']).'</loc>
					<lastmod>'.date('Y-m-d', strtotime($v['createTime'])).'</lastmod>
					<changefreq>weekly</changefreq>
					<priority>0.6</priority>
				</url>';
			}
		}
		foreach($this->getLogs() as $v){
			echo '<url>
				<loc>'.$this->App->baseUrl.Url::logs($v['id']).'</loc>
				<lastmod>'.date('Y-m-d', strtotime(!empty($v['updateTime']) ? $v['updateTime'] : $v['createTime'])).'</lastmod>
				<changefreq>weekly</changefreq>
				<priority>0.6</priority>
			</url>';
		}
		echo '</urlset>';
	}
}
