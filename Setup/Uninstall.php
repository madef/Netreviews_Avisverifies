<?php
namespace Netreviews\Avisverifies\Setup;
 
use Magento\Framework\Setup\UninstallInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\ModuleContextInterface;
 
class Uninstall implements UninstallInterface
{
    /**
     * Module uninstall code
     *
     * @param SchemaSetupInterface $setup
     * @param ModuleContextInterface $context
     * @return void
	 * DELETE FROM setup_module WHERE setup_module.module = 'Netreviews_Avisverifies'; - Pending
	 * DROP TABLE avisverifies_products_reviews, avisverifies_products_average;
     */
    public function uninstall( SchemaSetupInterface $setup, ModuleContextInterface $context ) {
    	$setup->startSetup();
        $setup->endSetup();
    }
}