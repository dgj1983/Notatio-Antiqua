<?php
defined('is_running') or die('Not an entry point...');


/*

	see /var/www/wikyblog/burlington/include/wiki2.php for mb_* string functions
*/
if( function_exists('mb_internal_encoding') ){
	mb_internal_encoding('UTF-8');
}
