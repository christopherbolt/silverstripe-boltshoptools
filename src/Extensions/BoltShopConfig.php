<?php

namespace ChristopherBolt\BoltShopTools\Extensions;

use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\EmailField;
use SilverStripe\Forms\TextField;
use SilverStripe\Forms\CheckboxField;
use SilverStripe\SiteConfig\SiteConfig;
use SilverStripe\Control\Email\Email;
use SilverStripe\ORM\DataExtension;

class BoltShopConfig extends DataExtension {
		
	private static $db = array(
		'SendAdminNotification' => 'Boolean',
		'OrderEmailTo' => 'Varchar(255)',
		'AdditionalOrderEmailTo' => 'Varchar(255)',
		'OrderEmailFrom' => 'Varchar(255)',
	);
		
	public function updateCMSFields(FieldList $fields) {
		
        $fields->addFieldToTab("Root.Shop.ShopTabs.Main", EmailField::create("OrderEmailFrom", 'Emails should come from'));

        $fields->addFieldToTab("Root.Shop.ShopTabs.Main", CheckboxField::create("SendAdminNotification", 'Send admin order notifications'));
        $fields->addFieldToTab("Root.Shop.ShopTabs.Main", EmailField::create("OrderEmailTo", 'Send admin notifications to'));
        $fields->addFieldToTab("Root.Shop.ShopTabs.Main", TextField::create("AdditionalOrderEmailTo", 'Send additional copies to')->setDescription('Separate multiple addresses with a comma'));
				
	}
	
	// return an array of additional email addresses to send orders to
	public static function OrderEmailTo() {
		$config = SiteConfig::current_site_config();
		$emails = array();
		
		if (!empty($config->OrderEmailTo)) {
			$emails[] = $config->OrderEmailTo;
		} else if (!empty($config->ContactEmail)) {
			$emails[] = $config->ContactEmail;
		}
		if (!empty($config->AdditionalOrderEmailTo)) {
			$e = explode(',', $config->AdditionalOrderEmailTo);
			foreach ($e as $k) {
				$emails[] = trim($k);
			}
		}
		if (!count($emails)) {
			$emails[] = Email::config()->admin_email;
		}
		return $emails;
	}
	
	// return from email
	public static function OrderEmailFrom() {
		$config = SiteConfig::current_site_config();
		$from = ShopConfigExtension::config()->email_from ? ShopConfigExtension::config()->email_from : Email::config()->admin_email;
		
		if (!empty($config->OrderEmailFrom)) {
			$from = $config->OrderEmailFrom;
		} else if (!empty($config->ContactEmail)) {
			$from = $config->ContactEmail;
		}
		
		return $from;
	}
	
}

