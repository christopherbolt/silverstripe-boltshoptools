<?php

namespace ChristopherBolt\BoltShopTools\Controllers;






use SilverShop\Page\Product;
use SilverStripe\View\SSViewer;
use SilverStripe\Security\Security;
use SilverStripe\View\ArrayData;
use SilverStripe\Control\Controller;
use SilverShop\ORM\FieldType\ShopCurrency;
 

class JsonPriceUpdater extends Controller {
	
	private static $allowed_actions = array('index');
	private static $price_template = 'ProductAjaxPrice';
	
	function Index($data) {
		$success = false;
		$imgURL = '';
		$imgURLForPopup = '';
		$priceHTML = '';
		$canPurchase = false;
		$internalItemID = '';
		
		//$success=true;$priceHTML = '<pre>'.htmlspecialchars(print_r($data, true)).'</pre>';
		$ID = $data->getVar('ProductID');
		$attributes = $data->getVar('ProductAttributes');
		
		if ($ID && $attributes && is_array($attributes) && ($product = Product::get()->byId($ID)) && ($variation = $product->getVariationByAttributes($attributes))) {
			$success = true;	
			
			$BasePrice = 0;
			$Price = 0;
			
			$internalItemID = $variation->InternalItemID;
			
			if ($variation->canPurchase()) {
				$canPurchase = true;
				$BasePrice = $variation->Price;
				if ($BasePrice == 0){
					$BasePrice = $product->BasePrice;
				}
				$Price = $variation->sellingPrice();
			}
			
			$viewer = new SSViewer(self::config()->price_template);
			$priceHTML = $viewer->process(new ArrayData(array(
				'Variation' => $variation,
				'BasePrice' => ShopCurrency::create()->setValue($BasePrice),
				'Price' => ShopCurrency::create()->setValue($Price),
				'Member' => Security::getCurrentUser()
			)))->forTemplate();
					
			if ((($image = $variation->Image()) && $image->exists()) || (($image = $product->Image())  && $image->exists())) {
				$imgURL = $image->getContentImage()->getURL();
				$imgURLLarge = $image->getLargeImage()->getURL();
			}
		}
		
		return json_encode(array(
			'success' => $success,
			'imgURL' => $imgURL,
			'imgURLLarge' => $imgURLForPopup,
			'priceHTML' => $priceHTML,
			'canPurchase' => $canPurchase,
            'InternalItemID' => $internalItemID
		));
	}
}