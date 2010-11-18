<?php

/* 

Custom SESSIONS

*/


class gpsession{
	
	
	function LogIn(){
		global $dataDir,$langmessage;
		
		if( !isset($_COOKIE['g']) && !isset($_COOKIE['gpEasy']) ){
			message($langmessage['COOKIES_REQUIRED']);
			return false;
		}
		
		//delete the entry in $sessions if we're going to create another one with login
		if( isset($_COOKIE['gpEasy']) ){
			gpsession::CleanSession($_COOKIE['gpEasy']);
		}
		
		
		include($dataDir.'/data/_site/users.php');
		$username = $_POST['username'];
		
		if( !isset($users[$username]) ){
			message($langmessage['incorrect_login']);
			return false;
		}
		$users[$username] += array('attempts'=> 0,'granted'=>'');
		$userinfo =& $users[$username];
		
		//Check Attempts
		if( $userinfo['attempts'] >= 5 ){
			$timeDiff = (time() - $userinfo['lastattempt'])/60; //minutes
			if( $timeDiff < 10 ){
				message($langmessage['LOGIN_BLOCK'],ceil(10-$timeDiff));
				return false;
			}
		}
		
		$pass = common::hash(trim($_POST['password']));
		
		//reset password
		if( isset($userinfo['newpass']) ){
			if( $userinfo['newpass'] == $pass ){
				$userinfo['password'] = $pass;
			}
		}
		
		//if passwords don't match
		if( $userinfo['password'] != $pass ){
			message($langmessage['incorrect_login']);
			$url = common::GetUrl('Admin?cmd=forgotten');
			message($langmessage['forgotten_password'],$url);
			gpsession::UpdateAttempts($users,$username);
			return false;
		}
		
		if( isset($userinfo['newpass']) ){
			unset($userinfo['newpass']); //will be saved in UpdateAttempts
		}
		
		//update the session files to .php files
		//changes to $userinfo will be saved by UpdateAttempts() below
		$userinfo = gpsession::SetSessionFileName($userinfo,$username);
		$user_file_name = $userinfo['file_name'];
		
		
		//logged in!
		if( gpsession::create($user_file_name) ){
			global $gpAdmin;
			unset($gpAdmin['adminuser']); //not needed any more, so it's being deleted
			$gpAdmin['username'] = $username;
			$gpAdmin['granted'] = $userinfo['granted'];
			message($langmessage['logged_in']);
		}else{
			message($langmessage['OOPS']);
		}
		
		//need to save the user info regardless of success or not
		gpsession::UpdateAttempts($users,$username,true);
		return true;
	}
	
	
	//get/set the value of $userinfo['file_name']
	function SetSessionFileName($userinfo,$username){
		
		if( !isset($userinfo['file_name']) ){
			if( isset($userinfo['cookie_id']) ){
				$old_file_name = 'gpsess_'.$userinfo['cookie_id'];
				unset($userinfo['cookie_id']);
			}else{
				//$old_file_name = 'gpsess_'.md5($username.$pass);
				$old_file_name = 'gpsess_'.md5($username.$userinfo['password']);
				
			}
			$userinfo['file_name'] = gpsession::UpdateFileName($old_file_name);
		}
		return $userinfo;
	}
	
	function UpdateFileName($old_file_name){
		global $dataDir;
		
		//get a new unique name
		do{
			$new_file_name = 'gpsess_'.common::RandomString(40).'.php';
			$new_file = $dataDir.'/data/_sessions/'.$new_file_name;
		}while( file_exists($new_file) );
		
		
		$old_file = $dataDir.'/data/_sessions/'.$old_file_name;
		if( !file_exists($old_file) ){
			return $new_file_name;
		}
		
		if( rename($old_file,$new_file) ){
			return $new_file_name;
		}
		return $old_file_name;
	}
	
	function LogOut(){
		global $langmessage;
		
		if( !isset($_COOKIE['gpEasy']) ){
			return false;
		}
		
		gpsession::cookie('gpEasy','',time()-42000);
		gpsession::CleanSession($_COOKIE['gpEasy']);
		message($langmessage['LOGGED_OUT']);
	}
	
	function CleanSession($session_id){
		//remove the session_id from session_ids.php
		$sessions = gpsession::GetSessionIds();
		unset($sessions[$_COOKIE['gpEasy']]);
		gpsession::SaveSessionIds($sessions);
	}
	
	
	function cookie($name,$value,$expires = false){
		global $config;
		
		$cookiePath = '/';
		if( !empty($config['dirPrefix']) ){
			$cookiePath = $config['dirPrefix'];
		}
		$cookiePath = str_replace(' ','%20',$cookiePath);
		
		
		if( $expires === false ){
			$expires = time()+2592000;
		}elseif( $expires === true ){
			$expires = 0; //expire at end of session
		}
		
		setcookie($name, $value, $expires, $cookiePath); //need to take care of spaces!
	}
	
	
	
	
	function UpdateAttempts($users,$username,$reset = false){
		global $dataDir;
		
		if( $reset ){
			$users[$username]['attempts'] = 0;
		}else{
			$users[$username]['attempts']++;
		}
		$users[$username]['lastattempt'] = time();
		gpFiles::SaveArray($dataDir.'/data/_site/users.php','users',$users);
	}	
	
	
	/* read/write handler functions */
	

	
	function create($user_file_name){
		global $dataDir;
		$user_file = $dataDir.'/data/_sessions/'.$user_file_name;
		
		//get session id
		$sessions = gpsession::GetSessionIds();
		do{
			$session_id = common::RandomString(40);
		}while( isset($sessions[$session_id]) );
		
		
		$expires = !isset($_POST['remember']);
		gpsession::cookie('gpEasy',$session_id,$expires);
		
		//save session id
		$sessions[$session_id] = array();
		$sessions[$session_id]['file_name'] = $user_file_name;
		$sessions[$session_id]['ip'] = common::IP($_SERVER['REMOTE_ADDR'],1);
		$sessions[$session_id]['time'] = time();
		gpsession::SaveSessionIds($sessions);
		
		//make sure the user's file exists
		if( $user_file && !file_exists($user_file) ){
			$fp = gpFiles::fopen($user_file);
			fclose($fp);
		}
		return gpsession::start($session_id);
	}
	
	//get a unique id
	/* deprecated */
	function GenerateId(){
		return common::RandomString(40);
	}
	
	function GetSessionIds(){
		global $dataDir;
		$sessions = array();
		$sessions_file = $dataDir.'/data/_site/session_ids.php';
		if( file_exists($sessions_file) ){
			require($sessions_file);
		}
		
		return $sessions;
	}
	
	function SaveSessionIds($sessions){
		global $dataDir;
		
		
		
		while( $current = current($sessions) ){
			$key = key($sessions);
			
			//delete if older than 
			if( isset($current['time']) && ($current['time'] < (time() - 1209600)) ){
			//if( $current['time'] < time() - 2592000 ){ //one month
				unset($sessions[$key]);
				$continue = true;
			}else{
				next($sessions);
			}
		}
		
		//clean
		$sessions_file = $dataDir.'/data/_site/session_ids.php';
		gpFiles::SaveArray($sessions_file,'sessions',$sessions);
	}
	
	
	//start a session
	function start($session_id){
		global $langmessage, $dataDir;

		//get the session file
		$sessions = gpsession::GetSessionIds();
		if( !isset($sessions[$session_id]) ){
			gpsession::cookie('gpEasy','',time()-42000); //make sure the cookie is deleted
			return false;
		}
		$info = $sessions[$session_id];
		
		//check ip address
		$ip = common::IP($_SERVER['REMOTE_ADDR'],1);
		if( !isset($info['ip']) || ($info['ip'] != $ip) ){
			gpsession::cookie('gpEasy','',time()-42000); //make sure the cookie is deleted
			return false;
		}
		
		
		$session_file = $dataDir.'/data/_sessions/'.$info['file_name'];
		if( ($session_file === false) || !file_exists($session_file) ){
			gpsession::cookie('gpEasy','',time()-42000); //make sure the cookie is deleted
			return false;
		}
		
		
		//update time and move to end of $sessions array
		if( $info['time'] < time() - 259200 ){ //three days
		//if( $info['time'] < time() - 604800 ){ //one week
			unset($sessions[$session_id]);
			$info['time'] = time();
			$sessions[$session_id] = $info;
			gpsession::SaveSessionIds($sessions);
		}
		
		$gpAdmin = array();
		require($session_file);
		
		$GLOBALS['gpAdmin'] = $gpAdmin;
		$checksum =& $GLOBALS['gpAdmin']['checksum'];
		$gpAdmin['temp'] = rand(0,100);
		
		//update to version 1.7a3, add file_editing permission
		if( !isset($fileVersion) && !empty($gpAdmin['granted']) ){
			if( $GLOBALS['gpAdmin']['granted'] != 'all' ){
				$GLOBALS['gpAdmin']['granted'] .= ',file_editing';
				gpsession::AddFileEditing($gpAdmin['username']);
			}
		}
		
		//update to version 1.7
		$GLOBALS['gpAdmin'] += array('browser_display'=>'browser_icons');
		//update to version 1.7a4
		$GLOBALS['gpAdmin'] += array('paneldock'=>true,'panelposx'=>100,'panelposy'=>100);
		
		register_shutdown_function(array('gpsession','close'),$session_file,$checksum);
		
		gpsession::SaveSetting();
		
		return true;
	}
	
	//update to version 1.7a3
	function AddFileEditing($username){
		global $dataDir;
		
		$file = $dataDir.'/data/_site/users.php';
		if( !file_exists($file) ){
			return;
		}
		
		include($file);
		
		if( !isset($users[$username]) || !isset($users[$username]['granted']) ){
			return;
		}
		$users[$username]['granted'] .= ',file_editing';
		gpFiles::SaveArray($file,'users',$users);
		
	}
	
	
	function CheckPosts($session_id){
		
		if( $_SERVER['REQUEST_METHOD'] != 'POST'){
			return;
		}
		
		if( !isset($_POST['verified']) ){
			gpsession::StripPost();
			return;
		}
		
		if( $_POST['verified'] !== $session_id ){
			gpsession::StripPost();
			return;
		}
	}
	
	function StripPost(){
		global $langmessage;
		message($langmessage['OOPS'].' (XSS)');
		foreach($_POST as $key => $value){
			unset($_POST[$key]);
		}
	}
	
	
	function close($file,$checksum_read){
		global $gpAdmin;
		
		unset($gpAdmin['checksum']);
		$checksum = gpsession::checksum($gpAdmin);
		
		//nothing changes
		if( $checksum === $checksum_read ){
			return;
		}
		
		$gpAdmin['checksum'] = $checksum; //store the new checksum
		gpFiles::SaveArray($file,'gpAdmin',$gpAdmin);
	}
	
	
	/* Save user settings */
	function SaveSetting(){
		global $gpAdmin;
		
		$cmd = common::GetCommand();
		if( empty($cmd) ){
			return;
		}
		
		switch($cmd){
			case 'panelposition':
				gpsession::PanelPosition();
			//dies
				
			case 'gppref':
				gpsession::BrowserDisplay();
			//dies
		}
	}
	
	function BrowserDisplay(){
		
		switch($_REQUEST['browser_display']){
			case 'browser_list':
			case 'browser_icons_small':
			case 'browser_icons':
				$GLOBALS['gpAdmin']['browser_display'] = $_POST['browser_display'];
			break;
		}
		die();
	}
	
	function PanelPosition(){
		
		if( is_numeric($_POST['panelposx']) && is_numeric($_POST['panelposy']) ){
			$GLOBALS['gpAdmin']['paneldock'] = false;
			$GLOBALS['gpAdmin']['panelposx'] = (int)$_POST['panelposx'];
			$GLOBALS['gpAdmin']['panelposy'] = (int)$_POST['panelposy'];
		}elseif( $_POST['paneldock'] == 'true' ){
			$GLOBALS['gpAdmin']['paneldock'] = true;
		}elseif( $_POST['paneldock'] == 'false' ){
			$GLOBALS['gpAdmin']['paneldock'] = false;
		}
		die();
	}
	
	
	
	/* generic functions */
	
	function checksum($array){
		return crc32(serialize($array) );
	}
	
	
}



