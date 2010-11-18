<?php
defined('is_running') or die('Not an entry point...');

//sleep(3); //for testing

class gpAjax{
	
	function ReplaceContent($id,$content){
		gpAjax::JavascriptCall('WBx.response','replace',$id,$content);
	}
	
	function JavascriptCall(){
		$args = func_get_args();
		if( !isset($args[0]) ){
			return;
		}
		
		echo array_shift($args);
		echo '(';
		$comma = '';
		foreach($args as $arg){
			echo $comma;
			gpAjax::quote($arg);
			$comma = ',';
		}
		echo ');';
	}
	
	function escape(&$content){
		return str_replace(array('\\','"',"\n","\r"),array('\\\\','\"','\n','\r'),$content);
	}
	
	function quote(&$content){
		echo '"';
		static $search = array('\\','"',"\n","\r",'<script','</script>');
		static $repl = array('\\\\','\"','\n','\r','<"+"script','<"+"/script>');
		echo str_replace($search,$repl,$content);
		echo '"';
	}
	
	function JsonEval($content){
		echo '{DO:"eval"';
		echo ',CONTENT:';
		gpAjax::quote($content);
		echo '},';
	}
	
	function JsonDo($do,$selector,&$content){
		echo '{DO:';
		gpAjax::quote($do);
		echo ',SELECTOR:';
		gpAjax::quote($selector);
		echo ',CONTENT:';
		gpAjax::quote($content);
		echo '},';
	}	
	
	function Response(){
		global $page;
		
		echo $_REQUEST['jsoncallback'];
		echo '([';
		
		if( is_array($page->ajaxReplace) ){
			foreach($page->ajaxReplace as $arguments){
				if( is_array($arguments) ){
					$arguments += array(0=>'',1=>'',2=>'');
					gpAjax::JsonDo($arguments[0],$arguments[1],$arguments[2]);
					
				}elseif( $arguments == '#gpx_content' ){
					
					ob_start();
					$page->GetContent();
					$content = ob_get_clean();
					gpAjax::JsonDo('replace','#gpx_content',$content);
					
				}
			}
		}
		
		//page content
			
		echo ']);';
		die();
	}
	
}


