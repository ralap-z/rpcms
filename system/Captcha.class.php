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

use rp\Config;

class Captcha{
	private $seKey='RPCMS';
	private $strings='ABCDEFGHIJKLMNPRSTUVWXYZ23456789';
	private $code='';
	private $width;
	private $height;
	private $types;
	private $expire=60;
	
	public function __construct(){
		$this->width=Config::get('captha_style_width');
		$this->height=Config::get('captha_style_height');
		$this->types=Config::get('webConfig.captha_style');
		if(empty($this->types)){
			$this->types=1;
		}
	}
	
	public function outImg($id=''){
		$image=imagecreate($this->width,$this->height);
		$bg=imagecolorallocate($image, mt_rand(200, 255), mt_rand(200, 255), mt_rand(200, 255));
		imagefill($image, 0, 0, $bg);
		if($this->types == 1){
			$this->createCode();
			$square=$this->width * $this->height;
			$effects=mt_rand($square / 1000, $square / 500);
			for($i = 0; $i < $effects; $i++){
				$this->drawLine($image, $this->width, $this->height);
			}
			for($i = 0; $i < strlen($this->code); $i++){
				$x = $i * 13 + mt_rand(0, 4);
				$y = mt_rand(0, 3);
				$color = imagecolorallocate($image, mt_rand(1, 100), mt_rand(1, 100), mt_rand(1, 100));
				imagechar($image, 5, $x + 10, $y + 3, $this->code[$i], $color);
			}
		}else{
			$codeStr=[rand(1,9),'','','=','?'];
			$codeStr[2]=rand(0,$codeStr[0]);
			switch(rand(1,3)){
				case 1:
					$codeStr[1]='+';
					$this->code = $codeStr[0] + $codeStr[2];
					break;
				case 2:
					$codeStr[1]='-';
					$this->code = $codeStr[0] - $codeStr[2];
					break;
				case 3:
					$codeStr[1]='x';
					$this->code = $codeStr[0] * $codeStr[2];
					break;
			}
			$codeNX=0;
			for($i = 0; $i < 5; $i++){
				$x = $i * 13 + mt_rand(0, 4);
				$y = mt_rand(0, 3);
				$color = imagecolorallocate($image, mt_rand(1, 100), mt_rand(1, 100), mt_rand(1, 100));
				imagechar($image, 5, $x + 10, $y + 3, $codeStr[$i], $color);
			}
		}
		$this->postEffect($image);
		$key=$this->authcode($this->seKey);
		$code = $this->authcode(strtoupper($this->code));
		session($key . $id,array('code'=>$code,'time'=>time()));
		header('Content-type: image/jpeg');
		imagejpeg($image, null, 90);
		imagedestroy($image);
	}
	
	public function check($code, $id = ''){
		if(Hook::hasHook('Captcha_check')){
			return Hook::doHook('Captcha_check',array($code, $id),true)[0];
		}
		$key = $this->authcode($this->seKey) . $id;
        $secode = session($key);		
        if (empty($code) || empty($secode)) {
            return false;
        }
        if (time() - $secode['time'] > $this->expire) {
            session($key, NULL);
            return false;
        }
        if ($this->authcode(strtoupper($code)) == $secode['code']) {
            session($key, NULL);
            return true;
        }
        return false;
	}
	
	/*生成随机验证码*/
	private function createCode($len=5){
		for($i = 0; $i < $len; $i++){
			$this->code .= substr($this->strings, mt_rand(0, strlen($this->strings) - 1), 1);
        }
	}
	
	/*生成干扰线*/
	private function drawLine($image, $width, $height, $color = null){
		if($color === null){
			$color = imagecolorallocate($image, mt_rand(100, 255), mt_rand(100, 255), mt_rand(100, 255));
		}
		if (mt_rand(0,1)){
			$Xa = mt_rand(0, $width / 2);
			$Ya = mt_rand(0, $height);
			$Xb = mt_rand($width / 2, $width);
			$Yb = mt_rand(0, $height);
		}else{
			$Xa = mt_rand(0, $width);
			$Ya = mt_rand(0, $height / 2);
			$Xb = mt_rand(0, $width);
			$Yb = mt_rand($height / 2, $height);
		}
		imagesetthickness($image, mt_rand(1, 3));
		imageline($image, $Xa, $Ya, $Xb, $Yb, $color);
	}
	
	protected function postEffect($image){
		if(!function_exists('imagefilter')){
			return;
		}
		if(mt_rand(0, 1) == 0){
			imagefilter($image, IMG_FILTER_NEGATE);
        }
        if(mt_rand(0, 10) == 0){
			imagefilter($image, IMG_FILTER_EDGEDETECT);
        }
		imagefilter($image, IMG_FILTER_CONTRAST, mt_rand(-50, 10));
		if(mt_rand(0, 5) == 0){
			imagefilter($image, IMG_FILTER_COLORIZE, mt_rand(-80, 50), mt_rand(-80, 50), mt_rand(-80, 50));
		}
    }
	
	private function authcode($str){
        $key = substr(md5($this->seKey), 5, 8);
        $str = substr(md5($str), 8, 10);
        return md5($key . $str);
    }
}