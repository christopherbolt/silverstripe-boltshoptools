<?php

use SilverStripe\Core\Config\Config;
use SilverShop\Page\Product;
use ChristopherBolt\BoltShopTools\Extensions\BoltShopConfig;

// Paths
/**
 * - BOLTSHOPTOOLS_DIR: Path relative to webroot, e.g. "boltmail"
 * - BOLTSHOPTOOLS_PATH: Absolute filepath, e.g. "/var/www/my-webroot/boltmail"
 */
define('BOLTSHOPTOOLS_DIR', basename(dirname(__FILE__)));
define('BOLTSHOPTOOLS_PATH', BASE_PATH . '/' . BOLTSHOPTOOLS_DIR);
define('BOLTSHOPTOOLS_THIRDPARTY_PATH', BOLTSHOPTOOLS_PATH.'/thirdparty');

// If the featured tick box is hidden then we must also hide it on any search facility
//if (Config::inst()->get(Product::class, 'hide_featured')) {
	//Config::inst()->remove(Product::class, 'searchable_fields', 2); // 2 is the array key in searchable_fields
	//print_r(Config::inst()->get('Product', 'searchable_fields'));
//}
