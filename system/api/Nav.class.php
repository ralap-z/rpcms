<?php
namespace rp\api;

use rp\Cache;
use rp\Url;

class Nav extends Base{
	
	public function __construct(){
		parent::__construct();
	}
	
	public function getList(){
		$data=Cache::read('nav');
		$data=arraySequence($data,'sort','SORT_ASC');
		$this->response($data);
	}
	
	
}