<?php
defined('is_running') or die('Not an entry point...');

/*

requires php 4.0.2+ for imagetypes()

*/


class thumbnail{
	
	/* memory usage tests
	
	
	 844792 at beginning
	1093804 before getSrcImg()
	1094616
	
	
	 
	*/
	 
	
	
	
	// 600x800 pixels = 480,000 sq/pixels
	// 660x880
	// 720x960 = 691,200
	// 780x1040 = 811,200
	// 1280x800 = 1,024,000
	function maxArea($source_path,$dest_path,$max_area=1024000){
		
		$src_img = thumbnail::getSrcImg($source_path,$dest_path); //memory usage before and after this call are small 1093804 vs 1094616
		if( !$src_img ){
			return false;
		}
		
		//Original Size
		$old_x = imagesx($src_img);
		$old_y = imagesy($src_img);
		$old_area = ($old_x * $old_y);
		
		//don't enlarge check 1
		if( $old_area < $max_area ){
			return move_uploaded_file($source_path,$dest_path);
		}
		
		//Calculate the new size
		$inv_ratio = $old_y / $old_x;
		
		$new_y = sqrt($max_area * $inv_ratio);
		$new_y = round($new_y);
		$new_x = round($max_area / $new_y);
		
		//message('old_X: '.$old_x.' by old_Y: '.$old_y.' = '.($old_x * $old_y));
		//message('New_X: '.$new_x.' by New_Y: '.$new_y.' = '.($new_x * $new_y));
		
		//don't enlarge check 2
		$new_area = ($new_y * $new_x);
		if( $new_area > $old_area ){
			return move_uploaded_file($source_path,$dest_path);
		}
		
		return thumbnail::createImg($src_img, $dest_path, 0, 0, 0, 0, $new_x, $new_y, $old_x, $old_y);
		
	}
	
	
	/* static */
	function createSquare($source_path,$dest_path,$size=50,$img_type=false){
		$new_w = $new_h = $size;
		
		$src_img = thumbnail::getSrcImg($source_path,$img_type);
		if( !$src_img ){
			return false;
		}
		
		//Size
		$old_x = imagesx($src_img);
		$old_y = imagesy($src_img);
		
		
		//
		if( $old_x > $old_y ){
			$off_w = ($old_x - $old_y) / 2;
			$off_h = 0;
			$old_x = $old_y;
		}elseif( $old_y > $old_x ){
			$off_w = 0;
			$off_h = ($old_y - $old_x) / 2;
			$old_y = $old_x;
		}else{
			$off_w = 0;
			$off_h = 0;
		}
		
		//don't make the thumbnail larger
		if( ($old_x < $size) && ($old_y < $size ) ){
			$new_w = $new_h = max($old_x,$old_y);
		}
		
		return thumbnail::createImg($src_img, $dest_path, 0, 0, $off_w, $off_h, $new_w, $new_h, $old_x, $old_y);
	}
	
	//get file type
	/* static */
	function getType($path){
		$nameParts = explode('.',$path);
		$type = array_pop($nameParts);
		return strtolower($type);
	}
	
	/* static */
	function AdjustMemoryLimit(){
		//Generally speaking, memory_limit should be larger than post_max_size http://php.net/manual/en/ini.core.php
		
		
		//get memory limit in bytes
		$limit = @ini_get('memory_limit') or '8M';
		$limit = thumbnail::getByteValue($limit);
		
		
		//get memory usage or use a default value
		if( function_exists('memory_get_usage') ){
			$memoryUsed = memory_get_usage();
		}else{
			$memoryUsed = 3*1048576; //sizable buffer 3MB
		}
		
		//since imageHeight and imageWidth aren't always available
		//use post_max_size to figure maximum memory limit
		$max_post = @ini_get('post_max_size') or '8M'; //defaults to 8M
		$max_post = thumbnail::getByteValue($max_post);
		
		
/*
		message('max_post: '.$max_post);
		message('used: '.$memoryUsed);
		message('needed: '.($max_post + $memoryUsed));
		message('limit: '.$limit);
*/
		
		$needed = $max_post + $memoryUsed;
		if( $limit < $needed ){
			@ini_set( 'memory_limit', $needed);
		}
	}
	
	
	function getByteValue($value){

		if( is_numeric($value) ){
			return (int)$value;
		}
		$value = strtolower($value);
		
		$lastChar = $value{strlen($value)-1};
		$num = (int)substr($value,0,-1);
		
		switch($lastChar){
			
			case 'g':
				$num *= 1024;
			case 'm':
				$num *= 1024;
			case 'k':
				$num *= 1024;
			break;
		}
		return $num;
	}	
	
	
	/* static */
	function getSrcImg($source_path,$type_file=false){
		if( !function_exists('imagetypes') ){
			return false;
		}
		
		thumbnail::AdjustMemoryLimit();
		
		
		if( $type_file !== false ){
			$img_type = thumbnail::getType($type_file);
		}else{
			$img_type = thumbnail::getType($source_path);
		}
		
		$supported_types = imagetypes();
		
		
		//start
		switch($img_type){
			case 'jpg':
			case 'jpeg':
				if( $supported_types & IMG_JPG ){
					return imagecreatefromjpeg($source_path);
				}
			break;
			case 'gif':
				return imagecreatefromgif($source_path);
			break;
			case 'png':
				if( $supported_types & IMG_PNG) {
					return imagecreatefrompng($source_path);
				}
			break;
			case 'bmp';
				if( $supported_types & IMG_WBMP) {
					return imagecreatefromwbmp($source_path);
				}
			break;
			default:
				//message('not supported for thumbnail: '.$img_type);
			return false;
		}
		return false;
	}
	
	function createImg($src_img, $dest_path, $dst_x, $dst_y, $off_w, $off_h, $new_w, $new_h, $old_x, $old_y){
		
		
		
		$img_type = thumbnail::getType($dest_path);
		$dst_img = imagecreatetruecolor($new_w,$new_h);
		if( !$dst_img ){
			trigger_error('dst_img not created');
			return false;
		}
		
		
		// allow gif & png to have transparent background
		if( function_exists('imagesavealpha') ){
			if( ($img_type == 'gif') || ($img_type == 'png') ){
				imagealphablending($dst_img, false);
				imagesavealpha($dst_img,true); //php 4.3.2+
				$transparent = imagecolorallocatealpha($dst_img, 255, 255, 255, 127);
				imagefilledrectangle($dst_img, 0, 0, $dst_x, $dst_y, $transparent);
			}
		}
		
		
		if( !imagecopyresampled($dst_img, $src_img, $dst_x, $dst_y, $off_w, $off_h, $new_w, $new_h, $old_x, $old_y) ){
			trigger_error('copyresample failed');
			imagedestroy($dst_img);
			imagedestroy($src_img);
			return false;
		}
		
		//will already have checked for support via the getSrcImg function
		switch($img_type){
			case 'jpeg':
			case 'jpg':
				imagejpeg($dst_img,$dest_path,90);
			break;
			case 'gif':
				imagegif($dst_img,$dest_path);
			break;
			case 'png':
				imagepng($dst_img,$dest_path);
			break;
			case 'bmp':
				imagewbmp($dst_img,$dest_path);
			break;
		}
		
		
		imagedestroy($dst_img);
		imagedestroy($src_img);
		return true;
	}		
	
}
