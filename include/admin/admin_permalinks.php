<?php
defined('is_running') or die('Not an entry point...');

includeFile('tool/FileSystem.php');
includeFile('tool/RemoteGet.php');

class admin_permalinks{
	
	var $changed_to_hide = false;
	
	function admin_permalinks(){
		global $langmessage,$dataDir;
		
		$this->htaccess_file = $dataDir.'/.htaccess';
		gp_filesystem_base::init($this->htaccess_file);
		
		echo '<h2>'.$langmessage['permalink_settings'].'</h2>';
		
		
		$cmd = common::GetCommand();
		switch($cmd){
			case 'continue':
				$this->SaveHtaccess();
			break;
			
			default:
				$this->ShowForm();
			break;
		}
	}
	
	function ShowForm(){
		global $langmessage,$gp_filesystem;
		
		$this->CheckHtaccess();
		
		echo '<form method="post" action="'.common::GetUrl('Admin_Permalinks').'">';
		
		echo '<table cellpadding="7">';
		
		//default
		echo '<tr>';
			echo '<td>';
			echo '<label>';
			$checked = '';
			if( !$_SERVER['gp_rewrite'] ){
				$checked = 'checked="checked"';
			}
			echo '<input type="radio" name="rewrite_setting" value="no_rewrite" '.$checked.'/> ';
			echo $langmessage['use_index.php'];
			echo '</label>';
			echo '</td></tr>';
		
		//hide index.php
		echo '<tr>';
			echo '<td>';
			echo '<label>';
			$checked = '';
			if( $_SERVER['gp_rewrite'] ){
				$checked = 'checked="checked"';
			}
			echo '<input type="radio" name="rewrite_setting" value="hide_index" '.$checked.'/> ';
			echo $langmessage['hide_index'];
			echo '</label>';
			echo '</td></tr>';
			
		echo '</table>';
		
		echo '<p>';
		echo '<input type="hidden" name="cmd" value="continue" />';
		echo '<input type="submit" name="" value="'.$langmessage['continue'].'" />';
		echo '</p>';
		
		echo '</form>';
		
		
		echo '<p>';
		$langmessage['limited_mod_rewrite'] = 'These settings will only work if your server has mod_rewrite enabled.';
		echo $langmessage['limited_mod_rewrite'];
		echo '</p>';
		
		
	}
	
	//check to see MOD_ENV is working
	function CheckHtaccess(){
		
		if( !file_exists($this->htaccess_file) ){
			return;
		}
		
		//it's working
		if( $_SERVER['gp_rewrite'] ){
			return;
		}

		
		$contents = file_get_contents($this->htaccess_file);
		
		//strip gpEasy code
		$pos = strpos($contents,'# BEGIN gpEasy');
		if( $pos === false ){
			return;
		}
		
		$pos2 = strpos($contents,'# END gpEasy');
		if( $pos2 > $pos ){
			$contents = substr($contents,$pos, $pos2-$pos);
		}else{
			$contents = substr($contents,$pos);
		}
		
		$lines = explode("\n",$contents);
		$HasRule = false;
		foreach($lines as $line){
			$line = trim($line);
			if( strpos($line,'RewriteRule') !== false ){
				$HasRule = true;
			}
		}
		
		if( $HasRule ){
			message('If you have enabled index.php hiding and you\'re still seeing index.php, you may need to set <i>gp_indexphp</i> to <i>false</i> in your index.php file.');
		}else{
			//do nothing
		}
		
	}
		
	
	/**
	 * Determine how to save the htaccess file to the server (ftp,direct,manual) and give user the appropriate options
	 * 
	 * @return boolean true if the .htaccess file is saved
	 */
	function SaveHtaccess(){
		global $gp_filesystem,$config,$langmessage;
		
		if( isset($_POST['rewrite_setting']) && $_POST['rewrite_setting'] == 'hide_index' ){
			$this->changed_to_hide = true;
		}
		
		$rules = admin_permalinks::Rewrite_Rules($this->changed_to_hide);
		
		
		//only proceed with save if we can test the results
		if( gpRemoteGet::Test() ){
			if( $gp_filesystem->ConnectOrPrompt() ){
				if( $this->SaveRules($this->htaccess_file,$rules) ){
					message($langmessage['SAVED']);
					
					if( $this->changed_to_hide ){
						$_SERVER['gp_rewrite'] = true;
					}else{
						$_SERVER['gp_rewrite'] = false;
					}
					
					echo '<form method="GET" action="'.common::GetUrl('Admin_Permalinks').'">';
					echo '<input type="submit" value="'.$langmessage['continue'].'" />';
					echo '</form>';
					
					return true;
				}else{
					message($langmessage['OOPS']);
					$gp_filesystem->CompleteForm($_POST);
				}
			}
		}
		
		
		echo '<h3>'.$langmessage['manual_method'].'</h3>';
		echo '<p>';
		echo $langmessage['manual_htaccess'];
		echo '</p>';
		
		echo '<textarea cols="60" rows="7" readonly="readonly" onClick="this.focus();this.select();">';
		echo htmlspecialchars($rules);
		echo '</textarea>';
		
		return false;
		
	}
	
	
	/**
	 * Save the htaccess rule to the server using $filesystem and test to make sure we aren't getting 500 errors
	 * 
	 * @access public
	 * @since 1.7
	 * 
	 * @param string $path The path to the local .htaccess file
	 * @param string $rules The rules to be added to the .htaccess file
	 * @return boolean
	 */
	function SaveRules($path,$rules){
		global $gp_filesystem, $langmessage;
		
		//force a 500 error for testing
		//$rules .= "\n</IfModule>";
		
		
		//get current .htaccess
		$contents = '';
		$original_contents = false;
		if( file_exists($path) ){
			$original_contents = $contents = file_get_contents($path);
		}
		
		// new gpeasy rules
		admin_permalinks::StripRules($contents);
		$contents .= $rules;
		
		$filesystem_base = $gp_filesystem->get_base_dir();
		if( $filesystem_base === false ){
			return false;
		}
		
		$filesystem_path = $filesystem_base.'/.htaccess';
		
		if( !$gp_filesystem->put_contents($filesystem_path,$contents) ){
			return false;
		}
		
		//if TestResponse Fails, undo the changes
		if( !admin_permalinks::TestResponse($this->changed_to_hide) ){
			if( $original_contents === false ){
				$gp_filesystem->unlink($filesystem_path);
			}else{
				$gp_filesystem->put_contents($filesystem_path,$original_contents);
			}
			return false;
		}
		
		return true;
	}
	
	
	/**
	 * Try to fetch a response using RemoteGet to see if we're getting a 500 error 
	 * 
	 * @access public
	 * @static
	 * @since 1.7
	 * 
	 * @return boolean
	 */
	function TestResponse($new_gp_rewrite){
		global $config;
		
		
		//get url, force gp_rewrite to $new_gp_rewrite
		$rewrite_before = $_SERVER['gp_rewrite'];
		$_SERVER['gp_rewrite'] = $new_gp_rewrite;
		$abs_url = common::AbsoluteUrl('Special_Site_Map');
		$_SERVER['gp_rewrite'] = $rewrite_before;
		
		$result = gpRemoteGet::Get_Successful($abs_url);
		if( !$result ){
			return false;
		}
		
		return true;
	}
	
	
	/**
	 * Strip rules enclosed by gpEasy comments
	 * 
	 * @access public
	 * @static
	 * @since 1.7
	 * 
	 * @param string $contents .htaccess file contents
	 */
	function StripRules(&$contents){
		//strip gpEasy code
		$pos = strpos($contents,'# BEGIN gpEasy');
		if( $pos === false ){
			return;
		}
		
		$pos2 = strpos($contents,'# END gpEasy');
		if( $pos2 > $pos ){
			$contents = substr_replace($contents,'',$pos,$pos2-$pos+12);
		}else{
			$contents = substr($contents,0,$pos);
		}
		
		$contents = rtrim($contents);		
	}
	
	function Rewrite_Rules($HideRules = true){
		global $dirPrefix;
		
		$RuleArray = array();
		if( $HideRules ){
			$home_root = $dirPrefix.'/';
			
			$RuleArray[] = '<IfModule mod_env.c>';
			$RuleArray[] = 'SetEnv gp_rewrite On';
			$RuleArray[] = '</IfModule>';
				
			$RuleArray[] = 'RewriteEngine On';
			$RuleArray[] = 'RewriteBase "'.$home_root.'"';
			$RuleArray[] = 'RewriteRule ^index\.php$ - [L]'; // Prevent -f checks on index.php.
			
			$RuleArray[] = 'RewriteCond %{REQUEST_FILENAME} !-f';
			$RuleArray[] = 'RewriteCond %{REQUEST_FILENAME} !-d';
			$RuleArray[] = 'RewriteRule . "'.$home_root.'index.php" [L]';
		}
		
		$rules = "\n# BEGIN gpEasy\n";
		$rules .= '<IfModule mod_rewrite.c>';
		$rules .= "\n\t";
		$rules .= implode("\n\t",$RuleArray);
		$rules .= "\n</IfModule>";
		$rules .= "\n# END gpEasy\n";
		
		return $rules;
	}
	
	
}

