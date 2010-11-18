<?php
defined("is_running") or die("Not an entry point...");

class admin_uploaded{
	
	var $baseDir;
	var $subdir = false;
	var $MaxUpload;
	var $thumbFolder;
	var $isThumbDir = false;
	var $browseString;
	var $queryString = '';
	var	$imgTypes;
	var $errorMessages = array();
	
	
	
	function admin_uploaded(){
		global $langmessage,$page;
		
		
		$this->browseString = 'Admin_Uploaded';
		$this->Init();
		
		$this->AdminCommands();
		
		$this->ShowPanel();
		$this->ShowFolder();
	}
	
	
	function AdminCommands(){
		$file_cmd = common::GetCommand('file_cmd');
		
		switch($file_cmd){
			case 'delete':
				$this->DeleteFile();
			break;
			
			case 'deleteconfirm':
				$this->DeleteConfirmed();
			break;
			
			case 'view':
				$this->View();
			break;
			case 'upload':
				$this->UploadFiles();
			break;
			case 'createdir':
				$this->CreateDir();
			break;
		}
		
	}
	
	function Init(){
		global $langmessage, $dataDir,$page;
		

		$this->baseDir = $dataDir.'/data/_uploaded';
		$this->thumbFolder = $dataDir.'/data/_uploaded/image/thumbnails';
		$this->currentDir = $this->baseDir;
		$page->admin_css .= '<link rel="stylesheet" type="text/css" href="'.common::GetDir('/include/css/browser.css').'" />';
		$page->head .= '<script type="text/javascript" language="javascript" src="'.common::GetDir('/include/js/browser_prefs.js').'"></script>';

		
		$this->AllowedExtensions = array('7z', 'aiff', 'asf', 'avi', 'bmp', 'csv', 'doc', 'fla', 'flv', 'gif', 'gz', 'gzip', 'jpeg', 'jpg', 'mid', 'mov', 'mp3', 'mp4', 'mpc', 'mpeg', 'mpg', 'ods', 'odt', 'pdf', 'png', 'ppt', 'pxd', 'qt', 'ram', 'rar', 'rm', 'rmi', 'rmvb', 'rtf', 'sdc', 'sitd', 'swf', 'sxc', 'sxw', 'tar', 'tgz', 'tif', 'tiff', 'txt', 'vsd', 'wav', 'wma', 'wmv', 'xls', 'xml', 'zip');
		$this->imgTypes = array('bmp'=>1,'png'=>1,'jpg'=>1,'jpeg'=>1,'gif'=>1,'tiff'=>1,'tif'=>1);
		

		
		//get the current path
		if( !empty($_REQUEST['dir']) ){
			$this->subdir = gpFiles::CleanArg($_REQUEST['dir']);
			$this->currentDir .= $this->subdir;
			
			if( !file_exists($this->currentDir) ){
				
				$action = common::GetUrl($this->browseString,$this->queryString.'dir='.rawurlencode($this->subdir));
				$mess = '<form action="'.$action.'" method="post">';
				$mess .= sprintf($langmessage['create_dir_mess'],$this->subdir);
				$mess .= '<input type="submit" name="aaa" value="'.$langmessage['create_dir'].'" class="gppost submit" />';
				$mess .= ' <input type="hidden" name="newdir" value="'.htmlspecialchars($this->subdir).'" />';
				$mess .= ' <input type="hidden" name="file_cmd" value="createdir" />';
				$mess .= ' <input type="submit" class="submit" name="file_cmd" value="'.$langmessage['cancel'].'" />';
				$mess .= '</form>';
				message($mess);
					
				$this->CheckDirectory();

			}
		}
		
		if( $this->subdir == '/' ){
			$this->subdir = false;
		}
		

		
		//is in thumbnail directory?
		if( strpos($this->currentDir,$this->thumbFolder) !== false ){
			$this->isThumbDir = true;
		}		
		
	}
	
	function CheckDirectory(){
		
		do{
			if( file_exists($this->currentDir) ){
				return;
			}
			
			$oldSub = $this->subdir;
			$this->subdir = dirname($this->subdir);
			$this->currentDir = $this->baseDir.$this->subdir;
			
		}while( $oldSub != $this->subdir );		
	}
	
	function CreateDir(){
		global $langmessage;
		
		$newDir = $_POST['newdir'];
		$newDir = str_replace('//','/',$newDir);
		if( empty($newDir) ){
			message($langmessage['OOPS'],'6');
			return;
		}

		$newDir = $this->currentDir .'/'. gpFiles::CleanArg($newDir);
		
		if( gpFiles::CheckDir($newDir) ){
			$temp = dirname($newDir);
			$len = strlen($this->baseDir);
			$this->subdir = substr($temp,$len);
			$this->CheckDirectory();
			message($langmessage['dir_created']);
		}else{
			message($langmessage['OOPS'],'7');
		}
	}
	

	function UploadPrep(){
		
		// not available till 4.3.1 and 5.0.3
		if( !defined('UPLOAD_ERR_NO_TMP_DIR') ){
			define('UPLOAD_ERR_NO_TMP_DIR',6);
		}
		
		// not available till 5.1.0
		if( !defined('UPLOAD_ERR_CANT_WRITE') ){
			define('UPLOAD_ERR_CANT_WRITE',7);
		}
		
		// not available till 5.2.0
		if( !defined('UPLOAD_ERR_EXTENSION') ){
			define('UPLOAD_ERR_EXTENSION',8);
		}		
	}
	function ReadableMax(){
		$value = ini_get('upload_max_filesize');
		
		if( empty($value) ){
			return '2 Megabytes';//php default
		}
		return $value;
	}
		
	
	function getByteValue(){
		$value = ini_get('upload_max_filesize');
		
		if( empty($value) ){
			return false;
			//$value = '2M';
		}

		if( is_numeric($value) ){
			return (int)$value;
		}
		
		$lastChar = $value{strlen($value)-1};
		$num = (int)substr($value,0,-1);
		
		switch(strtolower($lastChar)){
			
			case 'g':
				$num *= 1024;
			case 'm':
				$num *= 1024;
			case 'k':
				$num *= 1024;
			break;
		}
		return $num;

	}

		

	function UploadFiles(){
		global $langmessage;
		
		$uploadedList = array();
		$failedList = array();
		
		if( !isset($_FILES['userfiles']) ){
			message($langmessage['OOPS']);
			return;
		}
		
		foreach($_FILES['userfiles']['name'] as $key => $name){
			if( empty($name) ){
				continue;
			}

			$uploaded = $this->UploadFile($key);
			
			if( $uploaded !== false ){
				$uploadedList[] = $uploaded;
			}
		}
		
		if( count($uploadedList) ){
			message($langmessage['file_uploaded'], implode(', ',$uploadedList) );
		}
		
		if( count($this->errorMessages) > 0 ){
			foreach($this->errorMessages as $message ){
				message($message);
			}
		}
		
	}
		
	function UploadFile($key){
		global $langmessage,$rootDir,$config;
		

		$this->UploadPrep();
		
		$fName = $_FILES['userfiles']['name'][$key];
		
		switch( (int)$_FILES['userfiles']['error'][$key]){
			
			case UPLOAD_ERR_OK:
			break;
			
			case UPLOAD_ERR_FORM_SIZE:
			case UPLOAD_ERR_INI_SIZE:
				$this->errorMessages[] = sprintf($langmessage['upload_error_size'],$this->ReadableMax() );
			return false;
			
			case UPLOAD_ERR_NO_FILE:
			case UPLOAD_ERR_PARTIAL:
				$this->errorMessages[] = sprintf($langmessage['UPLOAD_ERROR_PARTIAL'], $fName);
			return false;
			
			case UPLOAD_ERR_NO_TMP_DIR:
				$this->errorMessages[] = sprintf($langmessage['UPLOAD_ERROR'], $fName);
				//trigger_error('Missing a temporary folder for file uploads.');
			return false;
			
			case UPLOAD_ERR_CANT_WRITE:
				$this->errorMessages[] = sprintf($langmessage['UPLOAD_ERROR'], $fName);
				//trigger_error('PHP couldn\'t write to the temporary directory: '.$fName);
			return false;
			
			case UPLOAD_ERR_EXTENSION:
				$this->errorMessages[] = sprintf($langmessage['UPLOAD_ERROR'], $fName);
				//trigger_error('File upload stopped by extension: '.$fName);
			return false;
		}
		
		
		$fName = $this->SanitizeName($fName);
		$from = $_FILES['userfiles']['tmp_name'][$key];
		$to = $this->currentDir.'/'.$fName;
		
		
		//dont' overwrite existing
		$num = 0;
		$nameParts = explode('.',$fName); 
		$fileType = array_pop($nameParts);
		$tempName = implode('.',$nameParts);
		while( file_exists($to) ){
			$fName = $tempName.'_'.$num.'.'.$fileType;
			$to = $this->currentDir.'/'.$fName;
			$num++;
		}
		$fileType = strtolower($fileType);

		
		//check file type
		if( $config['check_uploads'] && (count($this->AllowedExtensions) > 0)  ){
			if( !in_array( $fileType, $this->AllowedExtensions ) ){
				$this->errorMessages[] = sprintf($langmessage['UPLOAD_ERROR'], $fName);
				return false;
			}
		}
		
		
		//for images
		if( isset($this->imgTypes[$fileType]) && function_exists('imagetypes') ){
			require_once($rootDir.'/include/admin/tool_thumbnails.php');
			
			
			//create thumbnail now
			$currentThumbDir = $this->thumbFolder.$this->subdir;
			$thumbPath = $currentThumbDir.'/'.$fName.'.jpg';
			gpFiles::CheckDir($currentThumbDir);
			thumbnail::createSquare($from,$thumbPath,100,$fileType);
			
			
			
			//resize image if it's large
			if( thumbnail::maxArea($from,$to,$config['maximgarea']) ){
				return $fName;
			}
		}
		
		// for other files
		if( move_uploaded_file($from,$to) ){
			return $fName;
		}
		
		$this->errorMessages[] = sprintf($langmessage['UPLOAD_ERROR'], $fName);
		return false;
	}
	
	// Do a cleanup of the file name to avoid possible problems
	function SanitizeName( $sname ){
		global $config;
	
		$sname = stripslashes( $sname ) ;
	
		// Replace dots in the name with underscores (only one dot can be there... security issue).
		if( $config['check_uploads'] ){
			$sname = preg_replace( '/\\.(?![^.]*$)/', '_', $sname );
		}
	
		// Remove \ / | : ? * " < >
		if ( version_compare( phpversion(), '4.2.3',  '>=' ) ) {
			return preg_replace( '/\\\\|\\/|\\||\\:|\\?|\\*|"|<|>|[[:cntrl:]]/u', '_', $sname ) ;
		}else{
			return preg_replace( '/\\\\|\\/|\\||\\:|\\?|\\*|"|<|>|[[:cntrl:]]/', '_', $sname ) ;
		}
	}
	
	function DeleteFile(){
		global $langmessage;
		
		
		$file = $this->CheckFile();
		if( $file === false ){
			return;
		}
		
		echo '<div id="gp_file_delete" class="inline_box">';
			echo '<form action="'.common::GetUrl($this->browseString,$this->queryString.'dir='.rawurlencode($this->subdir)).'" method="post">';
			echo sprintf($langmessage['generic_delete_confirm'],'<i>'.htmlspecialchars($file).'</i>');
			echo ' <input type="hidden" name="file" value="'.htmlspecialchars($file).'" />';
			echo  ' <input type="hidden" name="file_cmd" value="deleteconfirm" />';
			echo  ' <input type="submit" name="aaa" value="'.$langmessage['delete'].'" class="submit"/>';
			//echo  ' <input type="submit" class="submit" name="file_cmd" value="'.$langmessage['cancel'].'" />';
			echo  '</form>';
		echo '</div>';
		
		die();
	}
	
	function DeleteConfirmed(){
		global $langmessage;
		
		if( $this->isThumbDir ){
			return false;
		}
		
		if( $_SERVER['REQUEST_METHOD'] != 'POST'){
			message($langmessage['OOPS'].' (0)');
			return false;
		}
		$file = $this->CheckFile();
		if( !$file ){
			return;
		}
		$fullPath = $this->currentDir.'/'.$file;
		
		if( !$fullPath ){
			//check file messages
			return;
		}
		
		if( is_dir($fullPath) ){
			
			$files = gpFiles::ReadDir($fullPath,false);
			if( count($files) > 0 ){
				message($langmessage['dir_not_empty']);
				return false;
			}
			
			if( !gpFiles::RmDir($fullPath) ){
			//if( !rmdir($fullPath) ){
				message($langmessage['OOPS'].' (1)');
				return;
			}
			
		}else{
			if( !unlink($fullPath) ){
				message($langmessage['OOPS'].' (2)');
				return;
			}
			
			$thumb = $this->thumbFolder.$this->subdir.'/'.$file.'.jpg';
			if( file_exists($thumb) ){
				unlink($thumb);
			}
			
		}
		message($langmessage['file_deleted']);
	}
	
	function CheckFile(){
		global $langmessage;
		
		if( empty($_REQUEST['file']) ){
			message($langmessage['OOPS'].'(2)');
			return false;
		}
			
		$file = $_REQUEST['file'];
		if( (strpos($file,'/') !== false ) || (strpos($file,'\\') !== false) ){
			message($langmessage['OOPS'].'(3)');
			return false;
		}
		$fullPath = $this->currentDir.'/'.$file;
		if( !file_exists($fullPath) ){
			message($langmessage['OOPS'].'(4)');
			return false;
		}
		
		if( strpos($fullPath,$this->baseDir) === false ){
			message($langmessage['OOPS'].' (5)');
			return false;
		}
		return $file;
		//return $fullPath;
	}
	
	
	//using because editing requires the file_cmd=edit for each link
	function BrowseLink($label,$folder=false,$args='',$attrs=''){
		
		$queryString = $this->queryString;
		
		if( $folder !== false ){
			$queryString .= 'dir='.rawurlencode($folder);
		}
		if( !empty($args) ){
			$queryString .= '&'.$args;
		}
		
		return common::Link($this->browseString,$label,$queryString,$attrs);
	}
	
	
	function ShowLocation(){
		global $langmessage;
		
		echo '<span class="location">';
		
		
		if( !empty($this->subdir) ){
			echo '<span>/</span>';
			echo $this->BrowseLink($langmessage['uploaded_files'],'','',' class="left" ');
		
		
			$dirs = str_replace('\\','/',$this->subdir);
			$dirs = trim($dirs,'/');
			$dirs = explode('/',$dirs);
			$current = '';
			foreach($dirs as $dir){
				$current .= '/'.$dir;
				echo '<span>/</span>';
				echo $this->BrowseLink($dir,$current);
			}
		}else{
			echo '<span>/</span>';
			echo $this->BrowseLink($langmessage['uploaded_files'],'');
		}

		echo '</span>';
		
	}
	
	
	function ShowPanel(){
		global $langmessage;
		if( !file_exists($this->currentDir) ){
			return;
		}

		echo '<div id="gp_file_browser_nav">';

			echo ' <span class="actions">';
				if( !empty($this->subdir) && !$this->isThumbDir ){

					$img = '<img src="'.common::GetDir('/include/imgs/add.png').'" height="16" width="16" alt=""/> ';
					echo $this->BrowseLink($img.$langmessage['upload_files'].'...',$this->subdir,'',' name="inline_box" rel="#gp_upload"');
					
					$img = '<img src="'.common::GetDir('/include/imgs/add.png').'" height="16" width="16" alt=""/> ';
					echo $this->BrowseLink($img.$langmessage['create_dir'].'...',$this->subdir,'',' name="inline_box" rel="#gp_create_dir"');
				}
			echo '</span>';			
			
			$this->ShowLocation();
			
			echo '<div style="clear:both"></div>';
		echo '</div>';
		
		echo '<div class="display_options">';
		
		$options['browser_list'] = 'application_view_list.png';
		$options['browser_icons_small'] = 'application_view_icons.png';
		$options['browser_icons'] = 'application_view_tile.png';
		
		foreach($options as $option => $img){
			$class = '';
			if( $GLOBALS['gpAdmin']['browser_display'] == $option ){
				$class = 'selected';
			}
			echo '<a href="#" name="browser_pref" rel="'.$option.'" class="'.$class.'"><img src="'.common::GetDir('/include/imgs/'.$img).'" alt="" height="16" width="16" /></a> ';
		}
		echo '</div>';
		
	}
		
		
	
	function ShowFolder(){
		global $langmessage;
		
		if( !file_exists($this->currentDir) ){
			return;
		}
		
		echo '<div class="'.$GLOBALS['gpAdmin']['browser_display'].' browser_class">';
		
			if( !empty($this->subdir) ){
				echo "\n<div class=\"list_item expand_child\">";
				echo '<div class="gen_links">';
					$img = '<img src="'.common::GetDir('/include/imgs/blank.gif').'" alt="" class="icon folder_up" /> ';
					echo $this->BrowseLink($img,dirname($this->subdir));
					echo '<div>';
					echo $this->BrowseLink('..',dirname($this->subdir));
					echo '</div>';
				echo '</div>';
				echo '</div>';
			}
		
			$this->ShowFiles();
		echo '</div>';
		
		$this->HiddenForms();
	}
	
	function ShowFiles(){
		global $langmessage,$page;
		
		$allFiles = gpFiles::ReadFolderAndFiles($this->currentDir);
		if( $allFiles === false ){
			return;
		}

	
		list($folders,$files) = $allFiles;
		
		foreach($folders as $folder){
			echo '<div class="list_item expand_child">';
			echo '<div class="gen_links">';
				$img = '<img src="'.common::GetDir('/include/imgs/blank.gif').'" alt="" class="icon folder" /> ';
				echo $this->BrowseLink($img,$this->subdir.'/'.$folder);
				echo '<div>';
				echo $this->BrowseLink($folder,$this->subdir.'/'.$folder);
				echo '</div>';
				
			echo '</div>';
			echo '<div class="more_links">';
				$this->File_Link_Right($folder,false); //delete
			echo '</div>';
			echo '</div>';
		}
		
		
		foreach($files as $file){
			
			
			$fileUrl = common::GetDir_Prefixed('/data/_uploaded'.$this->subdir.'/'.$file);
			//$fileUrl = common::GetDir('/data/_uploaded'.$this->subdir.'/'.$file);
			
			$type = $this->GetFileType($file);
			
			$is_img = false;
			if( isset($this->imgTypes[$type]) ){
				$is_img = true;
			}
			
			$draggable = $this->DraggableLink($file,$is_img);
			if( $draggable !== false ){
				echo '<div class="list_item expand_child draggable_element">';
				echo $draggable;
			}else{
				echo '<div class="list_item expand_child not_draggable">';
			}
			
			
			echo '<input type="hidden" name="fileUrl" value="'.htmlspecialchars($fileUrl).'" />'; //for admin_browser.php/.js
			
			
			echo '<div class="gen_links">';
				if( $is_img ){
					$this->Link_Img($file,$fileUrl);
					
				}else{
					echo '<img src="'.common::GetDir('/include/imgs/files_100.png').'" height="100" width="100" alt="" class="icon" /> ';
					echo '<div>';
					echo '<a href="'.$fileUrl.'" target="_blank" title="'.$file.'">';
					echo $file;
					echo '</a>';
					echo '</div>';
				}
				
			echo '</div>';
			
			
		
			echo '<div class="more_links">';
				$this->File_Link_Right($file,$is_img,$fileUrl); //delete
			echo '</div>';
			

			
			echo '</div>';
		}
		
		
		echo '<div style="clear:both"></div>';
	}
	
	

		
	function HiddenForms(){
		global $langmessage;
		
		$submit_class = ' class="submit"';
		
		//hidden forms
		echo '<div style="display:none">';
		if( !empty($this->subdir) && !$this->isThumbDir ){
			
				//create directory
				echo '<div id="gp_create_dir" class="inline_box">';
				$img = '<img src="'.common::GetDir('/include/imgs/folder.png').'" height="16" width="16" alt=""/> ';
				echo '<h2>'.$img.$langmessage['create_dir'].'</h2>';
				echo '<form action="'.common::GetUrl($this->browseString,$this->queryString.'dir='.rawurlencode($this->subdir)).'" method="post"  >';
					echo '<p>';
					echo ' <input type="text" class="text input" name="newdir" size="30" />';
					echo '</p>';
					echo '<p>';
					echo ' <input type="hidden" name="file_cmd" value="createdir" />';
					echo '<input type="submit" name="aaa" value="'.$langmessage['create_dir'].'" class="submit"/>';
					echo '</p>';
				echo '</form>';
				echo '</div>';
			
			
				//upload file
				echo '<div id="gp_upload" class="inline_box" >';
				$img = '<img src="'.common::GetDir('/include/imgs/page.png').'" height="16" width="16" alt=""/> ';
				echo '<h2>'.$img.$langmessage['upload_files'].'</h2>';
				echo '<form action="'.common::GetUrl($this->browseString,$this->queryString.'dir='.rawurlencode($this->subdir)).'" method="post"  enctype="multipart/form-data"  >';
				
					$max = $this->getByteValue();
					if( $max !== false ){
						echo '<input type="hidden" name="MAX_FILE_SIZE" value="'.$max.'" />';
					}
					echo ' <input type="hidden" name="file_cmd" value="upload" />';
					
					echo '<div id="gp_upload_list">';
					
						echo '<p>';
						echo '<input type="file" class="input text" name="userfiles[]" size="20" onchange="gpadmin.uploadFile(this)"/>';
						echo '</p>';
					
					echo '</div>';
					
					echo '<p id="gp_upload_field">';
					echo '<input type="file" class="input text" name="userfiles[]" size="20" onchange="gpadmin.uploadFile(this)"/>';
					echo '</p>';
					
					echo '<p>';
					echo '<input type="submit" class="submit" value="'.$langmessage['upload'].'" />';
					echo '</p>';
				echo '</form>';
				echo '</div>';

		}
		
		echo '</div>'; //end hidden forms
	}
	
	
	function DraggableLink(){
		return false;
	}

	
	function Link_Img($file,$fileUrl){
		
		echo '<a href="'.$fileUrl.'" name="gallery" rel="gallery_uploaded" title="'.$file.'">';
		if( !$this->isThumbDir ){
			echo ' <img src="'.common::GetDir('/data/_uploaded/image/thumbnails'.$this->subdir.'/'.$file.'.jpg').'" height="100" width="100" alt="" class="icon" />';
		}else{
			echo ' <img src="'.$fileUrl.'" height="100" width="100" alt="" class="icon" />';
		}
		echo '</a>';
		
		echo '<div>';
		echo '<a href="'.$fileUrl.'" name="gallery" rel="gallery_uploaded" title="'.$file.'">';
		echo $file;
		echo '</a>';
		echo '</div>';
	}
	
	function Link_Select($fileUrl){
		//nothing
	}
	

	function File_Link_Left($file,$is_img){
		global $langmessage;
		
		if( $is_img ){
			echo '<a href="'.common::GetDir('/data/_uploaded'.$this->subdir.'/'.$file).'" name="gallery" rel="gallery_uploaded" title="'.$file.'">';
		}else{
			echo '<a href="'.common::GetDir('/data/_uploaded'.$this->subdir.'/'.$file).'" >';
		}
		echo $file;
		echo '</a>';
	}
	
	function File_Link_Right($file,$is_img,$img_url=false){
		global $langmessage;
		
		if( $is_img ){
			//
		}
		
		if( !$this->isThumbDir ){
			$label = '<img src="'.common::GetDir('/include/imgs/delete.png').'" alt="" height="16" width="16" /> ';
			$label .= '<span>'.$langmessage['delete'].'</span>';
			echo $this->BrowseLink($label,$this->subdir,'file_cmd=delete&file='.urlencode($file),' name="ajax_box" ');
			
		}else{
			echo '<div>&nbsp;</div>'; //for display
		}
	}
	
	function GetFileType($file){
		$nameParts = explode('.',$file);
		$fileType = array_pop($nameParts);
		return strtolower($fileType);
	}
	
}

