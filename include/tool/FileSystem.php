<?php 
defined('is_running') or die('Not an entry point...');

global $gp_filesystem;
$gp_filesystem = false;


define('gp_filesystem_direct',1);
define('gp_filesystem_ftp',2);


/*
filesystem classes
*/

class gp_filesystem_base{
	var $conn_id = false;
	var $connect_vars = array();
	var $temp_file = false;
	
	
	function init($context = false){
		global $gp_filesystem;
		
		if( $gp_filesystem !== false ){
			return true;
		}
		
		$method = gp_filesystem_base::get_filesystem_method($context);
		return gp_filesystem_base::set_method($method);
	}
	
	function set_method($method){
		global $gp_filesystem;
		switch($method){
			case 'gp_filesystem_direct':
				$gp_filesystem = new gp_filesystem_direct();
			return true;
			case 'gp_filesystem_ftp':
				$gp_filesystem = new gp_filesystem_ftp();
			return true;
		}
		return false;
	}

	
	
	//needed for writing to the /include, .htaccess and possibly /themes files
	/* static */
	function get_filesystem_method($context = false){
		global $dataDir;
		
		if( $context === false ){
			$context = $dataDir . '/include';
		}
		
		while( !file_exists($context) ){
			$context = dirname($context);
		}
		
	
		//direct
		if( is_writable($context) ){
			
			if( !is_dir($context) ){
				return 'gp_filesystem_direct';
			}
			
			if( function_exists('posix_getuid') && function_exists('fileowner') ){
			
				//check more for directories 
				$direct = false;
				$temp_file_name = $context . '/temp-write-test-' . time();
				
				$temp_handle = @fopen($temp_file_name, 'w');
				
				if( $temp_handle ){
					if( posix_getuid() == @fileowner($temp_file_name) ){
						$direct = true;
					}
					@fclose($temp_handle);
					@unlink($temp_file_name);
				}
				
			}
			
			if( $direct ){
				return 'gp_filesystem_direct';
			}
		}
		
		//ftp
		if( function_exists('ftp_connect') ){
			return 'gp_filesystem_ftp';
		}

		return false;
	}
	
	
	/* check all files and folders in $context to get the correct filesystem method 
	 */
	function get_filesystem_method_all($context = array()){
		
		$result = 2;
		
		if( is_string($context) ){
			$context = array($context);
		}
		
		foreach($context as $file){
			$temp_result = gp_filesystem_base::get_filesystem_method_dir($file);
			if( $temp_result === false ){
				return false;
			}
			$result = max($temp_result,$result);
		}
		
		switch($result){
			case 1:
			return 'gp_filesystem_direct';
			case 2:
			return 'gp_filesystem_ftp';
			default:
			return false;
		}
	}
	
	function get_filesystem_method_dir($dir){
		$dh = @opendir($dir);
		if( !$dh ){
			return $files;
		}
		
		$result = gp_filesystem_base::get_filesystem_method_file($dir);
		if( $result === false ){
			return false;
		}
		
		while( ($file = readdir($dh)) !== false){
			if( strpos($file,'.') === 0){
				continue;
			}
			$fullPath = $dir.'/'.$file;
			if( is_dir($fullPath) ){
				$temp_result = gp_filesystem_base::get_filesystem_method_dir($fullPath);
			}else{
				$temp_result = gp_filesystem_base::get_filesystem_method_file($fullPath);
			}
				
			if( $temp_result === false ){
				return false;
			}
			$result = max($temp_result,$result);
			
		}
		
		return $result;
	}
	
	function get_filesystem_method_file($file){
		if( is_writable($file) ){
			return gp_filesystem_direct;
		}elseif( function_exists('ftp_connect') ){
			return gp_filesystem_ftp;
		}else{
			return false;
		}			
	}
	
	

	/* 
	returns true if connectForm() is needed
	*/
	function requiresForm($context){
		return false;
	}
	
	function connectForm(){
		return true;
	}
	function CompleteForm(){
		return true;
	}
	
	function ConnectOrPrompt(){
		return true;
	}
	function connect(){
		return true;
	}
	function connect_handler(){
		return true;
	}
	function get_base_dir(){
		global $dataDir;
		return $dataDir;
	}
	
	function mkdir($dir){
		return mkdir($dir,0755);
	}
	
	function unlink($path){
		return unlink($path);
	}
	
	function put_contents($file, $contents, $type = '' ){
		return gpFiles::Save($file,$contents);
	}
	
	function get_connect_vars($args){
		$result = array();
		if( is_array($args) ){
			foreach($args as $key => $value ){
				if( array_key_exists($key,$this->connect_vars) ){
					$result[$key] = $value;
				}
			}
		}
		return $result;
	}
	
	function destruct(){
		if( $this->temp_file == false ){
			return;
		}
		if( file_exists($this->temp_file) ){
			unlink($this->temp_file);
		}
	}
	
	
	function RequestToForm(){
		
		$this->ArrayToForm($_REQUEST);
		
	}
	
	function ArrayToForm($array,$name=false){
		
		foreach($array as $key => $value){
			
			if( $name ){
				$full_name = $name.'['.$key.']';
			}else{
				$full_name = $key;
			}
			
			if( is_array($value) ){
				$this->ArrayToForm($value,$full_name);
				continue;
			}
			echo '<input type="hidden" name="'.htmlspecialchars($full_name).'" value="'.htmlspecialchars($value).'" />';
		}
		
	}
	
	
	
	
	/**
	 * Determines if the string provided contains binary characters.
	 *
	 * @since 2.7
	 * @access private
	 *
	 * @param string $text String to test against
	 * @return bool true if string is binary, false otherwise
	 */
	function is_binary( $text ) {
		return (bool) preg_match('|[^\x20-\x7E]|', $text); //chr(32)..chr(127)
	}
	

}


class gp_filesystem_ftp extends gp_filesystem_base{
	
	var $connect_vars = array('ftp_server'=>'','ftp_user'=>'','ftp_pass'=>'','port'=>'21');
	
	function gp_filesystem_ftp(){
		includeFile('tool/ftp.php');
	}
	
	function get_base_dir(){
		global $dataDir;
		$root = gpftp::GetFTPRoot($this->conn_id,$dataDir);
		return rtrim($root,'/');
	}
	
	
	
	function connect($args){
		global $langmessage;
		
		if( empty($args['ftp_server']) ){
			return $langmessage['couldnt_connect'];
		}
		if( empty($args['port']) ){
			$args['port'] = 21;
		}
		
		$this->conn_id = @ftp_connect($args['ftp_server'],$args['port'],6);
		
		if( !$this->conn_id ){
			return $langmessage['couldnt_connect'];
		}
		
		if ( ! @ftp_login($this->conn_id,$args['ftp_user'], $args['ftp_pass']) ) {
			return $langmessage['couldnt_connect'];
		}
		
		@ftp_pasv($this->conn_id, true );
		
		return true;
	}
	
	function connect_handler(){
		global $config;
		
		$posted = false;
		$args = false;
		if( isset($config['ftp_pass']) ){
			$args = $config;
			
		}elseif( isset($_POST['ftp_pass']) ){
			$args = $_POST;
			$posted = true;
		}else{
			$args = false;
		}
		
		$args = $this->get_connect_vars($args);
		
		$connected = $this->connect($args);
		
		//save ftp info
		if( ($connected === true) && $posted ){
			$config['ftp_user'] = $args['ftp_user'];
			$config['ftp_server'] = $args['ftp_server'];
			$config['ftp_pass'] = $args['ftp_pass'];
			admin_tools::SaveConfig();
		}

		return $connected;
		
	}
	
	function ConnectOrPrompt($action=''){
		
		$connected = $this->connect_handler();
		
		if( $connected === true ){
			return true;
		}elseif( isset($_POST['connect_values_submitted']) ){
			message($connected);
		}
		$this->CompleteForm();
		
		return false;
	}
	
	
	
	function CompleteForm($args = false){
		global $langmessage;
		
		echo '<p>';
		echo $langmessage['supply_ftp_values_to_continue'];
		echo '</p>';
		
		if( empty($action) ){
			echo '<form method="post" action="">';
		}else{
			echo '<form method="post" action="'.common::GetUrl($action).'">';
		}
		echo '<table>';
		$this->RequestToForm();
		$this->connectForm($args);
		echo '</table>';
		echo '<input type="submit" name="" value="'.$langmessage['continue'].'..." class="submit" />';
		
		
		echo '</form>';
		
	}
	
	function connectForm($args = false){
		
		if( !is_array($args) ){
			$args = $_POST;
		}
		
		$args += $this->connect_vars;
		if( empty($args['ftp_server']) ){
			$args['ftp_server'] = gpftp::GetFTPServer();
		}
		
		echo '<input type="hidden" name="connect_values_submitted" value="true" />';
		echo '<tr><td>';
			echo 'FTP Hostname';
			echo '</td><td>';
			echo '<input type="text" class="text" name="ftp_server" value="'.htmlspecialchars($args['ftp_server']).'" />';
			echo '</td></tr>';
				
		echo '<tr><td>';
			echo 'FTP Username';
			echo '</td><td>';
			echo '<input type="text" class="text" name="ftp_user" value="'.htmlspecialchars($args['ftp_user']).'" />';
			echo '</td></tr>';		
			
		echo '<tr><td>';
			echo 'FTP Password';
			echo '</td><td>';
			echo '<input type="password" class="text" name="ftp_pass" value="'.htmlspecialchars($args['ftp_pass']).'" />';
			echo '</td></tr>';		
			
		echo '<tr><td>';
			echo 'FTP Port';
			echo '</td><td>';
			echo '<input type="text" class="text" name="port" value="'.htmlspecialchars($args['port']).'" />';
			echo '</td></tr>';		
	}
	
	function mkdir($path){
		
		if( !ftp_mkdir($this->conn_id, $path) ){
			return false;
		}
		return true;
	}
	
	function unlink($path){
		return ftp_delete($this->conn_id, $path);
	}
	
	function put_contents($file, $contents, $type = '' ){
		if( empty($type) ){
			$type = $this->is_binary($contents) ? FTP_BINARY : FTP_ASCII;
		}

		$temp = $this->put_contents_file();
		$handle = fopen($temp,'w+');
		if( !$handle ){
			trigger_error('Could not open temporary file');
			return false;
		}

		fwrite($handle, $contents);
		fseek($handle, 0); //Skip back to the start of the file being written to
		
		$ret = @ftp_fput($this->conn_id, $file, $handle, $type);

		fclose($handle);
		return $ret;
	}
	
	function put_contents_file(){
		global $dataDir;
		
		if( $this->temp_file === false ){
			do{
				$this->temp_file = $dataDir.'/data/_updates/temp_'.time();
			}while( file_exists($this->temp_file) );
		}
		return $this->temp_file;
	}
	
}

class gp_filesystem_direct extends gp_filesystem_base{
	
	
}
