<?php




class Install_Tools{
	
	function Form_UserDetails(){
		global $langmessage;
		
		$_POST += array('username'=>'');
		echo '<tr><th colspan="2">'.$langmessage['configuration'].'</th></tr>';
		echo '<tr><td>'.$langmessage['Admin_Username'].'*</td><td><input type="text" class="text" name="username" value="'.htmlspecialchars($_POST['username']).'" /></td></tr>';
		echo '<tr><td>'.$langmessage['Admin_Password'].'*</td><td><input type="password" class="text" name="password" value="" /></td></tr>';
		echo '<tr><td>'.$langmessage['Repeat_Password'].'*</td><td><input type="password" class="text" name="password1" value="" /></td></tr>';
	}
	
	function Form_Configuration(){
		global $langmessage;

		//Hide gpEasy Link
		echo '<tr><td colspan="2"><a href="javascript:toggleOptions()">'.$langmessage['options'].'...</a></td></tr>';
		echo '<tbody id="config_options" style="display:none">';
		echo '<tr><td><b>'.$langmessage['hidegplink'].'</b></td><td>';
		echo '<select name="hidegplink">';
		echo '<option value="">Show</option>';
		echo '<option value="hide">Hide</option>';
		echo '</select>';
		echo '</td></tr>';
		echo '</tbody>';

		
	}
	
	
	/* deprecated */
	function Install_DataFiles( $destination = false, $language = 'en' ){
		
		$config = array();
		$config['language'] = $language;
		if( !Install_Tools::gpInstall_Check() ){
			return false;
		}
		return Install_Tools::Install_DataFiles_New($destination, $config);
	}
	
	
	//based on the user supplied values, make sure we can go forward with the installation
	function gpInstall_Check(){
		global $langmessage;
		
		$passed = array();
		$failed = array();
		
		//Password
			if( ($_POST['password']=="") || ($_POST['password'] !== $_POST['password1'])  ){
				$failed[] = $langmessage['invalid_password'];
			}else{
				$passed[] = $langmessage['PASSWORDS_MATCHED'];
			}
			
		//Username
			$test = str_replace(array('.','_'),array(''),$_POST['username'] );
			if( empty($test) || !ctype_alnum($test) ){
				$failed[] = $langmessage['invalid_username'];
			}else{
				$passed[] = $langmessage['Username_ok'];
			}
			
			
		if( count($passed) > 0 ){
			foreach($passed as $message){
				echo '<li>';
				echo '<span class="passed">';
				echo $message;
				echo '</span>';
				echo '</li>';
			}
		}
			
		if( count($failed) > 0 ){
			foreach($failed as $message){
				echo '<li>';
				echo '<span class="failed">';
				echo $message;
				echo '</span>';
				echo '</li>';
			}
			return false;
		}
		return true;
	}
	
	function Install_DataFiles_New($destination = false, $config, $base_install = true ){
		global $langmessage;
		
		
		if( $destination === false ){
			$destination = $GLOBALS['dataDir'];
		}
		
		
		//set config variables
		//$config = array(); //because of ftp values
		
		$gpLayouts = array();
		$gpLayouts['default']['theme'] = 'One_Point_5/Blue';
		$gpLayouts['default']['color'] = '#93c47d';
		$gpLayouts['default']['label'] = $langmessage['default'];
		
		
		$config['gpLayout'] = 'default';
		//$config['theme'] = 'One_Point_5/Blue';
		$config['title'] = 'gp|Easy CMS';
		$config['keywords'] = 'gpEasy CMS, Easy CMS, Content Management, PHP, Free CMS, Website builder, Open Source';
		$config['desc'] = 'gp|Easy CMS is a complete and easy to use Content Management System. Written in PHP, it\'s free, open source and easy to use from the start.';
		$config['timeoffset'] = '0';
		$config['langeditor'] = 'inherit';
		$config['dateformat'] = '%m/%d/%y - %I:%M %p';
		$config['gpversion'] = $GLOBALS['gpversion'];
		$config['shahash'] = function_exists('sha1');
		$config['linkto'] = 'Powered by <a href="http://www.gpEasy.com" title="The Fast and Easy CMS">gpEasy CMS</a>';
		$config['gpuniq'] = common::RandomString(20);
		if( isset($_POST['hidegplink']) && $_POST['hidegplink'] == 'hide' ){
			$config['hidegplink'] = 'hide';
		}
		
		//$config['path_info'] = Install_Tools::GetPathInfo();
		
		//directories
		gpFiles::CheckDir($destination.'/data/_uploaded/image');
		gpFiles::CheckDir($destination.'/data/_uploaded/media');
		gpFiles::CheckDir($destination.'/data/_uploaded/file');
		gpFiles::CheckDir($destination.'/data/_uploaded/flash');
		gpFiles::CheckDir($destination.'/data/_sessions');
		
		
		//content
		$content = '<h1>Home</h1><p>Congratulations on a successfull installation!</p>
					<p>This is the default home page of your website which can be changed and edited as soon as you '.Install_Tools::Install_Link('Admin_Main','log in').'.
					Here are some more things you might want to do:</p>
					<ul>
					<li>Check out '.Install_Tools::Install_Link('Admin_Main','the admin pages').' for more administration options.</li>
					<li>Find themes and plugins on <a href="http://www.gpeasy.com">gpEasy.com</a> to customize your site.</li>';
		if( $base_install ){
			$content .= '<li>Delete the /include/install/install.php file for added security.</li>';
		}
		$content .= '</ul>';
		gpFiles::SaveTitle('Home',$content);
			
		gpFiles::SaveTitle('Another_Page',"<h1>Another</h1><p>This is just another page.</p>");

		gpFiles::SaveTitle('Another_SubLink','<h1>Another Sub-submenu</h1><p>This was created as a subpage of your <em>Another</em> page. You can easily '.Install_Tools::Install_Link('Admin_Menu','change the arrangement').' of all your pages.</p>');
		
		gpFiles::SaveTitle('About','<h1>About gpEasy CMS</h1><p><a href="http://www.gpEasy.com" title="gpeasy.com">gp|Easy</a> is a complete <a href="http://www.gpEasy.com/index.php/CMS">Content Management System (CMS)</a> that can help you create rich and flexible web sites with a simple and easy to use interface.</p>
		<h2>gpEasy CMS How To</h2>
		<p>Learn how to <a href="http://docs.gpeasy.org/index.php/Main/File%20Manager">manage your files</a>,
		<a href="http://docs.gpeasy.org/index.php/Main/Creating%20Galleries">create galleries</a> and more in the 
		<a href="http://docs.gpeasy.org/index.php/">gpEasy Documentation</a>.
		</p>
		
		<h2>gpEasy CMS Features</h2>
		<ul>
		<li>WYSIWYG Editor (CKEditor)</li>
		<li>Galleries (ColorBox)</li>
		<li>SEO Friendly Links</li>
		<li>Free and Open Source (GPL)</li>
		<li>Runs on PHP</li>
		<li>File Upload Manager</li>
		<li>Drag \'n Drop Theme Content</li>
		<li>Deleted File Trash Can</li>
		<li>Multiple User Administration</li>
		<li>Works in Safe Mode with FTP Functions</li>
		<li>Flat File Storage</li>
		<li>Fast Page Loading</li>
		<li>Fast and Easy Installation</li>
		<li>reCaptcha for Contact Form</li>
		<li>HTML Tidy (when available)</li>
		</ul>');
		
		//Side_Menu
		$file = $destination.'/data/_extra/Side_Menu.php';
		gpFiles::SaveFile($file,
				'<p>The text in this area of your pages is '.Install_Tools::Install_Link('Admin_Extra','also editable','cmd=extra&file=Side_Menu').'. 
				Since this will be a part of all of your pages, use it for significant information like announcements, news or links.</p>
				<h3><a href="http://www.gpeasy.com" title="The Fast and Easy CMS">gpEasy CMS</a></h3>
				<p><a href="http://www.gpeasy.com" title="The Fast and Easy CMS">gpEasy.com</a></p>
				<p><a href="http://docs.gpeasy.org" title="gpEasy CMS Documentation">Documentation</a></p>
				<p><a href="http://twitter.com/gpEasy" title="Follow gpEasy on Twitter">Follow on Twitter</a></p>
				<p><a href="http://www.facebook.com/pages/gpEasy/368153061397" title="Become a Fan of gpEasy">Become a Fan</a></p>
				<p><a href="http://freshmeat.net/projects/gpeasy" title="gpEasy on Freshmeat">@ freshmeat</a></p>
				<p><a href="http://sourceforge.net/project/stats/?group_id=264307&amp;ugn=gpeasy" title="gpEasy on Sourceforge.net">@ sourceforge</a></p>
				
				');
		
		//Header
		$file = $destination.'/data/_extra/Header.php';
		$contents = '<h1>'.Install_Tools::Install_Link('',$config['title']).'</h1>';
		$contents .= '<h4>'.'The Fast and Easy CMS'.'</h4>';
		gpFiles::SaveFile($file,$contents);
		
		//Footer
		$file = $destination.'/data/_extra/Footer.php';
		gpFiles::SaveFile($file,'<p>The text of the footer is editable.</p>');
		
		//contact html
		$file = $destination.'/data/_extra/Contact.php';
		gpFiles::SaveFile($file,'<h2>Contact Us</h2><p>Use the form below to contact us, and be sure to enter a valid email address if you want to hear back from us.</p>');
		
		
		//	gpmenu
		$new_menu = array();
		$new_menu['Home'] = 0;
		$new_menu['Another_Page'] = 0;
		$new_menu['Another_SubLink'] = 1;
		$new_menu['About'] = 0;
		$new_menu['Special_Contact'] = 1;
		
		//	links
		$new_titles = array();
		$new_titles['Home']['label'] = 'Home';
		$new_titles['Home']['type'] = 'page';
		
		$new_titles['Another_Page']['label'] = 'Another Page';
		$new_titles['Another_Page']['type'] = 'page';
		
		$new_titles['Another_SubLink']['label'] = 'Another SubLink';
		$new_titles['Another_SubLink']['type'] = 'page';
		
		$new_titles['About']['label'] = 'About';
		$new_titles['About']['type'] = 'page';
		
		$new_titles['Special_Site_Map']['type'] = 'special';
		$new_titles['Special_Site_Map']['lang_index'] = 'site_map';
		
		$new_titles['Special_Galleries']['type'] = 'special';
		$new_titles['Special_Galleries']['lang_index'] = 'galleries';
		
		$new_titles['Special_Contact']['type'] = 'special';
		$new_titles['Special_Contact']['lang_index'] = 'contact';		
		
		
		$pages = array();
		$pages['gpmenu'] = $new_menu;
		$pages['gptitles'] = $new_titles;
		$pages['gpLayouts'] = $gpLayouts;
		
		echo '<li>';
		if( !gpFiles::SaveArray($destination.'/data/_site/pages.php','pages',$pages) ){
			echo '<span class="failed">';
			//echo 'Could not save pages.php';
			echo sprintf($langmessage['COULD_NOT_SAVE'],'pages.php');
			echo '</span>';
			echo '</li>';
			return false;
		}
		echo '<span class="passed">';
		//echo 'Pages.php saved.';
		echo sprintf($langmessage['_SAVED'],'pages.php');
		echo '</span>';
		echo '</li>';	
		
		
		//users
		echo '<li>';
		$users = array();
		
		//sha1 is only available as of php 4.3, fixed in 1.6RC3
		if( function_exists('sha1') ){
			$users[$_POST['username']]['password'] = sha1(trim($_POST['password']));
		}else{
			$users[$_POST['username']]['password'] = md5(trim($_POST['password']));
		}
		
		$users[$_POST['username']]['granted'] = 'all';
		if( !gpFiles::SaveArray($destination.'/data/_site/users.php','users',$users) ){
			echo '<span class="failed">';
			echo sprintf($langmessage['COULD_NOT_SAVE'],'users.php');
			//echo 'Could not save users.php';
			echo '</span>';
			echo '</li>';
			return false;
		}
		echo '<span class="passed">';
		echo sprintf($langmessage['_SAVED'],'users.php');
		//echo 'Users.php saved.';
		echo '</span>';
		echo '</li>';
		
		
		
		//save config
		//not using SaveConfig() because $config is not global here
		echo '<li>';
		if( !gpFiles::SaveArray($destination.'/data/_site/config.php','config',$config) ){
		//if( !admin_tools::SaveConfig() ){
			echo '<span class="failed">';
			echo sprintf($langmessage['COULD_NOT_SAVE'],'config.php');
			//echo 'Could not save config.php';
			echo '</span>';
			echo '</li>';
			return false;
		}
		echo '<span class="passed">';
		echo sprintf($langmessage['_SAVED'],'config.php');
		//echo 'Config.php saved.';
		echo '</span>';
		echo '</li>';
		
		
		if( $base_install ){
			Install_Tools::InstallHtaccess($destination,$config);
		}

		return true;
	}
	
	
	/**
	 * attempt to create an htaccess file
	 * .htaccess creation only works for base_installations because of the $dirPrefix variable
	 * 		This is for the rewrite_rule and TestResponse() which uses AbsoluteUrl()
	 * 
	 * @access public
	 * @static
	 * @since 1.7
	 * 
	 * @param string $destination The root path of the installation
	 * @param array $config Current installation configuration
	 */
	function InstallHtaccess($destination,$config){
		global $install_ftp_connection;
		
		includeFile('admin/admin_permalinks.php');
		
		//only proceed with save if we can test the results
		if( !gpRemoteGet::Test() ){
			return;
		}
		
		$GLOBALS['config']['homepath'] = false; //to prevent a warning from bsoluteUrl()
		$file = $destination.'/.htaccess';
		
		$contents = '';
		$original_contents = false;
		if( file_exists($file) ){
			$original_contents = $contents = file_get_contents($file);
		}
		
		admin_permalinks::StripRules($contents); //the .htaccess file should not contain any rules
		$contents .= admin_permalinks::Rewrite_Rules(true);
		
		if( !isset($config['useftp']) ){
			//echo 'not using ftp';
			//ob_start(); //prevent error messages
			$fp = @fopen($file,'wb');
			if( $fp ){
				@fwrite($fp,$contents);
				fclose($fp);
			}
			chmod($file,0666);
			
			//return .htaccess to original state
			if( !admin_permalinks::TestResponse(true) ){
				if( $original_contents === false ){
					unlink($file);
				}else{
					$fp = @fopen($file,'wb');
					if( $fp ){
						@fwrite($fp,$original_contents);
						fclose($fp);
					}
				}
			}
			//ob_end_clean();
			return;
		}
			
			
		//using ftp
		$file = $config['ftp_root'].'/.htaccess';
		
		$temp = tmpfile();
		if( !$temp ){
			return false;
		}

		fwrite($temp, $contents);
		fseek($temp, 0); //Skip back to the start of the file being written to
		@ftp_fput($install_ftp_connection, $file, $temp, FTP_ASCII );
		fclose($temp);
		
		
		//return .htaccess to original state
		if( !admin_permalinks::TestResponse(true) ){
			if( $original_contents === false ){
				@ftp_delete($install_ftp_connection, $file);
			}else{
				$temp = tmpfile();
				fwrite($temp,$original_contents);
				fseek($temp,0);
				@ftp_fput($install_ftp_connection, $file, $temp, FTP_ASCII );
				fclose($temp);
			}
		}
	}
	
		
	function GetPathInfo(){
		$UsePathInfo =
			( strpos( php_sapi_name(), 'cgi' ) === false ) &&
			( strpos( php_sapi_name(), 'apache2filter' ) === false ) &&
			( strpos( php_sapi_name(), 'isapi' ) === false );
			
		return $UsePathInfo;
	}
	
	function Install_Link($href,$label,$query='',$attr=''){
		$text = '<';
		$text .= '?php';
		$text .= ' echo common::Link(\''.$href.'\',\''.$label.'\',\''.$query.'\',\''.$attr.'\'); ';
		$text .= '?';
		$text .= '>';
		return $text;
	}
	
}








/* 
 * Functions from skybluecanvas
 * 
 * 
 */

class FileSystem{
	
    function getExpectedPerms($file){
    
		if( !function_exists('posix_geteuid') ){
            return '777';
		}
    
		//if user id's match
		$puid = posix_geteuid();
		$suid = FileSystem::file_uid($file);
		if( ($suid !== false) && ($puid == $suid) ){
			return '755';
		}
		
		//if group id's match
		$pgid = posix_getegid();
		$sgid = FileSystem::file_group($file);
		if( ($sgid !== false) && ($pgid == $sgid) ){
			return '775';
		}
		
		//if user is a member of group
		$snam = FileSystem::file_owner($file);
		$pmem = FileSystem::process_members();
		if (in_array($suid, $pmem) || in_array($snam, $pmem)) {
			return '775';
		}
		
		return '777';
    }
	
	/*
	 * Compare Permissions
	 */
    function perm_compare($perm1, $perm2) {
		
		if( !FileSystem::ValidPermission($perm1) ){
			return false;
		}
		if( !FileSystem::ValidPermission($perm2) ){
			return false;
		}
		
/*
        if (strlen($perm1) != 3) return false;
        if (strlen($perm2) != 3) return false;
*/
		
        if (intval($perm1{0}) > intval($perm2{0})) {
            return false;
        }
        if (intval($perm1{1}) > intval($perm2{1})) {
            return false;
        }
        if (intval($perm1{2}) > intval($perm2{2})) {
            return false;
        }
        return true;
    }
	
	function ValidPermission(&$permission){
		if( strlen($permission) == 3 ){
			return true;
		}
		if( strlen($permission) == 4 ){
			if( intval($permission{0}) === 0 ){
				$permission = substr($permission,1);
				return true;
			}
		}
		return false;
	}
	
    /*
    * @description   Gets name of the file owner
    * @return string The name of the file owner
    */
	
	function file_owner($file) {
		$info = FileSystem::file_info($file);
		if (is_array($info)) {
			if (isset($info['name'])) {
				return $info['name'];
			}
			else if (isset($info['uid'])) {
				return $info['uid'];
			}
		}
		return false;
	}
	
		
    /*
    * @description  Gets Groups members of the PHP Engine
    * @return array The Group members of the PHP Engine
    */
	
	function process_members() {
		$info = FileSystem::process_info();
		if (isset($info['members'])) {
			return $info['members'];
		}
		return array();
	}	
	
	
    /*
    * @description Gets User ID of the file owner
    * @return int  The user ID of the file owner
    */
	
	function file_uid($file) {
		$info = FileSystem::file_info($file);
		if (is_array($info)) {
			if (isset($info['uid'])) {
				return $info['uid'];
			}
		}
		return false;
	}	
	
    /*
    * @description Gets Group ID of the file owner
    * @return int  The user Group of the file owner
    */
	
	function file_group($file) {
		$info = FileSystem::file_info($file);
		if (is_array($info) && isset($info['gid'])) {
			return $info['gid'];
		}
		return false;
	}
	
    /*
    * @description  Gets Info array of the file owner
    * @return array The Info array of the file owner
    */
	
	function file_info($file) {
		return posix_getpwuid(@fileowner($file));
	}

    /*
    * @description  Gets Group Info of the PHP Engine
    * @return array The Group Info of the PHP Engine
    */
	
	function process_info() {
		return posix_getgrgid(posix_getegid());
	}	
	
}
