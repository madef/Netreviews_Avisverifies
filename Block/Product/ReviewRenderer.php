<?php

namespace Netreviews\Avisverifies\Block\Product;
use Magento\Catalog\Block\Product\ReviewRendererInterface;
use Magento\Catalog\Model\Product;

class ReviewRenderer extends \Magento\Framework\View\Element\Template implements ReviewRendererInterface {

    public $productSku;
    public $productId;
    public $allAverage = array();

    protected $_availableTemplates = [
        self::DEFAULT_VIEW  => 'stars.phtml',
        self::FULL_VIEW     => 'stars.phtml',
        self::SHORT_VIEW    => 'short_stars.phtml',
    ];

    public function __construct
    (
        \Magento\Framework\View\Element\Template\Context $context
    )
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $ReviewsAPI = $objectManager->create('Netreviews\Avisverifies\Helper\ReviewsAPI');
        $this->allAverage = $ReviewsAPI->getAllAverage();
        parent::__construct($context);
    }

    public function getStoreId() {
        return $this->_storeManager->getStore()->getId();
    }

    public function getAverageProduct() {
        $productIdentifier = !empty($this->productSku) ? $this->productSku : $this->productId;
        if (!empty($productIdentifier)) {
            if (isset($this->allAverage[$productIdentifier])) {
                $return = $this->allAverage[$productIdentifier];
            }
        }
        return isset($return) ? $return : array('nb_reviews'=>0,'rate'=>0);
    }

    public function getReviewsSummaryHtml(\Magento\Catalog\Model\Product $product, $templateType = self::DEFAULT_VIEW, $displayIfNoReviews = false) {
        if (empty($this->_availableTemplates[$templateType])) { $templateType = self::DEFAULT_VIEW; }
        $this->productSku = $product->getSku();
        $this->productId = $product->getId();
        $this->setTemplate($this->_availableTemplates[$templateType]);
        return $this->toHtml();
    }

}
