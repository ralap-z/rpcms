<?php
// +----------------------------------------------------------------------
// | RPCMS
// +----------------------------------------------------------------------
// | Copyright (c) 2019 http://www.rpcms.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: ralap <www.rpcms.cn>
// +----------------------------------------------------------------------

namespace rp;

use rp\Db;

class Cache{
	
	protected static $instance;
	protected static $cacheData=array();
	private $prefix='';
	
	public function __construct(){
		$options=Config::get('db');
		$this->prefix=$options["prefix"];
	}
	
	public static function instance(){
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }
	
	public static function set($cacheName, $cacheData, $expire=0){
		$cache=self::instance();
		return $cache->cacheWrite($cacheData, $cacheName, $expire);
	}
	
	public static function read($name){
		$cache=self::instance();
		if(isset(self::$cacheData[$name])){
			$data=self::$cacheData[$name];
			if(0 != $data['expire'] && time() > $data['expire']){
				@unlink($data['file']);
				return;
			}
			return $data['data'];
		}else{
			$cachefile=$cache->getCacheKey($name);
			if(is_file($cachefile) && filesize($cachefile) > 0){
				$fp=fopen($cachefile, 'r');
				$data=fread($fp, filesize($cachefile));
				fclose($fp);
				clearstatcache();
				$expire=(int)substr($data, 13, 12);
				if(0 != $expire && time() > $expire){
					@unlink($cachefile);
					return;
				}
				$dataArr=json_decode(substr($data, 27),true);
				$data=$dataArr === null ? $data : $dataArr;
				unset($dataArr);
			}elseif(method_exists($cache, 'me_' . $name)){
				$data=call_user_func(array($cache, 'me_' . $name));
				$expire=0;
			}else{
				return;
			}
			self::$cacheData[$name]=[
				'expire'=>$expire,
				'file'=>$cachefile,
				'data'=>$data
			];
			unset($data);
			return self::$cacheData[$name]['data'];
		}
    }
	
	public static function update($name=''){
		$cache=self::instance();
		if(!empty($name) && is_string($name)){
            if(method_exists($cache, 'me_' . $name)){
                call_user_func(array($cache, 'me_' . $name));
            }
            return true;
        }
		if(is_array($name)){
			foreach($name as $v){
                if(method_exists($cache, 'me_' . $v)){
                    call_user_func(array($cache, 'me_' . $v));
                }
            }
            return true;
		}
		if(empty($name)){
            $nameArr = get_class_methods($cache);
            foreach ($nameArr as $method) {
                if(preg_match('/^me_/', $method)){
                    call_user_func(array($cache, $method));
                }
            }
			return true;
        }
	}
	
	/*系统配置缓存*/
	private function me_option(){
		$option=Db::name('config')->where('cname = "webconfig"')->field('cvalue')->find();
		$data=json_decode($option['cvalue'], true);
        $this->cacheWrite($data, 'option');
		return $data;
	}
	
	/*用户缓存*/
	private function me_user(){
		$user=Db::name('user')->alias('a')->join(array(
			array('(select authorId,count(*) as logNum FROM '.$this->prefix.'logs where status =0 group by authorId) b','a.id=b.authorId','left'),
			array('(select authorId,count(*) as pageNum FROM '.$this->prefix.'pages where status =0 group by authorId) c','a.id=c.authorId','left'),
			array('(select authorId,count(*) as commentNum FROM '.$this->prefix.'comment where status =0 group by authorId) d','a.id=d.authorId','left'),
			array('(select userId,count(*) as commentPostNum FROM '.$this->prefix.'comment where status =0 group by userId) e','a.id=e.userId','left'),
		))->where('a.status = 0')->field('a.id,a.username,a.nickname,a.role,a.status,IFNULL(b.logNum,0) as logNum,IFNULL(c.pageNum,0) as pageNum,IFNULL(d.commentNum,0) as commentNum,IFNULL(e.commentPostNum,0) as commentPostNum')->select();
		$user=array_column($user,NULL,'id');
		$this->cacheWrite($user, 'user');
		return $user;
	}
	
	/*导航缓存*/
	private function me_nav() {
        $nav_cache = array();
		$nav=Db::name('nav')->where('status=0')->order(array('topId'=>'asc','sort'=>'ASC'))->select();
		foreach($nav as $k=>$v){
			$v['url']=Url::nav($v['types'],$v['typeId'],$v['url'],true);
			if($v['topId'] == 0){
				$nav_cache[$v['id']]=$v;
				$nav_cache[$v['id']]['children']=array();
			}elseif(isset($nav_cache[$v['topId']])){
				$nav_cache[$v['topId']]['children'][] = $v;
			}
		}
        $this->cacheWrite($nav_cache, 'nav');
		return $nav_cache;
    }
	
	/*统计缓存*/
	private function me_total(){
		$log=Db::name('logs')->field('count(*) as logNum,authorId,status')->group('status,authorId')->select();
		$pages=Db::name('pages')->field('count(*) as pageNum,authorId')->group('authorId')->select();
		$comment=Db::name('comment')->field('count(*) as commentNum,authorId,status')->group('authorId,status')->select();
		$total=array(
			'logNum'=>0,
			'logOk'=>0,
			'logExa'=>0,
			'logDraft'=>0,
			'logLower'=>0,
			'pageNum'=>0,
			'commentNum'=>0,
			'commentOk'=>0,
			'commentExa'=>0,
		);
		foreach($log as $lk=>$lv){
			$total['logNum']+=$lv['logNum'];
			$total['logOk']+=$lv['status'] == 0 ? $lv['logNum'] : 0;
			$total['logExa']+=$lv['status'] == 1 ? $lv['logNum'] : 0;
			$total['logDraft']+=$lv['status'] == 2 ? $lv['logNum'] : 0;
			$total['logLower']+=$lv['status'] == -1 ? $lv['logNum'] : 0;
			if(!isset($total['logU'.$lv['authorId']])){
				$total['logU'.$lv['authorId']]=array(
					'logNum'=>0,
					'logOk'=>0,
					'logExa'=>0,
					'logDraft'=>0,
					'logLower'=>0,
				);
			}
			$total['logU'.$lv['authorId']]['logNum']+=$lv['logNum'];
			$total['logU'.$lv['authorId']]['logOk']+=$lv['status'] == 0 ? $lv['logNum'] : 0;
			$total['logU'.$lv['authorId']]['logExa']+=$lv['status'] == 1 ? $lv['logNum'] : 0;
			$total['logU'.$lv['authorId']]['logDraft']+=$lv['status'] == 2 ? $lv['logNum'] : 0;
			$total['logU'.$lv['authorId']]['logLower']+=$lv['status'] == -1 ? $lv['logNum'] : 0;
		}
		foreach($pages as $pk=>$pv){
			$total['pageNum']+=$pv['pageNum'];
			if(!isset($total['pageU'.$pv['authorId']])){
				$total['pageU'.$pv['authorId']]=array(
					'pageNum'=>0,
				);
			}
			$total['pageU'.$pv['authorId']]['pageNum']+=$pv['pageNum'];
		}
		foreach($comment as $ck=>$cv){
			$total['commentNum']+=$cv['commentNum'];
			$total['commentOk']+=$cv['status'] == 0 ? $cv['commentNum'] : 0;
			$total['commentExa']+=$cv['status'] == 1 ? $cv['commentNum'] : 0;
			if(!isset($total['commentU'.$cv['authorId']])){
				$total['commentU'.$cv['authorId']]=array(
					'commentNum'=>0,
					'commentOk'=>0,
					'commentExa'=>0,
				);
			}
			$total['commentU'.$cv['authorId']]['commentNum']+=$cv['commentNum'];
			$total['commentU'.$cv['authorId']]['commentOk']+=$cv['status'] == 0 ? $cv['commentNum'] : 0;
			$total['commentU'.$cv['authorId']]['commentExa']+=$cv['status'] == 1 ? $cv['commentNum'] : 0;
		}
		$this->cacheWrite($total, 'total');
		return $total;
	}
	
	/*文章归档缓存*/
	private function me_logRecord(){
		$log=Db::name('logs')->field('count(*) as logNum,DATE_FORMAT(createTime,\'%Y-%m\') as ym')->group('ym')->order('ym','desc')->select();
		$logRecord=array();
		foreach($log as $k=>$v){
			$logRecord[]=array(
				'record'=>date('Y年m月',strtotime($v['ym'])),
				'date'=>str_replace('-','',$v['ym']),
				'logNum'=>$v['logNum'],
			);
		}
		$this->cacheWrite($logRecord, 'logRecord');
		return $logRecord;
	}
	
	/*分类缓存*/
	private function me_category() {
        $cate_cache = array();
		$category=Db::name('category')->alias('a')->join('(select cateId,count(*) as logNum FROM '.$this->prefix.'logs where status =0 group by cateId) b','a.id=b.cateId','left')->order(array('a.topId'=>'asc','a.sort'=>'ASC'))->field('a.*,IFNULL(b.logNum,0) as logNum')->select();
		foreach($category as $k=>$v){
			$cate_cache[$v['id']]=$v;
			$cate_cache[$v['id']]['children']=array();
			if($v['topId'] != 0 && isset($cate_cache[$v['topId']])){
				$cate_cache[$v['topId']]['children'][] = $v['id'];
			}
		}
        $this->cacheWrite($cate_cache, 'category');
		return $cate_cache;
    }
	
	/*专题缓存*/
	private function me_special() {
		$special=Db::name('special')->alias('a')->join('(select specialId,count(*) as logNum FROM '.$this->prefix.'logs where status =0 group by specialId) b','a.id=b.specialId','left')->field('a.*,IFNULL(b.logNum,0) as logNum')->select();
		$special = array_column($special,NULL,'id');
        $this->cacheWrite($special, 'special');
		return $special;
    }
	
	/*友情链接缓存*/
	private function me_links() {
		$links=Db::name('links')->where('status =0')->field('sitename,sitedesc,siteurl')->order('sort','asc')->select();
        $this->cacheWrite($links, 'links');
		return $links;
    }
	
	/*tag标签缓存*/
	private function me_tages(){
		$allTag=Db::name('logs')->alias('a')->join('tages as b','find_in_set(b.id,a.tages)')->where('a.status =0 and a.tages != ""')->field('b.id,COUNT(*) as num')->group('b.id')->select();
		$allTag=array_column($allTag,'num','id');
		$tages=Db::name('tages')->select();
		foreach($tages as $k=>$v){
			$tages[$k]['logNum']=isset($allTag[$v['id']]) ? $allTag[$v['id']] : 0;
		}
		$tages=array_column($tages,NULL,'id');
		$this->cacheWrite($tages, 'tages');
		return $tages;
	}
	
	/*单页主要信息缓存*/
	private function me_pages(){
		$pages=Db::name('pages')->where('status =0')->field('id,title,alias,seo_key,seo_desc,password,authorId,comnum,template,createTime,extend,isRemark')->select();
		$pages=array_column($pages,NULL,'id');
		$this->cacheWrite($pages, 'pages');
		return $pages;
	}

	private function me_template(){
		$template=array('name'=>'defaults','config'=>array());
		$temp=Db::name('config')->where('cname = "template"')->field('cvalue')->find();
		if(empty($temp)){
			$template['name']='defaults';
		}else{
			$template['name']=$temp['cvalue'];
		}
		if($cfg=Db::name('config')->where('cname = "temp_'.$template['name'].'"')->field('cvalue')->find()){
			$template['config']=json_decode($cfg['cvalue'],true);
		}
		$this->cacheWrite($template, 'template');
		return $template;
	}
	
	private function me_waptemplate(){
		$option=Db::name('config')->where('cname = "webconfig"')->field('cvalue')->find();
		$optionData=json_decode($option['cvalue'],true);
		$template=array('name'=>$optionData['wap_template'],'config'=>array());
		if($cfg=Db::name('config')->where('cname = "temp_'.$template['name'].'"')->field('cvalue')->find()){
			$template['config']=json_decode($cfg['cvalue'],true);
		}
		$this->cacheWrite($template, 'waptemplate');
		return $template;
	}
	
    private function cacheWrite($cacheData, $cacheName, $expire=0){
		$cachefile=$this->getCacheKey($cacheName);
		$cacheDir=dirname($cachefile);
		if(!is_dir($cacheDir)){
			mkdir($cacheDir, 0755, true);
		}
		if(!empty($expire)){
			$expire=$expire < 0 ? 0 : $expire;
			$expire=time() + $expire;
		}
		$cacheData=json_encode($cacheData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
		$cacheData="<?php exit;//".sprintf('%012d', $expire)."?>\n".$cacheData;
		self::$cacheData[$cacheName]= null;
		$res=@file_put_contents($cachefile,$cacheData);
		if($res){
			clearstatcache();
			return true;
		}
		return false;
    }
	
	private function getCacheKey($name){
		$name=md5($name);
		$name=substr($name, 0, 2). DIRECTORY_SEPARATOR .substr($name, 2);
		return CMSPATH . '/data/cache/'.$name.'.php';
	}
	
}