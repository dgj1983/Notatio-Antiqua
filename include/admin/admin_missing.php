<?php
defined('is_running') or die('Not an entry point...');


/* 
 * wordpress info http://codex.wordpress.org/Creating_an_Error_404_Page
 * ability to see from url, something like /index.php/Special_Missing so that users can set "ErrorDocument 404 /index.php/Special_Missing?code=404" in .htaccess
 * 
 */

includeFile('special/special_missing.php');

class admin_missing extends special_missing{
	
	function admin_missing(){
		global $langmessage;
		
		$this->Init();

		
		$cmd = common::GetCommand();
		$show = true;
		$show_which = '404';
		switch($cmd){
			
			case 'save404':
				$show = $this->Save404();
			break;
			case 'edit404':
				$this->Edit404();
			return;
			
			case 'saveredir';
				$this->SaveRedir();
				$show_which = 'redir';
			break;
			case 'updateredir':
				$this->UpdateRedir();
				$show_which = 'redir';
			break;
			
			case 'rmredir':
				if( $this->RmRedir() ){
					return;
				}
				$show_which = 'redir';
			break;
			case 'rmredir_confirmed':
				$this->RmRedirConfirmed();
				$show_which = 'redir';
			break;
			
		}
		
		if( $show ){
			$this->Show($show_which);
		}
	}
	
	/* static */
	
	function AddRedirect($source,$target){
		global $dataDir;
		$error_data = array();
		$datafile = $dataDir.'/data/_site/error_data.php';
		if( file_exists($datafile) ){
			require($datafile);
		}
		$changed = false;
		
		//remove redirects from the $target
		if( isset($error_data['redirects'][$target]) ){
			unset($error_data['redirects'][$target]);
			$changed = true;
		}
		
		//redirect already exists for $source
		if( !isset($error_data['redirects'][$source]) ){
			$error_data['redirects'][$source]['target'] = $target;
			$error_data['redirects'][$source]['code'] = '301';
			$changed = true;
		}
		
		
		if( $changed ){
			gpFiles::SaveArray($datafile,'error_data',$error_data);
		}
	}
	
	
	function SaveData_Message(){
		global $langmessage;

		if( $this->SaveData() ){
			message($langmessage['SAVED']);
			return true;
		}else{
			message($langmessage['OOPS']);
			return false;
		}
	}
	function GetCodeLanguage($code){
		global $langmessage;
		switch($code){
			case '301':
			return $langmessage['301 Moved Permanently'];
			case '302':
			return $langmessage['302 Moved Temporarily'];
		}
		return '';
	}

	
	function Show($show_which){
		global $langmessage;
		
		echo '<h2>'.$langmessage['Link Errors'].'</h2>';

		echo '<p>';
		echo $langmessage['404_Usage'];
		echo '</p>';
		
		echo '<div class="layout_links">';
		
		if( $show_which == '404' ){
			echo '<a href="#Page_404" name="tabs" class="selected">'. $langmessage['404_Page'] .'</a>';
			echo ' <a href="#Redirection" name="tabs">'. $langmessage['Redirection'] .'</a>';
		}else{
			echo '<a href="#Page_404" name="tabs">'. $langmessage['404_Page'] .'</a>';
			echo ' <a href="#Redirection" name="tabs" class="selected">'. $langmessage['Redirection'] .'</a>';
		}
		echo '</div>';
		
		
		if( $show_which == '404' ){
			echo '<div id="Page_404">';
		}else{
			echo '<div id="Page_404" style="display:none">';
		}
		echo '<p>';
		echo $langmessage['About_404_Page'];
		echo '</p>';
		echo '<p>';
		echo common::Link('Special_Missing',$langmessage['preview']);
		echo ' - ';
		echo common::Link('Admin_Missing',$langmessage['edit'],'cmd=edit404');
		echo '</p>';
		echo '</div>';


		if( $show_which=='redir' ){
			echo '<div id="Redirection">';
		}else{
			echo '<div id="Redirection" style="display:none">';
		}
		$this->ShowRedirection();
		echo '</div>';
		
		echo '<div style="display:none">';
		$this->RedirForm();
		echo '</div>';
		
	}
	

	function Save404(){
		
		$text =& $_POST['gpcontent'];
		gpFiles::cleanText($text);
		$this->error_data['404_TEXT'] = $text;
		if( $this->SaveData_Message() ){
			return true;
		}
		
		$this->Edit404($text);
		return false;
	}
	
	function Edit404($text=false){
		global $langmessage;
		if( $text === false ){
			if( isset($this->error_data['404_TEXT']) ){
				$text = $this->error_data['404_TEXT'];
			}else{
				$text = special_missing::DefaultContent();
			}
		}
		
		echo '<h2>'.$langmessage['Link Errors'].' » '.$langmessage['404_Page'].'</h2>';

		
		echo '<form  action="'.common::GetUrl('Admin_Missing').'" method="post">';
		echo '<input type="hidden" name="cmd" value="save404" />';
			
		common::UseCK($text);
		
		echo '<input type="submit" class="submit" name="" value="'.$langmessage['save'].'" />';
		echo ' &nbsp; <input type="submit" class="submit" name="cmd" value="'.$langmessage['cancel'].'" />';
		echo '</form>';
		
		echo '<table class="bordered">';
		echo '<tr><th>';
		echo $langmessage['Useful Variables'];
		echo '</th>';
		echo '<th>';
		echo '&nbsp;';
		echo '</th>';
		echo '</tr>';
		
		
		echo '<tr><td>';
		echo '{{Similar_Titles}}';
		echo '</td>';
		echo '<td>';
		echo $langmessage['Similar_Titles'];
		echo '</td>';
		echo '</tr></table>';
		
	}
	
	/* 
	 * 
	 * Redirection Functions
	 * 
	 */
	
	function ShowRedirection(){
		global $langmessage,$gptitles,$page;
		
		$page->head .= '<script type="text/javascript" language="javascript" src="'.common::GetDir('/include/thirdparty/tablesorter/tablesorter.js').'"></script>';
		$page->jQueryCode .= '$("table.tablesorter").tablesorter({cssHeader:"gp_header",cssAsc:"gp_header_asc",cssDesc:"gp_header_desc"});';


		echo '<p>';
		echo $langmessage['About_Redirection'];
		echo '</p>';
		$this->InlineBoxLink($langmessage['New Redirection']);

		if( empty($this->error_data['redirects']) ){
			return;
		}
		
		echo '<table class="bordered tablesorter" width="100%">';
		echo '<thead>';
		echo '<tr><th>';
		echo $langmessage['Source URL'];
		echo '</th><th>';
		echo $langmessage['Target URL'];
		echo '</th><th>';
		echo $langmessage['Method'];
		echo '</th><th>';
		echo $langmessage['options'];
		echo '</th></tr>';
		echo '</thead>';
		
		echo '<tbody>';
		$has_invalid_target = false;
		foreach($this->error_data['redirects'] as $source => $data){
			echo '<tr>';
			echo '<td>';
			if( !empty($data['raw_source']) ){
				echo htmlspecialchars($data['raw_source']);
			}else{
				echo htmlspecialchars($source);
			}
			echo '</td>';
			echo '<td>';
			
			$target_show = $data['target'];
			if( strlen($target_show) > 40 ){
				$target_show = substr($target_show,0,15).' ... '.substr($target_show,-15);
			}
			$full_target = $this->GetTarget($data['target'],false);
			
			echo '<a href="'.htmlspecialchars($full_target).'">'.str_replace(' ','&nbsp;',htmlspecialchars($target_show)).'</a>';
			
			
			if( !empty($data['target']) && $this->isGPLink($data['target']) && !isset($gptitles[$data['target']]) ){
				$has_invalid_target = true;
				echo ' <img src="'.common::GetDir('/include/imgs/error.png').'" alt="" height="16" width="16" style="vertical-align:middle" title="'.$langmessage['Target URL Invalid'].'"/> ';
			}else{
				//echo ' <img src="'.common::GetDir('/include/imgs/blank.gif').'" alt="" height="16" width="16" style="vertical-align:middle" /> ';
			}
			
			echo '</td>';
			echo '<td>';
			echo $this->GetCodeLanguage($data['code']);
			echo '</td>';
			echo '<td>';
			
			$this->InlineBoxLink($langmessage['edit'],'updateredir',$source,$data['target'],$data['code']);
			
			echo ' &nbsp; ';
			echo common::Link($source,$langmessage['Test']);
			
			echo ' &nbsp; ';
			echo common::Link('Admin_Missing',$langmessage['delete'],'cmd=rmredir&link='.urlencode($source),' name="ajax_box" ');
			echo '</td>';
			echo '</tr>';
		}
		echo '</tbody>';
		echo '</table>';
		
		echo '<p>';
		$this->InlineBoxLink($langmessage['New Redirection']);
		echo '</p>';
		

		if( $has_invalid_target ){
			echo '<p>';
			echo ' <img src="'.common::GetDir('/include/imgs/error.png').'" alt="" height="16" width="16" style="vertical-align:middle" title="'.$langmessage['Target URL Invalid'].'"/> ';
			echo $langmessage['Target URL Invalid'];
			echo '</p>';
		}
	}
	
	function InlineBoxLink($label,$cmd='saveredir',$source='',$target='',$code='301'){
		
		$input = '<input type="hidden" name="cmd" value="'.htmlspecialchars($cmd).'" />';
		$input .= '<input type="hidden" name="source" value="'.htmlspecialchars($source).'" />';
		$input .= '<input type="hidden" name="orig_source" value="'.htmlspecialchars($source).'" />';
		$input .= '<input type="hidden" name="target" value="'.htmlspecialchars($target).'" />';
		$input .= '<input type="hidden" name="code" value="'.htmlspecialchars($code).'" />';
		echo common::Link('Admin_Missing',$label.$input,'',' name="inline_box" rel="#gp_redir" ');
	}
	
	function RedirForm(){
		global $langmessage,$gptitles,$page;
		
		common::PrepAutoComplete(true,false);

		echo '<div class="inline_box" id="gp_redir">';
		echo '<form method="post" action="'.common::GetUrl('Admin_Missing').'">';
		echo '<input type="hidden" name="cmd" value=""/>';
		echo '<input type="hidden" name="orig_source" value=""/>';

		
		echo '<table class="bordered">';
		echo '<tr><th>';
			echo $langmessage['Source URL'];
			echo '</th>';
			echo '<td>';
			echo common::GetUrl('');
			echo '<input type="text" name="source" value="" size="20"/>';
			echo '</td>';
			echo '</tr>';
		
		echo '<tr>';
			echo '<th>';
			echo $langmessage['Target URL'];
			echo '</th>';
			echo '<td>';
			echo '<input type="text" name="target" value="" class="autocomplete" size="40" />';
			echo '</td>';
			echo '</tr>';
			
		echo '<tr><th>';
			echo $langmessage['Method'];
			echo '</th>';
			echo '<td>';
			echo '<select name="code">';
			echo '<option value="301">'.$langmessage['301 Moved Permanently'].'</option>';
			echo '<option value="302">'.$langmessage['302 Moved Temporarily'].'</option>';
			echo '</select>';
			echo '</td></tr>';
		
		echo '<tr>';
			echo '<th>';
			echo '&nbsp;';
			echo '</th>';
			echo '<td>';
			echo '<input type="submit" name="" value="'.$langmessage['save_changes'].'" />'; //not using gppost because of autocomplete
			echo '</td></tr>';
			
		echo '</table>';
		
		echo '</form>';
		echo '</div>';
		
	}
	
	function CheckRedir(){
		global $gptitles,$langmessage;
		
		if( empty($_POST['source']) ){
			message($langmessage['OOPS']);
			return false;
		}
		
		if( $_POST['source'] == $_POST['target'] ){
			message($langmessage['OOPS']);
			return false;
		}
		
		if( gpFiles::CleanTitle($_POST['source']) == gpFiles::CleanTitle($_POST['target']) ){
			message($langmessage['OOPS']);
			return false;
		}
				
		if( $_POST['code'] != '302' ){
			$_POST['code'] = 301;
		}
		
		return true;
		
	}
	
	function UpdateRedir(){
		
		if( !$this->CheckRedir() ){
			return false;
		}
		
		$source = gpFiles::CleanTitle($_POST['source']); //CleanTitle because all requests go through WhichPage() which uses CleanTitle()	
		$orig_source = gpFiles::CleanTitle($_POST['orig_source']);

		if( !isset($this->error_data['redirects'][$orig_source]) ){
			message($langmessage['OOPS']);
			return false;
		}
		
		$data = array();
		$data['target'] = $_POST['target']; //gpFiles::CleanTitle($_POST['target']);
		$data['code'] = $_POST['code'];
		$data['raw_source'] = $_POST['source'];
		
		
		if( !gpFiles::ArrayReplace($orig_source,$source,$data,$this->error_data['redirects']) ){
			message($langmessage['OOPS']);
			return false;
		}

		return $this->SaveData_Message();
	}
	
	
	function SaveRedir(){
		global $langmessage;
		
		if( !$this->CheckRedir() ){
			return false;
		}
		
		
		$source = gpFiles::CleanTitle($_POST['source']); //CleanTitle because all requests go through WhichPage() which uses CleanTitle()
		
		if( isset($this->error_data['redirects'][$source]) ){
			message($langmessage['OOPS']);
			return false;
		}

		$this->error_data['redirects'][$source] = array();
		$this->error_data['redirects'][$source]['target'] = $_POST['target']; //gpFiles::CleanTitle($_POST['target']);
		$this->error_data['redirects'][$source]['code'] = $_POST['code'];
		$this->error_data['redirects'][$source]['raw_source'] = $_POST['source'];
		
		
		return $this->SaveData_Message();
	}
	
	function RmRedir(){
		global $langmessage;
		
		$link =& $_GET['link'];
		if( !isset($this->error_data['redirects'][$link]) ){
			message($langmessage['OOPS']);
			return false;
		}
		
		echo '<div class="inline_box">';
		echo '<form method="post" action="">';
		echo '<input type="hidden" name="cmd" value="rmredir_confirmed" />';
		echo sprintf($langmessage['generic_delete_confirm'],'<i>'.htmlspecialchars($link).'</i>');
		echo ' <input type="submit" class="submit" name="" value="'.$langmessage['continue'].'" />';
		echo ' <input type="hidden" name="link" value="'.htmlspecialchars($link).'" />';
		echo '</form>';
		echo '</div>';
		return true;
	}
	
	function RmRedirConfirmed(){
		global $langmessage;
		
		$link =& $_POST['link'];
		if( !isset($this->error_data['redirects'][$link]) ){
			message($langmessage['OOPS']);
			return false;
		}
		
		unset($this->error_data['redirects'][$link]);
		return $this->SaveData_Message();
	}
	
	
}




