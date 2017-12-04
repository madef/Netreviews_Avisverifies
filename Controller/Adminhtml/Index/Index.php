<?php
namespace Netreviews\Avisverifies\Controller\Adminhtml\Index;
class Index extends \Magento\Backend\App\Action
{
    protected $resultPageFactory;
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory
	){
        $this -> _resultPageFactory = $resultPageFactory;
        return parent::__construct( $context );
    }

    public function execute()
    {
        $page = $this -> _resultPageFactory -> create();
		
		// Menu highlight
		$page -> setActiveMenu('Netreviews_Avisverifies::top_level');
		
		// Change menu title
		$page->getConfig()->getTitle()->prepend(__('VerifiedReviews'));
		
    	return $page;
    }
    protected function _isAllowed()
    {
        return $this -> _authorization -> isAllowed('Netreviews_Avisverifies::menu_items');
    }

}