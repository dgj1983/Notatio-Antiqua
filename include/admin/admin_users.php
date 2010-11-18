<?php
defined('is_running') or die('Not an entry point...');



class admin_users{
	
	var $users;
	var $possible_permissions = array();
	
	function admin_users(){
		global $page,$langmessage;
		
		$page->head .= '<script type="text/javascript" language="javascript" src="'.common::GetDir('/include/js/admin_users.js').'"></script>';
		
		//set possible_permissions
		$this->possible_permissions['file_editing'] = $langmessage['file_editing'];
		$scripts = admin_tools::AdminScripts();
		foreach($scripts as $script => $info){
			$this->possible_permissions[$script] = $info['label'];
		}
		

		$this->GetUsers();
		$cmd = common::GetCommand();
		switch($cmd){
			case 'newuser':
				if( $this->CreateNewUser() ){
					break;
				}
			case 'newuserform';
				$this->NewUserForm();
			return;
			
			case 'rm':
				$this->RmUserStart();
			break;
			case 'rm_confirmed':
				$this->RmUserConfirmed();
			break;
			
			case 'resetpass':
				if( $this->ResetPass() ){
					break;
				}
			case 'changepass':
				$this->ChangePass();
			return;
			
			
			case 'ResetDetails':
				if( $this->ResetDetails() ){
					break;
				}
			case 'details':
				$this->ChangeDetails();
			return;
			
		}
	
		$this->ShowForm();
	}
	
	function ResetDetails(){
		global $langmessage, $dataDir,$gpAdmin;
		
		$username =& $_REQUEST['username'];
		if( !isset($this->users[$username]) ){
			message($langmessage['OOPS']);
			return false;
		}
		
		if( !empty($_POST['email']) ){
			$this->users[$username]['email'] = $_POST['email'];
		}
		
		$this->users[$username]['granted'] = $this->GetPostedPermissions($username);

		//this needs to happen before SaveUserFile();
		//update the /_session file
		includeFile('tool/sessions.php');
		$userinfo =& $this->users[$username];
		$userinfo = gpsession::SetSessionFileName($userinfo,$username); //make sure $userinfo['file_name'] is set
		
		
		if( !$this->SaveUserFile() ){
			message($langmessage['OOPS']);
			return false;
		}
		
		// update the $user_file_name file
		$is_curr_user = ($gpAdmin['username'] == $username);
		$this->UserFileDetails($userinfo['file_name'],$this->users[$username]['granted'],$is_curr_user);
		return true;
	}
	
	function UserFileDetails($user_file_name,$granted,$is_curr_user){
		global $dataDir;
		
		$user_file = $dataDir.'/data/_sessions/'.$user_file_name;
		
		if( !$is_curr_user ){
			if( !file_exists($user_file) ){
				return;
			}
			include($user_file);
		}else{
			global $gpAdmin;
		}
		
		$gpAdmin['granted'] = $granted;
		gpFiles::SaveArray($user_file,'gpAdmin',$gpAdmin);
	}
	
	
	function ChangeDetails(){
		global $langmessage;
		
		$username =& $_REQUEST['username'];
		if( !isset($this->users[$username]) ){
			message($langmessage['OOPS']);
			return false;
		}
		
		$userinfo = $this->users[$username];
		
		
		echo '<form action="'.common::GetUrl('Admin_Users').'" method="post" id="permission_form">';
		echo '<input type="hidden" name="cmd" value="ResetDetails" />';
		echo '<input type="hidden" name="username" value="'.htmlspecialchars($username).'" />';

		echo '<table class="bordered">';
		echo '<tr>';
			echo '<th colspan="2">';
			echo $langmessage['details'];
			echo ' - ';
			echo $username;
			echo '</th>';
			echo '</tr>';
			
		$this->DetailsForm($userinfo,$username);
			
		echo '<tr>';
			echo '<td>';
			echo '</td>';
			echo '<td>';
			echo ' <input type="submit" class="submit" name="aaa" value="'.$langmessage['continue'].'" />';
			echo ' <input type="reset" />';
			echo ' <input type="submit" class="submit" name="cmd" value="'.$langmessage['cancel'].'" />';
			echo '</td>';
			echo '</tr>';
			
		echo '</table>';
		echo '</form>';
		
	}
	
	function RmUserConfirmed(){
		global $langmessage;
		$username = $this->CheckUser();
		
		if( $username == false ){
			return;
		}
		
		unset($this->users[$username]);
		return $this->SaveUserFile();
	}
	
	function RmUserStart(){
		global $langmessage;
		$username = $this->CheckUser();
		
		if( $username == false ){
			return;
		}
		
		$mess = '';
		$mess .= '<form action="'.common::GetUrl('Admin_Users').'" method="post">';
		$mess .= sprintf($langmessage['generic_delete_confirm'],'<i>'.htmlspecialchars($username).'</i>');
		$mess .= '<input type="hidden" name="cmd" value="rm_confirmed" />';
		$mess .= '<input type="hidden" name="username" value="'.htmlspecialchars($username).'" />';
		$mess .= ' <input type="submit" class="submit" name="aaa" value="'.$langmessage['delete'].'" />';
		$mess .= ' <input type="submit" class="submit" name="cmd" value="'.$langmessage['cancel'].'" />';
		$mess .= '</form>';
		message($mess);
	}
	
	function CheckUser(){
		global $langmessage,$gpAdmin;
		$username = $_REQUEST['username'];
		
		if( !isset($this->users[$username]) ){
			message($langmessage['OOPS']);
			return false;
		}
		
		//don't allow deleting self
		if( $username == $gpAdmin['username'] ){
			message($langmessage['OOPS']);
			return false;
		}
		return $username;
	}
	
	
	
	function CreateNewUser(){
		global $langmessage;
		$_POST += array('grant'=>'');
		
		if( ($_POST['password']=="") || ($_POST['password'] !== $_POST['password1'])  ){
			message($langmessage['invalid_password']);
			return false;
		}
		
		
		$newname = $_POST['username'];
		$test = str_replace( array('.','_'), array(''), $newname );
		if( empty($test) || !ctype_alnum($test) ){
			message($langmessage['invalid_username']);
			return false;
		}
		
		if( isset($this->users[$newname]) ){
			message($langmessage['OOPS']);
			return false;
		}
		
		
		if( !empty($_POST['email']) ){
			$this->users[$newname]['email'] = $_POST['email'];
		}
		
		$this->users[$newname]['password'] = common::hash(trim($_POST['password']));
		$this->users[$newname]['granted'] = $this->GetPostedPermissions($newname);
		return $this->SaveUserFile();
	}
	
	function GetPostedPermissions($username){
		global $gpAdmin;
		
		if( isset($_POST['select_all']) && ($_POST['select_all'] == 'all') ){
			return 'all';
		}
		
		$_POST += array('grant'=>array());
		$array = $_POST['grant'];
		
		//cannot remove self from Admin_Users
		if( $username == $gpAdmin['username'] ){
			$array = array_merge($array, array('Admin_Users'));
		}
		
		if( !is_array($array) ){
			return '';
		}
		
		$keys = array_keys($this->possible_permissions);
		$array = array_intersect($keys,$array);
		return implode(',',$array);
	}
		
	
	function SaveUserFile(){
		global $langmessage, $dataDir;
		
		if( !gpFiles::SaveArray($dataDir.'/data/_site/users.php','users',$this->users) ){
			message($langmessage['OOPS']);
			return false;
		}
		message($langmessage['SAVED']);
		return true;
	}
	
	
	function ShowForm(){
		global $langmessage;
		
		
		echo '<h2>'.$langmessage['user_permissions'].'</h2>';
		echo '<table class="bordered">';
		echo '<tr>';
			echo '<th>';
			echo $langmessage['username'];
			echo '</th>';
			echo '<th>';
			echo $langmessage['permissions'];
			echo '</th>';
			echo '<th>';
			echo $langmessage['options'];
			echo '</th>';
			echo '</tr>';
			
		foreach($this->users as $username => $userinfo){
			$userinfo += array('granted'=>'');
			
			echo '<tr>';
			echo '<td>';
			echo $username;
			echo '</td>';
			echo '<td>';
				if( $userinfo['granted'] == 'all' ){
					echo 'all';
				}elseif( !empty($userinfo['granted']) ){
					
					$permissions = explode(',',$userinfo['granted']);
					$comma = '';
					foreach($permissions as $permission){
						if( isset($this->possible_permissions[$permission]) ){
							echo $comma;
							echo $this->possible_permissions[$permission];
							$comma = ', ';
						}
					}
				}else{
					echo '&nbsp;';
				}
					
			echo '</td>';
			echo '<td>';
/*
				if( $userinfo['granted'] != 'all' ){
					echo common::Link('Admin_Users',$langmessage['permissions'],'cmd=details&username='.$username);
					echo ' &nbsp; ';
				}
*/
				echo common::Link('Admin_Users',$langmessage['details'],'cmd=details&username='.$username);
				echo ' &nbsp; ';
				echo common::Link('Admin_Users',$langmessage['password'],'cmd=changepass&username='.$username);
				echo ' &nbsp; ';
				echo common::Link('Admin_Users',$langmessage['delete'],'cmd=rm&username='.$username);
			echo '</td>';
			echo '</tr>';
		}
		echo '<tr><th colspan="3">';
		echo common::Link('Admin_Users',$langmessage['new_user'],'cmd=newuserform');
		echo '</th>';
		
		echo '</table>';
		
	
	}
	
	function NewUserForm(){
		global $langmessage;
		
		$_POST += array('username'=>'','email'=>'','grant'=>array());
		
		echo '<form action="'.common::GetUrl('Admin_Users').'" method="post" id="permission_form">';
		echo '<table class="bordered" style="width:95%">';
		echo '<tr>';
			echo '<th colspan="2">';
			echo $langmessage['new_user'];
			echo '</th>';
			echo '</tr>';
		echo '<tr>';
			echo '<td>';
			echo $langmessage['username'];
			echo '</td>';
			echo '<td>';
			echo '<input type="text" class="text" name="username" value="'.htmlspecialchars($_POST['username']).'" />';
			echo '</td>';
			echo '</tr>';
		echo '<tr>';
			echo '<td>';
			echo $langmessage['password'];
			echo '</td>';
			echo '<td>';
			echo '<input type="password" class="text" name="password" value="" />';
			echo '</td>';
			echo '</tr>';
		echo '<tr>';
			echo '<td>';
			echo str_replace(' ','&nbsp;',$langmessage['repeat_password']);
			echo '</td>';
			echo '<td>';
			echo '<input type="password" class="text" name="password1" value="" />';
			echo '</td>';
			echo '</tr>';
			
		$_POST['granted'] = $this->GetPostedPermissions(false);
		$this->DetailsForm($_POST);
		
/*
		echo '<tr>';
			echo '<td>';
			echo '</td>';
			echo '<td>';
			echo '</td>';
			echo '</tr>';
*/
		echo '</table>';
		echo '<p>';
			echo '<input type="hidden" name="cmd" value="newuser" />';
			echo ' <input type="submit" class="submit" name="aaa" value="'.$langmessage['continue'].'" />';
			echo ' <input type="reset" />';
			echo ' <input type="submit" class="submit" name="cmd" value="'.$langmessage['cancel'].'" />';
			echo '</p>';
		echo '</form>';		
		
	}
	
	function DetailsForm( $values=array(), $username=false ){
		global $langmessage;
		
		$values += array('granted'=>'','email'=>'');
		
		
		echo '<tr>';
			echo '<td>';
			echo str_replace(' ','&nbsp;',$langmessage['email_address']);
			echo '</td>';
			echo '<td>';
			echo '<input type="text" class="text" name="email" value="'.htmlspecialchars($values['email']).'" />';
			echo '</td>';
			echo '</tr>';
		
		echo '<tr>';
			echo '<td>';
			echo str_replace(' ','&nbsp;',$langmessage['grant_usage']);
			echo '</td>';
			echo '<td class="all_checkboxes">';
			
		
		
		$all = false;
		$current = $values['granted'];
		if( $current == 'all' ){
			$all = true;
		}else{
			$current = ','.$current.',';
		}
		
		echo '<p>';
		$checked = '';
		if( $all ){
			$checked = ' checked="checked" ';
		}
		echo '<label class="select_all"><input type="checkbox" class="select_all" name="select_all" value="all" '.$checked.'/> '.$langmessage['All'].'</label> ';
		echo '</p>';
					
		foreach($this->possible_permissions as $permission => $label){
			$checked = '';
			if( $all ){
				$checked = ' checked="checked" ';
			}elseif( strpos($current,','.$permission.',') !== false ){
				$checked = ' checked="checked" ';
			}
			
			echo '<label class="all_checkbox"><input type="checkbox" name="grant[]" value="'.$permission.'" '.$checked.'/> '.$label.'</label> ';
		}
		
		echo '</td>';
		echo '</tr>';
		

	}
	
	function ChangePass(){
		global $langmessage;
		
		$username =& $_REQUEST['username'];
		if( !isset($this->users[$username]) ){
			message($langmessage['OOPS']);
			return;
		}
		
		
		echo '<form action="'.common::GetUrl('Admin_Users').'" method="post">';
		echo '<input type="hidden" name="cmd" value="resetpass" />';
		echo '<input type="hidden" name="username" value="'.htmlspecialchars($username).'" />';

		echo '<table class="bordered">';
		echo '<tr>';
			echo '<th colspan="2">';
			echo $langmessage['change_password'];
			echo ' - ';
			echo $username;
			echo '</th>';
			echo '</tr>';
		echo '<tr>';
			echo '<td>';
			echo $langmessage['new_password'];
			echo '</td>';
			echo '<td>';
			echo '<input type="password" class="text" name="password" value="" />';
			echo '</td>';
			echo '</tr>';
		echo '<tr>';
			echo '<td>';
			echo str_replace(' ','&nbsp;',$langmessage['repeat_password']);
			echo '</td>';
			echo '<td>';
			echo '<input type="password" class="text" name="password1" value="" />';
			echo '</td>';
			echo '</tr>';
		echo '<tr>';
			echo '<td>';
			echo '</td>';
			echo '<td>';
			echo '<input type="submit" class="submit" name="aaa" value="'.$langmessage['continue'].'" />';
			echo ' <input type="submit" class="submit" name="cmd" value="'.$langmessage['cancel'].'" />';
			echo '</td>';
			echo '</tr>';
		echo '</table>';
		echo '</form>';
	}
	
	function ResetPass(){
		global $langmessage;
		
		if( !$this->CheckPasswords() ){
			return false;
		}
		
		$username = $_POST['username'];
		if( !isset($this->users[$username]) ){
			message($langmessage['OOPS']);
			return false;
		}

		$this->users[$username]['password'] = common::hash(trim($_POST['password']));
		return $this->SaveUserFile();
	}
	
	function CheckPasswords(){
		global $langmessage;
		
		//see also admin_users for password checking
		if( ($_POST['password']=="") || ($_POST['password'] !== $_POST['password1'])  ){
			message($langmessage['invalid_password']);
			return false;
		}
		return true;
	}
		
	function GetUsers(){
		global $dataDir;
		
		require($dataDir.'/data/_site/users.php');
		
		$this->users = $users;
	}
	
}



	

