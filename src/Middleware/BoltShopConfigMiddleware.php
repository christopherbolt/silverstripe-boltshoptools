<?php

namespace ChristopherBolt\BoltShopTools\Middleware;

use SilverStripe\Control\Middleware\HTTPMiddleware;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\SiteConfig\SiteConfig;
use SilverStripe\Control\Email\Email;
use SilverShop\Extension\ShopConfigExtension;
use SilverShop\Checkout\OrderProcessor;

class BoltShopConfigMiddleware implements HTTPMiddleware {
	// Updates the various shop emails to those set in the SiteConfig
	// Normally these are hard coded in a config file, but I need to hack a way for them to be set through the CMS
	// This runs on each request and updates the config
	public function process(HTTPRequest $request, callable $delegate)
    {
		
		// Don't do this in dev/build to avoid first time build issues
		if ($request->getURL() != 'dev/build') {
		
			$config = SiteConfig::current_site_config();

			// Set if notifications should be sent
			OrderProcessor::config()->send_admin_notification = $config->SendAdminNotification;

			// Set who notifications should be sent to
			$admin_email = $config->OrderEmailTo ? $config->OrderEmailTo : $config->ContactEmail;
			if ($admin_email) Email::config()->admin_email = $admin_email;

			// Set who all shop emails should be sent from
			$email_from = $config->OrderEmailFrom ? $config->OrderEmailFrom : $admin_email;
			if ($email_from) ShopConfigExtension::config()->email_from = $email_from;
			
		}
		
		// If you want normal behaviour to occur, make sure you call $delegate($request)
		$response = $delegate($request);
		return $response;
    }
}