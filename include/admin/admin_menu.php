<?php
defined('is_running') or die('Not an entry point...');

includeFile('admin/admin_menu_tools.php');


class admin_menu extends admin_menu_tools{
	
	var $curr_levels;
	var $minlevels = 2;
	var $maxlevels = 5;
	var $no_menu = array();
	
	function admin_menu(){
		global $langmessage,$page,$config;
		
		//$this->no_menu['Special_Missing'] = true; //showing it allows users to define a theme
		
		$this->curr_levels = $config['menu_levels'];
		
		$page->head .= '<script type="text/javascript" language="javascript" src="'.common::GetDir('/include/js/dragdrop.js').'"></script>';
		$page->admin_css .= '<link rel="stylesheet" type="text/css" href="'.common::GetDir('/include/css/admin_themes.css').'" />';
		$page->head .= '<script type="text/javascript" language="javascript" src="'.common::GetDir('/include/js/admin_menu.js').'"></script>';
		
		$img = '<img src="'.common::GetDir('/include/imgs/page_add.png').'" height="16" width="16" style="float:left" alt=""/>';
		
		$cmd = common::GetCommand();
		
		switch($cmd){
			
			case 'newtitle':
				$this->NewTitle();
			break;
		
			case 'menu_levels';
				$this->MenuLevels();
			break;
			
			//rename
			case $langmessage['more_options']:
				$this->RenameForm(true);
			return;
			case 'renameform':
				$this->RenameForm(); //will die()
			return;
			
			case $langmessage['save_changes']:
				$this->RenameFile();
			break;

			
			case 'new':
				$this->InsertFileForm(); //will die()
			return;
			
			case 'insert_at':
				$this->InsertAt();
			break;
			
			case 'insert_before':
			case 'insert_after':
				$this->InsertNewFile($cmd);
			break;
			
			case 'drag':
				$this->Drag();
			break;
			case 'dragadd':
				$this->DragAdd();
			break;
			
			case 'hide':
				$this->Hide();
			break;

			
			case 'trash':
				$this->MoveToTrash();
			break;
			
			//layout
			case 'layout':
				$this->SelectLayout();
			return;
			case 'uselayout':
				$this->SetLayout();
			break;
			case 'restorelayout':
				$this->RestoreLayout();
			break;
			
		}
		
		$this->ShowForm($cmd);
		
		if( isset($_REQUEST['gpreq']) && ($_REQUEST['gpreq'] == 'json') && isset($_GET['menus']) ){
			$this->PrepJSON();
		}
	}
	
	function NewTitle(){
		global $langmessage;
		
		$title = $this->GetTitle(false);
		if( !$title ){
			return false;
		}
		
		$replace = '<em>'.$title.'</em>';
		$message = sprintf($langmessage['new_file_instructions'],$replace);
		message($message);
	}
	
	
	function MenuLevels(){
		global $config,$langmessage;
		
		$level =& $_POST['level'];
		if( ($level < $this->minlevels) || ($level > $this->maxlevels) ){
			message($langmessage['OOPS']);
			return;
		}
		
		$config['menu_levels'] = $level;
		if( admin_tools::SaveConfig() ){
			$this->curr_levels = $config['menu_levels'];
		}
	}
	
	//we do the json here because we're replacing more than just the content
	function PrepJson(){
		global $page,$gpOutConf,$GP_MENU_LINKS,$GP_MENU_CLASS;
		
		foreach($_GET['menus'] as $id => $menu){
			
			$info = gpOutput::GetgpOutInfo($menu);

			if( !isset($info['method']) ){
				continue;
			}
			
			$array = array();
			$array[0] = 'replacemenu';
			$array[1] = '#'.$id;
			
			if( !empty($_GET['menuh'][$id]) ){
				$GP_MENU_LINKS = rawurldecode($_GET['menuh'][$id]);
			}
			if( !empty($_GET['menuc'][$id]) ){
				$GP_MENU_CLASS = rawurldecode($_GET['menuc'][$id]);
			}
			
			ob_start();
			call_user_func($info['method'],$info['arg'],$info);
			$array[2] = ob_get_clean();
			
			$page->ajaxReplace[] = $array;

		}
	}
	
	function unhtmlspecialchars( $string ){
		$string = str_replace ( '&amp;', '&', $string );
		$string = str_replace ( '&#039;', '\'', $string );
		$string = str_replace ( '&quot;', '\"', $string );
		$string = str_replace ( '&lt;', '<', $string );
		$string = str_replace ( '&gt;', '>', $string );
		
		return $string;
	}
	

	function SelectLayout(){
		global $gptitles,$gpLayouts,$langmessage;
		
		$title =& $_GET['title'];
		if( !isset($gptitles[$title]) ){
			echo $langmessage['OOPS'];
			return;
		}
		

		$this->SetLayoutArray();
		$curr_layout = $this->LayoutArray[$title];
		$curr_info = $gpLayouts[$curr_layout];
		
		echo '<div class="inline_box">';
		
		echo '<p>';
		echo $langmessage['current_layout'].': ';
		echo '<span class="layout_color_id" style="background-color:'.$curr_info['color'].';" title="'.$curr_info['color'].'"></span> ';
		echo str_replace('_',' ',$curr_info['label']);
		echo '</p>';
		
		echo '<table class="bordered" style="width:100%">';
		
		echo '<tr>';
			echo '<th>';
			
			echo $langmessage['available_layouts'];
			echo '</th>';
			echo '<th>';
			echo $langmessage['theme'];
			echo '</th>';
			echo '</tr>';
			
		foreach($gpLayouts as $layout => $info){
			if( $layout == $curr_layout ){
				continue;
			}
			echo '<tr>';
			echo '<td>';
			echo '<span class="layout_color_id" style="background-color:'.$info['color'].';" title="'.$info['color'].'">';
			echo '</span> ';
			if( $layout == $curr_layout ){
				
				//echo common::Link('Admin_Menu',$info['label'],'cmd=uselayout&title='.urlencode($title).'&layout='.urlencode($layout));
			}else{
				echo common::Link('Admin_Menu',$info['label'],'cmd=uselayout&title='.urlencode($title).'&layout='.urlencode($layout),' name="withmenu"');
			}
			echo '</td>';
			echo '<td>';
			echo $info['theme'];
			echo '</td>';
			echo '</tr>';
			
		}
		echo '</table>';
		
		$affected = $this->GetAffectedFiles($title);
		
		echo '<h3>'.$langmessage['affected_files'].'</h3>';
		echo '<div class="admin_note" style="width:35em">';
		echo str_replace('_',' ',$title);
		$i = 0;
		foreach($affected as $tempTitle => $level){
			$i++;
/*
			if( $i % 3 == 0 ){
				echo '<br/>';
			}
*/
			echo ', '.str_replace('_',' ',$tempTitle);
		}
		echo '</div>';
		
		echo '<p class="admin_note">';
		echo '<b>';
		echo $langmessage['see_also'];
		echo '</b> ';
		echo common::Link('Admin_Theme_Content',$langmessage['layouts']);
		echo '</p>';
		
		echo '</div>';
	}
	
	function GetAffectedFiles($title){
		global $gpmenu;
		$temp = $gpmenu;
		$result = array();
		reset($temp);
		$i = 0;
		do{
			$menuTitle = key($temp);
			$level = current($temp);
			unset($temp[$menuTitle]);
			if( $title === $menuTitle ){
				$this->InheritingLayout($level+1,$temp,$result);
			}
			$i++;
		}while( (count($temp) > 0) );	
		return $result;	
	}
	
	function InheritingLayout($searchLevel,&$menu,&$result){
		global $gptitles;
		
		$children = true;
		do{
			$menuTitle = key($menu);
			$level = current($menu);
			
			if( $level < $searchLevel ){
				return;
			}
			if( $level < $searchLevel ){
				return;
			}
			if( $level > $searchLevel ){
				if( $children ){
					$this->InheritingLayout($level,$menu,$result);
				}else{
					unset($menu[$menuTitle]);
				}
				continue;
			}
			
			unset($menu[$menuTitle]);
			if( !empty($gptitles[$menuTitle]['layout']) ){
				$children = false;
				continue;
			}
			$children = true;
			$result[$menuTitle] = $level;
		}while( count($menu) > 0 );
			
	}
	

	function SetLayout(){
		global $gptitles,$langmessage,$gpLayouts;
		
		$title =& $_GET['title'];
		if( !isset($gptitles[$title]) ){
			message($langmessage['OOPS']);
			return;
		}
		
		$layout = $_GET['layout'];
		if( !isset($gpLayouts[$layout]) ){
			message($langmessage['OOPS']);
			return;
		}
			
		//unset, then reset if needed
		unset($gptitles[$title]['gpLayout']);
		$currentLayout = display::OrConfig($title,'gpLayout');
		if( $currentLayout != $layout ){
			$gptitles[$title]['gpLayout'] = $layout;
		}
		
		if( !admin_tools::SavePagesPHP() ){
			message($langmessage['OOPS'].'(3)');
			return false;
		}
		
		//reset the layout array
		$this->SetLayoutArray();
		message($langmessage['SAVED']);
	}
	
	
	
	function RestoreLayout(){
		global $gptitles,$langmessage;
		
		$title = $_GET['title'];
		if( !isset($gptitles[$title]) ){
			message($langmessage['OOPS']);
			return;
		}
		
		unset($gptitles[$title]['gpLayout']);
		if( !admin_tools::SavePagesPHP() ){
			message($langmessage['OOPS']);
			return false;
		}
		
		//reset the layout array
		$this->SetLayoutArray();

		message($langmessage['SAVED']);
	}	

	
	
	//move files to the trash
	//just hide special pages
	function MoveToTrash(){
		global $gpmenu,$gptitles,$langmessage;
		
		if( count($gpmenu) == 1 ){
			message($langmessage['OOPS'].' (M0)');
			return;
		}
		
		
		$title = $this->GetTitle();
		if( !$title ){
			return false;
		}
		$type = common::PageType($title);
		
		if( $type == 'special' ){
			$this->Hide();
			return;
		}
		
		
		unset($gpmenu[$title]);
		unset($gptitles[$title]);
		if( !admin_tools::SavePagesPHP() ){
			message($langmessage['OOPS'].' (M2)');
			return false;
		}
			
		if( !$this->MoveToTrash2($title) ){
			message($langmessage['OOPS'].' (M3)');
			return false;
		}
		
		message($langmessage['MOVED_TO_TRASH']);
		
		return true;
	}

	
	function MoveToTrash2($title){
		global $dataDir;
		$source_file = $dataDir.'/data/_pages/'.$title.'.php';
		$trash_dir = $dataDir.'/data/_trash';
		$trash_file = $trash_dir.'/'.$title.'.php';
		
		
		if( !file_exists($source_file) ){
			return false;
		}
		
		gpFiles::CheckDir($trash_dir);
		
		if( file_exists($trash_file) ){
			unlink($trash_file);
		}
		
		if( !rename($source_file,$trash_file) ){
			return false;
		}
		return true;
	}		
	

	
	function InsertAt(){
		global $gpmenu,$langmessage;
		
		$title = admin_tools::CheckPostedNewPage();
		if( $title === false ){
			return false;
		}
		
		
		if( !admin_tools::TitlesAdd($title,$_POST['title'],$_POST['file_type'],true) ){
			return false;
		}
		
		$oldmenu = $gpmenu;
		
		$this->PutTitle($title,$_POST['insert_position']);
		
		if( !admin_tools::SavePagesPHP() ){
			message($langmessage['OOPS']);
			$gpmenu = $oldmenu;
		}
		
	}
	
	function InsertNewFile($cmd){
		global $gpmenu,$langmessage;
		
		$title = admin_tools::CheckPostedNewPage();
		if( $title === false ){
			return false;
		}
		
		$insert_position = $_POST['insert_position'];
		if( !isset($gpmenu[$insert_position]) ){
			message($langmessage['OOPS']);
			return false;
		}
		
		//add to $gptitles and create a default file
		if( !admin_tools::TitlesAdd($title,$_POST['title'],$_POST['file_type'],true) ){
			return false;
		}
		
		
		//put the title in the menu
		$insert_level = $gpmenu[$insert_position];
		$oldmenu = $gpmenu;
		
		//insert after or before
		$offset = 0;
		if( $cmd == 'insert_after' ){
			$offset = 1;
		}
		
		if( !gpFiles::ArrayInsert($insert_position,$title,$insert_level,$gpmenu,$offset) ){
			return false;
		}
		
		if( !admin_tools::SavePagesPHP() ){
			message($langmessage['OOPS']);
			$gpmenu = $oldmenu;
			return false;
		}
	}
	


	
	function DragAdd(){
		global $gpmenu,$langmessage;
		
		
		$title = $this->GetTitle();
		if( !$title ){
			return;
		}
		
		//old data in case the save doesn't work
		$oldmenu = $gpmenu;
		
		$this->PutTitle($title,$_GET['to']);
		
		if( !admin_tools::SavePagesPHP() ){
			message($langmessage['OOPS']);
			$gpmenu = $oldmenu;
		}
		
	}
	//for handling data from the menu_position form
	function PutTitle($title,$to){
		global $gpmenu;
		
		
		if( empty($to) || (strpos($to,':') === false) ){
			return;
		}
		
		list($to_key,$new_level) = explode(':',$to);
		if( $to_key === 'hidden' ){
			return;
		}
		
		$titles = array_keys($gpmenu);
		
		//adjust to_key
		if( isset($titles[$to_key]) ){
			$holder_title = $titles[$to_key];
			$holder_level = $gpmenu[$holder_title];
			if( $new_level > $holder_level ){
				$to_key++;
			}
		}

		//place in $titles
		array_splice($titles,$to_key,0,$title);
		
		//rebuild gpmenu
		$newmenu = array();
		foreach($titles as $title_key){
			if( $title_key == $title ){
				$newmenu[$title] = $new_level;
			}else{
				$newmenu[$title_key] = $gpmenu[$title_key];
			}
		}
		
		$gpmenu = $newmenu;	
	}
	
	
	function Drag(){
		global $gpmenu,$langmessage;
		
		$title = $this->GetTitle();
		if( !$title ){
			return;
		}
		reset($gpmenu);
		$titles = array_keys($gpmenu);
		
		//get from
		$from_key = $_GET['from'];
		if( !isset( $titles[$from_key]) || ($titles[$from_key] != $title) ){
			message($langmessage['OOPS']);
			return;
		}
		
		$oldmenu = $gpmenu;
		
		//get info
		list($to_key,$new_level) = explode(':',$_GET['to']);
		
		if( isset($titles[$to_key]) ){
			$holder_title = $titles[$to_key];
			$holder_level = $gpmenu[$holder_title];
			
			//adjust to_key 
			if( ($from_key !== $to_key) ){
				
				if( ($new_level > $holder_level) && ($from_key > $to_key) ){
					$to_key++;
					
				}elseif( ($new_level < $holder_level) && ($from_key < $to_key) ){
					$to_key--;
				}
				
			}
		}
		
		//only move if needed
		if( $from_key !== $to_key ){
			
			//remove at old spot
			array_splice($titles,$from_key,1);
			
			//put in new spot
			array_splice($titles,$to_key,0,$title);
			
			
			//rebuild
			$newmenu = array();
			foreach($titles as $title_key){
				$newmenu[$title_key] = $gpmenu[$title_key];
			}
			$gpmenu = $newmenu;			
			
		}
		
		//set the new level
		$gpmenu[$title] = $new_level;
		
		//message('<table><tr><td>'.showArray($gpmenu).'</td><td>'.showArray($newmenu).'</td></tr></table>');

		if( !admin_tools::SavePagesPHP() ){
			message($langmessage['OOPS']);
			$gpmenu = $oldmenu;
		}
	}
	
	function Hide(){
		global $gpmenu,$langmessage;
		
		$title = $_GET['title'];
		if( !isset($gpmenu[$title]) ){
			message($langmessage['OOPS']);
			return;
		}
		
		$oldmenu = $gpmenu;
		
		unset($gpmenu[$title]);
		
		
		if( !admin_tools::SavePagesPHP() ){
			message($langmessage['OOPS']);
			$gpmenu = $oldmenu;
		}else{
			//message($langmessage['SAVED']);
		}		
		
		
	}
	
	
	function GetTitle($exists = true){
		global $gptitles,$langmessage;
		
		//not using request so that it's compat with creq
		if( isset($_POST['title']) ){
			$title = $_POST['title'];
		}elseif( isset($_GET['title']) ){
			$title = $_GET['title'];
		}else{
			message($langmessage['OOPS'].'(0)');
			return false;
		}
		
		$title = gpFiles::CleanTitle($title);
		
		if( $exists && !isset($gptitles[$title]) ){
			message($langmessage['OOPS'].'(1)');
			return false;
		}
		return $title;
	}
	
	
	
	function ShowForm($cmd){
		global $gpmenu, $langmessage, $page;
		
		
		//css widths
		$elementWidth = 130;
		$levelWidth = 200;
		$hiddenWidth = 500;
		$lastWidth = 153;
		
		switch($this->curr_levels){
			case 2:
				$elementWidth = 140;
				$levelWidth = 220;
				$hiddenWidth = 384;
				$lastWidth = 180;
			break;
			case 4:
				$elementWidth = 120;
				$levelWidth = 190;
				$hiddenWidth = 606;
				$lastWidth = 138;
			break;
			case 5:
				$elementWidth = 96;
				$levelWidth = 180;
				$hiddenWidth = 606; //not really needed unless someone increases the menu limit to 6 or more
				$lastWidth = 113;
			break;
		}
		
		//$hiddenWidth = $this->curr_levels * $lastWidth;
		//message($hiddenWidth);
		
		$page->head .= '<style type="text/css">';
		$page->head .= ' #menuconfig .level{width:'.$levelWidth.'px;}';
		$page->head .= ' #menuconfig .label{width:'.($levelWidth-20).'px;}';
		$page->head .= ' #menuconfig .hidden_element{width:'.$elementWidth.'px;}';
		$page->head .= ' #menuconfig .hidden_level{width:'.$hiddenWidth.'px;}';
		$page->head .= ' #menuconfig .last{width:'.$lastWidth.'px;}';
		$page->head .= '</style>';
		
		
		//level preferences
		echo '<div style="float:right">';
		echo '<form method="post" action="">';
		echo $langmessage['Menu Levels'].': ';
		echo '<select name="level" onchange="$(this.form).submit();">'; //use $(..) to trigger to trigger the verify() code in main.js
		for($i=$this->minlevels;$i<=$this->maxlevels;$i++){
			if( $this->curr_levels == $i ){
				echo '<option value="'.$i.'" selected="selected"> &nbsp; '.$i.'&nbsp;</option>';
			}else{
				echo '<option value="'.$i.'"> &nbsp; '.$i.'&nbsp; </option>';
			}
		}
		echo '</select>';
		echo '<input type="hidden" name="cmd" value="menu_levels"/>';
		//echo ' <input type="submit" name="" value="'.$langmessage['update'].'" />';
		echo '</form>';
		echo '</div>';
		
		echo '<h2>'.$langmessage['file_manager'].'</h2>';
		
		echo '<div id="menuconfig">';
		
		echo '<ul class="draggable_droparea">';
		
		$this->SetLayoutArray();
		
		$i = 0;
		$prevlevel = 0;
		$currentTheme = false;
		$hidden_level_indicated = 0;
		foreach($gpmenu as $title => $level){
			
			//title not visible because of menu_level settings
			if( $level >= $this->curr_levels ){
				$hidden_level_indicated++;
				$i++;
				continue;
			}
			if( $hidden_level_indicated > 0 ){
				echo '<li class="hidden_level draggable_nodrop" title="'.$hidden_level_indicated.' hidden files">';
				echo ' » &nbsp; ';
				echo '</li>';
				$hidden_level_indicated = 0;
			}
			
			
			for( $j = 0; $j < $this->curr_levels; $j++ ){
				$this->ShowLevel($level == $j,$title,$i,$j);
			}
			
			$i++;
			$prevlevel = $level;
		}
		
		//show remaining hidden titles
		if( $hidden_level_indicated > 0 ){
			echo '<li class="hidden_level draggable_nodrop" title="'.$hidden_level_indicated.' hidden files">';
			echo ' » &nbsp; ';
			echo '</li>';
		}
		
		for( $j = 0; $j < $this->curr_levels; $j++ ){
			$this->ShowLevel(false,false,$i,$j);
		}
		

		echo '<li style="clear:both" class="draggable_nodrop"></li>';
		echo '</ul>';
		
		
		
		$this->ShowHidden();
		echo '<div style="clear:both;"></div>';
		
		echo '</div>';
		
	}
	
	function InsertFileForm(){
		global $langmessage;
		
		
		$cmd =& $_REQUEST['relation'];
		$position =& $_REQUEST['insert_position'];
		$title = '';
		//$title = $_REQUEST['title']; //some users find it confusing to see the same title
		//$title = gpFiles::CleanTitle($title);
		//$title = str_replace('_',' ',$title);
		
		echo '<div class="inline_box">';
		echo '<form action="'.common::GetUrl('Admin_Menu').'" method="post">';
		echo '<h2>'.$langmessage['new_file'].'</h2>';
		echo '<table>';
		
		echo '<tr>';
			echo '<td class="formlabel">'.$langmessage['file_name'].'</td>';
			echo '<td>';
			echo '<input type="text" class="text" name="title" maxlength="60" value="'.$title.'" />';
			echo '</td>';
			echo '</tr>';
			
			
		echo '<tr>';
			echo '<td class="formlabel">'.$langmessage['file_type'].'</td>';
			echo '<td>';
			echo '<label>';
			echo '<input type="radio" name="file_type" value="page" checked="checked" />';
			echo 'Page';
			echo '</label>';
			echo '<label>';
			echo '<input type="radio" name="file_type" value="gallery" />';
			echo 'Gallery';
			echo '</label>';
			echo '</td>';
			echo '</tr>';
					
		echo '<tr>';
			echo '<td></td>';
			echo '<td>';
			echo '<input type="hidden" name="cmd" value="'.htmlspecialchars($cmd).'" />';
			echo '<input type="hidden" name="insert_position" value="'.htmlspecialchars($position).'" />';
			echo '<input type="submit" name="aaa" value="'.$langmessage['continue'].'" class="menupost submit"/> ';
			//echo '<input type="submit" name="aaa" value="'.$langmessage['continue'].'" class="submit"/> ';
			echo '</td>';
			echo '</tr>';
		echo '</table>';
		echo '</form>';
		echo '</div>';
	}
		

	function ShowLevel($display,$title,$position,$level){
		global $langmessage,$gptitles,$gpLayouts;
		
		//place holders
		$class = 'level simple_top expand_child';

		if( $level == 0 ){
			$class .= ' clear ';
		}
		if( $title === false ){
			$title = '';
			$class .= ' last';
		}
		
		if( $level >= ($this->curr_levels-1) ){
			$expand_class = 'expand_left';
		}else{
			$expand_class = 'expand_right';
		}
		
		
		
		if( $display === false ){
			$class .= ' hidden_element';
			echo '<li class="'.$class.'">';
				echo common::Link('Admin_Menu',$position.':'.$level,'',' style="display:none" class="dragdroplink"');
				$this->NewFileLink($position.':'.$level);
			echo '</li>';
			return;
		}
		
		$layout = $this->LayoutArray[$title];
		$layout_info = $gpLayouts[$layout];
		
		
			$isSpecialLink = common::SpecialOrAdmin($title);
			$class .= ' draggable_element ';
			echo '<li class="'.$class.'">';
			
				if( $position !== false ){
					echo common::Link('Admin_Menu',$position.':'.$level,'title='.urlencode($title).'&cmd=drag&from='.$position.'&to=%s',' name="withmenu" style="display:none" class="dragdroplink" ');
				}else{
					echo common::Link('Admin_Menu','','title='.urlencode($title).'&cmd=dragadd&to=%s',' name="withmenu" style="display:none" class="dragdroplink" '); 
				}
				

					//hidden options
					echo '<ul>';
					
						//view file
						echo '<li>';
						$label = '<img src="'.common::GetDir('/include/imgs/page_white_text.png').'" height="16" width="16"  alt=""/>';
						echo common::Link($title,$label.$langmessage['view_file']);
						echo '</li>';
						
						//rename
						$label = '<img src="'.common::GetDir('/include/imgs/page_edit.png').'" alt="" height="16" width="16" />';
						echo '<li>';
						echo common::Link('Admin_Menu',$label.$langmessage['rename/details'],'cmd=renameform&title='.urlencode($title),' title="" name="gpajax" ');
						echo '</li>';
							
						
						//insert above/below...
						if( $position !== false ){
							echo '<li class="expand_child '.$expand_class.'">';
									
								echo '<a href="javascript:void(0)">';
								echo '<img src="'.common::GetDir('/include/imgs/page_add.png').'" height="16" width="16"  alt=""/>';
								echo $langmessage['new_file'];
								echo '</a>';
							
								//expand right area
								echo '<ul>';
								
									//insert before
									echo '<li>';
									$label = '<img src="'.common::GetDir('/include/imgs/insert_before.png').'" alt="" height="15" width="16" /> ';
									$query = 'cmd=new&relation=insert_before&insert_position='.urlencode($title);
									if( isset($_REQUEST['title']) ){
										$query .= '&title='.urlencode(gpFiles::CleanTitle($_REQUEST['title']));
									}
									echo common::Link('Admin_Menu',$label.$langmessage['insert_before'],$query,' title="'.$langmessage['insert_before'].'" name="ajax_box" ');
									echo '</li>';
									
	
									//insert after
									echo '<li>';
									$label = '<img src="'.common::GetDir('/include/imgs/insert_after.png').'" alt="" height="16" width="16" /> ';
									$query = 'cmd=new&relation=insert_after&insert_position='.urlencode($title);
									if( isset($_REQUEST['title']) ){
										$query .= '&title='.urlencode(gpFiles::CleanTitle($_REQUEST['title']));
									}
									echo common::Link('Admin_Menu',$label.$langmessage['insert_after'],$query,' title="'.$langmessage['insert_after'].'" name="ajax_box" ');
									echo '</li>';
								echo '</ul>';
							echo '</li>';
						}

						//options
						$options = '';
							
							//edit file
							if( $isSpecialLink === false ){
								$options .= '<li>';
								$label = '<img src="'.common::GetDir('/include/imgs/page_edit.png').'" height="16" width="16"  alt=""/>';
								$options .= common::Link($title,$label.$langmessage['edit_file'],'cmd=edit');
								$options .= '</li>';
							}
					
							if( $position !== false ){
								//hide
								$label = '<img src="'.common::GetDir('/include/imgs/cut_list.png').'" alt="" height="16" width="16" />';
								$options .= '<li>';
								$options .= common::Link('Admin_Menu',$label.$langmessage['hide'],'cmd=hide&title='.urlencode($title),' title="'.$langmessage['hide'].'" name="withmenu" ');
								$options .= '</li>';
							}
							
							
								
							//trash
							if( $isSpecialLink === false ){
								$label = '<img src="'.common::GetDir('/include/imgs/bin.png').'" alt="" height="16" width="16" />';
								$options .= '<li>';
								$options .= common::Link('Admin_Menu',$label.$langmessage['delete'],'cmd=trash&title='.urlencode($title),' title="'.$langmessage['delete'].'" name="withmenu" ');
								$options .= '</li>';
							}
								
								
							if( !empty($options) ){
								echo '<li class="expand_child '.$expand_class.'">';
									
									echo '<a href="javascript:void(0)">';
									echo '<img src="'.common::GetDir('/include/imgs/page_white_gear.png').'" height="16" width="16"  alt=""/>';
									echo $langmessage['options'];
									echo '</a>';
									echo '<ul>';
									echo $options;
									echo '</ul>';
								echo '</li>';
							}
				
						
						//layout
						
						if( !empty($gptitles[$title]['gpLayout']) ){
						
							echo '<li class="expand_child '.$expand_class.'">';
							$label = '<img src="'.common::GetDir('/include/imgs/blank.gif').'" style="background-color:'.$layout_info['color'].';" height="16" width="16" class="layout_icon"  alt=""/>';
							//$label .= $langmessage['layout'].': ';
							$label .= $layout_info['label'];
							echo common::Link('Admin_Menu',$label,'cmd=layout&title='.urlencode($title),' title="'.$langmessage['layout'].'" name="ajax_box"');
								echo '<ul>';
									echo '<li>';
									$label = '<img src="'.common::GetDir('/include/imgs/arrow_undo.png').'" alt="" height="16" width="16" />';
									echo common::Link('Admin_Menu',$label.$langmessage['restore'],'cmd=restorelayout&title='.urlencode($title),' title="'.$langmessage['restore'].'" name="gpajax"');
									echo '</li>';
								echo '</ul>';
							echo '</li>';
							
						}else{
							echo '<li>';
							$label = '<img src="'.common::GetDir('/include/imgs/blank.gif').'" style="background-color:'.$layout_info['color'].';" height="16" width="16" class="layout_icon"  alt=""/>';
							$label .= $layout_info['label'];
							echo common::Link('Admin_Menu',$label,'cmd=layout&title='.urlencode($title),' title="'.$langmessage['layout'].'" name="ajax_box"');
							echo '</li>';
						}
						
					//end hidden options
					echo '</ul>';
					

				//options
				echo '<span class="options">';
				echo '<img src="'.common::GetDir('/include/imgs/blank.gif').'" alt="" height="12" width="13" class="layout" style="background-color:'.$layout_info['color'].';"/>';
				echo '</span>';
					
				//link
				echo '<span class="label">';
				echo common::GetLabel($title);
				echo '</span>';
				
							
			echo '</li>';		
	}
	
	function GetAvailable(){
		global $gptitles, $gpmenu,$config;
		
		$intitles = array_keys($gptitles);
		$inmenu = array_keys($gpmenu);
		$avail = array_diff($intitles,$inmenu);
		foreach($avail as $key => $link){
			$linkInfo = $gptitles[$link];
			
			//don't allow admin
			if( isset($linkInfo['type']) && $linkInfo['type'] == 'admin' ){
				unset($avail[$key]);
			}
		}
		return $avail;
	}
	
	function ShowHidden(){
		global $langmessage;
		
		$avail = $this->GetAvailable();
		
		$titles = array();
		$special = array();
		foreach($avail as $title){
			$type = common::SpecialOrAdmin($title);
			if( $type == false ){
				$titles[] = $title;
			}else{
				$special[] = $title;
			}
		}
		
		
		echo '<h2 style="margin-top:2em;" class="clear">'.$langmessage['hidden_pages'].'</h2>';
		echo '<table cellpadding="7">';
		
/*
		echo '<tr>';
			echo '<td>';
			echo '<b>';
			echo 'Static Files';
			echo '</b>';
			echo '</td>';
			echo '<td>';
			echo '<b>';
			echo 'Dynamic Files';
			echo '</b>';
			echo '</td>';
			echo '</tr>';
*/
		
		echo '<tr>';
		echo '<td>';
			echo '<ul>';
			$this->ShowLinkArray($titles);
			
				//add hidden file
				echo '<li class="level simple_top expand_child clear">';
				$this->NewFileLink('hidden');
				echo '</li>';
				
				//space
				echo '<li style="clear:both" class="draggable_nodrop"></li>';
				
			echo '</ul>';
		echo '</td>';
		echo '<td>';
		
			//echo '<h2 style="margin-top:2em;" class="clear">'.$langmessage['special_pages'].'</h2>';
			echo '<ul>';
			$this->ShowLinkArray($special);
			echo '</ul>';
		echo '</td>';
		echo '</tr>';
		echo '</table>';
		
	}
	
	function NewFileLink($position){
		global $langmessage;
		echo '<div class="hidden_options">';
			//insert at
			$label = '<img src="'.common::GetDir('/include/imgs/page_add.png').'" alt="" height="15" width="16" /> ';
			
			$query = 'cmd=new&relation=insert_at&insert_position='.urlencode($position);
			
			/* Can cause confusion. The title field will contain the name of a recent file that was dragged to another position, including Special pages
			if( isset($_REQUEST['title']) ){
				$query .= '&title='.urlencode(gpFiles::CleanTitle($_REQUEST['title']));
			}
			*/
			
			echo common::Link('Admin_Menu',$label.$langmessage['new_file'],$query,' title="'.$langmessage['new_file'].'" name="ajax_box" ');
		echo '</div>';
	}
	
	
	function ShowLinkArray($array){
		global $langmessage;
		
		foreach($array as $title){
			if( isset($this->no_menu[$title]) ){
				continue;
			}
			
			$this->ShowLevel(true,$title,false,false);
		}	
	}
	
	function RenameForm($more_options = false ){
		global $langmessage,$gptitles,$page;
		
		//don't send #gpx_content
		$page->ajaxReplace = array();
		
		//call renameprep function
		$array = array();
		$array[0] = 'renameprep';
		$array[1] = '';
		$array[2] = '';
		$page->ajaxReplace[] = $array;

		
		//prepare variables
		$title =& $_REQUEST['title'];
		
		if( !isset($gptitles[$title]) ){
			echo $langmessage['OOPS'];
			return;
		}
		
		$colorbox = '<div class="inline_box">';
		$colorbox .= parent::RenameForm($title,$more_options);
		$colorbox .= '</div>';

		
		$array = array();
		$array[0] = 'colorbox';
		$array[1] = '';
		$array[2] = $colorbox;
		$page->ajaxReplace[] = $array;
	
	}
	
	function RenameFile(){
		global $langmessage, $gptitles, $dataDir, $page;
		
		//prepare variables
		$title =& $_REQUEST['title'];
		if( !isset($gptitles[$title]) ){
			message($langmessage['OOPS'].' (R0)');
			return false;
		}

		parent::RenameFile($title);
	}
	
}

