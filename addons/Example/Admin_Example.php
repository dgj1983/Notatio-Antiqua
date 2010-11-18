<?php
defined('is_running') or die('Not an entry point...');


class Admin_Example{
	function Admin_Example(){ 
		echo '<h2>This is an Admin Only Script</h2>';
		
		echo '<p>';
		
		echo 'This is an example of a gpEasy Addon in the form of a Admin page.';
		
		echo '</p>';
		
		echo '<p>';
		
		echo 'Admin pages are only accessible to users with appropriate permissions on your installation of gpEasy. ';
		
		echo '</p>';
		echo '<p>';
		
		echo 'Admin pages are only accessible to users with appropriate permissions on your installation of gpEasy. ';
		
		echo '</p>';
		
		echo '<p>';
		
		echo common::Link('Special_Example','An Example Link');

		echo '</p>';
				
	}
}
	

