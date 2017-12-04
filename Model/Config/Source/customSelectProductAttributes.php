<?php
namespace Netreviews\Avisverifies\Model\Config\Source;

class customSelectProductAttributes implements \Magento\Framework\Option\ArrayInterface
{
	protected $_attributeFactory;
	
	public function __construct( \Magento\Catalog\Model\ResourceModel\Eav\Attribute $attributeFactory ) {
	    $this -> _attributeFactory = $attributeFactory;
	}
	
	public function getProductAttributes()
	{
            $attributeInfo = $this -> _attributeFactory -> getCollection();
            $attributes = [];

            foreach( $attributeInfo as $attributes )
            {
                    // You can get all fields of attribute here
                    $a_attributes[] = $attributes -> getAttributeCode();
            }

            return $a_attributes;
	}
	
    /**
     * @return array
     */
    public function toOptionArray()
    {
        $allAttributes = $this -> getProductAttributes();
        array_unshift( $allAttributes , '---' );
        
        $attributes = array();
        foreach ( $allAttributes as $i => $att ) {
                $attributes[$i]['value'] = $att;
                $attributes[$i]['label'] = __( $att );
        }

        //print("<pre>".print_r($allAttributes,true)."</pre>");
		
        return $attributes;
    }

}