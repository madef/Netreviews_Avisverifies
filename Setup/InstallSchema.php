<?php
namespace Netreviews\Avisverifies\Setup;

use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Ddl\Table;

class InstallSchema implements InstallSchemaInterface {
    
    /**
     * __construct
     * Uncomment if you have an error : [Magento\Framework\Exception\SessionException] Area code not set: Area code must be set before starting a session.
     *
     * @param \Magento\Framework\App\State $appState
     * @param $stateName
     * @return void
     */
    public function __construct(
        \Magento\Framework\App\State $appState,
        $stateName = null
    ) {
        try {
            $appState->setAreaCode('frontend');// 'frontend' or 'adminhtml'.
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            // intentionally left empty
        }
    }

    /**
     * Module install code
     *
     * @param SchemaSetupInterface $setup
     * @param ModuleContextInterface $context
     * @return void
     */
    public function install( SchemaSetupInterface $setup, ModuleContextInterface $context ) {
        
        $setup -> startSetup();

        $connection = $setup -> getConnection();
		$tableName = $setup -> getTable('sales_order');
		
		$column1 = array(
		    'type' => Table::TYPE_SMALLINT,
		    'length' => 4,
		    'nullable' => true,
		    'default' => 0,
		    'comment' => 'AV Flag'
		);
		
		$column2 = array(
		    'type' => Table::TYPE_TEXT,
		    'length' => 32,
		    'nullable' => true,
		    'default' => NULL,
		    'comment' => 'AV_horodate_get'
		);
        
        $connection -> addColumn( $tableName, 'av_flag', $column1);
        $connection -> addColumn( $tableName, 'av_horodate_get', $column2);
		
		// Flag all orders to 1
		$objectManager = \Magento\Framework\App\ObjectManager::getInstance();
		$helperDATA = $objectManager -> create('Netreviews\Avisverifies\Helper\Data');
		$helperDATA -> flagOrdersTo1ForAllStores();
		
		// Ser default config
		$helperDATA -> setDefaultConfig();
			
        $setup -> endSetup();
    }
}