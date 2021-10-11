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
use rp\Cache;

class Url{
	private static $httpCodes = array(
        100 => 'Continue',
        101 => 'Switching Protocols',
        102 => 'Processing',
        200 => 'OK',
        201 => 'Created',
        202 => 'Accepted',
        203 => 'Non-Authoritative Information',
        204 => 'No Content',
        205 => 'Reset Content',
        206 => 'Partial Content',
        207 => 'Multi-Status',
        300 => 'Multiple Choices',
        301 => 'Moved Permanently',
        302 => 'Found', // 1.1
        303 => 'See Other',
        304 => 'Not Modified',
        305 => 'Use Proxy',
        306 => 'Switch Proxy',
        307 => 'Temporary Redirect',
        400 => 'Bad Request',
        401 => 'Unauthorized',
        402 => 'Payment Required',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        406 => 'Not Acceptable',
        407 => 'Proxy Authentication Required',
        408 => 'Request Timeout',
        409 => 'Conflict',
        410 => 'Gone',
        411 => 'Length Required',
        412 => 'Precondition Failed',
        413 => 'Request Entity Too Large',
        414 => 'Request-URI Too Long',
        415 => 'Unsupported Media Type',
        416 => 'Requested Range Not Satisfiable',
        417 => 'Expectation Failed',
        451 => 'Unavailable For Legal Reasons',
        500 => 'Internal Server Error',
        501 => 'Not Implemented',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
        504 => 'Gateway Timeout',
        505 => 'HTTP Version Not Supported',
        506 => 'Variant Also Negotiates',
        507 => 'Insufficient Storage',
        508 => 'Loop Detected',
        509 => 'Bandwidth Limit Exceeded',
        510 => 'Not Extended',
    );
	
	public function __construct(){

	}
	
	public static function setCode($code=200){
		if(!empty($code) && isset(self::$httpCodes[$code])){
			header('HTTP/1.1 ' . $code . ' ' . self::$httpCodes[$code]);
		}
		return new self;
	}
	
	public static function setUrl($url='index', $data=array()){
		global $App;
		$module=$App->getUrlModule();
		$url=$modulePath=0 !== strpos($url, '/') ? '/'.$module.'/'.$url : $url;
		$modulePath=$modulePath == '/' ? $modulePath.'index/' : $modulePath;
		$rules=Route::getRules();
		$ruleGroup=[];
		if(!empty($rules)){
			$ruleGroup=array_column($rules, NULL, 'model');
		}
		$isRule=false;
		if(isset($ruleGroup[$url])){
			$patternUrl=$ruleGroup[$url]['patternUrl'];
			$param=$ruleGroup[$url]['param'];
			foreach($param as $k => $v){
				$keyVal='';
				if(isset($data[$v[1]]) && $data[$v[1]] !== '' && $data[$v[1]] !== NULL){
					$keyVal=$v[2].$data[$v[1]];
					unset($data[$v[1]]);
				}
				$patternUrl=str_replace(['<'.$v[1].'>','<'.$v[1].'?>'], $keyVal.'#', $patternUrl);
			}
			$patternUrl=explode('#',rtrim($patternUrl,'#'));
			if(in_array('',$patternUrl)){
				foreach(array_keys($patternUrl,'') as $pk=>$pv){
					$patternUrl[$pv]=$param[$pv][2];
				}
			}
			$url='/'.rtrim(join('',$patternUrl),'/');
			$isRule=true;
		}
		$domainRules = Config::get('domain_root_rules');
		$rootDomain = Config::get('domain_root');
		$httpHost=$App::server('HTTP_X_REAL_HOST') ? $App::server('HTTP_X_REAL_HOST') : $App::server('HTTP_HOST');
		if(empty($rootDomain)){
			$rootDomain=$httpHost;
			foreach($domainRules as $dk=>$dv){
				if(0 === stripos($httpHost, $dk)){
					$rootDomain=substr($httpHost, strlen($dk)+1);
					break;
				}
			}
		}
		foreach($domainRules as $dk=>$dv){
			if(1 === stripos($modulePath, $dv)){
				$rootDomain=$dk.'.'.$rootDomain;
				$url=!$isRule ? str_replace('/'.$dv, '' ,$url) : $url;
				break;
			}
		}
		$isAbs=$httpHost == $rootDomain ? false : true;
		$pageExt = in_array($url, ['/', '']) ? '' : '.'.$App->pageExt;
		$url=($isAbs ? $App::server('REQUEST_SCHEME').'://'.$rootDomain : '') . $App->appPath .$url.$pageExt;
		$data=array_filter($data);
		if(!empty($data)){
			$data=http_build_query($data);
			$url.='?'.$data;
		}
		return $url;
	}
	
	public static function redirect($url){
		header("Location: ".$url);
		exit;
	}
	
	/*文章URL*/
	public static function logs($logId, $page=null){
		if(Config::get('webConfig.logAlias')){
			$res=Db::name('logs')->where(array('id'=>$logId))->field('alias')->find();
			$logId= !empty($res['alias']) ? $res['alias'] : $logId;
		}
		return self::setUrl('/index/logs/detail',['id'=>$logId, 'page'=>$page]);
	}
	
	/*分类URL*/
	public static function cate($cateId, $page=null){
		$cate=Cache::read('category');
		if(!isset($cate[$cateId])){
			return '';
		}
		$cateId= (Config::get('webConfig.cateAlias') && !empty($cate[$cateId]['alias'])) ? $cate[$cateId]['alias'] : $cateId;
		return self::setUrl('/index/category/index',['id'=>$cateId, 'page'=>$page]);
	}
	
	/*专题URL*/
	public static function special($specialId, $page=null){
		$special=Cache::read('special');
		if(!isset($special[$specialId])){
			return '';
		}
		$specialId= (Config::get('webConfig.specialAlias') && !empty($special[$specialId]['alias'])) ? $special[$specialId]['alias'] : $specialId;
		return self::setUrl('/index/special/index',['id'=>$specialId, 'page'=>$page]);
	}
	
	/*单页URL*/
	public static function page($pageId){
		$pages=Cache::read('pages');
		if(!isset($pages[$pageId])){
			return '';
		}
		$pageId= (Config::get('webConfig.pageAlias') && !empty($pages[$pageId]['alias'])) ? $pages[$pageId]['alias'] : $pageId;
		return self::setUrl('/index/pages/index',['id'=>$pageId]);
	}
	
	/*标签URL*/
	public static function tag($tagId, $page = null){
		$tages=Cache::read('tages');
		if(!isset($tages[$tagId])){
			return '';
		}
		$tagId= (Config::get('webConfig.tagAlias') && !empty($tages[$tagId]['alias'])) ? $tages[$tagId]['alias'] : $tagId;
		return self::setUrl('/index/tags/index',['id'=>$tagId, 'page'=>$page]);
	}
	
	/*导航URL*/
	public static function nav($type, $typeId, $url, $isPath=false){
		global $App;
		switch($type){
			case 1:
			case 4:
				$url=$url;
				break;
			case 2:
				$url=self::cate($typeId);
				break;
			case 3:
				$url=self::page($typeId);
				break;
			default:
                $url = (strpos($url, 'http') === 0 ? '' : $App->baseUrl) . $url;
                break;
		}
        return $isPath ? str_replace($App->baseUrl, $App->appPath, $url) : $url;
	}
	
	/*插件URL*/
	public static function plugin($name, $action=array(), $page=NULL){
		$name=strtolower($name);
		$controller=!empty($action[0]) ? $action[0] : 'index';
		$action=!empty($action[1]) ? $action[1] : 'index';
		return self::setUrl('/index/plugin/run',['plugin'=>$name, 'controller'=>$controller, 'action'=>$action, 'page'=>$page]);
	}
	
	/*其他URL*/
	public static function other($name, $data=NULL, $page=NULL){
		global $App;
		switch(strtolower($name)){
			case 'index':
				return self::setUrl('/index/logs/index',['page'=>$page]);
			case 'author':
				return self::setUrl('/index/author/index', ['id'=>$data, 'page'=>$page]);
			case 'date':
				return self::setUrl('/index/logs/dates', ['date'=>$data, 'page'=>$page]);
			case 'captcha':
				return self::setUrl('/index/base/captcha', ['type'=>$data]);
			case 'comment':
				return ltrim($App::server('REDIRECT_URL'),'/').(!empty($page) ? '?comment-page='.$page : '').$data;
			case 'search':
				return self::setUrl('/index/logs/search', ['q'=>$data, 'page'=>$page]);
			case 'logs':
				return self::logs($data,$page);
			case 'cate':
				return self::cate($data,$page);
			case 'special':
				return self::special($data,$page);
			case 'page':
				return self::page($data,$page);
			case 'tages':
				return self::tag($data,$page);
		}
	}
}