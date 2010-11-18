<?php
defined('is_running') or die('Not an entry point...');

includeFile('tool/email_mailer.php');


class admin_configuration{
	
	var $variables;
	//var $defaultVals = array();
	
	function admin_configuration(){
		global $langmessage;
		
		
		$this->variables = array(
		
						// these values exist and are used, but not necessarily needed
		
						// these values aren't used
						//'author'=>'',
						//'timeoffset'=>'',
						//'fromname'=>'',
						//'fromemail'=>'',
						//'contact_message'=>'',
						//'dateformat'=>'',

						/* General Settings */
						'general_settings'=>false,
						'title'=>'',
						'keywords'=>'',
						'desc'=>'',
						
						'Interface'=>false,
						'colorbox_style' => array('example1'=>'Example 1', 'example2'=>'Example 2', 'example3'=>'Example 3', 'example4'=>'Example 4', 'example5'=>'Example 5', 'example6'=>'Example 6'),
						'jquery'=>'',
						'language'=>'',
						'langeditor'=>'',
						'maximgarea'=>'integer',
						'HTML_Tidy'=>'',
						'hidegplink'=>'',
						
						
						/* Contact Configuration */
						'contact_config'=>false,
						'toemail'=>'',
						'toname'=>'',
						'from_address'=>'',
						'from_name'=>'',
						'from_use_user'=>'boolean',
						'require_email'=>'',
						'mail_method'=>'',
						'sendmail_path'=>'',
						'smtp_hosts'=>'',
						'smtp_user'=>'',
						'smtp_pass'=>'password',
						//'fromemail'=>'',
						
						'reCaptcha'=>false,
						'recaptcha_public'=>'',
						'recaptcha_private'=>'',
						'recaptcha_language'=>'',
						);
						
		$cmd = common::GetCommand();
		switch($cmd){
			case 'save_config':
				$this->SaveConfig();
			break;
		}
		
		echo '<h2>'.$langmessage['configuration'].'</h2>';
		$this->showForm();
	}
	
	
	function SaveConfig(){
		global $config, $langmessage;
		
		$possible = $this->variables;
		
		foreach($possible as $key => $curr_possible){
			
			if( $curr_possible == 'boolean' ){
				if( isset($_POST[$key]) && ($_POST[$key] == 'true') ){
					$config[$key] = true;
				}else{
					$config[$key] = false;
				}
				
			}elseif( $curr_possible == 'integer' ){
				if( isset($_POST[$key]) && is_numeric($_POST[$key]) ){
					$config[$key] = $_POST[$key];
				}
				
			}elseif( isset($_POST[$key]) ){
				$config[$key] = $_POST[$key];
			}
		}
		
		if( !admin_tools::SaveConfig() ){
			message($langmessage['OOPS']);
			return false;
		}
		message($langmessage['SAVED']);
	}
	
	
	function getValues(){
		global $config,$gp_mailer;
		
		if( $_SERVER['REQUEST_METHOD'] != 'POST'){
			$show = $config;
		}else{
			$show = $_POST;
		}
		if( empty($show['jquery']) ){
			$show['jquery'] = 'local';
		}
		if( empty($show['recaptcha_language']) ){
			$show['recaptcha_language'] = 'inherit';
		}
		
		if( empty($show['from_address']) ){
			$show['from_address'] = $gp_mailer->From_Address();
		}
		if( empty($show['from_name']) ){
			$show['from_name'] = $gp_mailer->From_Name();
		}
		if( empty($show['mail_method']) ){
			$show['mail_method'] = $gp_mailer->Mail_Method();
		}
		if( empty($show['sendmail_path']) ){
			$show['sendmail_path'] = $gp_mailer->Sendmail_Path();
		}
	
		return $show;
	}
	
	function getPossible(){
		global $rootDir,$langmessage;
		
		$possible = $this->variables;
		
		//$langDir = $rootDir.'/include/thirdparty/fckeditor/editor/lang'; //fckeditor
		$langDir = $rootDir.'/include/thirdparty/ckeditor_32/lang'; //ckeditor
		
		$possible['langeditor'] = gpFiles::readDir($langDir,'js');
		unset($possible['langeditor']['_languages']);
		$possible['langeditor']['inherit'] = ' '.$langmessage['default']; //want it to be the first in the list
		asort($possible['langeditor']);
		
		
		//recaptcha language
		$possible['recaptcha_language'] = array();
		$possible['recaptcha_language']['inherit'] = $langmessage['default'];
		$possible['recaptcha_language']['en'] = 'en';
		$possible['recaptcha_language']['nl'] = 'nl';
		$possible['recaptcha_language']['fr'] = 'fr';
		$possible['recaptcha_language']['de'] = 'de';
		$possible['recaptcha_language']['pt'] = 'pt';
		$possible['recaptcha_language']['ru'] = 'ru';
		$possible['recaptcha_language']['es'] = 'es';
		$possible['recaptcha_language']['tr'] = 'tr';

		
		
		//website language
		$langDir = $rootDir.'/include/languages';
		$possible['language'] = gpFiles::readDir($langDir,1);
		asort($possible['language']);
		
		//jQuery
		$possible['jquery'] = array('local'=>'Local','google'=>'Google');
		
		//gpEasy Link
		$possible['hidegplink'] = array(''=>'Show','hide'=>'Hide');
		
		//tidy
		if( function_exists('tidy_parse_string') ){
			$possible['HTML_Tidy'] = array(''=>$langmessage['On'],'off'=>$langmessage['Off']);
		}else{
			$possible['HTML_Tidy'] = array(''=>'Unavailable');
		}


		
		//
		$possible['require_email'] = array(	'none'=>'None',
											''=>'Subject &amp; Message',
											'email'=>'Subject, Message &amp; Email');


		//see xoopsmultimailer.php
		$possible['mail_method'] = array(	'mail'=>'PHP mail()',
											'sendmail'=>'sendmail',
											'smpt'=>'smpt',
											'smtpauth'=>'SMTPAuth');
		
		return $possible;
	}
	
	function showForm(){
		global $langmessage;
		$possibleValues = $this->getPossible();
		
		
		$array = $this->getValues();
		
		echo '<form action="'.common::GetUrl('Admin_Configuration').'" method="post">';
		echo '<div class="collapsible">';
		
		
		//order by the possible values
		$openbody = false;
		foreach($possibleValues as $key => $possibleValue){
			
			if( $possibleValue === false ){
				$class = $style = '';
				if( $openbody ){
					echo '</table>';
					echo '</div>';
					$class = ' hidden';
					$style = ' style="display:none"';
				}
				echo '<h4 class="head'.$class.'">';
				if( isset($langmessage[$key]) ){
					echo $langmessage[$key];
				}else{
					echo str_replace('_',' ',$key);
				}
				echo '</h4>';
				
				//start new
				echo '<div '.$style.'>';
				echo '<table cellpadding="4" class="bordered configuration collapsible">';

				$openbody = true;
				continue;
			}
			
			if( isset($array[$key]) ){
				$value = $array[$key];
			}else{
				$value = '';
			}
			
			echo "\n\n";
			
			
			echo '<tr><td style="white-space:nowrap">';
			if( isset($langmessage[$key]) ){
				echo $langmessage[$key];
			}else{
				echo str_replace('_',' ',$key);
			}
			echo '</td>';
			echo '<td>';
			
			$curr_possible = $possibleValues[$key];
			if( $curr_possible === false ){
				echo 'unavailable';
			}elseif( is_array($curr_possible) ){
				$this->formSelect($key,$curr_possible,$value);
			}elseif( $curr_possible == 'boolean'){
				$this->formCheckbox($key,$value);
			}elseif( $curr_possible == 'password' ){
				$this->formInput($key,$value,'password');
			}else{
				$this->formInput($key,$value);
			}
			
/*
			if( isset($this->defaultVals[$key]) ){
				echo '<br/> <span class="sm">';
				echo $this->defaultVals[$key];
				echo '</span>';
			}
*/
			

			if( isset($langmessage['about_config'][$key]) ){
				echo $langmessage['about_config'][$key];
			}
			echo '</td></tr>';
			
		}
		
		echo '</table>';
		echo '</div>';
		echo '</div>'; //end collapsible
		
		echo '<div style="margin:1em 0">';
		echo '<input type="hidden" name="cmd" value="save_config" />';
		echo '<input value="'.$langmessage['save'].'" type="submit" class="submit" name="aaa" accesskey="s" />';
		echo ' &nbsp; ';
 		echo '<input type="reset"  />';
 		echo '</div>';
		echo '</form>';
		
		
		return;
	}
	
	
	//
	//	Form Functions
	//
	
	
	function formCheckbox($key,$value){
		$checked = '';
		if( $value ){
			$checked = ' checked="checked"';
		}
		echo '<input type="checkbox" name="'.$key.'" value="true" '.$checked.'/> &nbsp;';
	}

	function formInput($name,$value,$type='text'){
		
		$len = (strlen($value)+20)/20;
		$len = round($len);
		$len = $len*20;
		
		$value = htmlspecialchars($value);
		
		
		static $textarea = '<textarea name="%s" cols="30" rows="%d">%s</textarea>';
		if($len > 100 && (strpos($value,' ') != false) ){
			$cols=40;
			$rows = ceil($len/$cols);
			echo sprintf($textarea,$name,$rows,$value);
			return;
		}
		
		$len = min(40,$len);
		$text = '<input name="%s" size="%d" value="%s" type="'.$type.'" class="text"/>';
		echo '<div>';
		echo "\n".sprintf($text,$name,$len,$value);
		echo '</div>';
	}
	
	function formSelect($name,$possible,$value=null){
		
		echo '<div>';
		echo "\n".'<select name="'.$name.'">';
		if( !isset($possible[$value]) ){
			echo '<option value="" selected="selected"></option>';
		}
		
		$this->formOptions($possible,$value);
		echo '</select>';
		echo '</div>';
	}
	
	function formOptions($array,$current_value){
		global $languages;
		
		foreach($array as $key => $value){
			if( is_array($value) ){
				echo '<optgroup label="'.$value.'">';
				$this->formOptions($value,$current_value);
				echo '</optgroup>';
				continue;
			}
			
			if($key == $current_value){
				$focus = ' selected="selected" ';
			}else{
				$focus = '';
			}
			if( isset($languages[$value]) ){
				$value = $languages[$value];
			}

			echo '<option value="'.htmlspecialchars($key).'" '.$focus.'>'.$value.'</option>';
			
		}
		
	}
	
}

