<?php

namespace ChristopherBolt\BoltShopTools\Extensions;



use SilverStripe\Control\Controller;
use SilverStripe\Admin\LeftAndMain;
use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\TextField;
use SilverStripe\Forms\FieldList;
use SilverShop\Model\Variation\AttributeValue;
use SilverStripe\ORM\DataExtension;
use ChristopherBolt\BoltTools\Forms\AddNewDropdownField;

/**
 * Producte Attribute Type
 * Types of product attributes.
 * eg: color, size, length
 * @subpackage variations
 */
class BoltProductAttributeType extends DataExtension {

	/**
	 * Returns a dropdown field for the user to select a variant.
	 *
	 * @param string $emptyString
	 * @param ArrayList $values
	 *
	 * @return DropdownField
	*/
	public function getAdminDropDownField($emptystring = null, $values = null){
		$values = ($values) ? $values : $this->owner->Values()->sort('Sort ASC, Value ASC');
		
		$inAdmin = (is_subclass_of(Controller::curr(), LeftAndMain::class)) ? true : false;
		$fieldType = $inAdmin ? AddNewDropdownField::class : DropdownField::class;
		
		//if($values->exists()) {
			// Chris Bolt, natural sorting
			$array = $values->map('ID','Value')->toArray();
			natsort($array);
			// End Chris Bolt
			$field = $fieldType::create(
				'ProductAttributes['.$this->owner->ID.']',
				// Chris Bolt so that it uses label instead of title else what is the point of labels
				//$this->owner->Name,
				$this->owner->Label,
				// End Chris Bolt
				// Chris Bolt natural sorting
				$array//$values->map('ID','Value')
				// End Chris Bolt
			);
			if ($inAdmin) {
                $field->setModel(AttributeValue::class)->setDialogTitle('New Attribute Value')
                    ->setBeforeWriteCallback(array($this->owner, 'AdminDropDownFieldNewValueCallback'))
                    ->setFormFieldsMethod('getAddNewListboxFields');
            }
			
			if($emptystring) {
				$field->setEmptyString($emptystring);
			}

			return $field;
		//}

		//return null;
	}
	
	public function AdminDropDownFieldNewValueCallback($obj) {
		$obj->TypeID = $this->owner->ID;
		return $obj;
	}
    
    public function getAddNewListboxFields()
    {
        $fields = FieldList::create(
            TextField::create('Name', $this->owner->fieldLabel('Name')),
            TextField::create('Label', $this->owner->fieldLabel('Label'))
        );

        $this->owner->extend('updateCMSFields', $fields);

        return $fields;
    }

}
