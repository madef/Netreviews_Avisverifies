<?php
namespace Netreviews\Avisverifies\Helper;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\App\Helper\Context;

class ReviewsAPI extends AbstractHelper
{
    protected $objectManager;
    public $amazonUrl = '';
    
    /**
     * Constructor.
     *
     * @param $context
     * @param $objectManager
     * @param $storeManager
     * @return void
     */
    public function __construct(Context $context, ObjectManagerInterface $objectManager, \Magento\Store\Model\StoreManagerInterface $storeManager) {
        $this -> objectManager = $objectManager;
        $this->_storeManager = $storeManager;
        $helper = \Magento\Framework\App\ObjectManager::getInstance();
        $helper = $helper->create('Netreviews\Avisverifies\Helper\Data');
        $this->amazonUrl = $helper->getPlatformConfig('reviews_amazon_url',$this->getStoreId());
        parent::__construct( $context );
    }

    public function getStoreId() {
        return $this->_storeManager->getStore()->getId();
    }


    // Return the average rate & count of all products
    public function getAllAverage() {
        $return = array();
        $url = $this -> amazonUrl . 'AVERAGE/global_average.csv';
        $urlResponse = $this -> get_http_response_code( $url );
        if (!empty($this->amazonUrl) && $urlResponse == "200") {
            $content = @file_get_contents( $url );
            $lines = explode(PHP_EOL, $content);
            foreach ($lines as $line) {
                $line = str_getcsv($line,";",'"');
                if (isset($line[0]) && isset($line[1]) && isset($line[2])) {
                    $return[$line[0]] = array(
                        'rate' => (float) $line[2],
                        'nb_reviews' => (int) $line[1]
                    );
                }
            }
        }
        return $return;
    }

    // Return the average rate & count of a specific product
    public function getProductAverage($productSku, $productId) {
        $productIdentifier = !empty($productSku) ? $productSku : $productId;
        $productIdentifier = urlencode(urlencode($productIdentifier));
        $url = $this -> amazonUrl . 'AVERAGE/' . $productIdentifier . '.csv';
        $urlResponse = $this->get_http_response_code( $url );
        if (!empty( $this -> amazonUrl ) && $urlResponse == "200" ) {
            $content = @file_get_contents( $url );
            $lines = explode(PHP_EOL, $content);
            foreach ($lines as $line) {
                $line = str_getcsv($line,";",'"');
                if (isset($line[0]) && !empty($line[0]) && $line[0] != 'rate' && is_numeric($line[0])) {
                    $return['rate'] = (float) $line[0];
                }
                if (isset($line[1]) && !empty($line[1]) && $line[1] != 'count' && is_numeric($line[1])) {
                    $return['nb_reviews'] = $line[1];
                }
            }
        }
        return isset($return) ? $return : array('nb_reviews'=>0,'rate'=>0);
    }

    // Return the reviews of a specific product
    public function getProductReviews($productSku, $productId, $pageNumber, $reviewsPerPage, $rateFilter = array(1, 2, 3, 4, 5)) {
        
        $return = array();
        $productIdentifier = !empty($productSku) ? $productSku : $productId;
        $productIdentifier = urlencode(urlencode($productIdentifier));
        
        if (!empty($productIdentifier)) {
            $url = $this -> amazonUrl . 'REVIEWS/index_' . $productIdentifier . '.json';
            $urlResponse = $this -> get_http_response_code( $url );
            if ( !empty( $this -> amazonUrl ) && $urlResponse == "200" ) {
                
                // On récupère le contenu du fichier d'index
                $contentIndex = @file_get_contents( $url );
                if ($contentIndex = json_decode($contentIndex)) {

                    // On calcule le nombre d'avis total existants pour ce filtre par note
                    $reviewsTotalNumberToShow = 0;
                    foreach ($contentIndex->rated as $key => $value) {
                        if(in_array($key+1, $rateFilter)) {
                            $reviewsTotalNumberToShow += $value;
                        }
                    }

                    $offset = ($pageNumber*$reviewsPerPage);
                    $incrementReviews = 0;

                    // Si on demande à afficher une page supérieur au nombre de page max
                    if($offset < $reviewsTotalNumberToShow) {

                        // Si le nombre d'avis demandé est supérieur au nombre d'avis restant
                        if($reviewsPerPage > ($reviewsTotalNumberToShow-$offset)) {
                            $reviewsPerPage = ($reviewsTotalNumberToShow-$offset);
                        }

                        // On fait le tour des pages d'avis pour chercher ce qui nous intéresse
                        for ($incrementPages = 0 ; (($reviewsPerPage>0) && ($incrementPages < sizeof($contentIndex->pages))) ; $incrementPages++) {

                            $nb_review_page = 0;
                            $needOpenPage = false;
                            
                            // calcule du nb d'avis concernés dans la page
                            foreach($rateFilter as $value) {
                                $nb_review_page += $contentIndex->pages[$incrementPages][$value-1];
                            }

                            if($offset < $nb_review_page) {
                                $needOpenPage = true;
                            }
                            elseif($offset >= $nb_review_page) {
                                $offset = $offset - $nb_review_page;
                            }

                            if($needOpenPage) {

                                // On interroge une page contenant des avis
                                $url = $this->amazonUrl.'REVIEWS/p'.$incrementPages.'_'.$productIdentifier.'.json';
                                $urlResponse = $this->get_http_response_code( $url );
                                if ( !empty( $this->amazonUrl ) && $urlResponse == "200" ) {
                                    $contentPage = @file_get_contents( $url );
                                    if ($contentPage = json_decode($contentPage)) {
                                        if(!empty($contentPage)) {
                                            $rate_in_filter = false;

                                            // Pour chaque avis
                                            foreach($contentPage as $key_review => $review) {
                                                // On check si cet avis nous interesse en fonction de sa note
                                                if(in_array($contentPage[$key_review]->rate, $rateFilter)) {
                                                    if($offset == 0) {
                                                        $contentPage[$key_review]->review_date = date_create_from_format('Y-m-d H:i:s',$contentPage[$key_review]->review_date);
                                                        $contentPage[$key_review]->review_date = date_format($contentPage[$key_review]->review_date, 'Y-m-d');
                                                        $return[] = $contentPage[$key_review];
                                                        $reviewsPerPage--;
                                                    } else {
                                                        $offset--;
                                                    }
                                                    if($reviewsPerPage == 0) {
                                                        break;
                                                    }
                                                }
                                            }
                                            
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
        return json_encode($return);
    }




    public function get_http_response_code($url) {
    	if ( !empty ( $url ) && strpos($url, 'http://') !== false ) {
	        $headers = get_headers($url);
    	    return substr($headers[0], 9, 3);
    	} else {
    		return false;
    	}
    }

}