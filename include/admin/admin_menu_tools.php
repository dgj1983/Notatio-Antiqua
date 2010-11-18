<?php
defined('is_running') or die('Not an entry point...');

class admin_menu_tools{
	
	var $LayoutArray;
	
	var $RenameFormAction = 'Admin_Menu';
	var $FileUrlChanged = false;
	
	
	function SetLayoutArray(){
		global $gpmenu,$config,$gptitles;
		
		$titleThemes = array();
		$customThemes = array(0=>false,1=>false,2=>false);		
		$customThemeLevel = 0;
		foreach($gpmenu as $title => $level){
			
			//reset theme inheritance
			for( $i = $level; $i <= 5; $i++){
				if( isset($customThemes[$i]) ){
					$customThemes[$i] = false;
				}
			}
			
			
			if( !empty($gptitles[$title]['gpLayout']) ){
				$titleThemes[$title] = $gptitles[$title]['gpLayout'];
			}else{
				
				$parent_layout = false;
				$temp_level = $level;
				while( $temp_level >= 0 ){
					if( isset($customThemes[$temp_level]) && ($customThemes[$temp_level] !== false) ){
						$titleThemes[$title] = $parent_layout = $customThemes[$temp_level];
						break;
					}
					$temp_level--;
				}
				
				if( $parent_layout === false ){
					$titleThemes[$title] = $config['gpLayout'];
				}
			}
				
			$customThemes[$level] = $titleThemes[$title];
		}
		
		
		foreach($gptitles as $title => $titleInfo){
			if( isset($titleThemes[$title]) ){
				continue;
			}
			if( !empty($titleInfo['gpLayout']) ){
				$titleThemes[$title] = $titleInfo['gpLayout'];
			}else{
				$titleThemes[$title] = $config['gpLayout'];
			}
		}
			

		$this->LayoutArray = $titleThemes;
	}
	
	

	function RenameForm($title,$more_options = false){
		global $langmessage,$gptitles,$page;
		


		$label = common::GetLabel($title);
		$title_info = $gptitles[$title];
		
		if( empty($_REQUEST['new_title']) ){
			$new_title = $label;
		}else{
			$new_title = htmlspecialchars($_REQUEST['new_title']);
		}
		$new_title = str_replace('_',' ',$new_title);
		
		
		
		//show more options?
		if( htmlspecialchars($title) != gpFiles::CleanTitle($label) ){
			$more_options = true;
		}elseif( isset($title_info['browser_title']) ){
			$more_options = true;
		}elseif( isset($title_info['keywords']) ){
			$more_options = true;
		}
		
		
		ob_start();
		echo '<form action="'.common::GetUrl($this->RenameFormAction).'" method="post">';
		echo '<h2>'.$langmessage['rename/details'].'</h2>';
		

		echo '<input type="hidden" name="title" value="'.htmlspecialchars($title).'" />';
		
		echo '<table class="bordered">';
		echo '<tr>';
			echo '<th colspan="2">';
			echo $langmessage['options'];
			echo '</th>';
			echo '</tr>';
						
		echo '<tr>';
			echo '<td class="formlabel">';
			echo $langmessage['label'];
			echo '</td>';
			echo '<td>';
			echo '<input type="text" class="text title_label" name="new_label" maxlength="60" size="33" value="'.$new_title.'" />';
			echo '</td>';
			echo '</tr>';
			
		if( $more_options ){
			echo '<tr>';
				echo '<td class="formlabel">';
				echo $langmessage['Slug/URL'];
				echo '</td>';
				echo '<td>';
				
				
				//edited slug?
				$attr = '';
				$class = 'text';
				$editable = true;
				if( common::SpecialOrAdmin($title) ){
					$attr = 'disabled="disabled" ';
					$editable = false;
					
				}elseif( $title == gpFiles::CleanTitle($label) ){
					$attr = 'disabled="disabled" ';
					$class .= ' sync_label';
				}
				echo '<input type="text" class="'.$class.'" name="new_title" maxlength="60" size="33" value="'.$title.'" '.$attr.'/>';
				if( $editable ){
					echo ' <div class="admin_note label_synchronize">';
					if( empty( $attr ) ){
						echo '<a href="#">'.$langmessage['sync_with_label'].'</a>';
						echo '<a href="#" class="slug_edit" style="display:none">'.$langmessage['edit'].'</a>';
					}else{
						echo '<a href="#" style="display:none">'.$langmessage['sync_with_label'].'</a>';
						echo '<a href="#" class="slug_edit">'.$langmessage['edit'].'</a>';
					}
					echo '</div>';
					
				}
				
				echo '</td>';
				echo '</tr>';
				
			//browser title defaults to label
			echo '<tr>';
				echo '<td class="formlabel">';
				echo $langmessage['browser_title'];
				echo '</td>';
				echo '<td>';
				$attr = '';
				$class = 'text';
				if( isset($title_info['browser_title']) ){
					$browser_title = $title_info['browser_title'];
				}else{
					$browser_title = $label;
					$attr = 'disabled="disabled" ';
					$class .= ' sync_label';
				}
				echo '<input type="text" class="'.$class.'" size="33" name="browser_title" value="'.$browser_title.'" '.$attr.'/>';
				echo ' <div class="admin_note label_synchronize">';
				if( empty( $attr ) ){
					echo '<a href="#">'.$langmessage['sync_with_label'].'</a>';
					echo '<a href="#" class="slug_edit" style="display:none">'.$langmessage['edit'].'</a>';
				}else{
					echo '<a href="#" style="display:none">'.$langmessage['sync_with_label'].'</a>';
					echo '<a href="#" class="slug_edit">'.$langmessage['edit'].'</a>';
				}
				echo '</div>';
				echo '</td>';
				echo '</tr>';
				
			//meta keywords
			echo '<tr>';
				echo '<td class="formlabel">';
				echo $langmessage['keywords'];
				echo '</td>';
				echo '<td>';
				$keywords = '';
				if( isset($title_info['keywords']) ){
					$keywords = $title_info['keywords'];
				}
				echo '<input type="text" class="text" size="33" maxlength="60" name="keywords" value="'.$keywords.'" />';
				echo '</td>';
				echo '</tr>';
				
			//meta description
			echo '<tr>';
				echo '<td class="formlabel">';
				echo $langmessage['description'];
				echo '</td>';
				echo '<td>';
				$description = '';
				if( isset($title_info['description']) ){
					$description = $title_info['description'];
				}
				
				echo '<input type="text" class="text" size="33" name="description" value="'.$description.'" />';
				echo '</td>';
				echo '</tr>';
				
		}//end more options
		
		
		//redirection
		echo '<tr>';
			echo '<td class="formlabel">';
			echo $langmessage['Redirect'];
			echo '</td>';
			echo '<td>';
			echo '<input type="checkbox" name="add_redirect" value="add" /> ';
			echo $langmessage['Auto Redirect'];
			echo '</td>';
			echo '</tr>';
			
			
			
		echo '</table>';
		
		
		echo '<p>';
			echo '<input type="submit" name="cmd" value="'.$langmessage['save_changes'].'" class="menupost submit"/> ';
			if( !$more_options ){
				echo ' <input type="submit" name="cmd" value="'.$langmessage['more_options'].'" class="gppost submit"/> ';
			}
			
			//echo ' <input type="reset" />';
			echo '</p>';
			
		echo '</form>';
		
		return ob_get_clean();

	}	
	
	function RenameFile($title){
		global $langmessage, $gptitles, $dataDir, $page;
		
		
		//change the title
		$title = $this->RenameFileWorker($title);
		if( $title === false ){
			return;
		}
		
		if( !isset($gptitles[$title]) ){
			message($langmessage['OOPS']);
			return false;
		}
		$title_info = &$gptitles[$title];
		
		//change the label
		$title_info['label'] = gpFiles::CleanLabel($_POST['new_label']);
		if( isset($title_info['lang_index']) ){
			unset($title_info['lang_index']);
		}
		
		
		//change the browser title
		$custom_browser_title = false;
		if( isset($_POST['browser_title']) ){
			$browser_title = $_POST['browser_title'];
			$browser_title = htmlspecialchars($browser_title);
			if( $browser_title != $title_info['label'] ){
				$title_info['browser_title'] = $browser_title;
				$custom_browser_title = true;
			}
		}
		if( !$custom_browser_title ){
			unset($title_info['browser_title']);
		}
		
		
		//keywords
		if( isset($_POST['keywords']) ){
			$title_info['keywords'] = htmlspecialchars($_POST['keywords']);
			if( empty($title_info['keywords']) ){
				unset($title_info['keywords']);
			}
		}
		
		
		//description
		if( isset($_POST['description']) ){
			$title_info['description'] = htmlspecialchars($_POST['description']);
			if( empty($title_info['description']) ){
				unset($title_info['description']);
			}
		}
		
		if( !admin_tools::SavePagesPHP() ){
			message($langmessage['OOPS'].' (R1)');
			return false;
		}
		
		message($langmessage['SAVED']);
		return true;
	}
	
	
	//rename?
	//see CheckPostedNewPage()
	function RenameFileWorker($title){
		global $langmessage, $gptitles,$gpmenu,$dataDir;
		
		if( common::SpecialOrAdmin($title) ){
			return $title;
		}
		
		//use new_label or new_title
		if( isset($_POST['new_title']) ){
			$new_title = gpFiles::CleanTitle($_POST['new_title']);
		}else{
			$new_title = gpFiles::CleanTitle($_POST['new_label']);
		}
		
		//title unchanged
		if( $new_title == $title ){
			return $title;
		}
		
		if( isset($gptitles[$new_title]) ){
			message($langmessage['TITLE_EXISTS']);
			return false;
		}
		if( empty($title) ){
			message($langmessage['TITLE_REQUIRED']);
			return $title;
		}
		
		if( strlen($title) > 60 ){
			message($langmessage['LONG_TITLE']);
			return $title;
		}
		
		
		$old_gpmenu = $gpmenu;
		$old_gptitles = $gptitles;
		
		//menu position
		if( isset($gpmenu[$title]) ){
			
			if( !gpFiles::ArrayInsert($title,$new_title,$gpmenu[$title],$gpmenu) ){
				return $title;
			}
			unset($gpmenu[$title]);
		}
		
		//gptitle
		$gptitles[$new_title] = $gptitles[$title];
		unset($gptitles[$title]);
		
		
		//rename the php file
		$new_file = $dataDir.'/data/_pages/'.$new_title.'.php';
		$old_file = $dataDir.'/data/_pages/'.$title.'.php';
		
		if( !rename($old_file,$new_file) ){
			message($langmessage['OOPS'].' (N3)');
			
			$gpmenu = $old_gpmenu;
			$gptitles = $old_gptitles;
			
			return false;
		}
		
		$this->FileUrlChanged = $new_title;
		
		
		//create a 301 redirect
		if( isset($_POST['add_redirect']) && $_POST['add_redirect'] == 'add' ){
			includeFile('admin/admin_missing.php');
			admin_missing::AddRedirect($title,$new_title);
		}
		
		return $new_title;
	}
	
}
