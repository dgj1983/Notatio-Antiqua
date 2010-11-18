<?php
defined("is_running") or die("Not an entry point...");


includeFile('admin/admin_uploaded.php');


class admin_browser extends admin_uploaded{
	
	function admin_browser(){
		$this->Init();
		$this->Standalone();

	}
	function Standalone(){
		$this->browseString = 'Admin_Browser';
		
		$_REQUEST['gpreq'] = 'body'; //force showing only the body as a complete html document
		$this->PrepHead();
		$this->AdminCommands();
		
		$this->ShowPanel();
		$this->ShowFolder();

	}
	
	function PrepHead(){
		global $config,$page;
		common::AddColorBox();
		
		$page->head .= '<script src="'.common::GetDir('/include/js/browser.js').'" type="text/javascript"></script>';
		$page->head .= '<style>';
		$page->head .= 'html,body{padding:0;margin:0;background-color:#fff !important;background-image:none !important;}';
		$page->head .= '.browser_class{padding:20px;}';
		$page->head .= '</style>';
	}
	
	function Link_Img($file,$full_path){
		
		echo '<a href="'.$full_path.'" class="select">';
		echo '<input type="hidden" name="fileUrl" value="'.htmlspecialchars($full_path).'" />';
		if( !$this->isThumbDir ){
			echo ' <img src="'.common::GetDir('/data/_uploaded/image/thumbnails'.$this->subdir.'/'.$file.'.jpg').'" height="100" width="100" class="icon" />';
		}else{
			echo ' <img src="'.$full_path.'" height="100" width="100" class="icon" />';
		}
		echo '</a>';
		
		echo '<div>';
		echo '<a href="'.$full_path.'" class="select">';
		echo '<input type="hidden" name="fileUrl" value="'.htmlspecialchars($full_path).'" />';
		echo $file;
		echo '</a>';
		echo '</div>';
		
	}
	
	function File_Link_Right($file,$is_img,$img_url=false){
		global $langmessage;
		
		if( $is_img ){
			echo '<a href="'.$img_url.'" name="gallery" rel="gallery_uploaded" title="'.$file.'">';
			echo '<img src="'.common::GetDir('/include/imgs/page_white_magnify.png').'" alt="" height="16" width="16" /> ';
			echo '<span>'.$langmessage['preview'].'</span>';
			echo '</a>';
		}
		
		parent::File_Link_Right($file,$is_img,$img_url);
	}
	
	
}
