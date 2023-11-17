<?php
// +----------------------------------------------------------------------
// | RPCMS
// +----------------------------------------------------------------------
// | Copyright (c) 2019 http://www.rpcms.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( https://www.rpcms.cn/html/license.html )
// +----------------------------------------------------------------------
// | Author: ralap <www.rpcms.cn>
// +----------------------------------------------------------------------

namespace rp;

class Config{
	private static $config = array();
	
	/**
	* 设置配置参数 name 为数组则为批量设置
	* @access public
	* @param  string|array $name  配置参数名（支持二级配置 . 号分割）
	* @param  mixed        $value 配置值
	* @param  string       $range 作用域
	* @return mixed
	*/
	public static function set($name, $value=''){
		// 字符串则表示单个配置设置
		if (is_string($name)) {
			if (!strpos($name, '.')) {
				self::$config[strtolower($name)] = $value;
			} else {
				// 二维数组
				$name = explode('.', $name, 2);
				self::$config[strtolower($name[0])][$name[1]] = $value;
			}
			return $value;
		}
		// 数组则表示批量设置
		if (is_array($name)) {
			if (!empty($value)) {
				self::$config[$value] = isset(self::$config[$value]) ? array_merge(self::$config[$value], $name) : $name;
				return self::$config[$value];
			}
			return self::$config = array_merge(self::$config, array_change_key_case($name));
		}
		// 为空直接返回已有配置
		return self::$config;
	}
	
	
	/**
	* 获取配置参数 为空则获取所有配置
	* @access public
	* @param  string $name 配置参数名（支持二级配置 . 号分割）
	* @return mixed
	*/
	public static function get($name = null, $default=null){
		// 无参数时获取所有
		if(empty($name)){
			return self::$config;
		}
		// 非二级配置时直接返回
		if (!strpos($name, '.')) {
			$name = strtolower($name);
			return isset(self::$config[$name]) ? self::$config[$name] : $default;
		}
		// 二维数组设置和获取支持
		$nameArr    = explode('.', $name, 2);
		$nameArr[0] = strtolower($nameArr[0]);
		return isset(self::$config[$nameArr[0]][$nameArr[1]]) ? self::$config[$nameArr[0]][$nameArr[1]] : $default;
	}
	
	/**
	* 检测配置是否存在
	* @access public
	* @param  string $name 配置参数名（支持二级配置 . 号分割）
	* @return bool
	*/
	public static function has($name){
		if(!strpos($name, '.')){
			return isset(self::$config[strtolower($name)]);
		}
		// 二维数组设置和获取支持
		$name = explode('.', $name, 2);
		return isset(self::$config[strtolower($name[0])][$name[1]]);
	}
}