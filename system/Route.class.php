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

class Route{
	private static $rules = array();
	private static $subDomain='www';
	
	public function __construct(){

	}
	
	public static function rules($rules = ''){
        if(is_array($rules)){
            self::$rules = $rules;
        }
    }
	
	public static function check($path){
		$model='';
		$params=array();
		foreach(self::$rules as $v){
			if(isset($v['pattern']) && !empty($v['pattern']) && preg_match($v['pattern'], $path, $matches)){
				$model = $v['model'];
				$params = $matches;
				break;
			}
		}
		return array('model'=>$model,'params'=>$params);
	}
	
	public static function subDomain($subDomain){
		self::$subDomain=$subDomain;
	}
	
	/*
	*根据规则生成正则   预留功能
	*@param $url #host#category/#alisa#(_#page#)
	*/
	public static function makeReg($url){
		$url = str_replace('#host#', '^', $url);
		$url = str_replace('.', '\\.', $url);
		$url = str_replace(array('#id#'), '(\d+)', $url);
		$url = str_replace(array('#alisa#'), '(\w+)', $url);
		$url = str_replace(array('#date#'), '(\d{6,8})', $url);
		$url = str_replace(array('#page#'), '(\d+)', $url);
		return '|'.$url.'?/?$|';
	}
}