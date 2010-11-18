<?php
defined('is_running') or die('Not an entry point...');


class special_display extends display{
	var $pagetype = 'special_display';
	var $requested = false;
	var $scripts = array();
	
	var $editable_content = false;
	var $editable_details = false; //true; //could be true

	function special_display($title){
		global $langmessage,$config,$gptitles;
		
		$this->requested = $title;
		$this->title = $title;
		$this->label = 'Special';
		$this->SetTheme();
		
		
		$this->scripts['Special_Site_Map']['script'] = '/include/special/special_map.php';
		$this->scripts['Special_Site_Map']['class'] = 'special_map';

		$this->scripts['Special_Galleries']['script'] = '/include/special/special_galleries.php';
		$this->scripts['Special_Galleries']['class'] = 'special_galleries';

		$this->scripts['Special_Contact']['script'] = '/include/special/special_contact.php';
		$this->scripts['Special_Contact']['class'] = 'special_contact';
		
		$this->scripts['Special_Missing']['script'] = '/include/special/special_missing.php';
		$this->scripts['Special_Missing']['class'] = 'special_missing';
	}
	
	function RunScript(){
		global $langmessage,$dataDir,$gptitles;
		
		
		
		$scriptinfo = false;
		if( isset($this->scripts[$this->requested]) ){
			
			$scriptinfo = $this->scripts[$this->requested];
			
		}elseif( isset($gptitles[$this->requested]) ){
			$scriptinfo = $gptitles[$this->requested];
			
			
			if( isset($scriptinfo['addon']) ){
				if( !file_exists($dataDir.$scriptinfo['script']) ){
					$scriptinfo = false;
				}else{
					AddonTools::SetDataFolder($scriptinfo['addon']);
				}
			}
		}
			
		if( $scriptinfo === false ){
			$this->Error_404($this->title);
			return;
		}

			
			
		ob_start();
		
		$this->label = common::GetLabel($this->requested);
		$this->TitleInfo = $scriptinfo;
		
		if( isset($scriptinfo['script']) ){
			require($dataDir.$scriptinfo['script']);
		}
		if( isset($scriptinfo['class']) ){
			new $scriptinfo['class'](); //not passing any args to class, this is being used by special_missing.php
		}
		AddonTools::ClearDataFolder();
		
		$this->contentBuffer = ob_get_clean();
	}
	
	
	function GetContent(){
		echo '<div id="gpx_content">';
		GetMessages();
		echo $this->contentBuffer;
		echo '</div>';
	}

}
