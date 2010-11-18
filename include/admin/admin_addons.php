<?php
defined("is_running") or die("Not an entry point...");


/*
 * To Do
 * 
 * 		Addon Names should not contain html characters
 * 		Move messages to language file
 * 		
 * 
 * 
 * 
 * Notes
 * 		Copying the directories does not delete files from the /data/_addoncode folder that are no longer used
 * 		Should Admin_links be in $gptitles similarly to Special_links? .. they aren't needed in $gptitles for anything
 * 
 * 
 * 
 * Admin Plugin
 * 		/addons/<addon>/Addon.ini
 * 			- Addon_Name (required)
 * 			- link definitions: (optional)
 * 				- should be able to have multiple links, 
 * 				
 * 
 * 				- link_name (required)
 * 					an example link: Admin_<linkname>
 * 				- labels (required) should pull the language values during installation/upgrading 
 * 				- script (required)
 * 				- class / function to call once opened (optional)
 * 				
 * 			- minimum version / max version
 * 		/addons/<addon>/<php files>
 * 
 * 
 * 
 * 
 * 
 * 
 */

includeFile('admin/admin_addon_install.php'); // admin_addon_install extends admin_addon_tool

class admin_addons extends admin_addon_install{
	
	var $dataFile;
	var $develop = false;
	
	var $addon_type = 'plugin';

	
	function admin_addons(){
		global $langmessage,$config;
		
		$this->InitRating();
		
		if( !isset($config['admin_links']) ){
			$config['admin_links'] = array();
		}
		
		if( !isset($config['gadgets']) ){
			$config['gadgets'] = array();
		}
		
		$this->GetData();
		$cmd = common::GetCommand();
		
		
		switch($cmd){
			
			/* testing */
			case 'package':
				includeFile('admin/x_admin_addon_package.php');
				new addon_package();
			break;
			
			case 'remote_install':
			case 'remote_install2':
			case 'step1':
			case 'step2':
			case 'step3':
				$this->admin_addon_install($cmd);
			break;

			case 'Update Review';
			case 'Send Review':
			case 'rate':
				$this->admin_addon_rating('plugin','Admin_Addons');
				if( $this->ShowRatingText ){
					return;
				}
				$this->Select();
			break;

			case 'changeinstall_confirmed';
			case 'changeinstall':
			case 'enable':
			case 'disable':
			case 'show':
				$this->ShowAddon();
			break;
			
			case 'uninstall':
				$this->Uninstall();
			break;
			
			case 'confirm_uninstall':
				$this->Confirm_Uninstall();
			break;
			
			case 'history':
				$this->History();
			break;
			
			default:
				$this->Select();
			break;
		}
	}
	
	
	function GadgetVisibility($addon,$cmd){
		global $config,$langmessage;
		
		$gadget = $_GET['gadget'];
		
		if( !isset($config['gadgets']) || !is_array($config['gadgets']) || !isset($config['gadgets'][$gadget]) ){
			message($langmessage['OOPS']);
			return;
		}
		
		$gadgetInfo =& $config['gadgets'][$gadget];
		
		switch($cmd){
			case 'enable':
				unset($gadgetInfo['disabled']);
			break;
			case 'disable':
				$gadgetInfo['disabled']	= true;
			break;
		}
		
		if( !admin_tools::SaveConfig() ){
			message($langmessage['OOPS']);
		}
	}
	
	
	
	/*
	Addon Data
	*/
	
	function GetData(){
		global $dataDir,$config;
		
		//new
		if( !isset($config['addons']) ){
			$config['addons'] = array();
		}
		
		//fix data
		$firstValue = current($config['addons']);
		if( is_string($firstValue) ){
			
			foreach($config['addons'] as $addon => $addonName){
				$config['addons'][$addon] = array();
				$config['addons'][$addon]['name'] = $addonName;
			}
		}
	}
	
	
	/*
	Uninstall
	*/
	
	function Uninstall(){
		global $config,$langmessage;
		
		$addon =& $_REQUEST['addon'];
		if( !isset($config['addons'][$addon]) ){
			message($langmessage['OOPS']);
		}else{
		
			$mess = '<form action="'.common::GetUrl('Admin_Addons').'" method="post">';
			$mess .= $langmessage['confirm_uninstall'];
			$mess .= '<input type="hidden" name="addon" value="'.htmlspecialchars($addon).'" />';
			$mess .= '<input type="hidden" name="cmd" value="confirm_uninstall" />';
			$mess .= ' <input type="submit" class="submit" name="aaa" value="'.$langmessage['continue'].'" /> ';
			$mess .= ' <input type="submit" class="submit" name="cmd" value="'.$langmessage['cancel'].'" /> ';
			$mess .= '</form>';
			message($mess);
			
		}
		
		$this->Select();
	}
	
	function Confirm_Uninstall(){
		global $config, $langmessage, $dataDir, $gptitles;
		
		$addon =& $_POST['addon'];
		if( !isset($config['addons'][$addon]) ){
			message($langmessage['OOPS']);
			return;
		}
		
		//tracking
		$history = array();
		$history['name'] = $config['addons'][$addon]['name'];
		$history['action'] = 'uninstalled';
		if( isset($config['addons'][$addon]['id']) ){
			$history['id'] = $config['addons'][$addon]['id'];
		}
		
		unset($config['addons'][$addon]);
		
		
		//remove links
		$installedGadgets = $this->GetInstalledComponents($config['gadgets'],$addon);
		$this->RemoveFromHandlers($installedGadgets);
		
		$installedLinks = $this->GetInstalledComponents($gptitles,$addon);
		$this->CleanMenu($installedLinks);

		
		$this->RemoveFromConfig($config['gadgets'],$addon);
		$this->RemoveFromConfig($config['admin_links'],$addon);
		$this->RemoveFromConfig($gptitles,$addon);
		$this->CleanHooks($addon);

		
		if( !admin_tools::SaveAllConfig() ){
			message($langmessage['OOPS']);
			$this->Uninstall();
			return false;
		}
		
		
		/*
		 * Delete the data folders
		 */
		$installFolder = $dataDir.'/data/_addoncode/'.$addon;
		if( file_exists($installFolder) ){
			$this->RmDir($installFolder);
		}

		
		$dataFolder = $dataDir.'/data/_addondata/'.$addon;
		if( file_exists($dataFolder) ){
			$this->RmDir($dataFolder);
		}
		
		/* 
		 * Record the history
		 */
		$history['time'] = time();
		$this->addonHistory[] = $history;
		$this->SaveAddonData();

		
		message($langmessage['SAVED']);
		$this->Select();
	}
	
	function GetInstalledComponents($from,$addon){
		$result = array();
		if( !is_array($from) ){
			return $result;
		}
		
		foreach($from as $name => $info){
			if( !isset($info['addon']) ){
				continue;
			}
			if( $info['addon'] !== $addon ){
				continue;
			}
			$result[] = $name;
		}
		return $result;
	}
	
	function RemoveHooks(){
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
				
				unset($config['hooks'][$hook_name][$hook_dir ]);
			}
		}
		
		//reduce further if empty
		foreach($config['hooks'] as $hook_name => $hook_array){
			if( empty($hook_array) ){
				unset($config['hooks'][$hook_name]);
			}
		}
	}		
		
	
	function RemoveFromConfig(&$configFrom,$addon){
	
		if( !is_array($configFrom) ){
			return;
		}
		foreach($configFrom  as $key => $value ){
			if( !isset($value['addon']) ){
				continue;
			}
			if( $value['addon'] == $addon ){
				unset($configFrom[$key]);
			}
		}
	}
	
	
	
	function RmDir($dir){
		
		if( is_link($dir) ){
			return unlink($dir);
		}		
		
		$dh = @opendir($dir);
		if( !$dh ){
			return false;
		}
		$success = true;
		
		$subDirs = array();
		while( ($file = readdir($dh)) !== false){
			if( strpos($file,'.') === 0){
				continue;
			}
			$fullPath = $dir.'/'.$file;
			if( is_dir($fullPath) ){
				$subDirs[] = $fullPath;
				continue;
			}
			if( !unlink($fullPath) ){
				$success = false;
			}
		}
		closedir($dh);
		
		foreach($subDirs as $subDir){
			if( !$this->RmDir($subDir) ){
				$success = false;
			}
		}
		
		if( $success ){
			return gpFiles::RmDir($dir);
		}
		return false;
	}
	
	function ShowAddon($addon=false){
		global $config,$langmessage,$gptitles;

		
		
		if( $addon === false ){
			$addon =& $_REQUEST['addon'];
		}
		if( !isset($config['addons'][$addon]) ){
			message($langmessage['OOPS'].'(s1)');
			$this->Select();
			return;
		}
		
		$cmd = common::GetCommand();
		switch( $cmd ){
			case 'enable':
			case 'disable':
				$this->GadgetVisibility($addon,$cmd);
			break;
			
			case 'changeinstall':
				$this->ChangeInstallType($addon);
			break;
			
			case 'changeinstall_confirmed':
				$this->ChangeInstallConfirmed($addon);
			break;
			
		}
		
		
		echo '<div class="addon_heading">';
		echo '<h2>'.$langmessage['add-ons'].': '.$config['addons'][$addon]['name'].'</h2>';
		echo '<span><em>/addons/'.$addon.'</em></span>';
		$this->OptionLinks($addon);
		echo '</div>';
		
		echo '<table class="bordered" style="width:90%">';
		
		//show Special Links
			$sublinks = admin_tools::GetAddonComponents($gptitles,$addon);
			if( !empty($sublinks) ){
				echo '<tr><th>';
					echo 'Special Links';
					echo '</th>';
					echo '<th>';
					echo $langmessage['options'];
					echo '</th></tr>';
				
				foreach($sublinks as $linkName => $linkInfo){
					echo '<tr><td>';
						echo common::Link($linkName,$linkInfo['label']);
						echo '</td>';
						echo '<td>';
						echo '-';
						echo '</td></tr>';
				}
			}
		
		//show Admin Links
			$sublinks = admin_tools::GetAddonComponents($config['admin_links'],$addon);
			if( !empty($sublinks) ){
				echo '<tr><th>';
					echo 'Admin Links';
					echo '</th>';
					echo '<th>';
					echo $langmessage['options'];
					echo '</th></tr>';
				foreach($sublinks as $linkName => $linkInfo){
					echo '<tr><td>';
						echo common::Link($linkName,$linkInfo['label']);
						echo '</td>';
						echo '<td>';
						echo '-';
						echo '</td></tr>';
				}
			}
		
		
		//show Gadgets
			$gadgets = admin_tools::GetAddonComponents($config['gadgets'],$addon);
			if( is_array($gadgets) && (count($gadgets) > 0) ){
				echo '<tr><th>';
					echo $langmessage['gadgets'];
					echo '</th>';
					echo '<th>';
					echo $langmessage['options'];
					echo '</th></tr>';
				
				foreach($gadgets as $name => $value){
					echo '<tr><td>';
					echo str_replace('_',' ',$name);
					echo '</td><td>';
					if( isset($value['disabled']) ){
						echo common::Link('Admin_Addons',$langmessage['enable'],'cmd=enable&addon='.$addon.'&gadget='.rawurlencode($name),' name="creq" ');
						echo ' - ';
						echo '<b>'.$langmessage['disabled'].'</b>';
					}else{
						echo ' <b>'.$langmessage['enabled'].'</b>';
						echo ' - ';
						echo common::Link('Admin_Addons',$langmessage['disable'],'cmd=disable&addon='.$addon.'&gadget='.rawurlencode($name),' name="creq" ');
					}
					echo '</td></tr>';
				}
			}
			
		//editable text
		if( isset($config['addons'][$addon]['editable_text']) && admin_tools::HasPermission('Admin_Theme_Content') ){
				echo '<tr><th>';
					echo $langmessage['editable_text'];
					echo '</th>';
					echo '<th>';
					echo $langmessage['options'];
					echo '</th></tr>';
				echo '<tr><td>';
					echo $config['addons'][$addon]['editable_text'];
					echo '</td>';
					echo '<td>';
					echo common::Link('Admin_Theme_Content',$langmessage['edit'],'cmd=addontext&addon='.urlencode($addon),' title="'.urlencode($langmessage['editable_text']).'" name="ajax_box" ');
					echo '</td></tr>';
				
			
		}
			
			
		echo '</table>';
		
		if( !isset($config['addons'][$addon]['id']) ){
			return;
		}
		
		echo '<h3>'.$langmessage['rate_this_addon'].'</h3>';
		
		$id = $config['addons'][$addon]['id'];
	
		if( isset($this->addonReviews[$id]) ){
			
			$review =& $this->addonReviews[$id];
			$review += array('time'=>time());
			echo 'You posted the following review on '.date('M j, Y',$review['time']);
			
			
			echo '<table cellpadding="7">';
			echo '<tr>';
				echo '<td>';
				echo 'Rating';
				echo '</td>';
				echo '<td>';
				$this->ShowRating($id,$review['rating']);
				echo '</td>';
			echo '</tr>';
				
			echo '<tr>';
				echo '<td>';
				echo 'Review';
				echo '</td>';
				echo '<td>';
				echo nl2br(htmlspecialchars($review['review']));
				echo '</td>';
			echo '</tr>';
			echo '<tr>';
				echo '<td>';
				echo '</td>';
				echo '<td>';
				echo common::Link('Admin_Addons','Edit Review','cmd=rate&arg='.$id);
				echo '</td>';
			echo '</tr>';
			echo '</table>';
			
			
		}else{
			echo '<table cellpadding="7">';
			echo '<tr>';
				echo '<td>';
				echo 'Rating';
				echo '</td>';
				echo '<td>';
				$this->ShowRating($id,5);
				echo '</td>';
				echo '</tr>';
			echo '</table>';
		}

	}
	
	function OptionLinks($addon){
		global $dataDir, $langmessage;
		$installFolder = $dataDir.'/data/_addoncode/'.$addon;
		if( is_link($installFolder) ){
			echo common::Link('Admin_Addons',$langmessage['upgrade'],'cmd=step1&mode=dev&addon='.$addon);
		}else{
			echo common::Link('Admin_Addons',$langmessage['upgrade'],'cmd=step1&addon='.$addon);
		}
		echo common::Link('Admin_Addons',$langmessage['uninstall'],'cmd=uninstall&addon='.$addon);
		
		if( gptesting ){
			echo common::Link('Admin_Addons','Change Install Type','cmd=changeinstall&addon='.$addon);
		}
	}
	
	
	function GetAvailAddons(){
		global $dataDir;
		
		$addonPath = $dataDir.'/addons';
		
		if( !file_exists($addonPath) ){
			message('Warning: The /addons folder "<em>'.$addonPath.'</em>" does not exist on your server.');
			return array();
		}
		
		
		$folders = gpFiles::ReadDir($addonPath,1);
		$avail = array();
		
		foreach($folders as $key => $value){
			$fullPath = $addonPath .'/'.$key;
			$info = $this->GetAvailInstall($fullPath);

			if( $info !== false){
				$avail[$key] = $info;
			}
		}
		
		return $avail;
	}
		
	
	function Instructions(){
		global $langmessage;
		
		$lang = 'To install a new addon, you\'ll need to first <a href="http://gpeasy.com/index.php/Special_Addons" target="_blank">download the addon package</a> and unzip/untar the package. Then upload the contents of the uncompressed package to your /addons directory.';
		$lang .= ' Once the code for the addon has been uploaded to your server, <a href="%s">refresh</a> this page and follow the install instructions for the addon.';
		
		echo '<p>';
		echo sprintf($lang,common::GetUrl('Admin_Addons','cmd=new'));
		echo '</p>';
	}
		
	
	function Select(){
		global $langmessage,$config;
		$instructions = true;
		$available = $this->GetAvailAddons();
		
		//message('available: '.showArray($available));
		
		echo '<h2>'.$langmessage['add-ons'].'</h2>';

		if( !$this->ShowInstalled($available) ){
			$this->Instructions();
			$instructions = false;
		}
		
		
		//show available addons
		echo '<h3>'.$langmessage['available_addons'].'</h3>';
		
		echo '<div style="display:none" id="gpeasy_addons"></div>';
		
		if( count($available) == 0 ){
			//echo ' -empty- ';
		}else{
			echo '<table class="bordered">';
			echo '<tr>';
			echo '<th>';
			echo $langmessage['name'];
			echo '</th>';
			echo '<th>';
			echo $langmessage['version'];
			echo '</th>';
			echo '<th>';
			echo $langmessage['options'];
			echo '</th>';
			echo '</tr>';
			
			foreach($available as $folder => $info ){
				if( isset($config['addons'][$folder]) ){
					continue;
				}
				
				echo '<tr>';
				echo '<td>';
				echo $info['Addon_Name'];
				echo '<br/><em class="admin_note">/addons/'.$folder.'</em>';
				echo '</td>';
				echo '<td>';
				echo $info['Addon_Version'];
				echo '</td>';
				echo '<td>';
				echo common::Link('Admin_Addons',$langmessage['install'],'cmd=step1&addon='.$folder);
				echo ' &nbsp; ';
				if( function_exists('symlink') ){
					echo common::Link('Admin_Addons',$langmessage['develop'],'cmd=step1&mode=dev&addon='.$folder);
				}
				echo '</td>';
				
				echo '</tr>';
			}
			echo '</table>';
			
		}
		
		
		echo '<p>';
		echo '<a href="'.$GLOBALS['addonBrowsePath'].'/Special_Addon_Plugins" name="remote">Browse Additional Addons</a>';
		echo '</p>';

		if( $instructions ){
			echo '<h3>'.$langmessage['about'].'</h3>';
			$this->Instructions();
		}
		
	}
	
	function ShowInstalled(&$available){
		global $langmessage,$config,$dataDir;
		
		//show installed addons
		$show =& $config['addons'];
		if( !is_array($show) ){
			return false;
		}
		
		echo '<table class="bordered">';
		echo '<tr>';
		echo '<th>';
		echo $langmessage['name'];
		echo '</th>';
		echo '<th>';
		echo $langmessage['version'];
		echo '</th>';
		echo '<th>';
		echo $langmessage['options'];
		echo '</th>';
		echo '</tr>';
		foreach($show as $folder => $info){
			
			$addonName = $info['name'];
			
			echo '<tr>';
			echo '<td>';
			$label = $addonName;
			echo common::Link('Admin_Addons',$label,'cmd=show&addon='.$folder);
			echo '<br/><em class="admin_note">/addons/'.$folder.'</em>';
			echo '</td>';
			
			echo '<td>';
			if( isset($info['version']) ){
				echo $info['version'];
			}else{
				$info['version'] = '0';
				echo '&nbsp;';
			}
			
			
			$installFolder = $dataDir.'/data/_addoncode/'.$folder;
			$developerInstall = false;
			if( is_link($installFolder) ){
				echo '<br/> <em class="admin_note">'.$langmessage['developer_install'].'</em>';
				$developerInstall = true;
			}
			echo '</td>';
			
			echo '<td>';
			

			//new version available
				if( isset($available[$folder]) ){
					if( isset($available[$folder]['Addon_Version']) ){
						$newVersion = $available[$folder]['Addon_Version'];
						if(version_compare($newVersion,$info['version'] ,'>') ){
							echo ' <b>'.$langmessage['new_version'].'</b><br/>';
							//$label = ' <b>'.$langmessage['new_version'].'</b>';
						}
					}
					unset($available[$folder]);
				}


			if( isset($info['id']) ){
				echo common::Link('Admin_Addons',$langmessage['rate'],'cmd=rate&arg='.$info['id']);
			}else{
				echo $langmessage['rate'];
			}
			echo ' &nbsp; ';
			
			
			//upgrade link
				if( $developerInstall ){
					echo common::Link('Admin_Addons',$langmessage['upgrade'],'cmd=step1&mode=dev&addon='.$folder);
				}else{
					echo common::Link('Admin_Addons',$langmessage['upgrade'],'cmd=step1&addon='.$folder);
				}
					
				
			
			echo ' &nbsp; ';
			echo common::Link('Admin_Addons',$langmessage['uninstall'],'cmd=uninstall&addon='.$folder);
			
			
			echo '</td>';
			echo '</tr>';
		}
		echo '</table>';
		
		return true;
	}
		


	function ChangeInstallType(&$addonName){
		global $langmessage;
		
		$message = '';
		$message .= '<form method="post" action="">';
		$message .= '<input type="hidden" name="cmd" value="changeinstall_confirmed" />';
		$message .= 'Are you sure you want to change the install type? ';
		//$message .= ' <input type="submit" name="cmd" value="'.$langmessage['cancel'].'" />';
		$message .= ' <input type="submit" class="submit" name="aaa" value="'.$langmessage['continue'].'" />';
		$message .= '</form>';
		
		message($message);
	}
	
	
	function ChangeInstallConfirmed(&$addonName){
		global $dataDir,$langmessage;
		
		
		$installFolder = $dataDir.'/data/_addoncode/'.$addonName;
		$fromFolder = $dataDir.'/addons/'.$addonName;
		
		if( !file_exists($installFolder) ){
			message($langmessage['OOPS']);
			return;
		}
		if( !file_exists($fromFolder) ){
			message($langmessage['OOPS']);
			return;
		}
		
		if( is_link($installFolder) ){
			
			
			unlink($installFolder);
			
			
			if( !admin_addon_install::CopyAddonDir($fromFolder,$installFolder) ){
				message($langmessage['OOPS']);
				return;
			}

			
		}else{

			$this->RmDir($installFolder);

			if( !symlink($fromFolder,$installFolder) ){
				message($langmessage['OOPS']);
				return;
			}
		}
		
		message('Install Type Changed');
		
	}


}





