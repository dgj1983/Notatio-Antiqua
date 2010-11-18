<?php
defined('is_running') or die('Not an entry point...');


class special_map{
	function special_map(){
		global $page,$langmessage,$config;
		
		
		/*
		An xml site map will not show any of the pages from dynamic add-ons
		... which is precisely what the regular sitemap shows
		*/
		
		if( isset($_GET['xml']) ){
			$this->xml();
			return;
		}
			
		$this->MultiSiteData();

		echo '<div class="sitemap_xml">';
		echo common::Link('Special_Site_Map','XML','xml');
		echo '</div>';
		echo '<h2>';
		echo gpOutput::ReturnText('site_map');
		echo '</h2>';
		

		
		gpOutput::GetFullMenu();
		
		
		if( gpOutput::gpLink() ){
			echo '<br/>';
			
			echo '<div class="siteinfo">';
			echo '<h3>Site Info</h3>';
			echo '<p>';
			echo 'Powered by <a href="http://www.gpeasy.com" title="The Fast and Easy CMS">gp|Easy CMS</a>';
			if( defined('service_provider_id') && is_numeric(service_provider_id) && defined('service_provider_name') ){
				echo '<br/>';
				echo 'Hosted by <a href="http://www.gpeasy.com/Special_Services?cmd=serviceinfo&amp;id='.service_provider_id.'" title="'.htmlspecialchars(service_provider_name).'">'.htmlspecialchars(service_provider_name).'</a>';
			}
			echo '</p>';
			
			if( isset($config['addons']) && is_array($config['addons']) && (count($config['addons']) > 0) ){
				echo ' with the following add-ons.';
				echo '<ul>';
					foreach($config['addons'] as $addon => $info){
						if( !isset($info['id']) ){
							continue;
						}
						echo '<li>';
						echo '<a href="http://gpeasy.com/index.php/Special_Addon_Plugins?cmd=details&amp;id='.$info['id'].'">';
						echo $info['name'];
						echo '</a>';
						echo '</li>';
					}
				
				echo '</ul>';
			}
			echo '</div>';
		}
		

		
		
	}
	
	function MultiSiteData(){
		global $page;
		if( defined('multi_site_unique') ){
			$page->head .= '<meta name="multi_site_unique" content="'.multi_site_unique.'" />';
		}
		if( defined('service_provider_id') && is_numeric(service_provider_id) ){
			$page->head .= '<meta name="service_provider_id" content="'.service_provider_id.'" />';
		}
	}
	
	
	function xml(){
		global $gpmenu;
		
		
		header('Content-Type: text/xml; charset=UTF-8');
		echo '<?xml version="1.0" encoding="UTF-8"?>';
		echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
		
		
		/*
		<url>
		    <loc>http://www.example.com/</loc>
		    <lastmod>2005-01-01</lastmod>
		    <changefreq>monthly</changefreq>
		    <priority>0.8</priority>
		</url>
		*/
		
		foreach($gpmenu as $title => $level){
			echo "\n";
			echo '<url>';
			echo '<loc>';
			echo 'http://';
			echo $_SERVER['SERVER_NAME'];
			echo common::GetUrl(urlencode($title));
			echo '</loc>';
			
			echo '</url>';
		}
		
		echo '</urlset>';
		
		
		die();
	}
}
