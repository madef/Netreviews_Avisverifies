<?php
namespace Netreviews\Avisverifies\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\App\Helper\Context;
use Magento\Store\Model\ScopeInterface;

class Data extends AbstractHelper
{
    protected $storeManager;
    protected $objectManager;

    public $idwebsite;
    public $secretkey;
    public $SHA1;
    public $idStore;
    public $defaultOrStoreOrWebsite;
	public $allStoresId;

    const XML_PATH_AVISVERIFIES = 'av_configuration/';
    const XML_PATH_AVISVERIFIES_MAIN = 'system_integration/';
    const XML_PATH_AVISVERIFIES_ADVANCED = 'advanced_configuration/';
    const XML_PATH_AVISVERIFIES_PLATFORM = 'plateforme/';
    const XML_PATH_AVISVERIFIES_PLA = 'av_pla/fields_mapping/';
	
	
	
	/**
     * __construct
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
     * setIdStore
     *
     * @param $val
     * @return void
     */
    public function setIdStore( $val ) {
    	$this -> idStore = $val;
	}
	
	
	
	/**
     * setDefaultOrStoreOrWebsite
     *
     * @param $val
     * @return void
     */
    public function setDefaultOrStoreOrWebsite( $val ) {
    	$this -> defaultOrStoreOrWebsite = $val;
	}
	
	
	/**
     * setup
     *
     * @param $_msg
     * @return void
     */
    public function setup( array $_msg ) {
    	
        // On recoit dans l'appel API un idWebsite
        $this -> idwebsite = $_msg['idWebsite'];
		
        // On verifie donc si on a la secretKey correspondante
        $this -> secretkey = $this -> getModuleSecretKey( $_msg['idWebsite'] );
		
		// Get all store ids enabled with the idWebsite received
		$this -> allStoresId = $this -> getModuleActiveStoresIds( $_msg['idWebsite'] );
		
        /*
        * SHA1, secret Hashing.
        * The SHA1 signature is required to be sure that we can't use the Dialog controller
        * to perform operations without secret key provided.
        */
        $this -> SHA1 = SHA1( ( isset( $_msg['query'] ) ? $_msg['query'] : '' ) . $this -> idwebsite . $this -> secretkey );
    }
	
	
	
	/**
     * getMyConnection
     *
     * @param $orderId
     * @return object
     */
    protected function getMyConnection() {
    	// $connection = $this -> objectManager -> get('Magento\Framework\App\ResourceConnection') -> getConnection('\Magento\Framework\App\ResourceConnection::DEFAULT_CONNECTION'); // It works too
    	$connection = $this -> objectManager -> get('Magento\Framework\App\ResourceConnection') -> getConnection();
		return $connection;
    }
	
	
	
	/**
     * getModuleSecretKey
     * RECUPERA LA CLAVE SECRETA PARA EL IDWEBSITE QUE SE LE DA.
     *
     * @param $_idWebsite
     * @return string
     */
    public function getModuleSecretKey( $_idWebsite ) {
	    $connection = $this -> getMyConnection();
		$tableName   = $connection -> getTableName('core_config_data'); // It will return table with prefix
		$resultsIdwebsite = $connection -> fetchAll("SELECT * FROM " . $tableName . " WHERE scope = 'default' AND value = '" . $_idWebsite . "'");
		
		// Verifica si la configuración está guardada en "Default" para todos los Websites.
		if ( $resultsIdwebsite ) {
			$this -> defaultOrStoreOrWebsite = 'default';
			
		} else { // Verifica si la configuración está guardada en un "Main website".
		    $resultsIdwebsite = $connection -> fetchAll("SELECT * FROM " . $tableName . " WHERE scope = 'websites' AND value = '" . $_idWebsite . "'");
			if ( $resultsIdwebsite ) {
				$this -> defaultOrStoreOrWebsite = 'websites';
				
			} else { // Verifica si la configuración está guardada en una tienda.
			    $resultsIdwebsite = $connection -> fetchAll("SELECT * FROM " . $tableName . " WHERE scope = 'stores' AND value = '" . $_idWebsite . "'");
				if ( $resultsIdwebsite ) {
					$this -> defaultOrStoreOrWebsite = 'stores';
				}
			}
		}
		
		if ( $resultsIdwebsite ) {
			foreach ( $resultsIdwebsite as $value ) {
				$this -> idStore = $value["scope_id"];
                $resultsSecretkey = $connection -> fetchAll("SELECT * FROM " . $tableName . " WHERE scope = '" . $this -> defaultOrStoreOrWebsite . "' AND scope_id = '" . $this -> idStore . "' AND path = 'av_configuration/system_integration/secretkey'");
                if ( $resultsSecretkey ) {
                    foreach ( $resultsSecretkey as $value ) {
                        return $value["value"];
                    } // endforeach
                } // endif
			} // endforeach
		} // endif
		
        return null;
    }
	
	
	
	/**
     * getAllStatus
     * RECUPERA TODOS LOS STATUS Y SON STATE ASOCIADO EXISTENTES EN EL SITIO.
     *
     * @return array
     */
    public function getAllStatus() {
	    $statusCollection = $this -> objectManager -> create('Magento\Sales\Model\ResourceModel\Order\Status\Collection');
		$arrayStatusCollection = $statusCollection -> toOptionArray();
		
		// Simplify the array to be able to process the data in NetReviews sistem
		foreach ( $arrayStatusCollection as $value ) {
			$arrayStatusCollectionSimplified[ $value['value'] ] = $value['label'];
		}
		return ( $arrayStatusCollectionSimplified ) ? $arrayStatusCollectionSimplified : null;
    }
	
	
	
	/**
     * getAllWebsites
     * RECUPERA TODOS LOS WEBSITES DE LA TABLA "STORE_WEBSITE".
     *
     * @return array
     */
    public function getAllWebsites() {
    	$storeManager = $this -> objectManager -> get('Magento\Store\Model\StoreManagerInterface');
		$allWebsites = $storeManager -> getWebsites();
		foreach ( $allWebsites as $val ) {
			$websites_mapping[] = $val -> getData();
		}
		return ( $websites_mapping ) ? $websites_mapping : null;
    }
	
	
	
	/**
     * getAllStores
     * RECUPERA TODAS LAS TIENDAS DE LA TABLA "STORE".
     *
     * @return array
     */
    public function getAllStores() {
    	$storeManager = $this -> objectManager -> get('Magento\Store\Model\StoreManagerInterface');
		$allStores = $storeManager -> getStores();
		foreach ( $allStores as $val ) {
			$stores_mapping[] = $val -> getData();
		}
		return ( $stores_mapping ) ? $stores_mapping : null;
    }
	
	
	
	/**
     * getStoresWithIdwebsite
     * CREA UN ARRAY QUE PUEDE SER LEIDO POR LA PLATAFARMA DE NETREVIEWS CON LOS WEBSITES Y TIENDAS QUE ESTÁN CONFIGURADAS CON O SIN EL MÓDULO.
     *
     * @param $websites_mapping
     * @param $stores_mapping
     * @return array
     */
    public function getStoresWithIdwebsite( $_websites_mapping, $_stores_mapping ) {
    	$configWithIdwebsite = $this -> getStoresFromConfigWithIdwebsite();
		
		// Simplify Stores array
		$i = 0;
		foreach ( $_stores_mapping as $store ) {
			$stores_mapping[ $i ]['name'] = $store['name'] . " {" . $store['store_id'] . "}";
			$stores_mapping[ $i ]['website'] = $store['website_id'];
			foreach ( $configWithIdwebsite as $config ) {
				
				// enabledwebsite
				if ( $config['path'] == 'av_configuration/system_integration/enabledwebsite'  &&  $config['scope'] == 'stores'  &&  $config['scope_id'] == $store['store_id'] ) {
					$stores_mapping[ $i ]['is_active'] = $config['value'];
				}
				
				// NetReviews idwebsite
				if ( $config['path'] == 'av_configuration/system_integration/idwebsite'  &&  $config['scope'] == 'stores'  &&  $config['scope_id'] == $store['store_id'] ) {
					$stores_mapping[ $i ]['website_id'] = $config['value'];
				}
			}
			
			$i++;
		}
		
		
		// Simplify Websites array and add Stores in Websites array
		$i = 0;
		foreach ( $_websites_mapping as $website ) {
			
			// Simplify Websites array
			$websites_stores_mapping[ $i ]['name'] = $website['name'] . " {" . $website['website_id'] . "}";
			foreach ( $configWithIdwebsite as $config ) {
				
				// enabledwebsite
				if ( $config['path'] == 'av_configuration/system_integration/enabledwebsite'  &&  $config['scope'] == 'websites'  &&  $config['scope_id'] == $website['website_id'] ) {
					$websites_stores_mapping[ $i ]['is_active'] = $config['value'];
				}

				// NetReviews idwebsite
				if ( $config['path'] == 'av_configuration/system_integration/idwebsite'  &&  $config['scope'] == 'websites'  &&  $config['scope_id'] == $website['website_id'] ) {
					$websites_stores_mapping[ $i ]['AVwebsiteId'] = $config['value'];
				}
			}
			
			// Add Stores in Websites array
			$k = 0;
			foreach ( $stores_mapping as $store ) {
				if ( $store['website'] == $website['website_id'] ){
					
					// Si la tienda no tiene el campo "is_active" pero el Website sí, entonces ponerle el mismo que su website.
					if ( ! array_key_exists( 'is_active', $store )  &&  array_key_exists( 'is_active', $websites_stores_mapping[ $i ] ) ) {
						$store['is_active'] = $websites_stores_mapping[ $i ]['is_active'];
					}
					
					// Si la tienda no tiene el campo "website_id" pero el Website sí, entonces ponerle el mismo que su website.
					if ( ! array_key_exists( 'website_id', $store )  &&  array_key_exists( 'AVwebsiteId', $websites_stores_mapping[ $i ] ) ) {
						$store['website_id'] = $websites_stores_mapping[ $i ]['AVwebsiteId'];
					}
					$websites_stores_mapping[ $i ]['stores'][$k] = $store;
				}
				$k++;
			}
			$i++;
		}
		
    	$site_mapping = array(
    						'all' => 1,
    						'webistes' => $websites_stores_mapping
		);
	return $site_mapping;
    }
	
	
	
	/**
     * getStoresFromConfigWithIdwebsite
     * RECUPERA LOS "WEBSITES" Y "STORES" O "DEFAULT" QUE TIENEN UN IDWEBSITE EN LA TABLA "CORE_CONFIG_DATA".
     *
     * @return array
     */
    public function getStoresFromConfigWithIdwebsite() {
	    $connection = $this -> getMyConnection();
		$tableName   = $connection -> getTableName('core_config_data'); // It will return table with prefix
		$resultsAllIdwebsite = $connection -> fetchAll("SELECT * FROM " . $tableName . " WHERE path like '%av_configuration/system_integration/%'");
		if ( $resultsAllIdwebsite ) {
			foreach ( $resultsAllIdwebsite as $val ) {
				$core_config_data_av[] = $val;
			} // endforeach
			return $core_config_data_av;
		}
		return 0;
    }



	/**
     * getModuleActiveStoresIds
     * WE ARE GOING TO FILTER BY IDWEBSITE AND IS ACTIVE.
     *
	 * @param $idWebsite
     * @return array
     */
    public function getModuleActiveStoresIds( $idWebsite ) {
		$connection = $this -> getMyConnection();
		$tableName   = $connection -> getTableName('core_config_data'); // It will return table with prefix
		$resultsIdwebsite = $connection -> fetchAll("SELECT * FROM " . $tableName . " WHERE path = 'av_configuration/system_integration/idwebsite' AND value = '" . $idWebsite . "'");
		$activeStores = array();
		if ( $resultsIdwebsite ) {
			$i = 0;
			foreach ( $resultsIdwebsite as $val ) {
				$websiteOrStore = $val['scope'];
				$id = $val['scope_id'];
				$resultsEnabled = $connection -> fetchAll("SELECT * FROM " . $tableName . " WHERE path = 'av_configuration/system_integration/enabledwebsite' AND scope = '" . $websiteOrStore . "' AND scope_id = '" . $id . "' AND value = '1'");
				foreach ( $resultsEnabled as $val2 ) {
					$activeStores[ $i ]['scope'] = $val2['scope'];
					$activeStores[ $i ]['scope_id'] = $val2['scope_id'];
					$i++;
				}
			}
		}
		
		return $activeStores;
    }
	
	
	
	/**
     * getAllStoresIdsForWebsite
     * OBTENER TODAS LAS TIENDAS DE UN WEBSITE ESPECÍFICO.
     *
	 * @param $idWebsiteMagento
     * @return array
     */
    public function getAllStoresIdsForWebsite( $idWebsiteMagento ) {
		$connection = $this -> getMyConnection();
		$tableName   = $connection -> getTableName('store'); // It will return table with prefix
		$tableCore_config_data   = $connection -> getTableName('core_config_data'); // It will return table with prefix
		$resultsStores = $connection -> fetchAll("SELECT * FROM " . $tableName . " WHERE website_id = '" . $idWebsiteMagento . "'");
		
		$activeStores = array();
		if ( $resultsStores ) {
			$i = 0;
			foreach ( $resultsStores as $val ) {
				$storeId = $val['store_id'];
				// Check if the store have a config with "enabledwebsite"
				$resultsStoresInCore_config_data_enabledwebsite = $connection -> fetchAll("SELECT * FROM " . $tableCore_config_data . " WHERE scope = 'stores' AND path = 'av_configuration/system_integration/enabledwebsite' AND scope_id = '" . $storeId . "'");
				
				
				
				if ( $resultsStoresInCore_config_data_enabledwebsite ) { // La tienda existe por tanto NO la agregamos. Si tiene el mismo idWebsite que el website entonces ya ha sido agregada en la función allStoresId
					foreach ( $resultsStoresInCore_config_data_enabledwebsite as $val2 ) {
						if ( $val2['value'] == '1' ) { // Si la tienda está activada verificamos que NO TENGA otro idWebsite.
							$resultsStoresInCore_config_data_idwebsite = $connection -> fetchAll("SELECT * FROM " . $tableCore_config_data . " WHERE scope = 'stores' AND path = 'av_configuration/system_integration/idwebsite' AND scope_id = '" . $storeId . "'");
							if ( $resultsStoresInCore_config_data_idwebsite ) {
								// La tienda existe con otro av_idWebsite por tanto NO la agregamos. Si tiene el mismo av_idWebsite que el website entonces ya ha sido agregada en la variable global "allStoresId" de esta clase.
							} else {
								// Si la tienda está activada y no tiene idWebsite por tanto la agregamos con el Website.
								$activeStores[ $i ]['scope'] = 'stores';
								$activeStores[ $i ]['scope_id'] = $storeId;
							}
						} // else Si la tienda no está activada no se agrega (ya sea que tenga o no el mismo idWebsite como no esta activada no se agrega).
					}
				
				
				
				
				} else { // Como no existe otra configuración para la tienda la agregamos con el website.
					$activeStores[ $i ]['scope'] = 'stores';
					$activeStores[ $i ]['scope_id'] = $storeId;
				}
				
				$i++;
			}
		}
		
		return $activeStores;
    }
	
	
	
	/**
     * getAllOrders - RECUPERA DATOS DE TODOS LOS PEDIDOS.
     *
     * @return object
     */
	public function getAllOrders() {
		$o_orderDatamodel = $this -> objectManager -> get('Magento\Sales\Model\Order') -> getCollection();
		return $o_orderDatamodel;
	}
	
	
	
	/**
     * getOrdersAddingFilters
     * AGREGA UN FILTRO PARA RECUPERAR LOS PEDIDOS DE UNA SOLA TIENDA.
     *
	 * @param $storeId - ID de la tienda.
	 * @param $_status - Status seleccionados para recuperar los pedidos.
     * @return object
     */
	public function getOrdersAddingStatusFilter ( $_storeId, $_status = null ) {
		$o_getAllOrders = $this -> getAllOrders();
		$o_getAllOrders -> addFieldToFilter( 'store_id', $_storeId );
		$o_getAllOrders -> addFieldToFilter( 'av_flag', 0 );
		if ( $_status != null ) {
			$status = explode( ";", $_status );
				$o_getAllOrders -> addFieldToFilter( 'status', array( "in" => $status ) );
		}
		return $o_getAllOrders;
	}
	
	
	
	/**
     * getOrdersAddingDateFilter
     * RECUPERA LOS PEDIDOS DE UNA TIENDA EN ESPECÍFICO Y AGREGA UN FILTRO EN LAS FECHAS DE LOS PEDIDOS.
     *
	 * @param $storeId - ID de la tienda
     * @return object
     */
	public function getOrdersAddingDateFilter ( $_storeId = 0, $_fromDate = '1990-01-30 00:00:00', $_toDate = '2032-12-31 23:59:59' ) {
		$o_getAllOrders = $this -> getAllOrders();
		if ( $_storeId != 0 && !empty( $_storeId ) ) {
			$o_getAllOrders -> addFieldToFilter( 'store_id', $_storeId );
		}
		
		$fromDate = date('Y-m-d H:i:s', strtotime( $_fromDate ) );
		$toDate = date('Y-m-d H:i:s', strtotime( $_toDate ) );
		
		$o_getAllOrders -> addFieldToFilter( 'created_at', array( 'from' => $fromDate, 'to' => $toDate ) );
		
		return $o_getAllOrders;
	}
	
	
	
    /**
     * addFilterForbiddenEmails
     * AGREGA UN FILTRO PARA ELIMINAR LOS PEDIDOS QUE CONTIENEN LOS E-MAILS A LOS CUALES NO SE QUIERE ENVIAR LA PETICIÓN DE OPINIÓN.
     *
     * @param $o_getAllOrders - Lista de pedidos.
     * @param $_emails - String de e-mails prohibidos.
     * @return object
     */
    public function addFilterForbiddenEmails ( $o_getAllOrders, $_emails ) {
        if ( $_emails != null ) {
            $emails = explode( ";", $_emails );
            foreach ( $emails as $key => $email ) {
                if ( ! empty ( $email ) ) {
                    $o_getAllOrders -> addFieldToFilter( 'customer_email', array( "nlike" => '%@' . $email . '%' ) );
                }
            }
            
        }
        return $o_getAllOrders;
    }



	/**
     * addFilterStatus
     * AGREGA UN FILTRO PARA RECUPERAR UNICAMENTE LOS PEDIDOS QUE CONTIENEN LOS STATUS DADOS.
     *
	 * @param $_orders - Lista de pedidos.
	 * @param $_status - Lista de status.
     * @return object
     */
	public function addFilterStatus ( $_orders, $_status ) {
		if ( is_array( $_status ) ) {
			$_orders -> addFieldToFilter( 'status', array( "in" => $_status ) );
		}
		return $_orders;
	}
	
	
	
	/**
     * getParentProducts
     * PROPORCIONA UN ARRAY CON TODOS LOS PRODUCTOS PADRE DE ACUERDO AL ID HIJO QUE SE LE PASA, SIN IMPORTAR EL TIPO DE PRODUCTO PADRE.
     *
     * @param $idChild
     * @return array
     */
    public function getProducts( $_order ) {
        $o_items = $_order -> getAllVisibleItems();
		$store = $this -> objectManager -> get('Magento\Store\Model\StoreManagerInterface') -> getStore();
		$i = 0;
		$listProducts = array();

		// Get values of configuration.
		$use_parent_sku = $this -> getAdvancedConfig( "use_parent_sku", $this -> idStore ); // To get parent data for: "id_product", "sku", "id_product_in_db" and PLA.
		
		foreach( $o_items as $_item ):
			$id_product = $_item -> getProductId();
			$o_product = $this -> objectManager -> create('Magento\Catalog\Model\Product') -> load( $id_product );
			
			$sku = $o_product -> getSku();
			$name = $o_product -> getData('name');
			$gtin_ean = "";
			$mpn = "";
			$brand = "";
			$category = "";
			$url_product = $o_product -> getProductUrl(); // URL for children.
			//$url_product = $o_product -> getUrlModel() -> getUrl( $o_product );
			$url_image = $store -> getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA) . 'catalog/product' . $o_product -> getImage();
			$info1 = "";
			$info2 = "";
			$info3 = "";
			$info4 = "";
			$info5 = "";
			$info6 = "";
			$info7 = "";
			$info8 = "";
			$info9 = "";
			$info10 = "";
			
			if ( empty( $sku ) ) {
				$sku = $id_product;
			} else {
				$listProducts[ $i ]['sku'] = $sku;
			}
			
			$listProducts[ $i ]['id_product'] = $sku;
			$listProducts[ $i ]['id_product_in_db'] = $id_product; // Get real Id Product in data base for export file.
			$listProducts[ $i ]['name_product'] = $name; // We want to take children name.
			$listProducts[ $i ]['url'] = $url_product;
			$listProducts[ $i ]['url_image'] = $url_image; // We want to take children image.
			
			// Get extra data PLA - Google Shopping
			$listProducts[ $i ] = $this -> getExtraProductData( $o_product, $listProducts[ $i ] );

			// Get extra data PLA for Parent product - Google Shopping
			if ( $use_parent_sku == 1 ) {
				$idParents = $this -> getParentProducts ( $id_product );
				if ( $idParents ) {
					foreach ( $idParents as $idParent ) {
						
						if ( is_array( $idParent ) ) {
							foreach ( $idParent as $_id ) {
								$parentProduct = $this -> objectManager -> create('Magento\Catalog\Model\Product') -> load( $_id );
								$listProducts[ $i ] = $this -> getExtraProductData( $parentProduct, $listProducts[ $i ] );
							}
							
						} else {
							$parentProduct = $this -> objectManager -> create('Magento\Catalog\Model\Product') -> load( $idParent );
							$listProducts[ $i ] = $this -> getExtraProductData( $parentProduct, $listProducts[ $i ] );
						}
						
					} // foreach
				}
			}
			
			// Override child product data by Parent data.
			$listProducts[ $i ] = $this -> overrideParentProductsData( $_item, $listProducts[ $i ] );
			
			$i++;
		endforeach;
		return $listProducts;
    }
	
	
	
	/**
     * getExtraProductData
     * RECUPERA (Y EN CASO NECESARIO REEMPLAZA) LA INFORMACIÓN EXTRA PARA GOOGLE SHOPPING DE UN PRODUCTO.
     *
     * @param $_product
     * @param $_listProducts
     * @return array
     */
    public function getExtraProductData( $o_product, $listProducts ) {
    	
    	$att_id = $this -> getSpecificPlaConfig( "id", $this -> idStore, $this -> defaultOrStoreOrWebsite );
		if ( $att_id && $att_id != '---' ) {
			$listProducts['id_product'] = $o_product -> getData( $att_id );
		}
		
    	$att_sku = $this -> getSpecificPlaConfig( "sku", $this -> idStore, $this -> defaultOrStoreOrWebsite );
		if ( $att_sku && $att_sku != '---' ) {
			$listProducts['sku'] = $o_product -> getData( $att_sku );
		}
		
    	$att_name = $this -> getSpecificPlaConfig( "description", $this -> idStore, $this -> defaultOrStoreOrWebsite );
		if ( $att_name && $att_name != '---' ) {
			$listProducts['name_product'] = $o_product -> getData( $att_name );
		}
		
    	$att_url = $this -> getSpecificPlaConfig( "link", $this -> idStore, $this -> defaultOrStoreOrWebsite );
		if ( $att_url && $att_url != '---' ) {
			$listProducts['url'] = $o_product -> getData( $att_url );
		}
		
    	$att_image = $this -> getSpecificPlaConfig( "image_link", $this -> idStore, $this -> defaultOrStoreOrWebsite );
		if ( $att_image && $att_image != '---' ) {
			$listProducts['url_image'] = $o_product -> getData( $att_image );
		}
		
    	$att_brand = $this -> getSpecificPlaConfig( "brand", $this -> idStore, $this -> defaultOrStoreOrWebsite );
		if ( $att_brand && $att_brand != '---' ) {
			$listProducts['brand_name'] = $o_product -> getData( $att_brand );
			// If value is a number then rather get Text.
			$listProducts['brand_name'] = $this -> getAttributeAsText( $o_product, $listProducts['brand_name'], $att_brand );
		}
		
    	$att_category = $this -> getSpecificPlaConfig( "category", $this -> idStore, $this -> defaultOrStoreOrWebsite );
		if ( $att_category && $att_category != '---' ) {
			$listProducts['category'] = $o_product -> getData( $att_category );
			// If value is a number then rather get Text.
			$listProducts['category'] = $this -> getAttributeAsText( $o_product, $listProducts['category'], $att_category );
		}
		
    	$att_mpn = $this -> getSpecificPlaConfig( "mpn", $this -> idStore, $this -> defaultOrStoreOrWebsite );
		if ( $att_mpn && $att_mpn != '---' ) {
			$listProducts['MPN'] = $o_product -> getData( $att_mpn );
			// If value is a number then rather get Text.
			$listProducts['MPN'] = $this -> getAttributeAsText( $o_product, $listProducts['MPN'], $att_mpn );
		}
		
    	$att_gtinUpc = $this -> getSpecificPlaConfig( "gtin_upc", $this -> idStore, $this -> defaultOrStoreOrWebsite );
		if ( $att_gtinUpc && $att_gtinUpc != '---' ) {
			$listProducts['GTIN_UPC'] = $o_product -> getData( $att_gtinUpc );
			// If value is a number then rather get Text.
			$listProducts['GTIN_UPC'] = $this -> getAttributeAsText( $o_product, $listProducts['GTIN_UPC'], $att_gtinUpc );
		}
		
    	$att_gtinEan = $this -> getSpecificPlaConfig( "gtin_ean", $this -> idStore, $this -> defaultOrStoreOrWebsite );
		if ( $att_gtinEan && $att_gtinEan != '---' ) {
			$listProducts['GTIN_EAN'] = $o_product -> getData( $att_gtinEan );
			// If value is a number then rather get Text.
			$listProducts['GTIN_EAN'] = $this -> getAttributeAsText( $o_product, $listProducts['GTIN_EAN'], $att_gtinEan );
		}
		
    	$att_gtinJan = $this -> getSpecificPlaConfig( "gtin_jan", $this -> idStore, $this -> defaultOrStoreOrWebsite );
		if ( $att_gtinJan && $att_gtinJan != '---' ) {
			$listProducts['GTIN_JAN'] = $o_product -> getData( $att_gtinJan );
			// If value is a number then rather get Text.
			$listProducts['GTIN_JAN'] = $this -> getAttributeAsText( $o_product, $listProducts['GTIN_JAN'], $att_gtinJan );
		}
		
    	$att_gtinIsbn = $this -> getSpecificPlaConfig( "gtin_isbn", $this -> idStore, $this -> defaultOrStoreOrWebsite );
		if ( $att_gtinIsbn && $att_gtinIsbn != '---' ) {
			$listProducts['GTIN_ISBN'] = $o_product -> getData( $att_gtinIsbn );
			// If value is a number then rather get Text.
			$listProducts['GTIN_ISBN'] = $this -> getAttributeAsText( $o_product, $listProducts['GTIN_ISBN'], $att_gtinIsbn );
		}
		
    	$att_info1 = $this -> getSpecificPlaConfig( "info1", $this -> idStore, $this -> defaultOrStoreOrWebsite );
		if ( $att_info1 && $att_info1 != '---' ) {
			$listProducts['info1'] = $o_product -> getData( $att_info1 );
			// If value is a number then rather get Text.
			$listProducts['info1'] = $this -> getAttributeAsText( $o_product, $listProducts['info1'], $att_info1 );
		}
		
    	$att_info2 = $this -> getSpecificPlaConfig( "info2", $this -> idStore, $this -> defaultOrStoreOrWebsite );
		if ( $att_info2 && $att_info2 != '---' ) {
			$listProducts['info2'] = $o_product -> getData( $att_info2 );
			// If value is a number then rather get Text.
			$listProducts['info2'] = $this -> getAttributeAsText( $o_product, $listProducts['info2'], $att_info2 );
		}
		
    	$att_info3 = $this -> getSpecificPlaConfig( "info3", $this -> idStore, $this -> defaultOrStoreOrWebsite );
		if ( $att_info3 && $att_info3 != '---' ) {
			$listProducts['info3'] = $o_product -> getData( $att_info3 );
			// If value is a number then rather get Text.
			$listProducts['info3'] = $this -> getAttributeAsText( $o_product, $listProducts['info3'], $att_info3 );
		}
		
    	$att_info4 = $this -> getSpecificPlaConfig( "info4", $this -> idStore, $this -> defaultOrStoreOrWebsite );
		if ( $att_info4 && $att_info4 != '---' ) {
			$listProducts['info4'] = $o_product -> getData( $att_info4 );
			// If value is a number then rather get Text.
			$listProducts['info4'] = $this -> getAttributeAsText( $o_product, $listProducts['info4'], $att_info4 );
		}
		
    	$att_info5 = $this -> getSpecificPlaConfig( "info5", $this -> idStore, $this -> defaultOrStoreOrWebsite );
		if ( $att_info5 && $att_info5 != '---' ) {
			$listProducts['info5'] = $o_product -> getData( $att_info5 );
			// If value is a number then rather get Text.
			$listProducts['info5'] = $this -> getAttributeAsText( $o_product, $listProducts['info5'], $att_info5 );
		}
		
    	$att_info6 = $this -> getSpecificPlaConfig( "info6", $this -> idStore, $this -> defaultOrStoreOrWebsite );
		if ( $att_info6 && $att_info6 != '---' ) {
			$listProducts['info6'] = $o_product -> getData( $att_info6 );
			// If value is a number then rather get Text.
			$listProducts['info6'] = $this -> getAttributeAsText( $o_product, $listProducts['info6'], $att_info6 );
		}
		
    	$att_info7 = $this -> getSpecificPlaConfig( "info7", $this -> idStore, $this -> defaultOrStoreOrWebsite );
		if ( $att_info7 && $att_info7 != '---' ) {
			$listProducts['info7'] = $o_product -> getData( $att_info7 );
			// If value is a number then rather get Text.
			$listProducts['info7'] = $this -> getAttributeAsText( $o_product, $listProducts['info7'], $att_info7 );
		}
		
    	$att_info8 = $this -> getSpecificPlaConfig( "info8", $this -> idStore, $this -> defaultOrStoreOrWebsite );
		if ( $att_info8 && $att_info8 != '---' ) {
			$listProducts['info8'] = $o_product -> getData( $att_info8 );
			// If value is a number then rather get Text.
			$listProducts['info8'] = $this -> getAttributeAsText( $o_product, $listProducts['info8'], $att_info8 );
		}
		
    	$att_info9 = $this -> getSpecificPlaConfig( "info9", $this -> idStore, $this -> defaultOrStoreOrWebsite );
		if ( $att_info9 && $att_info9 != '---' ) {
			$listProducts['info9'] = $o_product -> getData( $att_info9 );
			// If value is a number then rather get Text.
			$listProducts['info9'] = $this -> getAttributeAsText( $o_product, $listProducts['info9'], $att_info9 );
		}
		
    	$att_info10 = $this -> getSpecificPlaConfig( "info10", $this -> idStore, $this -> defaultOrStoreOrWebsite );
		if ( $att_info10 && $att_info10 != '---' ) {
			$listProducts['info10'] = $o_product -> getData( $att_info10 );
			// If value is a number then rather get Text.
			$listProducts['info10'] = $this -> getAttributeAsText( $o_product, $listProducts['info10'], $att_info10 );
		}
		
		return $listProducts;
    }
	
	
	
	/**
     * getParentProducts
     * PROPORCIONA UN ARRAY CON TODOS LOS PRODUCTOS PADRE DE ACUERDO AL ID HIJO QUE SE LE PASA, SIN IMPORTAR EL TIPO DE PRODUCTO PADRE.
     *
     * @param $idChild
     * @return array
     */
    public function getParentProducts( $idChild ) {
        $idParents = $this -> objectManager -> get('Magento\ConfigurableProduct\Model\Product\Type\Configurable') -> getParentIdsByChild( $idChild );
		if ( ! $idParents ) {
			$idParents = $this -> objectManager -> get('Magento\GroupedProduct\Model\Product\Type\Grouped') -> getParentIdsByChild( $idChild );
			if ( ! $idParents ) {
				$idParents = $this -> objectManager -> get('Magento\Bundle\Model\Product\Type') -> getParentIdsByChild( $idChild );
			}
		}
		return $idParents;
    }
	
	
	
	/**
     * overrideParentProductsData
     * REEMPLAZA LA INFORMACIÓN DEL PRODUCTO SIMPLE POR LOS DEL PADRE.
     *
     * @param $idChild
     * @return array
     */
    public function overrideParentProductsData( $_item, $listProducts ) {
        // Get parent only if "item" is a Simple product.
        $id_product = $_item -> getProductId();
		$itemType = $_item -> getProductType();
		if ( $itemType == 'simple' ) {
			$idParents = $this -> getParentProducts ( $id_product );
			if ( $idParents ) {

				// Get values of configuration.
				$use_parent_url = $this -> getAdvancedConfig( "use_parent_url", $this -> idStore );
				$use_parent_sku = $this -> getAdvancedConfig( "use_parent_sku", $this -> idStore ); // To get parent data for: "id_product", "sku", "id_product_in_db" and PLA.

				foreach ( $idParents as $idParent ) {
					
					if ( is_array( $idParent ) ) {
						
						foreach ( $idParent as $_id ) {
							$parentProduct = $this -> objectManager -> create('Magento\Catalog\Model\Product') -> load( $_id );
							$pSku = $parentProduct -> getSku();
							
							if ( empty( $pSku ) ) {
								$pSku = $_id;
							} else {
								$listProducts['sku'] = ( $use_parent_sku == 1 ) ? $pSku : $listProducts['sku']; // Override child SKU by Parent ID.
							}
							$listProducts['id_product'] = ( $use_parent_sku == 1 ) ? $pSku : $listProducts['id_product']; // Override child ID by Parent ID.
							$listProducts['id_product_in_db'] = ( $use_parent_sku == 1 ) ? $_id : $listProducts['id_product_in_db']; // Get real Id Parent Product in data base for export file.
							$listProducts['url'] = ( $use_parent_url == 1 ) ? $parentProduct -> getProductUrl() : $listProducts['url'] ; // Override child URL by Parent URL.
						}
						
					} else {
						$parentProduct = $this -> objectManager -> create('Magento\Catalog\Model\Product') -> load( $idParent );
						$pSku = $parentProduct -> getSku();
						
						if ( empty( $pSku ) ) {
							$pSku = $idParent;
						} else {
							$listProducts['sku'] = ( $use_parent_sku == 1 ) ? $pSku : $listProducts['sku']; // Override child SKU by Parent ID.
						}
						$listProducts['id_product'] = ( $use_parent_sku == 1 ) ? $pSku : $listProducts['id_product']; // Override child ID by Parent ID.
						$listProducts['id_product_in_db'] = ( $use_parent_sku == 1 ) ? $idParent : $listProducts['id_product_in_db']; // Get real Id Parent Product in data base for export file.
						$listProducts['url'] = ( $use_parent_url == 1 ) ? $parentProduct -> getProductUrl() : $listProducts['url'] ; // Override child URL by Parent URL.
					}
					
				} // foreach
			} // if ( $idParents )
			
		} // if ( $itemType == 'simple' )
		return $listProducts;
    }
	
	
	
	/**
     * getAttributeAsText
     * IF SELECTBOX VALUE IS A NUMBER THEN RATHER GET TEXT.
     *
     * @param $o_product
     * @param $_val
     * @param $_attibute
     * @return string
     */
    public function getAttributeAsText( $o_product, $_val, $_attibute ) {
    	if ( is_numeric( $_val ) ) {
			$val_text = $o_product -> getAttributeText( $_attibute );
			if ( $val_text ) {
				return $val_text;
			}
		}
		return $_val;
    }
    
	
	
	/**
     * isFlag
     *
     * @param $orderId
     * @return void
     */
    public function isFlag( $orderId ) {
    	$connection = $this -> getMyConnection();
		$tableName   = $connection -> getTableName('sales_order'); // It will return table with prefix
		$resultFlags = $connection -> fetchAll("SELECT av_flag, av_horodate_get FROM " . $tableName . " WHERE entity_id = '" . $orderId . "'");
		$orderFlag = array();
		foreach ( $resultFlags as $val ) {
			$orderFlag['av_flag'] = $val['av_flag'];
			$orderFlag['av_horodate_get'] = $val['av_horodate_get'];
		}
		return $orderFlag;
    }
	
	
	
	/**
     * flagOrderTo0
     *
     * @param $orderId
     * @return void
     */
    public function flagOrderTo0( $orderId ) {
    	$connection = $this -> getMyConnection();
		$tableName   = $connection -> getTableName('sales_order'); // It will return table with prefix
		
		$result = $connection -> update(
			$tableName,
		    array(
		        'av_flag' => new \Zend_Db_Expr('0'),
		        'av_horodate_get' => new \Zend_Db_Expr('NULL'),
		    ),
		    array( "entity_id = ?" => $orderId )
		);
		
		//$result = $connection -> query("UPDATE " . $tableName . " SET av_flag = 0, av_horodate_get = " . NULL . " WHERE entity_id = '" . $orderId . "'");
		if ( $result ) {
			return 1;
		}
		return 0;
    }
	
	
	
	/**
     * flagOrderTo1
     *
     * @param $orderId
     * @return void
     */
    public function flagOrderTo1( $orderId ) {
    	$connection = $this -> getMyConnection();
		$tableName   = $connection -> getTableName('sales_order'); // It will return table with prefix
		$timestamp = strtotime( date('Y-m-d H:i:s') );
		
		$result = $connection -> update(
			$tableName,
		    array(
		        'av_flag' => new \Zend_Db_Expr('NULL'),
		        'av_horodate_get' => new \Zend_Db_Expr( $timestamp ),
		    ),
		    array( "entity_id = ?" => $orderId )
		);
		
		//$result = $connection -> query("UPDATE " . $tableName . " SET av_flag = 'NULL', av_horodate_get = '" . $timestamp . "' WHERE entity_id = '" . $orderId . "'");
		if ( $result ) {
			return 1;
		}
		return 0;
    }
	
	
	
    /**
     * flagOrdersTo1
     * FLAG ALL ORDERS TO 1 FOR STORES WITH CURRENT AV_IdWEBSITE.
     *
     * @return void
     */
    public function flagOrdersTo1() {
		// Get stores actives for a specific IdWebsite
		$activeStores = $this -> allStoresId;
		
		// Check if $activeStores have websites and get its stores id (because if we have a website it is active)
		if ( is_array( $activeStores ) ) {
			foreach ( $activeStores as $val ) {
				if ( $val['scope'] == 'websites' ) {
					$storesInWebsite = $this -> getAllStoresIdsForWebsite( $val['scope_id'] );
					$activeStores = array_unique( array_merge( $activeStores, $storesInWebsite ), SORT_REGULAR ); // SORT_REGULAR - compare les éléments normalement (ne modifie pas les types).
				}
			}
		}
		
		$i = 0;
		$reponse[0] = 'Set orders flag successful!';
		
		// Get orders for each Store
		if ( is_array( $activeStores ) ) {
			foreach ( $activeStores as $currentStore ) {
				
				// Jump this iteration is it is default configuration or it is a website in order to avoid duplicate values because we'll process each store for this website.
				if ( $currentStore['scope'] == 'websites' || $currentStore['scope'] == 'default' ) {
					continue;
				}
				
				$o_orders = $this -> getOrdersAddingStatusFilter( $currentStore['scope_id'], null );
				
				foreach( $o_orders as $_order ) {
					$currentOrder = $_order -> getData();
					
					// =============== FLAG ALL ORDERS TO 1 ===============
					if ( $this -> flagOrderTo1( $currentOrder["entity_id"] ) == 1 ) {
						// Continue loop normally
						$reponse['FLAG'][ $i ] = $currentOrder["increment_id"]; // Uncomment to display flaged orders.
					} else {
						$reponse[0] = 'WARNING! Error to flag some orders!'; // Delete current order because is not flag to 1.
						$reponse['NO_FLAG'][ $i ] = $currentOrder["increment_id"];
					}
					// =============== FLAG ALL ORDERS TO 1 END ===============
					
					$i++;
				} // endForeach $o_orders
				
			} // foreach store_id
		}
		
		return $reponse;
    }
	
	
	
    /**
     * flagOrdersTo1ForAllStores
     * FLAG ALL ORDERS TO 1 FOR ALL STORES (USED WHEN MODULE IS INSTALLED).
     *
     * @return void
     */
    public function flagOrdersTo1ForAllStores() {
		
		$i = 0;
		$reponse[0] = 'Set orders flag successful!';
		
		// Get orders for each Store
				
				$o_getAllOrders = $this -> getAllOrders();
				
				foreach( $o_getAllOrders as $_order ) {
					$currentOrder = $_order -> getData();
					
					// =============== FLAG ALL ORDERS TO 1 ===============
					if ( $this -> flagOrderTo1( $currentOrder["entity_id"] ) == 1 ) {
						// Continue loop normally
						$reponse['FLAG'][ $i ] = $currentOrder["increment_id"]; // Uncomment to display flaged orders.
					} else {
						$reponse[0] = 'WARNING! Error to flag some orders!'; // Delete current order because is not flag to 1.
						$reponse['NO_FLAG'][ $i ] = $currentOrder["increment_id"];
					}
					// =============== FLAG ALL ORDERS TO 1 END ===============
					
					$i++;
				} // endForeach $o_getAllOrders
				
		
		return $reponse;
    }
	
	
	
    /**
     * setDefaultConfig
     * PONE LOS VALORES POR DEFAULT PARA TODOS LOS SITIOS (USED WHEN MODULE IS INSTALLED).
     *
     * @return void
     */
    public function setDefaultConfig() {
		
		$i = 0;
		$reponse = 'setDefaultConfig successful!';
		
		$configData = array(
						[
						    'scope' => 'default',
						    'scope_id' => '0',
						    'path' => 'av_configuration/advanced_configuration/add_reviews_to_product_page',
						    'value' => '1'
						],
						[
						    'scope' => 'default',
						    'scope_id' => '0',
						    'path' => 'av_configuration/advanced_configuration/has_jquery',
						    'value' => '1'
						],
						[
						    'scope' => 'default',
						    'scope_id' => '0',
						    'path' => 'av_configuration/advanced_configuration/activate_rich_snippets',
						    'value' => 'default'
						],
						[
						    'scope' => 'default',
						    'scope_id' => '0',
						    'path' => 'av_configuration/advanced_configuration/use_parent_url',
						    'value' => '1'
						],
						[
						    'scope' => 'default',
						    'scope_id' => '0',
						    'path' => 'av_configuration/advanced_configuration/use_parent_sku',
						    'value' => '1'
						],
						[
						    'scope' => 'default',
						    'scope_id' => '0',
						    'path' => 'av_pla/fields_mapping/enable_fields',
						    'value' => '0'
						]
					);
		
		// Insert platform config in "core_config_data" table
		$connection = $this -> objectManager -> get('Magento\Framework\App\ResourceConnection') -> getConnection('\Magento\Framework\App\ResourceConnection::DEFAULT_CONNECTION');
		$tableName   = $connection -> getTableName('core_config_data'); // It will return table with prefix
		foreach ( $configData as $val ) {
			$connection -> insertOnDuplicate( $tableName , $val, ['value']);
		}
		
		return $reponse;
    }
	
	
	
	/**
     * getConfigValue
     *
     * @param $_field
     * @param $_storeId
     * @return object
     */
    public function getConfigValue( $_field, $_storeId = null )
    {
    	$storeId = $this -> getCurrentIdStoreOrIdWebsiteConfigured( $_storeId );
        return $this -> scopeConfig -> getValue(
            $_field, ScopeInterface::SCOPE_STORE, $storeId
        );
    }
	
	
	
	/**
     * getBaseConfig
     *
     * @param $code
     * @param $_storeId
     * @return string
     */
    public function getBaseConfig( $code, $_storeId = null )
    {
    	$storeId = $this -> getCurrentIdStoreOrIdWebsiteConfigured( $_storeId );
        return $this -> getConfigValue( self::XML_PATH_AVISVERIFIES . $code, $storeId );
    }
	
	
	
	/**
     * getMainConfig
     *
     * @param $code
     * @param $storeId
     * @return string
     */
    public function getMainConfig( $code, $storeId = null )
    {
    	$storeId = $this -> getCurrentIdStoreOrIdWebsiteConfigured( $storeId );
        return $this -> getConfigValue( self::XML_PATH_AVISVERIFIES . self::XML_PATH_AVISVERIFIES_MAIN . $code, $storeId );
    }
	
	
	
	/**
     * getAdvancedConfig
     *
     * @param $code
     * @param $storeId
     * @return string
     */
    public function getAdvancedConfig( $code, $storeId = null )
    {
    	$storeId = $this -> getCurrentIdStoreOrIdWebsiteConfigured( $storeId );
        return $this -> getConfigValue( self::XML_PATH_AVISVERIFIES . self::XML_PATH_AVISVERIFIES_ADVANCED . $code, $storeId );
    }
	
	
	
	/**
     * getPlatformConfig
     *
     * @param $code
     * @param $storeId
     * @return string
     */
    public function getPlatformConfig( $code, $storeId = null )
    {
    	$storeId = $this -> getCurrentIdStoreOrIdWebsiteConfigured( $storeId );
        return $this -> getConfigValue( self::XML_PATH_AVISVERIFIES . self::XML_PATH_AVISVERIFIES_PLATFORM . $code, $storeId );
    }
	
	
	
	/**
     * getSpecificPlatformConfig - RECUPERA LA CONFIGURACIÓN DE LA PLATAFORMA PARA UN UN WEBSITE O UNA TIENDA ESPECÍFICA.
     *
     * @param $code
     * @param $_id
     * @param $storeOrWebsite
     * @return string
     */
    public function getSpecificPlatformConfig( $code, $_id, $_storeOrWebsite )
    {
    	$scope = ScopeInterface::SCOPE_STORE;
		
    	if ( $_storeOrWebsite == "websites" ) {
    		$scope = ScopeInterface::SCOPE_WEBSITE;
    	}
		
    	$configValue = $this -> scopeConfig -> getValue(
            self::XML_PATH_AVISVERIFIES . self::XML_PATH_AVISVERIFIES_PLATFORM . $code,
            $scope,
            $_id
        );
        return $configValue;
    }
	
	
	
	/**
     * getSpecificPlaConfig
     * RECUPERA LA CONFIGURACIÓN PLA PARA UN WEBSITE NO PARA UNA TIENDA.
     *
     * @param $code
     * @param $_id
     * @param $storeOrWebsite
     * @return string
     */
    public function getSpecificPlaConfig( $code, $_id, $_storeOrWebsite )
    {
    	if ( $_storeOrWebsite == "stores" ) {
    		$storeManager = $this -> objectManager -> get('Magento\Store\Model\StoreManagerInterface');
			$store = $storeManager -> getStore( $_id );
			$_id = $store -> getWebsiteId();
		}
		
    	$scope = ScopeInterface::SCOPE_WEBSITE;
		
    	$configValue = $this -> scopeConfig -> getValue(
            self::XML_PATH_AVISVERIFIES_PLA . $code,
            $scope,
            $_id
        );
		
        return $configValue;
    }
	
	
	
	/**
     * getCurrentIdStoreOrIdWebsiteConfigured
	 * SI NO DAMOS EL ID DE LA TIENDA/WEBSITE TOMA EN CUENTA EL ID DE LA TIENDA/WEBSITE CON LA CONFIGURACIÓN RECUPERADA CONFORME A LA CLAVE SECRETA Y EL IDWEBSITE.
     *
     * @param $storeId
     * @return string
     */
    public function getCurrentIdStoreOrIdWebsiteConfigured( $storeId )
    {
    	if ( $storeId == null ) {
	    	$storeId = $this -> idStore;
		}
        return $storeId;
    }
	
	
	
	// Another who works fine
	/**
     * getConfig
     *
     * @param $config_path
     * @return string
     */
	/*public function getConfig($config_path) // getConfig(av_configuration/general/display_text)
	{
	    return $this->scopeConfig->getValue(
	            $config_path,
	            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
	            );
	}*/
}