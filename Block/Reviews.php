<?php

namespace Netreviews\Avisverifies\Block;

class Reviews extends \Magento\Framework\View\Element\Template
{
    protected $_reviewsFactory;
    protected $_reviewsCollection;
    protected $_coreRegistry;

    public function __construct
    (
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Framework\Registry $registry
    )
    {
        $this->_coreRegistry = $registry;
        parent::__construct($context);
    }

    public function getProductId()
    {
        $product = $this->_coreRegistry->registry('product');
        return $product ? $product->getId() : null;
    }

    public function getProductSku()
    {
        $product = $this->_coreRegistry->registry('product');
        return $product ? $product->getSku() : null;
    }

    public function getStoreId()
    {
        return $this->_storeManager->getStore()->getId();
    }

    public function getLocale()
    {
        $ObjectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $resolver = $ObjectManager->get('Magento\Framework\Locale\Resolver');
        return $resolver->getLocale();
    }

}