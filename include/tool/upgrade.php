<?php
defined("is_running") or die("Not an entry point...");


class gpupgrade{
	
	function gpupgrade(){
		global $config;
		
		includeFile('admin/admin_tools.php');

		if( version_compare($config['gpversion'],'1.6RC4','<') ){
			$this->to16RC4(); //1.6rc4
		}
		
		if( version_compare($config['gpversion'],'1.6','<') ){
			$this->to16();
		}
		
		if( version_compare($config['gpversion'],'1.7a2','<') ){
			$this->to17a2();
		}

	}
	
	function to17a2(){
		global $langmessage,$config,$gptitles;
		
		$layouts = array();
		$layouts[$config['theme']] = $langmessage['layout'].':01';
		
		$i = 2;
		foreach($gptitles as $title => $info){
			if( empty($info['theme']) ){
				continue;
			}
			if( isset($layouts[$info['theme']]) ){
				continue;
			}
			
			$layouts[$info['theme']] = $langmessage['layout'].':'.str_pad($i,2,'0',STR_PAD_LEFT);
			$i++;
		}
		
		
		$this->AddgpLayout($layouts);
		$this->FixgpLayout($layouts);
		
		$config['gpLayout'] = $langmessage['layout'].':01';
		unset($config['theme']);
		unset($config['theme_handlers']);
		
		$config['gpversion'] = $GLOBALS['gpversion'];
		admin_tools::SaveAllConfig();
		
	}
	
	//change ['theme'] entries in $gptitles to ['gpLayout'] entries
	function FixgpLayout($layouts){
		global $config,$gptitles,$gpLayouts;
		
		foreach($gptitles as $title => $info){
			if( !empty($info['theme']) ){
				$gptitles[$title]['gpLayout'] = $layouts[ $info['theme'] ];
				unset($gptitles[$title]['theme']);
			}
		}
	}
	
	function AddgpLayout($layouts){
		global $config,$gptitles,$gpLayouts;
		
		$gpLayouts = array();
		
		$colors[] = '#e06666';
		$colors[] = '#f6b26b';
		$colors[] = '#ffd966';
		$colors[] = '#93c47d';
		$colors[] = '#76a5af';
		$colors[] = '#6fa8dc';
		$colors[] = '#8e7cc3';
		$colors[] = '#c27ba0';
		
		
		//theme handlers
		$theme_handlers =& $config['theme_handlers'];
		
		//which themes are being used
		$themes = array();
		$themes[] = $config['theme'];
		
		//create new layouts
		$i = 0;
		foreach($layouts as $theme => $name){
			
			list($template,$color) = explode('/',$theme);

			$layout['theme'] = $theme;
			$layout['color'] = $colors[$i%count($colors)];
			$layout['label'] = $name;
			
			if( isset($theme_handlers[$template]) ){
				$layout['handlers'] = $theme_handlers[$template];
			}
			$gpLayouts[$name] = $layout;
			$i++;
		}
		
	}
	
	
	function to16(){
		global $dataDir,$config;
		
		$startDir = $dataDir.'/data';
		$this->indexDirs($startDir);
		
		//version
		require_once($GLOBALS['rootDir'].'/include/admin/admin_tools.php');
		$config['gpversion'] = $GLOBALS['gpversion'];
		admin_tools::SaveConfig();
	}
	
	function indexDirs($dir){
		$folders = gpFiles::ReadDir($dir,1);
		
		foreach($folders as $folder){
			$fullPath = $dir.'/'.$folder;
			if( is_link($fullPath) ){
				continue;
			}
			
			if( is_dir($fullPath) ){
				$this->indexDirs($fullPath);
			}
		}
		gpFiles::CheckDir($dir);
	}
	
	
	//FIX GALLERIES
	function to16RC4(){
		global $gptitles,$config;
		
		require_once($GLOBALS['rootDir'].'/include/admin/admin_tools.php');
		require_once($GLOBALS['rootDir'].'/include/tool/editing_gallery.php');
		
		foreach($gptitles as $title => $info){
			if( !isset($info['type']) || $info['type'] != 'gallery' ){
				continue;
			}
			$this->UpdateGallery($title);
		}		
		//version
		$config['gpversion'] = $GLOBALS['gpversion'];
		admin_tools::SaveConfig();
		
	}
	
	function UpdateGallery($title){
		global $dataDir;
		$file = $dataDir.'/data/_pages/'.gpFiles::CleanTitle($title).'.php';
		if( !file_exists($file) ){
			return false;
		}
		
		//
		$file_array = array();
		$caption_array = array();
		ob_start();
		require($file);
		ob_get_clean();
		
		editing_gallery::SaveFileArray($title,$file_array,$caption_array);
	}
	
	
}
	
