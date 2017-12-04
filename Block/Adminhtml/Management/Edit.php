<?php
namespace Netreviews\Avisverifies\Block\Adminhtml\Management;
 
use Magento\Backend\Block\Widget\Form\Container;
 
class Edit extends Container
{
    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry = null;
	
	
	
    /**
     * @param \Magento\Backend\Block\Widget\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Widget\Context $context,
        \Magento\Framework\Registry $registry,
        array $data = []
    ) {
        $this->_coreRegistry = $registry;
        parent::__construct($context, $data);
    }
	
	
	
    /**
     * Management edit block
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_objectId = 'entity_id';
        $this->_blockGroup = 'Netreviews_Avisverifies';
        $this->_controller = 'adminhtml_management';
 
        parent::_construct();
 
            if ($this->_isAllowedAction('Netreviews_Avisverifies::menu_items')) {
	            $this->buttonList->update('save', 'label', __('Export File'));
	        } else {
	            $this->buttonList->remove('save');
	        }
 
    }
	
	
	
    /**
     * Get header with Management name
     *
     * @return \Magento\Framework\Phrase
     */
    public function getHeaderText()
    {
        if ($this->_coreRegistry->registry('avisverifies_management')->getId()) {
            return __("Export management '%1'", $this->escapeHtml($this->_coreRegistry->registry('avisverifies_management')->getName()));
        } else {
            return __('New Management');
        }
    }
	
	
	
    /**
     * Check permission for passed action
     *
     * @param string $resourceId
     * @return bool
     */
    protected function _isAllowedAction($resourceId)
    {
        return $this->_authorization->isAllowed($resourceId);
    }
	
	
	
    /**
     * Getter of url for "Save and Continue" button
     * tab_id will be replaced by desired by JS later
     *
     * @return string
     */
    protected function _getSaveAndContinueUrl()
    {
        return $this->getUrl('management/*/save', ['_current' => true, 'back' => 'edit', 'active_tab' => '']);
    }
}