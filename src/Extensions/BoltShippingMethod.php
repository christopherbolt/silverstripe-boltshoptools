<?php

namespace ChristopherBolt\BoltShopTools\Extensions;






use SilverShop\Shipping\Model\ShippingMethod;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\CheckboxField;
use SilverStripe\Forms\ListboxField;
use SilverStripe\ORM\DataExtension;



// Adds some extra contraints for controlling what shipping modules are shown.

class BoltShippingMethod extends DataExtension {
	
	private static $db = array(
		'OnlyShowIfNoOther' => 'Boolean',
		'HideAllOthers' => 'Boolean'
	);
	
	private static $many_many = array(
		'OnlyShowIfMethods' => ShippingMethod::class,
		'HideExceptions' => ShippingMethod::class
	);
	
	function updateCMSFields(FieldList $fields) {
		$fields->addFieldToTab('Root.AdditionalConstraints', CheckboxField::create('OnlyShowIfNoOther', 'Only show this option if there are no other shipping methods available, or if the following methods are the only other methods available:')->addExtraClass('stacked'));	
		$fields->addFieldToTab('Root.AdditionalConstraints', ListboxField::create('OnlyShowIfMethods', '', ShippingMethod::get()->map('ID', 'Title')->toArray()));	
		$fields->removeByName('OnlyShowIfMethods');
		
		$fields->addFieldToTab('Root.AdditionalConstraints', CheckboxField::create('HideAllOthers', 'Hide all other options if this option is available, except:')->addExtraClass('stacked'));	
		$fields->addFieldToTab('Root.AdditionalConstraints', ListboxField::create('HideExceptions', '', ShippingMethod::get()->map('ID', 'Title')->toArray()));	
		$fields->removeByName('HideExceptions');
	}
}