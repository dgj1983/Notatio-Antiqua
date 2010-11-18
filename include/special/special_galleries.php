<?php
defined('is_running') or die('Not an entry point...');


class special_galleries{
	var $galleries = array();
	
	function special_galleries(){
		$this->galleries = special_galleries::GetData();
		
		if( common::LoggedIn() ){
			$cmd = common::GetCommand();
			switch($cmd){
				case 'drag':
					$this->Drag();
				case 'edit':
					$this->EditGalleries();
				return;
			}
		}
		
		$this->GenerateOutput();
	}
	
	function Drag(){
		global $langmessage;
		
		$to =& $_GET['to'];
		$title =& $_GET['title'];
		
		if( !isset($this->galleries[$to]) ){
			message($langmessage['OOPS']);
			return;
		}
		if( !isset($this->galleries[$title]) ){
			message($langmessage['OOPS']);
			return;
		}
		
		$title_info = $this->galleries[$title];
		unset($this->galleries[$title]);
		
		
		if( !gpFiles::ArrayInsert($to,$title,$title_info,$this->galleries) ){
			message($langmessage['OOPS']);
			return;
		}
		
		special_galleries::SaveIndex($this->galleries);
	}
	
	function EditGalleries(){
		global $page,$langmessage;
		
		$page->head .= '<script type="text/javascript" language="javascript" src="'.common::GetDir('/include/js/dragdrop.js').'"></script>';
		$page->head .= '<script type="text/javascript" language="javascript" src="'.common::GetDir('/include/js/browser_prefs.js').'"></script>';
		$page->admin_css .= '<link rel="stylesheet" type="text/css" href="'.common::GetDir('/include/css/edit_gallery.css').'" />';
		$page->admin_css .= '<link rel="stylesheet" type="text/css" href="'.common::GetDir('/include/css/browser.css').'" />';
		
		echo '<div id="admincontent">';
		echo '<div id="admincontent_panel"><a href="#" class="docklink"></a>gp|Easy Administration</div>';
		
		echo '<h2>';
		echo gpOutput::ReturnText('galleries');
		echo '</h2>';
		echo '<p>';
		echo $langmessage['DRAG-N-DROP-DESC2'];
		echo ' &nbsp; ';
		echo common::Link('Special_Galleries',$langmessage['back']);
		echo '</p>';
		
		echo '<div class="'.$GLOBALS['gpAdmin']['browser_display'].' browser_class draggable_droparea">';
		
		foreach($this->galleries as $title => $info ){
			
			if( is_array($info) ){
				$icon = $info['icon'];
			}else{
				$icon = $info;
			}
			
			if( empty($icon) ){
				$thumbPath = common::GetDir('/include/imgs/blank.gif');
			}elseif( strpos($icon,'/thumbnails/') === false ){
				$thumbPath = common::GetDir('/data/_uploaded/image/thumbnails'.$icon.'.jpg');
			}else{
				$thumbPath = common::GetDir('/data/_uploaded'.$icon);
			}
			echo '<div class="draggable_element list_item expand_child">';
			//echo common::Link('Special_Galleries',$title,'cmd=drag&from='.$title,' name="gpajax" style="display:none" ');
			echo common::Link('Special_Galleries',htmlspecialchars($title),'cmd=drag&to=%s&title='.urlencode($title),' name="gpajax" style="display:none" class="dragdroplink" ');

			echo '<div class="gen_links">';
			echo ' <img src="'.$thumbPath.'" height="100" width="100"  alt="" class="icon"/>';
			echo '<div class="caption">';
			echo str_replace('_',' ',$title);
			echo '</div>';
			echo '</div>';
			echo '</div>';

			
		}
		echo '</div>';
		echo '</div>';
		
	}
	
	
	//get gallery index
	function GetData(){
		global $dataDir;
		
		$file = $dataDir.'/data/_site/galleries.php';
		if( !file_exists($file) ){
			return special_galleries::DataFromFiles();
		}
		require($file);
		
		//pages.php is update when pages are deleted/added/renamed/hidden
		if( $GLOBALS['fileModTimes']['pages.php'] > $fileModTime ){
			return special_galleries::DataFromFiles($galleries);
		}
		
		return $galleries;
	}
	
	
	function GenerateOutput(){
		global $langmessage;
		
		common::ShowingGallery();
		
		echo '<h2>';
		echo gpOutput::ReturnText('galleries');
		echo '</h2>';


		$wrap = common::LoggedIn();
		if( $wrap ){
			echo '<div class="editable_area">'; // class="edit_area" added by javascript
			echo common::Link('Special_Galleries',$langmessage['edit'],'cmd=edit',' class="ExtraEditLink" style="display:none" ');
		}
		
		echo '<ul class="gp_gallery gp_galleries">';
		foreach($this->galleries as $title => $info ){
			
			$count = '';
			if( is_array($info) ){
				$icon = $info['icon'];
				if( $info['count'] == 1 ){
					$count = $info['count'].' '.gpOutput::ReturnText('image');
				}elseif( $info['count'] > 1 ){
					$count = $info['count'].' '.gpOutput::ReturnText('images');
				}
			}else{
				$icon = $info;
			}
			
			if( empty($icon) ){
				continue;
			}
			
			
			if( strpos($icon,'/thumbnails/') === false ){
				$thumbPath = common::GetDir('/data/_uploaded/image/thumbnails'.$icon.'.jpg');
			}else{
				$thumbPath = common::GetDir('/data/_uploaded'.$icon);
			}
			
			echo '<li>';
			$label = common::GetLabel($title);
			$title_attr = ' title="'.str_replace('_',' ',$label).'"';
			$label_img = ' <img src="'.$thumbPath.'" height="100" width="100"  alt=""/>';
			echo common::Link($title,$label_img,'',$title_attr);
			echo '<div>';
			echo common::Link($title, str_replace('_',' ',$label),'',$title_attr);
			echo '<p>';
			echo $count;
			echo '</p>';
			echo '</div>';
			echo '</li>';
		}
		echo '</ul>';
		echo '<div style="clear:both"></div>';
		if( $wrap ){
			echo '</div>';
		}
		
		
	}
	
	/*
	
	Updating Functions
	
	*/
	
	function DataFromFiles($galleries=array()){
		global $gptitles;
		
		
		//	remove deleted titles
		foreach($galleries as $title => $info){
			if( !isset($gptitles[$title]) ){
				unset($galleries[$title]);
			}
		}
		
		
		
		
		//
		//	Add New Galleries
		//
		foreach($gptitles as $title => $info){
			
			if( !isset($info['type']) || ($info['type'] != 'gallery') ){
				continue;
			}
			
			if( !isset($galleries[$title]) ){
				
				$info = special_galleries::GetIcon($title);
				$galleries[$title] = $info;
			}
		}
		
		
		special_galleries::SaveIndex($galleries);
		return $galleries;
	}
	
	function SaveIndex($galleries){
		global $dataDir;
		
		includeFile('admin/admin_tools.php');

		$file = $dataDir.'/data/_site/galleries.php';
		gpFiles::SaveArray($file,'galleries',$galleries);
	}
	
	function GetIcon($title){
		global $dataDir;
		
		$array = array('icon'=>false,'count'=>0);
		
		$file = $dataDir.'/data/_pages/'.$title.'.php';
		if( !file_exists($file) ){
			return $array;
		}
		
		ob_start();
		include_once($file);
		ob_get_clean();
		
		if( isset($file_data) && isset($file_data['file_array']) && isset($file_data['file_array'][0]) ){
			return array('icon'=>$file_data['file_array'][0],'count'=>count($file_data['file_array']));
		}
		
		if( isset($file_array) && isset($file_array[0]) ){
			return array('icon'=>$file_array[0],'count'=>count($file_array));
		}
		return $array;
	}	
	
	
	
}
