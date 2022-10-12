<?php

namespace ChristopherBolt\BoltShopTools\Extensions;




use SilverStripe\ORM\DataExtension;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\TextField;


// Adds sorting of product attributes

class BoltProductAttributeValue extends DataExtension {

	function updateCMSFields(FieldList $fields) {
		//$fields->push(NumericField::create('Sort', 'Sort order')->setDescription('Optional. Leave as 0 to sort alphabetically.'));
	}

    public function getAddNewListboxFields()
    {
        $fields = FieldList::create(
            TextField::create('Value', $this->owner->fieldLabel('Value'))
        );

        $this->owner->extend('updateCMSFields', $fields);

        return $fields;
    }
}