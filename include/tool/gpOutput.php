<?php
defined('is_running') or die('Not an entry point...');

//for output handlers, see admin_theme_content.php for more info
global $GP_ARRANGE,$gpOutConf,$gpOutStarted;

$gpOutStarted = false;
$GP_ARRANGE = true;
$gpOutConf = array();
$gpOutConf['FullMenu']['method']		= array('gpOutput','GetFullMenu');
$gpOutConf['FullMenu']['link']			= 'all_links';

$gpOutConf['ExpandMenu']['method']		= array('gpOutput','GetExpandMenu');
$gpOutConf['ExpandMenu']['link']		= 'expanding_links';

$gpOutConf['ExpandLastMenu']['method']	= array('gpOutput','GetExpandLastMenu');
$gpOutConf['ExpandLastMenu']['link']	= 'expanding_bottom_links';

$gpOutConf['Menu']['method']			= array('gpOutput','GetMenu');
$gpOutConf['Menu']['link']				= 'top_level_links';

$gpOutConf['SubMenu']['method']			= array('gpOutput','GetSubMenu');
$gpOutConf['SubMenu']['link']			= 'subgroup_links';

$gpOutConf['TopTwoMenu']['method']		= array('gpOutput','GetTopTwoMenu');
$gpOutConf['TopTwoMenu']['link']		= 'top_two_links';

$gpOutConf['BottomTwoMenu']['method']	= array('gpOutput','GetBottomTwoMenu');
$gpOutConf['BottomTwoMenu']['link']		= 'bottom_two_links';

$gpOutConf['MiddleSubMenu']['method']	= array('gpOutput','GetSecondSubMenu');
$gpOutConf['MiddleSubMenu']['link']		= 'second_sub_links';

$gpOutConf['BottomSubMenu']['method']	= array('gpOutput','GetThirdSubMenu');
$gpOutConf['BottomSubMenu']['link']		= 'third_sub_links';

$gpOutConf['CustomMenu']['method']		= array('gpOutput','CustomMenu');

$gpOutConf['Extra']['method']			= array('gpOutput','GetExtra');
//$gpOutConf['Text']['method']			= array('gpOutput','GetText'); //use Area() and GetArea() instead



class gpOutput{
	
	/* 
	 * 
	 * Request Type Functions
	 * functions used in conjuction with $_REQUEST['gpreq']
	 * 
	 */
	 
	function Prep(){
		global $page;
		if( !isset($page->rewrite_urls) ){
			return;
		}
		
		foreach($page->rewrite_urls as $key => $value){
			output_add_rewrite_var($key,$value);
		}
		
	}
	
	function Flush(){
		global $page;
		GetMessages();
		echo $page->contentBuffer;
	}
	
	function Content(){
		global $page;
		$page->GetContent();
		//echo '<div style="clear:both"></div>';
	}
	
	function BodyAsHTML(){
		global $page;
		echo '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">';
		echo '<html xml:lang="en" xmlns="http://www.w3.org/1999/xhtml" lang="en">';
		gpOutput::getHead();
		echo '<body>';
		$page->GetContent();
		echo '</body>';
		echo '</html>';
	}
	
	function Template(){
		global $dataDir,$page,$GP_ARRANGE;
		$themePath = $dataDir.'/themes/'.$page->theme_name.'/template.php';
		require_once($themePath);
	}
	
	
	/* 
	 * 
	 * Content Area Functions
	 * 
	 */

	
	/* static, deprecated V1.6 */
	function GetHandleIndex($name){
		global $gpOutConf;
		static $indeces = 0;
		
		if( !isset($gpOutConf[$name]) || !isset($gpOutConf[$name]['link']) ){
			return false;
		}
		
		$indeces++;
		return $indeces;
	}
	
	/* static */
	function GetContainerID($name){
		static $indices;
		if( !isset($indices[$name]) ){
			$indices[$name] = 0;
		}else{
			$indices[$name]++;
		}
		return $name.'_'.$indices[$name];
	}
	
	
	function Get($default,$arg=''){
		global $config,$langmessage,$page,$gpLayouts;
		
		
		//this shouldn't just be an integer..
		//	if someone is editing their theme, and moves handlers around, then these will get mixed up as well!
		$handle_index = gpOutput::GetHandleIndex($default); //deprecated V1.6
		$container_id = gpOutput::GetContainerID($default);
		
		
		$outSet = false;
		$outKeys = false;
		
		if( isset($gpLayouts[$page->gpLayout]) && isset($gpLayouts[$page->gpLayout]['handlers']) ){
			$handlers =& $gpLayouts[$page->gpLayout]['handlers'];
			
			//new method
			if( isset($handlers[$container_id]) ){
				
				$outKeys = $handlers[$container_id];
				$outSet = true;
			
			//old method
			}elseif( $handle_index && isset($handlers[$handle_index]) ){
				$function = $handlers[$handle_index];
				
				if( (substr($function,-4) == '_Out')  && (substr($function,0,3) === 'Get') ){
					$function = substr($function,3,-4);
				}
				
				$outKeys = array($function.':'.$arg);
				$outSet = true;
			}

			
		}
		
		//default values
		if( !$outSet ){
			$outKeys[] = $default.':'.$arg;
		}
		gpOutput::ForEachOutput($outKeys,$container_id);
	}
	
	function ForEachOutput($outKeys,$container_id){
		
		if( !is_array($outKeys) || (count($outKeys) == 0) ){
			$info = array();
			$info['gpOutKey'] = '';
			gpOutput::CallOutput($info,$container_id);
			return;
		}
		
		foreach($outKeys as $gpOutKey){
			
			$info = gpOutput::GetgpOutInfo($gpOutKey);
			if( $info === false ){
				trigger_error('gpOutKey <i>'.$gpOutKey.'</i> not set');
				continue;
			}
			$info['gpOutKey'] = $gpOutKey;
			gpOutput::CallOutput($info,$container_id);
		}
	}
	
	/* static */
	function GetgpOutInfo($key){
		global $gpOutConf,$config;
		
		$info = false;
		$arg = '';
		$pos = strpos($key,':');
		if( $pos > 0 ){
			$arg = substr($key,$pos+1);
			$key = substr($key,0,$pos);
		}
		
		
		if( isset($gpOutConf[$key]) ){
			$info = $gpOutConf[$key];
		}elseif( isset($config['gadgets'][$key]) ){
			$info = $config['gadgets'][$key];
		}else{
			return false;
		}
		$info['key'] = $key;
		$info['arg'] = $arg;
		return $info;
	}
	
	/* static */
	function GpOutLabel($key){
		global $gpOutConf,$langmessage;
		
		$info = gpOutput::GetgpOutInfo($key);
		
		$label = $key;
		if( isset($info['link']) && isset($langmessage[$info['link']]) ){
			$label = $langmessage[$info['link']];
		}
		return str_replace(array(' ','_',':'),array('&nbsp;','&nbsp;',':&nbsp;'),$label);
	}
	
	
	function CallOutput($info,$container_id){
		global $dataDir,$GP_ARRANGE,$page,$langmessage,$gpOutStarted,$GP_MENU_LINKS,$GP_MENU_CLASS;
		static $permission;
		$gpOutStarted = true;
		
		
		if( isset($info['disabled']) ){
			return;
		}
		
		//gpOutKey identifies the output function used, there can only be one 
		if( !isset($info['gpOutKey']) ){
			trigger_error('gpOutKey not set for $info in CallOutput()');
			return;
		}
		
		$empty_container = empty($info['gpOutKey']); //empty containers can't be removed and don't have labels
		$param = $container_id.'|'.$info['gpOutKey'];
		$class = 'gpArea_'.str_replace(':','_',trim($info['gpOutKey'],':'));
		$innerLinks = '';
		if( !isset($permission) ){
			$permission = common::LoggedIn() && admin_tools::HasPermission('Admin_Theme_Content');
		}
		
		
		//for theme content arrangement
		if( $GP_ARRANGE && $permission && isset($GLOBALS['GP_ARRANGE_CONTENT'])  ){
			$class .= ' output_area';
			
			$innerLinks .= '<div class="gplinks" style="display:none">';
			$innerLinks .= common::Link('Admin_Theme_Content',$param,'cmd=drag&layout='.urlencode($page->gpLayout).'&dragging='.urlencode($param).'&to=%s',' style="display:none" name="creq" class="dragdroplink"'); //drag-drop link
			if( !$empty_container ){
				$innerLinks .= '<div class="output_area_label">';
				$innerLinks .= ' '.gpOutput::GpOutLabel($info['gpOutKey']);
				$innerLinks .= '</div>';
			}
			$innerLinks .= '<div class="output_area_link">';
			if( !$empty_container ){
				$innerLinks .= ' '.common::Link('Admin_Theme_Content','Remove','cmd=rm&layout='.urlencode($page->gpLayout).'&param='.$param,' name="creq"');
			}
			$innerLinks .= ' '.common::Link('Admin_Theme_Content','Insert','cmd=insert&layout='.urlencode($page->gpLayout).'&param='.$param,' name="ajax_box"');
			$innerLinks .= '</div>';
			$innerLinks .= '</div>';
			
		}
		$GP_ARRANGE = true;
		
		
		//editable links only .. other editable_areas are handled by their output functions
		if( $permission ){
			$marker = false;
			if( isset($info['link']) ){
				$label = $langmessage[$info['link']];
				$class .=  ' editable_area';
				$innerLinks .= common::Link('Admin_Theme_Content',$langmessage['edit'],'cmd=editlinks&layout='.urlencode($page->gpLayout).'&handle='.$param,' class="ExtraEditLink" style="display:none" rel="links" name="ajax_box" title="'.$label.'" ');
				$marker = true;
			}elseif( isset($info['key']) && ($info['key'] == 'CustomMenu') ){
				$class .=  ' editable_area';
				$innerLinks .= common::Link('Admin_Theme_Content',$langmessage['edit'],'cmd=editcustom&layout='.urlencode($page->gpLayout).'&handle='.$param,' class="ExtraEditLink"  style="display:none" rel="links" name="ajax_box" title="'.$langmessage['Links'].'" ');
				$marker = true;
			}
			
			//for menu arrangement, admin_menu.js
			if( $marker ){
				echo '<div class="menu_marker" style="display:none">';
				echo '<input type="hidden" value="'.htmlspecialchars($info['gpOutKey']).'" />';
				echo '<input type="hidden" value="'.htmlspecialchars($GP_MENU_LINKS).'" />';
				echo '<input type="hidden" value="'.htmlspecialchars($GP_MENU_CLASS).'" />';
				echo '</div>';
				//echo '<a class="menu_marker" name="'.$info['gpOutKey'].'" style="display:none">'.htmlspecialchars($GP_MENU_LINKS).'</a>';
			}

		}


		echo '<div class="'.$class.' GPAREA">';
		echo $innerLinks;
		

		if( isset($info['addon']) ){
			AddonTools::SetDataFolder($info['addon']);
		}
		
		$empty = true;
		if( isset($info['script']) ){
			if( file_exists($dataDir.$info['script']) ){
				require($dataDir.$info['script']);
				$empty = false;
			}
		}
		
		if( isset($info['data']) ){
			if( file_exists($dataDir.$info['data']) ){
				require($dataDir.$info['data']);
				$empty = false;
			}
		}
		
		if( isset($info['class']) ){
			if( class_exists($info['class']) ){
				new $info['class'](); //should $arg and $info be passed to class
				$empty = false;
			}
		}
		
		if( isset($info['method']) ){
			$info += array('arg'=>'');
			call_user_func($info['method'],$info['arg'],$info);
			$empty = false;
		}
		
		if( $empty && common::LoggedIn() ){
			echo '&nbsp;';
		}
		
		AddonTools::ClearDataFolder();
		
		echo '</div>';
	}
	
	
	
	
	
	/* the issue here involves moving gadgets out, reorganizing gadgets and installing new gadgets 
	How do we detect a newly installed gadget after an organized list has been created?
	.. do we add it to the list when the addon is installed? remove when uninstalled?
			.. still going to have to remove from the lists they're uninstalled
	*/
	
	function GetAllGadgets(){
		global $config,$page,$gpLayouts;
		
		if( !isset($config['gadgets']) ){
			return;
		}
		
		$list = false;
		if( isset($gpLayouts[$page->gpLayout]['handlers']['GetAllGadgets']) ){
			gpOutput::ForEachOutput($gpLayouts[$page->gpLayout]['handlers']['GetAllGadgets'],'GetAllGadgets');
			
		}else{
		
			foreach($config['gadgets'] as $gadget => $info){
				
				if( isset($info['addon']) ){
					$info['gpOutKey'] = $gadget;
					gpOutput::CallOutput($info,'GetAllGadgets');
				}
			}
		}
	}
	
	
	/* 
	 * 
	 * @param string $arg comma seperated argument list: $top_level, $bottom_level, $options
	 *		$top_level  (int)  The upper level of the menu to show, if deeper (in this case > ) than 0, only the submenu is shown
	 *		$bottom_level  (int)  The lower level of menu to show
	 *		$expand_level (int)  The upper level from where to start expanding sublinks, if -1 no expansion
	 * 
	 */
	function CustomMenu($arg,$title=false){
		global $gpmenu,$page;
		
		//from output functions
		if( is_array($title) ){
			$title = $page->title;
		}
		
		$args = explode(',',$arg);
		$args += array( 0=>0, 1=>3, 2=>-1 ); //defaults
		list($top_level,$bottom_level,$expand_level) = $args;
		
		$menu = $gpmenu;

		//Reduce for expansion
		//first reduction
		if( (int)$expand_level >= 1 ){
			//message('expand level: '.$expand_level);
			$menu = gpOutput::MenuReduce_Expand($menu,$expand_level,$title,$top_level);
		}
		
		
		//Reduce if $top_level >= 0
		//second reduction
		if( (int)$top_level > 0 ){
			//message('top: '.$top_level);
			$menu = gpOutput::MenuReduce_Top($menu,$top_level,$title);
		}else{
			$top_level = 0;
		}
		
		//Reduce by trimming off titles below $bottom_level
		// last reduction : in case the selected link is below $bottom_level
		if( $bottom_level > 0 ){
			//message('bottom: '.$bottom_level);
			$menu = gpOutput::MenuReduce_Bottom($menu,$bottom_level);
		}
		
		//echo '<hr/>';
		gpOutput::OutputMenu($menu,$top_level);
		//echo '<hr/>';
		
	}
	
	

	
	//Reduce titles deeper than $expand_level || $current_level
	function MenuReduce_Expand($menu,$expand_level,$curr_title,$top_level){
		global $page;
		$result_menu = array();
		$submenu = array();
		$foundGroup = 0;
		
		if( $curr_title == false ){
			$curr_title = $page->title;
		}
		
		//if $top_level is set, we need to take it into consideration
		$expand_level = max( $expand_level, $top_level);
		
		//titles higher than the $expand_level
		$good_titles = array();
		foreach($menu as $title => $level){
			if( $level < $expand_level ){
				$good_titles[$title] = $level;
			}
		}
		
		
		if( isset($menu[$curr_title]) ){
			$curr_level = $menu[$curr_title];
			$good_titles[$curr_title] = $menu[$curr_title];
			
			
			//titles below selected
			// cannot use $submenu because $foundTitle may require titles above the $submenu threshold
			$foundTitle = false;
			foreach($menu as $title => $level){
				
				if( $title == $curr_title ){
					$foundTitle = true;
					continue;
				}
				
				if( !$foundTitle ){
					continue;
				}
				
				if( ($curr_level+1) == $level ){
					$good_titles[$title] = $level;
				}elseif( $curr_level < $level ){
					continue;
				}else{
					break;
				}
			}

			//$start_time = microtime();
			//reduce the menu to the current group
			$submenu = gpOutput::MenuReduce_Group($menu,$curr_title,$expand_level,$curr_level);
			//message('group: ('.count($submenu).') '.showArray($submenu));
			
			
			// titles even-with selected title within group
			$even_temp = array();
			$even_group = false;
			foreach($submenu as $title => $level){
				
				if( $title == $curr_title ){
					$even_group = true;
					$good_titles = $good_titles + $even_temp;
					continue;
				}
				
				if( $level < $curr_level ){
					if( $even_group ){
						$even_group = false; //done
					}else{
						$even_temp = array(); //reset
					}
				}
				
				if( $level == $curr_level ){
					if( $even_group ){
						$good_titles[$title] = $level;
					}else{
						$even_temp[$title] = $level;
					}
				}				
			}
			
			
			// titles above selected title, deeper than $expand_level, and within the group
			gpOutput::MenuReduce_Sub($good_titles,$submenu,$curr_title,$expand_level,$curr_level);
			gpOutput::MenuReduce_Sub($good_titles,array_reverse($submenu),$curr_title,$expand_level,$curr_level);
			
			//message('time: '.microtime_diff($start_time,microtime()));

		}

		
		
		//rebuild $good_titles in order
		// array_intersect_assoc() would be useful here, it's php4.3+ and there's no indication if the order of the first argument is preserved
		foreach($menu as $title => $level){
			if( isset($good_titles[$title]) ){
				$result_menu[$title] = $level;
			}
		}
		
		return $result_menu;
		
	}
	
	// reduce the menu to the group
	function MenuReduce_Group($menu,$curr_title,$expand_level,$curr_level){
		$result = array();
		$group_temp = array();
		$found_title = false;
		
		foreach($menu as $title => $level){
			
			//back at the top
			if( $level < $expand_level ){
				$group_temp = array();
				$found_title = false;
			}
			
			
			if( $title == $curr_title ){
				$found_title = true;
				$result = $group_temp;
			}
			
			if( $level >= $expand_level ){
				if( $found_title ){
					$result[$title] = $level;
				}else{
					$group_temp[$title] = $level;
				}
			}
		}
		
		return $result;
	}
	
	// titles above selected title, deeper than $expand_level, and within the group
	function MenuReduce_Sub(&$good_titles,$menu,$curr_title,$expand_level,$curr_level){
		$found_title = false;
		$test_level = $curr_level;
		foreach($menu as $title => $level){
			
			if( $title == $curr_title ){
				$found_title = true;
				$test_level = $curr_level;
				continue;
			}
			
			//after the title is found
			if( !$found_title ){
				continue;
			}
			if( $level < $expand_level ){
				break;
			}
			if( ($level >= $expand_level) && ($level < $test_level ) ){
				$test_level = $level+1; //prevent showing an adjacent menu trees
				$good_titles[$title] = $level;
			}
		}
	}
	
	//Reduce the menu to titles deeper than ($show_level-1)
	function MenuReduce_Top($menu,$show_level,$curr_title){
		$result_menu = array();
		$foundGroup = false;
		
		//current title not in menu, so there won't be a submenu
		if( !isset($menu[$curr_title]) ){
			return $result_menu;
		}
		
		$top_level = $show_level-1;
		
		foreach($menu as $title => $level){
			
			//no longer in subgroup, we can stop now
			if( $foundGroup && ($level <= $top_level) ){
				//message('no long in subgroup: '.$title);
				break;
			}
				
			if( $title == $curr_title ){
				//message('found: '.$title);
				$foundGroup = true;
			}
			
			//we're back at the $top_level, start over
			if( $level <= $top_level ){
				$result_menu = array();
				//message('start over: '.$title);
				//message('start over: '.showArray($result_menu));
				continue;
			}
			
			//we're at the correct level, put titles in $result_menu in case $page->title is found
			if( $level > $top_level ){
				$result_menu[$title] = $level;
			}
		}
		
		if( !$foundGroup ){
			return array();
		}
		
		return $result_menu;
	}
	

	//Reduce the menu to titles above $bottom_level value
	function MenuReduce_Bottom($menu,$bottom_level){
		$result_menu = array();
		foreach($menu as $title => $level){
			if( $level < $bottom_level ){
				$result_menu[$title] = $level;
			}
		}
		return $result_menu;
	}
	
	
	function GetExtra($name='Side_Menu'){
		global $dataDir,$langmessage,$page;
		
		$wrap =  common::LoggedIn() && admin_tools::HasPermission('Admin_Extra');
		
		if( $wrap ){
			echo '<div class="editable_area" >'; // class="edit_area" added by javascript
			echo common::Link('Admin_Extra',$langmessage['edit'],'cmd=edit&file='.$name,' class="ExtraEditLink" style="display:none" title="'.$name.'" ');
		}
		
		$file = $dataDir.'/data/_extra/' . $name . '.php';
		if( file_exists($file) ){
			include($file);
		}
		
		if( $wrap ){
			echo '</div>';
		}
	}


	
	function GetFullMenu(){
		global $gpmenu;
		gpOutput::OutputMenu($gpmenu,0);
	}
	
	function GetMenu(){
		global $gpmenu;
		
		$sendMenu = array();
		foreach($gpmenu as $title => $level){
			if( (int)$level !== 0 ){
				continue;
			}
			$sendMenu[$title] = $level;
		}
		gpOutput::OutputMenu($sendMenu,0);
		
	}
	
	function GetSecondSubMenu(){
		gpOutput::GetSubMenu(1);
	}
	function GetThirdSubMenu(){
		gpOutput::GetSubMenu(2);
	}
	
	function GetSubMenu($search_level=false){
		global $gpmenu,$page;
		
		$reset_level = 0;
		if( !empty($search_level) ){
			$reset_level = max(0,$search_level-1);
		}
		
		$menu = array();
		$foundGroup = false;
		foreach($gpmenu as $title => $level){
			if( $foundGroup ){
				if( $level <= $reset_level ){
					break;
				}
			}
				
			if( $title == $page->title ){
				$foundGroup = true;
			}
			
			if( $level <= $reset_level ){
				$menu = array();
				continue;
			}
			
			if( empty($search_level) ){
				$menu[$title] = $level;
			}elseif( $level == $search_level ){
				$menu[$title] = $level;
			}
		}
		
		if( !$foundGroup ){
			gpOutput::OutputMenu(array(),$reset_level+1);
		}else{
			gpOutput::OutputMenu($menu,$reset_level+1);
		}
	}
	
	function GetTopTwoMenu(){
		global $gpmenu;
		
		$sendMenu = array();
		foreach($gpmenu as $title => $level){
			if( $level >= 2 ){
				continue;
			}
			$sendMenu[$title] = $level;
		}
		gpOutput::OutputMenu($sendMenu,0);
	}
	
	function GetBottomTwoMenu(){
		global $gpmenu;
		
		$sendMenu = array();
		foreach($gpmenu as $title => $level){
			if( ($level == 1) || ($level == 2) ){
				$sendMenu[$title] = $level;
			}
		}
		gpOutput::OutputMenu($sendMenu,1);
	}
	
	function GetExpandLastMenu(){
		global $gpmenu,$page;
		
		$menu = array();
		$submenu = array();
		$foundGroup = false;
		foreach($gpmenu as $title => $level){
			
			if( ($level == 0) || ($level == 1) ){
				$submenu = array();
				$foundGroup = false;
			}
			
			if( $title == $page->title ){
				$foundGroup = true;
				$menu = $menu + $submenu; //not using array_merge because of numeric indexes
			}
			
			
			if( $foundGroup ){
				$menu[$title] = $level;
			}elseif( ($level == 0) || ($level == 1) ){
				$menu[$title] = $level;
			}else{
				$submenu[$title] = $level;
			}
		}
		
		gpOutput::OutputMenu($menu,0);
	}
	
	function GetExpandMenu(){
		global $gpmenu,$page;

		$menu = array();
		$submenu = array();
		$foundGroup = false;
		foreach($gpmenu as $title => $level){
			
			if( $level == 0 ){
				$submenu = array();
				$foundGroup = false;
			}
			
			if( $title == $page->title ){
				$foundGroup = true;
				$menu = $menu + $submenu; //not using array_merge because of numeric indexes
			}
			
			
			
			if( $foundGroup ){
				$menu[$title] = $level;
			}elseif( $level == 0 ){
				$menu[$title] = $level;
			}else{
				$submenu[$title] = $level;
			}
		}
		
		gpOutput::OutputMenu($menu,0);
	}

	
	function OutputMenu($menu,$startLevel){
		global $langmessage,$gpmenu,$page,$GP_MENU_LINKS,$GP_MENU_CLASS,$gptitles;
		
		$search = array('{$href_text}','{$attr}','{$label}','{$title}');
		$replace = array();
		
		if( count($menu) == 0 ){
			echo '<div class="emtpy_menu"></div>'; //an empty <ul> is not valid xhtml
			gpOutput::ResetMenuGlobals();
			return;
		}
		
		$rmenu = array_reverse( $gpmenu, true );
		$childselected = false;
		$selectedLevel = false;
		$result = array();
		$prevLevel = $startLevel;
		$open = false;
		
		$result[] = "\n\n";
		$result[] = '</ul>';
		foreach($rmenu as $title => $thisLevel){
			$class = '';
			
			//create link if in $menu
			if( isset($menu[$title]) ){
				
				$title_info = $gptitles[$title];
				
				//classes
				if( $thisLevel < $prevLevel){
					$class .= 'haschildren ';
				}
				if( $title == $page->title ){
					$class .= 'selected ';
				}elseif( $childselected && ($thisLevel < $selectedLevel) ){
					$class .= 'childselected ';
					$selectedLevel = $thisLevel;
				}
			
				if( !$open ){
					$result[] = '</li>';
				}
				
				if( $thisLevel < $prevLevel ){
					
					while( $thisLevel < $prevLevel ){
						$result[] = '<ul><li>';
						$prevLevel--;
					}
					
				}elseif( $thisLevel > $prevLevel ){
					
					if( $open ){
						$result[] = '</li><li>';
					}
					
					while( $thisLevel > $prevLevel ){
						$result[] = '</li></ul>';
						$prevLevel++;
					}
					
				}elseif( $open ){
					$result[] = '</li><li>';
				}
				
				$label = common::GetLabel($title,false);
				if( !empty($class) ){
					$class = 'class="'.$class.'" ';
				}
				
				if( isset($title_info['browser_title']) ){
					$browser_title = $title_info['browser_title'];
				}else{
					$browser_title = htmlspecialchars($label); //for ampersands
				}
				
				if( !empty($GP_MENU_LINKS) ){
					$replace = array();
					$replace[] = common::GetUrl($title);
					$replace[] = $class;
					$replace[] = $label;
					$replace[] = $browser_title;;
					
					$result[] = str_replace($search,$replace,$GP_MENU_LINKS);
				}else{
					
					$result[] = common::Link($title,$label,'',$class.' title="'.$browser_title.'" ');
				}
				
				$prevLevel = $thisLevel;
				$open = true;
				
			}

				
			
			//set information for following links
			if( $title == $page->title ){
				$childselected = true;
				$selectedLevel = $thisLevel;
			}
			
			if( $thisLevel == 0 ){
				$childselected = false;
			}
		}
		
		//finish it off
		while( $prevLevel >= $startLevel ){
			$result[] = '<ul><li>';
			$prevLevel--;
		}
		
		//make sure the top is labeled
		if( count($result) > 1 ){
			if( !empty($GP_MENU_CLASS) ){
				$result[count($result)-1] = '<ul class="'.$GP_MENU_CLASS.'"><li>';
			}else{
				$result[count($result)-1] = '<ul class="menu_top"><li>';
			}
		}
		
		$result[] = "\n";
		
		$result = array_reverse( $result);
		echo implode("\n",$result);
		gpOutput::ResetMenuGlobals();
	}
	
	function ResetMenuGlobals(){
		global $GP_MENU_LINKS,$GP_MENU_CLASS;
		$GP_MENU_LINKS = '';
		$GP_MENU_CLASS = '';
		unset($GP_MENU_LINKS);
		unset($GP_MENU_CLASS);
	}
	
	
	
	
	/* 
	 * 
	 * Output Additional Areas
	 * 
	 */
	
	/* draggable html and editable text */
	function Area($name,$html){
		global $gpOutConf,$gpOutStarted;
		if( $gpOutStarted ){
			trigger_error('gpOutput::Area() must be called before all other output functions');
			return;
		}
		$name = '[text]'.$name;
		$gpOutConf[$name] = array();
		$gpOutConf[$name]['method'] = array('gpOutput','GetAreaOut');
		$gpOutConf[$name]['html'] = $html;
	}
	
	function GetArea($name,$text){
		$name = '[text]'.$name;
		gpOutput::Get($name,$text);
	}
	
	function GetAreaOut($text,$info){
		global $config,$langmessage,$page;
		$name = substr($info['key'],5); //remove the "text:"
		$html =& $info['html'];
		
		$wrap = common::LoggedIn() && admin_tools::HasPermission('Admin_Theme_Content');
		if( $wrap ){
			echo '<div class="editable_area" >'; // class="edit_area" added by javascript
			echo common::Link('Admin_Theme_Content',$langmessage['edit'],'cmd=edittext&key='.urlencode($text).'&return='.$page->title,' class="ExtraEditLink" style="display:none" title="'.urlencode($text).'" name="ajax_box" ');
		}
		
		if( isset($config['customlang'][$text]) ){
			$text = $config['customlang'][$text];
			
		}elseif( isset($langmessage[$text]) ){
			$text =  $langmessage[$text];
		}
		
		echo str_replace('%s',$text,$html); //in case there's more than one %s
		
		if( $wrap ){
			echo '</div>';
		}
	}
	
	/* 
	 * 
	 * editable text, not draggable
	 * 
	 */
	
	/* similar to ReturnText() but links to script for editing all addon texts */
	// the $html parameter should primarily be used when the text is to be placed inside of a link or other element that cannot have a link and/or span as a child node
	function GetAddonText($key,$html='%s'){
		global $addonFolderName;
		
		if( !$addonFolderName ){
			return gpOutput::ReturnText($key,$html);
		}
		
		$query = 'cmd=addontext&addon='.urlencode($addonFolderName).'&key='.urlencode($key);
		return gpOutput::ReturnTextWorker($key,$html,$query);
	}
	
	/* deprecated, use ReturnText() */
	function GetText($key,$html='%s'){
		echo gpOutput::ReturnText($key,$html);
	}
	
	function ReturnText($key,$html='%s'){
		$query = 'cmd=edittext&key='.urlencode($key);
		return gpOutput::ReturnTextWorker($key,$html,$query);
	}
	
	function ReturnTextWorker($key,$html,$query){
		global $langmessage;
		
		$result = '';
		$wrap = common::LoggedIn() && admin_tools::HasPermission('Admin_Theme_Content');
		if( $wrap ){
			$result .= '<span class="editable_area" >'; // class="edit_area" added by javascript
			
			$title = htmlspecialchars(strip_tags($key));
			if( strlen($title) > 20 ){
				$title = substr($title,0,20).'...'; //javscript may shorten it as well
			}
			$result .= common::Link('Admin_Theme_Content',$langmessage['edit'],$query,' class="ExtraEditLink" style="display:none" title="'.$title.'" name="ajax_box" ');
		}
		
		$text = gpOutput::SelectText($key);
		$result .= str_replace('%s',$text,$html); //in case there's more than one %s
		
		if( $wrap ){
			$result .= '</span>';
		}
		
		return $result;
		
	}
		
		
	
	
	function SelectText($key){
		global $config,$langmessage;
		
		$text = $key;
		if( isset($config['customlang'][$key]) ){
			$text = $config['customlang'][$key];
			
		}elseif( isset($langmessage[$key]) ){
			$text = $langmessage[$key];
		}
		return $text;
	}
	
	
	/*
	 * 
	 * 
	 * 
	 */
	
	
	function GetHead() {
		global $config, $wbMessageBuffer,$page,$gpAdmin;
		
		echo "\n<!-- section start -->";
		
		if( common::LoggedIn() ){
			common::AddColorBox();
		}
		
		//start keywords;
		$keywords = array();
		if( !empty($page->TitleInfo['keywords']) ){
			$keywords += explode(',',$page->TitleInfo['keywords']);
		}
		
		
		//title
		echo "\n<title>";
		if( !empty($page->TitleInfo['browser_title']) ){
			$page_title = $page->TitleInfo['browser_title'];
			$keywords[] = $page->TitleInfo['browser_title'];
		}else{
			$page_title = htmlspecialchars($page->label);
		}
		echo $page_title;
		if( !empty($page_title) && !empty($config['title']) ){
			echo ' - ';
		}
		echo $config['title'].'</title>';
		echo "\n<meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\" />";
		
		
		//keywords
		$keywords[] = htmlspecialchars($page->label);
		$keywords += explode(',',$config['keywords']);
		$keywords = array_unique($keywords);
		$keywords = array_diff($keywords,array(''));
		echo "\n<meta name=\"keywords\" content=\"".implode(', ',$keywords)."\" />";
		
		
		//description
		$description = '';
		if( !empty($page->TitleInfo['description']) ){
			$description .= $page->TitleInfo['description'].' ';
		}
		if( !empty($config['desc']) ){
			$description .= htmlspecialchars($config['desc']);
		}
		if( !empty($description) ){
			echo "\n<meta name=\"description\" content=\"".$description."\" />";
		}
		
		
		echo "\n<meta name=\"generator\" content=\"gpEasy CMS\" />";

		//use local copy unless specified otherwise
		if( isset($config['jquery']) && $config['jquery'] == 'google' ){
			echo "\n<script src=\"http://ajax.googleapis.com/ajax/libs/jquery/1.4.2/jquery.min.js\" type=\"text/javascript\"></script>";
		}else{
			echo "\n<script src=\"".common::GetDir('/include/js/jquery.1.4.2.js')."\" type=\"text/javascript\"></script>";
		}
		
		echo "\n<script type=\"text/javascript\">\n/* <![CDATA[ */\n";
		echo 'var gplinks=[]';
		echo ',gpinputs=[]';
		echo ',gpresponse=[]';
		echo ',IE7=false';
		if( common::LoggedIn() ){
			echo ',isadmin=true';
			echo ',paneldock='.($gpAdmin['paneldock'] ? 'true' : 'false');
			echo ',panelposx='.$gpAdmin['panelposx'];
			echo ',panelposy='.$gpAdmin['panelposy'];
		}else{
			echo ',isadmin=false';
		}
		echo ',gp_ckconfig={},gpBase="'.common::GetDir_Prefixed('').'";';
		
		
		//before main.js (example: theme_content.js)
		if( !empty($page->jQueryCode) ){
			echo "\n\n";
			echo '$(function(){';
			echo '$("body").bind("gpReady",function(){';
			echo $page->jQueryCode;
			echo '});';
			echo '});';
			echo "\n\n";
			$page->admin_js = true;
		}
		
		echo $page->head_script;
		echo "\n/* ]]> */\n</script>";
		
		echo "\n<!--[if IE 7]>\n<script type=\"text/javascript\">IE7=true;</script>\n<![endif]-->";
		
		echo "\n<link rel=\"stylesheet\" type=\"text/css\" href=\"".common::GetDir('/include/css/additional.css?v='.$GLOBALS['gpversion'])."\" />";
		
		
		//gadget info
		if( !empty($config['addons']) ){
			foreach($config['addons'] as $addon_info){
				if( !empty($addon_info['html_head']) ){
					echo "\n";
					echo $addon_info['html_head'];
				}
			}
		}

		if( $page->admin_js ){
			echo "\n<script type=\"text/javascript\" src=\"".common::GetDir('/include/js/main.js?v='.$GLOBALS['gpversion'])."\"></script>";
		}
		
		if( !empty($page->head) ){
			echo $page->head;
		}
		
		
		//after other styles, so themes can overwrite defaults
		if( !empty($page->theme_name) ){
			echo "\n<link rel=\"stylesheet\" type=\"text/css\" href=\"".common::GetDir('/themes/'.$page->theme_name.'/'.$page->theme_color.'/style.css')."\" />";
		}
		
		
		//important admin css that shouldn't be overwritten by themes
		if( common::LoggedIn() ){
			echo "\n<link rel=\"stylesheet\" type=\"text/css\" href=\"".common::GetDir('/include/css/admin.css?v='.$GLOBALS['gpversion'])."\" />";
			echo "\n<script type=\"text/javascript\" src=\"".common::GetDir('/include/js/admin.js?v='.$GLOBALS['gpversion'])."\"></script>";
			
			if( !empty($page->admin_css) ){
				echo "\n";
				echo $page->admin_css; //styles that need to override admin.css should be added to $page->admin_css;
			}
		}
		echo "\n<!-- section end -->\n";
	}
	
	function gpLink(){
		global $config, $out, $langmessage,$page;
		
		if( empty($config['hidegplink'])){
			return true;
		}
		
		return gpOutput::DetectBot();
	}
	
	function DetectBot(){
		$tests[] = 'googlebot';
		$tests[] = 'yahoo! slurp';
		$tests[] = 'msnbot';
		$tests[] = 'ask jeeves';
		$tests[] = 'ia_archiver';
		$tests[] = 'bot';
		$tests[] = 'spider';
		
		$agent =& $_SERVER['HTTP_USER_AGENT'];
		$agent = strtolower($agent);
		$agent = str_replace($tests,'GP_FOUND_SPIDER',$agent);
		if( strpos($agent,'GP_FOUND_SPIDER') === false ){
			return false;
		}
		return true;		
	}
		
	
	// displays the login/logout link
	function GetAdminLink(){
		global $config, $out, $langmessage,$page;
		
		echo ' <span class="sitemap_link">';
		echo common::Link('Special_Site_Map',$langmessage['site_map']);
		echo '</span>';
		
		echo ' <span class="login_link">';
			if( common::LoggedIn() ){
				echo common::Link($page->title,$langmessage['logout'],'cmd=logout',' rel="nofollow" ');
			}else{
				echo common::Link('Admin_Main',$langmessage['login'],'file='.$page->title,' rel="nofollow"');
			}
		echo '</span>';
		
		if( gpOutput::gpLink() ){
			echo ' <span class="powered_by_link">';
			echo $config['linkto'];
			echo '</span>';
		}
		
		if( common::LoggedIn() ){
			admin_tools::AdminHtml();
		}

	}
	
	
	function GetLastHook($hook,$args=array()){
		global $config;
		
		if( !isset($config['hooks']) || !isset($config['hooks'][$hook]) ){
			return false;
		}
		
		$hook_info = end($config['hooks'][$hook]);
		
		if( isset($hook_info['addonDir']) ){
			AddonTools::SetDataFolder($hook_info['addonDir']);
		}
		
		$result = gpOutput::ExecHook($hook_info,$args);
		
		AddonTools::ClearDataFolder();
		
		return $result;
		
	}
	
	function ExecHook($hook_info,$args){
		global $dataDir;
		if( !isset($hook_info['script']) || !file_exists($dataDir.$hook_info['script']) ){
			return false;
		}
		
		include_once($dataDir.$hook_info['script']);

		if( isset($hook_info['method']) ){
			return call_user_func_array($hook_info['method'],$args);
		}
		
		return false;
	}
	
	

	
}
