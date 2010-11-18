<?php
defined('is_running') or die('Not an entry point...');


class special_missing{
	
	var $datafile;
	var $error_data = array();
	var $requested = false;
	
	
	function Init(){
		global $dataDir;
		$this->datafile = $dataDir.'/data/_site/error_data.php';
		if( file_exists($this->datafile) ){
			require($this->datafile);
			$this->error_data = $error_data;
		}
	}
	
	function SaveData(){
		return gpFiles::SaveArray($this->datafile,'error_data',$this->error_data);
	}
	
	
	
	
	function special_missing($requested=false){
		global $langmessage;
		$this->requested = $requested;
		$this->Init();
		
		$this->CheckRedirect();
		$this->CheckCase();
		$this->Get404();
		
	}
	
	
	//is it just a typo? /PAGE_TITLE vs /Page_Title
	function CheckCase(){
		global $gptitles;
		
		if( !function_exists('array_change_key_case') ){
			return;
		}
		
		
		$titles = array_keys($gptitles);
		$titles = array_combine($titles, $titles);
		
		$titles = array_change_key_case($titles, CASE_LOWER);
		$lower_title = strtolower($this->requested);
		
		if( !isset($titles[$lower_title]) ){
			return;
		}
		
		$location = common::GetUrl($titles[$lower_title]);
		
		Header('HTTP/1.1 301 Moved Permanently'); 
		header('Location: '.$location);
		die();
	}

	
	function CheckRedirect(){
		
		if( $this->requested === false ){
			return;
		}
		
		if( !isset($this->error_data['redirects'][$this->requested]) ){
			return;
		}
			
		$target = $this->error_data['redirects'][$this->requested]['target'];
		$target = $this->GetTarget($target);
		if( $target == false ){
			return;
		}
		
		$code = $this->error_data['redirects'][$this->requested]['code'];
		switch($code){
			case '301':
				Header('HTTP/1.1 301 Moved Permanently'); 
			break;
			case '302':
				Header('HTTP/1.1 302 Found');
			break;
		}
		
		
		header('Location: '.$target);
		die();
		
	}
	
	
	/**
	 * Translate the $target url to a url that can be used with Header() or in a link
	 * 
	 * @param string $target The user supplied value for redirection
	 * @param boolean $get_final If true, GetTarget() will check for additional redirection and $target existence before returning the url. Maximum of 10 redirects.
	 * @return string|false
	 */
	function GetTarget($target,$get_final = true){
		global $gptitles;
		static $redirects = 0;
		
		if( !$this->isGPLink($target) ){
			return $target;
		}
		
		if( !$get_final ){
			return common::GetUrl($target);
		}
		
		
		//check for more redirects
		if( isset($this->error_data['redirects'][$target]) ){
			$redirects++;
			if( $redirects > 10 ){
				return false;
			}
			
			$target = $this->error_data['redirects'][$target]['target'];
			return $this->GetTarget($target);
		}
		
		
		//check for target existence
		if( isset($gptitles[$target]) ){
			return common::GetUrl($target);
		}
		
		includeFile('admin/admin_tools.php');
		$scripts = admin_tools::AdminScripts();
		if( isset($scripts[$target]) ){
			return common::GetUrl($target);
		}
		
		return false;
	}
	
	function isGPLink($target){
		//has a url scheme (aka protocol)
		$reg = '#^[a-zA-Z][a-zA-Z0-9\+\.\-]+:#';
		if( preg_match($reg,$target,$matches) ){
			return false;
		}
		
		//strings beginning with / could be gplinks, they could also links to non-gpEasy managed pages
		// we could do additional testing, but we could never be certain what the user intent is
		if( strpos($target,'/') === 0 ){
			return false;
		}
		
		return true;
	}
	
	
	
	function Get404(){
		global $langmessage,$page;
		
		
		header('HTTP/1.0 404 Not Found');
		$page->head .= '<meta name="robots" content="noindex,nofollow" />';

		//message for admins
		if( common::LoggedIn() ){
			if( $this->requested && !common::SpecialOrAdmin($this->requested) ){
				$link = common::GetUrl('Admin_Menu','cmd=newtitle&title='.$this->requested).'" name="cnreq';
				$message = sprintf($langmessage['DOESNT_EXIST'],str_replace('_',' ',$this->requested),$link);
				message($message);
			}else{
				message($langmessage['About_404_Page']);
			}
		}
		
		//Contents of 404 page
		$wrap =  common::LoggedIn() && admin_tools::HasPermission('Admin_Missing');
		if( $wrap ){
			echo '<div class="editable_area" >'; // class="edit_area" added by javascript
			echo common::Link('Admin_Missing',$langmessage['edit'],'cmd=edit404',' class="ExtraEditLink" style="display:none" title="'.$langmessage['404_Page'].'" ');
		}

		echo special_missing::Get404Output();
		
		if( $wrap ){
			echo '</div>';
		}
		
	}
	
	
	function Get404Output(){
		
		if( isset($this->error_data['404_TEXT']) ){
			$text = $this->error_data['404_TEXT'];
		}else{
			$text = special_missing::DefaultContent();
		}
		
		return str_replace('{{Similar_Titles}}',$this->SimilarTitles(),$text);
	}
	
	function SimilarTitles(){
		global $gptitles;
		
		$similar = array();
		$char_first = $char_second = false;
		
		
		//the levenshtein limit is 255, but we shouldn't really need that many
		$comparison = $this->requested;
		if( strlen($comparison) > 150 ){
			$comparison = substr($comparison,0,150);
		}
		foreach($gptitles as $title => $titleinfo){
			$similar[$title] = levenshtein($comparison,$title);
		}
		
		asort($similar);
		$result = '';
		$i = 0;
		$comma = '';
		foreach($similar as $title => $levenshtein){
			$i++;
			$result .= $comma;
			$result .= common::Link($title,common::GetLabel($title));
			$comma = ', ';
			if( $i >= 7 ){
				break;
			}
		}
		return $result;
	}
	
	function DefaultContent(){
		global $langmessage;
		$text = '<h2>'.$langmessage['Not Found'].'</h2>';
		$text .= '<p>';
		$text .= $langmessage['OOPS_TITLE'];
		$text .= '</p>';
		$text .= '<p>';
		$text .= '<b>'.$langmessage['One of these titles?'].'</b>';
		$text .= '<div class="404_suggestions">{{Similar_Titles}}</div>';
		$text .= '</p>';
		return $text;
	}
	
}
