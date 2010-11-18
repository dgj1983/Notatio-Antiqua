<?php

/* 
 * this doesn't work for some installations since any configuration options defined in index.php are lost 
 * We need a config.php file in the root directory to accomplish the separate entry point
 */

define('gpdebug',true);
define('is_running',true);
require_once('common.php');
common::EntryPoint(1,'admin.php');

includeFile('admin/admin_display.php');


$title =& $_GET['areq'];
$page = new admin_display($title);
common::RunOut();


