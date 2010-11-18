<?php
defined('is_running') or die('Not an entry point...');

global $langmessage, $install_ftp_connection;
$install_ftp_connection = false;

includeFile('install/install_tools.php');
includeFile('admin/admin_tools.php');
includeFile('tool/ftp.php');

?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
<title>gpEasy Installation</title>
<meta http-equiv="content-type" content="text/html; charset=UTF-8" />
<script type="text/javascript">

function toggleOptions(){
	document.getElementById('config_options').style.display='';
}

</script>

<style type="text/css">

body{
	margin:1em 5em;
	font-family: "Lucida Grande",Verdana,"Bitstream Vera Sans",Arial,sans-serif;
	background:#444;
	
}
div,p,td,th{
	font-size:12px;
	}
	
a{
	color:#4466aa;
	text-decoration:none;
	}
	
h1{
	margin-top:0;
	padding-top:0;
	}


.wrapper{
	position:relative;
	width:800px;
	background:#fff;
	margin:0 auto;
	
	-o-box-shadow: 0px 0px 10px #fff;
	-icab-box-shadow: 0px 0px 10px #fff;
	-khtml-box-shadow: 0px 0px 10px #fff;
	-moz-box-shadow: 0px 0px 10px #fff;
	-webkit-box-shadow: 0px 0px 10px #fff;
	
	-moz-border-radius: 10px;
	-webkit-border-radius: 10px;
	-o-border-radius: 10px;
	border-radius: 10px;
	padding: 23px;
	border:1px solid #fff;
}
	
.fullwidth{
	width:100%;
	}
.styledtable td, .styledtable th {
	border-bottom: 1px solid #ccc; 
	padding: 5px 20px;
	text-align:left;
	vertical-align:top;
	}
.styledtable th{	
	background-color:#ededed;
	background-color:#666;
	color:#f1f1f1;
	white-space:nowrap;
	}
	
.styledtable table td{
	padding:1px;
	border:0 none;
	}

.lang_select{
	position:absolute;
	top:23px;
	right:23px;
}

.lang_select select{
	font-size:130%;
	padding:7px 9px;
}
.lang_select option{
}

.sm{
	font-size:smaller;
}
input.text{
	width:12em;
}
.failed{
	color:#FF0000;
}
.passed{
	color:#009900;
}
.passed_orange{
	color:orange;
}

.code{
	margin:4px 0;
	padding:5px 7px;
	white-space:nowrap;
	background-color:#f5f5f5;
	}
.nowrap{
	white-space:nowrap;
}

</style>

</head>
<body>
<div class="wrapper">

<?php

new gp_install();

echo '</div>';
echo '</body></html>';


//Install Class
class gp_install{
	
	var $can_write_data = true;
	
	
	function gp_install(){
		global $languages,$install_language,$langmessage;
		
		//language preferences
			$install_language = 'en';
		
			if( isset($_GET['lang']) && isset($languages[$_GET['lang']]) ){
				$install_language = $_GET['lang'];
				
			}elseif( isset($_COOKIE['lang']) && isset($languages[$_COOKIE['lang']]) ){
				$install_language = $_COOKIE['lang'];
			}
			setcookie('lang',$install_language);
			
			common::GetLangFile('install.php',$install_language);
	
	
		echo '<h1>'.$langmessage['Installation'].'</h1>';
		
		$installed = false;
		$cmd = common::GetCommand();
		switch($cmd){
			
			case 'Install_Safe':
				$installed = Install_Safe();
			break;
			
			case 'Continue':
				FTP_Prepare();
			break;
			
			case 'Install':
				$installed = Install_Normal();
			break;
		}
		
		if( !$installed ){
			LanguageForm();
			$this->CheckFolders();
		}else{
			Installed();
		}
		
	}
	
	function CheckFolders(){
		global $ok,$langmessage;
		
		$ok = true;
		
		echo '<h2>'.$langmessage['Checking_server'].'...</h2>';
		echo '<table cellpadding="5" cellspacing="0" class="styledtable fullwidth">';
		echo '<tr>';
		echo '<th>'.$langmessage['Checking'].'...</th>';
		echo '<th>'.$langmessage['Status'].'</th>';
		echo '<th>'.$langmessage['Current_Value'].'</th>';
		echo '<th>'.$langmessage['Expected_Value'].'</th>';
		echo '</tr>';
		
		
		$this->CheckDataFolder();
		
		//Check PHP Version
		echo '<tr>';
			echo '<td>';
			echo $langmessage['PHP_Version'];
			echo '</td>';
			if( !function_exists('version_compare') ){
				echo '<td class="failed">'.$langmessage['Failed'].'</td>';
				echo '<td class="failed">???</td>';
				$ok = false;
			}elseif( version_compare(phpversion(),"4.3") < 0){
				echo '<td class="failed">'.$langmessage['Failed'].'</td>';
				echo '<td class="failed">'.phpversion().'</td>';
				$ok = false;
			}else{
				echo '<td class="passed">'.$langmessage['Passed'].'</td>';
				echo '<td class="passed">'.phpversion().'</td>';
			}
			echo '<td>4.3+</td>';
			echo '</tr>';
			
		
		//make sure $_SERVER['SCRIPT_NAME'] is set
		echo '<tr>';
			echo '<td>';
			echo '<a href="http://www.php.net/manual/reserved.variables.server.php" target="_blank">';
			echo 'SCRIPT_NAME or PHP_SELF';
			echo '</a>';
			echo '</td>';
			if( common::GetEnv('SCRIPT_NAME','index.php') || common::GetEnv('PHP_SELF','index.php') ){
				echo '<td class="passed">'.$langmessage['Passed'].'</td>';
				echo '<td class="passed">'.$langmessage['Set'].'</td>';
			}else{
				echo '<td class="failed">'.$langmessage['Failed'].'</td>';
				echo '<td class="failed">'.$langmessage['Not_Set'].'</td>';
				$ok = false;
			}
			echo '<td>'.$langmessage['Set'].'</td>';
			echo '</tr>';
			
		//Check Safe Mode
		$checkValue = ini_get('safe_mode');
		echo '<tr>';
			echo '<td>';
			echo '<a href="http://php.net/manual/features.safe-mode.php" target="_blank">';
			echo 'Safe Mode';
			echo '</a>';
			echo '</td>';
			if( $checkValue ){
				echo '<td class="passed_orange">'.$langmessage['See_Below'].'</td>';
				echo '<td class="passed_orange">'.$langmessage['On'].'</td>';
				$ok = false;
			}else{
				echo '<td class="passed">'.$langmessage['Passed'].'</td>';
				echo '<td class="passed">'.$langmessage['Off'].'</td>';
			}
			echo '<td>'.$langmessage['Off'].'</td>';
			echo '</tr>';
			
		//Check register_globals
		$checkValue = ini_get('register_globals');
		echo '<tr>';
			echo '<td>';
			echo '<a href="http://php.net/manual/security.globals.php" target="_blank">';
			echo 'Register Globals';
			echo '</a>';
			echo '</td>';
			if( $checkValue ){
				echo '<td class="passed_orange">'.$langmessage['Passed'].'</td>';
				echo '<td class="passed_orange">'.$langmessage['On'].'</td>';
			}else{
				echo '<td class="passed">'.$langmessage['Passed'].'</td>';
				echo '<td class="passed">'.$langmessage['Off'].'</td>';
			}
			echo '<td>'.$langmessage['Off'].'</td>';
			echo '</tr>';
			
		//Check ini_get( 'magic_quotes_sybase' )
		$checkValue = ini_get('magic_quotes_sybase');
		echo '<tr>';
			echo '<td>';
			echo '<a href="http://php.net/manual/security.magicquotes.disabling.php" target="_blank">';
			echo 'Magic Quotes Sybase';
			echo '</a>';
			echo '</td>';
			if( $checkValue ){
				echo '<td class="failed">'.$langmessage['Failed'].'</td>';
				echo '<td class="failed">'.$langmessage['On'].'</td>';
				$ok = false;
			}else{
				echo '<td class="passed">'.$langmessage['Passed'].'</td>';
				echo '<td class="passed">'.$langmessage['Off'].'</td>';
			}
			echo '<td>'.$langmessage['Off'].'</td>';
			echo '</tr>';
			
		//magic_quotes_runtime
		$checkValue = ini_get('magic_quotes_runtime');
		echo '<tr>';
			echo '<td>';
			echo '<a href="http://php.net/manual/security.magicquotes.disabling.php" target="_blank">';
			echo 'Magic Quotes Runtime';
			echo '</a>';
			echo '</td>';
			if( $checkValue ){
				echo '<td class="failed">'.$langmessage['Failed'].'</td>';
				echo '<td class="failed">'.$langmessage['On'].'</td>';
				$ok = false;
			}else{
				echo '<td class="passed">'.$langmessage['Passed'].'</td>';
				echo '<td class="passed">'.$langmessage['Off'].'</td>';
			}
			echo '<td>'.$langmessage['Off'].'</td>';
			echo '</tr>';		
		
		echo '<tr>';
		echo '<th>'.$langmessage['Checking'].'...</th>';
		echo '<th>'.$langmessage['Status'].'</th>';
		echo '<th colspan="2">'.$langmessage['Notes'].'</th>';
		echo '</tr>';
		
		$this->CheckIndexHtml();
		$this->CheckImages();
		$ok = $ok && $this->CheckPath();
		
		
		echo '</table>';
		echo '<a href="">'.$langmessage['Refresh'].'</a>';
		
		if( $ok ){
			Form_Entry();
			return;
		}
		
		if( ini_get('safe_mode') ){
			Form_SafeMode();
			return;
		}
		
		if( !$this->can_write_data ){
			Form_Permissions();
		}
		
	}
	
	
	function CheckDataFolder(){
		global $ok,$rootDir,$langmessage;
		
		echo '<tr>';
		
		echo '<td class="nowrap">';
		$folder = $rootDir.'/data';
		if( strlen($folder) > 23 ){
			$show = '...'.substr($folder,-20);
		}else{
			$show = $folder;
		}
		echo sprintf($langmessage['Permissions_for'],$show);
		echo ' &nbsp; ';
		echo '</td>';
		
		$expected = FileSystem::getExpectedPerms($folder);
		
		
		//
		if( !is_dir($folder)){
			if(!@mkdir($folder, 0777)) {
				echo '<td class="passed_orange">'.$langmessage['See_Below'].' (0)</td>';
				$this->can_write_data = $ok = false;
			}else{
				echo '<td class="passed">'.$langmessage['Passed'].'</td>';
			}
		}elseif( is_writable($folder) ){
			echo '<td class="passed">'.$langmessage['Passed'].'</td>';
		}else{
			echo '<td class="passed_orange">'.$langmessage['See_Below'].' (1)</td>';
			$this->can_write_data = $ok = false;
		}
			
		//show current info
		if( $current = @substr(decoct(fileperms($folder)), -3) ){
			if( FileSystem::perm_compare($expected,$current) ){
				echo '<td class="passed">';
				echo $current;
			}else{
				echo '<td class="passed_orange">';
				echo $current;
			}
		}else{
			echo '<td class="passed_orange">';
			echo '???';
		}
		echo '</td>';
		echo '<td>';
		echo $expected;
		echo '</td>';
		echo '</tr>';
	}
	
	
	
	/*
	 * 
	 * Check Functions 
	 * 
	 */
	
	
	//very unlikely, cannot have two ".php/" in path: see SetGlobalPaths()
	function CheckPath(){
		global $langmessage;
		
		$path = __FILE__;
			
		$test = $path;
		$pos = strpos($test,'.php');
		if( $pos === false ){
			return true;
		}
		$test = substr($test,$pos+4);
		$pos = strpos($test,'.php');
		if( $pos === false ){
			return true;
		}
		
		echo '<tr>';
			echo '<td class="nowrap">';
			if( strlen($path) > 30 ){
				echo '...'.substr($path,-27);
			}else{
				echo $path;
			}
			echo '</td>';
			echo '<td class="failed">'.$langmessage['Failed'].'</td>';
			echo '<td class="failed" colspan="2">';
			echo str_replace('.php','<b>.php</b>',$path);
			echo '<br/>';
			echo 'The file structure contains multiple cases of ".php".';
			echo ' To Continue, rename your file structure so that directories do not use ".php".';
			echo '</td>';
			echo '</tr>';
		
			
		return false;
	}
	
	function CheckIndexHtml(){
		global $langmessage,$rootDir;
		
		$index = $rootDir.'/index.html';
		
		
		echo '<tr>';
			echo '<td>';
			if( strlen($index) > 30 ){
				echo '...'.substr($index,-27);
			}else{
				echo $index;
			}
			echo '</td>';
			
			if( !file_exists($index) ){
				echo '<td class="passed">'.$langmessage['Passed'].'</td>';
				echo '<td class="passed" colspan="2"></td>';
			}else{
				echo '<td class="passed_orange">'.$langmessage['Passed'].'</td>';
				echo '<td class="passed_orange" colspan="2">'.$langmessage['index.html exists'].'</td>';
			}
			echo '</tr>';		
		
	}
		
	
	function CheckImages(){
		global $langmessage;
		
		$passed = false;
		$supported = array();
		if( function_exists('imagetypes') ){
			$passed = true;
			$supported_types = imagetypes();
			if( $supported_types & IMG_JPG ){
				$supported[] = 'jpg';
			}
			if( $supported_types & IMG_PNG) {
				$supported[] = 'png';
			}
			if( $supported_types & IMG_WBMP) {
				$supported[] = 'bmp';
			}
			if( $supported_types & IMG_GIF) {
				$supported[] = 'gif';
			}
		}
		
		
		
		echo '<tr>';
			echo '<td>';
			echo '<a href="http://www.php.net/manual/en/book.image.php" target="_blank">';
			echo $langmessage['image_functions'];
			echo '</a>';
			echo '</td>';
			if( $passed ){
				
				if( count($supported) == 4 ){
					echo '<td class="passed">'.$langmessage['Passed'].'</td>';
					echo '<td class="passed" colspan="2">'.implode(', ',$supported).'</td>';
				}else{
					echo '<td class="passed_orange">'.$langmessage['partially_available'].'</td>';
					echo '<td class="passed_orange" colspan="2">'.implode(', ',$supported).'</td>';
				}
				
			}else{
				echo '<td class="passed_orange">'.$langmessage['Passed'].'</td>';
				echo '<td class="passed_orange" colspan="2">'.$langmessage['unavailable'].'</td>';
			}
			echo '</tr>';		
		
		
	}

	
	
	
}//end class



//Install Functions


	function LanguageForm(){
		global $languages, $install_language;
		
		echo '<div class="lang_select">';
		echo '<form action="" method="get">';
		echo '<select name="lang" onchange="this.form.submit()">';
		foreach($languages as $lang => $label){
			if( $lang === $install_language ){
				echo '<option value="'.$lang.'" selected="selected">';
			}else{
				echo '<option value="'.$lang.'">';
			}
			//echo $lang.' - '.$label;
			echo '&nbsp; '.$label.' &nbsp; ('.$lang.')';
			echo '</option>';
		}
		
		echo '</select>';
		echo '<div class="sm">';
		echo '<a href="http://ptrans.wikyblog.com/pt/gpEasy" target="_blank">Help translate gpEasy</a>';
		echo '</div>';

		echo '</form>';
		echo '</div>';
	}		
		



	function Installed(){
		global $langmessage;
		echo '<h4>'.$langmessage['Installation_Was_Successfull'].'</h4>';
		echo '<ul>';
		echo '<li>';
		echo '<a href="">'.$langmessage['View_your_web_site'].'</a>';
		echo '</li>';
		echo '<li>';
		echo '<a href="index.php/Admin">'.$langmessage['Log_in_and_start_editing'].'</a>';
		echo '</li>';
		echo '</ul>';
	}	

	function Form_Entry() {
		global $langmessage;
		
		echo '<h3>'.$langmessage['configuration'].'</h3>';
		//echo '<h3>'.$langmessage['User Details'].'</h3>';
		echo '<form action="" method="post">';
		echo '<table cellspacing="0" class="styledtable">';
		Install_Tools::Form_UserDetails();
		Install_Tools::Form_Configuration();
		echo '</table>';
		echo '<p>';
		echo '<input type="hidden" name="cmd" value="Install" />';
		echo '<input type="submit" class="submit" name="aaa" value="'.$langmessage['Install'].'" />';
		echo '</p>';
		echo '</form>';
	}
	
	function Form_Permissions(){
		global $langmessage,$rootDir;
			
		echo '<div>';
		echo '<h3>'.$langmessage['Changing_File_Permissions'].'</h3>';
		echo '<p>';
		echo $langmessage['REFRESH_AFTER_CHANGE'];
		echo '</p>';
		
		echo '<table cellpadding="5" cellspacing="0"  class="styledtable fullwidth">';
		echo '<tr><th>FTP</th>';
		echo '<th>-OR-</th>';
		echo '<th>Manual</th>';
		echo '</tr>';
		echo '<tr><td>';
		
		if( !function_exists('ftp_connect') ){
			echo $langmessage['MOST_FTP_CLIENTS'];
			
		}else{
			echo '<form action="" method="post">';
			echo '<table cellpadding="5" cellspacing="0">';
			Form_FTPDetails();
			echo '<tr>';
				echo '<td align="left">&nbsp;</td><td>';
				echo '<input type="hidden" name="cmd" value="Continue">';
				echo '<input type="submit" class="submit" name="aaa" value="'.$langmessage['Continue'].'">';
				echo '</td>';
				echo '</tr>';
			echo '</table>';
			
			echo '</form>';
			
		}
		
		echo '</td><td>';
		echo '</td><td>';
		
		echo $langmessage['LINUX_CHMOD'];
		echo '<div class="code"><tt>';
		echo 'chmod 777 "'.$rootDir.'/data"';
		//echo 'chmod 777 "/'.$langmessage['your_install_directory'].'/data"';
		echo '</tt></div>';
		echo '<a href="">'.$langmessage['Refresh'].'</a>';

		echo '</td></tr>';
		echo '</table>';
		echo '</div>';			
		
		
	}
	
	
	function Form_SafeMode(){
		global $langmessage;
		

		//echo '<h3>'.$langmessage['configuration'].'</h3>';
		
		if( !function_exists('ftp_connect') ){
			echo '<em>'.$langmessage['Safe_Mode_Unavailable'].'</em>';
			return;
		}
		
		echo '<p>'.$langmessage['FTP_INFORMATION'].'</p>';
		echo '<p> <em>'.$langmessage['Warning'].':</em> '.$langmessage['FTP_WARNING'].'</p>';
		
		echo '<form action="" method="post">';
		echo '<table cellpadding="5" cellspacing="0" class="styledtable">';
		Install_Tools::Form_UserDetails();
		Form_FTPDetails(true);
		Install_Tools::Form_Configuration();

		echo '</table>';
		
		echo '<p>';
			echo '<input type="hidden" name="cmd" value="Install_Safe">';
			echo '<input type="submit" class="submit" name="aaa" value="'.$langmessage['Install'].'">';
		echo '</p>';

		
		echo '</form>';		
	}	
	
	
	function Form_FTPDetails($required=false){
		global $langmessage;
		$_POST += array('ftp_server'=>gpftp::GetFTPServer(),'ftp_user'=>'');
		
		if( $required ){
			$required = '*';
		}
		echo '<tr>';
			echo '<td align="left">'.$langmessage['FTP_Server'].$required.' </td><td>';
			echo '<input class="text" type="text" class="text" size="20" name="ftp_server" value="'. htmlspecialchars($_POST['ftp_server']) .'">';
			echo '</td>';
			echo '</tr>';
			
		echo '<tr>';
			echo '<td align="left">'.$langmessage['FTP_Username'].$required.' </td><td>';
			echo '<input class="text" type="text" class="text" size="20" name="ftp_user" value="'. htmlspecialchars($_POST['ftp_user']) .'">';
			echo '</td>';
			echo '</tr>';
			
		echo '<tr>';
			echo '<td align="left">'.$langmessage['FTP_Password'].$required.' </td><td>';
			echo '<input class="text" type="password" size="20" name="ftp_pass" value="" />';
			echo '</td>';
			echo '</tr>';
	}



	function Install_Normal(){
		global $langmessage,$install_language;

		echo '<h2>'.$langmessage['Installing'].'</h2>';
		echo '<ul>';
		
		$config = array();
		$config['language'] = $install_language;
		
		$success = false;
		if( Install_Tools::gpInstall_Check() ){
			$success = Install_Tools::Install_DataFiles_New(false, $config);
		}
		echo '</ul>';
		
		return $success;
	}
	
	
	function Install_Safe(){
		global $langmessage,$install_language,$config,$install_ftp_connection;
		
		echo '<h2>'.$langmessage['Installing_in_Safe_Mode'].'</h2>';
		echo '<ul>';
			
		
		$ftp_root = false;
		if( Install_FTPConnection($ftp_root) === false ){
			return false;
		}
		
		//configuration
		$config = array();
		$config['useftp'] = true;
		$config['ftp_root'] = $ftp_root;
		$config['ftp_user'] = $_POST['ftp_user'];
		$config['ftp_server'] = $_POST['ftp_server'];
		$config['ftp_pass'] = $_POST['ftp_pass'];
		$config['language'] = $install_language;
		
		$ftpData = $ftp_root.'/data';
		ftp_site($install_ftp_connection, 'CHMOD 0777 '. $ftpData );
		
		$success = false;
		if( Install_Tools::gpInstall_Check() ){
			$success = Install_Tools::Install_DataFiles_New(false, $config);
		}		
		echo '</ul>';
		
		return $success;		
	}
		
		
	
	function FTP_Prepare(){
		global $langmessage,$install_ftp_connection;
		
		echo '<h2>'.$langmessage['Using_FTP'].'...</h2>';
		echo '<ul>';
		
		$ftp_root = false;
		if( Install_FTPConnection($ftp_root) === false ){
			return;
		}
		
		//Change Mode of /data
		echo '<li>';
			$ftpData = $ftp_root.'/data';
			$modDir = ftp_site($install_ftp_connection, 'CHMOD 0777 '. $ftpData );
			if( !$modDir ){
				echo '<span class="failed">';
				echo sprintf($langmessage['Could_Not_'],'<em>CHMOD 0777 '. $ftpData.'</em>');
				//echo 'Could not <em>CHMOD 0777 '. $ftpData.'</em>';
				echo '</span>';
				echo '</li></ul>';
				return false;
			}else{
				echo '<span class="passed">';
				echo sprintf($langmessage['FTP_PERMISSIONS_CHANGED'],'<em>'.$ftpData.'</em>');
				//echo 'File permissions for <em>'.$ftpData.'</em> changed.';
				echo '</span>';
			}
			echo '</li>';
		
		echo '<li>';
				echo '<span class="passed">';
				echo '<b>'.$langmessage['Success_continue_below'].'</b>';
				echo '</span>';
				echo '</li>';
		
		echo '</ul>';
		
		
	
	}
	
	function Install_FTPConnection(&$ftp_root){
		global $rootDir,$langmessage,$install_ftp_connection;
		
		//test for functions
		echo '<li>';
			if( !function_exists('ftp_connect') ){
				echo '<span class="failed">';
				echo $langmessage['FTP_UNAVAILABLE'];
				echo '</span>';
				echo '</li></ul>';
				return false;
			}else{
				echo '<span class="passed">';
				echo $langmessage['FTP_AVAILABLE'];
				echo '</span>';
			}
			echo '</li>';
			
		//Try to connect
		echo '<li>';
			$install_ftp_connection = @ftp_connect($_POST['ftp_server'],21,6);
			if( !$install_ftp_connection ){
				echo '<span class="failed">';
				echo sprintf($langmessage['FAILED_TO_CONNECT'],'<em>'.htmlspecialchars($_POST['ftp_server']).'</em>');
				echo '</span>';
				echo '</li></ul>';
				return false;
			}else{
				echo '<span class="passed">';
				echo sprintf($langmessage['CONNECTED_TO'],'<em>'.htmlspecialchars($_POST['ftp_server']).'</em>');
				//echo 'Connected to <em>'.$_POST['ftp_server'].'</em>';
				echo '</span>';
			}
			echo '</li>';
		
		//Log in
		echo '<li>';
			$login_result = @ftp_login($install_ftp_connection, $_POST['ftp_user'], $_POST['ftp_pass']);
			if( !$login_result ){
				echo '<span class="failed">';
				echo sprintf($langmessage['NOT_LOOGED_IN'],'<em>'.htmlspecialchars($_POST['ftp_user']).'</em>');
				//echo 'Could not log in user  <em>'.$_POST['ftp_user'].'</em>';
				echo '</span>';
				echo '</li></ul>';
				return false;
			}else{
				echo '<span class="passed">';
				echo sprintf($langmessage['LOGGED_IN'],'<em>'.htmlspecialchars($_POST['ftp_user']).'</em>');
				//echo 'User <em>'.$_POST['ftp_user'].'</em> logged in.';
				echo '</span>';
			}
			echo '</li>';
			
		//Get FTP Root
		
		echo '<li>';
			$ftp_root = false;
			if( $login_result ){
				$ftp_root = gpftp::GetFTPRoot($install_ftp_connection,$rootDir);
			}
			if( !$ftp_root ){
			//if( !$login_result ){
				echo '<span class="failed">';
				echo $langmessage['ROOT_DIRECTORY_NOT_FOUND'];
				echo '</span>';
				echo '</li></ul>';
				return false;
			}else{
				echo '<span class="passed">';
				echo sprintf($langmessage['FTP_ROOT'],'<em>'.$ftp_root.'</em>');
				//echo 'FTP Root found: <em>'.$ftp_root.'</em>';
				echo '</span>';
			}
			echo '</li>';			
			
		return true;
	}
	
	
	
	

