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

use rp\Cache;
use rp\Db;
use rp\Hook;

class Upload{
	public $postTime;
	public $oriName;//原始文件名称
	public $fileSizes;//文件大小
	public $fileTypes;//文件类型，如.jpg
	public $fullName;//文件相对路径
	public $filePath;//文件绝对路径
	public $fileName;//文件保存名称
	public $dirNames;//所在文件夹名称
	public $authorId;
	public $logId=0;
	public $pageId=0;
	
	public function __construct(){
        $this->postTime = date('Y-m-d H:i:s');
		$session=session('MEADMIN');
		$this->authorId=isset($session['uid']) ? $session['uid'] : '';
    }
	
	public function saveFile($tmpName,$isThumb=true){
		$dirRes=$this->checkDir();
		if($dirRes !== true){
			return array('code'=>-1, 'msg'=>$dirRes);
		}
        if(!(move_uploaded_file($tmpName, $this->filePath) && file_exists($this->filePath))){ //移动失败
			return array('code'=>-1, 'msg'=>'文件保存时出错');
        }
		return $this->doOper($isThumb);
    }
	
	public function saveBase64File($base64Data,$isThumb=true){
		$dirRes=$this->checkDir();
		if($dirRes !== true){
			return array('code'=>-1, 'msg'=>$dirRes);
		}
		$img = base64_decode($base64Data);
        if(!(file_put_contents($this->filePath, $img) && file_exists($this->filePath))){ //移动失败
            return array('code'=>-1, 'msg'=>'写入文件内容错误');
        }
		$this->oriName=date('YmdHis') . '_' . $this->oriName;
		return $this->doOper($isThumb);
	}
	
	/*保存附件信息到数据库*/
	public function saveAttr(){
		$data=array(
			'filename'=>$this->oriName,
			'filesize'=>$this->fileSizes,
			'filepath'=>$this->fullName,
			'token'=>md5($this->fullName).rand(100,999),
			'filetype'=>str_replace(['jpeg','docx','xlsx','pptx'], ['jpg','doc','xls','ppt'], ltrim($this->fileTypes, ".")),
			'logId'=>$this->logId,
			'pageId'=>$this->pageId,
			'authorId'=>$this->authorId,
			'createTime'=>$this->postTime,
		);
		Db::name('attachment')->insert($data);
	}
	
	/*图片生成缩略图*/
	public function createThumbnail($img,$width='',$height=''){
		$size = @getimagesize($img);
		$twidth=!empty($width) && intval($width) > 0 ? intval($width) : Config::get('webConfig.attImgWitch');
		$theight=!empty($height)&& intval($height) > 0 ? intval($height) : Config::get('webConfig.attImgHeight');
		$w = $size[0];
		$h = $size[1];
		$w_ratio = $twidth / $w;
		$h_ratio = $theight / $h;
		$ratio = ($w_ratio < $h_ratio) ? $h_ratio : $w_ratio;
		$ratio = !empty($ratio) ? $ratio : 1;
		if (($w <= $twidth) && ($h <= $theight)){
			$newW=$w;
			$newH=$h;
		}else{
			$newW = ceil($twidth / $ratio);
			$newH = ceil($theight / $ratio);
		}
		$thumPath = $this->dirNames . '/thum-' . $this->fileName;
		if(!function_exists('imagecreatefromstring')){
			return false;
		}
		$srcImg = imagecreatefromstring(file_get_contents($img));
		if(function_exists('imagecopyresampled')){
			$newImg = imagecreatetruecolor($twidth, $theight);
			imagecopyresampled($newImg, $srcImg, 0, 0, 0, 0, $twidth, $theight, $newW, $newH);
		}elseif(function_exists('imagecopyresized')){
			$newImg = imagecreate($twidth, $theight);
			imagecopyresized($newImg, $srcImg, 0, 0, 0, 0, $twidth, $theight, $newW, $newH);
		}else{
			return false;
		}
		if((!file_exists($this->dirNames) && !mkdir($this->dirNames, 0777, true)) || !is_writeable($this->dirNames)){
			return false;
		}
		switch($this->fileTypes){
			case '.png':
				if(function_exists('imagepng') && imagepng($newImg, $thumPath)){
					ImageDestroy($newImg);
					return $thumPath;
				}
				break;
			case '.gif':
				if(function_exists('imagegif') && imagegif($newImg, $thumPath)){
					ImageDestroy($newImg);
					return $thumPath;
				}
				break;
			case '.jpg':
			case '.jpeg':
			default:
				if(function_exists('imagejpeg') && imagejpeg($newImg, $thumPath)){
					ImageDestroy($newImg);
					return $thumPath;
				}
				break;
		}
		return false;
	}
	
	private function checkDir(){
		if(!is_dir($this->dirNames) && !mkdir($this->dirNames, 0777, true)){
			return '目录创建失败';
		}else if(!is_writeable($this->dirNames)){
			return '目录没有写权限';
		}
		return true;
	}
	
	private function doOper($isThumb){
		//$this->saveAttr();
		if($isThumb && in_array($this->fileTypes, array('.jpg','.jpeg','.png','.gif','.bmp'))){
			$this->createThumbnail($this->filePath);
		}
		Hook::doHook('admin_attach_upload',array($this->filePath));
		return array('code'=>200, 'msg'=>'SUCCESS', 'data'=>$this->fullName);
	}
}
