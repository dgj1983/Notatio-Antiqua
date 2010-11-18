<?php
defined('is_running') or die('Not an entry point...');


/* 
See also
/var/www/others/wordpress/wp-admin/update.php
/var/www/others/wordpress/wp-admin/includes/class-wp-upgrader.php
/var/www/others/wordpress/wp-admin/includes/class-wp-filesystem-ftpext.php
*/

includeFile('tool/RemoteGet.php');
includeFile('tool/FileSystem.php');

class update_class{
	
	//page variables
	var $label = 'gpEasy Updater';
	var $head = '';
	var $admin_css = '';
	var $contentBuffer = '';
	var $head_script = '';
	
	
	//update vars
	var $update_data = array();
	var $data_timestamp = 0;
	var $steps = array();
	var $replace_dirs = array();
	
	
	//content for template
	var $output_phpcheck = '';
	
	/* methods for $page usage */
	function GetContent(){
		global $langmessage;
		
		echo '<div id="gpx_content">';
		GetMessages();
		echo $this->contentBuffer;
		echo '</div>';
		
	}
	
	/* constructor */
	function update_class(){
		
		ob_start();
		$this->GetData();
		$this->Run();
		$this->contentBuffer = ob_get_clean();
	}
	
	/* static */
	//currently only returns information about the core package
	function VersionsAndCheckTime(&$new_versions){
		global $gpversion;
		
		$new_versions = array();

		if( !update_class::CheckPHP_Static() ){
			return update_class::CheckIncompatible();
		}
		
		update_class::GetDataStatic($update_data,$data_timestamp);
		
		//check core version
		if( isset($update_data['packages']['core']) ){
			$core_version = $update_data['packages']['core']['version'];
			
			if( $core_version && version_compare($gpversion,$core_version,'<') ){
				$new_versions['core'] = $core_version;
			}
		}
		
		if( $data_timestamp > 0 ){
			$diff = time() - $data_timestamp;
			
			//get new information
			//604800 one week
			if( $diff > 604800 ){
				//can't do SaveDataStatic() here, it will prevent RemoteCheck
				return 'checknow';
			}
		}
		
		return 'checklater';
	}
	
	
	function CheckIncompatible(){
		
		update_class::GetDataStatic($update_data,$data_timestamp);
		
		$diff = time() - $data_timestamp;
		if( $diff < 604800 ){
			return 'checklater';
		}
		if( empty($data_timestamp) || ($data_timestamp < 1) ){
			return 'checklater';
		}
			
		update_class::SaveDataStatic($update_data);
		return 'checkincompat';
	}
	
	
	
	function Run(){
		global $rootDir;
		
		$this->replace_dirs[] = $rootDir . '/include';
		$this->replace_dirs[] = $rootDir . '/themes';
		$this->replace_dirs[] = $rootDir . '/addons';
		
		if( !$this->CheckPHP() ){
			return;
		}

		
		$show = true;
		$cmd = common::GetCommand();
		switch($cmd){
			
			case 'embededcheck':
				$this->DoRemoteCheck();
				header('content-type: image/gif');
				echo base64_decode('R0lGODlhAQABAIAAAAAAAAAAACH5BAEAAAAALAAAAAABAAEAAAICRAEAOw=='); // send 1x1 transparent gif
				die();
			break;
			
			case 'checkremote':
				$this->DoRemoteCheck(true);
			break;
			
			
			case 'update';
				if( $this->Update() ){
					$show = false;
				}
			break;
		}
		
		if( $show ){
			$this->ShowStatus();
			
			echo '<h2>Status</h2>';
			$this->CheckStatus();
			echo $this->output_phpcheck;
		}
		
	}
	
	
	//
	//	$update_data['packages'][id] = array()
	//		
	//		--type--
	//		array['id'] = id of addon (unique across all types), "core" if type is "core"
	//		array['type'] = [core,plugin,theme]
	//		array['md5'] = expected md5 sum of zip file
	//		array['zip'] = name of file on remote server
	//		array['file'] = file on local system
	//		array['version'] = version of the package
	//
	//
	function GetData(){
		 update_class::GetDataStatic($this->update_data,$this->data_timestamp);
	}
	
	function GetDataStatic(&$update_data,&$data_timestamp){
		global $dataDir;
		
		$update_data = array();
		$file = $dataDir.'/data/_updates/updates.php';
		if( file_exists($file) ){
			require($file);
			$data_timestamp = $fileModTime;
		}
		
		$update_data += array('packages'=>array());
	}
	
	function SaveDataStatic(&$update_data){
		global $dataDir;
		$file = $dataDir.'/data/_updates/updates.php';
		gpFiles::SaveArray($file,'update_data',$update_data);
	}
	
	function SaveData(){
		update_class::SaveDataStatic($this->update_data);
	}
	
	
	function CheckPHP_Static(){
		global $rootDir,$dataDir,$langmessage;
		if( !gpRemoteGet::Test() ){
			return false;
		}
		if( $rootDir != $dataDir ){
			return false;
		}
		
		return true;
	}
	
	function CheckPHP(){
		global $rootDir,$dataDir,$langmessage;
		
		ob_start();

		$passed = true;
		echo '<table class="styledtable" cellspacing="0">';
		
		echo '<tr><th>';
			echo $langmessage['Test'];
			echo '</th><th>';
			echo $langmessage['Value'];
			echo '</th><th>';
			echo $langmessage['Expected'];
			echo '</th></tr>';
			
		echo '<tr><td>';
			echo 'RemoteGet';
			echo '</td><td>';
			if( gpRemoteGet::Test() ){
				echo '<span class="passed">'.$langmessage['True'].'</span>';
			}else{
				$passed = false;
				echo '<span class="failed">'.$langmessage['False'].'</span>';
			}
			
			echo '</td><td>';
			echo $langmessage['True'];
			echo '</td></tr>';
		
		echo '<tr><td>';
			echo 'Root Installation';
			echo '</td><td>';
			if( $rootDir == $dataDir ){
				echo '<span class="passed">'.$langmessage['True'].'</span>';
			}else{
				$passed = false;
				echo '<span class="failed">'.$langmessage['False'].'</span>';
			}
			
			echo '</td><td>';
			echo $langmessage['True'];
			echo '</td></tr>';
			
		echo '</table>';
		
		
		if( !$passed ){
			echo '<div class="inline_message">';
			echo $langmessage['Server_isnt_supported'];
			echo '</div>';
		}
		$this->output_phpcheck = ob_get_clean();
		
		return $passed;
	}
	
	function CheckStatus(){
		global $langmessage;
		
		$diff = time() - $this->data_timestamp;
		
		if( $this->data_timestamp > 0 ){
			echo '<p>';
			echo sprintf($langmessage['Software_updates_checked'],date('r',$this->data_timestamp));
			echo '</p>';
		}
		
		//one hour old
		if( $diff > 3600 ){
			echo '<p>';
			echo '<a href="?cmd=checkremote">'.$langmessage['Check Now'].'</a>';
			echo '</p>';
		}
		
	}
	
	function DoRemoteCheck($ForceCheck = false){
		global $langmessage;
		
		$diff = time() - $this->data_timestamp;
		
		//604800 one week
		if( !$ForceCheck && ($diff < 604800) ){
			return;
		}

		if( !$this->DoRemoteCheck2() ){
			message($langmessage['check_remote_failed']);
			$this->SaveData(); //installation may not be compat with RemoteGet()
			return;
		}
		
		$this->data_timestamp = time();
		$this->SaveData();			
		message($langmessage['check_remote_success']);
	}
	
	function DoRemoteCheck2(){
		global $config,$gpversion;

		$path = 'http://gpeasy.com/index.php/Special_Resources?cmd=check_versions';
		
		$result = gpRemoteGet::Get_Successful($path);
		
		if( !$result ){
			return false;
		}
		
		parse_str($result,$array);
		if( !is_array($array) || (count($array) < 1) ){
			return false;
		}
		
		$core_version = false;
		foreach($array as $id => $info){
			if( !is_numeric($id) ){
				continue;
			}
			if( !isset($info['type']) ){
				continue;
			}
			if( $info['type'] == 'core' ){
				$id = 'core';
				$core_version = $info['version'];
			}
			$this->update_data['packages'][$id] = $info;
		}
		
		
		
		//save some info in $config
/*
		if( $core_version && version_compare($gpversion,$core_version,'<') ){
			$config['updates']['core'] = time();
		}
		$config['updates']['checked'] = time();
*/

		//admin_tools::SaveConfig();
		
			
		return true;
	}
	
	
	
	
	function ShowStatus(){
		global $langmessage,$gpversion;
		
		if( !isset($this->update_data['packages']['core']) ){
			return;
		}
		$core_package = $this->update_data['packages']['core'];
		
		echo '<div class="inline_message">';
		if( version_compare($gpversion,$core_package['version'],'<') ){
			echo '<span class="green">';
			echo $langmessage['New_version_available'];
			echo ' &nbsp; ';
			echo '</span>';
			echo '<a href="?cmd=update"> » '.$langmessage['Update_Now'].' « </a>';
			
			echo '<table>';
			echo '<tr>';
			echo '<td>'.$langmessage['Your_version'].'</td>';
			echo '<td>'.$gpversion.'</td>';
			echo '</tr>';
			
			echo '<tr>';
			echo '<td>'.$langmessage['New_version'].'</td>';
			echo '<td>'.$core_package['version'].'</td>';
			echo '</tr>';
			echo '</table>';
			
		}else{
			echo $langmessage['UP_TO_DATE'];
			echo '<div>'.$langmessage['Your_version'];
			echo '  '.$gpversion;
			echo '</div>';
			echo '<div>';
			echo common::link('',$langmessage['return_to_your_site']);
			echo '</div>';
			
			//preliminary versions of the update software didn't run cleanup properly
			$this->RemoveUpdateMessage();
		}
		echo '</div>';
		
	}

	
	function Update(){
		global $langmessage,$gp_filesystem,$gpversion;

		
		if( !isset($this->update_data['packages']['core']) ){
			echo $langmessage['OOPS'];
			return false;
		}
			
		$core_package =& $this->update_data['packages']['core'];
		

		if( !isset($_POST['step']) ){
			$curr_step = 1;
		}else{
			$curr_step = (int)$_POST['step'];
		}
		
		//already up to date?
		if( ($curr_step < 4) && version_compare($gpversion,$core_package['version'],'>=') ){
			message($langmessage['UP_TO_DATE']);
			return false;
		}

		//filesystem
		$filesystem_method = false;
		if( isset($_POST['filesystem_method']) && gp_filesystem_base::set_method($_POST['filesystem_method']) ){
			$filesystem_method = $_POST['filesystem_method'];
		}elseif( $filesystem_method = $this->DetectFileSystem() ){
			$curr_step = 1;
		}else{
			message('Update Aborted: Could not establish a file writing method compatible with your server.');
			return false;
		}
		
		
		$this->steps[1] = $langmessage['step:prepare'];
		$this->steps[2] = $langmessage['step:download'];
		$this->steps[3] = $langmessage['step:unpack'];
		$this->steps[4] = $langmessage['step:clean'];
		
		echo '<div>'.$langmessage['update_steps'].'</div>';
		echo '<ol class="steps">';
		$curr_step_label = '';
		foreach($this->steps as $temp_step => $message ){
			
			if( $curr_step == $temp_step ){
				echo '<li class="current">'.$message.'</li>';
				$curr_step_label = $message;
			}elseif( $temp_step < $curr_step ){
				echo '<li class="done">'.$message.'</li>';
			}else{
				echo '<li>'.$message.'</li>';
			}
		}
		echo '</ol>';
		
		echo '<h3>'.$curr_step_label.'</h3>';
		
		
		echo '<form method="post" action="?cmd=update">';
		if( $filesystem_method ){
			echo '<input type="hidden" name="filesystem_method" value="'.htmlspecialchars($filesystem_method).'" />';
		}
		
		$done = false;
		$passed = false;
		switch($curr_step){
			case 4:
				$done = $this->CleanUp($core_package);
			break;
			case 3:
				echo '<ul>';
				$passed = $this->UnpackAndReplace($core_package);
				echo '</ul>';
			break;
			case 2:
				echo '<ul class="progress">';
				$passed = $this->DownloadSource($core_package);
				echo '</ul>';
			break;
			case 1:
				$passed = $this->GetServerInfo($core_package);
			break;
			
		}
		
		if( $gp_filesystem ){
			$gp_filesystem->destruct();
		}
		$this->SaveData(); //save any changes made by the steps


		if( !$done ){
			if( $passed ){
				echo '<input type="hidden" name="step" value="'.min(count($this->steps),$curr_step+1).'"/>';
				echo '<input type="submit" class="submit" name="" value="'.htmlspecialchars($langmessage['next_step']).'" />';
			}else{
				echo '<input type="hidden" name="step" value="'.$curr_step.'"/>';
				echo '<input type="submit" class="submit" name="" value="'.htmlspecialchars($langmessage['continue']).'..." />';
			}
		}
		
		echo '</form>';
		
		
		//echo showArray($this->update_data);
		//echo showArray($core_package);
		
		return true;
	}
	
	function DetectFileSystem(){
		
		$method = gp_filesystem_base::get_filesystem_method_all($this->replace_dirs);
		if( !$method ){
			return false;
		}
		
		gp_filesystem_base::set_method($method);
		return $method;
	}
	
	
	function CleanUp(&$package){
		global $langmessage, $config;
		
		if( !$this->RemoveUpdateMessage() ){
			return false;
		}
		
		
		//delete zip file
		if( !empty($package['file']) && file_exists($package['file']) ){
			unlink($package['file']);
		}

		echo '<ul>';
		echo '<li>'.$langmessage['settings_restored'].'</li>';
		echo '<li>'.$langmessage['software_updated'].'</li>';
		
		//get new package information .. has to be after deleting the zip
		echo '</ul>';
		
		
		echo '<h3>';
		echo common::link('','» '.$langmessage['return_to_your_site']);
		echo '</h3>';

		return true;
	}
	
	//remove updating message
	function RemoveUpdateMessage(){
		global $config,$langmessage;
		
		if( !isset($config['updating_message']) ){
			return true;
		}
		
		unset($config['updating_message']);
		if( !admin_tools::SaveConfig() ){
			message($langmessage['error_updating_settings']);
			return false;
		}
		
		return true;
	}
	
	
	//replace the /include, /themes and /addons folders
	function UnpackAndReplace(&$package){
		global $langmessage,$rootDir,$config,$gp_filesystem;
		
		$config['updating_message'] = $langmessage['sorry_currently_updating'];
		if( !admin_tools::SaveConfig() ){
			message($langmessage['error_updating_settings']);
			return false;
		}

		// Unzip uses a lot of memory, but not this much hopefully
		@ini_set('memory_limit', '256M');
		includeFile('thirdparty/pclzip-2-8-2/pclzip.lib.php');
		$archive = new PclZip($package['file']);
		$archive_files = $archive->extract(PCLZIP_OPT_EXTRACT_AS_STRING);
		if( $archive_files == false ){
			echo '<li>'.$langmessage['OOPS'].': '.$archive->errorInfo(true).'</li>';
			return false;
		}
		
		echo '<li>'.$langmessage['package_unpacked'].'</li>';
		
	
		//find $archive_root by finding Addon.ini
		$archive_root = false;
		foreach( $archive_files as $file ){
			if( strpos($file['filename'],'/Addon.ini') !== false ){
				$root = dirname($file['filename']);
				if( !$archive_root || ( strlen($root) < strlen($archive_root) ) ){
					$archive_root = $root;
				}
			}
		}
		$archive_root_len = strlen($archive_root);
		
		
		//write to $unpack_dest
		
		$unpack_dest = '';
		
		if( $gp_filesystem->connect_handler() !== true ){
			echo '<li>'.$langmessage['OOPS'].': (not connected)</li>';
			return false;
		}
		
		//$unpack_dest = $gp_filesystem->get_base_dir().'/__'.$package['version'];
		//$check_dest = $rootDir.'/__'.$package['version'];
		
		$unpack_dest = $gp_filesystem->get_base_dir();
		$check_dest = $rootDir;
		
		foreach( $archive_files as $file ){
			
			$filename = $file['filename'];
			
			//strip the archive root
			if( $archive_root ){
				if( strpos($filename,$archive_root) !== 0 ){
					trigger_error('$archive_root not in path');
					echo '<li>'.$langmessage['error_unpacking'].' (0)</li>';
					return false;
				}
				
				$filename = substr($filename,$archive_root_len);
			}
			
			//is it /include, /themes or /addons
			$filename = '/'.ltrim($filename,'/');
			$copy = false;
			if( strpos($filename,'/include/') === 0 ){
				$copy = true;
			}elseif( strpos($filename,'/themes/') === 0 ){
				$copy = true;
			}elseif( strpos($filename,'/addons/') === 0 ){
				$copy = true;
			}
			
			if( !$copy ){
				continue;
			}
			
			$translated_path = $unpack_dest.$filename;
			$check_dir = $check_dest.$filename;
		
			if( $file['folder'] ){
				$dir = $translated_path = rtrim($translated_path, '/');
				$check_dir = rtrim($check_dir, '/');
			}else{
				$dir = dirname($translated_path);
				$check_dir = dirname($check_dir);
			}
			
			
			if( !$this->CheckDir($check_dir,$dir) ){
				trigger_error('Could not create directory: '.$dir);
				echo '<li>'.$langmessage['error_unpacking'].' (1)</li>';
				return false;
			}
			
			if( $file['folder'] ){
				continue;
			}
			
			if( !$gp_filesystem->put_contents($translated_path,$file['content']) ){
				trigger_error('Could not create file: '.$translated_path);
				echo '<li>'.$langmessage['error_unpacking'].' (2)</li>';
				return false;
			}
			//echo '<li>copied: '.$check_dir.'</li>';
		}


		echo '<li>'.$langmessage['copied_new_files'].'</li>';

		return true;
	}
	
	function CheckDir($check_dir,$filesystem_dir){
		global $gp_filesystem;
		
		if( !file_exists($check_dir) ){
			$this->CheckDir(dirname($check_dir),dirname($filesystem_dir));
			return $gp_filesystem->mkdir($filesystem_dir);
		}
		return true;
	}

	
	
	function DownloadSource(&$package){
		global $langmessage;
		
		/* for testing
		 * $download = 'http://test.gpeasy.com/gpEasy_test.zip'; 
		 */
		$download = 'http://gpeasy.com/index.php/Special_gpEasy?cmd=download&version='.urlencode($package['version']).'&file='.urlencode($package['zip']);
		echo '<li>Downloading version '.$package['version'].' from gpEasy.com.</li>';
		
		
		$contents = gpRemoteGet::Get_Successful($download);
		if( !$contents || empty($contents) ){
			echo '<li>'.$langmessage['download_failed'].'</li>';
			return false;
		}
		echo '<li>'.$langmessage['package_downloaded'].'</li>';
		
		//check md5
		$md5 = md5($contents);
		if( $md5 != $package['md5'] ){
			echo '<li>'.$langmessage['download_failed_md5'].'</li>';
			return false;
		}
		
		echo '<li>'.$langmessage['download_verified'].'</li>';
		
		//save contents
		$tempfile = $this->tempfile();
		if( !gpFiles::Save($tempfile,$contents) ){
			message($langmessage['download_failed'].' (2)');
			return false;
		}
		
		$package['file'] = $tempfile;
		return true;
	}
	
	
	
	function GetServerInfo(&$package){
		global $langmessage,$gp_filesystem;
		
		$connect_result = $gp_filesystem->connect_handler();
		if( $connect_result === true ){
			echo '<ul class="progress">';
			echo '<li>';
			echo $langmessage['your_installation_is_ready_for_upgrade'];
			echo '</li>';
			echo '</ul>';
			return true;
			
		}elseif( isset($_POST['connect_values_submitted']) ){
			message($connect_result);
		}
		
		//not connected, show form
		echo '<table cellspacing="0" class="formtable">';
		echo '<tr><td>';
		
		$gp_filesystem->connectForm();
		
		echo '</table>';
		return false;
		
	}
	
	
	function tempfile(){
		global $dataDir;
		
		do{
			$tempfile = $dataDir.'/data/_temp/'.rand(1000,9000).'.zip';
		}while(file_exists($tempfile));
		
		return $tempfile;
	}

	
}

