<?php

class gp_recaptcha{
	
	function isActive(){
		global $config;
		
		if( !empty($config['recaptcha_public']) && !empty($config['recaptcha_private']) ){
			return true;
		}
		return false;
	}
	
	function Form(){
		global $config,$rootDir;
		
			require_once($rootDir.'/include/thirdparty/recaptchalib.php');
			$lang = $config['recaptcha_language'];
			if( $lang == 'inherit' ){
				$lang = $config['language'];
			}
			
			$recaptchaLangs['en'] = true;
			$recaptchaLangs['nl'] = true;
			$recaptchaLangs['fr'] = true;
			$recaptchaLangs['de'] = true;
			$recaptchaLangs['pt'] = true;
			$recaptchaLangs['ru'] = true;
			$recaptchaLangs['es'] = true;
			$recaptchaLangs['tr'] = true;
			if( isset($recaptchaLangs[$lang]) ){
				echo '<script type="text/javascript">var RecaptchaOptions = { lang : "'.$lang.'", };</script>';
			}
			
			echo recaptcha_get_html($config['recaptcha_public']);
	}

	function Check(){
		global $page,$langmessage,$config,$rootDir;
		
		if( !gp_recaptcha::isActive() ){
			return true;
		}
		
		require_once($rootDir.'/include/thirdparty/recaptchalib.php');
		$resp = recaptcha_check_answer($config['recaptcha_private'],
										$_SERVER['REMOTE_ADDR'],
										$_POST['recaptcha_challenge_field'],
										$_POST['recaptcha_response_field']);


		
		if (!$resp->is_valid) {
			message($langmessage['INCORRECT_CAPTCHA']);
			//if( common::LoggedIn() ){
			//	message($langmessage['recaptcha_said'],$resp->error);
			//}
			return false;
		}

		return true;
		
	}

	
}
