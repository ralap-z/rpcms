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

class Zip{
	
	private $zip = '';
	private $zipfile = '';

	public function __construct($file){
		if(!class_exists('ZipArchive', FALSE)) {
			return 'ZipArchive is error';
		}
		$this->zip = new \ZipArchive();
		$this->zipfile=$file;
	}
	
	public function readFiles(){
		$file_dir_list = array();
		$file_list = array();
		if($this->zip->open($this->zipfile) == true){
			for($i = 0; $i < $this->zip->numFiles; $i++){
				$numfiles = $this->zip->getNameIndex($i);
				if(preg_match('/\/$/i', $numfiles)){
                    $file_dir_list[] = $numfiles;
				}else{
					$file_list[] = $numfiles;
				}
			}
		}
		return array('files'=>$file_list, 'dirs'=>$file_dir_list);
    }
	
	public function getAppName(){
		$appname='';
		if($this->zip->open($this->zipfile) == true){
			$appname = $this->zip->getNameIndex(0);
		}
		$appname=explode('/', $appname)[0] .'/';
		return $appname;
	}
	
	public function getFiles($file){
		$res = $this->zip->getFromName($file);
		return false !== $res ? $res : '';
	}
	
	public function unzip($path){
		if($this->zip->open($this->zipfile) == true){
			if(true === @$this->zip->extractTo($path)){
				$this->zip->close();
				return array('code'=>200,'msg'=>'解压成功');
			}else{
				return array('code'=>-1,'msg'=>'解压失败，请检查目录是否可读写');
			}
		}
		return array('code'=>-1,'msg'=>'zip文件打开错误或目录不可读写');
	}
	
	public function compress($name){
		try{
			$this->zip->open($name, \ZipArchive::CREATE);
			$this->addFileToZip($this->zipfile);
			$this->zip->close();
			return true;
        }catch(Exception $e){
			return false;
        }
	}
	
	public function down($name=''){
		if(!empty($this->zipfile)){
			$name= empty($name) ? basename($this->zipfile) : $name;
			$fp=fopen($this->zipfile,'r');
			header("Content-Type: application/octet-stream");
			header("Accept-Ranges: bytes");
			header("Accept-Length: ".filesize($this->zipfile)); 
			header('Content-disposition: attachment; filename=' . $name);
			ob_clean();
			flush();
			$buffer=1024;
			while(!feof($fp)){
				echo fread($fp,$buffer);
			}
			fclose($fp);
		}
		exit;
	}
	
	public function close(){
		$this->zip->close();
	}
	
	private function addFileToZip($path){
		$handler = opendir($path);
		while(($filename = readdir($handler)) !== false){
			if($filename != "." && $filename != ".."){
				$filePath = $path.'/'.$filename;
				if(is_dir($filePath)){
					$this->addFileToZip($filePath);
				}else{ 
					$entryName=ltrim(str_replace($this->zipfile, '', $filePath), '/');
					$this->zip->addFile($filePath, $entryName);
				}
			}
		}
		@closedir($handler);
	}
}