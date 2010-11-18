<?php
defined('is_running') or die('Not an entry point...');

global $gp_mailer;
//includeFile('tool/email.php');
includeFile('thirdparty/PHPMailer_v2.0.4/class.phpmailer.php');


/**
 * An extension of phpmailer for usage with gpeasy
 * 
 * @since 1.7
 * 
 */
class gp_phpmailer extends PHPMailer{
	
	function gp_phpmailer(){
		global $rootDir,$config;
		
		$this->Reset();
		$this->PluginDir = $rootDir.'/thirdparty/PHPMailer_v2.0.4/';
		$this->CharSet = 'utf-8';
		$this->ContentType = 'text/html';
		
		$mail_method = $this->Mail_Method();
		switch($mail_method){
			
			//smtp & smtpauth
			case 'smtpauth':
				$this->SMTPAuth = true;
				$this->Username = common::ConfigValue('smtp_user','');
				$this->Password = common::ConfigValue('smtp_pass','');
			case 'smtp';
				$this->IsSMTP();
				$this->Host = common::ConfigValue('smtp_hosts','');
			break;
			
			//sendmail
			case 'sendmail':
				$this->IsSendmail();
				$this->Sendmail = $this->Sendmail_Path();
			break;
			
			//mail
			default:
				$this->IsMail();
				$this->Mailer = 'mail';
			break;
		}
		

	}
	
	// Empty out the values that may be set
	function Reset(){
		$this->ClearAddresses();
		$this->ClearAllRecipients();
		$this->ClearAttachments();
		$this->ClearBCCs();
		$this->ClearCCs();
		$this->ClearCustomHeaders();
		$this->ClearReplyTos();
		
		$this->From = $this->From_Address();
		$this->FromName = $this->From_Name();
	}	
	
	
	/**
	 * 
	 * @param string|array $to Array or comma-separated list of email addresses to send message.
	 * @param string $subject Email subject
	 * @param string $message Message contents
	 * @return bool Whether the email contents were sent successfully.
	 */
	function SendEmail($to,$subject,$message){
	//function SendEmail($to,$subject,$message,$headers=array()){
		global $config;
		
		// Set destination addresses
		foreach( (array)$to as $recipient){
			$recipient = $this->SplitNameAddress($recipient);
			$this->AddAddress( $recipient['address'], $recipient['name'] );
		}
		
		// Set mail's subject and body
		$this->Subject = $subject;
		$this->Body    = $message;
		
		
		// Send!
		$result = @$this->Send();
	
		$this->Reset();
		
		return $result;		
	}
	
  /**
   * Overrides the default from address for the current email. Similar to phpmailer's AddAddress() method
   * @param string $address
   * @param string $name
   * @return void
   */
	function SetFrom($address, $name = '') {
		$this->From = trim($address);
		$this->FromName = $name;
	}

	
	function SplitNameAddress($address){
		
		$address_name = '';
		if ( strpos($address, '<' ) !== false ) {
			$address_name = substr( $address, 0, strpos( $address, '<' ) - 1 );
			$address_name = str_replace( '"', '', $address_name );
			$address_name = trim( $address_name );

			$address = substr( $address, strpos( $address, '<' ) + 1 );
			$address = str_replace( '>', '', $address );
			$address = trim( $address );
		} else {
			$address = trim( $address );
		}
		
		return array('name'=>$address_name,'address'=>$address);
	}

	
	function From_Address(){
		global $config;
		
		if( !empty($config['from_address']) ){
			return $config['from_address'];
		}
		
		$from = ini_get('sendmail_from');
		if( !empty($sendmail_from) ){
			return $from;
		}
		if( isset($_SERVER['HTTP_HOST']) ){
			$server = $_SERVER['HTTP_HOST'];
		}elseif( isset($_SERVER['SERVER_NAME']) ){
			$server = $_SERVER['SERVER_NAME'];
		}else{
			$server = 'localhost';
		}
		if( substr( $server, 0, 4 ) == 'www.' ){
			$server = substr( $server, 4 );
		}
		return 'AutomatedSender@'.$server;
	}
	
	function From_Name(){
		return common::ConfigValue('from_name','Automated Sender');
	}
	
	function Mail_Method(){
		return common::ConfigValue('mail_method','mail');
	}
	
	function Sendmail_Path(){
		return common::ConfigValue('sendmail_path','/usr/sbin/sendmail');
	}
		
	
}

// (Re)create it, if it's gone missing
if ( !is_object( $gp_mailer ) || !is_a( $gp_mailer, 'gp_phpmailer' ) ) {
	$gp_mailer = new gp_phpmailer();
}


