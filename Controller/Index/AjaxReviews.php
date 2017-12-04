<?php
namespace Netreviews\Avisverifies\Controller\Index;
 
use Magento\Framework\App\Action\Context;
use Netreviews\Avisverifies\Block\Reviews;
use Netreviews\Avisverifies\Helper\ReviewsAPI;
 
class AjaxReviews extends \Magento\Framework\App\Action\Action
{
    protected $_resultPageFactory;
    protected $_resultJsonFactory;
    
    /**
     * __construct
     *
     * @param $context
     * @param $resultPageFactory
     * @return void
     */
    public function __construct( Context $context, 
        \Magento\Framework\View\Result\PageFactory $resultPageFactory, 
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory )
    {
        $this->_resultPageFactory = $resultPageFactory;
        $this->_resultJsonFactory = $resultJsonFactory;
        parent::__construct( $context );
    }
    
    /**
     * Return a reviews page
     *
     * @return echo object
     */
    public function execute()
    {
        $request = $this->getRequest();

        // Check for post
        if($request->getPost()) {

            $avisVerifiesPageNumber = $request->getPost('avisVerifiesPageNumber');
            $avisVerifiesProductSku = $request->getPost('avisVerifiesProductSku');
            $avisVerifiesProductId = $request->getPost('avisVerifiesProductId');
            $avisVerifiesReviewsPerPage = $request->getPost('avisVerifiesReviewsPerPage');

            if (!empty($avisVerifiesPageNumber)  && 
                !empty($avisVerifiesProductSku)  && 
                !empty($avisVerifiesProductId)   && 
                !empty($avisVerifiesReviewsPerPage)
            ) {

                $ObjectManager = \Magento\Framework\App\ObjectManager::getInstance();
                $helperReviewsAPI = $ObjectManager->create('Netreviews\Avisverifies\Helper\ReviewsAPI');
                $reviewsList = $helperReviewsAPI->getProductReviews($avisVerifiesProductSku, $avisVerifiesProductId, $avisVerifiesPageNumber, $avisVerifiesReviewsPerPage, array(1, 2, 3, 4, 5)); 

                if ($reviewsList = json_decode($reviewsList)) {

                    $htmltoreturn = "";

                    foreach ($reviewsList as $review) {
                        $name = (!empty($review->firstname))?$review->firstname." ":"Anonymous ";
                        $name .= (!empty($review->lastname))?$review->lastname[0].".":"";

                        $htmltoreturn .= '<div class="reviewAV">
                                            <div class="reviewInfosAV">
                                                <div style="text-transform:capitalize">
                                                    '.$name.'
                                                </div>
                                                <div>
                                                    '.__('the').'&nbsp;'.date(__('d/m/Y'), strtotime($review->review_date)).'
                                                </div>
                                                <div class="netreviews-stars">
                                                    <div class="netreviews_bg_stars netreviews_bg_stars_gold">
                                                        <span style="width:'.(($review->rate/5)*100).'%">&nbsp;</span>
                                                    </div>
                                                    '.$review->rate.'/5
                                                </div>
                                            </div>
                                            <div class="triangle-border top">
                                                '.$review->review.'
                                            </div>
                                        </div>';
                    }
                }   
            }   
        }
        $this->getResponse()->setBody($htmltoreturn);
    }
}