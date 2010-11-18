<?php
defined('is_running') or die('Not an entry point...');


class admin_tools{
	
	
	function AdminScripts(){
		global $langmessage,$config;
		$scripts = array();

		$scripts['Admin_Menu']['script'] = '/include/admin/admin_menu.php';
		$scripts['Admin_Menu']['class'] = 'admin_menu';
		$scripts['Admin_Menu']['label'] = $langmessage['file_manager'];
		

		$scripts['Admin_Uploaded']['script'] = '/include/admin/admin_uploaded.php';
		$scripts['Admin_Uploaded']['class'] = 'admin_uploaded';
		$scripts['Admin_Uploaded']['label'] = $langmessage['uploaded_files'];
		
		
		$scripts['Admin_Theme_Content']['script'] = '/include/admin/admin_theme_content.php';
		$scripts['Admin_Theme_Content']['class'] = 'admin_theme_content';
		$scripts['Admin_Theme_Content']['label'] = $langmessage['layouts'];
		
		
		$scripts['Admin_Extra']['script'] = '/include/admin/admin_extra.php';
		$scripts['Admin_Extra']['class'] = 'admin_extra';
		$scripts['Admin_Extra']['label'] = $langmessage['theme_content'];
		
		
		$scripts['Admin_Users']['script'] = '/include/admin/admin_users.php';
		$scripts['Admin_Users']['class'] = 'admin_users';
		$scripts['Admin_Users']['label'] = $langmessage['user_permissions'];
		
		
		$scripts['Admin_Configuration']['script'] = '/include/admin/admin_configuration.php';
		$scripts['Admin_Configuration']['class'] = 'admin_configuration';
		$scripts['Admin_Configuration']['label'] = $langmessage['configuration'];
		
		
		$scripts['Admin_Permalinks']['script'] = '/include/admin/admin_permalinks.php';
		$scripts['Admin_Permalinks']['class'] = 'admin_permalinks';
		$scripts['Admin_Permalinks']['label'] = $langmessage['permalinks'];
		
		$scripts['Admin_Missing']['script'] = '/include/admin/admin_missing.php';
		$scripts['Admin_Missing']['class'] = 'admin_missing';
		$scripts['Admin_Missing']['label'] = $langmessage['Link Errors'];

		
		$scripts['Admin_Trash']['script'] = '/include/admin/admin_trash.php';
		$scripts['Admin_Trash']['class'] = 'admin_trash';
		$scripts['Admin_Trash']['label'] = $langmessage['trash'];
		
		
		if( isset($config['admin_links']) && is_array($config['admin_links']) ){
			$scripts += $config['admin_links'];
		}

		$scripts['Admin_Uninstall']['script'] = '/include/admin/admin_rm.php';
		$scripts['Admin_Uninstall']['class'] = 'admin_rm';
		$scripts['Admin_Uninstall']['label'] = $langmessage['uninstall_prep'];
		
		
		/*
		 * 	Unlisted
		 */


		$scripts['Admin_Addons']['script'] = '/include/admin/admin_addons.php';
		$scripts['Admin_Addons']['class'] = 'admin_addons';
		$scripts['Admin_Addons']['label'] = $langmessage['add-ons'];
		$scripts['Admin_Addons']['list'] = false;
		

/*
		$scripts['Admin_Addon_Themes']['script'] = '/include/admin/admin_addon_themes.php';
		$scripts['Admin_Addon_Themes']['class'] = 'admin_addon_themes';
		$scripts['Admin_Addon_Themes']['label'] = $langmessage['addon_themes'];
		$scripts['Admin_Addon_Themes']['list'] = false;
*/


		return $scripts;
	}
	
	
	
	function GetInfo($script){
		
		$scripts = admin_tools::AdminScripts();
		if( !isset($scripts[$script]) ){
			return false;
		}
		
		return $scripts[$script];
	}

	//returns false if the user does not have permission for the $script
	function HasPermission($script){
		global $gpAdmin;
		
		//old
		$gpAdmin += array('granted'=>'');
		if( $gpAdmin['granted'] == 'all' ){
			return true;
		}
		
		$granted = ','.$gpAdmin['granted'].',';
		if( strpos($granted,','.$script.',') !== false ){
			return true;
		}
		
		return false;
	}

		
	function GetAdminPanel(){
		global $langmessage,$page,$gpAdmin;
		
		//don't send the panel when it's a gpreq=json request
		if( !empty($_REQUEST['gpreq']) ){
			return;
		}
		
		includeFile('install/update_class.php');
		
		echo '<div id="gpadminpanel">';
		
		echo '<div id="simplepanel">';
		echo '<div class="panelwrapper">';
		
			echo '<ul class="right">';
			
				//admin
				echo '<li class="expand_child simple_top">';
					$img = '<img src="'.common::GetDir('/include/imgs/page_white_gear.png').'" height="16" width="16" alt=""/>';
					echo common::Link('Admin_Main',$img.$langmessage['admin']);
					admin_tools::GetAdminLinks();
				echo '</li>';
				
				//add-ons
				admin_tools::GetAddonLinks();
				
				//username
				echo '<li class="expand_child simple_top">';
					$img = '<img src="'.common::GetDir('/include/imgs/user.png').'" height="16" width="16"  alt=""/>';
					echo common::Link('Admin_Preferences',$img.$gpAdmin['username'],'','class="toplink"');
					echo '<ul>';
						echo '<li>';
						echo common::Link('Admin_Preferences',$langmessage['Preferences']);
						echo '</li>';
						echo '<li>';
						echo common::Link($page->title,$langmessage['logout'],'cmd=logout');
						echo '</li>';
						echo '<li class="seperator">';
						//echo common::Link('Admin_About','About gpEasy','',' name="ajax_box" ');
						echo common::Link('Admin_About','About gpEasy');
						echo '</li>';
					echo '</ul>';
				echo '</li>';
				
				
				//check versions
				$check_status = update_class::VersionsAndCheckTime($new_versions);
				if( isset($new_versions['core']) ){
					echo '<li class="expand_child simple_top">';
					$img = '<img src="'.common::GetDir('/include/imgs/arrow_refresh.png').'" height="16" width="16" alt=""/>';
					echo '<a class="toplink">';
					echo $img;
					echo $langmessage['updates'];
					echo '</a>';
						echo '<ul>';
							echo '<li>';
							echo '<a href="'.common::GetDir('/include/install/update.php').'">gpEasy '.$new_versions['core'].'</a>';
							echo '</li>';
						echo '</ul>';
					echo '</li>';
				}
					
			echo '</ul>';
			
			
			echo '<ul class="left">';
				
				
				//frequently used
				echo '<li class="expand_child simple_top">';
					$img = '<img src="'.common::GetDir('/include/imgs/page_white_text.png').'" height="16" width="16"  alt=""/>';
					echo '<a href="#" class="toplink">'.$img.$langmessage['frequently_used'].'</a>';
					echo '<ul>';
					$scripts = admin_tools::AdminScripts();
					$add_one = true;
					if( isset($gpAdmin['freq_scripts']) ){
						foreach($gpAdmin['freq_scripts'] as $link => $hits ){
							if( isset($scripts[$link]) ){
								echo '<li>';
								echo common::Link($link,$scripts[$link]['label']);
								echo '</li>';
								if( $link === 'Admin_Menu' ){
									$add_one = false;
								}
							}
						}
						if( $add_one && count($gpAdmin['freq_scripts']) >= 5 ){
							$add_one = false;
						}
					}
					if( $add_one ){
						echo '<li>';
						echo common::Link('Admin_Menu',$scripts['Admin_Menu']['label']);
						echo '</li>';
					}
					echo '</ul>';
				echo '</li>';
								
				//title options
				$title_options = admin_tools::TitleOptions();
				if( !empty($title_options) ){
					echo '<li class="expand_child simple_top" id="edit_list_new">';
						$img = '<img src="'.common::GetDir('/include/imgs/page_edit.png').'" height="16" width="16"  alt=""/> ';
						echo '<a href="#" class="toplink">'.$img;
						echo $langmessage['title_options'];
						echo '</a>';
						echo '<ul>';
						echo $title_options;
						echo '</ul>';
					echo '</li>';
				}

				
			
			echo '</ul>';
			
			admin_tools::CheckStatus($check_status);
			
		echo '</div>';
		echo '</div>'; //end simplepanel
		

		//simplesubpanel
		if( count($page->admin_links) > 0 ){
			echo ' <div id="simplesubpanel">';
			echo '<div class="panelwrapper">';
			echo '<ul class="left">';
			
			foreach($page->admin_links as $label => $link){
				echo '<li class="simple_top">';
				
					if( is_array($link) ){
						echo call_user_func_array(array('common','Link'),$link); /* preferred */
						
					}elseif( is_numeric($label) ){
						echo $link; //just a text label
						
					}elseif( empty($link) ){
						echo '<span>';
						echo $label;
						echo '</span>';
						$link = '#';
						
					}else{
						echo '<a href="'.$link.'">';
						echo $label;
						echo '</a>';
					}
					
				echo '</li>';
			}
			
			echo '</ul> ';
			echo '<div style="clear:both"></div>';
			echo '</div> ';
			echo '</div> ';
		}

		echo '<div style="clear:both"></div>';
		echo '<div id="ckeditor_toolbar"></div>';
			
		echo '</div>'; //end adminpanel
		
	}
	
	
	function CheckStatus($status){
		global $gpversion,$config;
		$checkin = false;
		
		//done with <img..> so that systems without internet access won't be delayed with connection timeouts
		if( $status == 'checknow' ){
			echo '<img src="'.common::GetDir('/include/install/update.php?cmd=embededcheck').'" height="1" width="1" alt=""/>';
			$checkin = true;
		}elseif( $status == 'checkincompat' ){
			$checkin = true;
		}
		
		if( $checkin == false ){
			return;
		}
		
		if( !isset($config['gpuniq']) ){
			return;
		}
		
		//can't be done from update.php
		$q = '&uniq='.urlencode($config['gpuniq']);
		$q .= '&site='.urlencode(common::AbsoluteUrl(''));
		$q .= '&gpv='.urlencode($gpversion);
		$q .= '&php='.urlencode(phpversion());
		if( defined('service_provider_id') && is_numeric(service_provider_id) ){
			$q .= '&provider='.urlencode(service_provider_id);
		}
		$q .= '&s='.urlencode($status);
		
		echo '<img src="http://gpeasy.com/index.php/Special_Resources?cmd=checkin'.$q.'" height="1" width="1" alt=""/>';
		
	}
		
	function TitleOptions(){
		global $page,$langmessage;

		ob_start();
		
		//edit title
		if( admin_tools::HasPermission('file_editing') ){
			if( $page->editable_content ){
				echo '<li>';
				echo common::Link($page->title,$langmessage['edit'],'cmd=edit');
				echo '</li>';
			}
			
			//rename and meta content
			if( $page->editable_details ){
				echo '<li>';
				echo common::Link($page->title,$langmessage['title_details'],'cmd=renameform');
				echo '</li>';
			}
		}
						
					
		//arrange content
		if( admin_tools::HasPermission('Admin_Theme_Content') ){
			echo '<li>';
			//echo '<li class="expand_child">';
			if( !empty($page->gpLayout) ){
				echo common::Link('Admin_Theme_Content',$langmessage['edit_layout'],'cmd=editlayout&layout='.urlencode($page->gpLayout));
			}else{
				echo common::Link('Admin_Theme_Content',$langmessage['edit_layout']);
			}
			
			/*
			echo '<ul>';
			echo '<li>';
				echo common::Link('Admin_Theme_Content',$langmessage['layouts']);
			echo '</li>';
			echo '</ul>';
			*/
			

			echo '</li>';
		}		
		
		return ob_get_clean();
	}
	
	
	
	//admin_tools::AdminHtml();
	function AdminHtml(){
		global $langmessage;
		echo '<div id="gp_admin_html">';
			
			echo '<div id="edit_area_overlay_cont">';
				echo '<div id="edit_area_overlay_top" class="edit_area_overlay"></div>';
				echo '<div id="edit_area_overlay_right" class="edit_area_overlay"></div>';
				echo '<div id="edit_area_overlay_bottom" class="edit_area_overlay"></div>';
				echo '<div id="edit_area_overlay_left" class="edit_area_overlay"></div>';
			echo '</div>';
			
			echo '<div id="loading" style="display:none">';
				echo '<div>';
				echo '<img src="'.common::GetDir('/include/imgs/loader64.gif').'" alt="'.$langmessage['loading'].'" />';
				echo '</div>';
			echo '</div>';
			
			admin_tools::GetAdminPanel();
		echo '</div>';
		
		
	}
	
	function GetAdminLinks(){
		global $langmessage;
		
		$scripts = admin_tools::AdminScripts();
		

		$show = array();
		$count = 0;
		$addon = false;
		echo '<ul>';
		foreach($scripts as $script => $info){
			if( isset($info['list']) && ($info['list'] === false) ){
				continue;
			}
			if( admin_tools::HasPermission($script) ){
				$class = '';
				if( isset($info['addon']) ){
					if( $addon == false ){
						$class = ' class="seperator" ';
					}
					$addon = true;
				}elseif( $addon ){
					$class = ' class="seperator" ';
				}
				
				echo '<li '.$class.'>';
				echo common::Link($script,$info['label']);
				echo '</li>';
				$count++;
			}
		}
		
		if( $count < 1 ){
			echo '<li>';
			echo common::Link('Admin_Preferences',$langmessage['Preferences']);
			echo '</li>';
		}
		echo '</ul>';
	}
	

	
	function CheckPostedNewPage($title=false){
		global $langmessage,$gptitles;
		
		if( $title === false ){
			$title = $_POST['title'];
		}
		
		$title = gpFiles::CleanTitle($title);
		if( isset($gptitles[$title]) ){
			message($langmessage['TITLE_EXISTS']);
			return false;
		}
		if( empty($title) ){
			message($langmessage['TITLE_REQUIRED']);
			return false;
		}
		
		$type = common::SpecialOrAdmin($title);
		if( $type !== false ){
			message($langmessage['TITLE_RESERVED']);
			return false;
		}
		
		//check for case
		foreach($gptitles as $gptitle => $info){
			if( strcasecmp($gptitle,$title ) == 0 ){
				message($langmessage['TITLE_EXISTS']);
				return false;
			}
		}
		
		if( strlen($title) > 60 ){
			message($langmessage['LONG_TITLE']);
			return false;
		}
		return $title;
	}		
					

	
	
	/* deprecated */
	function PHPVariable($name,$value){
		return gpFiles::PHPVariable($name,$value);
	}
	
	/* deprecated */
	function ArrayToPHP($varname,&$array){
		return gpFiles::ArrayToPHP($varname,$array);
	}
	
	/* deprecated */
	function SaveArray($file,$varname,&$array){
		return gpFiles::SaveArray($file,$varname,$array);
	}
	
	
	//
	//	functions for gpmenu, gptitles
	//
	
	//admin_tools::SaveAllConfig();
	function SaveAllConfig(){
		if( !admin_tools::SaveConfig() ){
			return false;
		}

		if( !admin_tools::SavePagesPHP() ){
			return false;
		}
		return true;
	}
	
	function SavePagesPHP(){
		global $gpmenu, $gptitles, $dataDir, $gpLayouts;
		
		$pages = array();
		$pages['gpmenu'] = $gpmenu;
		$pages['gptitles'] = $gptitles;
		$pages['gpLayouts'] = $gpLayouts;
		
		if( !gpFiles::SaveArray($dataDir.'/data/_site/pages.php','pages',$pages) ){
			return false;
		}
		return true;
	}
	
	function SaveConfig(){
		global $config,$dataDir;
		
		if( !isset($config['gpuniq']) ) $config['gpuniq'] = common::RandomString(20);
		
		return gpFiles::SaveArray($dataDir.'/data/_site/config.php','config',$config);
	}
	
	function MenuInsert($new_title,$after,$new_level){
		global $gpmenu;
		$new_menu = array();
		foreach($gpmenu as $gpmenu_title => $gpmenu_level){
			$new_menu[$gpmenu_title] = $gpmenu_level;
			if( $gpmenu_title == $after ){
				$new_menu[$new_title] = $new_level;
			}
		}
		$gpmenu = $new_menu;
	}
	
	function TitlesAdd($title,$label,$type,$new=false){
		global $gptitles,$langmessage;
		
		$label = gpFiles::CleanLabel($label);
		$gptitles[$title]['label'] = $label;
		$gptitles[$title]['type'] = $type;
		
		if( $new ){
			
			//Put some default content in the pages directory
			if( $type == 'gallery' ){
				$defaultContent = '<p>'.$langmessage['NEW_PAGE'].'</p>';
			}else{
				$defaultContent = '<h2>'.$label.'</h2>';
				$defaultContent .= '<p>'.$langmessage['NEW_PAGE'].'</p>';
			}
			
			if( !gpFiles::SaveTitle($title,$defaultContent,$type) ){
				message($langmessage['OOPS']);
				return false;
			}
		}
		return true;		
	}
	
	
	
	//now in gpFiles
	function tidyFix(&$text){
		return gpFiles::tidyFix($text);
	}
	
	
	
	//
	//	Add-Ons
	//
	
	
	function GetAddonLinks(){
		global $langmessage, $config;
		
		ob_start();
		
		echo '<ul>';
		
		$addon_permissions = admin_tools::HasPermission('Admin_Addons');
		
		$show =& $config['addons'];
		if( is_array($show) ){
			
			foreach($show as $addon => $info){
				
				//backwards compat
				if( is_string($info) ){
					$addonName = $info;
				}else{
					$addonName = $info['name'];
				}
				
				echo '<li class="expand_child">';
				
				$sublinks = admin_tools::GetAddonSubLinks_New($addon);
				$sublinks .= admin_tools::GetAddonSubLinks($addon);
				if( !empty($sublinks) ){
					$sublinks = '<ul>'.$sublinks.'</ul>';
				}elseif( !$addon_permissions ){
					continue;
				}
				
				if( $addon_permissions ){
					echo common::Link('Admin_Addons',$addonName,'cmd=show&addon='.$addon);
				}else{
					echo '<a href="#">'.$addonName.'<a>';
				}
				echo $sublinks;
				
				echo '</li>';
			}
		}
		
		
		//Install Link
		$admin_addon_permission = admin_tools::HasPermission('Admin_Addons');
		if( $admin_addon_permission ){

			echo '<li class="seperator">';
			echo common::Link('Admin_Addons',$langmessage['manage']);
			echo '</li>';
			
			echo '<li>';
			echo '<a href="'.$GLOBALS['addonBrowsePath'].'/Special_Addon_Plugins" name="remote">Browse Addons</a>';
			echo '</li>';
		}
		
		echo '</ul>';
		$addon_links = ob_get_clean();
		
		
		//if it's empty, then the user doesn't have permission
		if( empty($addon_links) ){
			return;
		}
		
		echo '<li class="expand_child simple_top">';
			$img = '<img src="'.common::GetDir('/include/imgs/plugin.png').'" height="16" width="16" alt=""/>';
			$label = $img.$langmessage['plugins'].' (beta)';
		
			if( $admin_addon_permission ){
				echo common::Link('Admin_Addons',$label,'','class="toplink"');
				echo $addon_links;
			}else{
				echo '<a href="#" class="toplink">'.$label.'</a>';
			}
			echo $addon_links;
		echo '</li>';
		
	}
	
	
	
	function CanRemoteInstall(){
		static $bool;
		
		if( isset($bool) ){
			return $bool;
		}
		
		$bool = true;
		if( gptesting === false ){
			$bool = false;
		}
		
		if( !ini_get('allow_url_fopen') ){
			$bool = false;
		}
		
		if( !function_exists('gzinflate') ){
			$bool = false;
		}
		
		if( defined('Browse_Addons') && Browse_Addons === false ){
			$bool = false;
		}
		
		return $bool;
	}
		
	
	function GetAddonSubLinks_New($addon=false){
		global $gptitles;
		
		$sublinks = admin_tools::GetAddonComponents($gptitles,$addon);

		$result = '';
		foreach($sublinks as $linkName => $linkInfo){
			$result .= '<li>';
			$result .= common::Link($linkName,$linkInfo['label']);
			$result .= '</li>';
		}
		return $result;
	}
	
	/* admin links */
	function GetAddonSubLinks($addon=false){
		global $config;
		
		$sublinks = admin_tools::GetAddonComponents( $config['admin_links'], $addon);
		
		$result = '';
		foreach($sublinks as $linkName => $linkInfo){
			if( admin_tools::HasPermission($linkName) ){
				$result .= '<li>';
				$result .= common::Link($linkName,$linkInfo['label']);
				$result .= '</li>';
			}
		}
		return $result;
	}
	
	function GetAddonComponents($from,$addon){
		if( !is_array($from) ){
			return;
		}
		
		$result = array();
		foreach($from as $name => $value){
			if( !is_array($value) ){
				continue;
			}
			if( !isset($value['addon']) ){
				continue;
			}
			if( $value['addon'] !== $addon ){
				continue;
			}
			$result[$name] = $value;
		}
		
		return $result;
	}

	
}

