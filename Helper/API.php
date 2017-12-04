<?php
namespace Netreviews\Avisverifies\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\App\Helper\Context;

class API extends AbstractHelper
{
    protected $storeManager;
    protected $objectManager;
    public $msg = array();
	
	

	/**
     * Constructor.
     *
     * @param $context
     * @param $objectManager
     * @param $storeManager
     * @return void
     */
    public function __construct(Context $context,
        ObjectManagerInterface $objectManager,
        StoreManagerInterface $storeManager
    ) {
        $this -> objectManager = $objectManager;
        $this -> storeManager  = $storeManager;
        parent::__construct( $context );
    }
	


	/**
     * Codifica el mensaje.
     *
     * @param $_data
     * @return object
     */
    public function AC_encode_base64( $_data ) { 
        $sBase64 = base64_encode( $_data ); 
        return strtr( $sBase64, '+/', '-_' ); 
    }
	
	
	
	/**
     * Decodifica el mensaje.
     *
     * @param $_data
     * @return object
     */
    public function AC_decode_base64( $sData ) { 
        $sBase64 = strtr( $sData, '-_', '+/'); 
        return base64_decode( $sBase64 ); 
    }
	


	/**
     * construct
     *
     * @param $request
     * @return void
     */
	public function construct( $request ) {
        if ( $request -> getPost('message') ) {
            $this -> msg = unserialize( $this -> AC_decode_base64( $request -> getPost('message') ) );
        }
    }
	


	/**
     * Verifica si un valor está vacío. Check for isset is essential, because we could have empty $msg.
     *
     * @param $index
     * @return object
     */
     public function msg( $index ) {
        return ( isset( $this -> msg[ $index ] ) ) ? $this -> msg[ $index ] : null ;
    }
	
	

	/**
     * Imprime la variable dada.
     *
     * @param $_value
     * @return void
     */
    public function echome( $_value ) {
        printf( $_value );
        //exit;
    }
    
}