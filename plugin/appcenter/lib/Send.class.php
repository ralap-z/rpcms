<?php
namespace plugin\appcenter\lib;


class Send{
	
	private $RPCMSAPI='http://app.rpcms.cn/api/';
	public function __construct(){
		
	}
	
	public function http_curl($url,$data=array(),$ua='',$cookie=''){
		$ch = curl_init($this->RPCMSAPI . $url);
		if(extension_loaded('zlib')){
			curl_setopt($ch, CURLOPT_ENCODING, 'gzip');
		}
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_TIMEOUT, 30);
		curl_setopt($ch, CURLOPT_USERAGENT, $ua);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt($ch, CURLOPT_MAXREDIRS, 10);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
		if(!empty($cookie)){
			curl_setopt($ch, CURLOPT_COOKIE, $cookie);
		}
		if(!empty($data)){
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
		}
		$res = curl_exec($ch);
		curl_close($ch);
		return $res;
	}
}
