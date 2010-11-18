<?php
defined('is_running') or die('Not an entry point...');

class admin_rm{
	
	//should have a way to switch them back!
	function admin_rm(){
		global $langmessage;
		$cmd = common::GetCommand();
		switch($cmd){
			case 'continue':
				$this->uninstall();
			break;
			
			case 'restore':
				$this->restore();
			break;
		}
		
		echo '<h2>Uninstall Preparation</h2>';
		echo '<form class="renameform" action="'.common::GetUrl('Admin_Uninstall').'" method="post">';
		echo '<p>';
		echo 'For some installations, you won\'t be able to delete gpEasy\'s data files from your server untill the access permissions have been changed. ';
		echo ' This script will change file permissions for files and folders in the /data directory to 0777.';
		echo ' <br/><em>You should not continue unless you plan on deleting all gpEasy files from your server.</em>';
		echo '<input type="hidden" name="cmd" value="continue" />';
		echo ' <input type="submit" class="submit" name="aaa" value="'.$langmessage['continue'].'" />';
		//echo ' <input type="submit" class="submit" name="cmd" value="'.$langmessage['cancel'].'" />';
		echo '</p>';
		echo '</form>';
		
		echo '<h2>Change Your Mind?</h2>';
		echo '<form class="renameform" action="'.common::GetUrl('Admin_Uninstall').'" method="post">';
		echo 'You can restore the file permissions for added security here: ';
		echo '<input type="hidden" class="submit" name="cmd" value="restore" />';
		echo '<input type="submit" class="submit" name="aaa" value="'.$langmessage['restore'].'" />';
		echo '</form>';
		
	}
	
	function restore(){
		global $dataDir;
		
		$chmodDir = $dataDir.'/data';
		$this->DirPermission = 0777; //0755;
		$this->FilePermission = 0666; //0644; //0600 is too restrictive
		$this->chmoddir($chmodDir);
		message('The file permissions have been updated.');
	}
	
	function uninstall(){
		global $dataDir;
		
		$chmodDir = $dataDir.'/data';
		$this->DirPermission = 0777;
		$this->FilePermission = 0777; //0666;
		$this->chmoddir($chmodDir);
		
		message('The file permissions have been updated.');
	}
	
	function chmoddir($dir){
		global $config;
		
		
		$files = array();
		if( !file_exists($dir) ){
			return $files;
		}
		$dh = @opendir($dir);
		if( !$dh ){
			return $files;
		}
		
		while( ($file = readdir($dh)) !== false){
			if( ($file == '.') || ($file == '..') ){
				continue;
			}
			$fullPath = $dir.'/'.$file;
			
			
			if( is_dir($fullPath) ){
				if( !isset($config['useftp']) ){
					
					//dirs will already be 0777 when using ftp
					if( !@chmod($fullPath,$this->DirPermission) ){
						continue;
					}
				}
				
				$this->chmoddir($fullPath);
				
			}else{
				@chmod($fullPath,$this->FilePermission);
			}
		}
		
	}	
	
	
}
