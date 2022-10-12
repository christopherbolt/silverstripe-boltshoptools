<?php

namespace ChristopherBolt\BoltShopTools\Extensions;




use SilverShop\Extension\ShopConfigExtension;
use SilverStripe\SiteConfig\SiteConfig;
use SilverStripe\ORM\DataExtension;



// Ensures that all addresses have a country

class BoltAddress extends DataExtension{
	
	function onBeforeWrite() {
		parent::onBeforeWrite();
		$all_countries = ShopConfigExtension::config()->iso_3166_country_codes;
		if (!$this->owner->Country || !isset($all_countries[$this->owner->Country])) { 
			$allowed_countries = SiteConfig::current_site_config()->getCountriesList();
			if(count($allowed_countries) == 1){
				//$this->owner->Country = array_pop($allowed_countries);	
				foreach($allowed_countries as $key => $value){
					$this->owner->Country = $key;
				}	
			}
		}
	}
		
}