<?php
defined('is_running') or die('Not an entry point...');


//mod_rewrite settings
if( !isset($_SERVER['gp_rewrite']) ){
	if( defined('gp_indexphp') && (gp_indexphp === false) ){
		$_SERVER['gp_rewrite'] = true;
	}else{
		$_SERVER['gp_rewrite'] = false;
	}
}else{
	$_SERVER['gp_rewrite'] = true;
}
	
defined('gpdebug') or define('gpdebug',false);
defined('gptesting') or define('gptesting',false);


@ini_set( 'session.use_only_cookies', '1' );
@ini_set( 'default_charset', 'utf-8' );

error_reporting(E_ALL);
set_error_handler('showError');
if( gpdebug === true ){
	error_reporting(E_ALL);
}else{
	error_reporting(0);
}



//see /var/www/others/mediawiki-1.15.0/languages/Names.php
$languages = array();
$languages['ar'] = 'العربية';
$languages['ca'] = 'Català';
$languages['da'] = 'Dansk';
$languages['de'] = 'Deutsch';
$languages['en'] = 'English';
$languages['es'] = 'Español';
$languages['fi'] = 'Suomi';
$languages['fr'] = 'Français';
$languages['gl'] = 'Galego';
$languages['hu'] = 'Magyar';
$languages['it'] = 'Italiano';
$languages['nl'] = 'Nederlands';
$languages['no'] = 'Norsk';
$languages['pl'] = 'Polski';
$languages['pt'] = 'Português';
$languages['ru'] = 'Русский';
$languages['sk'] = 'Slovenčina';	# Slovak
$languages['sl'] = 'Slovenščina';	# Slovenian
$languages['sv'] = 'Svenska';		# Swedish
$languages['tr'] = 'Türkçe';		# Turkish
$languages['zh'] = '中文';			# (Zhōng Wén) - Chinese




$gpversion = '1.7.1';
$addonDataFolder = false;//deprecated
$addonCodeFolder = false;//deprecated
$addonPathData = false;
$addonPathCode = false;
$addonBrowsePath = 'http://www.gpeasy.com/index.php'; //'http://gpeasy.loc/glacier/index.php';
$checkFileIndex = true;


if( !defined('E_STRICT')){
	define('E_STRICT',2048);
}
if( !defined('E_RECOVERABLE_ERROR')){
	define('E_RECOVERABLE_ERROR',4096);
}
if( !defined('E_DEPRECATED') ){
	define('E_DEPRECATED',8192);
}
if( !defined('E_USER_DEPRECATED') ){
	define('E_USER_DEPRECATED',16384);
}


/* from wordpress
 * wp-settings.php
 * see also classes.php
 */
// Fix for IIS, which doesn't set REQUEST_URI
if ( empty( $_SERVER['REQUEST_URI'] ) ) {

	// IIS Mod-Rewrite
	if (isset($_SERVER['HTTP_X_ORIGINAL_URL'])) {
		$_SERVER['REQUEST_URI'] = $_SERVER['HTTP_X_ORIGINAL_URL'];
	}
	
	// IIS Isapi_Rewrite
	else if (isset($_SERVER['HTTP_X_REWRITE_URL'])) {
		$_SERVER['REQUEST_URI'] = $_SERVER['HTTP_X_REWRITE_URL'];
		
	}else{
		
		// Use ORIG_PATH_INFO if there is no PATH_INFO
		if ( !isset($_SERVER['PATH_INFO']) && isset($_SERVER['ORIG_PATH_INFO']) ){
			$_SERVER['PATH_INFO'] = $_SERVER['ORIG_PATH_INFO'];
		}
			

		// Some IIS + PHP configurations puts the script-name in the path-info (No need to append it twice)
		if ( isset($_SERVER['PATH_INFO']) ) {
			if( $_SERVER['PATH_INFO'] == $_SERVER['SCRIPT_NAME'] ){
				$_SERVER['REQUEST_URI'] = $_SERVER['PATH_INFO'];
			}else{
				$_SERVER['REQUEST_URI'] = $_SERVER['SCRIPT_NAME'] . $_SERVER['PATH_INFO'];
			}
		}

		// Append the query string if it exists and isn't null
		if (isset($_SERVER['QUERY_STRING']) && !empty($_SERVER['QUERY_STRING'])) {
			$_SERVER['REQUEST_URI'] .= '?' . $_SERVER['QUERY_STRING'];
		}
	}
}



function showError($errno, $errmsg, $filename, $linenum, $vars){
	
	if( gpdebug === false ){
		return;
	}
	
	// for "Undefined variable"
	if( $errno === 2048 ){
		return;
	}
	
	// for functions prepended with @ symbol to suppress errors
	if($errno === 0){
		return;
	}

	$errortype = array (
				E_ERROR				=> "Error",
				E_WARNING			=> "Warning",
				E_PARSE				=> "Parsing Error",
				E_NOTICE 			=> "Notice",
				E_CORE_ERROR		=> "Core Error",
				E_CORE_WARNING 		=> "Core Warning",
				E_COMPILE_ERROR		=> "Compile Error",
				E_COMPILE_WARNING 	=> "Compile Warning",
				E_USER_ERROR		=> "User Error",
				E_USER_WARNING 		=> "User Warning",
				E_USER_NOTICE		=> "User Notice",
				E_STRICT			=> "Runtime Notice",
				E_RECOVERABLE_ERROR => 'Recoverable Error',
				E_DEPRECATED		=> "Deprecated",
				E_USER_DEPRECATED	=> "User Deprecated",
			 );

	$mess = '';
	$mess .= '<fieldset style="padding:1em">';
	$mess .= '<legend>'.$errortype[$errno].' ('.$errno.')</legend> '.$errmsg;
	$mess .= '<br/> &nbsp; &nbsp; <b>in:</b> '.$filename;
	$mess .= '<br/> &nbsp; &nbsp; <b>on line:</b> '.$linenum;
	
	//mysql.. for some addons
	if( function_exists('mysql_errno') && mysql_errno() ){
		$mess .= '<br/> &nbsp; &nbsp; Mysql Error ('.mysql_errno().')'. mysql_error();
	}
	
	//backtrace
	if( ($errno !== E_NOTICE) && ($errno != E_STRICT) && function_exists('debug_backtrace') ){
		$mess .= '<div><a href="javascript:void(0)" onclick="this.nextSibling.style.display=\'block\';;return false;">Show Backtrace</a>';
		$mess .= '<div style="display:none">';
		
		$temp = debug_backtrace(); //php 4.3+
		@array_shift($temp); //showError()
		$mess .= showArray($temp);
		
		$mess .= '</div>';
		$mess .= '</div>';
	}
	$mess .= '</p>';
	$mess .= '</fieldset>';
	
	if( gpdebug === true ){
		echo $mess;
	}else{
		includeFile('tool/email.php');
		gp_email::SendEmail(gpdebug, 'debug ', $mess);
	}
}


function microtime_diff($a, $b, $eff = 6) {
	$a = array_sum(explode(" ", $a));
	$b = array_sum(explode(" ", $b));
	return sprintf('%0.'.$eff.'f', $b-$a);
}
/*
echo '<h2>'.microtime_diff($start_time,microtime()).'</h2>';
*/





//If Magic Quotes
//GPC: GET/POST/COOKIE .. therefore REQUEST

if ( function_exists( 'get_magic_quotes_gpc' ) && get_magic_quotes_gpc() ) {
	fix_magic_quotes( $_GET );
	fix_magic_quotes( $_POST );
	fix_magic_quotes( $_COOKIE );
	fix_magic_quotes( $_REQUEST );
	
	//In version 4, $_ENV was also quoted
	//fix_magic_quotes( $_ENV ); //use GETENV() instead of $_ENV
	
	//doing this can break the application, the $_SERVER variable is not affected by magic_quotes
	//fix_magic_quotes( $_SERVER ); 
}

//If Register Globals
if( ini_get('register_globals') ){
	foreach($_REQUEST as $key => $value){
		$key = strtolower($key);
		if( ($key == 'globals') || $key == '_post'){
			die('Hack attempted.');
		}
	}
}


function fix_magic_quotes( &$arr ) {
	$new = array();
	foreach( $arr as $key => $val ) {
		$key = stripslashes($key);
		
		if( is_array( $val ) ){
			fix_magic_quotes( $val );
		}else{
			$val = stripslashes( $val );
		}
		$new[$key] = $val;
	}
	$arr = $new;
}

function message(){
	global $wbMessageBuffer;
	$wbMessageBuffer[] = func_get_args();
}
function includeFile( $file){
	global $rootDir;
	require_once( $rootDir.'/include/'.$file );
}
function GetMessages(){
	global $wbMessageBuffer;
	
	if( empty($wbMessageBuffer) ){
		return;
	}

	$result = '';
	foreach($wbMessageBuffer as $args){
		if( !isset($args[0]) ){
			continue;
		}
		
		if( isset($args[1]) ){
			$result .= '<li>'.call_user_func_array('sprintf',$args).'</li>';
		}else{
			$result .= '<li>'.$args[0].'</li>';
		}
	}
	//$result = str_replace('%s',' ',$result);
	
	
	$wbMessageBuffer = array();
	echo '<div class="messages">';
	echo '<a style="float:right;text-decoration:none;line-height:0;font-weight:bold;margin:3px 0 0 2em;color:#666;font-size:larger;display:none;" href="" class="req_script" name="close_message">';
	echo 'x';
	echo '</a>';
	echo '<ul>'.$result.'</ul></div>';
}
	
	
if( !function_exists('array_combine') ){
	function array_combine($arr1, $arr2) {
		
	    $out = array();
	   
	    $arr1 = array_values($arr1);
	    $arr2 = array_values($arr2);
	   
	    foreach($arr1 as $key1 => $value1) {
	        $out[(string)$value1] = $arr2[$key1];
	    }
	   
	    return $out;
	}
}


function showArray($array){
	if( is_object($array) ){
		$array = get_object_vars($array);
	}

	$text = array();
	$text[] = '<table cellspacing="0" cellpadding="7" class="tableRows" border="0">';
	if(is_array($array)){
		$odd = null;
		$odd2 = null;
		
		foreach($array as $key => $value){
			
			if($odd2==1){
				$odd = 'bgcolor="white"';
				//$odd = ' class="tableRowEven" ';
				$odd2 = 2;
			}else{
				$odd = 'bgcolor="#ddddee"';
				//$odd = ' class="tableRowOdd" ';
				$odd2 = 1;
			}
			$text[] = '<tr '.$odd.'><td>';	
 			$text[] = $key;
			$text[] = "</td><td>";
			if( is_bool($value) ){
				if($value){
					$text[]= '<tt>TRUE</tt>';
				}else{
					$text[] = '<tt>FALSE</tt>';
				}
			}elseif( !empty($value) ){
				
				
				if( is_object($value) || is_array($value) ){
					$text[] = showArray($value);
				}elseif(is_string($value)||is_numeric($value)){
					$text[] = htmlspecialchars($value);
				}elseif( is_bool($value) ){
				}else{
					$text[] = '<b>--unknown value--:</b> '.gettype($value);
				}
			}
			$text[] = "</td></tr>";
		}
	}else{
		$text[] = '<tr><td>'.$array.'</td></tr>';
	}
	$text[] = "</table>";

	return "\n".implode("\n",$text)."\n";
}


/*
 *	Display Class 
 */

class display{
	var $pagetype = 'display';
	var $title;
	var $theme;
	var $theme_name;
	var $theme_color;
	var $gpLayout;
	var $label;
	var $file;
	var $contentBuffer;
	var $TitleInfo;
	var $fileType = '';
	var $ajaxReplace = array('#gpx_content');
	var $admin_links = array();
	
	//<head> content
	var $head = '';
	var $head_script = '';
	var $jQueryCode = false;
	var $admin_js = false;
	var $admin_css = '';
	
	
	var $editable_content = true;
	var $editable_details = true;

	function display($title,$type){
		$this->title = $title;
		$this->fileType = $type;
		$this->SetTheme();
	}
	
	
	//see special_missing.php and admin_missing.php
	function Error_404($requested){
		includeFile('special/special_missing.php');
		ob_start();
		new special_missing($requested);
		$this->contentBuffer = ob_get_clean();
	}
	
	
	function RunScript(){
		global $langmessage, $gptitles, $dataDir;
		
		if( !isset($gptitles[$this->title]) ){
			$this->Error_404($this->title);
			return;
		}

		$this->TitleInfo = $gptitles[$this->title];
		$this->label = $this->TitleInfo['label'];
		$this->file = $dataDir.'/data/_pages/'.$this->title.'.php';

		
		$cmd = common::GetCommand();
		if( !empty($cmd) && common::LoggedIn() ){
			require($GLOBALS['rootDir'].'/include/tool/editing_files.php');
			new file_editing();
		}
		
		if( !empty($this->contentBuffer) ){
			return;
		}
		
		$admin = common::LoggedIn() && ($cmd != 'edit') && admin_tools::HasPermission('file_editing');
		$class = '';
		if( $admin ){
			$class = 'editable_area ';
		}
		
		ob_start();
		echo '<div class="'.$class.'GPAREA">'; // class="edit_area" added by javascript
		if( $admin ){
			echo common::Link($this->title,$langmessage['edit'],'cmd=edit',' class="ExtraEditLink" style="display:none" title="'.htmlspecialchars($this->title).'" ');
			//echo common::Link($this->title,$langmessage['edit'],'cmd=edit',' class="ExtraEditLink" style="display:none" title="'.htmlspecialchars($this->title).'" name="ckeditor_inline" ');
		}
	
		if( $this->fileType == 'gallery' ){
			common::ShowingGallery();
			echo '<h2>'.$this->label.'</h2>';
		}
		require($this->file);
		if( isset($meta_data) ){
			$this->meta_data = $meta_data;
		}
		
		echo '<div style="clear:both;"></div>';
		echo '</div>';
		
		$this->contentBuffer = ob_get_clean();
		
		$this->ReplaceContent($this->contentBuffer);
	}
	
	function area_name($name){
		$name = base64_encode($this->title);
		return str_replace('=','',$name);
	}
	
	function ReplaceContent(&$content,$offset=0){
		global $dataDir,$gptitles;
		static $includes = 0;
		
		//prevent too many inlcusions
		if( $includes >= 10 ){
			return;
		}
		
		$pos = strpos($content,'{{',$offset);
		if( $pos === false ){
			return;
		}
		$pos2 = strpos($content,'}}',$pos);
		if( $pos2 === false ){
			return;
		}
			
		$arg = substr($content,$pos+2,$pos2-$pos-2);
		$title = gpFiles::CleanTitle($arg);
		if( !isset($gptitles[$title]) ){
			$this->ReplaceContent($content,$pos2);
			return;
		}
		
		$type = common::PageType($title);
		$file = $dataDir.'/data/_pages/'.gpFiles::CleanTitle($title).'.php';
		if( !file_exists($file) ){
			$this->ReplaceContent($content,$pos2);
			return;
		}
		
		ob_start();
		require($file);
		$replacement = ob_get_clean();
		
		$includes++;
		switch($type){
			case 'gallery':
				common::ShowingGallery();
			break;
		}
		
		//is {{...}} wrapped by <p>..</p>?
		$pos3 = strpos($content,'</p>',$pos2);
		if( $pos3 > 0 ){
			$pieceAfter = substr($content,$pos2,($pos3-$pos2));
			if( strpos($pieceAfter,'<') == false ){
				$replacement = "</p>\n".$replacement."\n<p>";
			}
		}
		
		$replacement = "\n<!-- replacement -->\n".$replacement."\n<!-- end replacement -->\n";
		
		
		$content = substr_replace($content,$replacement,$pos,$pos2-$pos+2);
		$this->ReplaceContent($content,$pos);
	}
	
	
	function SetTheme($layout=false){
		global $gpLayouts;
		
		if( $layout === false ){
			$layout = display::OrConfig($this->title,'gpLayout');
		}
		if( !isset($gpLayouts[$layout]) ){
			return false;
		}
		
		$theme = $gpLayouts[$layout]['theme'];
		$this->gpLayout = $layout;
		
		
		$this->theme = $theme;
		$this->theme_name = dirname($theme);
		$this->theme_color = basename($theme);
		return false;
	}
	

	
	//sets this title's info to config values, if a value doesn't exists specifically for the title
	function OrConfig($title,$var){
		global $config,$gptitles;
		
		
		if( !empty($gptitles[$title][$var]) ){
			return $gptitles[$title][$var];
		}
		
		if( display::ParentConfig($title,$var,$value) ){
			return $value;
		}
		
		if( isset($config[$var]) ){
			return $config[$var];
		}
		
		return false;
	}
	
	function ParentConfig($checkTitle,$var,&$value){
		global $gpmenu,$gptitles;
		
		//get configuration of parent titles
		if( !isset($gpmenu[$checkTitle]) ){
			return false;
		}
		
		$checkLevel = $gpmenu[$checkTitle];
		
		$menutitles = array_keys($gpmenu);
		$key = array_search($checkTitle,$menutitles);
		for($i = ($key-1); $i >= 0; $i--){
			$title = $menutitles[$i];
			
			//check the level
			$level = $gpmenu[$title];
			if( $level >= $checkLevel ){
				continue;
			}
			$checkLevel = $level;
			
			if( !empty($gptitles[$title][$var]) ){
				//die('hmm: '. $gptitles[$title][$var]);
				$value = $gptitles[$title][$var];
				return true;
			}
			
			//no need to go further
			if( $level == 0 ){
				return false;
			}
			
		}
		return false;
	}
	
	
	/*
	 * Get functions
	 * 
	 * Missing:
	 *		$#sitemap#$
	 * 		different menu output
	 * 
	 */	
	
	function GetSiteLabel(){
		global $config;
		echo $config['title'];
	}
	function GetSiteLabelLink(){
		global $config;
		echo common::Link('',$config['title']);
	}
	function GetPageLabel(){
		echo $this->label;
	}

	
	/* deprecated */
	function GetAllGadgets(){
		gpOutput::GetAllGadgets();
	}
	
	/* deprecated */
	function GetGadget(){}

	/* deprecated */
	function GetExpandMenu(){
		gpOutput::Get('ExpandMenu');
	}
	
	/* deprecated */
	function GetFullMenu(){
		gpOutput::Get('FullMenu');
	}
	/* deprecated */
	function GetMenu(){
		gpOutput::Get('Menu');
	}
	/* deprecated */
	function GetSubMenu(){
		gpOutput::Get('SubMenu');
	}
	/* deprecated */
	function GetExpandLastMenu(){
		gpOutput::Get('ExpandLastMenu');
	}
	/* deprecated */
	function GetTopTwoMenu(){
		gpOutput::Get('TopTwoMenu');
	}
	/* deprecated */
	function GetBottomTwoMenu(){
		gpOutput::Get('BottomTwoMenu');
	}
	
	/* deprecated */
	function GetFooter(){
		gpOutput::Get('Extra','Footer');
	}
	/* deprecated */
	function GetExtra($name='Side_Menu'){
		gpOutput::Get('Extra',$name);
	}
	/* deprecated */
	function GetAdminLink(){
		gpOutput::GetAdminLink();
	}

	/* deprecated */
	function GetHead() {
		gpOutput::GetHead();
	}
	
	/* deprecated */
	function GetLangText($key){
		gpOutput::Get('Text',$key);
	}
	
	function GetContent(){
		
		$class = 'filetype-'.$this->fileType;
		if( isset($this->meta_data['file_number']) ){
			$class .= ' filenum-'.$this->meta_data['file_number'];
		}
		
		
		echo '<div id="gpx_content" class="'.$class.'">';
		GetMessages();
		echo $this->contentBuffer;
		echo '</div>';
		
	}

	/* deprecated */
	//returnMessages
	function GetMessages(){
		GetMessages();
	}
}
	




class common{
	
	function RunOut(){
		global $page;
		
		$page->RunScript();
		
		//decide how to send the content
		gpOutput::Prep();
		$req = '';
		if( isset($_REQUEST['gpreq']) ){;
			$req = $_REQUEST['gpreq'];
		}
		switch($req){
			
			case 'flush':
				gpOutput::Flush();
			break;
			
			case 'body':
				gpOutput::BodyAsHTML();
			break;
			
			case 'json':
				includeFile('tool/ajax.php');
				gpAjax::Response();
			break;
			
			case 'content':
				gpOutput::Content();
			break;
			
			default:
				gpOutput::Template();
			break;
		}
	}
	
	
	/* 
	 * 
	 * 
	 * Entry Functions 
	 * 
	 * 
	 */
	
	function EntryPoint($level=0,$expecting='index.php'){
		
		clearstatcache();
		ob_start( 'ob_gzhandler' );//available since 4.0.4
		common::SetGlobalPaths($level,$expecting);
		common::RequestLevel();
		common::gpInstalled();
		common::GetConfig();
		common::sessions();
		includeFile('tool/gpOutput.php');
	
	}

	function gpInstalled(){
		global $dataDir, $rootDir;
		if( file_exists($dataDir.'/data/_site/config.php') ){
			return;
		}
		
		if( file_exists($rootDir.'/include/install/install.php') ){
			includeFile('install/install.php');
			die();
		}
		
		die('<p>Sorry, this site is temporarily unavailable.</p>');
		
	}

	function SetGlobalPaths($DirectoriesAway,$expecting){
		global $dataDir, $dirPrefix, $rootDir;
		
		$rootDir = str_replace('\\','/',dirname(dirname(__FILE__)));
		
		//dataDir, make sure it contains $expecting. Some servers using cgi do not set this properly
		$dataDir = common::GetEnv('SCRIPT_FILENAME',$expecting);
		if( $dataDir !== false ){
			$dataDir = common::ReduceGlobalPath($dataDir,$DirectoriesAway);
		}else{
			$dataDir = $rootDir;
		}
		
		
		//$dirPrefix
		$dirPrefix = common::GetEnv('SCRIPT_NAME',$expecting);
		if( $dirPrefix === false ){
			$dirPrefix = common::GetEnv('PHP_SELF',$expecting);
		}
		
		//remove everything after $expecting, $dirPrefix can at times include the PATH_INFO
		$pos = strpos($dirPrefix,$expecting);
		$dirPrefix = substr($dirPrefix,0,$pos+strlen($expecting));
		
		$dirPrefix = common::ReduceGlobalPath($dirPrefix,$DirectoriesAway);
		if( $dirPrefix == '/' ){
			$dirPrefix = '';
		}
		
		
		// Not entirely secure: http://blog.php-security.org/archives/72-Open_basedir-confusion.html
		// Only allowed to tighten open_basedir in php 5.3+
		if( $dataDir !== $rootDir ){
			//include directory and $dataDir
			$path = $dataDir.PATH_SEPARATOR.$rootDir.'/include'.PATH_SEPARATOR.$rootDir.'/themes'.PATH_SEPARATOR.$rootDir.'/addons';
			@ini_set('open_basedir',$path);
		}
		
	}
	
	//get the environment variable and make sure it contains $expecting
	function GetEnv($var,$expecting=false){
		$value = false;
		if( isset($_SERVER[$var]) ){
			$value = $_SERVER[$var];
		}else{
			$value = getenv($var);
		}
		if( $expecting && strpos($value,$expecting) === false ){
			return false;
		}
		return $value;
	}
	
	function ReduceGlobalPath($path,$DirectoriesAway){
		$path = dirname($path);
		
		$i = 0;
		while($i < $DirectoriesAway){
			$path = dirname($path);
			$i++;
		}
		return str_replace('\\','/',$path);
	}

	
	
	//use dirPrefix to find requested level
	function RequestLevel(){
		global $dirPrefixRel,$dirPrefix;
		
		$path = $_SERVER['REQUEST_URI'];
		
		//strip the query string.. in case it contains "/"
		$pos = strpos($path,'?');
		if( $pos > 0 ){
			$path =  substr($path,0,$pos);
		}
		
		//dirPrefix will be decoded
		$path = rawurldecode($path); //%20 ...
		
		if( !empty($dirPrefix) ){
			$pos = strpos($path,$dirPrefix);
			if( $pos !== false ){
				$path = substr($path,$pos+strlen($dirPrefix));
			}
		}
		
		$path = ltrim($path,'/');
		$count = substr_count($path,'/');
		if( $count == 0 ){
			$dirPrefixRel = '.';
		}else{
			$dirPrefixRel = str_repeat('../',$count); 
			$dirPrefixRel = rtrim($dirPrefixRel,'/');//GetDir() arguments always start with /
		}
	}
		
	
	
	/* 
	 * 
	 * Link Functions 
	 *
	 *  
	 */
	function Ampersands($arg){
		
		if( strpos($arg,';') ){
			return $arg;
		}
		return str_replace('&','&amp;',$arg);	
	}
	
	
	/* deprecated: Use common::Link() instead */
	function Link_Admin($href,$label,$query='',$attr=''){
		return common::Link($href,$label,$query,$attr);
	}
	
	function Link($href,$label,$query='',$attr=''){
		
		if( strpos($attr,'title="') === false){
			$attr .= ' title="'.common::Ampersands(strip_tags($label)).'" ';
		}
		
		//$href = urlencode($href);
		$href = common::Ampersands($href);
		$label = common::Ampersands($label);
		$query = common::Ampersands($query);
		
		return '<a href="'.common::GetUrl($href,$query,false).'" '.$attr.'>'.$label.'</a>';
	}
	
	
	function GetUrl($href,$query='',$ampersands=true){
		global $dirPrefix,$config;
		
		if( $ampersands ){
			$query = str_replace('&','&amp;',$query);
			$href = str_replace('&','&amp;',$href);
		}

		if( !empty($query) ){
			$query = '?'.$query;
		}
		
		if( $href == $config['homepath'] ){
			$href = '';
		}
		
		if( !$_SERVER['gp_rewrite'] ){
			return $dirPrefix.'/index.php/'.$href.$query;
		}else{
			return $dirPrefix.'/'.$href.$query;
		}
	}
	
	function AbsoluteLink($href,$label,$query='',$attr=''){
		
		if( strpos($attr,'title="') === false){
			$attr .= ' title="'.htmlspecialchars(strip_tags($label)).'" ';
		}
		
		return '<a href="'.common::AbsoluteUrl($href,$query).'" '.$attr.'>'.common::Ampersands($label).'</a>';
	}
	
	function AbsoluteUrl($href,$query=''){
		
		$href = common::Ampersands($href);
		$query = common::Ampersands($query);
		
		if( isset($_SERVER['HTTP_HOST']) ){
			$server = $_SERVER['HTTP_HOST'];
		}else{
			$server = $_SERVER['SERVER_NAME'];
		}
		$schema = ( isset($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) == 'on' ) ? 'https://' : 'http://';
		
		return $schema.$server.common::GetUrl($href,$query,false);
	}

	
	function escape(&$content){
		return str_replace(array('\\','"',"\n","\r"),array('\\\\','\"','\n','\r'),$content);
	}
	
	
	function GetDir($dir){
		global $dirPrefix;
		return $dirPrefix.str_replace(' ','%20',$dir);
		
		/* Breaks different entry points.. see admin_menu.php
		global $dirPrefixRel;
		return $dirPrefixRel.str_replace(' ','%20',$dir);
		*/
	}
	
	function GetDir_Prefixed($dir){
		global $dirPrefix;
		return $dirPrefix.str_replace(' ','%20',$dir);
	}
	
	
	function GetLabel($title,$amp=true){
		global $gptitles,$langmessage;
		
		if( !isset($gptitles[$title]) ){
			$return = gpFiles::CleanLabel($title);
			
		}else{
			
			$info =& $gptitles[$title];
			if( isset($info['label']) ){
				$return = $info['label'];
				
			}elseif( isset($info['lang_index']) ){
				
				$return = $langmessage[$info['lang_index']];
				
			}else{
				$return = gpFiles::CleanLabel($title);
			}
		}
		
		if( $amp ){
			return str_replace('&','&amp;',$return);
		}else{
			return $return;
		}
	}
	
	/* deprecated */
	function UseFCK($contents,$name='gpcontent'){
		common::UseCK($contents,$name);
	}
	
	/* ckeditor 3.0 
		- Does not have a file browser
		
		configuration options
		- http://docs.cksource.com/ckeditor_api/symbols/CKEDITOR.config.html
	*/
	function UseCK($contents,$name='gpcontent',$options=array()){
		global $config,$page;
		
		
		$options += array('config_file'=> common::GetDir_Prefixed('/include/js/ckeditor_config.js')
							,'browser'=>true
							);
							
							
		echo "\n\n";
		
		echo '<textarea name="'.$name.'" style="width:90%" rows="30" cols="100" class="CKEDITAREA">';
		echo htmlspecialchars($contents);
		echo '</textarea><br/>';
		
		$ck_folder = 'ckeditor_34';	//don't change for distributed code to keep image paths consistent
		
		$page->head .= '<script type="text/javascript" src="'. common::GetDir('/include/thirdparty/'.$ck_folder.'/ckeditor.js') .'"></script>';
		common::PrepAutoComplete(false,true);
		
		ob_start();
		
		echo 'CKEDITOR.replaceAll( function(tarea,config){';
		
		echo 'if( tarea.className.indexOf("CKEDITAREA") == -1 ) return false;';
		
		
		//paths
		if( $options['browser'] ){
			echo 'config.filebrowserBrowseUrl = "'.common::GetDir('/include/admin/admin_browser.html').'";';
			echo 'config.filebrowserImageBrowseUrl = "'.common::GetDir('/include/admin/admin_browser.html?dir=%2Fimage').'";';
			echo 'config.filebrowserFlashBrowseUrl = "'.common::GetDir('/include/admin/admin_browser.html?dir=%2Fflash').'";';
		}
		echo 'config.smiley_path = "'.common::GetDir('/include/thirdparty/'.$ck_folder.'/plugins/smiley/images/').'";';

		//echo 'config.sharedSpaces = {top:"ckeditor_toolbar"};';

		//language
		$langeditor = $config['langeditor'];
		if( $langeditor == 'inherit' ){
			$langeditor = $config['language'];
		}
		echo 'config.language="'.$langeditor.'";';
		
			
		//custom config
		echo 'config.customConfig = "'.$options['config_file'].'";';
		if( isset($options['config_text']) ){
			echo $options['config_text'];
		}
		
		//css
		//$css =  common::GetDir('/themes/'.$page->theme_name.'/'.$page->theme_color.'/style.css');
		//echo 'config.contentsCss = "'.$css.'";';
		
		echo 'return true;';
		echo '});';
		echo "\n\n";
		$page->jQueryCode .= ob_get_clean();
		
	}
	
	function PrepAutoComplete($autocomplete_js=true,$GetUrl=true){
		global $page,$gptitles;
		
		if( $autocomplete_js ){
			$page->head .= '<script type="text/javascript" src="'. common::GetDir('/include/js/autocomplete.js') .'"></script>';
		}
		
		$page->head .= '<script type="text/javascript" src="'. common::GetDir('/include/thirdparty/jquery_ui/jquery-ui-1.8.4.custom.min.js') .'"></script>';
		$page->admin_css .= '<link rel="stylesheet" type="text/css" href="'.common::GetDir('/include/thirdparty/jquery_ui/jquery-ui-1.8.4.custom.css').'" />';
		
		//internal link array
		$page->head_script .= 'var gptitles=[';
		foreach($gptitles as $title => $info){
			if( $GetUrl ){
				$page->head_script .= '["'.addslashes(common::GetLabel($title)).'","'.addslashes(common::GetUrl($title)).'"],';
			}else{
				$page->head_script .= '["'.addslashes(common::GetLabel($title)).'","'.addslashes($title).'"],';
			}
		}
		
		if( class_exists('admin_tools') ){
			$scripts = admin_tools::AdminScripts();
			foreach($scripts as $url => $info){
				if( $GetUrl ){
					$url = common::GetUrl($url);
				}
				$page->head_script .= '["'.addslashes($info['label']).'","'.addslashes($url).'"],';
			}
		}
		
		$page->head_script .= '];';

	}
	
	function ShowingGallery(){
		global $page;
		
		common::AddColorBox();
		$css = gpOutput::GetLastHook('Gallery_Style');
		if( $css === false  ){
			$css = common::GetDir('/include/css/default_gallery.css?v='.$GLOBALS['gpversion']);
		}
		
		$page->head .= "\n".'<link type="text/css" media="screen" rel="stylesheet" href="'.$css.'" />';
	}
	
	function AddColorBox(){
		global $page,$config;
		static $init = false;
		
		if( $init ){
			return;
		}
		$init = true;
		
		$folder = 'colorbox136';
		$folder = 'colorbox139';
		$style = $config['colorbox_style']; //'example1';
		
		$page->admin_js = true;
		
		$page->head .= "\n".'<link type="text/css" media="screen" rel="stylesheet" href="'.common::GetDir('/include/thirdparty/'.$folder.'/'.$style.'/colorbox.css?v='.$GLOBALS['gpversion']).'" />';
		$page->head .= "\n".'<script type="text/javascript" src="'.common::GetDir('/include/thirdparty/'.$folder.'/colorbox/jquery.colorbox.js?v='.$GLOBALS['gpversion']).'"></script>';
	}
	
	function GetConfig(){
		global $config, $rootDir, $gptitles, $gpmenu, $dataDir, $gpLayouts;
		
		//$start_time = microtime();
		
		
		//page information
		require($dataDir.'/data/_site/pages.php');
		$gptitles = $pages['gptitles'];
		$gpmenu = $pages['gpmenu'];
		if( isset($pages['gpLayouts']) ){
			$gpLayouts = $pages['gpLayouts'];
		}else{
			$gpLayouts = array();
		}
		$GLOBALS['fileModTimes']['pages.php'] = $fileModTime;
		
		//1.7b2
		if( !isset($gptitles['Special_Missing']) ){
			$gptitles['Special_Missing']['type'] = 'special';
			$gptitles['Special_Missing']['label'] = 'Missing';
		}
		
		
		//get config
		require($dataDir.'/data/_site/config.php');
		$GLOBALS['fileModTimes']['config.php'] = $fileModTime;
		if($config['language']=='') $config['language']='en';
		if($config['langeditor']=='') $config['langeditor']='en';
		if( !isset($config['maximgarea']) ) $config['maximgarea'] = '691200' ;
		if( !isset($config['linkto']) ) $config['linkto'] = 'Powered by <a href="http://www.gpEasy.com" title="The Fast and Easy CMS">gpEasy CMS</a>';
		if( !isset($config['check_uploads']) ) $config['check_uploads'] = true;
		if( !isset($config['shahash']) ) $config['shahash'] = function_exists('sha1'); //1.6RC3
		if( !isset($config['colorbox_style']) ) $config['colorbox_style'] = 'example1';
		if( !isset($config['menu_levels']) ) $config['menu_levels'] = 3;
		
		//$config['theme_text'] was created in 1.6RC1, decided against in 1.6RC2
		if( !isset($config['customlang']) ){
			$config['customlang'] = array();
		}
		if( isset($config['theme_text']) ){
			foreach($config['theme_text'] as $text){
				$config['customlang'] += $text;
			}
			unset($config['theme_text']);
		}
		//end $config['theme_text'] fix
		
				
		//set homepath
		reset($gptitles);
		reset($gpmenu);
		$config['homepath'] = key($gpmenu);//homepath is simply the first title in $gpmenu
		
		
		$config['dirPrefix'] = $GLOBALS['dirPrefix'];

		//get language file
		common::GetLangFile('main.php');
		
		//upgrade?
		if( version_compare($config['gpversion'],'1.6RC4','<') ){
			require($rootDir.'/include/tool/upgrade.php');
			new gpupgrade();
		}
		if( version_compare($config['gpversion'],'1.6','<') ){
			require($rootDir.'/include/tool/upgrade.php');
			new gpupgrade();
		}
		if( version_compare($config['gpversion'],'1.7a2','<') ){
			require($rootDir.'/include/tool/upgrade.php');
			new gpupgrade();
		}
		
	}
	
	/**
	 * Return the configuration value or default if it's not set
	 *
	 * @since 1.7
	 * 
	 * @param $key The key to the $config array
	 * @param $default The value to return if $config[$key] is not set
	 * @return mixed
	 */
	function ConfigValue($key,$default=false){
		global $config;
		if( !isset($config[$key]) ){
			return $default;
		}
		return $config[$key];
	}
	
	function RandomString($len = 40){
		
		$string = str_repeat('ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuv1234567890',round($len/2));
		
		$session_id = str_shuffle( $string );
		$start = mt_rand(1, (strlen($session_id)-$len));
		$session_id = substr($session_id,$start,$len);
		
		return $session_id;
	}
	
	function GetLangFile($file='main.php',$language=false){
		global $rootDir, $config, $langmessage;
		
		if( $language === false ){
			$language = $config['language'];
		}
		
		
		$fullPath = $rootDir.'/include/languages/'.$language.'/'.$file;
		if( file_exists($fullPath) ){
			include($fullPath);
			return;
		}
		
		//try to get the english file
		$fullPath = $rootDir.'/include/languages/en/'.$file;
		if( file_exists($fullPath) ){
			include($fullPath);
		}
		
	}
	
	function PageType($title){
		global $gptitles;
		
		$type = common::SpecialOrAdmin($title);
		if( $type !== false ){
			return $type;
		}
		
		if( !isset($gptitles[$title]) ){
			return 'page';
		}
		
		$titleInfo = $gptitles[$title];
		if( !isset($titleInfo['type']) ){
			return 'page';
		}
		
		return $titleInfo['type'];
	}
	

	function SpecialOrAdmin($title){
		
		//Admin without the _ because of the primary Admin page at /Admin
		//	will need to change this to Admin_, changing links for /Admin to /Admin_Main as of 1.7b2
		if( strpos($title,'Admin') === 0 ){ 
			return 'admin';
		}
		if( strpos($title,'Special_') === 0 ){
			return 'special';
		}
		return false;
	}

	
	function WhichPage(){
		global $config;
		
		//backwards support, redirect
		if( isset($_GET['r']) ){
			$path = $_GET['r'];
			$path = gpFiles::CleanTitle($path);
			header('Location: '.common::GetUrl($path,false));
		}
		
		$path = common::CleanRequest($_SERVER['REQUEST_URI']);
		
		$pos = strpos($path,'?');
		if( $pos !== false ){
			$path = substr($path,0,$pos);
		}
		
		//$path = trim($path,'/');
		$path = gpFiles::CleanTitle($path);
		
		if( empty($path) ){
			return $config['homepath'];
		}
		
		return $path;
	}
	
	function CleanRequest($path){
		global $dirPrefix;
		
		//use dirPrefix to find requested title
		$path = rawurldecode($path); //%20 ...
		
		if( !empty($dirPrefix) ){
			$pos = strpos($path,$dirPrefix);
			if( $pos !== false ){
				$path = substr($path,$pos+strlen($dirPrefix));
			}
		}
		
		
		//remove /index.php/
		$pos = strpos($path,'/index.php');
		if( $pos === 0 ){
			$path = substr($path,11);
		}
		
		$path = ltrim($path,'/');
		
		return $path;
	}
	
	//used with ob_start();
	/* deprecated */
	function get_clean(){
		return ob_get_clean();
	}

	//only starts session tracking if needed
	function sessions(){
		
		$start = false;
		$cmd = common::GetCommand();
		if( $cmd ){
			$start = true;
		}elseif( isset($_COOKIE['gpEasy']) ){
			$start = true;
		}
		
		if( $start === false ){
			return;
		}
		
		includeFile('tool/sessions.php');
		includeFile('admin/admin_tools.php');
		
		switch( $cmd ){
			case 'logout':
				gpsession::LogOut();
			return;
			case 'login':
				gpsession::LogIn();
			return;
		}
		
		if( isset($_COOKIE['gpEasy']) ){
			gpsession::CheckPosts($_COOKIE['gpEasy']);
			gpsession::start($_COOKIE['gpEasy']);
		}
		
	}

	
	function LoggedIn(){
		global $gpAdmin;
		static $loggedin;
		
		if( isset($loggedin) ){
			return $loggedin;
		}
		
		if( !isset($gpAdmin) ){
			//message('logged in false1 '.showArray($gpAdmin));
			$loggedin = false;
			return false;
		}
		
		$loggedin = true;
		return true;
	}
	
	function IP($ip,$level=2){
		
		$temp = explode('.',$ip);
		
		$i = 0;
		while( $level > $i){
			array_pop($temp);
			$i++;
		}
		
		$checkIP = array_shift($temp); //don't pad with zero's for first part
		foreach($temp as $num){
			$checkIP .= str_pad($num,3,'0',STR_PAD_LEFT); 
		}

		return $checkIP;
	}		
	
	
	//Don't use $_REQUEST here because SetCookieArgs() uses $_GET
	function GetCommand($type='cmd'){
		common::SetCookieArgs();
		
		if( isset($_POST[$type]) ){
			return $_POST[$type];
		}
		
		if( isset($_GET[$type]) ){
			return $_GET[$type];
		}
		return false;
	}
	
	
	//used for receiving arguments from javascript without having to put variables in the $_GET request
	//nice for things that shouldn't be repeated!
	function SetCookieArgs(){
		static $done = false;
		
		if( $done ){
			return;
		}
		
		//get cookie arguments
		if( empty($_COOKIE['cookie_cmd']) ){
			return;
		}
		$test = $_COOKIE['cookie_cmd'];
		if( $test{0} === '?' ){
			$test = substr($test,1);
		}
		parse_str($test,$_GET);
		parse_str($test,$_REQUEST);
		$done = true;
	}	

	
	
	function OrganizeFrequentScripts($page){
		global $gpAdmin;
		
		if( !isset($gpAdmin['freq_scripts']) ){
			$gpAdmin['freq_scripts'] = array();
		}
		if( !isset($gpAdmin['freq_scripts'][$page]) ){
			$gpAdmin['freq_scripts'][$page] = 0;
		}else{
			$gpAdmin['freq_scripts'][$page]++;
			if( $gpAdmin['freq_scripts'][$page] >= 10 ){
				common::CleanFrequentScripts();
			}
		}

		arsort($gpAdmin['freq_scripts']);
	}
	
	function CleanFrequentScripts(){
		global $gpAdmin;
		
		//reduce to length of 5;
		$count = count($gpAdmin['freq_scripts']);
		if( $count > 3 ){
			for($i=0;$i < ($count - 5);$i++){
				array_pop($gpAdmin['freq_scripts']);
			}
		}
		
		//reduce the hit count on each of the top five
		$min_value = end($gpAdmin['freq_scripts']);
		foreach($gpAdmin['freq_scripts'] as $page => $hits){
			$gpAdmin['freq_scripts'][$page] = $hits - $min_value;
		}
	}
	
	//$config['shahash'] won't be set for install!
	function hash($arg){
		global $config;
		
		if( isset($config['shahash']) && !$config['shahash'] ){
			return md5($arg);
		}
		return sha1($arg);
	}
		
}

class gpFiles{

	
	
	//$filetype		false=all,1=directories,'php'='.php' files
	function ReadDir($dir,$filetype='php'){
		$files = array();
		if( !file_exists($dir) ){
			return $files;
		}
		$dh = @opendir($dir);
		if( !$dh ){
			return $files;
		}
		
		while( ($file = readdir($dh)) !== false){
			if( strpos($file,'.') === 0){
				continue;
			}
			
			//get all
			if( $filetype === false ){
				$files[$file] = $file;
				continue;
			}
			
			//get directories
			if( $filetype === 1 ){
				$fullpath = $dir.'/'.$file;
				if( is_dir($fullpath) ){
					$files[$file] = $file;
				}
				continue;
			}
			
			
			$dot = strrpos($file,'.');
			if( $dot === false ){
				continue;
			}
			
			$type = substr($file,$dot+1);
			if( $type == $filetype ){
				$file = substr($file,0,$dot);
			}else{
				continue;
			}
			
			$files[$file] = $file;
		}
		closedir($dh);
		
		return $files;
		
	}
	
	function ReadFolderAndFiles($dir){
		$dh = @opendir($dir);
		if( !$dh ){
			return $files;
		}		
		
		$folders = array();
		$files = array();
		while( ($file = readdir($dh)) !== false){
			if( strpos($file,'.') === 0){
				continue;
			}
			
			$fullPath = $this->currentDir.'/'.$file;
			if( is_dir($fullPath) ){
				$folders[] = $file;
			}else{
				$files[] = $file;
			}
		}
		asort($folders);
		asort($files);
		return array($folders,$files);
	}
	
	//see also CleanTitle() in admin.js
	function CleanTitle($title){
		//$title = str_replace(array('"',"'",'?','&','#'),array(''),$title); // something like "Mission & Principles" should be ok
		
		
		$title = str_replace(array('<','>','/','\\','|'),array(' '),$title);
		$title = trim($title);
		$title = str_replace(' ','_',$title);
		
		$title = str_replace(array('"',"'",'?','#','*',':'),array(''),$title);
		
		
		// Remove control characters
		if( version_compare( phpversion(), '4.2.3',  '>=' ) ){
			return preg_replace( '#[[:cntrl:]]#u', '', $title ) ; // 	[\x00-\x1F\x7F]
		}else{
			return preg_replace( '#[[:cntrl:]]#', '', $title ) ; // 	[\x00-\x1F\x7F]
		}
	}
	
	//similar to CleanTitle() but less restrictive
	function CleanLabel($title){
		$title = str_replace(array('"'),array(''),$title);
		$title = str_replace(array('<','>'),array('_'),$title);
		$title = trim($title);
		
		// Remove control characters
		if( version_compare( phpversion(), '4.2.3',  '>=' ) ){
			return preg_replace( '#[[:cntrl:]]#u', '', $title ) ; // 	[\x00-\x1F\x7F]
		}else{
			return preg_replace( '#[[:cntrl:]]#', '', $title ) ; // 	[\x00-\x1F\x7F]
		}
	}
	
	function CleanArg($path){
		
		//all forward slashes
		$path = str_replace('\\','/',$path);
		
		//remove directory style changes
		$path = str_replace(array('../','./','..'),array('','',''),$path);
		
		//change other characters to underscore
		//$pattern = '#\\.|\\||\\:|\\?|\\*|"|<|>|[[:cntrl:]]#';
		$pattern = '#\\||\\:|\\?|\\*|"|<|>|[[:cntrl:]]#';
		if ( version_compare( phpversion(), '4.2.3',  '>=' ) ) {
			$pattern .= 'u';
		}
		$path = preg_replace( $pattern, '_', $path ) ;
		
		//reduce multiple slashes to single
		$pattern = '#\/+#';
		$path = preg_replace( $pattern, '/', $path ) ;
		
		return $path;
	}
	
	function cleanText(&$text){
		gpFiles::tidyFix($text);
		gpFiles::rmPHP($text);
	}
	
	function rmPHP(&$text){
		$search = array('<?','<?php','?>');
		$replace = array('&lt;?','&lt;?php','?&gt;');
		$text = str_replace($search,$replace,$text);
	}
	
	//
	//		tidy
	//
	function tidyFix(&$text){
		global $config;
		
		if( !function_exists('tidy_parse_string') ){
			return false;
		}
		if( isset($config['HTML_Tidy']) && $config['HTML_Tidy'] == 'off' ){
			return true;
		}
		
	
		$options = array();
		$options['wrap'] = 0;						//keeps tidy from wrapping... want the least amount of space changing as possible.. could get rid of spaces between words with the str_replaces below
		$options['doctype'] = 'omit';				//omit, auto, strict, transitional, user
		$options['drop-empty-paras'] = true;		//drop empty paragraphs
		$options['output-xhtml'] = true;			//need this so that <br> will be <br/> .. etc
		$options['show-body-only'] = true;
		$options['hide-comments'] = false;
		//$options['anchor-as-name'] = true;		//default is true, but not alwasy availabel. When true, adds an id attribute to anchor; when false, removes the name attribute... poorly designed, but we need it to be true
		
		
		//
		//	php4
		//
		if( function_exists('tidy_setopt') ){
			$options['char-encoding'] = 'utf8';
			gpFiles::tidyOptions($options);
			$tidy = tidy_parse_string($text);
			tidy_clean_repair();
			
			if( tidy_get_status() === 2){
				// 2 is magic number for fatal error
				// http://www.php.net/manual/en/function.tidy-get-status.php
				$tidyErrors[] = 'Tidy found serious XHTML errors: <br/>'.nl2br(htmlspecialchars( tidy_get_error_buffer($tidy)));
				return false;
			}
			$text = tidy_get_output();
		
		//	
		//	php5
		//
		}else{
			$tidy = tidy_parse_string($text,$options,'utf8');
			tidy_clean_repair($tidy);
			
			if( tidy_get_status($tidy) === 2){
				// 2 is magic number for fatal error
				// http://www.php.net/manual/en/function.tidy-get-status.php
				$tidyErrors[] = 'Tidy found serious XHTML errors: <br/>'.nl2br(htmlspecialchars( tidy_get_error_buffer($tidy)));
				return false;
			}
			$text = tidy_get_output($tidy);
		}
		return true;
	}
	
	//for php4
	function tidyOptions($options){
		foreach($options as $key => $value){
			tidy_setopt($key,$value);
		}
	}
	
	
	//$title		page title
	//$contents		html contents of page
	//$type			data type
	//$file_data	data specific to the file (see gallery data type)
	//$meta_data	
	function SaveTitle($title,$contents='',$type='page',$file_data=array(),$meta_data=array()){
		global $dataDir;
		
		if( empty($title) ){
			return false;
		}
		$file = $dataDir.'/data/_pages/'.$title.'.php';
		
		//get meta data
		$meta_data = $meta_data + gpFiles::GetTitleMeta($file);
		$meta_data['file_type'] = $type;
		unset($meta_data['keywords']);
		unset($meta_data['description']);
		
		//file count
		if( !isset($meta_data['file_number']) ){
			$meta_data['file_number'] = gpFiles::NewFileNumber();
		}
		
		
		$code = gpFiles::ArrayToPHP('meta_data',$meta_data);
		$code .= "\n\n";
		$code .= gpFiles::ArrayToPHP('file_data',$file_data);
		
		return gpFiles::SaveFile($file,$contents,$code);
	}
	
	function NewFileNumber(){
		global $config;
		
		if( !isset($config['file_count']) ){
			$config['file_count'] = 0;
		}
		$config['file_count']++;
		
		
		admin_tools::SaveConfig();
		
		return $config['file_count'];
		
	}
	
	//get current $meta_data
	function GetTitleMeta($file){
		
		$meta_data = array();
		if( file_exists($file) ){
			ob_start();
			include($file);
			ob_end_clean();
		}
		return $meta_data;
	}
	
	function SaveFile($file,$contents,$code=false){
		global $gpversion;
		
		$codeA[] = '<'.'?'.'php';
		$codeA[] = 'defined(\'is_running\') or die(\'Not an entry point...\');';
		$codeA[] = '$fileVersion = \''.$gpversion.'\';';
		$codeA[] = '$fileModTime = \''.time().'\';';
		if( $code !== false ){
			$codeA[] = $code;
		}
		$codeA[] = '';
		$codeA[] = '?'.'>';
		
		
		$contents = implode("\n",$codeA).$contents;
		return gpFiles::Save($file,$contents);
	}
	
	function Save($file,$contents,$checkDir=true){
		$fp = gpFiles::fopen($file,$checkDir);
		if( !$fp ){
			return false;
		}
		if( !fwrite($fp,$contents) ){
			fclose($fp);
			return false;
		}
		
		fclose($fp);
		return true;
	}
	
	
	function SaveArray($file,$varname,&$array){
		global $gpversion;
		
		$data = gpFiles::ArrayToPHP($varname,$array);
		
		$start = array();
		$start[] = '<'.'?'.'php';
		$start[] = 'defined(\'is_running\') or die(\'Not an entry point...\');';
		$start[] = '$fileVersion = \''.$gpversion.'\';';
		$start[] = '$fileModTime = \''.time().'\';';
		$start[] = '';
		$start[] = '';
		
		$start = implode("\n",$start);
		
		return gpFiles::Save($file,$start.$data);
	}
	
	//boolean, strings, and numbers
	function ArrayToPHP($varname,&$array){
		
		$data = array();
		
		if( count($array) == 0 ){
			$data[] = '$'.$varname.' = array();';
		}
		
		foreach($array as $name => $value){
			
			if( is_int($name) ){
				$name = $varname.'['.$name.']';
			}else{
				$name = $varname.'[\''.addcslashes($name,"'\\").'\']';
			}
			if( is_array($value) ){
				$data[] = gpFiles::ArrayToPHP($name,$value);
				continue;
			}
			$data[] = gpFiles::PHPVariable('$'.$name,$value);
		}
		return implode("\n",$data);
	}
	
	
	//insert into an associative $array
	//	insert before $search_key
	//	Use $offset=0;$length = 1 to replace the $search_key
	function ArrayInsert($search_key,$new_key,$new_value,&$array,$offset=0,$length=0){
		
		$array_keys = array_keys($array);
		$array_values = array_values($array);
		
		$insert_key = array_search($search_key,$array_keys);
		if( ($insert_key === null) || ($insert_key === false) ){
			return false;
		}
		
		array_splice($array_keys,$insert_key+$offset,$length,$new_key);
		array_splice($array_values,$insert_key+$offset,$length,'fill'); //use fill in case $new_value is an array
		$array = array_combine($array_keys, $array_values);
		$array[$new_key] = $new_value;
		
		return true;
	}

	

	function ArrayReplace($search_key,$new_key,$new_value,&$array){
		return gpFiles::ArrayInsert($search_key,$new_key,$new_value,$array,0,1);
	}

	
	function PHPVariable($name,$value){
		
		if( is_int($value) || is_float($value) ){
			return $name.' = '.$value.';';
		}elseif( is_bool($value) ){
			if( $value ){
				return $name.' = true;';
			}else{
				return $name.' = false;';
			}
		}
		return $name.' = \''.addcslashes($value,"'\\").'\';';
	}
	
	
	function fopen($file,$checkDir=true){
		
		if( file_exists($file) ){
			return fopen($file,'wb');
		}
		
		if( $checkDir ){
			$dir = dirname($file);
			if( !file_exists($dir) ){
				gpFiles::CheckDir($dir);
			}
		}
			
		$fp = fopen($file,'wb');
		//chmod($file,0644);
		chmod($file,0666);
		return $fp;
	}
	
	function CheckDir($dir,$index=true){
		global $config,$checkFileIndex;
		
		if( !file_exists($dir) ){
			$parent = dirname($dir);
			gpFiles::CheckDir($parent,$index);
			
			
			//ftp mkdir
			if( isset($config['useftp']) ){
				if( !gpFiles::FTP_CheckDir($dir) ){
					return false;
				}
			}else{
				if( !mkdir($dir,0755) ){
					return false;
				}
			}
			
		}
		
		//make sure there's an index.html file
		if( $index && $checkFileIndex ){
			$indexFile = $dir.'/index.html';
			if( !file_exists($indexFile) ){
				gpFiles::Save($indexFile,'<html></html>',false);
			}
		}
		
		return true;
	}
	
	function RmDir($dir){
		global $config;
		
		//ftp
		if( isset($config['useftp']) ){
			return gpFiles::FTP_RmDir($dir);
		}
		return rmdir($dir);
	}
	
	
	
	/* FTP Function */
	
	function FTP_RmDir($dir){
		$conn_id = gpFiles::FTPConnect();
		$dir = gpFiles::ftpLocation($dir);
		
		return ftp_rmdir($conn_id,$dir);
	}
	
	function FTP_CheckDir($dir){
		$conn_id = gpFiles::FTPConnect();
		$dir = gpFiles::ftpLocation($dir);
		
		if( !ftp_mkdir($conn_id,$dir) ){
			return false;
		}
		return ftp_site($conn_id, 'CHMOD 0777 '. $dir );
	}
	
	function FTPConnect(){
		global $config;
		
		static $conn_id = false;
		
		if( $conn_id ){
			return $conn_id;
		}
		
		
		$conn_id = @ftp_connect($config['ftp_server'],21,6);
		if( !$conn_id ){
			trigger_error('ftp_connect() failed for server : '.$config['ftp_server']);
			return false;
		}
		
		$login_result = @ftp_login($conn_id,$config['ftp_user'],$config['ftp_pass'] );
		if( !$login_result ){
			trigger_error('ftp_login() failed for server : '.$config['ftp_server'].' and user: '.$config['ftp_user']);
			return false;
		}
		register_shutdown_function(array('gpFiles','ftpClose'),$conn_id);
		return $conn_id;
	}
	
	function ftpClose($connection=false){
		if( $connection !== false ){
			@ftp_quit($connection);
		}
	}
	
	function ftpLocation(&$location){
		global $config,$rootDir;
		
		$len = strlen($rootDir);
		$temp = substr($location,$len);
		return $config['ftp_root'].$temp;
	}	
}

class AddonTools{
	
	function SetDataFolder($name){
		global $dataDir;
		global $addonDataFolder,$addonCodeFolder; //deprecated
		global $addonRelativeCode,$addonRelativeData,$addonPathData,$addonPathCode,$addonFolderName;
		
		
		$addonFolderName = $name;
		$addonPathCode = $addonCodeFolder = $dataDir.'/data/_addoncode/'.$name;
		$addonPathData = $addonDataFolder = $dataDir.'/data/_addondata/'.$name;
		$addonRelativeCode = common::GetDir_Prefixed('/data/_addoncode/'.$name);
		$addonRelativeData = common::GetDir_Prefixed('/data/_addondata/'.$name);
	}
	
	function ClearDataFolder(){
		global $addonDataFolder,$addonCodeFolder; //deprecated
		global $addonRelativeCode,$addonRelativeData,$addonPathData,$addonPathCode,$addonFolderName;
		
		
		$addonFolderName = false;
		$addonDataFolder = $addonCodeFolder = false;
		$addonRelativeCode = $addonRelativeData = $addonPathData = $addonPathCode = false;
		
	}
	
}
	
