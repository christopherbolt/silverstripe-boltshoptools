<?php

namespace ChristopherBolt\BoltShopTools\Tasks;

use SilverShop\Model\Order;
use SilverShop\Model\OrderAttribute;
use SilverShop\Model\OrderStatusLog;
use SilverStripe\Omnipay\Model\Payment;
use SilverStripe\Omnipay\Model\Message\PaymentMessage;
use SilverStripe\ORM\DataObject;
use SilverStripe\Core\ClassInfo;
use SilverStripe\ORM\DB;
use SilverStripe\Dev\BuildTask;

/**
 * Delete Ecommerce Orders
 * Deletes all orders, order items, and payments from the database.
 * @package shop
 * @subpackage tasks
 */
class DeleteOrdersTask extends BuildTask{
	protected $title = "Delete Orders";
	protected $description = "Deletes all orders, order items, and payments from the database.";
	
	private static $base_classes = array(
        Order::class,
        OrderAttribute::class,
        OrderStatusLog::class,
        Payment::class,
        PaymentMessage::class
    );
	
	private static $many_many_ignore = [
		'LinkTracking',
		'FileTracking'
	];
	
	function run($request = null){
		$this->sqldelete();
	}
	
	function sqldelete(){
		
		$this->extend('onBeforeDelete');
		
		$baseclasses = self::config()->base_classes;
		$many_many_ignore = self::config()->many_many_ignore;
		
		foreach($baseclasses as $class){
			
			//$table = $class::getSchema()->tableName($class);
			//if(!(ClassInfo::hasTable($table))) continue;
			
			// Find the full list of classes for this base class and loop over all of them
			foreach(ClassInfo::subclassesFor($class) as $subclass){
				$schema = $subclass::getSchema();
				
				// Check for has_many and delete the corresponding connection to prevent re-linking as new items are added to the DB
				$has_many = $subclass::config()->has_many;
				if ($has_many && is_array($has_many)) {
					foreach($has_many as $relationName => $relationClass) {
						$has_one = $relationClass::config()->has_one;
						if ($has_one && is_array($has_one)) {
							echo "<p>Deleting all $subclass::has_many to $relationClass::has_one connections</p>";
							foreach($has_one as $has_one_relationName => $has_one_relationClass) {
								if ($has_one_relationClass == $subclass) {
									$has_one_table = $schema->tableForField($relationClass, $has_one_relationName.'ID');
									DB::query("UPDATE \"{$has_one_table}\" SET {$has_one_relationName}ID = 0;");
								}
							}
						}
					}
				}
				
				// Check for many_many and belongs_many_many and delete the pivot data to remove all connections
				$many_many = $subclass::config()->many_many;
				if ($many_many && is_array($many_many)) {
					foreach($many_many as $relationName => $relationClass) {
						if (in_array($relationName, $many_many_ignore)) continue;
						$pivotData = $schema->manyManyComponent($subclass, $relationName);
						if (!empty($pivotData['join'])) {
							echo "<p>Deleting all $subclass::many_many[$relationName] connections</p>";
							DB::query("DELETE FROM \"{$pivotData['join']}\" WHERE 1;");
							DB::query("ALTER TABLE \"{$pivotData['join']}\" AUTO_INCREMENT = 1;");
						}
					}
				}
				$belongs_many_many = $subclass::config()->belongs_many_many;
				if ($belongs_many_many && is_array($belongs_many_many)) {
					foreach($belongs_many_many as $relationName => $relationClass) {
						if (in_array($relationName, $many_many_ignore)) continue;
						$pivotData = $schema->manyManyComponent($subclass, $relationName);
						if (!empty($pivotData['join'])) {
							echo "<p>Deleting all $subclass::belongs_many_many[$relationName] connections</p>";
							DB::query("DELETE FROM \"{$pivotData['join']}\" WHERE 1;");
							DB::query("ALTER TABLE \"{$pivotData['join']}\" AUTO_INCREMENT = 1;");
						}
					}
				}
				
				// Find the table name and delete all records
				$subtable = $schema->tableName($subclass);				
				if (ClassInfo::hasTable($subtable)){
					echo "<p>Deleting all $subclass</p>";
					DB::query("DELETE FROM \"$subtable\" WHERE 1;");
					DB::query("ALTER TABLE \"$subtable\" AUTO_INCREMENT = 1;");
				}
			}
		}
		
		$this->extend('onAfterDelete');
	}
}