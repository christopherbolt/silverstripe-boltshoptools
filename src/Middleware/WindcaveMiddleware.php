<?php

namespace ChristopherBolt\BoltShopTools\Middleware;

use SilverStripe\Control\Middleware\HTTPMiddleware;
use SilverStripe\Control\HTTPRequest;

class WindcaveMiddleware implements HTTPMiddleware {
	/*
    If this request has come from Windcave then add a random delay, 
    this should make it improbable for requests processing simultaneously???
    Windcave send a User Agent string of "PXL1" we will need to monitor server logs as they might change this!
    */
	public function process(HTTPRequest $request, callable $delegate)
    {
		
		$windcave = 'PXL1';
        if ($request->match('paymentendpoint')) {
            $agent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
            if (empty($agent) || $agent == $windcave) {
                // Sleep for a random number of seconds and nanoseconds
                time_nanosleep (rand (8, 16), rand(0, 900000000));
            }
        }
		
		// If you want normal behaviour to occur, make sure you call $delegate($request)
		$response = $delegate($request);
		return $response;
    }
}