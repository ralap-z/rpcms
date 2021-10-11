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
			foreach($rules as $k=>$v){
				self::$rules[$v['model']]=self::buildRule($v);
			}
        }
    }
	
	public static function check($path){
		$model='';
		$params=array();
		$rules=self::checkDomain();
		foreach($rules as $k=>$v){
			if(!empty($v['pattern']) && $matches = self::match($path, $v['pattern'])){
				$model = $v['model'];
				if(!empty($v['param'])){
					$param=array_column($v['param'],1,0);
					$params=array_combine(array_intersect_key($param, $matches), array_intersect_key($matches, $param));
				}
				break;
			}
		}
		return array('model'=>$model,'params'=>$params);
	}
	
	public static function subDomain($subDomain){
		self::$subDomain=$subDomain;
	}
	
	public static function getRules(){
		return self::$rules;
	}
	
	public static function checkDomain(){
		$domain=(self::$subDomain == '' || self::$subDomain == 'www') ? '' : self::$subDomain;
		$domainRules = Config::get('domain_root_rules');
		if(!empty($domain) && isset($domainRules[$domain])){
			$domainModule=$domainRules[$domain];
			return array_filter(self::$rules,function($v)use($domainModule){return $v['domain'] == $domainModule;});
		}
		return self::$rules;
	}
	
	protected static function match($path, $pattern){
		preg_match('#^'.$pattern.'$#', $path, $matches);
		if(empty($matches) || empty($matches[0])){
			return false;
		}
		return $matches;
	}
	
	protected static function buildRule($rule){
		$modelArr=explode('/',$rule['model']);
		$ruleArr=explode('/',$rule['pattern']);
		$nameKey=0;
		$name=[];
		$urlparam=[];
		foreach($ruleArr as $rk=>&$rv){
			$urlparamStr=[];
			if(false !== strpos($rv, '<') && preg_match_all('/([A-Za-z0-9]+)?<(\w+([\?\#\@\~\.\,\\\|_-]?)+)>/', $rv, $matches)){
				$replace=[];
				foreach($matches[2] as $mk=>$mv){
					$nameKey++;
					$optional=false;
					if(strpos($mv, '?')){
						$optional=true;
						$mv= substr($mv, 0, -1);
					}
					$valArr=array_filter(explode('#', $mv));
					$key=array_shift($valArr);
					$replaceDefault=$key == 'page' ? '\d+' : '[A-Za-z0-9-]+';
					$replaceStr='('.(isset($rule['replace'][$key]) ? $rule['replace'][$key] : $replaceDefault).')';
					$split='';
					if(!empty($valArr)){
						$valArr=str_replace('\\','/',$valArr);
						$split=$valArr[0];
						$replaceStr='(\\'.$split.$replaceStr.'?)';
						$nameKey++;
					}
					$param=$key;
					if($optional){
						$replaceStr.='?';
						$param.='?';
					}
					$replace[]=$matches[1][$mk].$replaceStr;
					$name[]=[$nameKey,$key,$split,$optional];
					$urlparamStr[]=$matches[1][$mk].'<'.$param.'>';
				}
				$rv=str_replace($matches[0], $replace, $rv);
			}else{
				$urlparamStr[]=$rv;
			}
			$urlparam[]=join('',$urlparamStr);
		}
		$rule['pattern']=join('/',$ruleArr);
		$rule['patternUrl']=rtrim(join('/',$urlparam),'/');
		$rule['param']=$name;
		$rule['domain']=$modelArr[0] == 'plugin' ? 'index' : $modelArr[1];
		return $rule;
	}
}