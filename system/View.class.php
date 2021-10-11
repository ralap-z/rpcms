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

class View{
	protected static $instance;
	protected static $data=array();
	private $includeFile=array();
	private $pluginName='';
	
	public function __construct(){
		
	}
	
	public static function instance(){
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }
	
	public static function assign($name, $value=''){
		if(is_array($name)){
            self::$data = array_merge($this->data, $name);
        }else{
			self::$data[$name] = $value;
        }
	}
	
	public static function display($temp='index'){
		global $App;
		$view=self::instance();
		$tempArr=$view->setTempFile($temp);
		if(!is_file($tempArr['tempDir'])) {
            rpMsg($tempArr['temp']." template is not find");
        }
		if(Config::get('tpl_cache')){
			$cashFiles=CMSPATH .'/data/cache/'.$tempArr['temp'];
			
			$cashDir=dirname($cashFiles);
			if(!file_exists($cashFiles) && !file_exists($cashDir)){
				@mkdir($cashDir, 0755, true);
			}
			$cashFiles=$cashDir.'/'.md5($tempArr['temp']).'.php';
			if(!$view->checkCache($cashFiles)){
				$view->setCache($tempArr['tempDir'], $cashFiles);
			}
			$tempDir=$cashFiles;
		}
		self::$data['cmspath']=!empty(Config::get('app_default_path')) ? '/'.Config::get('app_default_path') : '';
		self::$data['baseUrl']=$App->baseUrl;
		ob_start();
		ob_implicit_flush(0);
		self::startGzip();
		try{
			extract(self::$data, EXTR_OVERWRITE);
			include $tempDir;
        }catch(Exception $e){
            ob_end_clean();
        }
        $content = ob_get_clean();
        return $content;
	}
	
	public static function checkTemp($temp){
		global $App;
		if(isset($App->indexTemp) && !empty($App->indexTemp)){
			$tempDir=TMPPATH .'/index/'. $App->indexTemp .'/'. $temp. '.php';
		}else{
			$tempDir=TMPPATH .'/'.$App->route['module'].'/'. $temp. '.php';
		}
		return is_file($tempDir);
	}
	
	public static function update($temp=''){
		if(empty($temp)) return false;
		$view=self::instance();
		$tempArr=$view->setTempFile($temp);
		$cashFiles=CMSPATH .'/data/cache/'.$tempArr['temp'];
		$cashDir=dirname($cashFiles);
		if(!file_exists($cashFiles) && !file_exists($cashDir)){
			@mkdir($cashDir, 0755, true);
		}
		$cashFiles=$cashDir.'/'.md5($tempArr['temp']).'.php';
		$view->setCache($tempArr['tempDir'], $cashFiles);
	}
	
	public static function startGzip(){
		if(extension_loaded("zlib") && isset($_SERVER["HTTP_ACCEPT_ENCODING"]) &&strstr($_SERVER["HTTP_ACCEPT_ENCODING"], "gzip") && !headers_sent()){
			if(ini_get('output_handler')){
                return false;
            }
            $a = ob_list_handlers();
            if(in_array('ob_gzhandler', $a) || in_array('zlib output compression', $a)){
                return false;
            }
            if(function_exists('ob_gzhandler')){
                @ob_end_clean();
                @ob_start('ob_gzhandler');
            }elseif(function_exists('ini_set') && function_exists('zlib_encode')){
                @ob_end_clean();
                @ini_set('zlib.output_compression', 'On');
                @ini_set('zlib.output_compression_level', '5');
            }
		}
	}
	
	public function checkCache($file){
		if(!file_exists($file)){
			return false;
		}
		if(!$handle = @fopen($file, "r")){
            return false;
        }
		preg_match('/\/\*(.+?)\*\//', fgets($handle), $matches);
        if(!isset($matches[1])){
            return false;
        }
		$includeFile = unserialize($matches[1]);
        if (!is_array($includeFile)) {
            return false;
        }
		foreach($includeFile as $path => $time){
            if(is_file($path) && filemtime($path) > $time){
                return false;
            }
        }
		return true;
	}
	
	public static function setPluginName($name){
		$view=self::instance();
		$view->pluginName=$name;
	}
	
	private function setTempFile($temp){
		global $App;
		if(($App->route['controller'] == 'plugin' && $App->route['action'] == 'run') || 0 === strpos($temp, '/plugin/')){
			if(0 === strpos($temp, '/') && false === strpos($temp, 'plugin')){
				$temp='index/'. (isset($App->indexTemp) ? $App->indexTemp .'/' : ''). $temp. '.php';
				$tempDir=TMPPATH .'/'. $temp;
			}else{
				$temp2=$this->leftReplaceOne($temp,array('plugin','/plugin'));
				$tempDir=PLUGINPATH .$temp2.'.php';
			}
		}else{
			if(0 !== strpos($temp, '/')){
				$temp='/'.$App->route['controller'] .'/'. $temp;
			}
			if($App->route['module'] == 'plugin' || $temp == '/404'){
				$temp='index/'.$App->indexTemp. $temp. '.php';
			}else{
				$temp=$App->route['module']. $temp. '.php';
			}
			$tempDir=TMPPATH .'/'. $temp;
		}
		return array('tempDir'=>$tempDir,'temp'=>$temp);
	}
	
	private function setCache($tempDir, $cashFiles){
		$view=self::instance();
		$view->includeFile[$tempDir] = filemtime($tempDir);
		$content=$view->CompileFile(@file_get_contents($tempDir));
		$content = "<?php if(!defined('CMSPATH')) exit();/*" . serialize($view->includeFile) . "*/?>" . "\n" . $this->compress_html($content);
		@file_put_contents($cashFiles, $content);
		$view->includeFile = array();
	}
	
	private function compress_html($string){
		return trim(preg_replace(array("/> *([^ ]*) *</","//","'/\*[^*]*\*/'","/\r\n/","/\n/","/\t/",'/>[ ]+</'),array(">\\1<",'','','','','','><'),$string));
	}
	
	private function CompileFile($content){
		//$this->remove_php($content);
		$this->uncompile_code($content);
		$this->parse_incldue($content);
		$this->parse_comments($content);
		$this->parse_option($content);
		$this->parse_vars($content);
		$this->parse_function($content);
		$this->parse_hook($content);
		$this->parse_if($content);
		$this->parse_foreach($content);
		$this->parse_for($content);
		$this->parse_uncompile_code($content);
		return $content;
	}
	
	private function remove_php(&$content){
        $content = preg_replace("/\<\?php[\d\D]+?\?\>/si", '', $content);
    }
	private function uncompile_code(&$content){
        $this->uncompiledCodeStore = array();
        $matches = array();
        if ($i = preg_match_all('/\{(php|pre)\}([\D\d]+?)\{\/(php|pre)\}/si', $content, $matches) > 0) {
            if (isset($matches[2])) {
                foreach ($matches[2] as $j => $p) {
                    $content = str_replace($p, '<!-- parse_middle_code' . $j . '-->', $content);
                    $this->uncompiledCodeStore[$j] = array(
                        'type'    => $matches[1][$j],
                        'content' => $p,
                    );
                }
            }
        }
    }
	private function parse_comments(&$content){
        $content = preg_replace('/\{\*([^\}]+)\*\}/', '{php} /*$1*/ {/php}', $content);
    }
	private function parse_incldue(&$content){
		$content = preg_replace_callback('/\{include:([^\}]+)\}/', array($this, 'parse_incldue_do'), $content);
		if(preg_match_all('/\{include:([^\}]+)\}/', $content, $matches) > 0) {
			$this->parse_incldue($content);
		}
    }
	private function parse_option(&$content){
        $content = preg_replace('/\{RP.([^\}]+)\}/', '<?php if(defined(trim(\'\\1\'))){echo \\1;}else{echo rp\Config::get(\'webConfig.\\1\');} ?>', $content);
    }
	private function parse_vars(&$content){
        $content = preg_replace_callback('#\{\$(?!\()([^\}]+)\}#', array($this, 'parse_vars_do'), $content);
    }
	private function parse_function(&$content){
        $content = preg_replace_callback('/\{:([a-zA-Z0-9_]+?)\((.*?)\)\}/', array($this, 'parse_funtion_do'), $content);
    }
	private function parse_hook(&$content){
		$content = preg_replace('/\{hook:([^\}]+)\(([^\}]+)\)\}/', '{php}$hookAegs=array(\\2);foreach(\rp\Hook::doHook(\'\\1\',$hookAegs) as $hk=>$hv){echo $hv;}unset($hookAegs);{/php}', $content);
		$content = preg_replace('/\{hook:([^\}]+)\}/', '{php}foreach(\rp\Hook::doHook(\'\\1\') as $hk=>$hv){echo $hv;}{/php}', $content);
	}
	private function parse_if(&$content){
        while (preg_match('/\{if [^\n\}]+\}.*?\{\/if\}/s', $content)) {
            $content = preg_replace_callback(
                '/\{if ([^\n\}]+)\}(.*?)\{\/if\}/s',
                array($this, 'parse_if_do'),
                $content
            );
        }
    }
	private function parse_elseif($matches){
        $ifexp = $matches[1];
		$ifexp = preg_replace('/RP.([^\}]+)/', 'rp\Config::get(\'webConfig.\\1\')', $ifexp);
        return "{php}}elseif($ifexp) { {/php}";
    }
	private function parse_foreach(&$content){
        while (preg_match('/\{foreach(.+?)\}(.+?){\/foreach}/s', $content)) {
            $content = preg_replace_callback(
                '/\{foreach(.+?)\}(.+?){\/foreach}/s',
                array($this, 'parse_foreach_do'),
                $content
            );
        }
    }
	private function parse_for(&$content){
        while(preg_match('/\{for(.+?)\}(.+?){\/for}/s', $content)) {
            $content = preg_replace_callback(
                '/\{for(.+?)\}(.+?){\/for}/s',
                array($this, 'parse_for_do'),
                $content
            );
        }
    }
	private function parse_uncompile_code(&$content){
        foreach ($this->uncompiledCodeStore as $j => $p) {
            if ($p['type'] == 'php') {
                $content = str_replace('{php}<!-- parse_middle_code' . $j . '-->{/php}', '<' . '?php ' . $p['content'] . ' ?' . '>', $content);
            } else {
                $content = str_replace(
                    '{' . $p['type'] . '}<!-- parse_middle_code' . $j . '-->{/' . $p['type'] . '}',
                    $p['content'],
                    $content
                );
            }
        }
        $content = preg_replace('/\{php\}([\D\d]+?)\{\/php\}/', '<' . '?php $1 ?' . '>', $content);
        $this->uncompiledCodeStore = array();
    }
	private function parse_for_do($matches){
        $exp = $matches[1];
        $code = $matches[2];
        return "{php} for($exp) {{/php} $code{php} }  {/php}";
    }
	private function parse_foreach_do($matches){
        $exp = $matches[1];
        $code = $matches[2];
        return "{php} foreach ($exp) {{/php}$code{php}}  {/php}";
    }
	private function parse_if_do($matches){
        $content = preg_replace_callback(
            '/\{elseif ([^\n\}]+)\}/',
            array($this, 'parse_elseif'),
            $matches[2]
        );
        $ifexp = $matches[1];
        $content = str_replace('{else}', '{php}}else{ {/php}', $content);
        return "<?php if ($ifexp) { ?>$content<?php } ?>";
    }
	private function parse_incldue_do($matches){
		global $App;
		if(0 === strpos($matches[1], '$')){
			$tempDir=self::$data[substr($matches[1],1)];
		}else{
			if(($App->route['controller'] == 'plugin' && $App->route['action'] == 'run') || $App->route['module'] == 'plugin'){
				if(0 === strpos($matches[1], '/')){
					$matches[1]=($App->route['module'] == 'plugin' ? 'index' : $App->route['module']) .'/'. (isset($App->indexTemp) ? $App->indexTemp .'/' : ''). $matches[1]. '.php';
					$tempDir=TMPPATH .'/'. $matches[1];
				}else{
					$matches[1]='plugin/'. $this->pluginName .'/'.$this->leftReplaceOne($matches[1],'plugin');
					$tempDir=PLUGINPATH . $this->leftReplaceOne($matches[1],'plugin') . '.php';
				}
			}else{
				if($App->route['module'] == 'index'){
					$matches[1]= '/'.$App->indexTemp . '/'. ltrim($matches[1], '/');
				}elseif(0 !== strpos($matches[1], '/')){
					$matches[1]='/'.$App->route['controller'] .'/'. $matches[1];
				}
				$matches[1]=$App->route['module']. $matches[1]. '.php';
				$tempDir=TMPPATH .'/'. $matches[1];
			}
		}
		if(!is_file($tempDir)) {
            rpMsg($matches[1]." template is not find");
        }
		$this->includeFile[$tempDir] = filemtime($tempDir);
		return @file_get_contents($tempDir);
	}
	private function parse_vars_do($matches){
		$str = $matches[1];
		if(false == strpos($str, '=') || false != strpos($str, '?') || false != strpos($str, '|')){
			if(false != strpos($str, '|')){
				$this->parse_vars_function($str);
				return '{php} echo '.$str.'; {/php}';
			}
			return (false == strpos($str, '?') && !preg_match('/[\+\-\*\/\%]/', $str)) ? '{php} echo isset($'.$str.') ? $'.$str.' : \'\'; {/php}' : '{php} echo $'.$str.'; {/php}';
		}
		return '{php} $'.$str.'; {/php}';
    }
	private function parse_funtion_do($matches){
        return '{php} echo ' . $matches[1] . '(' . $matches[2] . '); {/php}';
    }
	private function parse_vars_function(&$varStr){
		if (false == strpos($varStr, '|')) {
			$varStr='$'.$varStr;
			return;
		}
		static $_varFunctionList = [];
		$_key = md5($varStr);
		if (isset($_varFunctionList[$_key])) {
			$varStr = $_varFunctionList[$_key];
		} else {
			$varArray = explode('|', $varStr);
			$name = '$'.array_shift($varArray);
			$length = count($varArray);
			$template_deny_funs = explode(',', Config::get('tpl_deny_func_list'));
			for ($i = 0; $i < $length; $i++) {
				$args = explode('=', $varArray[$i], 2);
				$fun = trim($args[0]);
				switch ($fun) {
					case 'default': 
						$name = '(isset(' . $name . ') && (' . $name . ' !== \'\') ? ' . $name . ' : ' . $args[1] . ')';
						break;
					default:
						if (!in_array($fun, $template_deny_funs)) {
							if (isset($args[1])){
								if (strstr($args[1], '###')) {
									$args[1] = str_replace('###', $name, $args[1]);
									$name    = "$fun($args[1])";
								} else {
									$name = "$fun($name,$args[1])";
								}
							} else {
								if (!empty($args[0])) {
									$name = "$fun($name)";
								}
							}
						}
				}
			}
			$_varFunctionList[$_key] = $name;
			$varStr = $name;
		}
		return;
	}

	private function leftReplaceOne($str,$find,$replace=''){
		if(is_array($find)){
			foreach($find as $k=>$v){
				$str= 0 === strpos($str, $v) ? substr_replace($str,$replace,0,strlen($v)) : $str;
			}
		}else{
			$str= 0 === strpos($str, $find) ? substr_replace($str,$replace,0,strlen($find)) : $str;
		}
		return $str;
	}
}