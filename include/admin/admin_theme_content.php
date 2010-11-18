<?php
defined('is_running') or die('Not an entry point...');


/*
what can be moved?
	* .editable_area

How do we position elements?
	* above, below, float:left, float:right in relation to another editable_area

How do we do locate them programatically
	* We need to know the calling functions that output the areas
		then be able to organize a list of output functions within each of the calling functions
		!each area is represented by a list, either a default value if an override hasn't been defined, or the custom list created by the user
		
How To Identify the Output Functions for the Output Lists?
	* Gadgets have:
		$info['script']
		$info['data']
		$info['class']


$gpOutConf = array() of output functions/classes.. to use with the theme content
	==potential values==
	$gpOutConf[-ident-]['script'] = -path relative to datadir or rootdir?
	$gpOutConf[-ident-]['data'] = -path relative to datadir-
	$gpOutConf[-ident-]['class'] = -path relative to datadir or rootdir?
	$gpOutConf[-ident-]['method'] = string or array: string=name of function, array(class,method)
	
	
	$gpLayout['Loyout_Name']['handlers'][-ident-] = array(0=>-ident-,1=>-ident-)
	$gpLayout['Loyout_Name']['color'] = '#123456'
	$gpLayout['Loyout_Name']['theme'] = 'One_Point_5/Blue'
	
*/

includeFile('admin/admin_menu_tools.php');

class admin_theme_content extends admin_menu_tools{
	
	var $curr_layout;
	
	function admin_theme_content(){
		global $page,$config,$gpLayouts;
		
		$GLOBALS['GP_ARRANGE_CONTENT'] = true;
		
		$page->head .= '<script type="text/javascript" language="javascript" src="'.common::GetDir('/include/js/theme_content.js').'"></script>';
		$page->head .= '<script type="text/javascript" language="javascript" src="'.common::GetDir('/include/js/dragdrop.js').'"></script>';
		$page->admin_css .= '<link rel="stylesheet" type="text/css" href="'.common::GetDir('/include/css/theme_content.css').'" />';
		$this->curr_layout = $config['gpLayout'];
		
		$this->SetLayoutArray();
		$cmd = common::GetCommand();
		switch($cmd){
			
			//adminlayout
			case 'adminlayout':
				$this->AdminLayout();
			return;
			
			//theme ratings
			case 'Update Review';
			case 'Send Review':
			case 'rate':
				
				includeFile('admin/admin_addons_tool.php');
				$rating = new admin_addons_tool();
				$rating->admin_addon_rating('theme','Admin_Theme_Content');
				if( $rating->ShowRatingText ){
					return;
				}
			break;
			
			//new layouts
			case 'preview':
				if( $this->PreviewTheme() ){
					return;
				}
			break;
			case 'newlayout':
				$this->NewLayout();
			break;
			
			
			//editing layouts
			case 'addcontent':
			case 'restore':
			case 'drag':
			case 'editlayout':
			case 'rm':
			case 'insert':
				if( $this->EditLayout($cmd) ){
					return;
				}
			break;

			
			//layout options
			case 'makedefault':
				$this->MakeDefault();
			break;
			case 'deletelayout':
				$this->DeleteLayout();
			return;
			case 'deletelayoutconfirmed':
				$this->DeleteLayoutConfirmed();
			break;
			
			case 'layout_details':
				$this->LayoutDetails();
			break;
			
			
			//links
			case 'editlinks':
			case 'editcustom':
				$this->SelectLinks();
			return;
			case 'savelinks':
				$this->SaveLinks();
			break;

			//text
			case 'edittext':
				$this->EditText();
			return;
			case 'savetext':
				$this->SaveText();
			break;
			
			
			case 'saveaddontext':
				$this->SaveAddonText();
			break;
			case 'addontext':
				$this->AddonText();
			return;
			

		}
		
		//message(showArray($_GET));
		$this->Show();
	}
	
	
	function AdminLayout(){
		global $langmessage;
		
		echo '<div class="inline_box">';
		echo '<form method="post" action="">';
		
		$admin_layout = $langmessage['default'];
		echo '<h2>'.'Admin Layout'.'</h2>';
		
		echo '<select name="">';
			echo '<option value="">'.$langmessage['default'].'</option>';
		echo '</select>';
		echo ' <input type="submit" name="" value="'.$langmessage['continue'].'" />';
			
		echo '</form>';
		echo '</div>';
	}
	
	
	function EditLayout($cmd){
		global $page,$gpLayouts,$langmessage;
		
		$layout = $_REQUEST['layout'];
		if( !isset($gpLayouts[$layout]) ){
			message($langmessage['OOPS']);
			return false;
		}
		
		$page->head .= '<script type="text/javascript">var gpLayouts=true;</script>';
		$this->curr_layout = $layout;
		$page->SetTheme($layout);
		
		switch($cmd){
			
			case 'restore':
				$this->Restore();
			break;
			case 'drag':
				$this->Drag();
			break;
			
			//insert
			case 'insert':
				$this->SelectContent();
			return true;
			
			case 'addcontent':
				$this->AddContent();
			break;
			
			//remove
			case 'rm':
				$this->RemoveArea();
			break;
			
		}

		$layout_info = $gpLayouts[$layout];
		echo '<h2>'.$langmessage['current_layout'].' » '.$layout_info['label'].'</h2>';
		
		echo '<p>';
		echo $langmessage['DRAG-N-DROP-DESC'];
		echo '</p>';
		
		
		$handlers_count = 0;
		if( isset($layout_info['handlers']) && is_array($layout_info['handlers']) ){
			foreach($layout_info['handlers'] as $val){
				$int = count($val);
				if( $int === 0){
					$handlers_count++;
				}
				$handlers_count += $int;
			}
		}
		if( $handlers_count > 0 ){
			//use layout_id so that the display doesn't change
			echo common::Link('Admin_Theme_Content',$langmessage['restore_defaults'],'cmd=restore&layout='.urlencode($layout),' name="creq" class="theme_buttons" '); 
			//echo $langmessage['restore_defaults'];
		}
		
		echo common::Link('Admin_Theme_Content','« '.$langmessage['layouts'],'',' class="theme_buttons" '); 
		

		echo '<br/>';
		$titles_count = $this->TitlesCount($layout);
		$heading = $langmessage['titles_using_layout'];
		$heading .= ': '.$titles_count;
		echo '<h3>'.$heading.'</h3>';
		if( $titles_count > 0 ){
			echo '<ul class="titles_using">';
			foreach( $this->LayoutArray as $title => $layout_comparison ){
				if( $layout == $layout_comparison ){
					
					echo "\n<li>";
					echo common::Link($title,common::GetLabel($title));
					echo '</li>';
				}
			}
			
			echo '</ul>';
			echo '<div class="clear"></div>';
		}
		
		
		return true;
		
	}
	
	function GetColors(){
		
		//color/layout_id changing
		$colors = array();
		
		$colors[] = '#ff0000';
		$colors[] = '#ff9900';
		$colors[] = '#ffff00';
		$colors[] = '#00ff00';
		$colors[] = '#00ffff';
		$colors[] = '#0000ff';
		$colors[] = '#9900ff';
		$colors[] = '#ff00ff';

		$colors[] = '#f4cccc';
		$colors[] = '#fce5cd';
		$colors[] = '#fff2cc';
		$colors[] = '#d9ead3';
		$colors[] = '#d0e0e3';
		$colors[] = '#cfe2f3';
		$colors[] = '#d9d2e9';
		$colors[] = '#ead1dc';
		
		
		$colors[] = '#ea9999';
		$colors[] = '#f9cb9c';
		$colors[] = '#ffe599';
		$colors[] = '#b6d7a8';
		$colors[] = '#a2c4c9';
		$colors[] = '#9fc5e8';
		$colors[] = '#b4a7d6';
		$colors[] = '#d5a6bd';
		
		$colors[] = '#e06666';
		$colors[] = '#f6b26b';
		$colors[] = '#ffd966';
		$colors[] = '#93c47d';
		$colors[] = '#76a5af';
		$colors[] = '#6fa8dc';
		$colors[] = '#8e7cc3';
		$colors[] = '#c27ba0';
		
		
		$colors[] = '#cc0000';
		$colors[] = '#e69138';
		$colors[] = '#f1c232';
		$colors[] = '#6aa84f';
		$colors[] = '#45818e';
		$colors[] = '#3d85c6';
		$colors[] = '#674ea7';
		$colors[] = '#a64d79';
		
		
		$colors[] = '#990000';
		$colors[] = '#b45f06';
		$colors[] = '#bf9000';
		$colors[] = '#38761d';
		$colors[] = '#134f5c';
		$colors[] = '#0b5394';
		$colors[] = '#351c75';
		$colors[] = '#741b47';

		
/*		Too dark
		$colors[] = '#660000';
		$colors[] = '#783f04';
		$colors[] = '#7f6000';
		$colors[] = '#274e13';
		$colors[] = '#0c343d';
		$colors[] = '#073763';
		$colors[] = '#20124d';
		$colors[] = '#4c1130';
*/
		
		return $colors;
	}
	
	
	function NewLayout(){
		global $gpLayouts,$langmessage,$config,$page;
		
		$theme =& $_POST['theme'];
		if( !$this->CheckTheme($theme) ){
			message($langmessage['OOPS']);
			return false;
		}
		
		
		$colors = $this->GetColors();
		$color_key = array_rand($colors);
		
		$newLayout = array();
		$newLayout['theme'] = $theme;
		$newLayout['color'] = $colors[$color_key];
		$newLayout['label'] = substr($theme,0,15);
		
		
		do{
			$layout_id = rand(1000,9999);
		}while( isset($gpLayouts[$layout_id]) );
		
		$gpLayoutsBefore = $gpLayouts;
		$gpLayouts[$layout_id] = $newLayout;
		if( admin_tools::SavePagesPHP() ){
			message($langmessage['SAVED']);
		}else{
			$gpLayouts = $gpLayoutsBefore;
			message($langmessage['OOPS']);
		}
		
		
		if( isset($_POST['default']) && $_POST['default'] == 'true' ){
			$config['gpLayout'] = $layout_id;
			admin_tools::SaveConfig();
			$page->SetTheme();
			$this->SetLayoutArray();
		}
	}
	
	function PreviewTheme(){
		global $langmessage,$config,$page;
		
		$theme =& $_GET['theme'];
		if( !$this->CheckTheme($theme) ){
			message($langmessage['OOPS']);
			return false;
		}
		
		$template = dirname($theme);
		$color = basename($theme);

		
		echo '<h2>'.$langmessage['preview'].'</h2>';
		echo '<p>';
		echo sprintf($langmessage['currently_previewing'],$theme);
		echo '</p>';

		
		echo '<p>';
		echo '<form action="'.common::GetUrl('Admin_Theme_Content').'" method="post">';
		echo '<input type="hidden" name="cmd" value="newlayout" />';
		echo '<input type="hidden" name="theme" value="'.htmlspecialchars($theme).'" />';
		echo ' <input type="submit" class="theme_buttons" name="" value="'.htmlspecialchars($langmessage['use_this_theme']).'" />';
		echo '</form>';
		echo '</p>';
		
		echo '<p>';
		echo '<form action="'.common::GetUrl('Admin_Theme_Content').'" method="post">';
		echo '<input type="hidden" name="cmd" value="newlayout" />';
		echo '<input type="hidden" name="default" value="true" />';
		echo '<input type="hidden" name="theme" value="'.htmlspecialchars($theme).'" />';
		echo ' <input type="submit" class="theme_buttons" name="" value="'.htmlspecialchars($langmessage['make_default_layout']).'" />';
		echo '</form>';
		echo '</p>';
		
		echo '<p>';
		echo '<form action="'.common::GetUrl('Admin_Theme_Content').'" method="post">';
		echo ' <input type="submit" class="theme_buttons" name="" value="« '.htmlspecialchars($langmessage['layouts']).'" />';
		echo '</form>';
		echo '</p>';
		
		$this->ShowAvailable();
		

		$page->gpLayout = false;
		$page->theme = $theme;
		$page->theme_name = $template;
		$page->theme_color = $color;
		return true;
	}
	
	function CheckTheme($theme){
		
		$template = dirname($theme);
		$color = basename($theme);
		
		$themes = $this->GetPossible();
		if( !isset($themes[$template]) || !isset($themes[$template]['colors'][$color]) ){
			return false;
		}
		
		return true;
	}
	//possible themes		
	function GetPossible(){
		global $dataDir;
		$dir = $dataDir.'/themes';
		$themes = array();
		$layouts = gpFiles::readDir($dir,1);
		asort($layouts);
		foreach($layouts as $name){
			$fullDir = $dir.'/'.$name;
			$templateFile = $fullDir.'/template.php';
			if( !file_exists($templateFile) ){
				continue;
			}
			
			//$InstallData = $this->GetAvailInstall($fullDir);
			//if( isset($InstallData['Addon_Unique_ID']) ){
			//	$themes[$name]['id'] = $InstallData['Addon_Unique_ID'];
			//}
			
			$themes[$name]['colors'] = $this->GetThemeColors($fullDir);
		}
		return $themes;
	}
	
	function GetThemeColors($dir){
		$subdirs = gpFiles::readDir($dir,1);
		$colors = array();
		asort($subdirs);
		foreach($subdirs as $subdir){
			if( $subdir == 'images'){
				continue;
			}
			$colors[$subdir] = $subdir;
		}
		return $colors;
	}
	
	
	
	function MakeDefault(){
		global $config,$langmessage,$gpLayouts,$page;
		
		$layout =& $_GET['layout_id'];
		if( !isset( $gpLayouts[$layout]) ){
			message($langmessage['OOPS']);
			return false;
		}
		
		$oldConfig = $config;
		$config['gpLayout'] = $layout;
		
		if( admin_tools::SaveConfig() ){
			
			$page->SetTheme();
			$this->SetLayoutArray();
			
			message($langmessage['SAVED']);
		}else{
			$config = $oldConfig;
			message($langmessage['OOPS']);
		}
	}

	
	function DeleteLayout(){
		global $langmessage,$gpLayouts;
		
		$layout =& $_GET['layout_id'];
		if( !isset( $gpLayouts[$layout]) ){
			message($langmessage['OOPS']);
			return false;
		}
		
		$label = $gpLayouts[$layout]['label'];

		echo '<div class="inline_box">';
		echo '<form method="post" action="">';
		
		echo '<input type="hidden" name="cmd" value="deletelayoutconfirmed" />';
		echo '<input type="hidden" name="layout_id" value="'.htmlspecialchars($layout).'" />';
		echo sprintf($langmessage['generic_delete_confirm'], '<i>'.$label.'</i>');
		echo ' <input type="submit" name="" value="'.$langmessage['continue'].'" />';
		echo ' <input type="submit" name="cmd" value="'.$langmessage['cancel'].'" />';
		echo '</form>';
		echo '</div>';
	}
	
	function DeleteLayoutConfirmed(){
		global $gpLayouts,$langmessage,$gptitles;
		
		
		$layout =& $_POST['layout_id'];
		if( !isset( $gpLayouts[$layout]) ){
			message($langmessage['OOPS']);
			return false;
		}
				
		
		//remove from $gptitles
		foreach($gptitles as $title => $titleInfo){
			if( isset($titleThemes[$title]) ){
				continue;
			}
			if( empty($titleInfo['gpLayout']) ){
				continue;
			}
			
			if( $titleInfo['gpLayout'] == $layout ){
				unset($gptitles[$title]['gpLayout']);
			}
		}
		
		//save
		$gpLayoutsBefore = $gpLayouts;
		unset($gpLayouts[$layout]);
		if( admin_tools::SavePagesPHP() ){
			message($langmessage['SAVED']);
		}else{
			$gpLayouts = $gpLayoutsBefore;
			message($langmessage['OOPS'].' (s1)');
		}		

	}
	
	function LayoutDetails(){
		global $gpLayouts,$langmessage;
		
		$gpLayoutsBefore = $gpLayouts;
		
		$layout =& $_POST['layout_id'];
		if( !isset( $gpLayouts[$layout]) ){
			message($langmessage['OOPS']);
			return false;
		}
		
		if( !empty($_POST['color']) && (strlen($_POST['color']) == 7) && $_POST['color']{0} == '#' ){
			$gpLayouts[$layout]['color'] = $_POST['color'];
		}
		
		$gpLayouts[$layout]['label'] = htmlspecialchars($_POST['layout_label']);
		
		
		if( admin_tools::SavePagesPHP() ){
			message($langmessage['SAVED']);
		}else{
			$gpLayouts = $gpLayoutsBefore;
			message($langmessage['OOPS'].' (s1)');
		}		
	}
	

	
	function Show(){
		global $config,$page,$langmessage,$gptitles,$gpLayouts;

/*
		echo '<div style="float:right">';
		$admin_layout = $langmessage['default'];
		echo 'Admin Layout: ';
		echo common::Link('Admin_Theme_Content',$admin_layout,'cmd=adminlayout',' name="ajax_box" ');
		echo '</div>';
*/
		
		echo '<h2>'.$langmessage['layouts'].'</h2>';
		
		echo '<table class="bordered" style="width:100%">';
		echo '<tr>';
			echo '<th>';
			echo $langmessage['layouts'];
			echo '</th>';
			echo '<th>';
			echo $langmessage['usage'];
			echo '</th>';
			echo '<th>';
			echo $langmessage['theme'];
			echo '</th>';
			echo '<th>';
			echo $langmessage['options'];
			echo '</th>';
			echo '</tr>';
		
		foreach($gpLayouts as $layout => $info){
			$this->ShowLayout($layout,$info);
		}
		
		echo '</table>';
		
/*
		echo '<form method="post" action="">';
		echo '<select>';
			echo '<option value="">Some Layout Name</option>';
			echo '<option value="">Some Layout Name</option>';
		echo '</select>';
		echo '</form>';
*/
		
		echo '<br/>';
		
		$this->ShowAvailable();
		
		echo '<p class="admin_note">';
		echo $langmessage['see_also'];
		echo ' ';
		echo common::Link('Admin_Menu',$langmessage['file_manager']);
		echo '</p>';
		echo '<p>';
		echo '<a href="'.$GLOBALS['addonBrowsePath'].'/Special_Addon_Themes" name="remote">Browse Additional Themes</a>';
		echo '</p>';
		
		
		
		$colors = $this->GetColors();
		echo '<div id="layout_ident">';
		echo '<form method="post" action="">';
		echo '<input type="hidden" name="layout_id" value="" />';
		echo '<input type="hidden" name="color" value="" />';
		echo '<input type="hidden" name="cmd" value="layout_details" />';
		
		echo '<table cellpadding="3">';
		
		
		echo '<tr>';
			echo '<td>';
			echo ' <a href="#" class="layout_color_id" id="current_color"></a> ';
			echo '<input type="text" name="layout_label" value="" maxlength="15"/>';
			echo '</td>';
			echo '</tr>';
			
		echo '<tr>';
			echo '<td>';
			echo '<div class="colors">';
			foreach($colors as $color){
				echo '<a href="#" class="color" style="background-color:'.$color.'" title="'.$color.'"></a>';
			}
			echo '</div>';
			echo '</td>';
			echo '</tr>';
			
		echo '<tr>';
			echo '<td>';
			echo ' <input type="submit" class="submit" name="" value="Ok" />';
			echo ' <input type="button" class="submit cancel" name="" value="Cancel" />';
			echo '</td>';
			echo '</tr>';
					
		echo '</table>';
		echo '</form>';
		echo '</div>';
		
	}
	
	function ShowAvailable(){
		global $langmessage;
		$themes = $this->GetPossible();
		
		echo '<table class="bordered" style="width:100%">';
		echo '<tr>';
			echo '<th>';
			echo $langmessage['available_themes'];
			echo '</th>';
			echo '<th>';
			echo $langmessage['color'];
			echo '</th>';
			echo '<th>';
			echo $langmessage['options'];
			echo '</th>';
			echo '</tr>';
			
			foreach($themes as $theme => $info){
				echo '<tr>';
				echo '<td>';
				echo str_replace('_',' ',$theme);
				echo '</td>';
				echo '<td>';
				$comma = '';
				foreach($info['colors'] as $color){
					echo $comma;
					echo common::Link('Admin_Theme_Content',$color,'cmd=preview&theme='.rawurlencode($theme.'/'.$color)); //,' name="creq" ');
					$comma = ', ';
				}
				
				echo '</td>';
				echo '<td>';
				echo common::Link('Admin_Theme_Content',$langmessage['rate'],'cmd=rate&arg='.rawurlencode($theme));
				echo '</td>';
				echo '</tr>';
			}
			
		echo '</table>';
		
	}
	
	
	
	
	function ShowLayout($layout,$info){
		global $page, $langmessage, $config, $gptitles;
		
		echo '<tr>';

		//label
			echo '<td class="nowrap">';
			echo '<a href="#" class="layout_color_id" style="background-color:'.$info['color'].';" title="'.$info['color'].'">';
			echo '<input type="hidden" class="layout_id" name="layout_id" value="'.htmlspecialchars($layout).'"  /> ';
			echo '<input type="hidden" class="layout_label" name="layout_label" value="'.$info['label'].'"  /> ';
			echo '</a> ';
			echo common::Link('Admin_Theme_Content',$info['label'],'cmd=editlayout&layout='.urlencode($layout));
			echo '</td>';
		
		//usage
			echo '<td>';
			$titles_count = $this->TitlesCount($layout);
			echo $titles_count;
				
			echo ' <span class="admin_note">';
			if( $config['gpLayout'] == $layout ){
				echo $langmessage['default'];
			}else{
				echo common::Link('Admin_Theme_Content',str_replace(' ','&nbsp;',$langmessage['make_default']),'cmd=makedefault&layout_id='.urlencode($layout),' name="creq" ');
			}
			echo '</span>';
			echo '</td>';
			
		//theme
			echo '<td>';
			echo $info['theme'];
			echo '</td>';

		//options
			echo '<td class="nowrap">';
			
			echo common::Link('Admin_Theme_Content',$langmessage['edit'],'cmd=editlayout&layout='.urlencode($layout));
			echo ' ';
			
			if( $config['gpLayout'] == $layout ){
				echo '<span>'.$langmessage['delete'].'</span>';
			}else{
				echo common::Link('Admin_Theme_Content',$langmessage['delete'],'cmd=deletelayout&layout_id='.urlencode($layout),' name="ajax_box"');
			}
			
			echo '</td>';
			
		echo '</tr>';		
		
	}
	
	function TitlesCount($layout){
		$titles_count = 0;
		foreach( $this->LayoutArray as $title => $layout_comparison ){
			if( $layout == $layout_comparison ){
				$titles_count++;
			}
		}
		return $titles_count;
	}
	
	
	function Restore(){
		$this->SaveHandlersNew(array(),$_GET['layout']);
	}
	
	function SaveHandlersNew($handlers,$layout=false){
		global $config,$page,$langmessage,$gpLayouts;
		
		if( $layout == false ){
			$layout = $this->curr_layout;
		}
		
		if( !isset( $gpLayouts[$layout] )  ){
			message($langmessage['OOPS']);
			return false;
		}
		
		$gpLayoutsBefore = $gpLayouts;
		if( count($handlers) === 0 ){
			unset($gpLayouts[$layout]['handlers']);
		}else{
			$gpLayouts[$layout]['handlers'] = $handlers;
		}
		
		if( admin_tools::SavePagesPHP() ){
			
			message($langmessage['SAVED']);
			
		}else{
			$gpLayouts = $gpLayoutsBefore;
			message($langmessage['OOPS'].' (s1)');
		}
	}
	
	
	
	
	function ParseHandlerInfo($str,&$info){
		global $config,$gpOutConf;
		
		if( substr_count($str,'|') !== 1 ){
			return false;
		}
		
		
		list($container,$fullKey) = explode('|',$str);
		
		$arg = '';
		$pos = strpos($fullKey,':');
		$key = $fullKey;
		if( $pos > 0 ){
			$arg = substr($fullKey,$pos+1);
			$key = substr($fullKey,0,$pos);
		}
		
		if( !isset($gpOutConf[$key]) && !isset($config['gadgets'][$key]) ){
			return false;
		}
		
		$info = array();
		$info['gpOutKey'] = $fullKey;
		$info['container'] = $container;
		$info['key'] = $key;
		$info['arg'] = $arg;
		
		return true;
		
	}
	
	
	
	
	function GetAllHandlers($layout=false){
		global $page,$gpLayouts, $config;
		
		if( $layout === false ){
			$layout = $this->curr_layout;
		}
		
		$handlers =& $gpLayouts[$layout]['handlers'];
		
		if( !is_array($handlers) || count($handlers) < 1 ){
			$handlers = array();
		}
		return $handlers;
	}
	
	
	//set default values if not set
	function PrepContainerHandlers(&$handlers,$container,$gpOutKey){
		if( isset($handlers[$container]) && is_array($handlers[$container]) ){
			return;
		}
		$handlers[$container] = $this->GetDefaultList($container,$gpOutKey);
	}

	
	
	function GetDefaultList($container,$gpOutkey){
		global $config;

		if( $container !== 'GetAllGadgets' ){
			return array($gpOutkey);
		}
		
		$result = array();
		if( isset($config['gadgets']) && is_array($config['gadgets']) ){
			foreach($config['gadgets'] as $gadget => $info){
				$result[] = $gadget;
			}
		}
		return $result;
	}
	
	function GetValues($a,&$container,&$gpOutKey){
		if( substr_count($a,'|') !== 1 ){
			return false;
		}
		
		list($container,$gpOutKey) = explode('|',$a);
		return true;
	}
	
	
	
	function AddToContainer(&$container,$to_gpOutKey,$new_gpOutKey,$replace=true){
		global $langmessage;
		
		//unchanged?
		if( $replace && ($to_gpOutKey == $new_gpOutKey) ){
			return true;
		}
		
		
		//add to to_container in front of $to_gpOutKey
		if( !isset($container) || !is_array($container) ){
			message($langmessage['OOPS'].' (a1)');
			return false;
		}
		
		//can't have two identical outputs in the same container
		$check = array_search($new_gpOutKey,$container);
		if( ($check !== null) && ($check !== false) ){
			message($langmessage['OOPS']. '(a2)');
			return false;
		}
		
		//if empty, just add
		if( count($container) === 0 ){
			$container[] = $new_gpOutKey;
			return true;
		}
		
		$length = 1;
		if( $replace === false ){
			$length = 0;
		}
		
		//insert
		$where = array_search($to_gpOutKey,$container);
		if( ($where === null) || ($where === false) ){
			message($langmessage['OOPS']. '(a3)');
			return false;
		}
		
		array_splice($container,$where,$length,$new_gpOutKey);
		
		return true;
	}
	

	
	function SelectContent(){
		global $langmessage,$config,$gpOutConf;
		
		if( !isset($_GET['param']) ){
			message($langmessage['OOPS'].' (0)');
			return;
		}
		$param = $_GET['param'];
		
		//counts
		$count_gadgets = ( isset($config['gadgets']) && is_array($config['gadgets']) ) ? count($config['gadgets']) : false;
		echo '<div class="inline_box">';
		
		echo '<div class="layout_links">';
		echo '<a href="#layout_extra_content" class="selected" name="tabs">'. $langmessage['theme_content'] .'</a>';
		if( $count_gadgets > 0 ){
			echo ' <a href="#layout_gadgets" name="tabs">'. $langmessage['gadgets'] .'</a>';
		}
		echo ' <a href="#layout_menus" name="tabs">'. $langmessage['Link_Menus'] .'</a>';
		
		echo ' <a href="#layout_custom" name="tabs">'. $langmessage['Custom Menu'] .'</a>';
		
		echo '</div>';
		
		$this->SelectContent_Areas($param,$count_gadgets);
		echo '</div>';		
	}
	
	function SelectContent_Areas($param,$count_gadgets){
		global $dataDir,$langmessage,$config,$gpOutConf;


		$addQuery = 'cmd=addcontent&layout='.urlencode($this->curr_layout).'&where='.urlencode($param);
		echo '<div id="area_lists">';
		
			//extra content
			echo '<div id="layout_extra_content">';
			echo '<table class="bordered">';
				$extrasFolder = $dataDir.'/data/_extra';
				$files = gpFiles::ReadDir($extrasFolder);
				asort($files);
				foreach($files as $file){
					$extraName = $file;
					echo '<tr>';
					echo '<td>';
					echo str_replace('_',' ',$extraName);
					echo '</td>';
					echo '<td class="add">';
					echo common::Link('Admin_Theme_Content',$langmessage['add'],$addQuery.'&insert=Extra:'.$extraName,' name="creq" ');
					echo '</td>';
					echo '</tr>';
				}
				
				
				//new extra area
				echo '</table>';
				echo '<br/>';
				echo '<form method="post" action="">';
				echo '<input type="hidden" name="cmd" value="addcontent" />';
				echo '<input type="hidden" name="addtype" value="new_extra" />';
				echo '<input type="hidden" name="layout" value="'.htmlspecialchars($this->curr_layout).'" />';
				echo '<input type="hidden" name="where" value="'.htmlspecialchars($param).'" />';
				
				echo '<input type="text" name="extra_area" value="" size="15" />';
				echo '<input type="submit" name="" value="'.$langmessage['Add New Area'].'" />';
				echo '</form>';
				
			echo '</div>';
			
			//gadgets
			if( $count_gadgets > 0){
				echo '<div id="layout_gadgets" style="display:none">';
					echo '<table class="bordered">';
					foreach($config['gadgets'] as $gadget => $info){
						echo '<tr>';
							echo '<td>';
							echo str_replace('_',' ',$gadget);
							echo '</td>';
							echo '<td class="add">';
							echo common::Link('Admin_Theme_Content',$langmessage['add'],$addQuery.'&insert='.$gadget,' name="creq" ');
							echo '</td>';
							echo '</tr>';
					}
					echo '</table>';
				echo '</div>';
			}
			
			//menus
			echo '<div id="layout_menus" style="display:none">';

				echo '<table class="bordered">';
					foreach($gpOutConf as $outKey => $info){
						
						if( !isset($info['link']) ){
							continue;
						}
						echo '<tr>';
							echo '<td>';
							if( isset($langmessage[$info['link']]) ){
								echo str_replace(' ','&nbsp;',$langmessage[$info['link']]);
							}else{
								echo str_replace(' ','&nbsp;',$info['link']);
							}
							echo '</td>';
							echo '<td class="add">';
							echo common::Link('Admin_Theme_Content',$langmessage['add'],$addQuery.'&insert='.$outKey,' name="creq" ');
							echo '</td>';
							echo '</tr>';
					}
				echo '</table>';
				
			echo '</div>';

			
			echo '<div id="layout_custom" style="display:none">';
			
				//custom area
				echo '<form method="post" action="">';
				echo '<input type="hidden" name="cmd" value="addcontent" />';
				echo '<input type="hidden" name="addtype" value="custom_menu" />';
				echo '<input type="hidden" name="layout" value="'.htmlspecialchars($this->curr_layout).'" />';
				echo '<input type="hidden" name="where" value="'.htmlspecialchars($param).'" />';
				
				echo '<br/>';
				$this->CustomMenuForm();
					
				echo '<tr><td>';
					echo '&nbsp;';
					echo '</td><td>';
					echo '<input type="submit" name="" value="'.$langmessage['Add New Menu'].'" />';
					echo '</td></tr>';
				echo '</table>';
				
				echo '</form>';
			echo '</div>';
		echo '</div>';
	}
	
	function NewCustomMenu(){
		$upper_bound =& $_POST['upper_bound'];
		$lower_bound =& $_POST['lower_bound'];
		$expand_bound =& $_POST['expand_bound'];
		
		$this->CleanBounds($upper_bound,$lower_bound,$expand_bound);
			
		$arg = $upper_bound.','.$lower_bound.','.$expand_bound;
		//message('arg: '.$arg);
		return 'CustomMenu:'.$arg;
	}
	
	function CleanBounds(&$upper_bound,&$lower_bound,&$expand_bound){
		
		$upper_bound = (int)$upper_bound;
		$upper_bound = max(0,$upper_bound);
		$upper_bound = min(4,$upper_bound);
		
		$lower_bound = (int)$lower_bound;
		$lower_bound = max(-1,$lower_bound);
		$lower_bound = min(4,$lower_bound);
		
		$expand_bound = (int)$expand_bound;
		$expand_bound = max(-1,$expand_bound);
		$expand_bound = min(4,$expand_bound);	
	}
	
	function CustomMenuForm($arg = ''){
		global $langmessage, $gpOutConf;
		
		$args = explode(',',$arg);
		$args += array( 0=>0, 1=>-1, 2=>-1 ); //defaults
		list($upper_bound,$lower_bound,$expand_bound) = $args;
		
		$this->CleanBounds($upper_bound,$lower_bound,$expand_bound);
	
		
		echo $langmessage['Show Titles...'];
		echo '<table class="bordered">';
		echo '<tr><td>';
			echo $langmessage['... Below Level'];
			echo '</td><td>';
			echo '<select name="upper_bound">';
			for($i=0;$i<=4;$i++){
				$label = $i;
				if( $i === 0 ){
					$label = '&nbsp;';
				}
				if( $i === $upper_bound ){
					echo '<option value="'.$i.'" selected="selected">'.$label.'</option>';
				}else{
					echo '<option value="'.$i.'">'.$label.'</option>';
				}
			}
			echo '</select>';
			echo '</td></tr>';
			
		echo '<tr><td>';
			echo $langmessage['... At And Above Level'];
			echo '</td><td>';
			echo '<select name="lower_bound">';
			for($i=0;$i<=4;$i++){
				$label = $i;
				if( $i === 0 ){
					$label = '&nbsp;';
				}
				if( $i === $lower_bound ){
					echo '<option value="'.$i.'" selected="selected">'.$label.'</option>';
				}else{
					echo '<option value="'.$i.'">'.$label.'</option>';
				}
			}
				
				
			echo '</select>';
			echo '</td></tr>';
			
		echo '<tr><td>';
			echo $langmessage['Expand Menu Below This Level'];
			echo '</td><td>';
			echo '<select name="expand_bound">';
			for($i=0;$i<=4;$i++){
				$label = $i;
				if( $i === 0 ){
					$label = '&nbsp;';
				}
				if( $i === $expand_bound ){
					echo '<option value="'.$i.'" selected="selected">'.$label.'</option>';
				}else{
					echo '<option value="'.$i.'">'.$label.'</option>';
				}
			}
			
			echo '</select>';
			echo '</td></tr>';
		
	}

	
	function AddContent(){
		global $langmessage;
		
		//message(showArray($_REQUEST));
		
		if( !isset($_REQUEST['where']) ){
			message($langmessage['OOPS']);
			return;
		}
		
		//prep destination
		if( !$this->GetValues($_REQUEST['where'],$to_container,$to_gpOutKey) ){
			message($langmessage['OOPS'].' (0)');
			return;
		}
		$handlers = $this->GetAllHandlers();
		$this->PrepContainerHandlers($handlers,$to_container,$to_gpOutKey);
		
	
		
		//figure out what we're inserting
		$addtype =& $_REQUEST['addtype'];
		switch($_REQUEST['addtype']){
			
			case 'new_extra':
				$extra_name = $this->NewExtraArea();
				if( $extra_name === false ){
					message($langmessage['OOPS'].'(2)');
					return false;
				}
				$insert = 'Extra:'.$extra_name;
			break;
			
			case 'custom_menu':
				$insert = $this->NewCustomMenu();
				message($insert);
			break;
			
			default:
				$insert = $_REQUEST['insert'];
			break;
			
			
		}
		
		
		//new info
		$new_gpOutInfo = gpOutput::GetgpOutInfo($insert);
		if( !$new_gpOutInfo ){
			message($langmessage['OOPS'].' (1)');
			return;
		}
		$new_gpOutKey = $new_gpOutInfo['key'].':'.$new_gpOutInfo['arg'];
		
		
		if( !$this->AddToContainer($handlers[$to_container],$to_gpOutKey,$new_gpOutKey,false) ){
			return;
		}
		
		$this->SaveHandlersNew($handlers);
	}
	
	//return the name of the cleansed extra area name, create file if it doesn't already exist
	function NewExtraArea(){
		global $dataDir,$langmessage;
		
		if( empty($_POST['extra_area']) ){
			return false;
		}
		
		$extra_name = gpFiles::CleanTitle($_POST['extra_area']);
		$extra_file = $dataDir.'/data/_extra/'.$extra_name.'.php';
		
		if( file_exists($extra_file) ){
			return $extra_name;
		}
		
		$text = '<div>'.htmlspecialchars($_POST['extra_area']).'</div>';
		if( !gpFiles::SaveFile($extra_file,$text) ){
			return false;
		}
		
		return $extra_name;
	}

	
	function Drag(){
		global $page,$gpOutConf,$langmessage;
				
		if( !$this->GetValues($_GET['dragging'],$from_container,$from_gpOutKey) ){
			message($langmessage['OOPS'].' (0)');
			return;
		}
		if( !$this->GetValues($_GET['to'],$to_container,$to_gpOutKey) ){
			message($langmessage['OOPS'].'(1)');
			return;
		}
		
		
		//prep work
		$handlers = $this->GetAllHandlers();
		$this->PrepContainerHandlers($handlers,$from_container,$from_gpOutKey);
		$this->PrepContainerHandlers($handlers,$to_container,$to_gpOutKey);
		
		
		//remove from from_container
		if( !isset($handlers[$from_container]) || !is_array($handlers[$from_container]) ){
			message($langmessage['OOPS'].' (2)');
			return;
		}
		$where = array_search($from_gpOutKey,$handlers[$from_container]);
		if( ($where === null) || ($where === false) ){
			message($langmessage['OOPS']. '(3)');
			return;
		}
		array_splice($handlers[$from_container],$where,1);
		
		
		if( !$this->AddToContainer($handlers[$to_container],$to_gpOutKey,$from_gpOutKey,false) ){
			return;
		}
		
		$this->SaveHandlersNew($handlers);
		
	}
	
	
	function RemoveArea(){
		global $langmessage;
		if( !$this->ParseHandlerInfo($_GET['param'],$curr_info) ){
			message($langmessage['OOPS'].' (0)');
			return;
		}
		$gpOutKey = $curr_info['gpOutKey'];
		$container = $curr_info['container'];

		
		//prep work
		$handlers = $this->GetAllHandlers();
		$this->PrepContainerHandlers($handlers,$container,$gpOutKey);
		
		
		//remove from $handlers[$container]
		$where = array_search($gpOutKey,$handlers[$container]);
		
		if( ($where === null) || ($where === false) ){
			message($langmessage['OOPS'].' (2)');
			return;
		}
		
		array_splice($handlers[$container],$where,1);
		$this->SaveHandlersNew($handlers);
		
	}
	
	
	function SelectLinks(){
		global $langmessage,$gpLayouts,$gpOutConf;
		
		$layout =& $_REQUEST['layout'];
		
		if( !isset($gpLayouts[$layout]) ){
			message($langmessage['OOPS'].' (0)');
			return;
		}
		
		if( !$this->ParseHandlerInfo($_GET['handle'],$curr_info) ){
			message($langmessage['00PS']);
			return;
		}
		
			
		$showCustom = false;
		$current_function = false;
		if( $curr_info['key'] == 'CustomMenu' ){
			$showCustom = true;
		}else{
			$current_function = $curr_info['key'];
		}
		
		
		echo '<div class="inline_box" style="width:30em">';
		
		echo '<div class="layout_links">';
		if( $showCustom ){
			echo ' <a href="#layout_menus" name="tabs">'. $langmessage['Link_Menus'] .'</a>';
			echo ' <a href="#layout_custom" name="tabs" class="selected">'. $langmessage['Custom Menu'] .'</a>';
		}else{
			echo ' <a href="#layout_menus" name="tabs" class="selected">'. $langmessage['Link_Menus'] .'</a>';
			echo ' <a href="#layout_custom" name="tabs">'. $langmessage['Custom Menu'] .'</a>';
		}
		echo '</div>';
		
		echo '<br/>';
		echo '<div id="area_lists">';
		
		//preset menus
			$style = '';
			if( $showCustom ){
				$style = ' style="display:none"';
			}
			echo '<div id="layout_menus" '.$style.'>';
				echo '<table class="bordered">';
					foreach($gpOutConf as $outKey => $info){
						
						if( !isset($info['link']) ){
							continue;
						}
						echo '<tr>';
							echo '<td>';
							
							if( $current_function == $outKey ){
								echo '<b>';
							}
							if( isset($langmessage[$info['link']]) ){
								echo str_replace(' ','&nbsp;',$langmessage[$info['link']]);
							}else{
								echo str_replace(' ','&nbsp;',$info['link']);
							}
							if( $current_function == $outKey ){
								echo '</b>';
							}
							
							echo '</td>';
							echo '<td class="add">';
							echo '<form action="'.common::GetUrl('Admin_Theme_Content').'" method="post">';
							echo '<input type="hidden" name="handle" value="'.htmlspecialchars($_GET['handle']).'" />';
							echo '<input type="hidden" name="return" value="" />';
							echo '<input type="hidden" name="layout" value="'.htmlspecialchars($layout).'" />';
							echo '<input type="hidden" name="cmd" value="savelinks" />';
							echo '<input type="hidden" name="new_handle" value="'.$outKey.'" />';
							if( $current_function == $outKey ){
								echo '<input type="submit" value="'.$langmessage['save'].'" disabled="disabled"/>';
							}else{
								echo '<input type="submit" value="'.$langmessage['save'].'" />';
							}
							echo '</form>';
							echo '</td>';
							echo '</tr>';
					}
				echo '</table>';
			
			echo '</div>';
		
		//custom menus
			$style = ' style="display:none"';
			if( $showCustom ){
				$style = '';
			}
			echo '<div id="layout_custom" '.$style.'>';
			echo '<form action="'.common::GetUrl('Admin_Theme_Content').'" method="post">';
			echo '<input type="hidden" name="handle" value="'.htmlspecialchars($_GET['handle']).'" />';
			echo '<input type="hidden" name="return" value="" />';
			echo '<input type="hidden" name="layout" value="'.htmlspecialchars($layout).'" />';
			echo '<input type="hidden" name="cmd" value="savelinks" />';
		
			$this->CustomMenuForm($curr_info['arg']);
			
			echo '<tr><td>';
				echo '&nbsp;';
				echo '</td><td>';
				echo '<input type="submit" class="submit" name="aaa" value="'.$langmessage['save'].'" /> ';
				echo '</td></tr>';
			echo '</table>';
			echo '</form>';
			
			echo '</div>';

			echo '<p class="admin_note">';
			echo $langmessage['see_also'];
			echo ' ';
			echo common::Link('Admin_Menu',$langmessage['file_manager']);
			echo ', ';
			echo common::Link('Admin_Theme_Content',$langmessage['content_arrangement']);
			echo '</p>';
		
		echo '</div>';
		echo '</div>';
		
	}
	

	function SaveLinks(){
		global $config,$langmessage,$gpOutConf,$gpLayouts;
		
		$layout =& $_POST['layout'];
		
		if( !isset($gpLayouts[$layout]) ){
			message($langmessage['OOPS'].' (0)');
			return;
		}
		
	
		if( !$this->ParseHandlerInfo($_POST['handle'],$curr_info) ){
			message($langmessage['OOPS'].' (0)');
			return;
		}
		
		
		if( isset($_POST['new_handle']) ){
			
			$new_gpOutKey = $_POST['new_handle'];
			if( !isset($gpOutConf[$new_gpOutKey]) || !isset($gpOutConf[$new_gpOutKey]['link']) ){
				message($langmessage['OOPS'].' (1)');
				return;
			}
			
		}else{
			$new_gpOutKey = $this->NewCustomMenu();
			
		}
		
		
		//prep
		$handlers = $this->GetAllHandlers($layout);
		$container =& $curr_info['container'];
		$this->PrepContainerHandlers($handlers,$container,$curr_info['gpOutKey']);
		

		if( !$this->AddToContainer($handlers[$container],$curr_info['gpOutKey'],$new_gpOutKey,true) ){
			return;
		}
		
		$this->SaveHandlersNew($handlers,$layout);
		
		
		//message('not forwarding');
		$this->ReturnHeader();
	}
	
	
	function ReturnHeader(){
		
		if( empty($_POST['return']) ){
			return;
		}
		
		$return = $_POST['return'];
		//$return = str_replace('cmd=','x=',$return); //some dynamic plugins rely on cmd to show specific pages.
		
		if( strpos($return,'http') == 0 ){
			header('Location: '.$return);
			die();
		}
			
		header('Location: '.common::GetUrl($_POST['return'],false));
		die();
	}


	
	function GetAddonTexts($addon){
		global $dataDir,$langmessage,$config;
		
		$addonDir = $dataDir.'/data/_addoncode/'.$addon;
		if( !is_dir($addonDir) ){
			return false;
		}
		
		//not set up correctly
		if( !isset($config['addons'][$addon]['editable_text']) ){
			return false;
		}
		
		$file = $addonDir.'/'.$config['addons'][$addon]['editable_text'];
		if( !file_exists($file) ){
			return false;
		}
		
		include($file);
		if( !isset($texts) || !is_array($texts) || (count($texts) == 0 ) ){
			return false;
		}
		
		return $texts;
	}
		
	
	function SaveAddonText(){
		global $dataDir,$langmessage,$config;
		
		$addon = gpFiles::CleanArg($_REQUEST['addon']);
		$texts = $this->GetAddonTexts($addon);
		//not set up correctly
		if( $texts === false ){
			message($langmessage['OOPS'].' (0)');
			return;
		}
		
		$configBefore = $config;
		foreach($texts as $text){
			if( !isset($_POST['values'][$text]) ){
				continue;
			}
			
			
			$default = $text;
			if( isset($langmessage[$text]) ){
				$default = $langmessage[$text];
			}
			
			$value = htmlspecialchars($_POST['values'][$text]);
			
			if( ($value === $default) || (htmlspecialchars($default) == $value) ){
				unset($config['customlang'][$text]);
			}else{
				$config['customlang'][$text] = $value;
			}
		}			
		
		if( !admin_tools::SaveConfig() ){
			//these two lines are fairly useless when the ReturnHeader() is used
			$config = $configBefore;
			message($langmessage['OOPS'].' (1)');
		}else{
			
			$this->UpdateAddon($addon);

			message($langmessage['SAVED']);
			
		}
		
		$this->ReturnHeader();
	}
	
	function UpdateAddon($addon){
		if( !function_exists('OnTextChange') ){
			return;
		}
			
		AddonTools::SetDataFolder($addon);
		
		OnTextChange();
		
		AddonTools::ClearDataFolder();
	}
	
	function AddonText(){
		global $dataDir,$langmessage,$config;
		
		$addon = gpFiles::CleanArg($_REQUEST['addon']);
		$texts = $this->GetAddonTexts($addon);
		
		//not set up correctly
		if( $texts === false ){
			$this->EditText();
			return;
		}
		
		
		echo '<div class="inline_box" style="text-align:right">';
		echo '<form action="'.common::GetUrl('Admin_Theme_Content').'" method="post">';
		echo '<input type="hidden" name="cmd" value="saveaddontext" />';
		echo '<input type="hidden" name="return" value="" />'; //will be populated by javascript
		echo '<input type="hidden" name="addon" value="'.htmlspecialchars($addon).'" />'; //will be populated by javascript
		
		
		$count = count($texts);
		if( $count > 5 ){
			
			echo '<table><tr><td>';
			$half = ceil($count/2);
			
			$out = array_slice($texts,0,$half);
			$this->AddonTextFields($out);
			echo '</td><td>';
			$out = array_slice($texts,$half);
			$this->AddonTextFields($out);
			echo '</td></tr>';
			echo '</table>';
			
		}else{
			$this->AddonTextFields($texts);
		}
		echo ' <input type="submit" class="submit" name="aaa" value="'.$langmessage['save'].'" />';
			
			
		echo '</form>';
		echo '</div>';
		
	}
	
	function AddonTextFields($array){
		global $langmessage,$config;
		echo '<table class="bordered">';
			echo '<tr>';
			echo '<th>';
			echo $langmessage['default'];
			echo '</th>';
			echo '<th>';
			echo '</th>';
			echo '</tr>';

		$key =& $_GET['key'];
		foreach($array as $text){
			
			$default = $value = $text;
			if( isset($langmessage[$text]) ){
				$default = $value = $langmessage[$text];
			}
			if( isset($config['customlang'][$text]) ){
				$value = $config['customlang'][$text];
			}
			
			$style = '';
			if( $text == $key ){
				$style = ' style="background-color:#f5f5f5"';
			}
			
			echo '<tr'.$style.'>';
			echo '<td>';
			echo $text;
			echo '</td>';
			echo '<td>';
			echo '<input type="text" class="text" name="values['.htmlspecialchars($text).']" value="'.$value.'" />'; //value has already been escaped with htmlspecialchars()
			echo '</td>';
			echo '</tr>';
			
		}
		echo '</table>';
	}
		
		

	
	
	function EditText(){
		global $config, $langmessage,$page;
		
		if( !isset($_GET['key']) ){
			message($langmessage['OOPS'].' (0)');
			return;
		}
		
		$default = $value = $key = $_GET['key'];
		if( isset($langmessage[$key]) ){
			$default = $value = $langmessage[$key];
			
		}
		if( isset($config['customlang'][$key]) ){
			$value = $config['customlang'][$key];
		}
		
		
		echo '<div class="inline_box">';
		echo '<form action="'.common::GetUrl('Admin_Theme_Content').'" method="post">';
		echo '<input type="hidden" name="cmd" value="savetext" />';
		echo '<input type="hidden" name="key" value="'.htmlspecialchars($key).'" />';
		echo '<input type="hidden" name="return" value="" />'; //will be populated by javascript
		
		echo '<table class="bordered">';
			echo '<tr>';
			echo '<th>';
			echo $langmessage['default'];
			echo '</th>';
			echo '<th>';
			echo '</th>';
			echo '</tr>';
			echo '<tr>';
			echo '<td>';
			echo $default;
			echo '</td>';
			echo '<td>';
			//$value is already escaped using htmlspecialchars()
			echo '<input type="text" class="text" name="value" value="'.$value.'" />';
			echo ' <input type="submit" class="submit" name="aaa" value="'.$langmessage['save'].'" />';
			echo '</td>';
			echo '</tr>';
		echo '</table>';
		
		echo '</form>';
		echo '</div>';
	}
	
		
	
	function SaveText(){
		global $config, $langmessage,$page;
		
		if( !isset($_POST['key']) ){
			message($langmessage['OOPS'].' (0)');
			return;
		}
		if( !isset($_POST['value']) ){
			message($langmessage['OOPS'].' (1)');
			return;
		}
		
		$default = $key = $_POST['key'];
		if( isset($langmessage[$key]) ){
			$default = $langmessage[$key];
		}
		
		$config['customlang'][$key] = $value = htmlspecialchars($_POST['value']);
		if( ($value === $default) || (htmlspecialchars($default) == $value) ){
			unset($config['customlang'][$key]);
		}
		
		if( admin_tools::SaveConfig() ){
			message($langmessage['SAVED']);
		}else{
			message($langmessage['OOPS'].' (s1)');
		}
		$this->ReturnHeader();
		
	}


}
