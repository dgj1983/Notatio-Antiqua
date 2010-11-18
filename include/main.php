<?php

define('is_running',true);
require_once('common.php');
common::EntryPoint(0);

/*
 *	Flow Control
 */


if( !empty($GLOBALS['config']['updating_message']) ){
	die($GLOBALS['config']['updating_message']);
}


$title = common::WhichPage();
$type = common::PageType($title);
switch($type){
	
	case 'special':
		include_once($rootDir.'/include/special.php');
		$page = new special_display($title,$type);
	break;
	
	case 'admin':
		include_once($rootDir.'/include/admin/admin_display.php');
		$page = new admin_display($title,$type);
	break;
	
	case 'gallery':
	default:
		$page = new display($title,$type);
	break;
}

common::RunOut();






