<?php
defined("is_running") or die("Not an entry point...");

require_once($GLOBALS['rootDir'].'/include/admin/admin_users.php');


class admin_preferences extends admin_users{
	var $username;
	
	
	function admin_preferences(){
		global $gpAdmin,$langmessage;
		
		$this->GetUsers();
		$this->username = $gpAdmin['username'];
		if( !isset($this->users[$this->username]) ){
			message($langmessage['OOPS']);
			return;
		}
		$this->user_info =  $this->users[$this->username];
		$cmd = common::GetCommand();
		
		switch($cmd){
			case 'changeprefs':
				$this->DoChange();
			break;
		}
	
		$this->Form();
		
	}
	
	function DoChange(){
		
		$this->ChangeEmail();
		$this->ChangePass();
		
		$this->SaveUserFile();
	}
	
	function ChangeEmail(){
		global $langmessage;
		
		if( empty($_POST['email']) ){
			$this->users[$this->username]['email'] = '';
			return;
		}
		
		if( $this->ValidEmail($_POST['email']) ){
			$this->users[$this->username]['email'] = $_POST['email'];
		}else{
			message($langmessage['invalid_email']);
		}
		
	}
	
	function ValidEmail($email){
		return (bool)preg_match('/^[^@]+@[^@]+\.[^@]+$/', $email);
	}
	
	function ChangePass(){
		global $langmessage;
		
		
		$fields = 0;
		if( !empty($_POST['oldpassword']) ){
			$fields++;
		}
		if( !empty($_POST['password']) ){
			$fields++;
		}
		if( !empty($_POST['password1']) ){
			$fields++;
		}
		if( $fields < 2 ){
			return; //assume user didn't try to reset password
		}
		
		
		//see also admin_users for password checking
		if( !$this->CheckPasswords() ){
			return false;
		}
		
		$oldpass = common::hash(trim($_POST['oldpassword']));
		if( $this->userinfo['password'] != $oldpass ){
			message($langmessage['couldnt_reset_pass']);
			return false;
		}
		
		$this->users[$this->username]['password'] = common::hash(trim($_POST['password']));
	}
	
	
	function Form(){
		global $langmessage, $gpAdmin;
		
		if( $_SERVER['REQUEST_METHOD'] == 'POST'){
			$array = $_POST;
		}else{
			$array = $this->user_info;
		}
		$array += array('email'=>'');
		
		
		echo '<form action="'.common::GetUrl('Admin_Preferences').'" method="post">';
		echo '<div class="collapsible">';
		
		echo '<h4 class="head">'.$langmessage['general_settings'].'</h4>';
		echo '<div>';
		echo '<table class="bordered">';
		echo '<tr>';
			echo '<td>';
			echo $langmessage['email_address'];
			echo '</td>';
			echo '<td>';
			echo '<input type="text" class="text" name="email" value="'.htmlspecialchars($array['email']).'" />';
			echo '</td>';
			echo '</tr>';
		echo '</table>';
		echo '</div>';
		
		
		echo '<h4 class="head hidden">'.$langmessage['change_password'].'</h4>';
		
		echo '<div style="display:none">';
		echo '<table class="bordered">';
		echo '<tr>';
			echo '<td>';
			echo $langmessage['old_password'];
			echo '</td>';
			echo '<td>';
			echo '<input type="password" class="text" name="oldpassword" value="" />';
			echo '</td>';
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
			echo $langmessage['repeat_password'];
			echo '</td>';
			echo '<td>';
			echo '<input type="password" class="text" name="password1" value="" />';
			echo '</td>';
			echo '</tr>';
		echo '</table>';
		echo '</div>';
		
		echo '<p>';
		echo '<input type="hidden" name="cmd" value="changeprefs" />';
		echo ' <input type="submit" class="submit" name="aaa" value="'.$langmessage['continue'].'" />';
		echo '</p>';
		echo '</div>';
		echo '</form>';
	}
	
}

