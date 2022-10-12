<?php

namespace ChristopherBolt\BoltShopTools\Extensions;





use Bummzack\SortableFile\Forms\SortableUploadField;

use UncleCheese\DisplayLogic\Forms\Wrapper as DisplayLogicWrapper;

use SilverShop\Discounts\Model\SpecificPrice;
use SilverStripe\Assets\Image;
use SilverShop\Page\Product;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\DatetimeField;
use SilverStripe\Forms\LiteralField;
use SilverStripe\Forms\CheckboxField;
use SilverShop\Discounts\Model\GiftVoucherProduct;
use SilverShop\Extension\ProductVariationsExtension;
use SilverStripe\Forms\NumericField;
use SilverShop\Model\Variation\AttributeType;
use ChristopherBolt\BoltTools\Forms\AddNewListboxField;
use SilverStripe\Forms\HeaderField;
use SilverStripe\Forms\GridField\GridFieldConfig_RelationEditor;
use SilverStripe\Forms\GridField\GridFieldAddNewButton;
use SilverStripe\Forms\GridField\GridFieldEditButton;
use SilverStripe\Forms\GridField\GridFieldAddExistingAutocompleter;
use Symbiote\GridFieldExtensions\GridFieldAddExistingSearchButton;
use SilverStripe\Forms\GridField\GridField;
use SilverShop\Model\Variation\Variation;
use SilverStripe\Security\Security;
use SilverStripe\ORM\ArrayList;
use SilverShop\ORM\FieldType\ShopCurrency;
use SilverStripe\View\ArrayData;
use SilverStripe\ORM\DataExtension;

// Adds stock management
// Adds additional images
// Adds GST exempt

class BoltProduct extends DataExtension{
	
	private static $db = array(
		'ManageStock' => 'Boolean',
		'StockLevel' => 'Int',
		'SoldOut' => 'Boolean',
		'TaxExempt' => 'Int',
        'DateAdded' => "Datetime",
	);
	
	private static $has_one = array(
	);
	
	// This page can have many images
    private static $many_many = array(
        'AdditionalImages' => Image::class,
		'RelatedProducts' => Product::class
    );
	
	private static $belongs_many_many = array(
		'ReverseRelatedProducts' => Product::class,
	);

    // this adds the SortOrder field to the relation table. 
    // Please note that the key (in this case 'Images') 
    // has to be the same key as in the $many_many definition!
    private static $many_many_extraFields = array(
        'AdditionalImages' => array('SortOrder' => 'Int'),
		'RelatedProducts' => array('SortOrder' => 'Int')
    );
	
	// Summary fields
  	private static $summary_fields = array(
		'Image.CMSThumbnail',
  	);
	
	private static $field_labels = array(
		'Image.CMSThumbnail' => 'Image',
	);
	
	private static $defaults = array(
		'Weight' => '0.5'
	);
	
	private static $casting = array(
		'CachedPrice' => 'Currency'
	);
    
    private static $owns = array(
        'AdditionalImages'
    );
	
	public function populateDefaults(){
		parent::populateDefaults();
		$this->owner->setField('DateAdded', date('Y-m-d H:i:s', strtotime('now')));
	}

	function updateCMSFields(FieldList $fields) {
		
		$owner = $this->owner;
		
		if ($owner->ID && !defined('IN_PRODUCT_ID')) define('IN_PRODUCT_ID', $owner->ID);
				
		// Set default folder of product images
		if ($fields->dataFieldByName('Image')) $fields->dataFieldByName('Image')->setFolderName('Products');
		
		// Hide featured option to save the hassle of being asked why it does nothing and then being expected to build a facility to display featured products for free
		if ($owner::config()->hide_featured) $fields->removeFieldFromTab('Root.Main', 'Featured');
		
		// Hide model since it is not used anywhere and just clutters the interface
		if ($owner::config()->hide_model) $fields->removeFieldFromTab('Root.Main', 'Model');
		
		// Sort the categories better
		if ($parentField = $fields->dataFieldByName('ParentID')) {
			$categories = $parentField->getSource();
			asort($categories);
			$parentField->setSource($categories)->setTitle('Primary Category');
		}
		$categories = $fields->dataFieldByName('ProductCategories')->getSource();
		asort($categories);
		$fields->dataFieldByName('ProductCategories')->setSource($categories);
		
		// Show data added field?
		if ($owner::config()->show_date_added) {
			$fields->addFieldToTab("Root.Main", $dateField = new DatetimeField("DateAdded", 'Date added'), "AllowPurchase");
			//$dateField->getDateField()->setConfig('showcalendar', true);
			//$dateField->getTimeField()->setConfig('timeformat', 'H:m:s');
		}
		
		// Add additional images??
		if ($owner::config()->show_additional_images) {
			if ($fields->dataFieldByName(Image::class)) $fields->dataFieldByName('Image')->setTitle('Main Product Image');
			if (!$this->owner->exists()) {
				
				$fields->addFieldToTab("Root.Images", new LiteralField('AdditionalImages', '<div class="message">You can add additional images once you have saved for the first time.</div>'));
				
				
					
			} else {
				$fields->addFieldToTab("Root.Images", SortableUploadField::create('AdditionalImages', 'Additional Images')->setFolderName('Products')->setDescription('Drag and drop to re-order'));
			}
		}
		
		// Fix annoying bug when creating products from the model admin
		if (!$this->owner->exists()) {
			$fields->removeFieldFromTab("Root.Variations", 'Variations');
			$fields->addFieldToTab("Root.Variations", new LiteralField('Variations', '<p class="message warning">You can add variations once you have saved for the first time.</p>'));
		}
		
		// PRICING AND TAX ------
		if ($owner::config()->hide_cost_price) $fields->removeFieldFromTab('Root.Pricing', 'CostPrice'); // I don't think we need this???, lets hide for simplicity
		
		if ($owner::config()->show_price_instructions) {
			if ($owner->exists() && $owner->Variations()->Count()) {
				
			} else {
				$fields->addFieldToTab('Root.Pricing', LiteralField::create('PricingTaxInstructions','<div class="message">Enter pricing including tax if any, i.e. the full price the customer will pay.</div>'), 'BasePrice');
			}
		}
		
		// TAX ---------
		if ($owner::config()->show_tax_exempt) {
			$before = $fields->fieldByName('SpecificPrices') ? 'SpecificPrices' : null;
			$fields->addFieldToTab('Root.Pricing', 
				CheckboxField::create('TaxExempt', 'GST Exempt')
				->setDescription('Tick this box for items that are legally GST exempt such as donations.')
			, $before);
		}
		
		// GIFT VOUCHERS ------
		if ($this->owner->ClassName == GiftVoucherProduct::class) {
			$fields->removeFieldFromTab('Root.Pricing', 'SpecificPrices');
		}
		
		# Stock Management ----------
		if ($owner::config()->show_stock_options && $this->owner->ClassName != GiftVoucherProduct::class) {
		
			// Add stock management options
			$fields->insertAfter($manageStock = CheckboxField::create('ManageStock', 'Manage stock'), 'AllowPurchase');
			if ($this->owner->hasExtension(ProductVariationsExtension::class) && $this->owner->Variations()->count()) {
				$manageStock->setDescription('Because you have one or more variations, only tick this option if you do NOT want to manage stock levels individually for each variation, stock levels can then be set from the variations tab.');
			}
			$fields->insertAfter(DisplayLogicWrapper::create(
                NumericField::create('StockLevel')
                ->setDescription('When the stock level reaches 0 this item will no-longer be available for purchase.')
            )->displayIf('ManageStock')->isChecked()->end(), 'ManageStock');
			//$stocklevel;
			//$stocklevel->setName('StockLevelLogicWrapper');
		}
		
		# Variations
		// Replace attribute field with new field
		$fields->replaceField("VariationAttributeTypes", 
			AddNewListboxField::create("VariationAttributeTypes", "Attributes", 
				AttributeType::get()->map("ID", "Title")->toArray()
			)
			->setDescription("These are fields to indicate the way(s) each variation varies. Once selected, they can be edited on each variation.")
			->setModel(AttributeType::class)
			->setDialogTitle('New Attribute')
            ->setFormFieldsMethod('getAddNewListboxFields')
		);
		
		# Related Products ----------
		if ($owner::config()->show_related_products && $owner->exists()) {
			
			$fields->addFieldToTab("Root.RelatedProducts", HeaderField::create('RelatedProductsHeader', 'Related products'));
			$fields->addFieldToTab("Root.RelatedProducts", GridField::create('RelatedProducts', 'Related Products', $this->owner->RelatedProducts(), GridFieldConfig_RelationEditor::create()
				->removeComponentsByType(GridFieldAddNewButton::class)
				->removeComponentsByType(GridFieldEditButton::class)
				//->addComponent(new GridFieldOrderableRows('SortOrder'))
				->removeComponentsByType(GridFieldAddExistingAutocompleter::class)
				->addComponent(new GridFieldAddExistingSearchButton())
			));
			
			if ($owner::config()->show_reverse_related_products) {
				$fields->addFieldToTab("Root.RelatedProducts", HeaderField::create('RelatedProductsHeader', 'Products that relate back to this product'));
				$fields->addFieldToTab("Root.RelatedProducts", GridField::create('ReverseRelatedProducts', 'Related Products', $this->owner->ReverseRelatedProducts(), GridFieldConfig_RelationEditor::create()
					->removeComponentsByType(GridFieldAddNewButton::class)
					->removeComponentsByType(GridFieldEditButton::class)
					//->addComponent(new GridFieldOrderableRows('SortOrder'))
					->removeComponentsByType(GridFieldAddExistingAutocompleter::class)
					//->addComponent(new GridFieldAddExistingSearchButton())
				));
			}
									
		}
		
		# Shipping??
		if ($owner::config()->hide_shipping) {
			$fields->removeByName('Shipping');
		} else {
			if ($owner::config()->show_free_shipping_instructions)
				$fields->addFieldToTab("Root.Shipping", new LiteralField('freeshippingmessage','<div class="message">Enter all zero values for free shipping. A package with no weight or volume ships for free.</div>'), 'Weight');
		}
	}
	
	function onBeforeWrite() {
		parent::onBeforeWrite()	;
		// Check stock level for entire product
		$this->owner->SoldOut = $this->owner->IsSoldOut();
	}
	
	// Duplicate 
	function onAfterDuplicate($page) {
		if ($this->owner->ID < 1 || $page->ID < 1) {
			// Can only add relations if this exists.
			return;
		}
		// Due to API differences between SiteTree->duplicate and DataObject->duplicate we need to determine which is the original and which is the duplicate
		if ($this->owner->ID > $page->ID) {
			$clone = $this->owner;
			$original = $page;
		} else {
			$clone = $page;
			$original = $this->owner;
		}
		
		// Has many relations to duplicate...
		$relations = array('Variations','SpecificPrices');
		$clone->_inDuplication = true;
		foreach ($relations as $relation) {
			foreach($original->$relation() as $item) {
				$new = $item->duplicate();
				$clone->$relation()->add($new);
			}
		}
		unset($clone->_inDuplication);
        
        // Belongs many relations to duplicate
        $relations = array('ProductCategories','VariationAttributeTypes');
        foreach ($relations as $relation) {
			foreach($original->$relation() as $item) {
				$clone->$relation()->add($item);
			}
		}
	}
	
	function IsSoldOut ($quantity = 1) {
		// Check stock level for entire product
		if ($this->owner->ManageStock) {
			if ($this->owner->StockLevel-$quantity < 0) {
				return true; // no stock!
			}
		} else if (!$this->owner->ManageStock && $this->owner->hasExtension(ProductVariationsExtension::class) && $this->owner->Variations()->count()) {
			foreach ($this->owner->Variations() as $variation) {
				if (!$variation->ManageStock) {
					return false;
				} else if ($variation->StockLevel > 0) {
					return false;
				}
			}
			return true;
		}
		return false;
	}
	
	function canPurchase($member = null, $quantity = 1) {
		// Stock management
		if ($this->IsSoldOut(/*$quantity*/)) { // Quantities greater than one now handled by shopping cart extension
			return false;
		}
		return null;
	}
	
	// returns true if it has a discount
	function OnSpecial() {
		if ($this->owner->Variations()->count()) {
			$variations = Variation::get()->filter('ProductID', $this->owner->ID)
				->leftJoin('SilverShop_SpecificPrice', '"SilverShop_SpecificPrice"."ProductVariationID" = "SilverShop_Variation"."ID"');
			$variations = SpecificPrice::filter($variations, Security::getCurrentUser());
			return ($variations->Count());
		} else {
			return ($this->owner->getPrice() < $this->owner->BasePrice);
		}
	}
	function IsNewProduct($newdays=30) {
        $newdays = intval($newdays);
		$day = 3600*24;
		$owner = $this->owner;
		$field = 'Created';
		if ($owner::config()->show_date_added) {
			$field = 'DateAdded';
		}
		return $this->owner->$field ? (strtotime($this->owner->$field) > time()-($newdays*$day)) : false;
	}
	
	
	public function SortedAdditionalImages(){
        return $this->owner->AdditionalImages()->Sort('SortOrder');
    }
	// returns all additional images including variation images
	function AllAdditionalImages() {
		$images = array();
		$variations = $this->owner->Variations();
		foreach ($variations as $variation) {
			if (($image = $variation->Image()) && $image->exists()) {
				array_push($images, $image);
			}
		}
		$additional = $this->owner->SortedAdditionalImages();
		foreach ($additional as $image) {
			if ($image && $image->exists()) {
				array_push($images, $image);
			}
		}
		
		if (count($images) && $this->owner->ImageID) {
			array_unshift($images, $this->owner->Image());
		}
		
		return new ArrayList($images);	
	}
	
	// Returns all related products including reverses
	function AllRelatedProducts() {
		$related = $this->owner->RelatedProducts()->getIdList();
		$reverse = $this->owner->ReverseRelatedProducts()->getIdList();
		$ids = array_unique(array_merge($related, $reverse));
		if (count($ids)) {
			$products = Product::get()->byIds($ids);	
			$this->owner->extend('updateAllRelatedProducts', $products);
			return $products;
		}
	}
	
	// returns a better price range that includes specific pricing
	/*public function BetterPriceRange(){
		$variations = $this->owner->Variations();
		if(!$variations->exists() || !$variations->Count()){
			return null;
		}
		$prices = $variations->map('ID','Price')->toArray();
		$pricedata = array(
			'HasRange' => false,
			'Max' => ShopCurrency::create(),
			'Min' => ShopCurrency::create(),
			'Average' => ShopCurrency::create()
		);
		$count = count($prices);
		$sum = array_sum($prices);
		$maxprice = max($prices);
		$minprice = min($prices);
		$pricedata['HasRange'] = ($minprice != $maxprice);
		$pricedata['Max']->setValue($maxprice);
		$pricedata['Min']->setValue($minprice);
		if($count > 0){
			$pricedata['Average']->setValue($sum/$count);
		}

		return new ArrayData($pricedata);
	}*/
	// Replaces price range to be variations special price aware
	public function PriceRange(){
		//exit('here');
		// Used cached pricing
		if (BoltProductCategoryController::$cached_prices && isset(BoltProductCategoryController::$cached_prices[$this->owner->ID])) {
			
			$row = BoltProductCategoryController::$cached_prices[$this->owner->ID];
			
			$prices = $row['VariationPrices'];
			$oldprices = $row['VariationBasePrices'];
			
			if (!count($prices)) return null;
			
			$pricedata = array(
				'HasRange' => false,
				'Max' => ShopCurrency::create(),
				'Min' => ShopCurrency::create(),
				// Chris Bolt, added for specials, this is the old min-price
				'BasePrice' => ShopCurrency::create(),
				'BaseAverage' => ShopCurrency::create(),
				// End Chris Bolt
				'Average' => ShopCurrency::create()
			);
			$count = count($prices);
			$sum = array_sum($prices);
			$maxprice = max($prices);
			$minprice = min($prices);
			$pricedata['HasRange'] = ($minprice != $maxprice);
			$pricedata['Max']->setValue($maxprice);
			$pricedata['Min']->setValue($minprice);
			// Chris Bolt, added for specials, this is the old min-price
			$baseprice = min($oldprices);
			$pricedata['BasePrice']->setValue($baseprice);
			// End Chris Bolt
			if($count > 0){
				$pricedata['Average']->setValue($sum/$count);
				// Chris Bolt, added for specials, this is the old, non-special average
				$pricedata['BaseAverage']->setValue(array_sum($oldprices)/count($oldprices));
				// End Chris Bolt
			}
			
			$this->owner->extend('updatePriceRange', $pricedata);
			
			return new ArrayData($pricedata);
		}
		// End Caching
		// use live pricing
		
		$variations = $this->owner->Variations();
		if(!$variations->exists() || !$variations->Count()){
			return null;
		}
		// Chris Bolt, add support for wholsaler prices AND specific prices
		//$prices = $variations->map('ID','Price')->toArray();
		$prices = array();
		
		$pricesField = 'Price';
		/*if(($Group = Group::get()->filter(array('Code' => 'wholesalers'))->first()) && ($member = Security::getCurrentUser())) && $member->inGroup($Group)) {
			$pricesField = 'WholesalerPrice';
		}*/
		$oldprices = $variations->map('ID',$pricesField)->toArray();
		
		foreach ($variations as $variation) {
			$prices[$variation->ID] = $variation->sellingPrice();
		}
		// End Chris Bolt
		
		$pricedata = array(
			'HasRange' => false,
			'Max' => ShopCurrency::create(),
			'Min' => ShopCurrency::create(),
			// Chris Bolt, added for specials, this is the old min-price
			'BaseAverage' => ShopCurrency::create(),
			'BasePrice' => ShopCurrency::create(),
			// End Chris Bolt
			'Average' => ShopCurrency::create()
		);
		$count = count($prices);
		$sum = array_sum($prices);
		$maxprice = max($prices);
		$minprice = min($prices);
		$pricedata['HasRange'] = ($minprice != $maxprice);
		$pricedata['Max']->setValue($maxprice);
		$pricedata['Min']->setValue($minprice);
		// Chris Bolt, added for specials, this is the old min-price
		$baseprice = min($oldprices);
		$pricedata['BasePrice']->setValue($baseprice);
		// End Chris Bolt
		if($count > 0){
			$pricedata['Average']->setValue($sum/$count);
			// Chris Bolt, added for specials, this is the old, non-special average
			$pricedata['BaseAverage']->setValue(array_sum($oldprices)/count($oldprices));
			// End Chris Bolt
		}
		
		$this->owner->extend('updatePriceRange', $pricedata);
		
		return new ArrayData($pricedata);
	}
	
	public function CachedPrice() {
		if (BoltProductCategoryController::$cached_prices && isset(BoltProductCategoryController::$cached_prices[$this->owner->ID])) {
			$price = BoltProductCategoryController::$cached_prices[$this->owner->ID]['Price'];
		} else {
			$price = $this->owner->getPrice();	
		}
        $this->owner->extend('updateCachedPrice', $price);
        return $price;
	}
}
