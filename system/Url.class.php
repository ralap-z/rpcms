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
	
	public static function setUrl($str='index'){
		global $App;
		$domainRules = Config::get('domain_root_rules');
		$module=$App->getUrlModule();
		$url=$App->baseUrl . (0 !== strpos($str, '/') ? '/'.$module.'/'.$str : $str) .'.'. $App->pageExt;
		if(!empty($domainRules) && isset($domainRules[$App->subDomain])){
			$domainRulesPattern=$domainRules[$App->subDomain];
			$domainPath = array_values(array_filter(explode('/', $domainRulesPattern)));
			if(!empty($domainPath)){
				$url=str_replace(trim($domainRulesPattern,'/').'/','',$url);
			}
		}
		return $url;
	}
	
	public static function redirect($url){
		header("Location: ".$url);
		exit;
	}
	
	/*文章URL*/
	public static function logs($logId, $page=null){
		global $App;
		$logUrl = $App->baseUrl.'/post/';
		if(Config::get('webConfig.logAlias')){
			$res=Db::name('logs')->where(array('id'=>$logId))->field('alias')->find();
			$logUrl.= !empty($res['alias']) ? $res['alias'] : $logId;
		}else{
			$logUrl.= $logId;
		}
		$logUrl.= !empty($page) ? '_'.$page : '';
		$logUrl.= '.'.$App->pageExt;
		return $logUrl;
	}
	
	/*分类URL*/
	public static function cate($cateId, $page=null){
		global $App;
		$cate=Cache::read('category');
		if(!isset($cate[$cateId])){
			return $App->baseUrl;
		}
		$cateUrl = $App->baseUrl.'/category/';
		$cateUrl.= (Config::get('webConfig.cateAlias') && !empty($cate[$cateId]['alias'])) ? $cate[$cateId]['alias'] : $cateId;
		$cateUrl.= !empty($page) ? '_'.$page : '';
		$cateUrl.= '.'.$App->pageExt;
		return $cateUrl;
	}
	
	/*专题URL*/
	public static function special($specialId, $page=null){
		global $App;
		$special=Cache::read('special');
		if(!isset($special[$specialId])){
			return $App->baseUrl;
		}
		$specialUrl = $App->baseUrl.'/special/';
		$specialUrl.= (Config::get('webConfig.specialAlias') && !empty($special[$specialId]['alias'])) ? $special[$specialId]['alias'] : $specialId;
		$specialUrl.= !empty($page) ? '_'.$page : '';
		$specialUrl.= '.'.$App->pageExt;
		return $specialUrl;
	}
	
	/*单页URL*/
	public static function page($pageId){
		global $App;
		$pages=Cache::read('pages');
		if(!isset($pages[$pageId])){
			return $App->baseUrl;
		}
		$pageUrl = $App->baseUrl.'/html/';
		$pageUrl.= (Config::get('webConfig.pageAlias') && !empty($pages[$pageId]['alias'])) ? $pages[$pageId]['alias'] : $pageId;
		$pageUrl.='.'.$App->pageExt;
		return $pageUrl;
	}
	
	/*标签URL*/
	public static function tag($tagId, $page = null){
		global $App;
		$tages=Cache::read('tages');
		if(!isset($tages[$tagId])){
			return $App->baseUrl;
		}
		$tagUrl = $App->baseUrl.'/tag/';
		$tagUrl.= (Config::get('webConfig.tagAlias') && !empty($tages[$tagId]['alias'])) ? $tages[$tagId]['alias'] : urlencode($tages[$tagId]['tagName']);
		$tagUrl.= !empty($page) ? '_'.$page : '';
		$tagUrl.= '.'.$App->pageExt;
        return $tagUrl;
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
        return $isPath ? str_replace($App->baseUrl,$App->appPath,$url) : $url;
	}
	
	/*插件URL*/
	public static function plugin($name, $action=array(), $page=NULL){
		global $App;
		$pluginUrl = $App->baseUrl.'/plugin/'.strtolower($name);
		$pluginUrl.= !empty($action) ? '/'.join('/',$action) : '';
		$pluginUrl.= !empty($page) ? '_'.$page : '';
		$pluginUrl.= '.'.$App->pageExt;
        return $pluginUrl;
	}
	
	/*其他URL*/
	public static function other($name, $data=NULL, $page=NULL){
		global $App;
		$url = $App->baseUrl.'/';
		switch(strtolower($name)){
			case 'index':
				$url.='index';
				break;
			case 'author':
				$url.='author/'.$data;
				break;
			case 'date':
				$url.='date/'.$data;
				break;
			case 'captcha':
				$url.='captcha'.(!empty($data) ? '/'.$data : '');
				break;
			case 'comment':
				$url.=ltrim($App::server('REDIRECT_URL'),'/').(!empty($page) ? '?comment-page='.$page : '').$data;
				return $url;
				break;
			case 'search':
				return $url.='search/?q='.$data.(!empty($page) ? '&page='.$page : '');
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
		$url.=!empty($page) ? '_'.$page : '';
		$url.='.'.$App->pageExt;
        return $url;
	}
}