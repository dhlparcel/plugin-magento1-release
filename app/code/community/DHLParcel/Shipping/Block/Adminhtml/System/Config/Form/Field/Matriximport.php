<?php
class DHLParcel_Shipping_Block_Adminhtml_System_Config_Form_Field_Matriximport
    extends Mage_Adminhtml_Block_System_Config_Form_Field
    implements Varien_Data_Form_Element_Renderer_Interface
{
    /**
     * Render the element.
     *
     * @param Varien_Data_Form_Element_Abstract $element
     *
     * @return string
     */
    public function render(Varien_Data_Form_Element_Abstract $element)
    {
        $element->setType('file')
                ->removeClass('input-text');

        $html = parent::render($element);

        return $html;
    }
}
