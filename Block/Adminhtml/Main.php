<?php
namespace Netreviews\Avisverifies\Block\Adminhtml;

class Main extends \Magento\Backend\Block\Template
{
	/**
     * Prepare layout
     *
     * @return void
     */
    function _prepareLayout() {}
	
	
	
	/**
     * LIMPIA EL HTML DE LOS DATOS PARA LA EXPORTACIÓN Y CREA EL ARCHIVO
     *
     * @param $header - encabezados
     * @param $data - datos del archivo
     * @param $seperator - Separador de columnas
     * @return object
     */
	public function contentFileCSV( $header, $data, $seperator = ';' ) {
		ob_start();
		$file = fopen("php://output", 'w');
		
		fputcsv( $file, $header, $seperator );
		
		foreach ( $data as $fields ) {
			fputcsv( $file, $fields, $seperator );
		}
		
		fclose( $file );
		
		return ob_get_clean();
	}
	
	
	
	// EJECUTA LA DERCARGA DEL ARCHIVO
	/**
     * Module install code
     *
     * @param $name - Nombre del archivo
     * @param $content - Objeto con el contenido del archivo
     * @return void
     */
	public function downloadFileCSV( $name, $content ) {
		header('Content-Description: File Transfer');
		header('Content-Type: application/octet-stream');
		header('Content-Disposition: attachment; filename=' . $name . '.csv');
		header('Content-Transfer-Encoding: binary');
		header('Expires: 0');
		header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
		header('Pragma: public');

		printf( chr(239) . chr(187) . chr(191) . $content );

		//exit;
	}
	
	
	
	/**
     * RECUPERAR DATOS DEL FORMULARIO EXPORT
     *
     * @return object
     */
	public function getExportFormData() {
		$o_postExport = $this -> getRequest() -> getPost();
		return $o_postExport;
	}
	
	
	
	/**
     * OBTENER LA FECHA DE HOY CON EL FORMATO DE MAGENTO
     *
     * @return date
     */
	public function getMagentoCurrentDate() {
		$o_objectManager = \Magento\Framework\App\ObjectManager::getInstance();
		$magentoDateObject = $o_objectManager -> create('Magento\Framework\Stdlib\DateTime\DateTime');
		$magentoDateNow = $magentoDateObject -> gmtDate();
		//$dateFormat = $magentoDateNow -> format('Y-m-d H:i:s');
		
		return $magentoDateNow;
	}
	
	
	
	/**
     * VERIFICAR SI EL VALOR DADO ES UNA FECHA EN FORMATO ALGUN FORMATO CON GUION
     *
     * @return boolean
     */
	public function isDate( $_date ) {
		if ( strpos( $_date, '-' ) !== false ) {
		    return true;
		} else {
			return false;
		}
	}
	
}
