<?php
defined('is_running') or die('Not an entry point...');

includeFile('admin/admin_menu_tools.php');

class file_editing extends admin_menu_tools{
	var $buffer = false;
	
	function file_editing(){
		global $page,$langmessage;
		
		
		//has file_editing permission?
		if( !admin_tools::HasPermission('file_editing') ){
			message($langmessage['not_permitted']);
			return;
		}
		
		$cmd = common::GetCommand();
		
		ob_start();
		switch($cmd){
			
			case $langmessage['preview']:
				$this->preview();
			break;
			case 'edit':
				$this->edit();
			break;
			case 'save';
				$this->save();
			break;
			
			case 'renameform':
				$this->RenameForm();
			break;
			case $langmessage['save_changes']:
				$this->RenameFile();
			break;
		}
		$page->contentBuffer = ob_get_clean();
	}
	
	function RenameFile(){
		global $page,$langmessage;
		parent::RenameFile($page->title);
		
		if( $this->FileUrlChanged ){
			echo sprintf($langmessage['will_redirect'],common::Link($this->FileUrlChanged,$this->FileUrlChanged));
			$page->head .= '<meta http-equiv="refresh" content="15;url='.common::GetUrl($this->FileUrlChanged).'">';
		}
	}
	
	function RenameForm(){
		global $page;
		
		$page->jQueryCode = 'RenamePrep(true);';
		$this->RenameFormAction = $page->title;
		
		echo parent::RenameForm($page->title,true);
	}
	
	
	function Preview(){
		global $langmessage;
		
		message($langmessage['preview_warning']);

		$text =& $_POST['gpcontent'];
		gpFiles::cleanText($text);
		
		echo $text;
		
		echo '<p><br/></p>';
		
		$this->edit($text);
	}
	
	
	
	function save(){
		global $langmessage,$page;

		$text =& $_POST['gpcontent'];
		gpFiles::cleanText($text);
		
		if( gpFiles::SaveTitle($page->title,$text,$page->fileType) ){
			message($langmessage['SAVED']);
			return;
		}
		
		message($langmessage['OOPS']);
		$this->edit($text);
	}
		
	function edit($contents=false){
		global $langmessage,$page;
		
		if( $page->fileType == 'gallery' ){
			includeFile('/tool/editing_gallery.php');
			new editing_gallery();
		}else{
			$this->edit_page($contents);
		}
		
	}
	
	function edit_page($contents){
		global $page,$langmessage;
		
		echo '<form action="'.common::GetUrl($page->title).'" method="post">';
		echo '<input type="hidden" name="cmd" value="save" />';
		
		if( $contents === false ){
			ob_start();
			include($page->file);
			$contents = ob_get_clean();
		}
		common::UseCK( $contents );
		
		echo '<input type="submit" class="submit" name="" value="'.$langmessage['save'].'" />';
		echo ' <input type="submit" class="submit" name="cmd" value="'.$langmessage['preview'].'" />';
		echo ' <input type="submit" class="submit" name="cmd" value="'.$langmessage['cancel'].'" />';
		echo '</form>';
	}
	
	
}
