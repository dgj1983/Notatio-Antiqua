<?php

class gpftp{
	
	//try to get the ftp_server
	function GetFTPServer(){
			
		if( isset($_SERVER['HTTP_HOST']) ){
			$server = $_SERVER['HTTP_HOST'];
		}elseif( isset($_SERVER['SERVER_NAME']) ){
			$server = $_SERVER['SERVER_NAME'];
		}else{
			return '';
		}
			
		$conn_id = @ftp_connect($server,21,6);
		
		if( $conn_id ){
			
			@ftp_quit($conn_id);
			return $server;
		}
		return '';
	}	
	
	function GetFTPRoot($conn_id,$testDir){
		$ftp_root = false;
		
		//attempt to find the ftp_root			
		$testDir = $testDir.'/';
		$array = ftp_nlist( $conn_id, '.');
		if( !$array ){
			return false;
		}
		$possible = array();
		foreach($array as $file){
			if( $file{0} == '.' ){
				continue;
			}
			
			
			//is the $file within the $testDir.. not the best test..
			$pos = strpos($testDir,'/'.$file.'/');
			if( $pos === false ){
				continue;
			}
			
			$possible[] = substr($testDir,$pos);
		}
		$possible[] = '/'; //test this too
		
		foreach($possible as $file){
			
			if( gpftp::TestFTPDir($conn_id,$file,$testDir) ){
				$ftp_root = $file;
				break;
			}
			
		}
		return $ftp_root;
	}
	
	
	//test the $file by adding a directory and seeing if it exists in relation to the $testDir
	function TestFTPDir($conn_id,$file,$testDir){
		$success = false;
	
		//prevent warnings from showing when we try a directory that doesn't exist
		ob_start();
		if( !@ftp_chdir( $conn_id, $file ) ){
			ob_end_clean();
			return false;
		}
		ob_end_clean();
		
	
		$randomName = 'gpeasy_random_'.rand(100,999);
		if( !@ftp_mkdir($conn_id,$randomName) ){
			return false;
		}
		if( file_exists($testDir.'/'.$randomName) ){
			$success = true;
		}
		ftp_rmdir($conn_id,$randomName);
		
		ftp_chdir( $conn_id, '/');
		
		return $success;
	}	
	
}
