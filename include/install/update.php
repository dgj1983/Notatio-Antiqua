<?php

define('is_running',true);

//old entry point
if( defined('gpdebug') ){

	$dir = dirname(dirname(__FILE__));
	require_once($dir.'/common.php');
	common::EntryPoint(0,'update.php');


//new entry poing	
}else{
	define('gpdebug',true);
	require_once('../common.php');
	common::EntryPoint(2,'update.php');
}
	


/* check permissions */

if( !common::LoggedIn() ){
	die('You must be logged in to access this area.');
}

if( !isset($gpAdmin['granted']) || ($gpAdmin['granted'] !== 'all') ){
	die('Sorry, you do not have sufficient privileges to access this area.');
}

includeFile('admin/admin_tools.php');
includeFile('install/update_class.php');
common::GetLangFile('update.php');

$page = new update_class();

includeFile('install/template.php');

