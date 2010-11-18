<?php
defined('is_running') or die('Not an entry point...');


class Special_Example{
	function Special_Example(){ 
		echo '<h2>This is an Special Script</h2>';
		
		echo '<p>';
		
		echo 'This is an example of a gpEasy Addon in the form of a Special page.';
		
		echo '</p>';
		
		
		echo '<p>';
		
		echo 'Special pages can be used to add more than just content to a gpEasy installation. ';
		
		echo '</p>';
		
		echo '<p>';
		
		echo common::Link('Admin_Example','An Example Link');

		echo '</p>';
	}
}
	

