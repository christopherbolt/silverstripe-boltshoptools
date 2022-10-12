<?php

namespace ChristopherBolt\BoltShopTools\Extensions;









//use SQLQuery;
use SilverStripe\ORM\Queries\SQLSelect;
use SilverStripe\Assets\Image;
use SilverStripe\Forms\FieldList;
use SilverStripe\AssetAdmin\Forms\UploadField;
use SilverStripe\SiteConfig\SiteConfig;
use SilverStripe\ORM\DataExtension;
use SilverShop\Page\ProductCategory;
use SilverStripe\Control\Controller;
use ChristopherBolt\BoltTools\Lists\LimitedPaginatedList;
use SilverStripe\Security\Security;
use SilverShop\Page\Product;
use SilverShop\Model\Variation\Variation;



// Adds function for getting all additional images, adds filters for stock

class BoltProductCategory extends DataExtension{
		
	private static $has_one = array(
		"Image" => Image::class
	);
    
    private static $owns = array(
        'Image'
    );

	function updateCMSFields(FieldList $fields) {
		$owner=$this->owner;
		if ($owner::config()->show_image) {
			$fields->addFieldToTab('Root.Main', UploadField::create('Image')->setFolderName('Products'), 'Content');
		}
	}
	
	function ImageForTemplate() {
		// Check for image
		$image = $this->owner->getComponent('Image');
		if ($image && $image->exists()) return $image;
		$image = SiteConfig::current_site_config()->DefaultProductImage();
		
		// Else return first product image
		if (($products = $this->owner->ProductsShowable()) && $products->count()) return $products->First()->Image();
		
		// Else return default product image
		if ($image && $image->exists()) return $image;
		//return $this->owner->model->Image->newObject();
	}
	
	public function FilterOutUnsellable($filter) {
		$owner = $this->owner;
		if (!$owner::config()->show_not_allowed_purchase) $filter = $filter->filter(array('AllowPurchase' => 1));
		if (!$owner::config()->show_sold_out) $filter = $filter->filter(array('SoldOut' => 0));
		$this->owner->extend('updateFilterOutUnsellable', $filter);
		return $filter;
	}
	
	/**
	 * Return the products for this group.
	 */
	function BoltProductsShowable($recursive = true) {
		$products = $this->owner->FilterOutUnsellable($this->owner->ProductsShowable($recursive));//->sort("Artist ASC, Title ASC") ;
		$this->owner->extend('updateProductsShowable', $products);
		return $products;
		
	}
	
	function HasProductsOrCategories() {
		if ($this->owner->ChildCategories() && $this->owner->ChildCategories()->count()) return true;
		if ($this->owner->BoltProductsShowable() && $this->owner->BoltProductsShowable()->count()) return true;
		return false;
	}


}
class BoltProductCategoryController extends DataExtension{
	
	// use BoltProducts in your templates!!
	public static $cached_prices = null;
	public function BoltProducts ($recursive = true) {
		$products = $this->owner->BoltProductsShowable();
		if ($products) {
			//$products = $this->owner->ProductsShowable();
			//sort the products
			if (ProductCategory::config()->sort_method == 'ListSorter') {
				$products = $this->owner->getSorter()->sortList($products);
			} else {
				$products = $products->sort(ProductCategory::config()->default_sort_field);	
			}
			//paginate the products
			$products = new LimitedPaginatedList($products, Controller::curr()->getRequest(), ProductCategory::config()->page_length);
			//$products->setPageLength(/*ProductCategory::config()->page_length*/);
			$products->TotalCount = $products->getTotalItems();
			
			// Cache prices
			if (ProductCategory::config()->use_price_caching) 
				self::$cached_prices = $this->pricesArray($products);

			return $products;
		}
	}
	
	// used to speed up listings by caching an array of all prices in one query instead of querying this on each product individually
	function pricesArray(&$products) {
		
		$ids = array();
		foreach($products as $product) {
			$ids[] = $product->ID;	
		}
		
		if (!count($ids)) return;
		
		$member = Security::getCurrentUser();
		
		$sqlQuery = new SQLSelect();
		
		$sqlQuery->selectField('SilverShop_Product.ID', 'ID');
		$sqlQuery->selectField('SilverShop_Product.BasePrice', 'BasePrice');
		$sqlQuery->selectField('SilverShop_Variation.Price', 'VariationPrice');
		$sqlQuery->selectField('SilverShop_SpecificPrice.Price', 'SpecificPrice');
		$sqlQuery->selectField('SilverShop_SpecificPrice.ID', 'SpecificPriceID');
		$sqlQuery->selectField('SilverShop_Variation.ID', 'ProductVariation_ID');
		
		$sqlQuery->setFrom('SilverShop_Product');
		$sqlQuery->setWhere("SilverShop_Product.ID IN (".implode(',',$ids).")");
		
		
		$sqlQuery = $sqlQuery
				->addLeftJoin('SilverShop_Variation', '"SilverShop_Variation"."ProductID" = "SilverShop_Product"."ID"')
				->addLeftJoin('SilverShop_SpecificPrice', '"SilverShop_SpecificPrice"."ProductID" = "SilverShop_Product"."ID" OR "SilverShop_SpecificPrice"."ProductVariationID" = "SilverShop_Variation"."ID"');
		//$filter = SpecificPrice::filter($filter, Security::getCurrentUser());	
		// Cannot use the SpecificPrice::filter because it filters out any items without a specific price so we need to copy it into here and make adjustments
		// Used for specific price matching
		$now = date('Y-m-d H:i:s');
		$nowminusone = date('Y-m-d H:i:s',strtotime("-1 day"));
		$groupids = array(0);
		if($member){
			$groupids = array_merge($member->Groups()->map('ID', 'ID')->toArray(), $groupids);
		}
		/*
		$sqlQuery = $sqlQuery		
				->addWhere('
					("SpecificPrice"."Price" IS NULL)
					OR
					'."(((\"SpecificPrice\".\"StartDate\" IS NULL) OR (\"SpecificPrice\".\"StartDate\" < '$now')) AND ((\"SpecificPrice\".\"EndDate\" IS NULL) OR (\"SpecificPrice\".\"EndDate\" > '$nowminusone')) AND (\"SpecificPrice\".\"GroupID\" IN (".implode(',', $groupids).")))"
				);
		*/
		//exit($sqlQuery->sql());
		//exit($now);
		
		$array = array();
		$result = $sqlQuery->execute();
		//echo '<!--';
		//foreach ($sqlQuery->execute() as $row) {
        foreach ($result as $row) {
			//if ($row['ID'] == 487) print_r($row);
			if (empty($array[$row['ID']])) {
				$array[$row['ID']] = array();
				$array[$row['ID']]['BasePrice'] = $row['BasePrice'];
				$array[$row['ID']]['Price'] = $row['BasePrice'];
				$array[$row['ID']]['VariationBasePrices'] = array();
				$array[$row['ID']]['VariationPrices'] = array();
			}
			
			// Specific prices attached directly to the product:
			if (($row['ProductVariationID'] == 0 && $row['SpecificPrice'] && $row['SpecificPrice'] < $array[$row['ID']]['Price'])
			 && (((!$row['StartDate'] || $row['StartDate'] < $now) && (!$row['EndDate'] || $row['EndDate'] > $nowminusone)) && (in_array($row['GroupID'], $groupids)))
			) {
				$array[$row['ID']]['Price'] = $row['SpecificPrice'];			
			}
			
			//if ($row['ProductVariationID']) {
			if ($row['ProductVariation_ID']) {
				
				// Variation prices
				if (empty($array[$row['ID']]['VariationPrices'][$row['ProductVariation_ID']])) {
					$array[$row['ID']]['VariationBasePrices'][$row['ProductVariation_ID']] = $row['VariationPrice'];
					$array[$row['ID']]['VariationPrices'][$row['ProductVariation_ID']] = $row['VariationPrice'];
				}
			}
			if ($row['ProductVariationID']) {	
				// Specific prices attached directly to a variation:
				if (($row['SpecificPrice'] && $row['SpecificPrice'] < $array[$row['ID']]['VariationPrices'][$row['ProductVariationID']])
			 	&& (((!$row['StartDate'] || $row['StartDate'] < $now) && (!$row['EndDate'] || $row['EndDate'] > $nowminusone)) && (in_array($row['GroupID'], $groupids)))
				) {
					$array[$row['ID']]['VariationPrices'][$row['ProductVariationID']] = $row['SpecificPrice'];
				}
			}
		}
		
		//print_r($array);
		//echo '-->';
		//exit;
	
		return $array;
	}
	
	/**
	 * Return products that are featured, that is products that have "FeaturedProduct = 1"
	 */
	public function BoltFeaturedProducts($recursive = true) {
		return $this->owner->BoltProductsShowable($recursive)
			->filter("Featured",true);
	}

	/**
	 * Return products that are not featured, that is products that have "FeaturedProduct = 0"
	 */
	public function BoltNonFeaturedProducts($recursive = true) {
		return $this->owner->BoltProductsShowable($recursive)
			->filter("Featured",false);
	}
	
}
	