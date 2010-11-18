<?php
defined('is_running') or die('Not an entry point...');

/*


		
bool symlink  ( string $target  , string $link  )
symlink() creates a symbolic link to the existing target with the specified name link . 


A Script Like this would most likely need to work with install.php to set up the data directories
	Using the Install_DataFiles() function
		- $_POST['username']
		- $_POST['password']


*/

//message('To Do<br/>* Use FTP (for file permission issues) => two step process <br/>* Ability to Delete ');

includeFile('tool/ftp.php');

class SetupSite{
	
	var $siteData = array();
	var $dataFile;
	var $checksum;
	var $useftp;
	
	var $site_uniq_id;
	
	function SetupSite(){
		global $rootDir, $dataDir, $page;
		
		$_POST += array('destination'=>dirname($rootDir));
		
		
		if( $dataDir !== $rootDir ){
			message('Notice: This is not the root installation of gpEasy.');
			return;
		}
		
		
		$page->admin_links[] = array('Admin_Site_Setup','Multi-Site Home');
		
		$page->admin_links[] = array('Admin_Site_Setup','New Installation','cmd=new');
		
		$page->admin_links[] = array('Admin_Site_Setup','Settings','cmd=settings');
		
		$page->admin_links[] = array('Admin_Site_Setup','About','cmd=about');

		
		
		//ftp setup
		$this->GetSiteData();

		
		$hide = false;
		$cmd = common::GetCommand();
		switch($cmd){
			
			case 'about':
				$this->About(true);
				$hide = true;
			break;
			
			case 'installed':
				$this->ShowSites();
				$hide = true;
			break;
			
			/* settings */
			case 'settings':
				$this->SettingsForm($this->siteData);
				$hide = true;
			break;
			case 'Save Settings':
				$this->SaveSettings();
				$this->SettingsForm($_POST);
				$hide = true;
			break;
			
						
			/* installation */
			case 'new';
			case 'New Installation':
				$this->SiteForm($this->siteData,false);
				$hide=true;
			break;
			
			case 'Create Site':
				if( !$this->Create1() ){
					$this->SiteForm($_POST,true);
				}
				$hide = true;
			break;
			
			case 'site_url':
			case 'options':
				$this->Options($cmd);
				$hide = true;
			break;
			
			
			/* setup */
			
			case 'Use FTP Functions':
				$hide = true;
				$this->UseFTP();
			break;
			case 'Save FTP Information':
				$hide = $this->SaveFTPInformation();
			break;
			case 'Cancel FTP Usage':
				$this->CancelFTP();
			break;
			
			
			case 'uninstall':
				$hide = true;
				$this->UninstallCheck();
			break;
			case 'Remove Site':
				$this->UninstallSite();
			break;
			
		}
		
		if( !$hide ){
			$this->ShowSimple();
			echo '<br/>';
			$this->About(false);
		}
	}
	
	
	function Options($cmd){
		global $langmessage;
		
		$site =& $_GET['site'];
		if( !isset($this->siteData['sites'][$site]) ){
			message($langmessage['OOPS']);
			return false;
		}
		
		//message('Posted: '.showArray($_POST));
		//message('site data: '.showArray($this->siteData['sites'][$site]));
		
		switch($cmd){
			case 'site_url';
				$this->Options_SiteUrl($site);
			break;
		}
		
		$form_action = common::GetUrl('Admin_Site_Setup','cmd=options&site='.urlencode($site));
		$_POST += array('site_url'=>'http://');
		echo '<form method="post" action="'.$form_action.'">';
		echo '<table class="bordered" width="100%">';
		echo '<tr><th colspan="2">';
		echo 'Site URL';
		echo '</th></tr>';
		echo '<tr><td>';
		$url = $_POST['site_url'];
		if( isset($this->siteData['sites'][$site]['url']) ){
			$url = $this->siteData['sites'][$site]['url'];
		}
		echo '<input type="text" name="site_url" value="'.htmlspecialchars($url).'" />';
		echo '</td><td>';
		echo '<input type="hidden" name="cmd" value="site_url" />';
		echo '<input type="submit" name="" value="Save Url" />';
		echo '</td></tr>';
		echo '</table>';
		echo '</form>';
		
		echo '<p>More Options Under Development</p>';
		echo '<p>';
 		echo common::Link_Admin('Admin_Site_Setup',$langmessage['back']);
 		echo '</p>';

		//echo showArray($_GET);
		//echo showArray($this->siteData);
	}
	
	function Options_SiteUrl($site){
		global $langmessage;
		
		if( empty($_POST['site_url']) ){
			unset($this->siteData['sites'][$site]['url']);
			
		}else{
			
			$site_url = $_POST['site_url'];
			if( $site_url == 'http://' ){
				message($langmessage['OOPS'].' Invalid URL');
				return false;
			}
			
			$array = @parse_url($site_url);
			if( $array === false ){
				message($langmessage['OOPS'].' Invalid URL');
				return false;
			}
			
			if( empty($array['scheme']) ){
				$site_url = 'http://'.$site_url;
			}
			
			$this->siteData['sites'][$site]['url'] = $site_url;
		}
		
		$this->SaveSiteData();
		message($langmessage['SAVED']);
	}
	
	
	function About($full){
		echo '<h2>About</h2>';
		echo 'This addon will allow you to easily add installations of gpEasy to your server.';
		
		echo ' <h3>Note</h3> ';
		echo '<p>';
		echo ' This will not copy gpEasy code to new folders.';
		echo ' Rather, new installations will use the code running the current gpEasy installation.';
		echo ' This is more efficient and will enable you to update all of your gpEasy installations at once by updating the root installation.';
		echo '</p>';
		
		if( !$full ){
			echo '<p>';
			echo common::Link_Admin('Admin_Site_Setup','Read More','cmd=about');
			echo ' - ';
			echo common::Link_Admin('Admin_Site_Setup','Create a New Installation Now','cmd=new');
			echo '</p>';
			return;
		}
		
		
		echo '<h2>';
		echo common::Link_Admin('Admin_Site_Setup','Settings','cmd=settings');
		echo '</h2>';
		
		echo '<dl>';
		echo '<dt>Service Provider ID</dt>';
		echo '<dd>When your provider id is entered, <a href="http://www.gpeasy.com/Special_Services">gpEasy.com Services</a> can attribute each installation to your service.</dd>';
		echo '</dl>';
		
		echo '<dl>';
		echo '<dt>Service Provider Name</dt>';
		echo '<dd>Displayed on the site map of your hosted installations.</dd>';
		echo '</dl>';
	}
	
	

	function SaveSettings(){
		global $langmessage;
		
		$UpdateIndexFiles = false;
		$messages = false;
		
		//destination
		if( !empty($_POST['destination']) ){
			if( file_exists($_POST['destination']) && is_dir($_POST['destination']) ){
				$this->siteData['destination'] = $_POST['destination'];
			}else{
				message('The Default Destination Root must be an existing folder.');
				$messages = true;
			}
		}
		
		//provider id
		if( !empty($_POST['service_provider_id']) ){
			if( is_numeric($_POST['service_provider_id']) ){
				
				//update index.php files
				if( !isset($this->siteData['service_provider_id']) || ($_POST['service_provider_id'] != $this->siteData['service_provider_id']) ){
					$UpdateIndexFiles = true;
				}
				
				$this->siteData['service_provider_id'] = $_POST['service_provider_id'];
			}else{
				message('The Service Provider ID must be a number.');
				$messages = true;
			}
		}
		
		//provider name
		if( !empty($_POST['service_provider_name']) ){
				
			//update index.php files
			if( !isset($this->siteData['service_provider_name']) || ($_POST['service_provider_name'] != $this->siteData['service_provider_name']) ){
				$UpdateIndexFiles = true;
			}
			
			$this->siteData['service_provider_name'] = $_POST['service_provider_name'];
		}		
		
		
		if( $UpdateIndexFiles ){
			$this->UpdateProviderID();
		}
		
		if( !$messages ){
			message($langmessage['SAVED']);
		}
		
		$this->SaveSiteData();
	}
	
	function UpdateProviderID(){
		foreach($this->siteData['sites'] as $path => $info){
			if( !isset($info['unique']) ){
				$info['unique'] = $this->NewId();
			}
			$this->CreateIndex($path,$info['unique']);
		}
		$this->SaveSiteData();
	}
	

	function SettingsForm($values=array()){
		global $rootDir,$langmessage;
		
		$values += array('destination'=>$rootDir,'service_provider_id'=>'','service_provider_name'=>'');
		
		echo '<br/>';
		
		echo '<form method="post" action="">';
		echo '<table class="bordered" width="100%">';
		
		echo '<tr>';
		echo '<th>Setting</th>';
		echo '<th>Value</th>';
		echo '</tr>';
		
		echo '<tr>';
		echo '<td>Default Destination Root</td>';
		echo '<td>';
		echo '<input type="text" name="destination" value="'.htmlspecialchars($values['destination']).'" />';
		echo '</td>';
		echo '</tr>';
		
		echo '<tr>';
		echo '<td>';
		echo 'Service Provider ID';
		echo '</td>';
		echo '<td>';
		echo '<input type="text" name="service_provider_id" value="'.htmlspecialchars($values['service_provider_id']).'" />';
		echo '</td>';
		echo '</tr>';

		echo '<tr>';
		echo '<td>';
		echo 'Service Provider Name';
		echo '</td>';
		echo '<td>';
		echo '<input type="text" name="service_provider_name" value="'.htmlspecialchars($values['service_provider_name']).'" />';
		echo '</td>';
		echo '</tr>';
		
		echo '<tr>';
		echo '<td>&nbsp;</td>';
		echo '<td>';
		echo '<input type="submit" name="cmd" value="Save Settings" />';
		echo ' <input type="submit" name="cmd" value="'.$langmessage['cancel'].'" />';
		echo ' ';
		echo common::Link_Admin('Admin_Site_Setup',$langmessage['back']);
		echo '</td>';
		echo '</tr>';
		
		echo '</table>';
		
		echo '</form>';
	}
	
	function ShowSimple(){
		global $langmessage;
		
		if( !isset($this->siteData['sites']) || (count($this->siteData['sites']) == 0) ){
			return;
		}
		echo '<form method="get" action="'.common::GetUrl('Admin_Site_Setup').'">';
		echo '<table class="bordered">';
		echo '<tr>';
		echo '<th>';
		echo 'Recent Installations';
		echo '</th>';
		echo '<th>';
		echo 'URL';
		echo '</th>';
		echo '<th>';
		echo $langmessage['options'];
		echo '</th>';
		echo '</tr>';
		$reverse = 	array_reverse($this->siteData['sites']);
		$i = 0;
		foreach($reverse as $site => $data){
			$this->ShowRow($site,$data);
			$i++;
			if( $i == 5 ){
				break;
			}
		}
		$this->SearchRow();
		echo '</table>';
		
		if( count($this->siteData['sites']) > 5 ){
			echo '<p>';
			echo common::Link_Admin('Admin_Site_Setup','More Installations','cmd=installed');
			echo ' - '.common::Link_Admin('Admin_Site_Setup','New Installation','cmd=new');
			echo '</p>';
		}

	}
	
	function ShowRow(&$site,&$data){
		global $langmessage;
			
		echo '<tr>';
		echo '<td>';
		if( strlen($site) > 25 ){
			echo '...'.substr($site,-21);;
		}else{
			echo $site;
		}
		echo '</td>';
		echo '<td>';
		if( !empty($data['url']) ){
			echo '<a href="'.$data['url'].'" target="_blank">';
			if( strlen($data['url']) > 40 ){
				echo substr($data['url'],0,35).'...';
			}else{
				echo $data['url'];
			}
			echo '</a>';
		}
		
		echo '</td>';
		echo '<td>';
		echo common::Link_Admin('Admin_Site_Setup',$langmessage['options'],'cmd=options&site='.urlencode($site));
		echo ' &nbsp; ';
		echo common::Link_Admin('Admin_Site_Setup',$langmessage['uninstall'],'cmd=uninstall&site='.urlencode($site));
		echo '</td>';
		echo '</tr>';
	}

	
	function ShowSites(){
		global $langmessage;
		
		$limit = 20; //20
		$offset = 0;
		if( isset($_GET['offset']) && is_numeric($_GET['offset']) ){
			$offset = $_GET['offset'];
		}

		if( !isset($this->siteData['sites']) || (count($this->siteData['sites']) == 0) ){
			return;
		}
		
		echo '<form method="get" action="'.common::GetUrl('Admin_Site_Setup').'">';
		echo '<table class="bordered">';
		echo '<tr>';
		echo '<th>';
		echo 'Recent Installations';
		echo '</th>';
		echo '<th>';
		echo 'URL';
		echo '</th>';
		echo '<th>';
		echo '&nbsp;';
		//echo $langmessage['options'];
		echo '</th>';
		echo '</tr>';
		
		$this->SearchRow();
		
		$reverse = 	array_reverse($this->siteData['sites']);
		if( !empty($_GET['q']) ){
			$reverse = $this->Search($reverse);
			if( count($reverse) == 0 ){
				echo '<tr>';
				echo '<td colspan="2">';
				echo 'Could not find any installations matching your search criteria.';
				echo '</td>';
				echo '</tr>';
			}
		}
		if( $offset > 0 ){
			$reverse = array_splice($reverse,$offset);
		}
		
		$i = 0;
		foreach($reverse as $site => $data){
			$this->ShowRow($site,$data);
			$i++;
			if( $i == $limit ){
				break;
			}
		}
		
		
		echo '</table>';
		echo '</form>';
		
		//navigation links
		if( $offset > 0 ){
			echo common::Link_Admin('Admin_Site_Setup','Prev','cmd=installed&q='.urlencode($_GET['q']).'&offset='.max(0,$offset-$limit));
		}else{
			echo 'Prev';
		}
		echo ' &nbsp; ';
		if( count($reverse) > $limit ){
			echo common::Link_Admin('Admin_Site_Setup','Next','cmd=installed&q='.urlencode($_GET['q']).'&offset='.($offset+$limit));
		}else{
			echo 'Next';
		}
	}
	
	function Search(&$array){
		$result = array();
		$key = $_GET['q'];
		foreach($array as $path => $info){
			
			if( strpos($path,$key) !== false ){
				$result[$path] = $info;
				continue;
			}
		}
		return $result;
	}
	
	function SearchRow(){
		$_GET += array('q'=>'');
		
		echo '<tr>';
			echo '<td colspan="2">';
			echo '<input type="text" name="q" value="'.htmlspecialchars($_GET['q']).'" />';
			echo '</td>';
			echo '<td>';
			echo '<input type="hidden" name="cmd" value="installed" />';
			echo '<input type="submit" name="" value="Search" />';
			echo '</td>';
			echo '</tr>';
	}
	
	function UninstallCheck(){
		global $langmessage;
		
		$site = $_REQUEST['site'];
		
		echo '<h3>'.$langmessage['uninstall'].': '.$site.'</h3>';
		echo '<form action="'.common::GetUrl('Admin_Site_Setup').'" method="post">';
		echo '<p>';
		echo 'Are you sure you want to permenantly remove <em>'.$site.'</em>?'; 
		echo '</p>';
		echo '<p>';
		echo 'All of the files and folders in this directory will be permenantly deleted.';
		echo '</p>';
		echo '<input type="hidden" name="site" value="'.htmlspecialchars($site).'" />';
		echo '<input type="submit" name="cmd" value="Remove Site" />';
		echo ' <input type="submit" name="cmd" value="Cancel" />';
		echo '</form>';
		
	}
	
	function UninstallSite(){
		global $langmessage;
		
		$site = $_POST['site'];
		if( !isset($this->siteData['sites'][$site]) ){
			message($langmessage['OOPS']);
			return false;
		}
		
		if( !$this->RmSite($site) ){
			message($langmessage['OOPS'].'(2)');
			return false;
		}
		
		message($langmessage['SAVED']);		
		
		unset($this->siteData['sites'][$site]);
		$this->SaveSiteData();
	}
	
	function RmSite($site){
		
		if( !$this->RmDir($site) ){
			return false;
		}
		
		if( $this->siteData['useftp'] && isset($this->siteData['sites'][$site]['ftp_destination']) ){
			$destination = $this->siteData['sites'][$site]['ftp_destination'];
			$conn_id = gpFiles::FTPConnect();
			ftp_rmdir($conn_id,$destination);
		}else{
			rmdir($site);
		}		
		
		return true;
	}
	
	function RmDir($dir){
		
		if( !file_exists($dir) ){
			return true;
		}
		
		
		if( is_link($dir) ){
			return unlink($dir);
		}
		
		$dh = @opendir($dir);
		if( !$dh ){
			return false;
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
			
			if( is_link($fullPath) ){
				if( !unlink($fullPath) ){
					$success = false;
				}
				continue;
			}
				
			
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
			if( !gpFiles::RmDir($subDir) ){
				$success = false;
			}
			
		}
		
		return $success;
	}	
	
	
	
	
	function GetSiteData(){
		global $addonDataFolder;
		
		$this->dataFile = $addonDataFolder.'/data.php';
		if( file_exists($this->dataFile) ){
			require($this->dataFile);
			if( isset($siteData) ){
				$this->siteData = $siteData;
			}
			$this->checksum = $this->CheckSum($this->siteData);
		}
		
		if( isset($this->siteData['useftp']) && ($this->siteData['useftp'] === true) ){
			
		}else{
			$this->siteData['useftp'] = false;
		}
		$this->siteData += array('sites'=>array());
	}
	
	function SaveSiteData(){
		$check = $this->CheckSum($this->siteData);
		if( $check === $this->checksum ){
			return;
		}
		
		admin_tools::SaveArray($this->dataFile,'siteData',$this->siteData);
	}
	
	function CheckSum($array){
		return crc32( serialize($array) );
	}	
	
	
	function SiteForm($values = array(),$posted=false){
		global $page, $rootDir;
		
		$page->head .= '<script type="text/javascript" language="javascript" src="'.common::GetDir('/include/js/admin_users.js').'"></script>';

		
		common::GetLangFile('install.php');
		includeFile('install/install_tools.php');
		
		$values += array('destination'=>'','themes'=>array(),'plugins'=>array());
		$_POST += array('new_folder'=>'');
		if( !$posted ){
			$values += array('all_themes'=>'all');
		}
		
		echo '<form action="'.common::GetUrl('Admin_Site_Setup').'" method="post" id="permission_form">';

		echo '<h2>New Installation</h2>';
		echo '<table class="bordered" style="width:100%">';
		echo '<tr>';
			echo '<th>Destination Root';
			echo ' / ';
			echo 'Install Folder</th>';
			echo '</tr>';
		echo '<tr>';
			echo '<td>';
			
			echo '<table cellpadding="0" cellspacing="0" border="0">';
			echo '<tr><td>';
			echo '<input type="text" name="destination" value="'.$values['destination'].'" size="40" />';
			echo '</td><td>';
			echo ' &nbsp; / &nbsp;';
			echo '<input type="text" name="new_folder" value="'.$_POST['new_folder'].'" size="10" />';
			echo '</td></tr>';
			echo '<tr>';
			echo '<td>';
			echo 'ex: '.$rootDir.'/Installations';
			echo '</td>';
			echo '<td>';
			echo ' &nbsp; / &nbsp; ex: New_Site';
			echo '</td>';
			echo '</tr>';
			echo '</table>';
			
			echo '</td>';
			echo '</tr>';
			
		echo '</table>';
		
		echo '<br/>';
		

		echo '<table class="bordered" style="width:100%">';
		Install_Tools::Form_UserDetails();
		echo '</table>';		
		
		echo '<br/>';
		
		
		//
		//	Themes
		//
		
		$all_themes = false;
		if( isset($values['all_themes']) && $values['all_themes'] == 'all' ){
			$all_themes = true;
		}

		echo '<table class="bordered" style="width:100%">';
		echo '<tr>';
			echo '<th>Themes</th>';
			echo '</tr>';
		echo '<tr>';
			echo '<td class="all_checkboxes">';
			echo '<div>';
			echo 'Select which themes will be available to the new installation. ';
			echo '</div>';
			echo '<br/>';
			
			echo '<table border="0" cellpadding="7">';
			echo '<tr>';
			echo '<td>';
			$checked = '';
			if( $all_themes ){
				$checked = ' checked="checked" ';
			}
			echo '<label class="select_all"><input type="checkbox" class="select_all" name="all_themes" value="all" '.$checked.'/> All Themes</label> ';
			echo '</td>';
			
			echo '<td style="border-left:1px solid #ccc;border-right:1px solid #ccc;vertical-align:middle;font-weight:bold;" rowspan="2">';
			echo ' OR ';
			echo '</td>';
			
			echo '<td>';
			//one point 5
			echo '<input type="hidden" name="themes[]" value="One_Point_5" />';
			echo '<label class="all_checkbox">';
			echo '<input type="checkbox" name="themes[]" value="'.htmlspecialchars('One_Point_5').'" checked="checked" disabled="disabled" />';
			echo 'One Point 5';
			echo '</label>';
			echo ' And ... <br/>';
			
			echo '<p>';
			$dir = $rootDir.'/themes';
			$layouts = gpFiles::readDir($dir,1);
			asort($layouts);
			$i = 1;
			foreach($layouts as $name){
				if( $name == 'One_Point_5' ){
					continue;
				}
				
				$checked = '';
				if( $all_themes || (array_search($name,$values['themes']) > 0) ){
					$checked = ' checked="checked" ';
				}

				echo '<label class="all_checkbox">';
				echo '<input type="checkbox" name="themes['.$i++.']" value="'.htmlspecialchars($name).'" '.$checked.'/>';
				echo str_replace('_',' ',$name);
				echo '</label>';
			}
			echo '</p>';
			echo '</td>';
			echo '</tr>';
			echo '</table>';
			
			
			echo '</td>';
			echo '</tr>';		
		echo '</table>';
		
		
		echo '<table class="bordered" style="width:100%">';
		echo '<tr>';
			echo '<th>Plugins</th>';
			echo '</tr>';
		echo '<tr>';
			echo '<td class="all_checkboxes">';
			echo '<div>';
			echo 'Select which plugins will be available to the new installation. Note, selected plugins will not be installed.';
			echo '</div>';
			echo '<br/>';
			
			$dir = $rootDir.'/addons';
			$addons = gpFiles::readDir($dir,1);
			foreach($addons as $addon){
				$checked = '';
				if( array_search($addon,$values['plugins']) > 0 ){
					$checked = ' checked="checked" ';
				}
				echo '<label class="all_checkbox">';
				echo '<input type="checkbox" name="plugins['.$i++.']" value="'.htmlspecialchars($addon).'"'.$checked.'/>';
				echo str_replace('_',' ',$addon);
				echo '</label>';
			}
			
			echo '</td>';
			echo '</tr>';
		echo '</table>';

		
		
		echo '<p>';
		echo '<input type="submit" name="cmd" value="Create Site" />';
		echo ' <input type="submit" name="cmd" value="Cancel" />';
		$this->ftpinput();
		echo '</p>';
		
		
		
		echo '</form>';
	}
	
	
	function Create1(){
		echo '<style>';
		echo '#multisite li{margin:0 !important;padding:3px !important;};';
		echo '</style>';
		echo '<ul id="multisite">';
		$destination = false;
		$result = $this->CreateWorker($destination);
		
		if( !$result ){
			echo '<li><b>Installation Aborted</b></li>';
		}
		echo '</ul>';
		
		
		//clean up if failed
		if( !$result ){
			
			
			if( $destination !== false ){
				$this->RmSite($destination);
			}
			
			return false;
		}
			
		echo '<p></p>';
		echo '<form>';
		echo '<b>Installation was completed successfully.</b> ';
		echo '<input type="submit" name="" value="Continue" />';
		echo '</form>';
		
		return true;
	}
	
	function CreateWorker(&$destination){
		global $rootDir,$config,$checkFileIndex;

		$_POST += array('themes'=>array(),'plugins'=>array());
		$checkFileIndex = false;
		common::GetLangFile('install.php');
		includeFile('install/install_tools.php');

		echo '<li>Starting Installation</li>';

		//check destination
		if( empty($_POST['new_folder']) ){
			message('Oops, <em>Install Folder</em> can not be empty.');
			return false;
		}
		
		
		//check user values first
		if( !Install_Tools::gpInstall_Check() ){
			return false;
		}
		
		
		//themes
		if( isset($_POST['all_themes']) && $_POST['all_themes'] == 'all' ){
			//$themesOk = true;
		}elseif( count($_POST['themes']) ){
			//$themesOk = true;
		}else{
			message('Oops, you must install at least one theme. ');
			return false;
		}
				
		$destination = $_POST['destination'].'/'.$_POST['new_folder'];
		$destination = str_replace(array('\\','//'),array('/','/'),$destination); //clean slashes


		//if the destination already exists, it should be writable and empty
		if( file_exists($destination) ){
			
			$files = gpFiles::readDir($destination,false);
			if( count($files) > 0 ){
				message('Oops, <em>'.$destination.'</em> already exists and is not empty, please try again.');
				$destination = false; //don't remove $destination after we return false because it already exists!!!!
				return false;
			}
		}
		
		$filename = basename($destination);
		$parentDir = dirname($destination);
		
		
		if( $this->siteData['useftp'] ){
			echo '<li>Using FTP</li>';
			
			$conn_id = gpFiles::FTPConnect();
			if( $conn_id === false ){
				return false;
			}
			
			$ftp_parent = gpftp::GetFTPRoot($conn_id,$parentDir);
			$ftp_destination = $ftp_parent.'/'.$filename;
			
			if( !@ftp_chdir( $conn_id, $ftp_parent ) ){
				message('Oops, could not create <em>'.$destination.'</em> because the parent directory does not exist. Please make sure <em>'.$parentDir.'</em> exists before continuing.');
				return false;
			}
			
			if( !file_exists($destination) ){
				if( !ftp_mkdir($conn_id,$ftp_destination) ){
					message('Oops, could not create <em>'.$destination.'</em>');
					return false;
				}
			}
			
			ftp_site($conn_id, 'CHMOD 0777 '. $ftp_destination );
			
			
		}else{
			echo '<li>Using File Functions</li>';
		
			if( !file_exists($parentDir) ){
				message('Oops, could not create <em>'.$destination.'</em> because the parent directory does not exist. Please make sure <em>'.$parentDir.'</em> exists before continuing.');
				return false;
			}
			
			if( !is_writable($parentDir) ){
				message('Oops, could not create <em>'.$destination.'</em> because the parent directory is not writable. Please make sure <em>'.$parentDir.'</em> is writable before continuing.');
				return false;
			}

			if( !file_exists($destination) ){
				if( !gpFiles::CheckDir($destination) ){
					message('Oops, could not create <em>'.$destination.'</em>');
					return false;
				}
			}elseif( !is_writable($destination) ){
				message('Oops, <em>'.$destination.'</em> already exists and is not writable, please try again.');
				$destination = false; //don't remove $destination after we return false because it already exists!!!!
				return false;
			}
		}
		
		$this->site_uniq_id = $this->NewId();
		
		//	Create index.php file
		echo '<li>Create index.php file</li>';
		if( !$this->CreateIndex($destination,$this->site_uniq_id) ){
			message('Failed to save the index.php file');
			return false;
		}
		
		//	Create /include symlink
		$target = $rootDir.'/include';
		$name = $destination.'/include';
		if( !$this->Create_Symlink($target,$name,'main.php') ){
			return false;
		}
		
		
		//	Create /themes folder
		if( !$this->CopyThemes($destination) ){
			return false;
		}
		
		//	Create /plugins folder
		if( !$this->CreatePlugins($destination) ){
			return false;
		}
		
		
		//	variable juggling
		global $dataDir; //for SaveTitle(), SaveConfig()
		$oldDir = $dataDir;
		$dataDir = $destination;
		
		$new_config = array();
		$new_config['language'] = $config['language'];
		
		if( !Install_Tools::Install_DataFiles_New( $destination, $new_config, false ) ){
			return false;
		}
		
		$dataDir = $oldDir;
		
		$this->siteData['sites'][$destination] = array();
		$this->siteData['sites'][$destination]['unique'] = $this->site_uniq_id;
		
		if( $this->siteData['useftp'] ){
			$this->siteData['sites'][$destination]['ftp_destination'] = $ftp_destination;
			ftp_site($conn_id, 'CHMOD 0777 '. $ftp_destination );
		}
		$this->SaveSiteData();
		
		return true;
	}
	
	function CreatePlugins($destination){
		global $rootDir;
		
		//selection of themes
		if( !gpFiles::CheckDir($destination.'/addons') ){
			message('Failed to create <em>'.$destination.'/addons'.'</em>');
			return false;
		}
		
		foreach($_POST['plugins'] as $plugin){
			$target = $rootDir.'/addons/'.$plugin;
			if( !file_exists($target) ){
				continue;
			}
			$name = $destination.'/addons/'.$plugin;
			$this->Create_Symlink($target,$name);
		}
		
		
		return true;
	}
	
	
	//Don't create symlink for /themes, users may want to add to their collection of themes
	function CopyThemes($destination){
		global $rootDir;
		
		//selection of themes
		if( !gpFiles::CheckDir($destination.'/themes') ){
			message('Failed to create <em>'.$destination.'/themes'.'</em>');
			return false;
		}

		$count = 0;
		foreach($_POST['themes'] as $theme){
			$target = $rootDir.'/themes/'.$theme;
			if( !file_exists($target) ){
				continue;
			}
			$name = $destination.'/themes/'.$theme;
			if( $this->Create_Symlink($target,$name) ){
				$count++;
			}
		}
		if( $count == 0 ){
			message('Failed to populate <em>'.$destination.'/themes'.'</em>');
			return false;
		}
		
		return true;
	}
	
	//create the index.php file
	function CreateIndex($destination,$unique){
		
		$path = $destination.'/index.php';
		
		
		$indexA = array();
		$indexA[] = '<'.'?'.'php';
		if( isset($this->siteData['service_provider_id']) ){
			$indexA[] = 'define(\'service_provider_id\',\''.(int)$this->siteData['service_provider_id'].'\');';
		}
		if( isset($this->siteData['service_provider_name']) ){
			$indexA[] = 'define(\'service_provider_name\',\''.addslashes($this->siteData['service_provider_name']).'\');';
		}
		$indexA[] = 'define(\'multi_site_unique\',\''.$unique.'\');';
		$indexA[] = 'require_once(\'include/main.php\');';
		$index = implode("\n",$indexA);
		if( !gpFiles::Save($path,$index) ){
			return false;
		}
		
		@chmod($path,0644); //to prevent 500 Internal Server Errors on some servers
		
		return true;
	}
	
	function NewId(){
		if( isset($_SERVER['HTTP_HOST']) ){
			$server = $_SERVER['HTTP_HOST'];
		}else{
			$server = $_SERVER['SERVER_NAME'];
		}
		
		do{
			$unique = base64_encode($server).uniqid('_');
			foreach($this->siteData['sites'] as $array){
				if( isset($array['unique']) && ($array['unique'] == $unique) ){
					$unique = false;
					break;
				}
			}
		}while($unique==false);
		
		return $unique;
	}
	
	//create a symbolic link and test for $test_file
	function Create_Symlink($target,$path,$test_file = false ){
		
		echo '<li>Create Symlink: <em>'.$path.'</em></li>';
		if( !symlink($target,$path) ){
			message('Oops, Symlink creation failed (1)');
			return false;
		}
		
		if( $test_file && !file_exists($path.'/'.$test_file) ){
			message('Oops, Symlink creation failed (2)');
			return false;
		}
		
		return true;
	}
	
	
	
	/* File Handling Functions */
	
	function UseFTP(){
		
		$_POST += array('ftp_server'=>gpftp::GetFTPServer(),'ftp_user'=>'','ftp_pass'=>'');

		
		echo '<form action="'.common::GetUrl('Admin_Site_Setup').'" method="post">';
		
		echo '<h3>FTP Information</h3>';
		echo '<table class="bordered">';
			echo '<tr>';
				echo '<td><b>FTP Server*</b></td>';
				echo '<td>';
				echo '<input type="text" name="ftp_server" value="'.$_POST['ftp_server'].'" size="40" />';
				echo '</td></tr>';
			echo '<tr>';
				echo '<td><b>FTP Username*</b></td>';
				echo '<td>';
				echo '<input type="text" name="ftp_user" value="'.$_POST['ftp_user'].'" size="40" />';
				echo '</td></tr>';
			echo '<tr>';
				echo '<td><b>FTP Password*</b></td>';
				echo '<td>';
				echo '<input type="password" name="ftp_pass" value="'.$_POST['ftp_pass'].'" />';
				echo '</td></tr>';
			
		echo '</table>';
		echo '<p>';
		echo '<input type="submit" name="cmd" value="Save FTP Information" />';
		echo ' <input type="submit" name="cmd" value="Cancel" />';
		
		echo '</form>';
		
	}
	
	function CancelFTP(){
		global $config, $langmessage;
		
		$this->siteData['useftp'] = false;
		unset($config['ftp_root']);
		unset($config['ftp_user']);
		unset($config['ftp_server']);
		unset($config['ftp_pass']);
		if( !admin_tools::SaveConfig() ){
			message($langmessage['OOPS']);
			return false;
		}
		
		message($langmessage['SAVED']);
		$this->SaveSiteData();
	}

	
	function SaveFTPInformation(){
		global $config, $langmessage;
		
		$_POST += array('ftp_server'=>'','ftp_user'=>'','ftp_pass'=>'');
		$conn_id = $this->FTPConnect($_POST);
		if( $conn_id === false ){
			$this->UseFTP();
			return true;
		}
		
		
		$config['ftp_user'] = $_POST['ftp_user'];
		$config['ftp_server'] = $_POST['ftp_server'];
		$config['ftp_pass'] = $_POST['ftp_pass'];

		if( !admin_tools::SaveConfig() ){
			message($langmessage['OOPS']);
			return false;
		}
		
		message($langmessage['SAVED']);
		$this->siteData['useftp'] = true;
		$this->SaveSiteData();
	}
	
	function ftpinput(){
		
		if( $this->siteData['useftp'] ){
			echo ' - ';
			echo ' <input type="submit" name="cmd" value="Cancel FTP Usage" />';
			return;
		}
		
		if( function_exists('ftp_connect') ){
			echo ' &nbsp; &nbsp; ';
			echo ' <input type="submit" name="cmd" value="Use FTP Functions" />';
		}
	}
	
	function FTPConnect($array){
		static $conn_id = false;
		
		if( $conn_id ){
			return $conn_id;
		}
		
		
		$conn_id = @ftp_connect($array['ftp_server'],21,6);
		if( !$conn_id ){
			message('ftp_connect() failed for server : '.$array['ftp_server']);
			return false;
		}
		
		$login_result = @ftp_login($conn_id,$array['ftp_user'],$array['ftp_pass'] );
		if( !$login_result ){
			message('ftp_login() failed for server : '.$array['ftp_server'].' and user: '.$array['ftp_user']);
			return false;
		}
		register_shutdown_function(array('gpFiles','ftpClose'),$conn_id);
		return $conn_id;
	}
	
}
