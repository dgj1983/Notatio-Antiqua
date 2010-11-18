<?php
defined('is_running') or die('Not an entry point...');

class admin_trash{

	function admin_trash(){
		global $langmessage;
		
		$cmd = common::GetCommand();
		switch($cmd){
			
			case $langmessage['restore']:
				$this->RestoreNew();
			break;
			
			case $langmessage['delete']:
				$this->DeleteFromTrash();
			break;
			
		}
		
		$this->Trash();
		
	}
	
	function RestoreNew(){
		global $langmessage,$dataDir;
		
		$title = admin_tools::CheckPostedNewPage();
		if( $title == false ){
			return false;
		}
		
		$restore_title = gpFiles::CleanTitle($_POST['title']); //just in case
		

		$trash_file = $dataDir.'/data/_trash/'.$restore_title.'.php';
		if( !file_exists($trash_file) ){
			message($langmessage['OOPS'].' (R2)');
			return false;
		}
		
		//get file_type from file contents
		$fileType = $this->GetFileType($trash_file);
		
		//move the file from the trash
		if( !$this->MoveFromTrash($restore_title,$title) ){
			message($langmessage['OOPS'].' (R3)');
			return false;
		}
		
		//	Add to titles
		$label = str_replace('_',' ',$_POST['title']);
		admin_tools::TitlesAdd($title,$label,$fileType);

		
		if( !admin_tools::SavePagesPHP() ){
			message($langmessage['OOPS'].' (R4)');
			return false;
		}
		
		$link = common::GetUrl('Admin_Menu');
		$message = sprintf($langmessage['file_restored'],'<em>'.$label.'</em>',$link);
		message($message);
	}
	
	
	function Trash(){
		global $dataDir,$langmessage;
		
		
		echo '<h2>'.$langmessage['trash'].'</h2>';
		
		$trash_dir = $dataDir.'/data/_trash';
		$trashtitles = gpFiles::ReadDir($trash_dir);
		asort($trashtitles);
		
		if( count($trashtitles) == 0 ){
			echo '<ul><li>'.$langmessage['TRASH_IS_EMPTY'].'</li></ul>';
			return false;
		}
		
		echo '<table class="bordered">';
		echo '<tr>';
		echo '<th>'.$langmessage['title'].'</th>';
		echo '<th>'.$langmessage['options'].'</th>';
		echo '</tr>';
		
		foreach($trashtitles as $title){
			echo '<tr>';
			echo '<td>';
			echo str_replace('_',' ',$title);
			echo '</td>';
			echo '<td>';
			
			echo '<form method="post" action="">';
			echo '<input type="hidden" name="title" value="'.htmlspecialchars($title).'" />';
			echo '<input type="submit" name="cmd" value="'.$langmessage['restore'].'" />';
			
			echo ' &nbsp; ';
			
			echo '<input type="hidden" name="title" value="'.htmlspecialchars($title).'" />';
			echo '<input type="submit" name="cmd" value="'.$langmessage['delete'].'" />';
			echo '</form>';
			
			echo '</td>';
			echo '</tr>';
		}
		echo '</table>';
	}	
	

	
	function DeleteFromTrash(){
		global $dataDir,$langmessage;
		
		$title = gpFiles::CleanTitle($_POST['title']);

		$trash_file = $dataDir.'/data/_trash/'.$title.'.php';
		if( !file_exists($trash_file) ){
			message($langmessage['OOPS'].' (D1)');
			return;
		}
		
		if( !unlink($trash_file) ){
			message($langmessage['OOPS'].' (D2)');
			return;
		}
		
		message($langmessage['file_deleted']);
	}
	
		
	function GetFileType($file){
		ob_start();
		include($file);
		$contents = ob_get_clean();

		if( isset($meta_data['file_type']) ){
			return $meta_data['file_type'];
		}
		
		/* deprecated in 1.7 */
		if( isset($file_type) ){
			return $file_type;
		}
		
		return 'page';
	}
		
		
	
	function MoveFromTrash($trash_title,$new_title){
		global $dataDir;
		
		$trash_file = $dataDir.'/data/_trash/'.$trash_title.'.php';
		$new_file = $dataDir.'/data/_pages/'.$new_title.'.php';
		
		if( !file_exists($trash_file) ){
			return false;
		}
		
		if( !rename($trash_file,$new_file) ){
			return false;
		}
		return true;
	}	
}

