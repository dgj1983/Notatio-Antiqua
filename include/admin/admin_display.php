<?php
defined('is_running') or die('Not an entry point...');


class admin_display extends display{
	var $pagetype = 'admin_display';
	var $requested = false;
	
	var $editable_content = false;
	var $editable_details = false;

	function admin_display($title){
		$this->requested = $title;
		$this->title = $title;
		$this->label = 'Administration';
		
	}
	
	function RunScript(){
		
		
		$this->SetTheme(); //needs to be before RunAdminScript() for admin_theme_content
		
		ob_start();
		$this->RunAdminScript();
		$this->contentBuffer = ob_get_clean();
	}
	
	
	//called by templates
	function GetContent(){
		
		echo '<div id="gpx_content">';
		echo '<div id="admincontent">';
		if( common::LoggedIn() ){	//the login form does not need this
			echo '<div id="admincontent_panel"><a href="#" class="docklink"></a>gp|Easy Administration</div>';
		}
		
		GetMessages();
		
		echo $this->contentBuffer;
		
		echo '</div>';
		echo '</div>';
	}
	
	
	function RunAdminScript(){
		global $dataDir,$langmessage;
		
		
		if( !common::LoggedIn() ){
			$cmd = common::GetCommand();
			switch($cmd){
				case 'send_password';
					if( $this->SendPassword() ){
						$this->LoginForm();
					}else{
						$this->FogottenPassword();
					}
				break;
				
				case 'forgotten':
					$this->FogottenPassword();
				break;
				default:
					$this->LoginForm();
				break;
			}
			
			return;
		}
		
		
		
		$scriptinfo = admin_tools::GetInfo($this->requested);
		if( $scriptinfo !== false ){
			
			if( admin_tools::HasPermission($this->requested) ){
				if( isset($scriptinfo['addon']) ){
					AddonTools::SetDataFolder($scriptinfo['addon']);
				}
				common::OrganizeFrequentScripts($this->requested);
	
				
				if( isset($scriptinfo['script']) ){
					require($dataDir.$scriptinfo['script']);
				}
				if( isset($scriptinfo['class']) ){
					new $scriptinfo['class']();
				}
				
				AddonTools::ClearDataFolder();
				
				return;
			}else{
				message($langmessage['not_permitted']);
			}
		}
		
		
		//these are here because they should be available to everyone
		switch($this->requested){
			case 'Admin_Browser':
				require($dataDir.'/include/admin/admin_browser.php');
				new admin_browser();
			return;
			
			case 'Admin_Preferences':
				require($dataDir.'/include/admin/admin_preferences.php');
				new admin_preferences();
			return;
			
			case 'Admin_About':
				require($dataDir.'/include/admin/admin_about.php');
				new admin_about();
			return;
		}
		
			
		$this->AdminPanel();
	}
	
	
	
	function AdminPanel(){
		global $langmessage;
		
		echo '<h2>'.$langmessage['administration'].'</h2>';
		echo '<div class="adminlinks">';
		admin_tools::GetAdminLinks();
		echo '<div style="clear:both"></div>';
		echo '</div>';


		
		echo '<h2>'.$langmessage['resources'].' (gpEasy.com)</h2>';
		echo '<div class="adminlinks">';
		echo '<ul>';
		echo '<li>';
		echo '<a href="'.$GLOBALS['addonBrowsePath'].'/Special_Addon_Plugins" name="remote">Plugins</a>';
		echo '</li>';
		echo '<li>';
		echo '<a href="'.$GLOBALS['addonBrowsePath'].'/Special_Addon_Themes" name="remote">Themes</a>';
		echo '</li>';
		echo '<li>';
		echo '<a href="'.$GLOBALS['addonBrowsePath'].'/Special_Services" name="remote">Services</a>';
		echo '</li>';
		echo '<ul>';
		echo '<div style="clear:both"></div>';
		echo '</div>';
		
		
		
		echo '<div id="adminfooter">';
		
		echo '<h3>gpEasy Links</h3>';
		echo '<ul>';
		echo '<li>';
		echo '<a href="http://www.gpeasy.com">Official gpEasy Site</a>';
		echo '</li>';
		echo '<li>';
		echo '<a href="https://sourceforge.net/tracker/?group_id=264307&amp;atid=1127698">Report A Bug</a>';
		echo '</li>';
		echo '</ul>';
		
		echo '<h3>Credits</h3>';
		echo '<ul>';
		echo '<li>';
		echo 'WYSIWYG editor by  <a href="http://ckeditor.com/">CKEditor.net</a>';
		echo '</li>';
		echo '<li>';
		echo 'Galleries made possible by <a href="http://colorpowered.com/colorbox/">ColorBox</a>';
		echo '</li>';
		echo '<li>';
		echo 'Icons by <a href="http://www.famfamfam.com/">famfamfam.com</a>';
		echo '</li>';
		echo '<li>';
		echo 'Theme inspiration from <a href="http://www.freecsstemplates.org/">freecsstemplates.org</a>, <a href="http://www.styleshout.com/">styleshout.com</a>, <a href="http://www.free-css-templates.com">free-css-templates.com</a>';
		echo '</li>';
		echo '</ul>';
		echo '</div>';
	}
	
	function SendPassword(){
		global $langmessage,$dataDir;
		
		include($dataDir.'/data/_site/users.php');
		require_once($GLOBALS['rootDir'].'/include/admin/admin_tools.php');
		$username = $_POST['username'];
		
		if( !isset($users[$username]) ){
			message($langmessage['OOPS']);
			return false;
		}
		
		$userinfo = $users[$username];
		
		
		
		if( empty($userinfo['email']) ){
			message($langmessage['no_email_provided']);
			return false;
		}
		
		$passwordChars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
		$newpass = str_shuffle($passwordChars);
		$newpass = substr($newpass,0,8);
		
		
		$users[$username]['newpass'] = common::hash(trim($newpass));
		if( !gpFiles::SaveArray($dataDir.'/data/_site/users.php','users',$users) ){
			message($langmessage['OOPS']);
			return false;
		}
		
		if( isset($_SERVER['HTTP_HOST']) ){
			$server = $_SERVER['HTTP_HOST'];
		}else{
			$server = $_SERVER['SERVER_NAME'];
		}			
		
		$link = common::AbsoluteLink('Admin_Main',$langmessage['login']);
		$message = sprintf($langmessage['passwordremindertext'],$server,$link,$username,$newpass);
		
		includeFile('tool/email.php');
		if( gp_email::SendEmail($userinfo['email'], $langmessage['new_password'], $message) ){
			
			list($namepart,$sitepart) = explode('@',$userinfo['email']);
			$showemail = substr($namepart,0,3).'...@'.$sitepart;
			message(sprintf($langmessage['password_sent'],$username,$showemail));
			return true;
		}
		
		message($langmessage['OOPS']);
		
		return false;
	}

	
	function FogottenPassword(){
		global $langmessage;
		$_POST += array('username'=>'');
		
		echo '<form class="loginform" action="'.common::GetUrl('Admin_Main').'" method="post">';
		echo '<h2>'.$langmessage['send_password'].'</h2>';
		echo '<table>';
		echo '<tr><td>';
			echo $langmessage['username'];
			echo '</td><td>';
			echo '<input type="text" class="text" name="username" value="'.htmlspecialchars($_POST['username']).'" />';
			echo '</td></tr>';
			
		echo '<tr><td>';
			echo '</td><td>';
			echo '<input type="hidden" class="submit" name="cmd" value="send_password" />';
			echo '<input type="submit" class="submit" name="aa" value="'.$langmessage['send_password'].'" />';
			echo ' <input type="submit" class="submit" name="cmd" value="'.$langmessage['cancel'].'" />';
			echo '</td></tr>';
		
		echo '</table>';
	}

	function LoginForm(){
		global $langmessage,$gptitles,$page;
		$_POST += array('username'=>'');
		$page->admin_js = true;
		includeFile('tool/sessions.php');
		gpsession::cookie('g',2);
		
		
/*
		if( isset($_GET['file']) && isset($gptitles[$_GET['file']]) ){
			$action = '/'.$_GET['file'];
		}else{
			$action = '/Admin';
		}
*/
		$action = 'Admin_Main';
		
		echo '<form class="loginform" action="'.common::GetUrl($action).'" method="post">';
		if( isset($_REQUEST['file']) && isset($gptitles[$_REQUEST['file']]) ){
			echo '<input type="hidden" name="file" value="'.htmlspecialchars($_REQUEST['file']).'" />';
		}
		echo '<h2>'.$langmessage['LOGIN_REQUIRED'].'</h2>';
		
		echo '<div style="display:none;" class="req_script">';
			echo '<table>';
			echo '<tr><td>';
				echo $langmessage['username'];
				echo '</td><td>';
				echo '<input type="text" class="text" name="username" value="'.htmlspecialchars($_POST['username']).'" />';
				echo '</td></tr>';
			echo '<tr><td>';
				echo $langmessage['password'];
				echo '</td><td>';
				echo '<input type="password" class="text" name="password" value="" />';
				echo '</td></tr>';
			echo '<tr><td>';
				echo $langmessage['remember_me'];
				echo '</td><td>';
				echo '<input type="checkbox" name="remember" checked="checked" />';
				echo '</td></tr>';
			echo '<tr><td>';
				echo '</td><td>';
					echo '<input type="submit" class="submit" name="aa" value="'.$langmessage['login'].'" />';
				echo '</td></tr>';
			echo '</table>';
		echo '</div>';

		echo '<div class="without_script">';
		echo '<p><b>'.$langmessage['JAVASCRIPT_REQ'].'</b></p>';
		echo '<p>';
		echo $langmessage['INCOMPAT_BROWSER'];
		echo ' ';
		echo $langmessage['MODERN_BROWSER'];
		echo '</p>';
		echo '</div>';
		
		
		echo '<input type="hidden" name="cmd" value="login" />';
		echo '</form>';
	}
	
	
}
