<?php
defined("is_running") or die("Not an entry point...");

includeFile('admin/admin_addons_tool.php');

class admin_addon_install extends admin_addons_tool{
	
	var $develop;
	
	//for remote install
	var $addon_type;
	
	function admin_addon_install($cmd){
		global $langmessage;
		
		$this->GetAddonData(); //for addonHistory

		if( isset($_REQUEST['mode']) && ($_REQUEST['mode'] == 'dev') ){
			if( !function_exists('symlink') ){
				message($langmessage['OOPS']);
				return false;
			}
			$this->develop = true;
		}
		
		echo '<h2>'.$langmessage['install'].'</h2>';
		
		switch($cmd){
			case 'remote_install':
				$this->RemoteInstall();
			break;
			
			case 'remote_install2':
				$this->RemoteInstall2();
			break;
			
			case 'step1':
				$this->Install_Step1();
			break;
			case 'step2':
				$this->Install_Step2();
			break;
			case 'step3':
				$this->Install_Step3();
			break;
		}
	}
	
	
	
	function RemoteInstall(){
		
		if( !admin_tools::CanRemoteInstall() ){
			message($langmessage['OOPS']);
			return false;
		}
		
		//todo to do
		message('Themes and addons! .. data/_addon_themes'); //or do we try and make the /themes folder writable?
		message('Make sure there\'s not already an addon of the same name installed');
		message('Need to check "from" and addonName value!');
		message('Use md5 check to verify content!');
		message('"Cancel" shows the rating message');
		message('See setMemoryForImage() function in ckFinder');
		message('install vs upgrade');
		message('Browser bookmark/reload features');
		message('Warn users about untrusted/untested content');
		
		$addonName =& $_REQUEST['name'];
		
		if( empty($_REQUEST['from']) ){
			message($langmessage['OOPS']);
			return false;
		}
		
		
		echo '<form method="post">';
		echo '<input type="hidden" name="from" value="'.htmlspecialchars($_REQUEST['from']).'" />';
		echo '<input type="hidden" name="name" value="'.htmlspecialchars($_REQUEST['name']).'" />';
		echo '<input type="hidden" name="type" value="'.htmlspecialchars($_REQUEST['type']).'" />';
		
		echo 'You have chosen to install <em>'.$addonName.'</em> from gpEasy.com.';
		
		echo '<input type="hidden" name="cmd" value="remote_install2" />';
		echo ' <input type="submit" class="submit" name="" value="Continue" />';
		echo ' <input type="submit" class="submit" name="cmd" value="Cancel" />';
		
		echo '</form>';
	}
	
	
	function RemoteInstall2(){
		global $dataDir,$langmessage;
		
		if( !admin_tools::CanRemoteInstall() ){
			message($langmessage['OOPS']);
			return false;
		}
		
		
		$addonName =& $_POST['name'];
		$get = 'http://gpeasy.loc'.str_replace(' ','%20',$_REQUEST['from']);
		
		if( $_POST['type'] == 'plugin' ){
			$addonFolder = $dataDir.'/data/_addoncode/'.$addonName;
		}else{
			$addonFolder = $dataDir.'/data/_addon_themes/'.$addonName;
		}
			
		/*
		if( file_exists($addonFolder) ){
			message('upgrade (not install)');
		}else{
			message('install (not upgrade)');
		}
		*/
		
		$fp = fopen($get,'rb');
		if( !$fp ){
			message($langmessage['OOPS']);
			return false;
		}
		
		$files = '';
		while(!feof($fp)) {
			$files .= fread($fp, 8192);
		}
		fclose($fp);
		
		$md5 = md5($files); //for the check
		$files = gzinflate($files);
		$files = unserialize($files);
		$this->write_package($addonFolder,$files);
		
		if( $this->addon_type == 'theme' ){
			$message = sprintf($langmessage['installed'],$addonName);
			$message .= 'You may now preview this theme... which colors?';
			//http://gpeasy.loc/glacier/index.php/Admin_Theme?cmd=view&theme=One_Point_5/Black
			message($message);
		}else{
			$this->InstallForm($addonName,'3');
		}
	}
	
	function write_package($dir,&$files){
		
		if( !gpFiles::CheckDir($dir) ){
			message('Couldn\'t create the directory: '.$dir);
			return false;
		}
		
		foreach($files as $file => $contents){
			$fullPath = $dir.$file;
			
			if( strpos($file,'/') !== false ){
				$test = dirname($fullPath);
				if( !gpFiles::CheckDir($test) ){
					message('Couldn\'t create the directory: '.$dir);
					return false;
				}
			}
			gpFiles::Save($fullPath,$contents);
		}
	}
	
	
	function InstallForm($addonName,$step){
		global $langmessage;
		
		echo '<form action="'.common::GetUrl('Admin_Addons').'" method="post">';
		$this->Install_Progress($step-1);
		echo '<input type="hidden" name="addon" value="'.htmlspecialchars($addonName).'" />';
		echo '<input type="hidden" name="cmd" value="step'.$step.'" />';
		echo ' <input type="submit" class="submit" name="aaa" value="'.$langmessage['continue'].'" />';
		if( $this->develop ){
			echo ' <input type="hidden" name="mode" value="dev" />';
			echo ' <em>'.$langmessage['developer_install'].'</em>';
		}
		echo '</form>';
	}	
	
	
	function Install_Progress($step){
		$steps = 3;
		
		echo 'Progress: ';
		echo '<img src="'.common::GetDir('/include/imgs/blank.gif').'" alt="" height="9" width="'.($step*25).'" class="progressDone" />';
		echo '<img src="'.common::GetDir('/include/imgs/blank.gif').'" alt="" height="9" width="'.( ($steps-$step)*25 ).'" class="progressToDo"/> ';
	}
	
	/*
	 * 
	 * Step 1
	 * 
	 */
	

	function Install_Step1(){
		global $dataDir, $langmessage, $gpversion,$config;
		
		$addonName = $_REQUEST['addon'];
		$addonFolder = $dataDir.'/addons/'.$addonName;
		
		echo '<p>';
		echo 'Checking addon folder: <em>'.$addonFolder.'</em>';
		echo '</p>';
		
		if( !file_exists($addonFolder) ){
			echo '<p>';
			echo 'Could not find find folder:<em>'.$addonFolder.'</em>';
			echo '</p>';
			return false;
		}
		
		
		$iniFile = $addonFolder.'/Addon.ini';
		if( !file_exists($iniFile) ){
			echo '<p>';
			echo 'Could not find find Addon.ini:<em>'.$iniFile.'</em>';
			echo '</p>';
			return false;
		}
		
		$Install = gp_ini::ParseFile($iniFile);
		if( !$Install ){
			message('Oops, there was an error parsing the ini file for this addon.');
			return false;
		}
		if( !isset($Install['Addon_Name']) ){
			message('<i>Addon_Name</i> must be set in the Addon.ini of your addon.');
			return false;
		}
		if( !isset($Install['Addon_Unique_ID']) ){
			echo '<p>Warning: This add-on does not have a Unique_ID which will prevent you from being able to review it on gpEasy.com.</p>';
		}
		
		foreach($config['addons'] as $folder => $data){
			if( $folder == $addonName ){
				continue;
			}
			if( $data['name'] == $Install['Addon_Name'] ){
				message('An addon with the name <em>'.$Install['Addon_Name'].'</em> is already installed.');
				return false;
			}
		}
		
		
		//Check Versions
		if( isset($Install['min_gpeasy_version']) ){
			if(version_compare($Install['min_gpeasy_version'], $gpversion,'>') ){
				$langmessage['min_version'] = 'A minimum of gpEasy %s is required for this add-on.';
				message($langmessage['min_version'],$Install['min_gpeasy_version'],$gpversion);
				return false;
			}
		}
		
		
		//Install_Check()
		$checkFile = $addonFolder.'/Install_Check.php';
		if( file_exists($checkFile) ){
			include($checkFile);
		}
		
		if( function_exists('Install_Check') ){
			if( !Install_Check() ){
				return false;
			}
		}

		
		$this->InstallForm($addonName,'2');
	}
	
	
	
	
	/*
	 * 
	 * Step 2
	 * 
	 */
	
	
	//copy files over to the /data/_addoncode folder
	function Install_Step2(){
		global $langmessage, $dataDir;
		
		echo '<p>';
		echo 'Copying Addon Files';
		echo '</p>';
		
		
		
		$addonName = $_REQUEST['addon'];
		$fromFolder = $dataDir.'/addons/'.$addonName;
		$toFolder = $dataDir.'/data/_addoncode/'.$addonName;
		
		
		
		if( $this->develop ){
			
			if( !file_exists($toFolder)  ){

				if( !symlink($fromFolder,$toFolder) ){
					message($langmessage['OOPS']);
					$this->InstallForm($addonName,'2');
					return false;
				}
			}
			
		}else{
			//message('from: '.$fromFolder.' to '.$toFolder);
			if( !admin_addon_install::CopyAddonDir($fromFolder,$toFolder) ){
				message($langmessage['OOPS']);
				$this->InstallForm($addonName,'2');
				return false;
			}
		}

		$this->InstallForm($addonName,'3');
		return true;
	}

	
	
	function CopyAddonDir($fromDir,$toDir){
		
		$dh = @opendir($fromDir);
		if( !$dh ){
			return false;
		}
		
		if( !gpFiles::CheckDir($toDir) ){
			message('Copy failed: '.$fromDir.' to '.$toDir);
			return false;
		}
		
		
		while( ($file = readdir($dh)) !== false){
			
			if( strpos($file,'.') === 0){
				continue;
			}
			
			$fullFrom = $fromDir.'/'.$file;
			$fullTo = $toDir.'/'.$file;
			
			
			//directories
			if( is_dir($fullFrom) ){
				if( !admin_addon_install::CopyAddonDir($fullFrom,$fullTo) ){
					closedir($dh);
					return false;
				}
				continue;
			}
			
			//files
			//If the destination file already exists, it will be overwritten. 
			if( !copy($fullFrom,$fullTo) ){
				message('Copy failed: '.$fullFrom.' to '.$fullTo);
				closedir($dh);
				return false;
			}
		}
		closedir($dh);

		
		return true;
	}


	
	/*
	 * 
	 * Step 3
	 * 		Update configuration
	 * 
	 */
	
	function Install_Step3(){
		global $config, $langmessage, $gptitles, $dataDir;
		
		
		echo '<p>';
		echo 'Configuring...';
		echo '</p>';
		
		
		$addonDir = $_REQUEST['addon'];
		$toFolder = $dataDir.'/data/_addoncode/'.$addonDir;
		$iniFile = $toFolder.'/Addon.ini';
		
		//install variables
		$variables = array(	'{$addon}'=>$addonDir,
							'{$addonRelativeData}'=> common::GetDir('/data/_addondata/'.$addonDir),
							'{$addonRelativeCode}'=> common::GetDir('/data/_addoncode/'.$addonDir),
							);
		
		$Install = gp_ini::ParseFile($iniFile,$variables);
		

		echo '<p>';
		echo 'Adding Gadgets';
		echo '</p>';
		
		//needs to be before other gadget functions
		$installedGadgets = $this->GetInstalledComponents($config['gadgets'],$addonDir);
		
		$gadgets = $this->ExtractFromInstall($Install,'Gadget:');
		$gadgets = $this->CleanGadgets($gadgets,$addonDir);
		$this->PurgeExisting($addonDir,$config['gadgets'],$gadgets);
		$this->AddToConfig($addonDir,$config['gadgets'],$gadgets);
		
		//remove gadgets that were installed but are no longer part of package
		$gadgetNames = array_keys($gadgets);
		$toRemove = array_diff($installedGadgets,$gadgetNames);
		$this->RemoveFromHandlers($toRemove);
		
		//add new gadgets to GetAllGadgets handler
		$toAdd = array_diff($gadgetNames,$installedGadgets);
		$this->AddToHandlers($toAdd);


		echo '<p>';
		echo 'Adding Links';
		echo '</p>';

		//admin links
		$Admin_Links = $this->ExtractFromInstall($Install,'Admin_Link:');
		$Admin_Links = $this->CleanLinks($Admin_Links,'Admin_');
		$this->PurgeExisting($addonDir,$config['admin_links'],$Admin_Links);
		$this->AddToConfig($addonDir,$config['admin_links'],$Admin_Links);
		
		
		//special links
		$installedLinks = $this->GetInstalledComponents($gptitles,$addonDir);
		
		$Special_Links = $this->ExtractFromInstall($Install,'Special_Link:');
		$Special_Links = $this->CleanLinks($Special_Links,'Special_','special');
		$this->PurgeExisting($addonDir,$gptitles,$Special_Links);
		$this->AddToConfig($addonDir,$gptitles,$Special_Links);
		
		//Remove links from $gpmenu
		$LinkNames = array_keys($Special_Links);
		$toRemove = array_diff($installedLinks,$LinkNames);
		$this->CleanMenu($toRemove);
		
		
		//generic hooks
		$this->AddHooks($Install,$addonDir);

		
		//general configuration
		$config['addons'][$addonDir]['name'] = $Install['Addon_Name'];
		if( isset($Install['Addon_Version']) ){
			$config['addons'][$addonDir]['version'] = $Install['Addon_Version'];
		}elseif( isset($config['addons'][$addonDir]['version']) ){
			unset($config['addons'][$addonDir]['version']);
		}
		
		//addon id
		if( isset($Install['Addon_Unique_ID']) && is_numeric($Install['Addon_Unique_ID']) ){
			$config['addons'][$addonDir]['id'] = $Install['Addon_Unique_ID'];
		}elseif( isset($config['addons'][$addonDir]['id']) ){
			unset($config['addons'][$addonDir]['id']);
		}
		
		//editable text
		if( isset($Install['editable_text']) ){
			$config['addons'][$addonDir]['editable_text'] = $Install['editable_text'];
		}elseif( isset($config['addons'][$addonDir]['editable_text']) ){
			unset($config['addons'][$addonDir]['editable_text']);
		}
		
		//html_head
		if( isset($Install['html_head']) ){
			$config['addons'][$addonDir]['html_head'] = $Install['html_head'];
		}elseif( isset($config['addons'][$addonDir]['html_head']) ){
			unset($config['addons'][$addonDir]['html_head']);
		}
		
		
		
		if( !admin_tools::SaveAllConfig() ){
			message($langmessage['OOPS']);
			$this->InstallForm($addonDir,'3');
			return false;
		}
		
		
		$this->Install_Progress(4);	
		echo '<p>';
		echo '<form action="'.common::GetUrl('Admin_Addons').'" method="post">';
		echo sprintf($langmessage['installed'],$Install['Addon_Name']);
		echo ' <input type="submit" class="submit" name="aaa" value="'.$langmessage['continue'].'" />';
		echo '</form>';
		echo '</p>';
		
		
		/*
		 * History
		 */
		
		
		$history = array();
		$history['name'] = $config['addons'][$addonDir]['name'];
		$history['action'] = 'installed';
		if( isset($config['addons'][$addonDir]['id']) ){
			$history['id'] = $config['addons'][$addonDir]['id'];
		}
		$history['time'] = time();
		
		$this->addonHistory[] = $history;
		$this->SaveAddonData();	

	}
	
	
	function AddHooks($Install,$addonDir){
		
		$installed = array();
		foreach($Install as $hook => $hook_args){
			switch($hook){
				case 'Gallery_Style':
					if( $this->AddHook($hook,$hook_args,$addonDir) ){
						$installed[$hook] = $hook;
					}
				break;
			}
		}
		
		//Remove Old Hooks
		$this->CleanHooks($addonDir,$installed);
		
	}
	
	
	function CleanHooks($addonDir,$keep_hooks = array()){
		global $config;
		
		if( !isset($config['hooks']) ){
			return;
		}
		
		foreach($config['hooks'] as $hook_name => $hook_array){
			
			foreach($hook_array as $hook_dir => $hook_args){
				
				//not cleaning other addons
				if( $hook_dir != $addonDir ){
					continue;
				}
				
				if( !isset($keep_hooks[$hook_name]) ){
					unset($config['hooks'][$hook_name][$hook_dir ]);
					//message('remove this hook: '.$hook_name);
				}
			}
		}
		
		//reduce further if empty
		foreach($config['hooks'] as $hook_name => $hook_array){
			if( empty($hook_array) ){
				unset($config['hooks'][$hook_name]);
			}
		}
		
	}
	
	
	
	function AddHook($hook,$hook_args,$addonDir){
		global $config;
		
		if( !isset($hook_args['script']) ){
			return false;
		}
		if( !isset($hook_args['method']) ){
			return false;
		}
		
		$add['script'] = '/data/_addoncode/'.$addonDir.'/'.$hook_args['script'];
		$add['method'] = $hook_args['method'];
		$add['addonDir'] = $addonDir;
		$config['hooks'][$hook][$addonDir] = $add;
		
		return true;
	}
	
	
	//extract the configuration type (extractArg) from $Install
	function ExtractFromInstall(&$Install,$extractArg){
		if( !is_array($Install) || (count($Install) <= 0) ){
			return array();
		}
		
		$extracted = array();
		$removeLength = strlen($extractArg);
		
		foreach($Install as $InstallArg => $ArgInfo){
			if( strpos($InstallArg,$extractArg) !== 0 ){
				continue;
			}
			$extractName = substr($InstallArg,$removeLength);
			if( !$this->CheckName($extractName) ){
				continue;
			}
			
			$extracted[$extractName] = $ArgInfo;
		}
		return $extracted;
	}
	

	
	function CheckName($name){
		
		$test = str_replace(array('.','_',' '),array(''),$name );
		if( empty($test) || !ctype_alnum($test) ){
			message('Could not install <em>'.$name.'</em>. Link and gadget names can only contain alphanumeric characters with underscore "_", dot "." and space " " characters.');
			return false;
		}
		return true;
	}
	
		
	
	
	/* Add to $AddTo
	 * 	Don't add elements already defined by gpEasy or other addons
	 */
	function AddToConfig($addonDir,&$AddTo,$New_Config){
		
		if( !is_array($New_Config) || (count($New_Config) <= 0) ){
			return;
		}
		
		foreach($New_Config as $Config_Key => $linkInfo){
			
			if( isset($AddTo[$Config_Key]) ){
				$addlink = true;
				
				if( !isset($AddTo[$Config_Key]['addon']) ){
					$addlink = false;
				}elseif( $AddTo[$Config_Key]['addon'] != $addonDir ){
					$addlink = false;
				}
				
				if( !$addlink ){
					message('Could not install <em>'.$Config_Key.'</em>. It is already defined by gpEasy or another add-on.');
					continue;
				}
			}
			
			if( !isset($AddTo[$Config_Key]) ){
				$AddTo[$Config_Key] = $linkInfo;
			}else{
				//this will overwrite things like label which are at times editable by users
				//$AddTo[$Config_Key] = $linkInfo + $AddTo[$Config_Key]; 
			}
			
			//these need to be set at install and update
			$AddTo[$Config_Key]['addon'] = $addonDir;
			if( isset($linkInfo['script']) ){
				$AddTo[$Config_Key]['script'] = '/data/_addoncode/'.$addonDir.'/'.$linkInfo['script'];
			}else{
				unset($AddTo[$Config_Key]['script']);
			}
			if( isset($linkInfo['data']) ){
				$AddTo[$Config_Key]['data'] = '/data/_addondata/'.$addonDir.'/'.$linkInfo['data'];
			}else{
				unset($AddTo[$Config_Key]['data']);
			}
			if( isset($linkInfo['class']) ){
				$AddTo[$Config_Key]['class'] = $linkInfo['class'];
			}else{
				unset($AddTo[$Config_Key]['class']);
			}
			if( isset($linkInfo['method']) ){
				$AddTo[$Config_Key]['method'] = $linkInfo['method'];
			}else{
				unset($AddTo[$Config_Key]['method']);
			}
			
			
		}
	}
	
	
	/* 
	Purge Links from $purgeFrom that were once defined for $addonDir
	 */
	function PurgeExisting($addonDir,&$purgeFrom,$NewLinks){
		if( !is_array($purgeFrom) ){
			return;
		}
		
		foreach($purgeFrom as $linkName => $linkInfo){
			if( !isset($linkInfo['addon']) ){
				continue;
			}
			if( $linkInfo['addon'] !== $addonDir ){
				continue;
			}
			
			if( isset($NewLinks[$linkName]) ){
				continue;
			}
			
			unset($purgeFrom[$linkName]);
		}
		
	}


	/* Make sure the extracted links are valid
	 * 
	 */
	function CleanLinks(&$links,$prefix,$linkType=false){

		
		if( !is_array($links) || (count($links) <= 0) ){
			return array();
		}
		
		$result = array();
		foreach($links as $linkName => $linkInfo){
			if( !isset($linkInfo['script']) ){
				continue;
			}
			if( !isset($linkInfo['label']) ){
				continue;
			}
			
			if( strpos($linkName,$prefix) !== 0 ){
				$linkName = $prefix.$linkName;
			}
			
			//check against special and admin links
/*			Inaccurate, this prevents updates
			global $gptitles;
			$adminLinks = admin_tools::AdminScripts();
			if( isset($gptitles[$linkName]) || isset($adminLinks[$linkName]) ){
				message('Could not install <em>'.$linkName.'</em>. That link has already been defined.');
				continue;
			}
*/
			
			
			$result[$linkName] = array();
			$result[$linkName]['script'] = $linkInfo['script'];
			$result[$linkName]['label'] = $linkInfo['label'];
			
			if( isset($linkInfo['class']) ){
				$result[$linkName]['class'] = $linkInfo['class'];
			}
			
			/*	method only available for gadgets as of 1.7b1
			if( isset($linkInfo['method']) ){
				$result[$linkName]['method'] = $linkInfo['method'];
			}
			*/
			
			if( $linkType ){
				$result[$linkName]['type'] = $linkType;
			}
			
		}
		
		return $result;
	}
	
	
	function CleanMenu($links){
		global $gpmenu;
		
		foreach($links as $title){
			if( isset($gpmenu[$title]) ){
				unset($gpmenu[$title]);
			}
		}
	}
		


	/* 
	 * 
	 * Gadget Functions 
	 * 
	 */
	
	
	function AddToHandlers($gadgets){
		global $gpLayouts;
		
		if( !is_array($gpLayouts) || !is_array($gadgets) ){
			return;
		}
		
		foreach($gpLayouts as $layout => $containers){
			if( !is_array($containers) ){
				continue;
			}
			
			if( isset($containers['handlers']['GetAllGadgets']) ){
				$container =& $gpLayouts[$layout]['handlers']['GetAllGadgets'];
				if( !is_array($container) ){
					$container = array();
				}
				$container = array_merge($container,$gadgets);
			}
		}
	}

	
	//remove gadgets from $gpLayouts
	function RemoveFromHandlers($gadgets){
		global $gpLayouts;
		
		if( !is_array($gpLayouts) || !is_array($gadgets) ){
			return;
		}
		
		
		foreach($gpLayouts as $theme => $containers){
			if( !is_array($containers) || !isset($containers['handlers']) || !is_array($containers['handlers']) ){
				continue;
			}
			foreach($containers['handlers'] as $container => $handlers){
				if( !is_array($handlers) ){
					continue;
				}
				
				foreach($handlers as $index => $handle){
					$pos = strpos($handle,':');
					if( $pos > 0 ){
						$handle = substr($handle,0,$pos);
					}
					
					foreach($gadgets as $gadget){
						if( $handle === $gadget ){
							$handlers[$index] = false; //set to false
						}
					}
				}
				
				$handlers = array_diff($handlers, array(false)); //remove false entries
				$handlers = array_values($handlers); //reset keys
				$gpLayouts[$theme]['handlers'][$container] = $handlers;
			}
		}
	}
	
	function CleanGadgets(&$gadgets,$addon){
		global $gpOutConf,$config;
		
		if( !is_array($gadgets) || (count($gadgets) <= 0) ){
			return array();
		}
		
		$result = array();
		foreach($gadgets as $gadgetName => $gadgetInfo){
			
			//check against $gpOutConf
			if( isset($gpOutConf[$gadgetName]) ){
				continue;
			}
			
			//check against ather gadgets
			if( isset($config['gadgets'][$gadgetName]) && ($config['gadgets'][$gadgetName]['addon'] !== $addon) ){
				continue;
			}
			
			
			$temp = array();
			if( isset($gadgetInfo['script']) ){
				$temp['script'] = $gadgetInfo['script'];
			}
			if( isset($gadgetInfo['class']) ){
				$temp['class'] = $gadgetInfo['class'];
			}
			if( isset($gadgetInfo['data']) ){
				$temp['data'] = $gadgetInfo['data'];
			}
			if( isset($gadgetInfo['method']) ){
				$temp['method'] = $gadgetInfo['method'];
			}
			
			if( count($temp) > 0 ){
				$result[$gadgetName] = $temp;
			}
		}
		
		return $result;
	}	
	
}
