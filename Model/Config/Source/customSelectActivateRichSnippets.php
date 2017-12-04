<?php
namespace Netreviews\Avisverifies\Model\Config\Source;

class customSelectActivateRichSnippets implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => 'default', 'label' => __('No rich-snippets')],
            ['value' => 'schema', 'label' => __('Rich-snippets using schema.org format')],
            //['value' => 'rdfa', 'label' => __('Rich-snippets using RDFa format')],
        ];
    }
}
