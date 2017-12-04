<?php
namespace Netreviews\Avisverifies\Controller\Index;
 
use Magento\Framework\App\Action\Context;
 
class DialogController extends \Magento\Framework\App\Action\Action
{
    protected $_resultPageFactory;
    protected $objectManager;
    
    
    /**
     * __construct
     *
     * @param $context
     * @param $resultPageFactory
     * @return void
     */
    public function __construct(
        Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory
    )
    {
        $this -> _resultPageFactory = $resultPageFactory;
    $this -> objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        parent::__construct( $context );
    }
    
    
    
    /**
     * execute
     * Recupera la Clave Secreta para el idWebsite que se le da.
     *
     * @return echo object
     */
    public function execute()
    {
        $request = $this -> getRequest();

        // API helper
        $helperAPI = $this -> objectManager -> create('Netreviews\Avisverifies\Helper\API');
        $helperAPI -> construct( $request );
        
        // Data helper
        $helperDATA = $this -> objectManager -> create('Netreviews\Avisverifies\Helper\Data');
        
        // Check for post
        if ( $request -> getPost() ) {
            
            // setup magento helperDATA
            $helperDATA -> setup( array(
            'idWebsite' => $helperAPI -> msg('idWebsite'),
                'query' => $helperAPI -> msg('query'),
            ));

            // Check security data
            $this -> checkSecurityData( $helperDATA, $helperAPI );
           
            /* ############ DEBUT DU TRAITEMENT ############*/
            // Switch case on query type.
            switch ( $request -> getPost('query') ) {
                case 'isActiveModule':
                    $toReply['debug'] = "Module Installé et activé";
                    $toReply['return'] = 1;
                    $toReply['query'] = $request -> getPost('query');
                    break;
                case 'setModuleConfiguration' :
                    $toReply = $this -> setModuleConfiguration( $helperDATA, $helperAPI );
                    break;  
                case 'getModuleAndSiteConfiguration' :
                    $toReply = $this -> getModuleAndSiteConfiguration( $helperDATA, $helperAPI );
                    break;
                case 'getOrders' :
                    $toReply = $this -> getOrders( $helperDATA, $helperAPI );
                    break;
                case 'setProductsReviews' :
                    //$toReply = $this -> setProductsReviews( $DATA, $API );
                    break;  
                case 'truncateTables' : // Used by API4
                    //$toReply = $this -> truncateTables( $DATA, $API );
                    $toReply = $this -> deleteModuleAndSiteConfiguration( $helperDATA, $helperAPI );
                    break;
                case 'deleteModuleAndSiteConfiguration' : // Used by BO AV
                    $toReply = $this -> deleteModuleAndSiteConfiguration( $helperDATA, $helperAPI );
                    break;
                case 'getUrlProducts' :
                    //$toReply = $this -> getUrlProducts( $DATA, $API );
                    break;
                case 'getProductParentIds' :
                    //$toReply = $this -> getProductParentIds( $DATA, $API );
                    break;
                case 'cleanCache' :
                    $toReply = $this -> cleanCache( $helperAPI );
                    break;
                case 'setOrderFlag' :
                    $toReply = $this -> setOrderFlag( $helperDATA, $helperAPI );
                    break;
                default:
                    $toReply['debug'] = "Aucun variable query";
                    $toReply['return'] = 2;
                    $toReply['query'] = $request -> getPost('query');
            }
            // Affichage du retour des fonctions pour récupération du résultat par AvisVerifies
            $valueToPrint = $helperAPI -> AC_encode_base64( serialize( $toReply ) );
            $helperAPI -> echome( $valueToPrint );

        }
        else {
            $reponse['debug'] = "Aucun variable POST";
            $reponse['return'] = 2;
            $reponse['query'] = "";
            $valueToPrint = $helperAPI -> AC_encode_base64( serialize( $reponse ) );
            $helperAPI -> echome( $valueToPrint );
        }

    }
    
    
    
    /**
     * checkSecurityData
     * Verifica si la firma es la misma que en nuestro sitio OV.
     *
     * @param $helperDATA - class object
     * @param $helperAPI - class object
     * @return void
     */
    protected function checkSecurityData( $helperDATA, $helperAPI ) {
        $sign = $helperAPI -> msg( 'sign' );
        $errorArray = array();
        // Si il n'y a pas de sign dans l'appel POST
        if ( empty( $sign ) ) {
            $errorArray['debug'] = "Please sign your request.";
            $errorArray['message'] = "Please sign your request.";
            $errorArray['return'] = 3;
            $errorArray['query'] = 'checkSecurityData';
        }
        // Si l'idWebsite ou la secretKey est vide
        elseif ( empty( $helperDATA -> idwebsite ) OR empty( $helperDATA -> secretkey ) ) {
            $errorArray['debug'] = "Identifiants clients non renseignés sur le module";
            $errorArray['message'] = "Identifiants clients non renseignés sur le module";
            $errorArray['return'] = 3;
            $errorArray['query'] = 'checkSecurityData';
        }
        // Si le parametre sign ne correpsond pas à ce qu'on a calculé
        elseif ( $helperDATA -> SHA1 !== $sign ) {
            $errorArray['message'] = "La signature est incorrecte";        
            $errorArray['debug'] = "La signature est incorrecte";          
            $errorArray['return'] = 5;
            $errorArray['query'] = 'checkSecurityData';    
        }
        // Si il y a une erreur
        if ( count( $errorArray ) ) {
            $valueToPrint = $helperAPI -> AC_encode_base64( serialize( $errorArray ) );
            $helperAPI -> echome( $valueToPrint );
        }
    }
    
    
    
    /**
     * setModuleConfiguration
     * Inserta en la base de datos magento la configuración enviada desde la plataforma NetReviews.
     *
     * @param $helperDATA - class object
     * @param $helperAPI - class object
     * @return void
     */
    protected function setModuleConfiguration( $helperDATA, $helperAPI ) {
        $activeStores = $helperDATA -> allStoresId;
        
        $platformFields = array(
            'processinit',
            'orderstateschoosen',
            'delay',
            'forbidden_email',
            'getprodreviews',
            'displayprodreviews',
            'scriptfloat_allowed',
            'scriptfloat',
            'urlcertificat',
            'requirejs',
            'reviews_amazon_url'
        );
        
        $postFields = array(
            'init_reviews_process',
            'id_order_status_choosen',
            'delay',
            'forbidden_mail_extension',
            'get_product_reviews',
            'display_product_reviews',
            'display_float_widget',
            'script_float_widget',
            'url_certificat',
            'requirejs',
            'reviews_amazon_url'
        );
        
        $configData = array();
        $basePath = 'av_configuration/plateforme/';
        foreach ( $activeStores as $k => $store ) {
            foreach ( $platformFields as $i => $platformField ) {
                
                if ( $postFields[ $i ] == 'delay' ) {
                    $delay = $helperAPI -> msg( $postFields[ $i ] );
                    $delay = ( $delay == ''  ||  $delay == null  ||  empty( $delay )  ||  ! $delay ) ? 0 : $delay;
                    $configData[$k][ $i ] = [
                        'scope' => $store['scope'],
                        'scope_id' => $store['scope_id'],
                        'path' => $basePath . $platformField,
                        'value' => $delay
                    ];
                    
                } else if ( $postFields[ $i ] == 'id_order_status_choosen' ) {
                    $id_order_status_choosen = ( is_array( $helperAPI -> msg( $postFields[ $i ] ) ) )  ?  implode( ';', $helperAPI -> msg( $postFields[ $i ] ) )  :  $helperAPI -> msg( $postFields[ $i ] );
                    
                    $configData[$k][ $i ] = [
                        'scope' => $store['scope'],
                        'scope_id' => $store['scope_id'],
                        'path' => $basePath . $platformField,
                        'value' => $id_order_status_choosen
                    ];
                    
                } else {
                    $configData[$k][ $i ] = [
                        'scope' => $store['scope'],
                        'scope_id' => $store['scope_id'],
                        'path' => $basePath . $platformField,
                        'value' => $helperAPI -> msg( $postFields[ $i ] )
                    ];
                }
            }
        }
        
        // Insert platform config in "core_config_data" table
        $connection = $this -> objectManager -> get('Magento\Framework\App\ResourceConnection') -> getConnection('\Magento\Framework\App\ResourceConnection::DEFAULT_CONNECTION');
        $tableName   = $connection -> getTableName('core_config_data'); // It will return table with prefix
        foreach ( $configData as $store ) {
                    foreach ( $store as $val ) {
                        $connection -> insertOnDuplicate( $tableName , $val, ['value'] );
                    }
        }
        
        $reponse['configuredStores'] = $activeStores;
        $reponse['message'] = $this -> _getModuleAndSiteInfos( $helperDATA );
                $reponse['debug'] = "La configuration du site a été mise à jour";       
                $reponse['return'] = 1; //A definir     
                $reponse['query'] = $helperAPI -> msg('query');
        
        return $reponse;
    }
    
    
    
    /**
     * getModuleAndSiteConfiguration
     * Recupera los parámetros de configuración del sitio y del módulo.
     *
     * @param $helperDATA - class object
     * @param $helperAPI - class object
     * @return void
     */
    protected function getModuleAndSiteConfiguration( $helperDATA, $helperAPI ) {       
        $reponse['message'] = $this -> _getModuleAndSiteInfos( $helperDATA );
        $reponse['query'] = $helperAPI -> msg('query');
        $reponse['return'] = ( empty( $reponse['message'] ) ) ? 2 : 1; // 2:error, 1:success.
        return $reponse;
    }
    
    
    
    /**
     * getModuleAndSiteConfiguration
     * Recupera los parámetros de configuración del sitio y del módulo.
     *
     * @param $helperDATA - class object
     * @param $helperAPI - class object
     * @return void
     */
    protected function _getModuleAndSiteInfos( $helperDATA ) {
        $productMetadata = $this -> objectManager -> get('Magento\Framework\App\ProductMetadataInterface');
        $magentoVersion = $productMetadata -> getVersion(); // Will return the magento version

        $moduleInfo =  $this -> _objectManager -> get('Magento\Framework\Module\ModuleList') -> getOne('Netreviews_Avisverifies'); // SR_Learning is module name

        // Get All Websites
        $websites_mapping = $helperDATA -> getAllWebsites();

        // Get All Stores
        $stores_mapping = $helperDATA -> getAllStores();

        // Get All Status
        $arrayStatusCollectionSimplified = $helperDATA -> getAllStatus();

        // Create the array in order to NetReviews system process the data.
        $site_mapping = $helperDATA -> getStoresWithIdwebsite( $websites_mapping, $stores_mapping );

        $idStore = $helperDATA -> idStore;
        $temp = array(
                // Magento configuration
                'Version_PS'            => $magentoVersion,
                'Version_Module'        => $moduleInfo['setup_version'],
                'Enabled_Website'       => $helperDATA -> getMainConfig( "enabledwebsite", $idStore ),
                'idWebsite'             => $helperDATA -> idwebsite,
                'Id_Website_encours'    => $idStore . ' [' . $helperDATA -> defaultOrStoreOrWebsite . ']',

                // Advanced configuration
                'Use_parent_url'        => $helperDATA -> getAdvancedConfig( "use_parent_url", $idStore ),

                // Platform configuration
                'Initialisation_du_Processus'   => $helperDATA -> getSpecificPlatformConfig( "processinit", $idStore, $helperDATA -> defaultOrStoreOrWebsite ),
                'Statut_choisi'                 => $helperDATA -> getSpecificPlatformConfig( "orderstateschoosen", $idStore, $helperDATA -> defaultOrStoreOrWebsite ),
                'Requirejs'                     => $helperDATA -> getSpecificPlatformConfig( "requirejs", $idStore, $helperDATA -> defaultOrStoreOrWebsite ),
                'Reviews_amazon_url'            => $helperDATA -> getSpecificPlatformConfig( "reviews_amazon_url", $idStore, $helperDATA -> defaultOrStoreOrWebsite ),
                'Delay'                         => $helperDATA -> getSpecificPlatformConfig( "delay", $idStore, $helperDATA -> defaultOrStoreOrWebsite ),
                'Emails_Interdits'              => $helperDATA -> getSpecificPlatformConfig( "forbidden_email", $idStore, $helperDATA -> defaultOrStoreOrWebsite ),
                'Recuperation_Avis_Produits'    => $helperDATA -> getSpecificPlatformConfig( "getprodreviews", $idStore, $helperDATA -> defaultOrStoreOrWebsite ),
                'Affiche_Avis_Produits'         => $helperDATA -> getSpecificPlatformConfig( "displayprodreviews", $idStore, $helperDATA -> defaultOrStoreOrWebsite ),
                //'Affichage_Widget_Fixe'           => $helperDATA -> getPlatformConfig( "scriptfixe_allowed", $idStore ),
                //'Position_Widget_Fixe'            => $helperDATA -> getPlatformConfig( "scriptfixe_position", $idStore ),
                //'Script_Widget_Fixe'          => $helperDATA -> getPlatformConfig( "scriptfixe", $idStore ),
                'Affichage_Widget_Flottant'     => $helperDATA -> getSpecificPlatformConfig( "scriptfloat_allowed", $idStore, $helperDATA -> defaultOrStoreOrWebsite ),
                'Script_Widget_Flottant'        => $helperDATA -> getSpecificPlatformConfig( "scriptfloat", $idStore, $helperDATA -> defaultOrStoreOrWebsite ),
                'Map_Configuration'             => $site_mapping, // use $websites_mapping/$stores_mapping to get all mapping even if websites/stores are not configured for the plugin.
                'Liste_des_statuts'             => $arrayStatusCollectionSimplified,
                'Date_Recuperation_Config'      => date('Y-m-d H:i:s')
                );

        return $temp;
    }
    
    
    
    /**
     * getOrders
     * Recupera los pedidos.
     *
     * @param $helperDATA - class object
     * @param $helperAPI - class object
     * @return void
     */
    protected function getOrders( $helperDATA, $helperAPI ) {
        // Platform configuration
        $reponse['return'] = 1;
        $reponse['query'] = $helperAPI -> msg('query');

        $idStore = $helperDATA -> idStore;

        // Platform configuration
        $processinit = $helperDATA -> getSpecificPlatformConfig( "processinit", $idStore, $helperDATA -> defaultOrStoreOrWebsite );
        $status = null;
        if ( $processinit == null ) {
            $reponse['return'] = 0;
            $reponse['message'] = "Aucun évènement processinit n'a été renseigné pour la récupération des commandes."; //test con $a_row
            return $reponse;
        } else if ( $processinit == 'onorderstatuschange' ) {
            $reponse['debug']['mode'] = $processinit;
            $status = $helperDATA -> getSpecificPlatformConfig( "orderstateschoosen", $idStore, $helperDATA -> defaultOrStoreOrWebsite );
            if ( $status == null ) {
                $reponse['return'] = 0;
                $reponse['message'] = "Aucun évènement onorderstatuschange n'a été renseigné."; //test con $a_row
                return $reponse;
            } else {
                $reponse['debug']['status'] = $status;
            }
        } else if ( $processinit == 'onorder' ) {
            $reponse['debug']['mode'] = $processinit;
        }
        $getProductReviews = $helperDATA -> getSpecificPlatformConfig( "getprodreviews", $idStore, $helperDATA -> defaultOrStoreOrWebsite );
        $delay = $helperDATA -> getSpecificPlatformConfig( "delay", $idStore, $helperDATA -> defaultOrStoreOrWebsite );
        $reponse['getProductReviews'] = $getProductReviews;
        $forbiddenEmails = $helperDATA -> getSpecificPlatformConfig( "forbidden_email", $idStore, $helperDATA -> defaultOrStoreOrWebsite );

        // Get stores actives for a specific IdWebsite
        $activeStores = $helperDATA -> allStoresId;

        // Check if $activeStores have websites and get its stores id (because if we have a website it is active)
        foreach ( $activeStores as $val ) {
            if ( $val['scope'] == 'websites' ) {
                $storesInWebsite = $helperDATA -> getAllStoresIdsForWebsite( $val['scope_id'] );
                $activeStores = array_unique( array_merge( $activeStores, $storesInWebsite ), SORT_REGULAR ); // SORT_REGULAR - compare les éléments normalement (ne modifie pas les types).
            }
        }


        $i = 0;
        $allOrders = array();
        $is_cron = $helperAPI -> msg('no_flag');

        // Get orders for each Store
        foreach ( $activeStores as $currentStore ) {

            // Jump this iteration is it is default configuration or it is a website in order to avoid duplicate values because we'll process each store for this website.
            if ( $currentStore['scope'] == 'websites' || $currentStore['scope'] == 'default' ) {
                continue;
            }

            $o_orders = $helperDATA -> getOrdersAddingStatusFilter( $currentStore['scope_id'], $status );

            // Si hay e-mails prohibidos agregar el filtro para eliminar los pedidos con estos e-mails.
            if ( $forbiddenEmails ) {
                $reponse['debug']['Forbidden_Emails'] = $forbiddenEmails;
                $o_orders = $helperDATA -> addFilterForbiddenEmails ( $o_orders, $forbiddenEmails );
            }
            $listOrders = array();

            foreach( $o_orders as $_order ) {
                $currentOrder = $_order -> getData();

                $listOrders[ $i ]['store_id'] = $currentOrder["store_id"];
                $listOrders[ $i ]['id_order'] = $currentOrder["increment_id"];
                $timestamp = strtotime( $currentOrder["created_at"] );
                $listOrders[ $i ]['amount_order'] = $currentOrder["base_grand_total"] . " " . $currentOrder["base_currency_code"];
                $listOrders[ $i ]['date_order'] = $timestamp;
                $listOrders[ $i ]['date_order_formatted'] = date('Y-m-d H:i:s', $timestamp );
                $listOrders[ $i ]['date_order_formatted'] = date('Y-m-d H:i:s', $timestamp );
                $isFlag = $helperDATA -> isFlag( $currentOrder["entity_id"] );
                $listOrders[ $i ]['is_flag'] = $isFlag['av_flag'];
                $listOrders[ $i ]['date_av_getted_order'] = $isFlag['av_horodate_get'];
                $listOrders[ $i ]['state_order'] = $currentOrder["status"];
                $listOrders[ $i ]['firstname_customer'] = ( empty( $currentOrder["customer_firstname"] ) ) ? 'anonymous' : $currentOrder["customer_firstname"];
                $listOrders[ $i ]['lastname_customer'] = ( empty( $currentOrder["customer_lastname"] ) ) ? 'anonymous' : $currentOrder["customer_lastname"];
                $listOrders[ $i ]['email_customer'] = $currentOrder["customer_email"];

                // =============== WITH PRODUCTS ===============
                if ( $getProductReviews == 'yes' ) {
                        $listOrders[ $i ]['products'] = Array();

                        $listProducts = $helperDATA -> getProducts( $_order );

                        foreach ( $listProducts as $key => $product ) {
                            $listOrders[ $i ]['products'][ $key ] = $product;
                        }
                }
                // =============== WITH PRODUCTS END ===============

                // =============== FLAG ORDER TO 1 IF CALL COME FROM CRON ===============
                if ( $is_cron == 0 ) {
                    if ( $helperDATA -> flagOrderTo1( $currentOrder["entity_id"] ) == 1 ) {
                        // Continue loop normally
                    } else {
                        unset( $listOrders[ $i ] ); // Delete current order because is not flag to 1.
                        continue;
                    }
                }
                // =============== FLAG ORDER TO 1 IF CALL COME FROM CRON END ===============

                $i++;
            } // endForeach $o_orders

            $allOrders = array_merge( $allOrders, $listOrders);
        } // foreach store_id

        $arrayOrders['nb_orders'] = $i;
        $arrayOrders['delay'] = $delay;
        // $arrayOrders['nb_orders_bloques'] = ;

        // Platform configuration
        $reponse['debug']['force'] = $helperAPI -> msg('force');
        $reponse['debug']['produit'] = $getProductReviews;
        $reponse['debug']['no_flag'] = $is_cron;
        $reponse['debug']['get_orders_from_stores'] = $activeStores;

        // Advanced configuration
        $reponse['debug']['Use_parent_url'] = $helperDATA -> getAdvancedConfig( "use_parent_url", $idStore );

        $arrayOrders['list_orders'] = $allOrders;
        $reponse['message'] = $arrayOrders; //test con $a_row
        /*
        $reponse['message']['nb_orders_bloques'] = 0;
        $reponse['debug']['no_flag'] = $API->msg('no_flag');
        */
        return $reponse;
    }



    /**
     * deleteModuleAndSiteConfiguration
     * Borra en la base de datos magento la configuración enviada desde la plataforma NetReviews.
     *
     * @param $helperDATA - class object
     * @param $helperAPI - class object
     * @return array
     */
    protected function deleteModuleAndSiteConfiguration( $helperDATA, $helperAPI ) {
        $idStore = $helperDATA -> idStore;
        $defaultOrStoreOrWebsite = $helperDATA -> defaultOrStoreOrWebsite;

        // Delete platform config in "core_config_data" table.
        $connection = $this -> objectManager -> get('Magento\Framework\App\ResourceConnection') -> getConnection('\Magento\Framework\App\ResourceConnection::DEFAULT_CONNECTION');
        $tableName   = $connection -> getTableName('core_config_data'); // It will return table with prefix
        $query = "DELETE FROM " . $tableName . " WHERE path LIKE '%av_configuration/plateforme%' AND scope = '" . $defaultOrStoreOrWebsite . "' AND scope_id = '" . $idStore . "'";
        $result = $connection -> query( $query );

        $reponse['queryExecuted'] = $query;
        $reponse['message'] = $this -> _getModuleAndSiteInfos( $helperDATA );
        $reponse['debug'] = "La configuration du site a été effacée.";
        $reponse['return'] = ( $result ) ? 1 : 2 ; // 1:success, 2:error.
        $reponse['query'] = $helperAPI -> msg('query');

        return $reponse;
    }
    
    
    
    /**
     * cleanCache
     * Efface les caches.
     *
     * @return void
     */
    protected function cleanCache( $helperAPI ) {
        $_cacheTypeList = $this -> objectManager -> create('Magento\Framework\App\Cache\TypeListInterface');
        $_cacheFrontendPool = $this -> objectManager -> create('Magento\Framework\App\Cache\Frontend\Pool');
        
        $types = array(
            'config',
            'layout',
            'block_html',
            'collections',
            'db_ddl',
            'eav',
            'full_page',
            'reflection',
            'translate',
            'config_integration',
            'config_integration_api',
            'config_webservice'
        );

        // Clearing caches
        foreach ( $types as $type ) {
            $_cacheTypeList -> cleanType( $type );
        }

        // Clearing in-memory pool of all cache instances
        foreach ( $_cacheFrontendPool as $cacheFrontend ) {
            $cacheFrontend -> getBackend() -> clean();
        }
        
        $reponse['message'] = 'Cache cleaned successfully!!';
        $reponse['query'] = $helperAPI -> msg('query');
        $reponse['return'] = ( empty( $reponse['message'] ) ) ? 2 : 1; // 2:error, 1:success.
        return $reponse;
    }


    /**
     * setOrderFlag
     * Force le flag des commandes à 1.
     *
     * @return void
     */
    protected function setOrderFlag( $helperDATA, $helperAPI ) {
        $message = $helperDATA -> flagOrdersTo1();
            $reponse['message'] = $message[0];
        
            if ( array_key_exists( 'NO_FLAG', $message ) ) {
                $reponse['NO_FLAG'] = $message['NO_FLAG'];
            }
            if ( array_key_exists( 'FLAG', $message ) ) {
                $reponse['FLAG'] = $message['FLAG'];
            }
        
        $reponse['query'] = $helperAPI -> msg('query');
        $reponse['return'] = ( empty( $reponse['message'] ) ) ? 2 : 1; // 2:error, 1:success.
        
        return $reponse;
    }
    
    
    
}