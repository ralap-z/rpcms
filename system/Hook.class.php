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

use ReflectionMethod;
use ReflectionClass;

class Hook{
	protected static $hookData=array();
	
	public function __construct(){
		
	}
	
	public static function addHook($hook,$actionFun){
		if(!empty($actionFun) && (!isset(self::$hookData[$hook]) || !@in_array($actionFun, self::$hookData[$hook]))){
			self::$hookData[$hook][] = $actionFun;
		}
		return true;
	}
	
	public static function deleHook($hook,$actionFun){
		if(isset(self::$hookData[$hook])){
			$key=array_search($actionFun,self::$hookData[$hook]);
			if($key !== false) unset(self::$hookData[$hook][$key]);
		}
		return true;
	}
	
	public static function doHook($hook, &$args='', $isReturn=false){
		$fun='';
		if(is_string($args) && strstr($args,'::')){
			$fun=$args;
			$args=!is_bool($isReturn) ? $isReturn : '';
			$isReturn=func_num_args() == 4 ? func_get_arg(3) : $isReturn; 
		}
		$res=array();
		if(!empty($hook) && isset(self::$hookData[$hook])){
			if(!empty($fun)){
				$funArr=explode('::',$fun);
				if(isset($funArr[1])){
					$class=new ReflectionClass($funArr[0]);
					$class=$class->newInstanceArgs();
					$reflect = new ReflectionMethod($class, $funArr[1]);
					$res[]=$reflect->invokeArgs($class, array(&$args));
				}
			}else{
				foreach(self::$hookData[$hook] as $fun){
					$funArr=explode('::',$fun);
					if(isset($funArr[1])){
						$class=new ReflectionClass($funArr[0]);
						$class=$class->newInstanceArgs();
						$reflect = new ReflectionMethod($class, $funArr[1]);
						$res[]=$reflect->invokeArgs($class, array(&$args));
						if($isReturn) return $res;
					}
				}
			}
		}
		return $res;
	}
	
	
	/**
     * 获取对象类型的参数值
     * @access protected
     * @param  string   $className  类名
     * @param  array    $vars       参数
     * @return mixed
     */
    protected static function getObjectParam($className, &$vars){
        $array = $vars;
        $value = array_shift($array);

        if ($value instanceof $className) {
            $result = $value;
            array_shift($vars);
        } else {
            $result = $this->make($className);
        }

        return $result;
    }
	
	
	public static function getHook($hook=null){
		return !empty($hook) ? (isset(self::$hookData[$hook]) ? self::$hookData[$hook] : null) : self::$hookData;
	}
	
	public static function hasHook($hook){
		return isset(self::$hookData[$hook]) ? true : false;
	}
	
	public static function saveHook(){
		$hookFile=CMSPATH .'/data/hook.php';
		@file_put_contents($hookFile,'<?php '. PHP_EOL .' return '.var_export(self::$hookData,true).';');
	}
	
	public static function setHookNull(){
		self::$hookData=array();
	}
	
	
}